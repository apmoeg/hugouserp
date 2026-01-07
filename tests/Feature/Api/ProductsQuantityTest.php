<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\Branch;
use App\Models\Product;
use App\Models\Store;
use App\Models\StoreToken;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ProductsQuantityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        if (! Schema::hasColumn('store_tokens', 'deleted_at')) {
            Schema::table('store_tokens', function (Blueprint $table): void {
                $table->softDeletes();
            });
        }
    }

    public function test_store_sets_initial_stock_quantity(): void
    {
        $branch = Branch::factory()->create();
        $store = Store::create([
            'name' => 'Main Store',
            'type' => Store::TYPE_CUSTOM,
            'branch_id' => $branch->id,
            'is_active' => true,
        ]);

        $token = StoreToken::create([
            'store_id' => $store->id,
            'name' => 'Writer',
            'token' => 'tok-init-'.$store->id,
            'abilities' => ['products.write'],
        ]);

        $response = $this->withHeader('Authorization', 'Bearer '.$token->token)
            ->postJson('/api/v1/products', [
                'name' => 'Stocked Product',
                'sku' => 'SKU-INIT-001',
                'price' => 10,
                'quantity' => 7,
            ]);

        $response->assertCreated();

        $this->assertEquals(7, Product::first()->stock_quantity);
    }

    public function test_update_applies_quantity_to_stock(): void
    {
        $branch = Branch::factory()->create();
        $store = Store::create([
            'name' => 'Main Store',
            'type' => Store::TYPE_CUSTOM,
            'branch_id' => $branch->id,
            'is_active' => true,
        ]);

        $token = StoreToken::create([
            'store_id' => $store->id,
            'name' => 'Writer',
            'token' => 'tok-update-'.$store->id,
            'abilities' => ['products.write'],
        ]);

        $product = Product::forceCreate([
            'name' => 'Adjustable Product',
            'sku' => 'SKU-UPD-001',
            'default_price' => 5,
            'branch_id' => $branch->id,
            'stock_quantity' => 1,
        ]);

        $response = $this->withHeader('Authorization', 'Bearer '.$token->token)
            ->putJson('/api/v1/products/'.$product->id, [
                'quantity' => 12,
            ]);

        $response->assertOk();

        $this->assertEquals(12, $product->fresh()->stock_quantity);
    }
}
