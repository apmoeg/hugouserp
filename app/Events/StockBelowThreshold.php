<?php

declare(strict_types=1);

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class StockBelowThreshold
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public \App\Models\Product $product,
        public ?\App\Models\Warehouse $warehouse = null,
        public float $currentQty = 0.0,
        public float $threshold = 0.0
    ) {}
}
