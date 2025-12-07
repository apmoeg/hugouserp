<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\StockBelowThreshold;
use App\Notifications\GeneralNotification;
use Illuminate\Contracts\Queue\ShouldQueue;

class ProposePurchaseOrder implements ShouldQueue
{
    public function handle(StockBelowThreshold $event): void
    {
        $product = $event->product;
        $warehouse = $event->warehouse;
        $current = $event->currentQty ?? 0.0;
        $threshold = $event->threshold ?? 0.0;

        $title = __('Stock low: :name', ['name' => $product->name]);
        $body = __('Current :current below threshold :threshold in :wh', [
            'current' => number_format($current, 2),
            'threshold' => number_format($threshold, 2),
            'wh' => $warehouse?->name ?? 'N/A',
        ]);

        // Notify branch managers (assumes a scope/role exists)
        $notifiables = \App\Models\User::query()
            ->whereHas('roles', fn ($q) => $q->where('name', 'Branch Manager'))
            ->get();

        foreach ($notifiables as $user) {
            $user->notify(new GeneralNotification($title, $body, [
                'product_id' => $product->getKey(),
                'warehouse_id' => $warehouse?->getKey(),
            ]));
        }
    }
}
