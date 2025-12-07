<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\ContractOverdue;
use App\Models\RentalInvoice;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class ApplyLateFee implements ShouldQueue
{
    use InteractsWithQueue;

    public function __construct(
        protected float $penaltyPercent = 2.0, // default 2%
        protected float $minPenalty = 10.0
    ) {}

    public function handle(ContractOverdue $event): void
    {
        /** @var \App\Models\RentalContract $contract */
        $contract = $event->contract;
        $invoice = RentalInvoice::query()
            ->where('contract_id', $contract->getKey())
            ->where('status', 'unpaid')
            ->orderBy('due_date')
            ->first();

        if (! $invoice) {
            return;
        }

        $base = (float) $invoice->amount;
        $penalty = max($base * ($this->penaltyPercent / 100), $this->minPenalty);
        $invoice->amount = round($base + $penalty, 2);
        $invoice->save();
    }
}
