<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\InventoryBatch;
use App\Models\Product;
use Illuminate\Support\Facades\DB;

/**
 * CostingService - Inventory costing methods (FIFO, LIFO, Weighted Average, Standard)
 * 
 * STATUS: ACTIVE - Production-ready inventory costing service
 * PURPOSE: Calculate inventory costs based on configurable costing methods
 * METHODS: Supports FIFO, LIFO, Weighted Average, and Standard costing
 * USAGE: Called by inventory/stock services for cost calculations
 * 
 * This service is fully implemented and provides critical inventory valuation
 * functionality for the ERP system.
 */
class CostingService
{
    /**
     * Calculate cost for stock movement based on product's costing method
     * Falls back to system-wide default costing method from settings
     */
    public function calculateCost(
        Product $product,
        int $warehouseId,
        float $quantity
    ): array {
        // Use product-specific method if set, otherwise use system default from settings
        $costMethod = $product->cost_method 
            ?? strtolower(setting('inventory.costing_method', 'weighted_average'));

        return match ($costMethod) {
            'fifo', 'FIFO' => $this->calculateFifoCost($product->id, $warehouseId, $quantity),
            'lifo', 'LIFO' => $this->calculateLifoCost($product->id, $warehouseId, $quantity),
            'weighted_average', 'AVG' => $this->calculateWeightedAverageCost($product->id, $warehouseId, $quantity),
            'standard' => $this->calculateStandardCost($product, $quantity),
            default => $this->calculateWeightedAverageCost($product->id, $warehouseId, $quantity),
        };
    }

    /**
     * FIFO: First In, First Out
     * Uses the cost of the oldest batches first
     */
    protected function calculateFifoCost(int $productId, int $warehouseId, float $quantity): array
    {
        $batches = InventoryBatch::where('product_id', $productId)
            ->where('warehouse_id', $warehouseId)
            ->active()
            ->orderBy('created_at', 'asc')
            ->get();

        return $this->allocateCostFromBatches($batches, $quantity);
    }

    /**
     * LIFO: Last In, First Out
     * Uses the cost of the newest batches first
     */
    protected function calculateLifoCost(int $productId, int $warehouseId, float $quantity): array
    {
        $batches = InventoryBatch::where('product_id', $productId)
            ->where('warehouse_id', $warehouseId)
            ->active()
            ->orderBy('created_at', 'desc')
            ->get();

        return $this->allocateCostFromBatches($batches, $quantity);
    }

    /**
     * Weighted Average: Calculate average cost across all batches
     */
    protected function calculateWeightedAverageCost(int $productId, int $warehouseId, float $quantity): array
    {
        $result = InventoryBatch::where('product_id', $productId)
            ->where('warehouse_id', $warehouseId)
            ->active()
            ->selectRaw('SUM(quantity * unit_cost) as total_value, SUM(quantity) as total_quantity')
            ->first();

        $totalQuantity = (string) ($result->total_quantity ?? 0);
        $totalValue = (string) ($result->total_value ?? 0);

        if (bccomp($totalQuantity, '0', 4) <= 0) {
            return [
                'unit_cost' => 0.0,
                'total_cost' => 0.0,
                'batches_used' => [],
            ];
        }

        $avgCost = bcdiv($totalValue, $totalQuantity, 4);
        $totalCost = bcmul($avgCost, (string) $quantity, 4);

        return [
            'unit_cost' => (float) $avgCost,
            'total_cost' => (float) bcdiv($totalCost, '1', 2),
            'batches_used' => [],
        ];
    }

    /**
     * Standard Cost: Use the product's standard cost
     */
    protected function calculateStandardCost(Product $product, float $quantity): array
    {
        $unitCost = (float) $product->standard_cost;

        return [
            'unit_cost' => $unitCost,
            'total_cost' => $unitCost * $quantity,
            'batches_used' => [],
        ];
    }

    /**
     * Allocate cost from batches based on order (FIFO/LIFO)
     */
    protected function allocateCostFromBatches($batches, float $quantityNeeded): array
    {
        $totalCost = '0';
        $remainingQty = (string) $quantityNeeded;
        $batchesUsed = [];

        foreach ($batches as $batch) {
            if (bccomp($remainingQty, '0', 4) <= 0) {
                break;
            }

            $batchQuantity = (string) $batch->quantity;
            $batchQty = bccomp($remainingQty, $batchQuantity, 4) < 0 ? $remainingQty : $batchQuantity;
            $batchCost = bcmul($batchQty, (string) $batch->unit_cost, 4);

            $totalCost = bcadd($totalCost, $batchCost, 4);
            $remainingQty = bcsub($remainingQty, $batchQty, 4);

            $batchesUsed[] = [
                'batch_id' => $batch->id,
                'batch_number' => $batch->batch_number,
                'quantity' => (float) $batchQty,
                'unit_cost' => (float) $batch->unit_cost,
                'total_cost' => (float) bcdiv($batchCost, '1', 2),
            ];
        }

        $unitCost = $quantityNeeded > 0 ? bcdiv($totalCost, (string) $quantityNeeded, 4) : '0';

        return [
            'unit_cost' => (float) $unitCost,
            'total_cost' => (float) bcdiv($totalCost, '1', 2),
            'batches_used' => $batchesUsed,
        ];
    }

    /**
     * Update batch quantities after stock movement
     */
    public function consumeBatches(array $batchesUsed): void
    {
        DB::transaction(function () use ($batchesUsed) {
            foreach ($batchesUsed as $batchInfo) {
                $batch = InventoryBatch::lockForUpdate()->find($batchInfo['batch_id']);
                if ($batch) {
                    $newQuantity = $batch->quantity - $batchInfo['quantity'];
                    $batch->quantity = max(0, $newQuantity);
                    
                    if ($batch->quantity <= 0) {
                        $batch->status = 'depleted';
                    }
                    
                    $batch->save();
                }
            }
        });
    }

    /**
     * Create or update batch for incoming stock
     */
    public function addToBatch(
        int $productId,
        int $warehouseId,
        int $branchId,
        float $quantity,
        float $unitCost,
        ?string $batchNumber = null,
        ?array $batchData = []
    ): InventoryBatch {
        if (!$batchNumber) {
            $batchNumber = 'BATCH-' . date('Ymd') . '-' . uniqid();
        }

        $batch = InventoryBatch::firstOrNew([
            'product_id' => $productId,
            'warehouse_id' => $warehouseId,
            'batch_number' => $batchNumber,
        ]);
        
        if ($batch->exists) {
            // Update existing batch - increment quantity
            $batch->quantity = $batch->quantity + $quantity;
        } else {
            // New batch
            $batch->fill(array_merge([
                'branch_id' => $branchId,
                'quantity' => $quantity,
                'unit_cost' => $unitCost,
                'status' => 'active',
            ], $batchData));
        }
        
        $batch->save();
        return $batch;
    }
}
