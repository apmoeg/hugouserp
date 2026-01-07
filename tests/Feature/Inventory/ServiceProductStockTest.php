<?php

declare(strict_types=1);

namespace Tests\Feature\Inventory;

use App\Exceptions\InvalidQuantityException;
use App\Models\Branch;
use App\Models\Product;
use App\Models\Warehouse;
use App\Services\InventoryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ServiceProductStockTest extends TestCase
{
    use RefreshDatabase;

    protected InventoryService $service;

    protected Branch $branch;

    protected Warehouse $warehouse;

    protected Product $serviceProduct;

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

        // Create a service product
        $this->serviceProduct = Product::create([
            'name' => 'Consulting Service',
            'code' => 'SRV001',
            'sku' => 'SRV-SKU001',
            'type' => 'service',
            'product_type' => 'service',
            'default_price' => 100,
            'branch_id' => $this->branch->id,
        ]);

        // Set branch context
        request()->attributes->set('branch_id', $this->branch->id);
    }

    public function test_cannot_adjust_stock_for_service_products(): void
    {
        $this->expectException(InvalidQuantityException::class);
        $this->expectExceptionMessage('Cannot adjust stock for service products.');

        $this->service->adjust($this->serviceProduct->id, 10, $this->warehouse->id, 'Test adjustment');
    }

    public function test_cannot_transfer_stock_for_service_products(): void
    {
        $warehouse2 = Warehouse::create([
            'name' => 'Second Warehouse',
            'code' => 'WH002',
            'branch_id' => $this->branch->id,
        ]);

        $this->expectException(InvalidQuantityException::class);
        $this->expectExceptionMessage('Cannot transfer stock for service products.');

        $this->service->transfer($this->serviceProduct->id, 5, $this->warehouse->id, $warehouse2->id);
    }

    public function test_can_adjust_stock_for_physical_products(): void
    {
        $physicalProduct = Product::create([
            'name' => 'Physical Product',
            'code' => 'PHY001',
            'sku' => 'PHY-SKU001',
            'type' => 'product',
            'product_type' => 'physical',
            'default_price' => 50,
            'standard_cost' => 25,
            'branch_id' => $this->branch->id,
        ]);

        $result = $this->service->adjust($physicalProduct->id, 10, $this->warehouse->id, 'Initial stock');

        $this->assertInstanceOf(\App\Models\StockMovement::class, $result);
        $this->assertEquals(10, $result->qty);
    }

    public function test_adjustment_requires_a_warehouse(): void
    {
        $physicalProduct = Product::create([
            'name' => 'Physical Product',
            'code' => 'PHY002',
            'sku' => 'PHY-SKU002',
            'type' => 'product',
            'product_type' => 'physical',
            'default_price' => 50,
            'standard_cost' => 25,
            'branch_id' => $this->branch->id,
        ]);

        $this->expectException(InvalidQuantityException::class);
        $this->expectExceptionMessage('Warehouse is required for inventory adjustments.');

        $this->service->adjust($physicalProduct->id, 5, null, 'Missing warehouse');
    }
}
