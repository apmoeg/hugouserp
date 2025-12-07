<?php

declare(strict_types=1);

namespace App\Services\Store\Clients;

use App\Models\Store;
use Illuminate\Support\Facades\Http;

class ShopifyClient
{
    protected Store $store;

    protected string $baseUrl;

    protected string $accessToken;

    public function __construct(Store $store)
    {
        $this->store = $store;
        $this->baseUrl = rtrim($store->url, '/').'/admin/api/2024-01';
        $this->accessToken = $store->integration?->access_token ?? '';
    }

    public function getProducts(int $limit = 250): array
    {
        $products = [];
        $pageInfo = null;

        do {
            $params = ['limit' => $limit];
            if ($pageInfo) {
                $params['page_info'] = $pageInfo;
            }

            $response = $this->request('GET', '/products.json', $params);

            if (! $response->successful()) {
                break;
            }

            $data = $response->json();
            $products = array_merge($products, $data['products'] ?? []);

            $linkHeader = $response->header('Link');
            $pageInfo = $this->extractNextPageInfo($linkHeader);

        } while ($pageInfo);

        return $products;
    }

    public function getProduct(string $productId): ?array
    {
        $response = $this->request('GET', "/products/{$productId}.json");

        if ($response->successful()) {
            return $response->json()['product'] ?? null;
        }

        return null;
    }

    public function updateProduct(string $productId, array $data): bool
    {
        $response = $this->request('PUT', "/products/{$productId}.json", [
            'product' => $data,
        ]);

        return $response->successful();
    }

    public function updateInventory(string $inventoryItemId, int $quantity, ?string $locationId = null): bool
    {
        if (! $locationId) {
            $locationId = $this->getDefaultLocationId();
        }

        if (! $locationId) {
            return false;
        }

        $response = $this->request('POST', '/inventory_levels/set.json', [
            'inventory_item_id' => $inventoryItemId,
            'location_id' => $locationId,
            'available' => $quantity,
        ]);

        return $response->successful();
    }

    public function getOrders(string $status = 'any', int $limit = 250): array
    {
        $orders = [];
        $pageInfo = null;

        do {
            $params = [
                'limit' => $limit,
                'status' => $status,
            ];
            if ($pageInfo) {
                $params['page_info'] = $pageInfo;
            }

            $response = $this->request('GET', '/orders.json', $params);

            if (! $response->successful()) {
                break;
            }

            $data = $response->json();
            $orders = array_merge($orders, $data['orders'] ?? []);

            $linkHeader = $response->header('Link');
            $pageInfo = $this->extractNextPageInfo($linkHeader);

        } while ($pageInfo);

        return $orders;
    }

    public function getOrder(string $orderId): ?array
    {
        $response = $this->request('GET', "/orders/{$orderId}.json");

        if ($response->successful()) {
            return $response->json()['order'] ?? null;
        }

        return null;
    }

    public function getLocations(): array
    {
        $response = $this->request('GET', '/locations.json');

        if ($response->successful()) {
            return $response->json()['locations'] ?? [];
        }

        return [];
    }

    public function getDefaultLocationId(): ?string
    {
        $locations = $this->getLocations();

        foreach ($locations as $location) {
            if ($location['active'] ?? false) {
                return (string) $location['id'];
            }
        }

        return null;
    }

    public function registerWebhooks(array $topics): array
    {
        $results = [];
        $webhookUrl = route('webhooks.shopify', ['storeId' => $this->store->id]);

        foreach ($topics as $topic) {
            $response = $this->request('POST', '/webhooks.json', [
                'webhook' => [
                    'topic' => $topic,
                    'address' => $webhookUrl,
                    'format' => 'json',
                ],
            ]);

            $results[$topic] = $response->successful();
        }

        return $results;
    }

    protected function request(string $method, string $endpoint, array $data = [])
    {
        $url = $this->baseUrl.$endpoint;

        $http = Http::withHeaders([
            'X-Shopify-Access-Token' => $this->accessToken,
            'Content-Type' => 'application/json',
        ]);

        return match (strtoupper($method)) {
            'GET' => $http->get($url, $data),
            'POST' => $http->post($url, $data),
            'PUT' => $http->put($url, $data),
            'DELETE' => $http->delete($url, $data),
            default => $http->get($url, $data),
        };
    }

    protected function extractNextPageInfo(?string $linkHeader): ?string
    {
        if (! $linkHeader) {
            return null;
        }

        if (preg_match('/<[^>]+page_info=([^>&>]+)[^>]*>;\s*rel="next"/', $linkHeader, $matches)) {
            return $matches[1];
        }

        return null;
    }
}
