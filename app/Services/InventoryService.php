<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\InvalidQuantityException;
use App\Models\Product;
use App\Models\Warehouse;
use App\Repositories\Contracts\StockLevelRepositoryInterface;
use App\Repositories\Contracts\StockMovementRepositoryInterface;
use App\Services\Contracts\InventoryServiceInterface;
use App\Traits\HandlesServiceErrors;
use App\Traits\HasRequestContext;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Factory as ValidatorFactory;

class InventoryService implements InventoryServiceInterface
{
    use HandlesServiceErrors;
    use HasRequestContext;

    public function __construct(
        protected ValidatorFactory $validator,
        protected StockMovementRepositoryInterface $movements,
        protected StockLevelRepositoryInterface $stockLevels,
    ) {}

    public function currentQty(int $productId, ?int $warehouseId = null): float
    {
        return $this->handleServiceOperation(
            callback: function () use ($productId, $warehouseId) {
                $branchId = $this->currentBranchId();

                if ($branchId === null) {
                    $this->logServiceWarning('currentQty', 'Called without branch context', [
                        'product_id' => $productId,
                        'warehouse_id' => $warehouseId,
                    ]);

                    return 0.0;
                }

                if ($warehouseId !== null) {
                    $perWarehouse = $this->movements->currentStockPerWarehouse($branchId, $productId);

                    return (float) ($perWarehouse->get($warehouseId, 0.0));
                }

                return $this->stockLevels->getForProduct($branchId, $productId);
            },
            operation: 'currentQty',
            context: ['product_id' => $productId, 'warehouse_id' => $warehouseId],
            defaultValue: 0.0
        );
    }

    public function adjust(
        int $productId,
        int $warehouseId,
        float $qty,
        string $direction,
        ?string $reason = null,
        ?array $meta = null,
    ): void {
        $this->handleServiceOperation(
            callback: function () use ($productId, $warehouseId, $qty, $direction, $reason, $meta) {
                $branchId = $this->currentBranchId();

                if ($branchId === null) {
                    throw new InvalidQuantityException('Branch context is required for inventory adjustments.', 422);
                }

                if (abs($qty) < 1e-9) {
                    throw new InvalidQuantityException('Qty cannot be zero.', 422);
                }

                if (! in_array($direction, ['in', 'out'], true)) {
                    throw new InvalidQuantityException('Invalid movement direction.', 422);
                }

                $data = [
                    'product_id' => $productId,
                    'warehouse_id' => $warehouseId,
                    'qty' => $qty,
                    'direction' => $direction,
                    'reason' => $reason,
                    'meta' => $meta ?? [],
                ];

                $validator = $this->validator->make($data, [
                    'product_id' => ['required', 'integer', 'exists:products,id'],
                    'warehouse_id' => ['required', 'integer', 'exists:warehouses,id'],
                    'qty' => ['required', 'numeric'],
                    'direction' => ['required', 'in:in,out'],
                ]);

                $validator->validate();

                DB::transaction(function () use ($branchId, $data): void {
                    $product = Product::query()
                        ->where('branch_id', $branchId)
                        ->lockForUpdate()
                        ->findOrFail($data['product_id']);

                    $warehouse = Warehouse::query()
                        ->where('branch_id', $branchId)
                        ->lockForUpdate()
                        ->findOrFail($data['warehouse_id']);

                    $qty = (float) $data['qty'];
                    $direction = $data['direction'];

                    if ($direction === 'out' && $qty > 0) {
                        $qty = -$qty;
                    }

                    $movementData = [
                        'branch_id' => $branchId,
                        'product_id' => $product->getKey(),
                        'warehouse_id' => $warehouse->getKey(),
                        'qty' => abs($qty),
                        'direction' => $direction,
                        'reason' => $data['reason'],
                        'meta' => $data['meta'] ?? [],
                        'created_by' => $this->currentUser()?->getAuthIdentifier(),
                    ];

                    $this->movements->create($movementData);
                });

                $this->logServiceInfo('adjust', 'Inventory adjusted', [
                    'product_id' => $productId,
                    'warehouse_id' => $warehouseId,
                    'qty' => $qty,
                    'direction' => $direction,
                ]);
            },
            operation: 'adjust',
            context: [
                'product_id' => $productId,
                'warehouse_id' => $warehouseId,
                'qty' => $qty,
                'direction' => $direction,
                'reason' => $reason,
            ]
        );
    }

    public function transfer(
        int $productId,
        int $fromWarehouseId,
        int $toWarehouseId,
        float $qty,
        ?string $reason = null,
    ): void {
        $this->handleServiceOperation(
            callback: function () use ($productId, $fromWarehouseId, $toWarehouseId, $qty, $reason) {
                $branchId = $this->currentBranchId();

                if ($branchId === null) {
                    throw new InvalidQuantityException('Branch context is required for inventory transfers.', 422);
                }

                if ($fromWarehouseId === $toWarehouseId) {
                    throw new InvalidQuantityException('Source and destination warehouses must be different.', 422);
                }

                if ($qty <= 0) {
                    throw new InvalidQuantityException('Qty must be positive for transfer.', 422);
                }

                DB::transaction(function () use ($branchId, $productId, $fromWarehouseId, $toWarehouseId, $qty, $reason): void {
                    $product = Product::query()
                        ->where('branch_id', $branchId)
                        ->lockForUpdate()
                        ->findOrFail($productId);

                    $fromWarehouse = Warehouse::query()
                        ->where('branch_id', $branchId)
                        ->lockForUpdate()
                        ->findOrFail($fromWarehouseId);

                    $toWarehouse = Warehouse::query()
                        ->where('branch_id', $branchId)
                        ->lockForUpdate()
                        ->findOrFail($toWarehouseId);

                    $userId = $this->currentUser()?->getAuthIdentifier();

                    $this->movements->create([
                        'branch_id' => $branchId,
                        'product_id' => $product->getKey(),
                        'warehouse_id' => $fromWarehouse->getKey(),
                        'qty' => $qty,
                        'direction' => 'out',
                        'reason' => $reason ?? 'transfer',
                        'meta' => [
                            'type' => 'transfer',
                            'direction_label' => 'from',
                            'to_warehouse_id' => $toWarehouse->getKey(),
                        ],
                        'created_by' => $userId,
                    ]);

                    $this->movements->create([
                        'branch_id' => $branchId,
                        'product_id' => $product->getKey(),
                        'warehouse_id' => $toWarehouse->getKey(),
                        'qty' => $qty,
                        'direction' => 'in',
                        'reason' => $reason ?? 'transfer',
                        'meta' => [
                            'type' => 'transfer',
                            'direction_label' => 'to',
                            'from_warehouse_id' => $fromWarehouse->getKey(),
                        ],
                        'created_by' => $userId,
                    ]);
                });

                $this->logServiceInfo('transfer', 'Inventory transferred', [
                    'product_id' => $productId,
                    'from_warehouse_id' => $fromWarehouseId,
                    'to_warehouse_id' => $toWarehouseId,
                    'qty' => $qty,
                ]);
            },
            operation: 'transfer',
            context: [
                'product_id' => $productId,
                'from_warehouse_id' => $fromWarehouseId,
                'to_warehouse_id' => $toWarehouseId,
                'qty' => $qty,
                'reason' => $reason,
            ]
        );
    }
}
