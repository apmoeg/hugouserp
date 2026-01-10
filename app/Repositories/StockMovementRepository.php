<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\StockMovement;
use App\Repositories\Contracts\StockMovementRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

final class StockMovementRepository extends EloquentBaseRepository implements StockMovementRepositoryInterface
{
    public function __construct(StockMovement $model)
    {
        parent::__construct($model);
    }

    protected function baseQuery(): Builder
    {
        return $this->query();
    }

    public function paginateForBranch(int $branchId, array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        // Note: stock_movements table doesn't have branch_id - filter via warehouse
        $query = $this->baseQuery();

        if (! empty($filters['product_id'])) {
            $query->where('product_id', (int) $filters['product_id']);
        }

        if (! empty($filters['warehouse_id'])) {
            $query->where('warehouse_id', (int) $filters['warehouse_id']);
        }

        if (! empty($filters['movement_type'])) {
            $query->where('movement_type', $filters['movement_type']);
        }

        if (! empty($filters['from_date'])) {
            $query->whereDate('created_at', '>=', $filters['from_date']);
        }

        if (! empty($filters['to_date'])) {
            $query->whereDate('created_at', '<=', $filters['to_date']);
        }

        // Filter by branch through warehouse relationship
        $query->whereHas('warehouse', function ($q) use ($branchId) {
            $q->where('branch_id', $branchId);
        });

        return $query->orderByDesc('id')->paginate($perPage);
    }

    public function movementsForProduct(int $branchId, int $productId, array $filters = [], int $perPage = 50): LengthAwarePaginator
    {
        $filters['product_id'] = $productId;

        return $this->paginateForBranch($branchId, $filters, $perPage);
    }

    public function summaryForProduct(int $branchId, int $productId): array
    {
        $baseQuery = $this->baseQuery()
            ->where('product_id', $productId)
            ->whereHas('warehouse', fn ($q) => $q->where('branch_id', $branchId));

        // quantity > 0 = in, quantity < 0 = out
        $in = (float) (clone $baseQuery)->where('quantity', '>', 0)->sum('quantity');
        $out = (float) abs((clone $baseQuery)->where('quantity', '<', 0)->sum('quantity'));

        return [
            'in' => $in,
            'out' => $out,
            'net' => $in - $out,
        ];
    }

    public function currentStockForBranch(int $branchId, int $productId): float
    {
        $baseQuery = $this->baseQuery()
            ->where('product_id', $productId)
            ->whereHas('warehouse', fn ($q) => $q->where('branch_id', $branchId));

        // Sum all quantities (positive = in, negative = out)
        return (float) $baseQuery->sum('quantity');
    }

    public function currentStockPerWarehouse(int $branchId, int $productId): Collection
    {
        $movements = $this->baseQuery()
            ->where('product_id', $productId)
            ->whereHas('warehouse', fn ($q) => $q->where('branch_id', $branchId))
            ->get(['warehouse_id', 'quantity']);

        $map = $movements->groupBy('warehouse_id')
            ->map(function ($group) {
                return (float) $group->sum('quantity');
            });

        return $map;
    }

    /**
     * Create a stock movement with proper column mapping
     * Uses pessimistic locking to prevent race conditions in high-concurrency scenarios
     */
    public function create(array $data): StockMovement
    {
        // Use transaction with pessimistic locking to prevent race conditions
        return DB::transaction(function () use ($data) {
            // Map legacy field names to new schema
            $mappedData = [
                'product_id' => $data['product_id'],
                'warehouse_id' => $data['warehouse_id'],
                'movement_type' => $data['movement_type'] ?? $data['reason'] ?? 'adjustment',
                'reference_type' => $data['reference_type'] ?? null,
                'reference_id' => $data['reference_id'] ?? null,
                'notes' => $data['notes'] ?? $data['reason'] ?? null,
                'created_by' => $data['created_by'] ?? null,
            ];

            // Handle quantity: direction 'out' should be negative
            $qty = abs((float) ($data['qty'] ?? $data['quantity'] ?? 0));
            $direction = $data['direction'] ?? 'in';

            if ($direction === 'out') {
                $qty = -$qty;
            }
            $mappedData['quantity'] = $qty;

            // Calculate stock_before and stock_after with pessimistic locking
            // Lock the latest stock movement record to prevent concurrent modifications
            // This ensures that when two concurrent transactions try to create stock movements,
            // they will be serialized: the second one will wait until the first completes
            StockMovement::where('product_id', $data['product_id'])
                ->where('warehouse_id', $data['warehouse_id'])
                ->orderByDesc('id')
                ->lockForUpdate()
                ->first();

            // Calculate current stock from all movements
            $currentStock = (float) StockMovement::where('product_id', $data['product_id'])
                ->where('warehouse_id', $data['warehouse_id'])
                ->sum('quantity');

            $mappedData['stock_before'] = $currentStock;
            $mappedData['stock_after'] = $currentStock + $qty;
            $mappedData['unit_cost'] = $data['unit_cost'] ?? null;

            return StockMovement::create($mappedData);
        });
    }
}
