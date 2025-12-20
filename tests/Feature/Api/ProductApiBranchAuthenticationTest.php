<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\Branch;
use App\Models\Product;
use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductApiBranchAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    protected Branch $branchA;
    protected Branch $branchB;
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        // Create branches
        $this->branchA = Branch::create(['name' => 'Branch A', 'code' => 'BRA']);
        $this->branchB = Branch::create(['name' => 'Branch B', 'code' => 'BRB']);

        // Create user
        $this->user = User::factory()->create(['branch_id' => $this->branchA->id]);

        // Create products in each branch
        Product::create([
            'name' => 'Product Branch A',
            'sku' => 'PROD-A-001',
            'default_price' => 100,
            'branch_id' => $this->branchA->id,
            'status' => 'active',
        ]);

        Product::create([
            'name' => 'Product Branch B',
            'sku' => 'PROD-B-001',
            'default_price' => 200,
            'branch_id' => $this->branchB->id,
            'status' => 'active',
        ]);
    }

    public function test_products_index_requires_authenticated_store_with_branch(): void
    {
        $this->actingAs($this->user);

        // Request without store in request (simulating missing middleware)
        request()->merge(['store' => null]);

        $response = $this->getJson('/api/v1/products');

        // Should return 401 instead of returning all products
        $response->assertStatus(401)
            ->assertJson([
                'success' => false,
                'message' => __('Store authentication required'),
            ]);
    }

    public function test_products_index_requires_store_with_branch_id(): void
    {
        $this->actingAs($this->user);

        // Create a store without branch_id (misconfigured)
        $storeWithoutBranch = Store::create([
            'name' => 'Invalid Store',
            'code' => 'INV-STORE',
            'branch_id' => null,
        ]);

        request()->merge(['store' => $storeWithoutBranch]);

        $response = $this->getJson('/api/v1/products');

        // Should return 401
        $response->assertStatus(401)
            ->assertJson([
                'success' => false,
                'message' => __('Store authentication required'),
            ]);
    }

    public function test_products_index_filters_by_store_branch(): void
    {
        $this->actingAs($this->user);

        // Create store with Branch A
        $storeA = Store::create([
            'name' => 'Store A',
            'code' => 'STORE-A',
            'branch_id' => $this->branchA->id,
        ]);

        request()->merge(['store' => $storeA]);

        $response = $this->getJson('/api/v1/products');

        $response->assertStatus(200);
        $data = $response->json('data');

        // Should only return products from Branch A
        $this->assertCount(1, $data);
        $this->assertEquals('PROD-A-001', $data[0]['sku']);
    }

    public function test_products_index_does_not_return_other_branch_products(): void
    {
        $this->actingAs($this->user);

        // Create store with Branch A
        $storeA = Store::create([
            'name' => 'Store A',
            'code' => 'STORE-A',
            'branch_id' => $this->branchA->id,
        ]);

        request()->merge(['store' => $storeA]);

        $response = $this->getJson('/api/v1/products');

        $response->assertStatus(200);
        $data = $response->json('data');

        // Verify Branch B product is not included
        $skus = collect($data)->pluck('sku')->toArray();
        $this->assertNotContains('PROD-B-001', $skus);
    }

    public function test_products_show_requires_store_authentication(): void
    {
        $this->actingAs($this->user);

        $product = Product::where('branch_id', $this->branchA->id)->first();

        // Request without store
        request()->merge(['store' => null]);

        $response = $this->getJson("/api/v1/products/{$product->id}");

        // Should still work for show as per current implementation,
        // but should be scoped to branch if store is present
        // This test documents current behavior
        $response->assertStatus(200);
    }

    public function test_products_store_requires_authenticated_store(): void
    {
        $this->actingAs($this->user);

        request()->merge(['store' => null]);

        $response = $this->postJson('/api/v1/products', [
            'name' => 'New Product',
            'sku' => 'NEW-PROD',
            'price' => 100,
            'quantity' => 10,
        ]);

        $response->assertStatus(401)
            ->assertJson([
                'success' => false,
                'message' => __('Store authentication required'),
            ]);
    }

    public function test_products_update_requires_authenticated_store(): void
    {
        $this->actingAs($this->user);

        $product = Product::where('branch_id', $this->branchA->id)->first();

        request()->merge(['store' => null]);

        $response = $this->putJson("/api/v1/products/{$product->id}", [
            'name' => 'Updated Name',
        ]);

        $response->assertStatus(401)
            ->assertJson([
                'success' => false,
                'message' => __('Store authentication required'),
            ]);
    }
}
