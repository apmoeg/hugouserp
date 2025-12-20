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

/**
 * Tests for security fixes:
 * - BUG-006: Warehouse branch scoping
 * - BUG-007: External ID lookup uses correct column
 */
class OrdersSecurityTest extends TestCase
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

    /**
     * BUG-006: Test that warehouse_id must belong to the store's branch.
     */
    public function test_order_creation_rejects_warehouse_from_other_branch(): void
    {
        $this->withExceptionHandling();
        
        $branchA = Branch::factory()->create();
        $branchB = Branch::factory()->create();

        // Warehouse in branch A
        $warehouseA = Warehouse::create([
            'name' => 'Branch A WH',
            'status' => 'active',
            'branch_id' => $branchA->id,
        ]);

        // Warehouse in branch B (foreign)
        $warehouseB = Warehouse::create([
            'name' => 'Branch B WH',
            'status' => 'active',
            'branch_id' => $branchB->id,
        ]);

        $product = Product::factory()->create(['branch_id' => $branchA->id, 'default_price' => 50]);
        $token = $this->createStoreToken($branchA);

        // Try to create order in branch A with warehouse from branch B
        $payload = [
            'customer' => ['name' => 'Test Customer'],
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 1,
                    'price' => 50,
                ],
            ],
            'warehouse_id' => $warehouseB->id,  // Foreign warehouse
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token->token,
        ])->postJson('/api/v1/orders', $payload);

        // Should reject due to warehouse not belonging to branch
        $response->assertStatus(422);
        $this->assertStringContainsString('warehouse', strtolower($response->json('message') ?? '') . strtolower(json_encode($response->json('errors') ?? [])));
    }

    /**
     * BUG-006: Test that order creation succeeds with warehouse from same branch.
     */
    public function test_order_creation_accepts_warehouse_from_same_branch(): void
    {
        $branch = Branch::factory()->create();

        $warehouse = Warehouse::create([
            'name' => 'Same Branch WH',
            'status' => 'active',
            'branch_id' => $branch->id,
        ]);

        $product = Product::factory()->create(['branch_id' => $branch->id, 'default_price' => 50]);
        $token = $this->createStoreToken($branch);

        $payload = [
            'customer' => ['name' => 'Test Customer'],
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 1,
                    'price' => 50,
                ],
            ],
            'warehouse_id' => $warehouse->id,
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token->token,
        ])->postJson('/api/v1/orders', $payload);

        $response->assertStatus(201);
        $this->assertEquals($warehouse->id, $response->json('data.warehouse_id'));
    }

    /**
     * BUG-007: Test that byExternalId endpoint finds orders using reference_no.
     */
    public function test_external_id_lookup_uses_reference_no_column(): void
    {
        $branch = Branch::factory()->create();
        $warehouse = Warehouse::create([
            'name' => 'Test WH',
            'status' => 'active',
            'branch_id' => $branch->id,
        ]);
        $product = Product::factory()->create(['branch_id' => $branch->id, 'default_price' => 25]);
        $token = $this->createStoreToken($branch);

        $externalId = 'EXT-UNIQUE-123';

        // Create order with external_id
        $createPayload = [
            'customer' => ['name' => 'External ID Customer'],
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 1,
                    'price' => 25,
                ],
            ],
            'warehouse_id' => $warehouse->id,
            'external_id' => $externalId,
        ];

        $createResponse = $this->withHeaders([
            'Authorization' => 'Bearer '.$token->token,
        ])->postJson('/api/v1/orders', $createPayload);

        $createResponse->assertStatus(201);
        $createdOrderId = $createResponse->json('data.id');

        // Verify order was stored with reference_no
        $sale = Sale::find($createdOrderId);
        $this->assertEquals($externalId, $sale->reference_no);

        // Now fetch by external_id
        $fetchResponse = $this->withHeaders([
            'Authorization' => 'Bearer '.$token->token,
        ])->getJson('/api/v1/orders/external/'.$externalId);

        // Should find the order (previously this returned 404)
        $fetchResponse->assertStatus(200);
        $this->assertEquals($createdOrderId, $fetchResponse->json('data.id'));
    }

    /**
     * BUG-006: Test that default warehouse is scoped to branch.
     */
    public function test_default_warehouse_fallback_is_scoped_to_branch(): void
    {
        $this->withExceptionHandling();
        
        $branchA = Branch::factory()->create();
        $branchB = Branch::factory()->create();

        // Only create warehouse in branch B
        Warehouse::create([
            'name' => 'Branch B Only WH',
            'status' => 'active',
            'branch_id' => $branchB->id,
        ]);

        $product = Product::factory()->create(['branch_id' => $branchA->id, 'default_price' => 50]);
        $token = $this->createStoreToken($branchA);

        // Try to create order in branch A (no warehouse exists for branch A)
        $payload = [
            'customer' => ['name' => 'Test Customer'],
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 1,
                    'price' => 50,
                ],
            ],
            // No warehouse_id - should fail as no branch A warehouse exists
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token->token,
        ])->postJson('/api/v1/orders', $payload);

        // Should fail with warehouse error (not fall back to branch B warehouse)
        $response->assertStatus(422);
        $this->assertStringContainsString('warehouse', strtolower($response->json('message') ?? '') . strtolower(json_encode($response->json('errors') ?? [])));
    }
}
