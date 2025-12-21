<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\Branch;
use App\Models\Product;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class BranchStockSecurityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withExceptionHandling();
        $this->setupPermissions();
    }

    public function test_branch_user_cannot_adjust_foreign_product(): void
    {
        $branchA = Branch::factory()->create();
        $branchB = Branch::factory()->create();
        $user = $this->userWithPermissions($branchA);

        $foreignWarehouse = Warehouse::create([
            'name' => 'Remote Warehouse',
            'branch_id' => $branchB->id,
            'status' => 'active',
            'type' => 'store',
        ]);

        $foreignProduct = Product::factory()->create(['branch_id' => $branchB->id]);

        Sanctum::actingAs($user);

        $response = $this->postJson("/api/v1/branches/{$branchA->id}/stock/adjust", [
            'product_id' => $foreignProduct->id,
            'qty' => 5,
            'warehouse_id' => $foreignWarehouse->id,
        ]);

        $response->assertStatus(404);
    }

    public function test_branch_user_can_adjust_product_in_own_branch(): void
    {
        $branch = Branch::factory()->create();
        $user = $this->userWithPermissions($branch);

        $warehouse = Warehouse::create([
            'name' => 'Local Warehouse',
            'branch_id' => $branch->id,
            'status' => 'active',
            'type' => 'store',
        ]);

        $product = Product::factory()->create(['branch_id' => $branch->id]);

        Sanctum::actingAs($user);

        $response = $this->postJson("/api/v1/branches/{$branch->id}/stock/adjust", [
            'product_id' => $product->id,
            'qty' => 5,
            'warehouse_id' => $warehouse->id,
        ]);

        $response->assertOk();
        $response->assertJsonPath('success', true);
    }

    public function test_transfer_rejects_cross_branch_payloads(): void
    {
        $branchA = Branch::factory()->create();
        $branchB = Branch::factory()->create();
        $user = $this->userWithPermissions($branchA);

        $product = Product::factory()->create(['branch_id' => $branchB->id]);

        $warehouseB1 = Warehouse::create([
            'name' => 'B1',
            'branch_id' => $branchB->id,
            'status' => 'active',
            'type' => 'store',
        ]);

        $warehouseB2 = Warehouse::create([
            'name' => 'B2',
            'branch_id' => $branchB->id,
            'status' => 'active',
            'type' => 'store',
        ]);

        Sanctum::actingAs($user);

        $response = $this->postJson("/api/v1/branches/{$branchA->id}/stock/transfer", [
            'product_id' => $product->id,
            'qty' => 3,
            'from_warehouse' => $warehouseB1->id,
            'to_warehouse' => $warehouseB2->id,
        ]);

        $response->assertStatus(404);
    }

    protected function setupPermissions(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        Permission::findOrCreate('stock.adjust', 'web');
        Permission::findOrCreate('stock.transfer', 'web');
        Permission::findOrCreate('stock.view', 'web');
    }

    protected function userWithPermissions(Branch $branch): User
    {
        $user = User::factory()->create(['branch_id' => $branch->id]);
        $user->givePermissionTo(['stock.adjust', 'stock.transfer', 'stock.view']);

        return $user;
    }
}
