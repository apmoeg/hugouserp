<?php

namespace App\Notifications\Rental;

use App\Models\RentalContract;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;

class ContractExpiringNotification extends Notification
{
    use Queueable;

    public RentalContract $contract;

    public function __construct(RentalContract $contract)
    {
        $this->contract = $contract;
    }

    public function via($notifiable): array
    {
        return ['database', 'broadcast'];
    }

    public function toArray($notifiable): array
    {
        $contract = $this->contract;

        $unit = $contract->unit;
        $tenant = $contract->tenant;

        $daysLeft = now()->diffInDays($contract->end_date, false);

        return [
            'type' => 'rental.contract.expiring',
            'contract_id' => $contract->id,
            'unit_code' => optional($unit)->code,
            'tenant_name' => optional($tenant)->name,
            'end_date' => $contract->end_date,
            'days_left' => $daysLeft,
            'message' => sprintf(
                'Contract for unit %s with %s ends on %s (%d days left)',
                optional($unit)->code ?? '#'.$contract->id,
                optional($tenant)->name ?? 'unknown tenant',
                $contract->end_date,
                $daysLeft
            ),
        ];
    }

    public function toBroadcast($notifiable): BroadcastMessage
    {
        return new BroadcastMessage($this->toArray($notifiable));
    }
}
