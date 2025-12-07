<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\User;
use App\Notifications\InAppMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendInAppNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public $timeout = 60;

    public function __construct(
        public int $userId,
        public string $title,
        public string $message,
        public array $data = []
    ) {}

    public function handle(): void
    {
        $user = User::find($this->userId);
        if ($user) {
            $user->notify(new InAppMessage($this->title, $this->message, $this->data));
        }
    }

    public function tags(): array
    {
        return ['notify', 'inapp', 'user:'.$this->userId];
    }
}
