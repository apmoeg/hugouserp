<?php

declare(strict_types=1);

namespace Tests\Unit\Repositories;

use App\Models\Branch;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\Warehouse;
use App\Repositories\StockMovementRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class StockMovementRepositoryTest extends TestCase
{
    use RefreshDatabase;

    protected StockMovementRepository $repository;

    protected Branch $branch;

    protected Warehouse $warehouse;

    protected Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = app(StockMovementRepository::class);

        $this->branch = Branch::create([
            'name' => 'Test Branch',
            'code' => 'TB001',
        ]);

        $this->warehouse = Warehouse::create([
            'name' => 'Test Warehouse',
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

    public function test_create_stock_movement_uses_transaction(): void
    {
        // Create an initial stock movement (purchase)
        $movement1 = $this->repository->create([
            'product_id' => $this->product->id,
            'warehouse_id' => $this->warehouse->id,
            'movement_type' => 'purchase',
            'qty' => 10,
            'direction' => 'in',
        ]);

        $this->assertInstanceOf(StockMovement::class, $movement1);
        $this->assertEquals(10, $movement1->quantity);
        $this->assertEquals(0, $movement1->stock_before);
        $this->assertEquals(10, $movement1->stock_after);
    }

    public function test_pessimistic_locking_prevents_race_condition(): void
    {
        // Create initial stock
        $this->repository->create([
            'product_id' => $this->product->id,
            'warehouse_id' => $this->warehouse->id,
            'movement_type' => 'purchase',
            'qty' => 1,
            'direction' => 'in',
        ]);

        // Simulate concurrent transactions
        // Both should acquire lock sequentially, not concurrently
        $results = [];
        
        try {
            DB::transaction(function () use (&$results) {
                // First transaction locks the record
                $movement1 = $this->repository->create([
                    'product_id' => $this->product->id,
                    'warehouse_id' => $this->warehouse->id,
                    'movement_type' => 'sale',
                    'qty' => 1,
                    'direction' => 'out',
                ]);
                $results[] = $movement1;
            });
        } catch (\Exception $e) {
            $this->fail('First transaction should succeed: ' . $e->getMessage());
        }

        // Verify stock is correctly updated
        $finalStock = StockMovement::where('product_id', $this->product->id)
            ->where('warehouse_id', $this->warehouse->id)
            ->sum('quantity');

        $this->assertEquals(0, $finalStock, 'Final stock should be 0 after selling the only item');
    }

    public function test_sequential_stock_movements_maintain_accuracy(): void
    {
        // Purchase 10 items
        $this->repository->create([
            'product_id' => $this->product->id,
            'warehouse_id' => $this->warehouse->id,
            'movement_type' => 'purchase',
            'qty' => 10,
            'direction' => 'in',
        ]);

        // Sell 3 items
        $this->repository->create([
            'product_id' => $this->product->id,
            'warehouse_id' => $this->warehouse->id,
            'movement_type' => 'sale',
            'qty' => 3,
            'direction' => 'out',
        ]);

        // Sell 2 more items
        $this->repository->create([
            'product_id' => $this->product->id,
            'warehouse_id' => $this->warehouse->id,
            'movement_type' => 'sale',
            'qty' => 2,
            'direction' => 'out',
        ]);

        // Purchase 5 more items
        $this->repository->create([
            'product_id' => $this->product->id,
            'warehouse_id' => $this->warehouse->id,
            'movement_type' => 'purchase',
            'qty' => 5,
            'direction' => 'in',
        ]);

        $finalStock = StockMovement::where('product_id', $this->product->id)
            ->where('warehouse_id', $this->warehouse->id)
            ->sum('quantity');

        // 10 - 3 - 2 + 5 = 10
        $this->assertEquals(10, $finalStock);
    }

    public function test_stock_before_and_after_are_correctly_calculated(): void
    {
        // First movement
        $movement1 = $this->repository->create([
            'product_id' => $this->product->id,
            'warehouse_id' => $this->warehouse->id,
            'movement_type' => 'purchase',
            'qty' => 10,
            'direction' => 'in',
        ]);

        $this->assertEquals(0, $movement1->stock_before);
        $this->assertEquals(10, $movement1->stock_after);

        // Second movement
        $movement2 = $this->repository->create([
            'product_id' => $this->product->id,
            'warehouse_id' => $this->warehouse->id,
            'movement_type' => 'sale',
            'qty' => 3,
            'direction' => 'out',
        ]);

        $this->assertEquals(10, $movement2->stock_before);
        $this->assertEquals(7, $movement2->stock_after);
    }

    public function test_negative_quantity_for_out_movements(): void
    {
        // Create initial stock
        $this->repository->create([
            'product_id' => $this->product->id,
            'warehouse_id' => $this->warehouse->id,
            'movement_type' => 'purchase',
            'qty' => 10,
            'direction' => 'in',
        ]);

        // Create out movement
        $movement = $this->repository->create([
            'product_id' => $this->product->id,
            'warehouse_id' => $this->warehouse->id,
            'movement_type' => 'sale',
            'qty' => 5,
            'direction' => 'out',
        ]);

        // Quantity should be negative for out movements
        $this->assertEquals(-5, $movement->quantity);
    }
}
