<?php

declare(strict_types=1);

namespace Tests\Feature\Livewire;

use App\Livewire\Inventory\Services\Form as ServicesForm;
use App\Models\Branch;
use App\Models\Module;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class ServiceProductFormAuthorizationTest extends TestCase
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
     * BUG-001: Test unauthorized user receives 403 when accessing form.
     */
    public function test_unauthorized_user_receives_403_when_accessing_form(): void
    {
        $branch = Branch::factory()->create();
        $user = User::factory()->create(['branch_id' => $branch->id]);
        // User has no permissions

        $this->actingAs($user);

        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);

        $component = new ServicesForm;
        app()->call([$component, 'mount']);
    }

    /**
     * BUG-001: Test branchless user cannot create service products.
     */
    public function test_branchless_user_cannot_create_service_product(): void
    {
        $user = User::factory()->create(['branch_id' => null]);
        $user->givePermissionTo(['inventory.products.create']);

        $this->actingAs($user);

        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);

        $component = new ServicesForm;
        app()->call([$component, 'mount']);
    }

    /**
     * BUG-001: Test user with permission can access form.
     */
    public function test_user_with_permission_can_access_form(): void
    {
        $branch = Branch::factory()->create();
        $user = User::factory()->create(['branch_id' => $branch->id]);
        $user->givePermissionTo(['inventory.products.create']);

        $this->actingAs($user);

        $component = new ServicesForm;
        app()->call([$component, 'mount']);

        // If we reach here without exception, the component mounted successfully
        $this->assertTrue(true);
    }

    /**
     * BUG-001: Test user cannot edit product from another branch.
     */
    public function test_user_cannot_edit_product_from_another_branch(): void
    {
        $branchA = Branch::factory()->create();
        $branchB = Branch::factory()->create();
        $user = User::factory()->create(['branch_id' => $branchA->id]);
        $user->givePermissionTo(['inventory.products.update']);

        $product = Product::factory()->create([
            'branch_id' => $branchB->id,
            'type' => 'service',
        ]);

        $this->actingAs($user);

        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);

        $component = new ServicesForm;
        app()->call([$component, 'mount'], ['service' => $product->id]);
    }

    /**
     * BUG-001: Test branch mismatch aborts on save.
     */
    public function test_branch_mismatch_aborts_on_save(): void
    {
        $branchA = Branch::factory()->create();
        $branchB = Branch::factory()->create();
        $user = User::factory()->create(['branch_id' => $branchA->id]);
        $user->givePermissionTo(['inventory.products.update']);

        $product = Product::factory()->create([
            'branch_id' => $branchB->id,
            'type' => 'service',
        ]);

        $this->actingAs($user);

        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);

        $component = new ServicesForm;
        $component->serviceId = $product->id;
        $component->name = 'Test Service';
        $component->defaultPrice = 100;
        app()->call([$component, 'save']);
    }

    /**
     * BUG-001: Test service product is created with user's branch.
     */
    public function test_service_product_created_with_users_branch(): void
    {
        $branch = Branch::factory()->create();
        $user = User::factory()->create(['branch_id' => $branch->id]);
        $user->givePermissionTo(['inventory.products.create']);

        $serviceModule = Module::create([
            'key' => 'services',
            'slug' => 'services',
            'name' => 'Services',
            'is_service' => true,
            'is_active' => true,
        ]);

        $this->actingAs($user);

        $component = new ServicesForm;
        app()->call([$component, 'mount'], ['moduleId' => $serviceModule->id]);

        $component->name = 'Test Service';
        $component->defaultPrice = 100;
        app()->call([$component, 'save']);

        $product = Product::where('name', 'Test Service')->first();
        $this->assertNotNull($product);
        $this->assertEquals($branch->id, $product->branch_id);
    }

    /**
     * BUG-005: Test invalid module_id is rejected.
     */
    public function test_invalid_module_id_is_rejected(): void
    {
        $branch = Branch::factory()->create();
        $user = User::factory()->create(['branch_id' => $branch->id]);
        $user->givePermissionTo(['inventory.products.create']);

        // Create a non-service module
        $nonServiceModule = Module::create([
            'key' => 'inventory',
            'slug' => 'inventory',
            'name' => 'Inventory',
            'is_service' => false,
            'is_active' => true,
        ]);

        $this->actingAs($user);

        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);

        $component = new ServicesForm;
        app()->call([$component, 'mount'], ['moduleId' => $nonServiceModule->id]);
    }

    /**
     * BUG-001: Test user without update permission cannot save existing product.
     */
    public function test_user_without_update_permission_cannot_save_existing_product(): void
    {
        $branch = Branch::factory()->create();
        $user = User::factory()->create(['branch_id' => $branch->id]);
        $user->givePermissionTo(['inventory.products.view']); // Only view permission

        $product = Product::factory()->create([
            'branch_id' => $branch->id,
            'type' => 'service',
        ]);

        $this->actingAs($user);

        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);

        $component = new ServicesForm;
        $component->serviceId = $product->id;
        $component->name = 'Updated Service';
        $component->defaultPrice = 200;
        app()->call([$component, 'save']);
    }
}
