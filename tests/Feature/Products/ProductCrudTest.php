<?php

declare(strict_types=1);

namespace Tests\Feature\Products;

use App\Models\Branch;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductCrudTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected Branch $branch;

    protected function setUp(): void
    {
        parent::setUp();

        $this->branch = Branch::create([
            'name' => 'Test Branch',
            'code' => 'TB001',
        ]);

        $this->user = User::factory()->create([
            'branch_id' => $this->branch->id,
        ]);
    }

    public function test_can_create_product(): void
    {
        $this->actingAs($this->user);

        $data = [
            'name' => 'Test Product',
            'code' => 'PRD001',
            'sku' => 'SKU001',
            'type' => 'stock',
            'default_price' => 100,
            'standard_cost' => 50,
            'status' => 'active',
            'branch_id' => $this->branch->id,
        ];

        $product = Product::create($data);

        $this->assertDatabaseHas('products', [
            'name' => 'Test Product',
            'sku' => 'SKU001',
        ]);
    }

    public function test_can_read_product(): void
    {
        $product = Product::create([
            'name' => 'Test Product',
            'code' => 'PRD001',
            'type' => 'stock',
            'default_price' => 100,
            'branch_id' => $this->branch->id,
        ]);

        $found = Product::find($product->id);

        $this->assertNotNull($found);
        $this->assertEquals('Test Product', $found->name);
    }

    public function test_can_update_product(): void
    {
        $product = Product::create([
            'name' => 'Test Product',
            'code' => 'PRD001',
            'type' => 'stock',
            'default_price' => 100,
            'branch_id' => $this->branch->id,
        ]);

        $product->update(['name' => 'Updated Product']);

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'name' => 'Updated Product',
        ]);
    }

    public function test_can_delete_product(): void
    {
        $product = Product::create([
            'name' => 'Test Product',
            'code' => 'PRD001',
            'type' => 'stock',
            'default_price' => 100,
            'branch_id' => $this->branch->id,
        ]);

        $product->delete();

        $this->assertSoftDeleted('products', [
            'id' => $product->id,
        ]);
    }

    public function test_validates_required_fields(): void
    {
        $this->expectException(\Illuminate\Database\QueryException::class);

        Product::create([
            'type' => 'stock',
        ]);
    }
}
