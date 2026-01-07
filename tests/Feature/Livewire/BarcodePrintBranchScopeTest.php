<?php

declare(strict_types=1);

namespace Tests\Feature\Livewire;

use App\Livewire\Inventory\BarcodePrint;
use App\Models\Branch;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class BarcodePrintBranchScopeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withExceptionHandling();
        $this->setUpPermissions();
    }

    protected function setUpPermissions(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        Permission::findOrCreate('inventory.products.view', 'web');
    }

    /**
     * BUG-003: Test branchless user is denied access.
     */
    public function test_branchless_user_denied_access(): void
    {
        $user = User::factory()->create(['branch_id' => null]);
        $user->givePermissionTo(['inventory.products.view']);

        $this->actingAs($user);

        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);

        $component = new BarcodePrint;
        app()->call([$component, 'mount']);
    }

    /**
     * BUG-003: Test user cannot add product from another branch.
     */
    public function test_cannot_add_product_from_another_branch(): void
    {
        $branchA = Branch::factory()->create();
        $branchB = Branch::factory()->create();
        $user = User::factory()->create(['branch_id' => $branchA->id]);
        $user->givePermissionTo(['inventory.products.view']);

        $otherBranchProduct = Product::factory()->create([
            'branch_id' => $branchB->id,
        ]);

        $this->actingAs($user);

        $component = new BarcodePrint;
        app()->call([$component, 'mount']);

        app()->call([$component, 'addProduct'], ['productId' => $otherBranchProduct->id]);

        $this->assertEmpty($component->selectedProducts);
    }

    /**
     * BUG-003: Test user can add product from their own branch.
     */
    public function test_user_can_add_product_from_own_branch(): void
    {
        $branch = Branch::factory()->create();
        $user = User::factory()->create(['branch_id' => $branch->id]);
        $user->givePermissionTo(['inventory.products.view']);

        $product = Product::factory()->create([
            'branch_id' => $branch->id,
        ]);

        $this->actingAs($user);

        $component = new BarcodePrint;
        app()->call([$component, 'mount']);

        app()->call([$component, 'addProduct'], ['productId' => $product->id]);

        $this->assertContains($product->id, $component->selectedProducts);
    }

    /**
     * BUG-003: Test user without view permission is denied.
     */
    public function test_user_without_permission_denied(): void
    {
        $branch = Branch::factory()->create();
        $user = User::factory()->create(['branch_id' => $branch->id]);
        // No permissions

        $this->actingAs($user);

        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);

        $component = new BarcodePrint;
        app()->call([$component, 'mount']);
    }

    /**
     * BUG-007: Test selection limit is enforced.
     */
    public function test_selection_limit_is_enforced(): void
    {
        $branch = Branch::factory()->create();
        $user = User::factory()->create(['branch_id' => $branch->id]);
        $user->givePermissionTo(['inventory.products.view']);

        // Create 101 products
        $products = Product::factory()->count(101)->create([
            'branch_id' => $branch->id,
        ]);

        $this->actingAs($user);

        $component = new BarcodePrint;
        app()->call([$component, 'mount']);

        // Add all products (should stop at MAX_SELECTED_PRODUCTS = 100)
        foreach ($products as $product) {
            app()->call([$component, 'addProduct'], ['productId' => $product->id]);
        }

        $this->assertLessThanOrEqual(100, count($component->selectedProducts));
    }
}
