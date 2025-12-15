<?php

declare(strict_types=1);

namespace Tests\Feature\Sales;

use App\Models\Branch;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Sale;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SaleCrudTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected Branch $branch;

    protected Customer $customer;

    protected Product $product;

    protected Warehouse $warehouse;

    protected function setUp(): void
    {
        parent::setUp();

        $this->branch = Branch::create(['name' => 'Test Branch', 'code' => 'TB001']);
        $this->warehouse = Warehouse::create(['name' => 'Test Warehouse', 'code' => 'WH001', 'branch_id' => $this->branch->id]);
        $this->user = User::factory()->create(['branch_id' => $this->branch->id]);
        $this->customer = Customer::create(['name' => 'Test Customer', 'branch_id' => $this->branch->id]);
        $this->product = Product::create([
            'name' => 'Test Product',
            'code' => 'PRD001',
            'type' => 'stock',
            'default_price' => 100,
            'branch_id' => $this->branch->id,
        ]);
    }

    public function test_can_create_sale(): void
    {
        $sale = Sale::create([
            'customer_id' => $this->customer->id,
            'status' => 'completed',
            'branch_id' => $this->branch->id,
            'warehouse_id' => $this->warehouse->id,
        ]);

        $this->assertDatabaseHas('sales', ['id' => $sale->id]);
    }

    public function test_can_read_sale(): void
    {
        $sale = Sale::create([
            'customer_id' => $this->customer->id,
            'branch_id' => $this->branch->id,
            'warehouse_id' => $this->warehouse->id,
        ]);

        $found = Sale::find($sale->id);
        $this->assertNotNull($found);
    }

    public function test_can_update_sale(): void
    {
        $sale = Sale::create([
            'customer_id' => $this->customer->id,
            'branch_id' => $this->branch->id,
            'warehouse_id' => $this->warehouse->id,
        ]);

        $sale->update(['grand_total' => 200]);
        $this->assertDatabaseHas('sales', ['id' => $sale->id, 'grand_total' => 200]);
    }

    public function test_can_delete_sale(): void
    {
        $sale = Sale::create([
            'customer_id' => $this->customer->id,
            'branch_id' => $this->branch->id,
            'warehouse_id' => $this->warehouse->id,
        ]);

        $sale->delete();
        $this->assertSoftDeleted('sales', ['id' => $sale->id]);
    }
}
