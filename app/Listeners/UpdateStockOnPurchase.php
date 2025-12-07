<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\PurchaseReceived;
use App\Models\StockMovement;
use Illuminate\Contracts\Queue\ShouldQueue;

class UpdateStockOnPurchase implements ShouldQueue
{
    public function handle(PurchaseReceived $event): void
    {
        $purchase = $event->purchase;
        $branchId = $purchase->branch_id;
        $warehouseId = $purchase->warehouse_id;

        foreach ($purchase->items as $item) {
            StockMovement::create([
                'branch_id' => $branchId,
                'warehouse_id' => $warehouseId,
                'product_id' => $item->product_id,
                'ref_type' => 'purchase',
                'ref_id' => $purchase->getKey(),
                'qty' => $item->qty,
                'direction' => 'in',
                'note' => 'Purchase received',
                'created_by' => $purchase->created_by,
            ]);
        }
    }
}
