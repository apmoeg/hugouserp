<?php

declare(strict_types=1);

namespace Tests\Feature\Livewire;

use App\Livewire\Inventory\ProductStoreMappings;
use App\Livewire\Inventory\ProductStoreMappings\Form as ProductStoreMappingsForm;
use App\Models\Branch;
use App\Models\Product;
use App\Models\ProductStoreMapping;
use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class ProductStoreMappingsAuthorizationTest extends TestCase
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
        Permission::findOrCreate('inventory.products.create', 'web');
        Permission::findOrCreate('inventory.products.update', 'web');
        Permission::findOrCreate('inventory.products.delete', 'web');
    }

    /**
     * BUG-002: Test user with only view permission receives 403 on save.
     */
    public function test_user_with_view_only_permission_cannot_save(): void
    {
        $branch = Branch::factory()->create();
        $user = User::factory()->create(['branch_id' => $branch->id]);
        $user->givePermissionTo(['inventory.products.view']); // Only view permission

        $product = Product::factory()->create(['branch_id' => $branch->id]);
        $store = Store::create([
            'name' => 'Test Store',
            'type' => 'custom',
            'branch_id' => $branch->id,
            'is_active' => true,
        ]);

        $this->actingAs($user);

        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);

        $component = new ProductStoreMappingsForm;
        app()->call([$component, 'mount'], ['productId' => $product->id]);

        $component->store_id = $store->id;
        $component->external_id = 'ext-123';
        app()->call([$component, 'save']);
    }

    /**
     * BUG-002: Test user cannot access mappings for product from another branch.
     */
    public function test_cross_branch_product_mapping_is_blocked(): void
    {
        $branchA = Branch::factory()->create();
        $branchB = Branch::factory()->create();
        $user = User::factory()->create(['branch_id' => $branchA->id]);
        $user->givePermissionTo(['inventory.products.view', 'inventory.products.create']);

        // Product belongs to branch B
        $product = Product::factory()->create(['branch_id' => $branchB->id]);

        $this->actingAs($user);

        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);

        $component = new ProductStoreMappings;
        app()->call([$component, 'mount'], ['productId' => $product->id]);
    }

    /**
     * BUG-002: Test user with create permission can create mapping for own branch product.
     */
    public function test_user_with_create_permission_can_access_form(): void
    {
        $branch = Branch::factory()->create();
        $user = User::factory()->create(['branch_id' => $branch->id]);
        $user->givePermissionTo(['inventory.products.view', 'inventory.products.create']);

        $product = Product::factory()->create(['branch_id' => $branch->id]);

        $this->actingAs($user);

        $component = new ProductStoreMappingsForm;
        app()->call([$component, 'mount'], ['productId' => $product->id]);

        // If we reach here without exception, the authorization passed
        $this->assertTrue(true);
    }

    /**
     * BUG-002: Test user cannot delete mapping without delete permission.
     */
    public function test_user_without_delete_permission_cannot_delete_mapping(): void
    {
        $branch = Branch::factory()->create();
        $user = User::factory()->create(['branch_id' => $branch->id]);
        $user->givePermissionTo(['inventory.products.view']); // Only view

        $product = Product::factory()->create(['branch_id' => $branch->id]);
        $store = Store::create([
            'name' => 'Test Store',
            'type' => 'custom',
            'branch_id' => $branch->id,
            'is_active' => true,
        ]);
        $mapping = ProductStoreMapping::create([
            'product_id' => $product->id,
            'store_id' => $store->id,
            'external_id' => 'ext-123',
        ]);

        $this->actingAs($user);

        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);

        $component = new ProductStoreMappings;
        app()->call([$component, 'mount'], ['productId' => $product->id]);
        app()->call([$component, 'delete'], ['id' => $mapping->id]);
    }

    /**
     * BUG-002: Test user cannot edit mapping without update permission.
     */
    public function test_user_cannot_access_edit_form_without_update_permission(): void
    {
        $branch = Branch::factory()->create();
        $user = User::factory()->create(['branch_id' => $branch->id]);
        $user->givePermissionTo(['inventory.products.view']); // Only view

        $product = Product::factory()->create(['branch_id' => $branch->id]);
        $store = Store::create([
            'name' => 'Test Store',
            'type' => 'custom',
            'branch_id' => $branch->id,
            'is_active' => true,
        ]);
        $mapping = ProductStoreMapping::create([
            'product_id' => $product->id,
            'store_id' => $store->id,
            'external_id' => 'ext-123',
        ]);

        $this->actingAs($user);

        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);

        $component = new ProductStoreMappingsForm;
        app()->call([$component, 'mount'], ['productId' => $product->id, 'mapping' => $mapping->id]);
    }

    /**
     * BUG-002: Test branchless user cannot access mappings.
     */
    public function test_branchless_user_cannot_access_mappings(): void
    {
        $branch = Branch::factory()->create();
        $user = User::factory()->create(['branch_id' => null]);
        $user->givePermissionTo(['inventory.products.view', 'inventory.products.create']);

        $product = Product::factory()->create(['branch_id' => $branch->id]);

        $this->actingAs($user);

        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);

        $component = new ProductStoreMappings;
        app()->call([$component, 'mount'], ['productId' => $product->id]);
    }
}
