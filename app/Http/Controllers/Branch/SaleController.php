<?php

declare(strict_types=1);

namespace App\Http\Controllers\Branch;

use App\Http\Controllers\Controller;
use App\Http\Requests\SaleReturnRequest;
use App\Http\Requests\SaleVoidRequest;
use App\Models\Sale;
use App\Services\Contracts\SaleServiceInterface as Sales;
use Illuminate\Http\Request;

class SaleController extends Controller
{
    public function __construct(protected Sales $sales) {}

    public function index(Request $request)
    {
        $per = min(max($request->integer('per_page', 20), 1), 100);
        $rows = Sale::query()->orderByDesc('id')->paginate($per);

        return $this->ok($rows);
    }

    public function store()
    {
        return $this->ok([], __('Use POS /checkout'));
    }

    public function show(Sale $sale)
    {
        return $this->ok($sale->load('items'));
    }

    public function update(Request $request, Sale $sale)
    {
        $sale->fill($request->only(['notes']))->save();

        return $this->ok($sale);
    }

    public function handleReturn(SaleReturnRequest $request, int $sale)
    {
        $data = $request->validated();

        return $this->ok($this->sales->handleReturn($sale, $data['items'], $request->input('reason')), __('Return processed'));
    }

    public function voidSale(SaleVoidRequest $request, int $sale)
    {
        return $this->ok($this->sales->voidSale($sale, $request->input('reason')), __('Voided'));
    }

    public function printInvoice(int $sale)
    {
        return $this->ok($this->sales->printInvoice($sale));
    }
}
