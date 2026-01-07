<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Models\Branch;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\Warehouse;
use App\Services\InventoryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InventoryServiceTest extends TestCase
{
    use RefreshDatabase;

    protected InventoryService $service;

    protected Branch $branch;

    protected Warehouse $warehouse;

    protected Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(InventoryService::class);

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

    public function test_can_get_product_stock_level(): void
    {
        StockMovement::create([
            'product_id' => $this->product->id,
            'warehouse_id' => $this->warehouse->id,
            'quantity' => 100,
            'type' => 'in',
            'branch_id' => $this->branch->id,
        ]);

        $stock = $this->service->getStockLevel($this->product->id, $this->warehouse->id);

        $this->assertIsNumeric($stock);
        $this->assertGreaterThanOrEqual(0, $stock);
    }

    public function test_can_record_stock_adjustment(): void
    {
        $adjustment = $this->service->recordStockAdjustment([
            'product_id' => $this->product->id,
            'warehouse_id' => $this->warehouse->id,
            'quantity' => 50,
            'type' => 'in',
            'reason' => 'Initial stock',
            'branch_id' => $this->branch->id,
        ]);

        $this->assertInstanceOf(StockMovement::class, $adjustment);
    }

    public function test_can_check_stock_availability(): void
    {
        StockMovement::create([
            'product_id' => $this->product->id,
            'warehouse_id' => $this->warehouse->id,
            'quantity' => 100,
            'type' => 'in',
            'branch_id' => $this->branch->id,
        ]);

        $available = $this->service->isStockAvailable($this->product->id, 50, $this->warehouse->id);

        $this->assertTrue($available);
    }

    public function test_detects_insufficient_stock(): void
    {
        StockMovement::create([
            'product_id' => $this->product->id,
            'warehouse_id' => $this->warehouse->id,
            'quantity' => 10,
            'type' => 'in',
            'branch_id' => $this->branch->id,
        ]);

        $available = $this->service->isStockAvailable($this->product->id, 50, $this->warehouse->id);

        $this->assertFalse($available);
    }
}
