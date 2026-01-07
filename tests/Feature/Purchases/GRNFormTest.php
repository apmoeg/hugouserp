<?php

declare(strict_types=1);

namespace Tests\Feature\Purchases;

use App\Livewire\Purchases\GRN\Form;
use App\Models\Branch;
use App\Models\GoodsReceivedNote;
use App\Models\GRNItem;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\Supplier;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class GRNFormTest extends TestCase
{
    use RefreshDatabase;

    private function allowGrnPermissions(): void
    {
        Permission::findOrCreate('grn.update', 'web');
        Permission::findOrCreate('grn.create', 'web');
        Permission::findOrCreate('purchases.manage', 'web');

        Gate::define('grn.update', fn () => true);
        Gate::define('grn.create', fn () => true);
    }

    private function makeBranch(): Branch
    {
        return Branch::factory()->create();
    }

    private function makeUser(Branch $branch): User
    {
        return User::factory()->create(['branch_id' => $branch->id]);
    }

    private function makeWarehouse(Branch $branch): Warehouse
    {
        return Warehouse::create([
            'name' => 'WH '.Str::random(4),
            'code' => 'WH-'.Str::random(4),
            'status' => 'active',
            'branch_id' => $branch->id,
        ]);
    }

    private function makeSupplier(Branch $branch): Supplier
    {
        return Supplier::create([
            'branch_id' => $branch->id,
            'name' => 'Supplier '.Str::random(4),
            'email' => null,
            'phone' => null,
        ]);
    }

    private function makePurchase(Branch $branch, Warehouse $warehouse, Supplier $supplier, Product $product, User $user): array
    {
        $purchase = Purchase::create([
            'branch_id' => $branch->id,
            'warehouse_id' => $warehouse->id,
            'supplier_id' => $supplier->id,
            'status' => 'approved',
            'currency' => 'USD',
            'sub_total' => 100,
            'grand_total' => 100,
            'created_by' => $user->id,
        ]);

        $purchaseItem = PurchaseItem::create([
            'purchase_id' => $purchase->id,
            'product_id' => $product->id,
            'branch_id' => $branch->id,
            'qty' => 5,
            'uom' => 'pcs',
            'unit_cost' => 10,
            'discount' => 0,
            'tax_rate' => 0,
            'line_total' => 50,
        ]);

        return [$purchase, $purchaseItem];
    }

    private function makeGrn(User $user, Branch $branch, Warehouse $warehouse, Supplier $supplier, Purchase $purchase): GoodsReceivedNote
    {
        return GoodsReceivedNote::create([
            'branch_id' => $branch->id,
            'warehouse_id' => $warehouse->id,
            'purchase_id' => $purchase->id,
            'supplier_id' => $supplier->id,
            'status' => 'draft',
            'received_date' => now(),
            'received_by' => $user->id,
            'inspected_by' => null,
            'notes' => 'Existing GRN',
            'created_by' => $user->id,
        ]);
    }

    public function test_can_submit_existing_grn_without_quantity_schema_mismatch(): void
    {
        $this->allowGrnPermissions();

        $branch = $this->makeBranch();
        $user = $this->makeUser($branch);
        $warehouse = $this->makeWarehouse($branch);
        $supplier = $this->makeSupplier($branch);
        $product = Product::factory()->create(['branch_id' => $branch->id]);
        [$purchase, $purchaseItem] = $this->makePurchase($branch, $warehouse, $supplier, $product, $user);
        $grn = $this->makeGrn($user, $branch, $warehouse, $supplier, $purchase);

        $item = GRNItem::create([
            'grn_id' => $grn->id,
            'product_id' => $product->id,
            'purchase_item_id' => $purchaseItem->id,
            'qty_ordered' => 5,
            'qty_received' => 4,
            'qty_rejected' => 1,
            'qty_accepted' => 3,
            'unit_cost' => 10,
            'quality_status' => 'good',
            'uom' => 'pcs',
            'created_by' => $user->id,
        ]);

        $this->actingAs($user);

        Livewire::test(Form::class, ['id' => $grn->id])
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('grn_items', [
            'id' => $item->id,
            'qty_received' => 4,
            'qty_rejected' => 1,
            'qty_accepted' => 3,
        ]);
    }

    public function test_grn_save_rolls_back_when_item_creation_fails(): void
    {
        $this->allowGrnPermissions();

        $branch = $this->makeBranch();
        $user = $this->makeUser($branch);
        $warehouse = $this->makeWarehouse($branch);
        $supplier = $this->makeSupplier($branch);
        $product = Product::factory()->create(['branch_id' => $branch->id]);
        [$purchase, $purchaseItem] = $this->makePurchase($branch, $warehouse, $supplier, $product, $user);
        $grn = $this->makeGrn($user, $branch, $warehouse, $supplier, $purchase);

        $existingItem = GRNItem::create([
            'grn_id' => $grn->id,
            'product_id' => $product->id,
            'purchase_item_id' => $purchaseItem->id,
            'qty_ordered' => 2,
            'qty_received' => 2,
            'qty_rejected' => 0,
            'qty_accepted' => 2,
            'unit_cost' => 10,
            'quality_status' => 'good',
            'uom' => 'pcs',
            'created_by' => $user->id,
        ]);

        GRNItem::creating(function () {
            throw new \Exception('force failure');
        });

        $this->actingAs($user);

        try {
            Livewire::test(Form::class, ['id' => $grn->id])
                ->set('items', [[
                    'product_id' => $product->id,
                    'purchase_item_id' => $purchaseItem->id,
                    'quantity_ordered' => 2,
                    'quantity_received' => 2,
                    'quality_status' => 'good',
                    'quantity_damaged' => 0,
                    'quantity_defective' => 0,
                ]])
                ->call('save');

            $this->fail('Expected exception was not thrown.');
        } catch (\Exception $exception) {
            $this->assertSame('force failure', $exception->getMessage());
        } finally {
            GRNItem::flushEventListeners();
        }

        $this->assertDatabaseHas('grn_items', [
            'id' => $existingItem->id,
            'grn_id' => $grn->id,
        ]);
        $this->assertDatabaseCount('grn_items', 1);
    }

    public function test_user_cannot_load_grn_from_another_branch(): void
    {
        $this->allowGrnPermissions();

        $branchA = $this->makeBranch();
        $branchB = $this->makeBranch();
        $user = $this->makeUser($branchA);
        $warehouseB = $this->makeWarehouse($branchB);
        $supplierB = $this->makeSupplier($branchB);
        $productB = Product::factory()->create(['branch_id' => $branchB->id]);
        [$purchaseB, $purchaseItemB] = $this->makePurchase($branchB, $warehouseB, $supplierB, $productB, $user);
        $grnB = $this->makeGrn($user, $branchB, $warehouseB, $supplierB, $purchaseB);

        GRNItem::create([
            'grn_id' => $grnB->id,
            'product_id' => $productB->id,
            'purchase_item_id' => $purchaseItemB->id,
            'qty_ordered' => 1,
            'qty_received' => 1,
            'qty_rejected' => 0,
            'qty_accepted' => 1,
            'unit_cost' => 5,
            'quality_status' => 'good',
        ]);

        $this->actingAs($user);

        try {
            Livewire::test(Form::class, ['id' => $grnB->id]);
            $this->fail('Expected an error when loading GRN from another branch.');
        } catch (HttpException $exception) {
            $this->assertSame(403, $exception->getStatusCode());
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException) {
            $this->assertTrue(true);
        }
    }
}
