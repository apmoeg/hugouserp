<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\Branch;
use App\Models\Product;
use App\Models\Sale;
use App\Models\Store;
use App\Models\StoreToken;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrdersSchemaAlignmentTest extends TestCase
{
    use RefreshDatabase;

    protected function createStoreToken(Branch $branch): StoreToken
    {
        $store = Store::create([
            'name' => 'Test Store',
            'type' => Store::TYPE_CUSTOM,
            'url' => 'https://example.com',
            'branch_id' => $branch->id,
            'is_active' => true,
        ]);

        return StoreToken::create([
            'store_id' => $store->id,
            'name' => 'api',
            'token' => 'apitoken-'.$branch->id,
            'abilities' => ['orders.write', 'orders.read'],
        ]);
    }

    public function test_order_creation_persists_required_fields_and_totals(): void
    {
        $branch = Branch::factory()->create();
        $warehouse = Warehouse::create([
            'name' => 'Main WH',
            'status' => 'active',
            'branch_id' => $branch->id,
        ]);
        $product = Product::factory()->create(['branch_id' => $branch->id, 'default_price' => 50]);
        $token = $this->createStoreToken($branch);

        $payload = [
            'customer' => ['name' => 'API Customer'],
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 2,
                    'price' => 50,
                    'discount' => 5,
                ],
            ],
            'discount' => 3,
            'tax' => 2,
            'shipping' => 4,
            'external_id' => 'EXT-API-1',
            'warehouse_id' => $warehouse->id,
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token->token,
        ])->postJson('/api/v1/orders', $payload);

        $response->assertStatus(201);

        $sale = Sale::first();

        $this->assertNotNull($sale?->uuid);
        $this->assertNotNull($sale?->code);
        $this->assertSame($branch->id, $sale?->branch_id);
        $this->assertSame($warehouse->id, $sale?->warehouse_id);
        $this->assertSame('draft', $sale?->status);
        $this->assertSame('EXT-API-1', $sale?->reference_no);

        $this->assertEquals(100.0, (float) $sale?->sub_total);
        $this->assertEquals(8.0, (float) $sale?->discount_total); // item + order discount
        $this->assertEquals(2.0, (float) $sale?->tax_total);
        $this->assertEquals(4.0, (float) $sale?->shipping_total);
        $this->assertEquals(98.0, (float) $sale?->grand_total);
        $this->assertEquals(0.0, (float) $sale?->paid_total);
        $this->assertEquals(98.0, (float) $sale?->due_total);

        $this->assertCount(1, $sale?->items);
        $item = $sale->items->first();
        $this->assertEquals(2.0, (float) $item->qty);
        $this->assertEquals(50.0, (float) $item->unit_price);
        $this->assertEquals(5.0, (float) $item->discount);
        $this->assertEquals(95.0, (float) $item->line_total);
    }

    public function test_external_id_is_idempotent_per_branch(): void
    {
        $branch = Branch::factory()->create();
        $warehouse = Warehouse::create([
            'name' => 'Main WH',
            'status' => 'active',
            'branch_id' => $branch->id,
        ]);
        $product = Product::factory()->create(['branch_id' => $branch->id, 'default_price' => 20]);
        $token = $this->createStoreToken($branch);

        $payload = [
            'customer' => ['name' => 'API Customer'],
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 1,
                    'price' => 20,
                ],
            ],
            'external_id' => 'EXT-API-2',
            'warehouse_id' => $warehouse->id,
        ];

        $first = $this->withHeaders([
            'Authorization' => 'Bearer '.$token->token,
        ])->postJson('/api/v1/orders', $payload)->json('data.id');

        $second = $this->withHeaders([
            'Authorization' => 'Bearer '.$token->token,
        ])->postJson('/api/v1/orders', $payload)->json('data.id');

        $this->assertSame($first, $second);
        $this->assertEquals(1, Sale::count());
    }
}
