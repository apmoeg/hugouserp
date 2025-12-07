<?php

declare(strict_types=1);

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ContractDueSoon
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public \App\Models\RentalContract $contract,
        public ?string $reason = null
    ) {}
}
