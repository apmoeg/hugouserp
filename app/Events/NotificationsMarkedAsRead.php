<?php

declare(strict_types=1);

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NotificationsMarkedAsRead
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * @param  string[]  $notificationIds
     */
    public function __construct(
        public int $userId,
        public array $notificationIds
    ) {}
}
