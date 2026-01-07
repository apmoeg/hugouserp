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

    protected function requireBranchId(Request $request): int
    {
        $branchId = $request->attributes->get('branch_id');

        abort_if($branchId === null, 400, __('Branch context is required.'));

        return (int) $branchId;
    }

    public function checkout(PosCheckoutRequest $request)
    {
        $payload = $request->validated();
        $payload['branch_id'] = $this->requireBranchId($request);

        $sale = $this->pos->checkout($payload);

        return $this->ok($sale->load('items'), __('Checkout completed'));
    }

    public function hold(Request $request)
    {
        $data = $this->validate($request, ['items' => ['required', 'array', 'min:1'], 'note' => ['nullable', 'string', 'max:255']]);
        $branch = $this->requireBranchId($request);
        $id = Str::ulid()->toBase32();
        Cache::put("pos:hold:{$branch}:{$id}", ['items' => $data['items'], 'note' => $data['note'] ?? null, 'user_id' => $request->user()->getKey()], now()->addHours(12));

        return $this->ok(['hold_id' => $id], __('Held'));
    }

    public function resume(Request $request)
    {
        $this->validate($request, ['hold_id' => ['required', 'string']]);
        $branch = $this->requireBranchId($request);
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

    public function reprint(Request $request, int $sale)
    {
        $this->requireBranchId($request);

        return $this->ok(app(\App\Services\Contracts\SaleServiceInterface::class)->printInvoice($sale));
    }

    public function xReport(Request $request)
    {
        $this->requireBranchId($request);

        return $this->ok(['report' => 'X']);
    }

    public function zReport(Request $request)
    {
        $this->requireBranchId($request);

        return $this->ok(['report' => 'Z']);
    }
}
