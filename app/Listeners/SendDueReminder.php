<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\ContractDueSoon;
use App\Notifications\GeneralNotification;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendDueReminder implements ShouldQueue
{
    public function handle(ContractDueSoon $event): void
    {
        $contract = $event->contract;
        $tenant = $contract->tenant;

        $title = __('Rent due soon');
        $body = __('Your rent is due on :date for unit :unit', [
            'date' => optional($contract->end_date)->toDateString(),
            'unit' => $contract->unit?->code,
        ]);

        if ($tenant && method_exists($tenant, 'notify')) {
            $tenant->notify(new GeneralNotification($title, $body, [
                'contract_id' => $contract->getKey(),
            ], sendMail: true));
        }
    }
}
