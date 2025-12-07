<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\SaleCompleted;
use App\Models\StockMovement;
use Illuminate\Contracts\Queue\ShouldQueue;

class UpdateStockOnSale implements ShouldQueue
{
    public function handle(SaleCompleted $event): void
    {
        $sale = $event->sale;
        $branchId = $sale->branch_id;
        $warehouseId = $sale->warehouse_id;

        foreach ($sale->items as $item) {
            StockMovement::create([
                'branch_id' => $branchId,
                'warehouse_id' => $warehouseId,
                'product_id' => $item->product_id,
                'ref_type' => 'sale',
                'ref_id' => $sale->getKey(),
                'qty' => -1 * abs((float) $item->qty),
                'direction' => 'out',
                'note' => 'Sale completed',
                'created_by' => $sale->created_by,
            ]);
        }
    }
}
