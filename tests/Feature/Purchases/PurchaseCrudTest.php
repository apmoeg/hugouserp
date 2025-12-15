<?php

declare(strict_types=1);

namespace Tests\Feature\Purchases;

use App\Models\Branch;
use App\Models\Purchase;
use App\Models\Supplier;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PurchaseCrudTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected Branch $branch;

    protected Supplier $supplier;

    protected Warehouse $warehouse;

    protected function setUp(): void
    {
        parent::setUp();

        $this->branch = Branch::create(['name' => 'Test Branch', 'code' => 'TB001']);
        $this->warehouse = Warehouse::create(['name' => 'Test Warehouse', 'code' => 'WH001', 'branch_id' => $this->branch->id]);
        $this->user = User::factory()->create(['branch_id' => $this->branch->id]);
        $this->supplier = Supplier::create(['name' => 'Test Supplier', 'branch_id' => $this->branch->id]);
    }

    public function test_can_create_purchase(): void
    {
        $purchase = Purchase::create([
            'supplier_id' => $this->supplier->id,
            'status' => 'pending',
            'branch_id' => $this->branch->id,
            'warehouse_id' => $this->warehouse->id,
        ]);

        $this->assertDatabaseHas('purchases', ['id' => $purchase->id]);
    }

    public function test_can_read_purchase(): void
    {
        $purchase = Purchase::create([
            'supplier_id' => $this->supplier->id,
            'branch_id' => $this->branch->id,
            'warehouse_id' => $this->warehouse->id,
        ]);

        $found = Purchase::find($purchase->id);
        $this->assertNotNull($found);
    }

    public function test_can_update_purchase(): void
    {
        $purchase = Purchase::create([
            'supplier_id' => $this->supplier->id,
            'branch_id' => $this->branch->id,
            'warehouse_id' => $this->warehouse->id,
        ]);

        $purchase->update(['status' => 'approved']);
        $this->assertDatabaseHas('purchases', ['id' => $purchase->id, 'status' => 'approved']);
    }

    public function test_can_delete_purchase(): void
    {
        $purchase = Purchase::create([
            'supplier_id' => $this->supplier->id,
            'branch_id' => $this->branch->id,
            'warehouse_id' => $this->warehouse->id,
        ]);

        $purchase->delete();
        $this->assertSoftDeleted('purchases', ['id' => $purchase->id]);
    }
}
