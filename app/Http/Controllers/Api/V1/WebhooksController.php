<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Models\Store;
use App\Services\Store\StoreSyncService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WebhooksController extends BaseApiController
{
    public function __construct(
        protected StoreSyncService $syncService
    ) {}

    public function handleShopify(Request $request, int $storeId): JsonResponse
    {
        $store = Store::with('integration')->find($storeId);

        if (! $store || ! $store->is_active || $store->type !== 'shopify') {
            return $this->errorResponse(__('Store not found or not active'), 404);
        }

        if (! $this->verifyShopifyWebhook($request, $store)) {
            return $this->errorResponse(__('Invalid webhook signature'), 401);
        }

        $topic = $request->header('X-Shopify-Topic');
        $data = $request->all();

        try {
            match ($topic) {
                'products/create', 'products/update' => $this->syncService->handleShopifyProductUpdate($store, $data),
                'products/delete' => $this->syncService->handleShopifyProductDelete($store, $data),
                'orders/create' => $this->syncService->handleShopifyOrderCreate($store, $data),
                'orders/updated' => $this->syncService->handleShopifyOrderUpdate($store, $data),
                'inventory_levels/update' => $this->syncService->handleShopifyInventoryUpdate($store, $data),
                default => null,
            };

            return $this->successResponse(null, __('Webhook processed successfully'));
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 500);
        }
    }

    public function handleWooCommerce(Request $request, int $storeId): JsonResponse
    {
        $store = Store::with('integration')->find($storeId);

        if (! $store || ! $store->is_active || $store->type !== 'woocommerce') {
            return $this->errorResponse(__('Store not found or not active'), 404);
        }

        if (! $this->verifyWooCommerceWebhook($request, $store)) {
            return $this->errorResponse(__('Invalid webhook signature'), 401);
        }

        $topic = $request->header('X-WC-Webhook-Topic');
        $data = $request->all();

        try {
            match ($topic) {
                'product.created', 'product.updated' => $this->syncService->handleWooProductUpdate($store, $data),
                'product.deleted' => $this->syncService->handleWooProductDelete($store, $data),
                'order.created' => $this->syncService->handleWooOrderCreate($store, $data),
                'order.updated' => $this->syncService->handleWooOrderUpdate($store, $data),
                default => null,
            };

            return $this->successResponse(null, __('Webhook processed successfully'));
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 500);
        }
    }

    protected function verifyShopifyWebhook(Request $request, Store $store): bool
    {
        $hmacHeader = $request->header('X-Shopify-Hmac-Sha256');
        $secret = $store->integration?->webhook_secret;

        if (! $hmacHeader || ! $secret) {
            return false;
        }

        $calculatedHmac = base64_encode(hash_hmac('sha256', $request->getContent(), $secret, true));

        return hash_equals($hmacHeader, $calculatedHmac);
    }

    protected function verifyWooCommerceWebhook(Request $request, Store $store): bool
    {
        $signature = $request->header('X-WC-Webhook-Signature');
        $secret = $store->integration?->webhook_secret;

        if (! $signature || ! $secret) {
            return false;
        }

        $calculatedSignature = base64_encode(hash_hmac('sha256', $request->getContent(), $secret, true));

        return hash_equals($signature, $calculatedSignature);
    }
}
