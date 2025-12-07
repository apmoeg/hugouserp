<?php

declare(strict_types=1);

namespace App\Http\Controllers\Branch;

use App\Http\Controllers\Controller;
use App\Http\Requests\StockAdjustRequest;
use App\Http\Requests\StockTransferRequest;
use App\Services\Contracts\InventoryServiceInterface as Inventory;
use Illuminate\Http\Request;

class StockController extends Controller
{
    public function __construct(protected Inventory $inv) {}

    public function current(Request $request)
    {
        $pid = (int) $request->integer('product_id');
        $wid = $request->integer('warehouse_id') ?: null;
        $qty = $this->inv->currentQty($pid, $wid);

        return $this->ok(['product_id' => $pid, 'warehouse_id' => $wid, 'qty' => $qty]);
    }

    public function adjust(StockAdjustRequest $request)
    {
        $data = $request->validated();
        $m = $this->inv->adjust($data['product_id'], (float) $data['qty'], $data['warehouse_id'] ?? null, $data['note'] ?? null);

        return $this->ok($m, __('Adjusted'));
    }

    public function transfer(StockTransferRequest $request)
    {
        $data = $request->validated();
        $res = $this->inv->transfer($data['product_id'], (float) $data['qty'], $data['from_warehouse'], $data['to_warehouse']);

        return $this->ok(['out' => $res[0], 'in' => $res[1]], __('Transferred'));
    }
}
