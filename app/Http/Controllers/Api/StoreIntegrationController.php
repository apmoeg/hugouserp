<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductVariation;
use App\Models\StoreOrder;
use App\Services\Store\StoreOrderToSaleService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class StoreIntegrationController extends Controller
{
    public function products(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $user || ! $user->can('store.api.products')) {
            abort(403);
        }

        $perPage = (int) $request->input('per_page', 50);
        $perPage = max(1, min($perPage, 200));

        $query = Product::query()->with('variations');

        if ($request->filled('branch_id')) {
            $query->where('branch_id', (int) $request->input('branch_id'));
        }

        if ($request->filled('updated_since')) {
            $query->where('updated_at', '>=', $request->input('updated_since'));
        }

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search): void {
                $q->where('sku', 'like', '%'.$search.'%')
                    ->orWhere('name', 'like', '%'.$search.'%');
            });
        }

        $products = $query->paginate($perPage);

        return response()->json($products);
    }

    public function stock(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $user || ! $user->can('store.api.products')) {
            abort(403);
        }

        $skus = (array) $request->input('skus', []);

        $query = Product::query();

        if (! empty($skus)) {
            $query->whereIn('sku', $skus);
        }

        if ($request->filled('branch_id')) {
            $query->where('branch_id', (int) $request->input('branch_id'));
        }

        $rows = $query
            ->get(['id', 'sku', 'name', 'current_stock'])
            ->map(static function (Product $product): array {
                return [
                    'id' => $product->getKey(),
                    'sku' => $product->sku,
                    'name' => $product->name,
                    'current_stock' => (float) ($product->current_stock ?? 0),
                ];
            })
            ->values()
            ->all();

        return response()->json($rows);
    }

    public function syncStock(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $user || ! $user->can('store.api.products')) {
            abort(403);
        }

        $validated = $request->validate([
            'items' => ['required', 'array', 'min:1'],
            'items.*.sku' => ['nullable', 'string', 'max:191'],
            'items.*.variation_id' => ['nullable', 'integer'],
            'items.*.variation_sku' => ['nullable', 'string', 'max:191'],
            'items.*.qty' => ['required', 'numeric'],
        ]);

        $updated = [];
        $errors = [];

        foreach ($validated['items'] as $row) {
            $qty = (float) $row['qty'];

            try {
                $target = null;
                $type = null;

                if (! empty($row['variation_id'])) {
                    $target = ProductVariation::query()->find((int) $row['variation_id']);
                    $type = 'variation';
                } elseif (! empty($row['variation_sku'])) {
                    $target = ProductVariation::query()->where('sku', $row['variation_sku'])->first();
                    $type = 'variation';
                } elseif (! empty($row['sku'])) {
                    $target = Product::query()->where('sku', $row['sku'])->first();
                    $type = 'product';
                }

                if (! $target) {
                    $errors[] = [
                        'item' => $row,
                        'reason' => 'not_found',
                    ];

                    continue;
                }

                $target->current_stock = $qty;
                $target->save();

                $updated[] = [
                    'type' => $type,
                    'id' => $target->getKey(),
                ];
            } catch (\Throwable $e) {
                $errors[] = [
                    'item' => $row,
                    'reason' => 'exception',
                ];
            }
        }

        return response()->json([
            'updated' => $updated,
            'errors' => $errors,
        ]);
    }

    /**
     * Store order endpoint.
     * 
     * Handles store order creation with best-effort conversion to Sale.
     * If conversion fails, the error is logged but the API response remains stable (201 Created).
     * This ensures external integrations remain reliable while allowing internal diagnosis
     * of conversion issues through logs.
     */
    public function storeOrder(Request $request, StoreOrderToSaleService $converter): JsonResponse
    {
        $user = $request->user();

        if (! $user || ! $user->can('store.api.orders')) {
            abort(403);
        }

        $validated = $request->validate([
            'external_id' => ['required', 'string', 'max:191'],
            'branch_id' => ['required', 'integer', 'exists:branches,id'],
            'currency' => ['nullable', 'string', 'max:10'],
            'total' => ['nullable', 'numeric'],
            'discount_total' => ['nullable', 'numeric'],
            'shipping_total' => ['nullable', 'numeric'],
            'tax_total' => ['nullable', 'numeric'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.sku' => ['nullable', 'string', 'max:191'],
            'items.*.variation_id' => ['nullable', 'integer'],
            'items.*.variation_sku' => ['nullable', 'string', 'max:191'],
            'items.*.qty' => ['required', 'numeric', 'min:0.01'],
            'items.*.price' => ['nullable', 'numeric'],
            'items.*.discount' => ['nullable', 'numeric'],
            'items.*.total' => ['nullable', 'numeric'],
            'customer' => ['nullable', 'array'],
            'meta' => ['nullable', 'array'],
        ]);

        $order = StoreOrder::query()->updateOrCreate(
            [
                'external_order_id' => $validated['external_id'],
                'branch_id' => $validated['branch_id'],
            ],
            [
                'status' => 'pending',
                'currency' => $validated['currency'] ?? null,
                'total' => $validated['total'] ?? 0,
                'discount_total' => $validated['discount_total'] ?? 0,
                'shipping_total' => $validated['shipping_total'] ?? 0,
                'tax_total' => $validated['tax_total'] ?? 0,
                'payload' => $validated,
            ]
        );

        // Best-effort stock sync based on items
        try {
            foreach ($validated['items'] as $item) {
                $qty = (float) $item['qty'];

                if ($qty <= 0) {
                    continue;
                }

                $target = null;

                if (! empty($item['variation_id'])) {
                    $target = ProductVariation::query()->find((int) $item['variation_id']);
                } elseif (! empty($item['variation_sku'])) {
                    $target = ProductVariation::query()->where('sku', $item['variation_sku'])->first();
                } elseif (! empty($item['sku'])) {
                    $target = Product::query()->where('sku', $item['sku'])->first();
                }

                if (! $target) {
                    continue;
                }

                try {
                    $current = (float) ($target->current_stock ?? 0);
                    $target->current_stock = max(0, $current - $qty);
                    $target->save();
                } catch (\Throwable $e) {
                    // ignore stock save issues
                }
            }
        } catch (\Throwable $e) {
            // ignore
        }

        // Auto-convert to Sale (best-effort with error logging)
        try {
            $converter->convert($order);
        } catch (\Throwable $e) {
            // Log conversion failure with context for diagnosis
            Log::warning('Store order conversion to sale failed', [
                'order_id' => $order->id,
                'external_id' => $order->external_order_id,
                'branch_id' => $order->branch_id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            // Keep API response stable despite conversion failure
        }

        return response()->json([
            'id' => $order->id,
            'status' => $order->status,
            'external_id' => $order->external_order_id,
        ], 201);
    }

    public function updateOrderStatus(Request $request, string $externalId): JsonResponse
    {
        $user = $request->user();

        if (! $user || ! $user->can('store.api.orders')) {
            abort(403);
        }

        $validated = $request->validate([
            'status' => ['required', 'string', 'max:50'],
            'branch_id' => ['required', 'integer', 'exists:branches,id'],
        ]);

        $order = StoreOrder::query()
            ->where('external_order_id', $externalId)
            ->where('branch_id', $validated['branch_id'])
            ->firstOrFail();

        $order->update([
            'status' => $validated['status'],
        ]);

        return response()->json([
            'id' => $order->id,
            'status' => $order->status,
            'external_id' => $order->external_order_id,
        ]);
    }
}
