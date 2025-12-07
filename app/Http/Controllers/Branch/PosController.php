<?php

declare(strict_types=1);

namespace App\Http\Controllers\Branch;

use App\Http\Controllers\Controller;
use App\Http\Requests\PosCheckoutRequest;
use App\Services\Contracts\POSServiceInterface as POS;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class PosController extends Controller
{
    public function __construct(protected POS $pos) {}

    public function checkout(PosCheckoutRequest $request)
    {
        $sale = $this->pos->checkout($request->validated());

        return $this->ok($sale->load('items'), __('Checkout completed'));
    }

    public function hold(Request $request)
    {
        $data = $this->validate($request, ['items' => ['required', 'array', 'min:1'], 'note' => ['nullable', 'string', 'max:255']]);
        $branch = (int) $request->attributes->get('branch_id');
        $id = Str::ulid()->toBase32();
        Cache::put("pos:hold:{$branch}:{$id}", ['items' => $data['items'], 'note' => $data['note'] ?? null, 'user_id' => $request->user()->getKey()], now()->addHours(12));

        return $this->ok(['hold_id' => $id], __('Held'));
    }

    public function resume(Request $request)
    {
        $this->validate($request, ['hold_id' => ['required', 'string']]);
        $branch = (int) $request->attributes->get('branch_id');
        $data = Cache::pull("pos:hold:{$branch}:".$request->input('hold_id'));
        if (! $data) {
            return $this->fail(__('Hold not found'), 404);
        }

        return $this->ok($data, __('Resumed'));
    }

    public function closeDay()
    {
        // Could dispatch a job in your app: dispatch(new ClosePosDayJob(...))
        return $this->ok(['status' => 'closed', 'at' => now()->toDateTimeString()], __('Closed'));
    }

    public function reprint(int $sale)
    {
        return $this->ok(app(\App\Services\Contracts\SaleServiceInterface::class)->printInvoice($sale));
    }

    public function xReport()
    {
        return $this->ok(['report' => 'X']);
    }

    public function zReport()
    {
        return $this->ok(['report' => 'Z']);
    }
}
