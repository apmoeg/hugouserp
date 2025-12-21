<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\Store;
use App\Models\StoreIntegration;
use App\Services\Store\StoreSyncService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class WebhookReplayProtectionTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Mockery::close();
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_shopify_webhook_rejects_replay_requests_and_is_rate_limited(): void
    {
        $store = Store::factory()->shopify()->create();

        StoreIntegration::create([
            'store_id' => $store->id,
            'platform' => 'shopify',
            'webhook_secret' => 'secret-key',
        ]);

        $mock = Mockery::mock(StoreSyncService::class);
        $mock->shouldReceive('handleShopifyProductUpdate')->once();
        app()->instance(StoreSyncService::class, $mock);

        Carbon::setTestNow(now());

        $payload = ['product' => ['id' => 123]];
        $body = json_encode($payload);

        $firstResponse = $this->withHeaders($this->shopifyHeaders($body, 'products/create', 'delivery-1', 'secret-key'))
            ->postJson(route('webhooks.shopify', ['storeId' => $store->id]), $payload);

        $firstResponse->assertStatus(200);
        $firstResponse->assertHeader('X-RateLimit-Limit');

        Carbon::setTestNow(now()->addMinutes(11));

        $secondResponse = $this->withHeaders($this->shopifyHeaders($body, 'products/create', 'delivery-1', 'secret-key'))
            ->postJson(route('webhooks.shopify', ['storeId' => $store->id]), $payload);

        $secondResponse->assertStatus(401);
        $secondResponse->assertJsonFragment(['message' => 'Invalid webhook signature']);
    }

    protected function shopifyHeaders(string $body, string $topic, string $deliveryId, string $secret): array
    {
        $timestamp = now()->toIso8601String();
        $hmac = base64_encode(hash_hmac('sha256', $body, $secret, true));

        return [
            'X-Shopify-Hmac-Sha256' => $hmac,
            'X-Shopify-Webhook-Id' => $deliveryId,
            'X-Shopify-Triggered-At' => $timestamp,
            'X-Shopify-Topic' => $topic,
            'Content-Type' => 'application/json',
        ];
    }
}
