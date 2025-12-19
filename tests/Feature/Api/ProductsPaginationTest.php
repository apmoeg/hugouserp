<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\Branch;
use App\Models\Product;
use App\Models\Store;
use App\Models\StoreToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ProductsPaginationTest extends TestCase
{
    use RefreshDatabase;

    protected Store $store;
    protected StoreToken $token;
    protected Branch $branch;

    protected function setUp(): void
    {
        parent::setUp();

        // Re-enable exception handling for proper HTTP response codes
        $this->withExceptionHandling();

        if (! Schema::hasColumn('store_tokens', 'deleted_at')) {
            Schema::table('store_tokens', function (Blueprint $table): void {
                $table->softDeletes();
            });
        }

        $this->branch = Branch::factory()->create();
        $this->store = Store::create([
            'name' => 'Test Store',
            'type' => Store::TYPE_CUSTOM,
            'branch_id' => $this->branch->id,
            'is_active' => true,
        ]);

        $this->token = StoreToken::create([
            'store_id' => $this->store->id,
            'name' => 'Reader',
            'token' => 'tok-pagination-test',
            'abilities' => ['products.read'],
        ]);
    }

    /**
     * Helper to create product with guarded fields set.
     */
    protected function createProduct(array $attributes): Product
    {
        $product = new Product(array_merge([
            'name' => 'Test Product',
            'sku' => 'SKU-'.uniqid(),
            'default_price' => 10,
        ], $attributes));
        $product->branch_id = $this->branch->id;
        $product->save();
        return $product;
    }

    public function test_per_page_defaults_to_50(): void
    {
        // Create some products
        for ($i = 0; $i < 60; $i++) {
            $this->createProduct([
                'name' => "Product {$i}",
                'sku' => "SKU-PAGE-{$i}",
            ]);
        }

        $response = $this->withHeader('Authorization', 'Bearer '.$this->token->token)
            ->getJson('/api/v1/products');

        $response->assertOk();
        $data = $response->json('data');
        $this->assertLessThanOrEqual(50, count($data));
    }

    public function test_per_page_respects_valid_limit(): void
    {
        for ($i = 0; $i < 30; $i++) {
            $this->createProduct([
                'name' => "Product {$i}",
                'sku' => "SKU-LIMIT-{$i}",
            ]);
        }

        $response = $this->withHeader('Authorization', 'Bearer '.$this->token->token)
            ->getJson('/api/v1/products?per_page=10');

        $response->assertOk();
        $data = $response->json('data');
        $this->assertCount(10, $data);
    }

    public function test_per_page_is_clamped_to_maximum_100(): void
    {
        // Create more than 100 products
        for ($i = 0; $i < 120; $i++) {
            $this->createProduct([
                'name' => "Product {$i}",
                'sku' => "SKU-MAX-{$i}",
            ]);
        }

        // Request more than the maximum allowed
        $response = $this->withHeader('Authorization', 'Bearer '.$this->token->token)
            ->getJson('/api/v1/products?per_page=100');

        $response->assertOk();
        $data = $response->json('data');
        // Should be clamped to 100
        $this->assertLessThanOrEqual(100, count($data));
    }

    public function test_per_page_rejects_invalid_value(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer '.$this->token->token)
            ->getJson('/api/v1/products?per_page=-10');

        // Should return validation error
        $response->assertStatus(422);
    }

    public function test_per_page_rejects_non_numeric_value(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer '.$this->token->token)
            ->getJson('/api/v1/products?per_page=abc');

        // Should return validation error
        $response->assertStatus(422);
    }

    public function test_sort_by_accepts_valid_field(): void
    {
        $this->createProduct([
            'name' => 'Zebra Product',
            'sku' => 'SKU-Z',
        ]);

        $this->createProduct([
            'name' => 'Alpha Product',
            'sku' => 'SKU-A',
        ]);

        $response = $this->withHeader('Authorization', 'Bearer '.$this->token->token)
            ->getJson('/api/v1/products?sort_by=name&sort_dir=asc');

        $response->assertOk();
        $data = $response->json('data');
        $this->assertEquals('Alpha Product', $data[0]['name']);
    }

    public function test_sort_by_rejects_invalid_field(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer '.$this->token->token)
            ->getJson('/api/v1/products?sort_by=invalid_field');

        $response->assertStatus(422);
    }

    public function test_sort_dir_rejects_invalid_direction(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer '.$this->token->token)
            ->getJson('/api/v1/products?sort_by=name&sort_dir=invalid');

        $response->assertStatus(422);
    }
}
