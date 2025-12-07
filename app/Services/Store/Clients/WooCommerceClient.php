<?php

declare(strict_types=1);

namespace App\Services\Store\Clients;

use App\Models\Store;
use Illuminate\Support\Facades\Http;

class WooCommerceClient
{
    protected Store $store;

    protected string $baseUrl;

    protected string $consumerKey;

    protected string $consumerSecret;

    public function __construct(Store $store)
    {
        $this->store = $store;
        $this->baseUrl = rtrim($store->url, '/').'/wp-json/wc/v3';
        $this->consumerKey = $store->integration?->api_key ?? '';
        $this->consumerSecret = $store->integration?->api_secret ?? '';
    }

    public function getProducts(int $perPage = 100): array
    {
        $products = [];
        $page = 1;

        do {
            $response = $this->request('GET', '/products', [
                'per_page' => $perPage,
                'page' => $page,
            ]);

            if (! $response->successful()) {
                break;
            }

            $data = $response->json();
            if (empty($data)) {
                break;
            }

            $products = array_merge($products, $data);
            $page++;

            $totalPages = (int) $response->header('X-WP-TotalPages', 1);

        } while ($page <= $totalPages);

        return $products;
    }

    public function getProduct(string $productId): ?array
    {
        $response = $this->request('GET', "/products/{$productId}");

        if ($response->successful()) {
            return $response->json();
        }

        return null;
    }

    public function updateProduct(string $productId, array $data): bool
    {
        $response = $this->request('PUT', "/products/{$productId}", $data);

        return $response->successful();
    }

    public function updateStock(string $productId, int $quantity): bool
    {
        return $this->updateProduct($productId, [
            'stock_quantity' => $quantity,
            'manage_stock' => true,
        ]);
    }

    public function getOrders(string $status = 'any', int $perPage = 100): array
    {
        $orders = [];
        $page = 1;

        do {
            $params = [
                'per_page' => $perPage,
                'page' => $page,
            ];

            if ($status !== 'any') {
                $params['status'] = $status;
            }

            $response = $this->request('GET', '/orders', $params);

            if (! $response->successful()) {
                break;
            }

            $data = $response->json();
            if (empty($data)) {
                break;
            }

            $orders = array_merge($orders, $data);
            $page++;

            $totalPages = (int) $response->header('X-WP-TotalPages', 1);

        } while ($page <= $totalPages);

        return $orders;
    }

    public function getOrder(string $orderId): ?array
    {
        $response = $this->request('GET', "/orders/{$orderId}");

        if ($response->successful()) {
            return $response->json();
        }

        return null;
    }

    public function updateOrderStatus(string $orderId, string $status): bool
    {
        $response = $this->request('PUT', "/orders/{$orderId}", [
            'status' => $status,
        ]);

        return $response->successful();
    }

    public function getCustomers(int $perPage = 100): array
    {
        $customers = [];
        $page = 1;

        do {
            $response = $this->request('GET', '/customers', [
                'per_page' => $perPage,
                'page' => $page,
            ]);

            if (! $response->successful()) {
                break;
            }

            $data = $response->json();
            if (empty($data)) {
                break;
            }

            $customers = array_merge($customers, $data);
            $page++;

            $totalPages = (int) $response->header('X-WP-TotalPages', 1);

        } while ($page <= $totalPages);

        return $customers;
    }

    public function registerWebhooks(array $topics): array
    {
        $results = [];
        $webhookUrl = route('webhooks.woocommerce', ['storeId' => $this->store->id]);

        foreach ($topics as $topic) {
            $response = $this->request('POST', '/webhooks', [
                'name' => 'ERP Integration - '.$topic,
                'topic' => $topic,
                'delivery_url' => $webhookUrl,
                'secret' => $this->store->integration?->webhook_secret ?? '',
            ]);

            $results[$topic] = $response->successful();
        }

        return $results;
    }

    protected function request(string $method, string $endpoint, array $data = [])
    {
        $url = $this->baseUrl.$endpoint;

        $http = Http::withBasicAuth($this->consumerKey, $this->consumerSecret)
            ->withHeaders(['Content-Type' => 'application/json']);

        return match (strtoupper($method)) {
            'GET' => $http->get($url, $data),
            'POST' => $http->post($url, $data),
            'PUT' => $http->put($url, $data),
            'DELETE' => $http->delete($url, $data),
            default => $http->get($url, $data),
        };
    }
}
