<?php

declare(strict_types=1);

namespace Tests\Feature\Livewire;

use App\Livewire\Inventory\Products\Form;
use App\Models\Branch;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class ProductBranchAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_cannot_load_product_from_another_branch(): void
    {
        $branchA = Branch::factory()->create();
        $branchB = Branch::factory()->create();
        $user = $this->userWithPermissions($branchA);
        $product = Product::factory()->create(['branch_id' => $branchB->id]);

        $this->actingAs($user);

        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        Livewire::test(Form::class, ['product' => $product->id])
            ->assertStatus(403);
    }

    public function test_branch_id_is_forced_for_updates(): void
    {
        $branch = Branch::factory()->create();
        $otherBranch = Branch::factory()->create();
        $user = $this->userWithPermissions($branch);
        $product = Product::factory()->create(['branch_id' => $branch->id]);

        $this->actingAs($user);

        Livewire::test(Form::class, ['product' => $product->id])
            ->set('form.name', 'Updated Name')
            ->set('form.branch_id', $otherBranch->id)
            ->call('save');

        $this->assertEquals($branch->id, $product->fresh()->branch_id);
    }

    protected function userWithPermissions(Branch $branch): User
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        Permission::findOrCreate('inventory.products.view', 'web');
        Permission::findOrCreate('inventory.products.update', 'web');

        $user = User::factory()->create(['branch_id' => $branch->id]);
        $user->givePermissionTo(['inventory.products.view', 'inventory.products.update']);

        return $user;
    }
}
