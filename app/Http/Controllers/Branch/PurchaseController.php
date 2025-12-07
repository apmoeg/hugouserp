<?php

declare(strict_types=1);

namespace App\Http\Controllers\Branch;

use App\Http\Controllers\Controller;
use App\Http\Requests\PurchaseApproveRequest;
use App\Http\Requests\PurchaseCancelRequest;
use App\Http\Requests\PurchasePayRequest;
use App\Http\Requests\PurchaseReceiveRequest;
use App\Http\Requests\PurchaseReturnRequest;
use App\Http\Requests\PurchaseStoreRequest;
use App\Http\Requests\PurchaseUpdateRequest;
use App\Models\Purchase;
use App\Services\Contracts\PurchaseServiceInterface as Purchases;
use Illuminate\Http\Request;

class PurchaseController extends Controller
{
    public function __construct(protected Purchases $purchases) {}

    public function index(Request $request)
    {
        $per = min(max($request->integer('per_page', 20), 1), 100);
        $rows = Purchase::query()->orderByDesc('id')->paginate($per);

        return $this->ok($rows);
    }

    public function store(PurchaseStoreRequest $request)
    {
        $p = $this->purchases->create($request->validated());

        return $this->ok($p, __('Created'), 201);
    }

    public function show(Purchase $purchase)
    {
        return $this->ok($purchase->load('items'));
    }

    public function update(PurchaseUpdateRequest $request, Purchase $purchase)
    {
        $purchase->fill($request->validated())->save();

        return $this->ok($purchase);
    }

    public function approve(PurchaseApproveRequest $request, int $purchase)
    {
        return $this->ok($this->purchases->approve($purchase), __('Approved'));
    }

    public function receive(PurchaseReceiveRequest $request, int $purchase)
    {
        return $this->ok($this->purchases->receive($purchase), __('Received'));
    }

    public function pay(PurchasePayRequest $request, int $purchase)
    {
        $data = $request->validated();

        return $this->ok($this->purchases->pay($purchase, (float) $data['amount']), __('Paid'));
    }

    public function handleReturn(PurchaseReturnRequest $request, int $purchase)
    {
        // No payload yet, but request handles authorization
        return $this->ok(['purchase_id' => $purchase], __('Return handled'));
    }

    public function cancel(PurchaseCancelRequest $request, int $purchase)
    {
        return $this->ok($this->purchases->cancel($purchase), __('Cancelled'));
    }
}
