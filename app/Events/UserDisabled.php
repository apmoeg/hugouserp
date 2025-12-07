<?php

declare(strict_types=1);

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class UserDisabled
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public \App\Models\User $user
    ) {}
}
