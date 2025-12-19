<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\Branch;
use App\Models\Sale;
use App\Models\Store;
use App\Models\StoreToken;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderStatusTransitionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
    }

    protected function tokenForBranch(Branch $branch): StoreToken
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
            'token' => 'token-'.$branch->id,
            'abilities' => ['orders.write', 'orders.read'],
        ]);
    }

    protected function makeSale(Branch $branch, Warehouse $warehouse): Sale
    {
        return Sale::create([
            'branch_id' => $branch->id,
            'warehouse_id' => $warehouse->id,
            'status' => 'draft',
            'sub_total' => 0,
            'discount_total' => 0,
            'discount_type' => 'fixed',
            'discount_value' => 0,
            'tax_total' => 0,
            'shipping_total' => 0,
            'grand_total' => 0,
            'paid_total' => 0,
            'due_total' => 0,
            'amount_paid' => 0,
            'amount_due' => 0,
            'payment_status' => 'unpaid',
        ]);
    }

    public function test_completed_is_blocked_when_unpaid(): void
    {
        $branch = Branch::factory()->create();
        $warehouse = Warehouse::create([
            'name' => 'Main WH',
            'status' => 'active',
            'branch_id' => $branch->id,
        ]);
        $sale = $this->makeSale($branch, $warehouse);
        $token = $this->tokenForBranch($branch);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token->token,
        ])->patchJson("/api/v1/orders/{$sale->id}/status", ['status' => 'completed']);

        $response->assertStatus(422);
    }

    public function test_valid_transition_from_draft_to_pending(): void
    {
        $branch = Branch::factory()->create();
        $warehouse = Warehouse::create([
            'name' => 'Main WH',
            'status' => 'active',
            'branch_id' => $branch->id,
        ]);
        $sale = $this->makeSale($branch, $warehouse);
        $token = $this->tokenForBranch($branch);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token->token,
        ])->patchJson("/api/v1/orders/{$sale->id}/status", ['status' => 'pending']);

        $response->assertStatus(200);
        $this->assertEquals('pending', $sale->fresh()->status);
    }
}
