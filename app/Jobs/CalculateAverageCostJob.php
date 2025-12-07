<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Product;
use App\Models\StockMovement;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class CalculateAverageCostJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public $timeout = 120;

    public function __construct(public int $productId) {}

    public function handle(): void
    {
        $product = Product::query()->find($this->productId);
        if (! $product) {
            return;
        }

        // Simple moving average based on last N inbound movements
        $movements = StockMovement::query()
            ->where('product_id', $product->getKey())
            ->where('direction', 'in')
            ->latest('id')
            ->limit(config('inventory.avg_window', 50))
            ->get(['qty', 'note', 'ref_type', 'ref_id']);

        if ($movements->isEmpty()) {
            return;
        }

        // Assume purchase price saved on ref if needed; here we fallback to unit price in notes (optional)
        $totalQty = 0.0;
        $totalCost = 0.0;
        foreach ($movements as $m) {
            $qty = (float) $m->qty;
            if ($qty <= 0) {
                continue;
            }
            $price = 0.0;
            // Try to infer purchase item price if relation available
            if ($m->ref_type === 'purchase' && class_exists(\App\Models\PurchaseItem::class)) {
                $pi = \App\Models\PurchaseItem::query()
                    ->where('purchase_id', $m->ref_id)
                    ->where('product_id', $product->getKey())
                    ->first();
                $price = (float) ($pi?->price ?? 0.0);
            }
            $totalQty += $qty;
            $totalCost += $qty * $price;
        }

        if ($totalQty > 0 && $totalCost > 0) {
            $product->cost = round($totalCost / $totalQty, 2);
            $product->save();
        }
    }

    public function tags(): array
    {
        return ['inventory', 'cost', 'product:'.$this->productId];
    }
}
