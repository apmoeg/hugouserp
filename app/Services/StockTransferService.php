<?php

namespace App\Services;

use App\Models\StockMovement;
use App\Models\StockTransfer;
use App\Models\StockTransferItem;
use App\Traits\HandlesServiceErrors;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class StockTransferService
{
    use HandlesServiceErrors;

    public function __construct(
        protected StockService $stockService
    ) {}

    /**
     * Create a new stock transfer request
     */
    public function createTransfer(array $data): StockTransfer
    {
        return $this->handleServiceOperation(
            callback: fn() => DB::transaction(function () use ($data) {
                // Validate different warehouses
                abort_if(
                    $data['from_warehouse_id'] === $data['to_warehouse_id'],
                    422,
                    'Cannot transfer to the same warehouse'
                );

                // Create transfer
                $transfer = StockTransfer::create([
                    'from_warehouse_id' => $data['from_warehouse_id'],
                    'to_warehouse_id' => $data['to_warehouse_id'],
                    'from_branch_id' => $data['from_branch_id'] ?? null,
                    'to_branch_id' => $data['to_branch_id'] ?? null,
                    'transfer_type' => $this->determineTransferType($data),
                    'status' => StockTransfer::STATUS_PENDING,
                    'transfer_date' => $data['transfer_date'] ?? now()->toDateString(),
                    'expected_delivery_date' => $data['expected_delivery_date'] ?? null,
                    'priority' => $data['priority'] ?? StockTransfer::PRIORITY_MEDIUM,
                    'reason' => $data['reason'] ?? null,
                    'notes' => $data['notes'] ?? null,
                    'shipping_cost' => $data['shipping_cost'] ?? 0,
                    'insurance_cost' => $data['insurance_cost'] ?? 0,
                    'total_cost' => ($data['shipping_cost'] ?? 0) + ($data['insurance_cost'] ?? 0),
                    'currency' => $data['currency'] ?? 'EGP',
                    'requested_by' => auth()->id(),
                    'created_by' => auth()->id(),
                ]);

                // Add items
                foreach ($data['items'] as $itemData) {
                    // Validate stock availability
                    $availableStock = $this->stockService->getCurrentStock(
                        $itemData['product_id'],
                        $data['from_warehouse_id']
                    );

                    $requestedQty = (float)($itemData['qty'] ?? 0);

                    abort_if(
                        $availableStock < $requestedQty,
                        422,
                        "Insufficient stock for product ID {$itemData['product_id']}. Available: {$availableStock}, Requested: {$requestedQty}"
                    );

                    StockTransferItem::create([
                        'stock_transfer_id' => $transfer->id,
                        'product_id' => $itemData['product_id'],
                        'qty_requested' => $requestedQty,
                        'qty_approved' => $requestedQty, // Auto-approve quantity initially
                        'batch_number' => $itemData['batch_number'] ?? null,
                        'expiry_date' => $itemData['expiry_date'] ?? null,
                        'unit_cost' => $itemData['unit_cost'] ?? 0,
                        'condition_on_shipping' => $itemData['condition'] ?? 'good',
                        'notes' => $itemData['notes'] ?? null,
                    ]);
                }

                // Calculate totals
                $transfer->calculateTotals();

                Log::info('Stock transfer created', [
                    'transfer_id' => $transfer->id,
                    'transfer_number' => $transfer->transfer_number,
                    'from_warehouse' => $data['from_warehouse_id'],
                    'to_warehouse' => $data['to_warehouse_id'],
                ]);

                return $transfer->load(['items.product', 'fromWarehouse', 'toWarehouse']);
            }),
            operation: 'create_transfer',
            context: $data
        );
    }

    /**
     * Approve a stock transfer
     */
    public function approveTransfer(int $transferId, ?int $userId = null): StockTransfer
    {
        return $this->handleServiceOperation(
            callback: fn() => DB::transaction(function () use ($transferId, $userId) {
                $transfer = StockTransfer::with(['items.product'])->findOrFail($transferId);
                $userId = $userId ?? auth()->id();

                abort_if(
                    !$transfer->canBeApproved(),
                    422,
                    "Transfer {$transfer->transfer_number} cannot be approved in {$transfer->status} status"
                );

                // Re-validate stock availability before approval
                foreach ($transfer->items as $item) {
                    $availableStock = $this->stockService->getCurrentStock(
                        $item->product_id,
                        $transfer->from_warehouse_id
                    );

                    abort_if(
                        $availableStock < $item->qty_approved,
                        422,
                        "Insufficient stock for {$item->product->name}. Available: {$availableStock}, Required: {$item->qty_approved}"
                    );
                }

                $transfer->approve($userId);

                Log::info('Stock transfer approved', [
                    'transfer_id' => $transfer->id,
                    'transfer_number' => $transfer->transfer_number,
                    'approved_by' => $userId,
                ]);

                return $transfer->refresh();
            }),
            operation: 'approve_transfer',
            context: ['transfer_id' => $transferId]
        );
    }

    /**
     * Ship/dispatch the transfer
     */
    public function shipTransfer(int $transferId, array $shippingData): StockTransfer
    {
        return $this->handleServiceOperation(
            callback: fn() => DB::transaction(function () use ($transferId, $shippingData) {
                $transfer = StockTransfer::with(['items.product'])->findOrFail($transferId);
                $userId = auth()->id();

                abort_if(
                    !$transfer->canBeShipped(),
                    422,
                    "Transfer {$transfer->transfer_number} cannot be shipped in {$transfer->status} status"
                );

                // Deduct stock from source warehouse
                foreach ($transfer->items as $item) {
                    $qtyToShip = $shippingData['items'][$item->id]['qty_shipped'] ?? $item->qty_approved;

                    // Update item shipped quantity
                    $item->update(['qty_shipped' => $qtyToShip]);

                    // Deduct from source warehouse
                    $this->stockService->adjustStock(
                        productId: $item->product_id,
                        warehouseId: $transfer->from_warehouse_id,
                        quantity: -$qtyToShip, // Negative for deduction
                        type: StockMovement::TYPE_TRANSFER_OUT,
                        reference: "Transfer Out: {$transfer->transfer_number}",
                        notes: "Transferred to " . $transfer->toWarehouse->name
                    );
                }

                // Update transfer totals
                $transfer->calculateTotals();

                // Mark as shipped
                $transfer->markAsShipped($userId, $shippingData);

                Log::info('Stock transfer shipped', [
                    'transfer_id' => $transfer->id,
                    'transfer_number' => $transfer->transfer_number,
                    'tracking_number' => $shippingData['tracking_number'] ?? null,
                ]);

                return $transfer->refresh();
            }),
            operation: 'ship_transfer',
            context: ['transfer_id' => $transferId, 'shipping_data' => $shippingData]
        );
    }

    /**
     * Receive the transfer at destination
     */
    public function receiveTransfer(int $transferId, array $receivingData): StockTransfer
    {
        return $this->handleServiceOperation(
            callback: fn() => DB::transaction(function () use ($transferId, $receivingData) {
                $transfer = StockTransfer::with(['items.product'])->findOrFail($transferId);
                $userId = auth()->id();

                abort_if(
                    !$transfer->canBeReceived(),
                    422,
                    "Transfer {$transfer->transfer_number} cannot be received in {$transfer->status} status"
                );

                // Process received items
                foreach ($transfer->items as $item) {
                    $itemReceivingData = $receivingData['items'][$item->id] ?? [];
                    
                    $qtyReceived = (float)($itemReceivingData['qty_received'] ?? $item->qty_shipped);
                    $qtyDamaged = (float)($itemReceivingData['qty_damaged'] ?? 0);
                    $qtyGood = $qtyReceived - $qtyDamaged;

                    // Update item
                    $item->update([
                        'qty_received' => $qtyReceived,
                        'qty_damaged' => $qtyDamaged,
                        'condition_on_receiving' => $itemReceivingData['condition'] ?? 'good',
                        'damage_report' => $itemReceivingData['damage_report'] ?? null,
                    ]);

                    // Add good stock to destination warehouse
                    if ($qtyGood > 0) {
                        $this->stockService->adjustStock(
                            productId: $item->product_id,
                            warehouseId: $transfer->to_warehouse_id,
                            quantity: $qtyGood,
                            type: StockMovement::TYPE_TRANSFER_IN,
                            reference: "Transfer In: {$transfer->transfer_number}",
                            notes: "Transferred from " . $transfer->fromWarehouse->name
                        );
                    }

                    // Record damaged items separately if any
                    if ($qtyDamaged > 0) {
                        $this->stockService->adjustStock(
                            productId: $item->product_id,
                            warehouseId: $transfer->to_warehouse_id,
                            quantity: $qtyDamaged,
                            type: StockMovement::TYPE_ADJUSTMENT,
                            reference: "Transfer Damage: {$transfer->transfer_number}",
                            notes: "Damaged during transfer - " . ($itemReceivingData['damage_report'] ?? 'No details')
                        );
                    }
                }

                // Update transfer totals
                $transfer->calculateTotals();

                // Mark as received
                $transfer->markAsReceived($userId);

                // Auto-complete if all items fully received
                if ($this->isFullyReceived($transfer)) {
                    $transfer->complete();
                }

                Log::info('Stock transfer received', [
                    'transfer_id' => $transfer->id,
                    'transfer_number' => $transfer->transfer_number,
                    'received_by' => $userId,
                    'total_received' => $transfer->total_qty_received,
                    'total_damaged' => $transfer->total_qty_damaged,
                ]);

                return $transfer->refresh();
            }),
            operation: 'receive_transfer',
            context: ['transfer_id' => $transferId, 'receiving_data' => $receivingData]
        );
    }

    /**
     * Reject a transfer
     */
    public function rejectTransfer(int $transferId, ?string $reason = null, ?int $userId = null): StockTransfer
    {
        return $this->handleServiceOperation(
            callback: fn() => DB::transaction(function () use ($transferId, $reason, $userId) {
                $transfer = StockTransfer::findOrFail($transferId);
                $userId = $userId ?? auth()->id();

                abort_if(
                    $transfer->status !== StockTransfer::STATUS_PENDING,
                    422,
                    "Transfer {$transfer->transfer_number} cannot be rejected in {$transfer->status} status"
                );

                $transfer->reject($userId, $reason);

                Log::info('Stock transfer rejected', [
                    'transfer_id' => $transfer->id,
                    'transfer_number' => $transfer->transfer_number,
                    'reason' => $reason,
                    'rejected_by' => $userId,
                ]);

                return $transfer->refresh();
            }),
            operation: 'reject_transfer',
            context: ['transfer_id' => $transferId, 'reason' => $reason]
        );
    }

    /**
     * Cancel a transfer
     */
    public function cancelTransfer(int $transferId, ?string $reason = null, ?int $userId = null): StockTransfer
    {
        return $this->handleServiceOperation(
            callback: fn() => DB::transaction(function () use ($transferId, $reason, $userId) {
                $transfer = StockTransfer::with(['items'])->findOrFail($transferId);
                $userId = $userId ?? auth()->id();

                $oldStatus = $transfer->status;

                // If already shipped, need to return stock to source
                if ($transfer->status === StockTransfer::STATUS_IN_TRANSIT) {
                    foreach ($transfer->items as $item) {
                        if ($item->qty_shipped > 0) {
                            // Return stock to source warehouse
                            $this->stockService->adjustStock(
                                productId: $item->product_id,
                                warehouseId: $transfer->from_warehouse_id,
                                quantity: $item->qty_shipped,
                                type: StockMovement::TYPE_ADJUSTMENT,
                                reference: "Transfer Cancelled: {$transfer->transfer_number}",
                                notes: "Stock returned due to cancellation"
                            );
                        }
                    }
                }

                $transfer->cancel($userId, $reason);

                Log::info('Stock transfer cancelled', [
                    'transfer_id' => $transfer->id,
                    'transfer_number' => $transfer->transfer_number,
                    'old_status' => $oldStatus,
                    'reason' => $reason,
                    'cancelled_by' => $userId,
                ]);

                return $transfer->refresh();
            }),
            operation: 'cancel_transfer',
            context: ['transfer_id' => $transferId, 'reason' => $reason]
        );
    }

    /**
     * Get transfer statistics
     */
    public function getTransferStatistics(?int $warehouseId = null, ?string $startDate = null, ?string $endDate = null): array
    {
        $query = StockTransfer::query();

        if ($warehouseId) {
            $query->where(function ($q) use ($warehouseId) {
                $q->where('from_warehouse_id', $warehouseId)
                  ->orWhere('to_warehouse_id', $warehouseId);
            });
        }

        if ($startDate) {
            $query->whereDate('transfer_date', '>=', $startDate);
        }

        if ($endDate) {
            $query->whereDate('transfer_date', '<=', $endDate);
        }

        return [
            'total_transfers' => $query->count(),
            'pending_transfers' => (clone $query)->where('status', StockTransfer::STATUS_PENDING)->count(),
            'approved_transfers' => (clone $query)->where('status', StockTransfer::STATUS_APPROVED)->count(),
            'in_transit_transfers' => (clone $query)->where('status', StockTransfer::STATUS_IN_TRANSIT)->count(),
            'completed_transfers' => (clone $query)->where('status', StockTransfer::STATUS_COMPLETED)->count(),
            'overdue_transfers' => (clone $query)->where('expected_delivery_date', '<', now())->whereNotIn('status', [StockTransfer::STATUS_COMPLETED, StockTransfer::STATUS_CANCELLED])->count(),
            'total_qty_transferred' => (clone $query)->where('status', StockTransfer::STATUS_COMPLETED)->sum('total_qty_received'),
            'total_cost' => (clone $query)->sum('total_cost'),
        ];
    }

    /**
     * Helper methods
     */
    protected function determineTransferType(array $data): string
    {
        if (isset($data['from_branch_id']) && isset($data['to_branch_id']) && $data['from_branch_id'] !== $data['to_branch_id']) {
            return StockTransfer::TYPE_INTER_BRANCH;
        }

        return StockTransfer::TYPE_INTER_WAREHOUSE;
    }

    protected function isFullyReceived(StockTransfer $transfer): bool
    {
        foreach ($transfer->items as $item) {
            if (!$item->isFullyReceived()) {
                return false;
            }
        }

        return true;
    }
}
