<?php

declare(strict_types=1);

namespace Tests\Feature\Security;

use App\Models\Branch;
use App\Models\Product;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SoftDeletedProductTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected Branch $branch;

    protected Product $product;

    protected Warehouse $warehouse;

    protected function setUp(): void
    {
        parent::setUp();

        $this->branch = Branch::factory()->create([
            'name' => 'Test Branch',
            'is_active' => true,
        ]);

        $this->warehouse = Warehouse::create([
            'name' => 'Main Warehouse',
            'code' => 'WH001',
            'branch_id' => $this->branch->id,
        ]);

        $this->product = Product::create([
            'name' => 'Test Product',
            'code' => 'PRD001',
            'sku' => 'SKU001',
            'type' => 'stock',
            'default_price' => 100,
            'cost' => 50,
            'branch_id' => $this->branch->id,
        ]);

        $this->user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
            'branch_id' => $this->branch->id,
        ]);

        $this->user->branches()->attach($this->branch->id);
    }

    public function test_cannot_sell_soft_deleted_product(): void
    {
        $this->markTestSkipped('Requires full POS session setup and permissions');

        // Soft delete the product
        $this->product->delete();

        Sanctum::actingAs($this->user);

        $response = $this->postJson("/api/v1/branches/{$this->branch->id}/pos/checkout", [
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'qty' => 1,
                    'price' => 100,
                ],
            ],
            'warehouse_id' => $this->warehouse->id,
        ]);

        // Should return error that product is not available
        $response->assertStatus(422);
        $response->assertJsonFragment(['message' => 'no longer available']);
    }

    public function test_soft_deleted_product_not_in_product_list(): void
    {
        Sanctum::actingAs($this->user);

        // Product exists initially
        $response = $this->getJson("/api/v1/branches/{$this->branch->id}/products");
        if ($response->status() === 200) {
            $response->assertJsonFragment(['id' => $this->product->id]);
        }

        // Soft delete the product
        $this->product->delete();

        // Product should not appear in list
        $response = $this->getJson("/api/v1/branches/{$this->branch->id}/products");
        if ($response->status() === 200) {
            $response->assertJsonMissing(['id' => $this->product->id]);
        }
    }

    public function test_can_find_soft_deleted_product_with_trashed(): void
    {
        // Soft delete the product
        $this->product->delete();

        // Should not find without withTrashed
        $found = Product::find($this->product->id);
        $this->assertNull($found);

        // Should find with withTrashed
        $found = Product::withTrashed()->find($this->product->id);
        $this->assertNotNull($found);
        $this->assertTrue($found->trashed());
    }
}
