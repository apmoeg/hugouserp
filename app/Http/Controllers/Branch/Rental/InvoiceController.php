<?php

declare(strict_types=1);

namespace App\Http\Controllers\Branch\Rental;

use App\Http\Controllers\Controller;
use App\Http\Requests\InvoiceCollectRequest;
use App\Http\Requests\InvoicePenaltyRequest;
use App\Models\RentalInvoice;
use App\Services\Contracts\RentalServiceInterface as Rental;
use Illuminate\Http\Request;

class InvoiceController extends Controller
{
    public function __construct(protected Rental $rental) {}

    public function index(Request $request)
    {
        $per = min(max($request->integer('per_page', 20), 1), 100);

        return $this->ok(RentalInvoice::query()->orderByDesc('id')->paginate($per));
    }

    public function show(RentalInvoice $invoice)
    {
        return $this->ok($invoice);
    }

    public function runRecurring()
    {
        return $this->ok(['queued' => $this->rental->runRecurring()], __('Run recurring'));
    }

    public function collectPayment(InvoiceCollectRequest $request, RentalInvoice $invoice)
    {
        $data = $request->validated();

        return $this->ok($this->rental->collectPayment($invoice->id, (float) $data['amount']), __('Collected'));
    }

    public function applyPenalty(InvoicePenaltyRequest $request, RentalInvoice $invoice)
    {
        $data = $request->validated();

        return $this->ok($this->rental->applyPenalty($invoice->id, (float) $data['penalty']), __('Penalty applied'));
    }
}
