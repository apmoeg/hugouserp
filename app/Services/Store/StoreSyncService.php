<?php

declare(strict_types=1);

namespace App\Services\Store;

use App\Models\Customer;
use App\Models\Product;
use App\Models\ProductStoreMapping;
use App\Models\Sale;
use App\Models\Store;
use App\Models\StoreSyncLog;
use App\Services\Store\Clients\ShopifyClient;
use App\Services\Store\Clients\WooCommerceClient;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class StoreSyncService
{
    public function pullProductsFromShopify(Store $store): StoreSyncLog
    {
        $log = $this->createSyncLog($store, StoreSyncLog::TYPE_PRODUCTS, StoreSyncLog::DIRECTION_PULL);

        try {
            $client = new ShopifyClient($store);
            $products = $client->getProducts();

            foreach ($products as $product) {
                try {
                    $this->syncShopifyProductToERP($store, $product);
                    $log->incrementSuccess();
                } catch (\Exception $e) {
                    $log->incrementFailed();
                    Log::error('Failed to sync Shopify product: '.$e->getMessage());
                }
            }

            $log->markCompleted();
        } catch (\Exception $e) {
            $log->markFailed($e->getMessage());
        }

        return $log;
    }

    public function pushStockToShopify(Store $store): StoreSyncLog
    {
        $log = $this->createSyncLog($store, StoreSyncLog::TYPE_INVENTORY, StoreSyncLog::DIRECTION_PUSH);

        try {
            $client = new ShopifyClient($store);
            $mappings = ProductStoreMapping::where('store_id', $store->id)->with('product')->get();

            foreach ($mappings as $mapping) {
                try {
                    $product = $mapping->product;
                    if ($product) {
                        $client->updateInventory($mapping->external_id, $product->quantity);
                        $mapping->markSynced();
                        $log->incrementSuccess();
                    }
                } catch (\Exception $e) {
                    $log->incrementFailed();
                    Log::error('Failed to push stock to Shopify: '.$e->getMessage());
                }
            }

            $log->markCompleted();
        } catch (\Exception $e) {
            $log->markFailed($e->getMessage());
        }

        return $log;
    }

    public function pullOrdersFromShopify(Store $store): StoreSyncLog
    {
        $log = $this->createSyncLog($store, StoreSyncLog::TYPE_ORDERS, StoreSyncLog::DIRECTION_PULL);

        try {
            $client = new ShopifyClient($store);
            $orders = $client->getOrders();

            foreach ($orders as $order) {
                try {
                    $this->syncShopifyOrderToERP($store, $order);
                    $log->incrementSuccess();
                } catch (\Exception $e) {
                    $log->incrementFailed();
                    Log::error('Failed to sync Shopify order: '.$e->getMessage());
                }
            }

            $log->markCompleted();
        } catch (\Exception $e) {
            $log->markFailed($e->getMessage());
        }

        return $log;
    }

    public function pullProductsFromWooCommerce(Store $store): StoreSyncLog
    {
        $log = $this->createSyncLog($store, StoreSyncLog::TYPE_PRODUCTS, StoreSyncLog::DIRECTION_PULL);

        try {
            $client = new WooCommerceClient($store);
            $products = $client->getProducts();

            foreach ($products as $product) {
                try {
                    $this->syncWooProductToERP($store, $product);
                    $log->incrementSuccess();
                } catch (\Exception $e) {
                    $log->incrementFailed();
                    Log::error('Failed to sync WooCommerce product: '.$e->getMessage());
                }
            }

            $log->markCompleted();
        } catch (\Exception $e) {
            $log->markFailed($e->getMessage());
        }

        return $log;
    }

    public function pushStockToWooCommerce(Store $store): StoreSyncLog
    {
        $log = $this->createSyncLog($store, StoreSyncLog::TYPE_INVENTORY, StoreSyncLog::DIRECTION_PUSH);

        try {
            $client = new WooCommerceClient($store);
            $mappings = ProductStoreMapping::where('store_id', $store->id)->with('product')->get();

            foreach ($mappings as $mapping) {
                try {
                    $product = $mapping->product;
                    if ($product) {
                        $client->updateStock($mapping->external_id, (int) $product->quantity);
                        $mapping->markSynced();
                        $log->incrementSuccess();
                    }
                } catch (\Exception $e) {
                    $log->incrementFailed();
                    Log::error('Failed to push stock to WooCommerce: '.$e->getMessage());
                }
            }

            $log->markCompleted();
        } catch (\Exception $e) {
            $log->markFailed($e->getMessage());
        }

        return $log;
    }

    public function pullOrdersFromWooCommerce(Store $store): StoreSyncLog
    {
        $log = $this->createSyncLog($store, StoreSyncLog::TYPE_ORDERS, StoreSyncLog::DIRECTION_PULL);

        try {
            $client = new WooCommerceClient($store);
            $orders = $client->getOrders();

            foreach ($orders as $order) {
                try {
                    $this->syncWooOrderToERP($store, $order);
                    $log->incrementSuccess();
                } catch (\Exception $e) {
                    $log->incrementFailed();
                    Log::error('Failed to sync WooCommerce order: '.$e->getMessage());
                }
            }

            $log->markCompleted();
        } catch (\Exception $e) {
            $log->markFailed($e->getMessage());
        }

        return $log;
    }

    public function handleShopifyProductUpdate(Store $store, array $data): void
    {
        $this->syncShopifyProductToERP($store, $data);
    }

    public function handleShopifyProductDelete(Store $store, array $data): void
    {
        $externalId = (string) ($data['id'] ?? '');
        if ($externalId) {
            ProductStoreMapping::where('store_id', $store->id)
                ->where('external_id', $externalId)
                ->delete();
        }
    }

    public function handleShopifyOrderCreate(Store $store, array $data): void
    {
        $this->syncShopifyOrderToERP($store, $data);
    }

    public function handleShopifyOrderUpdate(Store $store, array $data): void
    {
        $this->syncShopifyOrderToERP($store, $data);
    }

    public function handleShopifyInventoryUpdate(Store $store, array $data): void
    {
        $inventoryItemId = $data['inventory_item_id'] ?? null;
        $available = $data['available'] ?? null;

        if ($inventoryItemId && $available !== null) {
            $mapping = ProductStoreMapping::where('store_id', $store->id)
                ->whereJsonContains('external_data->inventory_item_id', $inventoryItemId)
                ->first();

            if ($mapping && $mapping->product) {
                $mapping->product->update(['quantity' => $available]);
            }
        }
    }

    public function handleWooProductUpdate(Store $store, array $data): void
    {
        $this->syncWooProductToERP($store, $data);
    }

    public function handleWooProductDelete(Store $store, array $data): void
    {
        $externalId = (string) ($data['id'] ?? '');
        if ($externalId) {
            ProductStoreMapping::where('store_id', $store->id)
                ->where('external_id', $externalId)
                ->delete();
        }
    }

    public function handleWooOrderCreate(Store $store, array $data): void
    {
        $this->syncWooOrderToERP($store, $data);
    }

    public function handleWooOrderUpdate(Store $store, array $data): void
    {
        $this->syncWooOrderToERP($store, $data);
    }

    protected function syncShopifyProductToERP(Store $store, array $data): void
    {
        $externalId = (string) ($data['id'] ?? '');
        if (! $externalId) {
            return;
        }

        DB::transaction(function () use ($store, $data, $externalId) {
            $mapping = ProductStoreMapping::where('store_id', $store->id)
                ->where('external_id', $externalId)
                ->first();

            $productData = [
                'name' => $data['title'] ?? 'Unknown Product',
                'description' => strip_tags($data['body_html'] ?? ''),
                'sku' => $data['variants'][0]['sku'] ?? 'SHOP-'.$externalId,
                'price' => (float) ($data['variants'][0]['price'] ?? 0),
                'quantity' => (int) ($data['variants'][0]['inventory_quantity'] ?? 0),
                'branch_id' => $store->branch_id,
            ];

            if ($mapping) {
                $mapping->product->update($productData);
                $mapping->update([
                    'external_data' => $data,
                    'last_synced_at' => now(),
                ]);
            } else {
                $product = Product::create($productData);
                ProductStoreMapping::create([
                    'product_id' => $product->id,
                    'store_id' => $store->id,
                    'external_id' => $externalId,
                    'external_sku' => $data['variants'][0]['sku'] ?? null,
                    'external_data' => $data,
                    'last_synced_at' => now(),
                ]);
            }
        });
    }

    protected function syncShopifyOrderToERP(Store $store, array $data): void
    {
        $externalId = (string) ($data['id'] ?? '');
        if (! $externalId) {
            return;
        }

        $existingOrder = Sale::where('external_reference', $externalId)
            ->where('source', 'shopify')
            ->first();

        if ($existingOrder) {
            $existingOrder->update([
                'status' => $this->mapShopifyOrderStatus($data['financial_status'] ?? 'pending'),
            ]);

            return;
        }

        DB::transaction(function () use ($store, $data, $externalId) {
            $customerData = $data['customer'] ?? null;
            $customerId = null;

            if ($customerData) {
                $customer = Customer::firstOrCreate(
                    ['email' => $customerData['email'] ?? 'shopify-'.($customerData['id'] ?? '').'@unknown.com'],
                    [
                        'name' => trim(($customerData['first_name'] ?? '').' '.($customerData['last_name'] ?? '')),
                        'phone' => $customerData['phone'] ?? null,
                        'branch_id' => $store->branch_id,
                    ]
                );
                $customerId = $customer->id;
            }

            $sale = Sale::create([
                'branch_id' => $store->branch_id,
                'customer_id' => $customerId,
                'subtotal' => (float) ($data['subtotal_price'] ?? 0),
                'tax' => (float) ($data['total_tax'] ?? 0),
                'discount' => (float) ($data['total_discounts'] ?? 0),
                'total' => (float) ($data['total_price'] ?? 0),
                'status' => $this->mapShopifyOrderStatus($data['financial_status'] ?? 'pending'),
                'source' => 'shopify',
                'external_reference' => $externalId,
            ]);

            foreach ($data['line_items'] ?? [] as $lineItem) {
                $productMapping = ProductStoreMapping::where('store_id', $store->id)
                    ->where('external_id', (string) $lineItem['product_id'])
                    ->first();

                $sale->items()->create([
                    'product_id' => $productMapping?->product_id,
                    'quantity' => (int) ($lineItem['quantity'] ?? 1),
                    'unit_price' => (float) ($lineItem['price'] ?? 0),
                    'discount' => (float) ($lineItem['total_discount'] ?? 0),
                    'total' => (float) ($lineItem['quantity'] ?? 1) * (float) ($lineItem['price'] ?? 0) - (float) ($lineItem['total_discount'] ?? 0),
                ]);
            }
        });
    }

    protected function syncWooProductToERP(Store $store, array $data): void
    {
        $externalId = (string) ($data['id'] ?? '');
        if (! $externalId) {
            return;
        }

        DB::transaction(function () use ($store, $data, $externalId) {
            $mapping = ProductStoreMapping::where('store_id', $store->id)
                ->where('external_id', $externalId)
                ->first();

            $productData = [
                'name' => $data['name'] ?? 'Unknown Product',
                'description' => strip_tags($data['description'] ?? ''),
                'sku' => $data['sku'] ?? 'WOO-'.$externalId,
                'price' => (float) ($data['price'] ?? 0),
                'quantity' => (int) ($data['stock_quantity'] ?? 0),
                'branch_id' => $store->branch_id,
            ];

            if ($mapping) {
                $mapping->product->update($productData);
                $mapping->update([
                    'external_data' => $data,
                    'last_synced_at' => now(),
                ]);
            } else {
                $product = Product::create($productData);
                ProductStoreMapping::create([
                    'product_id' => $product->id,
                    'store_id' => $store->id,
                    'external_id' => $externalId,
                    'external_sku' => $data['sku'] ?? null,
                    'external_data' => $data,
                    'last_synced_at' => now(),
                ]);
            }
        });
    }

    protected function syncWooOrderToERP(Store $store, array $data): void
    {
        $externalId = (string) ($data['id'] ?? '');
        if (! $externalId) {
            return;
        }

        $existingOrder = Sale::where('external_reference', $externalId)
            ->where('source', 'woocommerce')
            ->first();

        if ($existingOrder) {
            $existingOrder->update([
                'status' => $this->mapWooOrderStatus($data['status'] ?? 'pending'),
            ]);

            return;
        }

        DB::transaction(function () use ($store, $data, $externalId) {
            $billing = $data['billing'] ?? [];
            $customerId = null;

            if (! empty($billing['email'])) {
                $customer = Customer::firstOrCreate(
                    ['email' => $billing['email']],
                    [
                        'name' => trim(($billing['first_name'] ?? '').' '.($billing['last_name'] ?? '')),
                        'phone' => $billing['phone'] ?? null,
                        'address' => ($billing['address_1'] ?? '').' '.($billing['address_2'] ?? ''),
                        'city' => $billing['city'] ?? null,
                        'country' => $billing['country'] ?? null,
                        'branch_id' => $store->branch_id,
                    ]
                );
                $customerId = $customer->id;
            }

            $sale = Sale::create([
                'branch_id' => $store->branch_id,
                'customer_id' => $customerId,
                'subtotal' => (float) ($data['total'] ?? 0) - (float) ($data['total_tax'] ?? 0),
                'tax' => (float) ($data['total_tax'] ?? 0),
                'discount' => (float) ($data['discount_total'] ?? 0),
                'total' => (float) ($data['total'] ?? 0),
                'status' => $this->mapWooOrderStatus($data['status'] ?? 'pending'),
                'source' => 'woocommerce',
                'external_reference' => $externalId,
            ]);

            foreach ($data['line_items'] ?? [] as $lineItem) {
                $productMapping = ProductStoreMapping::where('store_id', $store->id)
                    ->where('external_id', (string) $lineItem['product_id'])
                    ->first();

                $sale->items()->create([
                    'product_id' => $productMapping?->product_id,
                    'quantity' => (int) ($lineItem['quantity'] ?? 1),
                    'unit_price' => (float) ($lineItem['price'] ?? 0),
                    'discount' => 0,
                    'total' => (float) ($lineItem['total'] ?? 0),
                ]);
            }
        });
    }

    protected function mapShopifyOrderStatus(string $status): string
    {
        return match ($status) {
            'paid' => 'completed',
            'pending' => 'pending',
            'refunded', 'partially_refunded' => 'refunded',
            'voided' => 'cancelled',
            default => 'pending',
        };
    }

    protected function mapWooOrderStatus(string $status): string
    {
        return match ($status) {
            'completed' => 'completed',
            'processing' => 'processing',
            'on-hold', 'pending' => 'pending',
            'cancelled' => 'cancelled',
            'refunded' => 'refunded',
            default => 'pending',
        };
    }

    protected function createSyncLog(Store $store, string $type, string $direction): StoreSyncLog
    {
        return StoreSyncLog::create([
            'store_id' => $store->id,
            'type' => $type,
            'direction' => $direction,
            'status' => StoreSyncLog::STATUS_RUNNING,
            'started_at' => now(),
        ]);
    }
}
