<?php

declare(strict_types=1);

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class SendEmailNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public $timeout = 120;

    public function __construct(
        public string $to,
        public string $subject,
        public string $view,
        public array $data = []
    ) {}

    public function handle(): void
    {
        Mail::send($this->view, $this->data, function ($m) {
            $m->to($this->to)->subject($this->subject);
        });
    }

    public function tags(): array
    {
        return ['notify', 'email', 'to:'.$this->to];
    }
}
