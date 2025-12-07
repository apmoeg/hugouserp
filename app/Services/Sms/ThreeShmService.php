<?php

declare(strict_types=1);

namespace App\Services\Sms;

use App\Services\SettingsService;
use App\Traits\HandlesServiceErrors;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ThreeShmService implements SmsServiceInterface
{
    use HandlesServiceErrors;

    protected array $config;

    public function __construct(protected SettingsService $settings)
    {
        $this->config = $settings->getSmsConfig('3shm');
    }

    public function send(string $to, string $message, ?string $filePath = null): array
    {
        return $this->handleServiceOperation(
            callback: function () use ($to, $message, $filePath) {
                if (! $this->isConfigured()) {
                    return [
                        'success' => false,
                        'error' => 'SMS provider not configured',
                    ];
                }

                $payload = [
                    'appkey' => $this->config['appkey'],
                    'authkey' => $this->config['authkey'],
                    'to' => $this->formatPhone($to),
                    'message' => $message,
                    'sandbox' => $this->config['sandbox'] ? 'true' : 'false',
                ];

                if ($filePath) {
                    $payload['file'] = $filePath;
                }

                $response = Http::timeout(30)
                    ->asMultipart()
                    ->post('https://app.3shm.com/api/create-message', $payload);

                $result = $response->json();

                Log::info('3shm SMS sent', [
                    'to' => $to,
                    'has_file' => ! empty($filePath),
                    'response' => $result,
                ]);

                return [
                    'success' => $response->successful(),
                    'response' => $result,
                    'provider' => '3shm',
                ];
            },
            operation: 'send',
            context: ['to' => $to, 'has_file' => ! empty($filePath)],
            defaultValue: ['success' => false, 'error' => 'SMS sending failed', 'provider' => '3shm']
        );
    }

    public function isConfigured(): bool
    {
        return $this->config['enabled']
            && ! empty($this->config['appkey'])
            && ! empty($this->config['authkey']);
    }

    public function getProviderName(): string
    {
        return '3shm';
    }

    protected function formatPhone(string $phone): string
    {
        $phone = preg_replace('/[^0-9]/', '', $phone);

        if (str_starts_with($phone, '0')) {
            $phone = '2'.$phone;
        }

        if (! str_starts_with($phone, '20')) {
            $phone = '20'.$phone;
        }

        return $phone;
    }
}
