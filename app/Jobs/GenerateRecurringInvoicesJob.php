<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\RentalContract;
use App\Models\RentalInvoice;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class GenerateRecurringInvoicesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public $timeout = 300;

    public function __construct(public ?string $forDate = null) {}

    public function handle(): void
    {
        $target = $this->forDate ? \Carbon\Carbon::parse($this->forDate) : now();
        $period = $target->format('Y-m');

        $contracts = RentalContract::query()
            ->where('status', 'active')
            ->whereDate('start_date', '<=', $target->endOfDay())
            ->get();

        foreach ($contracts as $contract) {
            $exists = RentalInvoice::query()
                ->where('contract_id', $contract->getKey())
                ->where('period', $period)
                ->exists();
            if ($exists) {
                continue;
            }

            RentalInvoice::query()->create([
                'contract_id' => $contract->getKey(),
                'code' => 'INV-'.strtoupper(uniqid()),
                'period' => $period,
                'due_date' => $target->copy()->endOfMonth()->toDateString(),
                'amount' => $contract->rent,
                'status' => 'unpaid',
            ]);
        }
    }

    public function tags(): array
    {
        return ['rental', 'recurring', 'period:'.($this->forDate ?? now()->format('Y-m'))];
    }
}
