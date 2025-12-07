<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Models\Product;
use App\Models\ProductStoreMapping;
use App\Models\StockMovement;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InventoryController extends BaseApiController
{
    public function getStock(Request $request): JsonResponse
    {
        $store = $this->getStore($request);

        $query = Product::query()
            ->select('id', 'name', 'sku', 'quantity', 'min_stock', 'warehouse_id')
            ->when($store?->branch_id, fn ($q) => $q->where('branch_id', $store->branch_id))
            ->when($request->filled('sku'), fn ($q) => $q->where('sku', $request->sku)
            )
            ->when($request->filled('warehouse_id'), fn ($q) => $q->where('warehouse_id', $request->warehouse_id)
            )
            ->when($request->boolean('low_stock'), fn ($q) => $q->whereColumn('quantity', '<=', 'min_stock')
            );

        $products = $query->paginate($request->get('per_page', 100));

        return $this->paginatedResponse($products, __('Stock levels retrieved successfully'));
    }

    public function updateStock(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'product_id' => 'required_without:external_id|exists:products,id',
            'external_id' => 'required_without:product_id|string',
            'quantity' => 'required|integer',
            'type' => 'required|in:set,adjust',
            'reason' => 'nullable|string|max:255',
        ]);

        $store = $this->getStore($request);

        $product = null;

        if ($request->filled('product_id')) {
            $product = Product::query()
                ->when($store?->branch_id, fn ($q) => $q->where('branch_id', $store->branch_id))
                ->find($validated['product_id']);
        } elseif ($request->filled('external_id') && $store) {
            $mapping = ProductStoreMapping::where('store_id', $store->id)
                ->where('external_id', $validated['external_id'])
                ->first();

            if ($mapping) {
                $product = $mapping->product;
            }
        }

        if (! $product) {
            return $this->errorResponse(__('Product not found'), 404);
        }

        $oldQuantity = $product->quantity;
        $newQuantity = $validated['type'] === 'set'
            ? $validated['quantity']
            : $product->quantity + $validated['quantity'];

        DB::transaction(function () use ($product, $newQuantity, $oldQuantity, $validated) {
            $product->update(['quantity' => max(0, $newQuantity)]);

            StockMovement::create([
                'product_id' => $product->id,
                'warehouse_id' => $product->warehouse_id,
                'type' => $newQuantity > $oldQuantity ? 'in' : 'out',
                'quantity' => abs($newQuantity - $oldQuantity),
                'before_quantity' => $oldQuantity,
                'after_quantity' => $product->quantity,
                'reason' => $validated['reason'] ?? 'API stock update',
                'reference_type' => 'api_sync',
            ]);
        });

        return $this->successResponse([
            'product_id' => $product->id,
            'sku' => $product->sku,
            'old_quantity' => $oldQuantity,
            'new_quantity' => $product->quantity,
        ], __('Stock updated successfully'));
    }

    public function bulkUpdateStock(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'items' => 'required|array|min:1|max:100',
            'items.*.product_id' => 'required_without:items.*.external_id|exists:products,id',
            'items.*.external_id' => 'required_without:items.*.product_id|string',
            'items.*.quantity' => 'required|integer',
            'items.*.type' => 'required|in:set,adjust',
        ]);

        $store = $this->getStore($request);
        $results = [
            'success' => [],
            'failed' => [],
        ];

        foreach ($validated['items'] as $item) {
            $product = null;

            if (isset($item['product_id'])) {
                $product = Product::query()
                    ->when($store?->branch_id, fn ($q) => $q->where('branch_id', $store->branch_id))
                    ->find($item['product_id']);
            } elseif (isset($item['external_id']) && $store) {
                $mapping = ProductStoreMapping::where('store_id', $store->id)
                    ->where('external_id', $item['external_id'])
                    ->first();

                if ($mapping) {
                    $product = $mapping->product;
                }
            }

            if (! $product) {
                $results['failed'][] = [
                    'identifier' => $item['product_id'] ?? $item['external_id'],
                    'error' => __('Product not found'),
                ];

                continue;
            }

            try {
                $oldQuantity = $product->quantity;
                $newQuantity = $item['type'] === 'set'
                    ? $item['quantity']
                    : $product->quantity + $item['quantity'];

                $product->update(['quantity' => max(0, $newQuantity)]);

                $results['success'][] = [
                    'product_id' => $product->id,
                    'sku' => $product->sku,
                    'old_quantity' => $oldQuantity,
                    'new_quantity' => $product->quantity,
                ];
            } catch (\Exception $e) {
                $results['failed'][] = [
                    'identifier' => $item['product_id'] ?? $item['external_id'],
                    'error' => $e->getMessage(),
                ];
            }
        }

        return $this->successResponse($results, __('Bulk stock update completed'));
    }

    public function getMovements(Request $request): JsonResponse
    {
        $store = $this->getStore($request);

        $query = StockMovement::query()
            ->with(['product:id,name,sku'])
            ->when($store?->branch_id, fn ($q) => $q->whereHas('product', fn ($pq) => $pq->where('branch_id', $store->branch_id))
            )
            ->when($request->filled('product_id'), fn ($q) => $q->where('product_id', $request->product_id)
            )
            ->when($request->filled('type'), fn ($q) => $q->where('type', $request->type)
            )
            ->when($request->filled('from_date'), fn ($q) => $q->whereDate('created_at', '>=', $request->from_date)
            )
            ->when($request->filled('to_date'), fn ($q) => $q->whereDate('created_at', '<=', $request->to_date)
            )
            ->orderBy('created_at', 'desc');

        $movements = $query->paginate($request->get('per_page', 50));

        return $this->paginatedResponse($movements, __('Stock movements retrieved successfully'));
    }
}
