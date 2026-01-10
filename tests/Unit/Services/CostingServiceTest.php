<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Models\Branch;
use App\Models\Product;
use App\Models\Warehouse;
use App\Services\CostingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CostingServiceTest extends TestCase
{
    use RefreshDatabase;

    protected CostingService $service;

    protected Branch $branch;

    protected Warehouse $warehouse;

    protected Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(CostingService::class);

        $this->branch = Branch::create([
            'name' => 'Test Branch',
            'code' => 'TB001',
        ]);

        $this->warehouse = Warehouse::create([
            'name' => 'Main Warehouse',
            'code' => 'WH001',
            'branch_id' => $this->branch->id,
        ]);

        $this->product = Product::create([
            'name' => 'Test Product',
            'code' => 'PRD001',
            'sku' => 'SKU001',
            'type' => 'stock',
            'default_price' => 100,
            'standard_cost' => 50,
            'branch_id' => $this->branch->id,
        ]);
    }

    public function test_weighted_average_cost_is_calculated_correctly(): void
    {
        // First batch: 10 units at 100 each
        $batch1 = $this->service->addToBatch(
            productId: $this->product->id,
            warehouseId: $this->warehouse->id,
            branchId: $this->branch->id,
            quantity: 10,
            unitCost: 100,
            batchNumber: 'BATCH-001'
        );

        $this->assertEquals(10, $batch1->quantity);
        $this->assertEquals(100, $batch1->unit_cost);

        // Second batch: 10 units at 200 each (same batch number to trigger weighted average)
        $batch2 = $this->service->addToBatch(
            productId: $this->product->id,
            warehouseId: $this->warehouse->id,
            branchId: $this->branch->id,
            quantity: 10,
            unitCost: 200,
            batchNumber: 'BATCH-001'
        );

        // Weighted average should be (10*100 + 10*200) / (10+10) = 3000/20 = 150
        $this->assertEquals(20, $batch2->quantity);
        $this->assertEquals(150, $batch2->unit_cost);
    }

    public function test_weighted_average_cost_handles_different_quantities(): void
    {
        // First batch: 5 units at 100 each
        $batch1 = $this->service->addToBatch(
            productId: $this->product->id,
            warehouseId: $this->warehouse->id,
            branchId: $this->branch->id,
            quantity: 5,
            unitCost: 100,
            batchNumber: 'BATCH-002'
        );

        $this->assertEquals(100, $batch1->unit_cost);

        // Second batch: 15 units at 200 each
        $batch2 = $this->service->addToBatch(
            productId: $this->product->id,
            warehouseId: $this->warehouse->id,
            branchId: $this->branch->id,
            quantity: 15,
            unitCost: 200,
            batchNumber: 'BATCH-002'
        );

        // Weighted average should be (5*100 + 15*200) / (5+15) = 3500/20 = 175
        $this->assertEquals(20, $batch2->quantity);
        $this->assertEquals(175, $batch2->unit_cost);
    }

    public function test_weighted_average_cost_prevents_simple_averaging_error(): void
    {
        // This test ensures we don't use the wrong formula: (old_cost + new_cost) / 2
        
        // First batch: 1 unit at 100
        $batch1 = $this->service->addToBatch(
            productId: $this->product->id,
            warehouseId: $this->warehouse->id,
            branchId: $this->branch->id,
            quantity: 1,
            unitCost: 100,
            batchNumber: 'BATCH-003'
        );

        // Second batch: 1 unit at 200
        $batch2 = $this->service->addToBatch(
            productId: $this->product->id,
            warehouseId: $this->warehouse->id,
            branchId: $this->branch->id,
            quantity: 1,
            unitCost: 200,
            batchNumber: 'BATCH-003'
        );

        // Correct weighted average: (1*100 + 1*200) / 2 = 150
        // Wrong simple average: (100 + 200) / 2 = 150
        // Both give same result here, so test with different quantities
        
        // Add another unit at 200
        $batch3 = $this->service->addToBatch(
            productId: $this->product->id,
            warehouseId: $this->warehouse->id,
            branchId: $this->branch->id,
            quantity: 1,
            unitCost: 200,
            batchNumber: 'BATCH-003'
        );

        // Correct weighted average: (2*150 + 1*200) / 3 = 500/3 = 166.67
        // Wrong simple average: (150 + 200) / 2 = 175
        $this->assertEquals(3, $batch3->quantity);
        $this->assertEqualsWithDelta(166.67, $batch3->unit_cost, 0.01);
    }

    public function test_new_batch_uses_provided_cost(): void
    {
        $batch = $this->service->addToBatch(
            productId: $this->product->id,
            warehouseId: $this->warehouse->id,
            branchId: $this->branch->id,
            quantity: 10,
            unitCost: 75.50,
            batchNumber: 'BATCH-NEW'
        );

        $this->assertEquals(10, $batch->quantity);
        $this->assertEquals(75.50, $batch->unit_cost);
    }
}
