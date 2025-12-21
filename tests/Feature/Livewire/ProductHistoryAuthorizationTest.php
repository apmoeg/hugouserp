<?php

declare(strict_types=1);

namespace Tests\Feature\Livewire;

use App\Livewire\Inventory\ProductHistory;
use App\Models\AuditLog;
use App\Models\Branch;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class ProductHistoryAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withExceptionHandling();
        $this->setPermissions();
    }

    public function test_user_cannot_view_other_branch_product_history(): void
    {
        $branchA = Branch::factory()->create();
        $branchB = Branch::factory()->create();
        $user = $this->userWithPermissions($branchA);
        $product = Product::factory()->create(['branch_id' => $branchB->id]);

        $this->actingAs($user);

        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);

        Livewire::test(ProductHistory::class, ['product' => $product->id])
            ->assertStatus(403);
    }

    public function test_product_history_is_scoped_to_branch_movements_and_audits(): void
    {
        $branch = Branch::factory()->create();
        $otherBranch = Branch::factory()->create();
        $user = $this->userWithPermissions($branch);
        $product = Product::factory()->create(['branch_id' => $branch->id]);

        $warehouse = Warehouse::create([
            'name' => 'Main',
            'branch_id' => $branch->id,
            'status' => 'active',
            'type' => 'store',
        ]);

        $foreignWarehouse = Warehouse::create([
            'name' => 'Foreign',
            'branch_id' => $otherBranch->id,
            'status' => 'active',
            'type' => 'store',
        ]);

        StockMovement::create([
            'branch_id' => $branch->id,
            'warehouse_id' => $warehouse->id,
            'product_id' => $product->id,
            'qty' => 5,
            'direction' => 'in',
            'type' => 'purchase',
        ]);

        StockMovement::create([
            'branch_id' => $otherBranch->id,
            'warehouse_id' => $foreignWarehouse->id,
            'product_id' => $product->id,
            'qty' => 10,
            'direction' => 'out',
            'type' => 'sale',
        ]);

        AuditLog::forceCreate([
            'auditable_type' => Product::class,
            'auditable_id' => $product->id,
            'branch_id' => $branch->id,
        ]);

        AuditLog::forceCreate([
            'auditable_type' => Product::class,
            'auditable_id' => $product->id,
            'branch_id' => $otherBranch->id,
        ]);

        $this->actingAs($user);

        $component = Livewire::test(ProductHistory::class, ['product' => $product->id]);

        $component->assertSet('currentStock', 5.0);
        $this->assertCount(1, $component->get('stockMovements'));
        $this->assertCount(1, $component->get('auditLogs'));
    }

    protected function setPermissions(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        Permission::findOrCreate('inventory.products.view', 'web');
    }

    protected function userWithPermissions(Branch $branch): User
    {
        $user = User::factory()->create(['branch_id' => $branch->id]);
        $user->givePermissionTo(['inventory.products.view']);

        return $user;
    }
}
