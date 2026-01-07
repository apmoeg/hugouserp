<?php

declare(strict_types=1);

namespace Tests\Feature\Warehouse;

use App\Livewire\Warehouse\Adjustments\Form;
use App\Models\Adjustment;
use App\Models\AdjustmentItem;
use App\Models\Branch;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

class AdjustmentIntegrityTest extends TestCase
{
    use RefreshDatabase;

    private function allowWarehouseManage(): void
    {
        Gate::define('warehouse.manage', fn () => true);
    }

    private function makeWarehouse(Branch $branch): Warehouse
    {
        return Warehouse::create([
            'name' => 'WH '.Str::random(4),
            'code' => 'WH-'.Str::random(5),
            'status' => 'active',
            'branch_id' => $branch->id,
        ]);
    }

    public function test_branch_is_derived_from_warehouse_when_user_has_no_branch(): void
    {
        $this->allowWarehouseManage();

        $branch = Branch::factory()->create();
        $warehouse = $this->makeWarehouse($branch);
        $product = Product::factory()->create(['branch_id' => $branch->id]);
        $user = User::factory()->create(['branch_id' => null]);

        $this->actingAs($user);

        Livewire::test(Form::class)
            ->set('warehouseId', $warehouse->id)
            ->set('reason', 'Stock correction')
            ->set('note', '')
            ->set('items', [
                ['product_id' => $product->id, 'qty' => 5],
            ])
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('adjustments', [
            'warehouse_id' => $warehouse->id,
            'branch_id' => $branch->id,
        ]);

        $movement = StockMovement::first();
        $this->assertNotNull($movement);
        $this->assertSame($warehouse->id, $movement->warehouse_id);
        $this->assertSame($branch->id, $movement->branch_id);
        $this->assertSame('in', $movement->direction);
        $this->assertEquals(5.0, (float) $movement->qty);
    }

    public function test_adjustment_items_and_stock_rollback_on_failure(): void
    {
        $this->allowWarehouseManage();

        $branch = Branch::factory()->create();
        $warehouse = $this->makeWarehouse($branch);
        $product = Product::factory()->create(['branch_id' => $branch->id]);
        $user = User::factory()->create(['branch_id' => $branch->id]);

        $adjustment = Adjustment::create([
            'branch_id' => $branch->id,
            'warehouse_id' => $warehouse->id,
            'reason' => 'Existing',
            'note' => '',
            'created_by' => $user->id,
        ]);

        $existingItem = AdjustmentItem::create([
            'adjustment_id' => $adjustment->id,
            'product_id' => $product->id,
            'qty' => 1,
        ]);

        AdjustmentItem::creating(function () {
            throw new \Exception('adjustment fail');
        });

        $this->actingAs($user);

        try {
            Livewire::test(Form::class, ['id' => $adjustment->id])
                ->set('warehouseId', $warehouse->id)
                ->set('reason', 'Rollback case')
                ->set('items', [
                    ['product_id' => $product->id, 'qty' => 2],
                ])
                ->call('save');

            $this->fail('Expected exception was not thrown.');
        } catch (\Exception $exception) {
            $this->assertSame('adjustment fail', $exception->getMessage());
        } finally {
            AdjustmentItem::flushEventListeners();
        }

        $this->assertDatabaseHas('adjustments', ['id' => $adjustment->id]);
        $this->assertDatabaseHas('adjustment_items', ['id' => $existingItem->id]);
        $this->assertDatabaseCount('stock_movements', 0);
    }
}
