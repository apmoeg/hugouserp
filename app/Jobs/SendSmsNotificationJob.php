<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Services\SettingsService;
use App\Services\Sms\SmsManager;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendSmsNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 60;

    public int $backoff = 30;

    public function __construct(
        public string $toPhone,
        public string $message,
        public ?string $filePath = null
    ) {}

    public function handle(SettingsService $settingsService): void
    {
        $smsManager = new SmsManager($settingsService);

        if (! $smsManager->isConfigured()) {
            Log::warning('SMS not configured, skipping notification', [
                'to' => $this->toPhone,
            ]);

            return;
        }

        $result = $smsManager->send($this->toPhone, $this->message, $this->filePath);

        if ($result['success']) {
            Log::info('SMS sent successfully', [
                'to' => $this->toPhone,
                'provider' => $result['provider'] ?? 'unknown',
            ]);
        } else {
            Log::error('SMS send failed', [
                'to' => $this->toPhone,
                'error' => $result['error'] ?? 'Unknown error',
            ]);

            if ($this->attempts() < $this->tries) {
                $this->release($this->backoff);
            }
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('SMS job failed permanently', [
            'to' => $this->toPhone,
            'error' => $exception->getMessage(),
        ]);
    }

    public function tags(): array
    {
        return ['notify', 'sms', 'to:'.$this->toPhone];
    }
}
