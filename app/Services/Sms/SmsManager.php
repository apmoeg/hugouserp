<?php

declare(strict_types=1);

namespace App\Services\Sms;

use App\Services\SettingsService;
use App\Traits\HandlesServiceErrors;
use Illuminate\Support\Facades\Log;

class SmsManager
{
    use HandlesServiceErrors;

    protected array $providers = [];

    public function __construct(protected SettingsService $settings)
    {
        $this->providers = [
            '3shm' => fn () => new ThreeShmService($settings),
            'smsmisr' => fn () => new SmsMisrService($settings),
        ];
    }

    public function send(string $to, string $message, ?string $filePath = null): array
    {
        return $this->handleServiceOperation(
            callback: function () use ($to, $message, $filePath) {
                $providerName = $this->settings->getSmsProvider();

                if ($providerName === 'none' || ! isset($this->providers[$providerName])) {
                    Log::warning('SMS provider not set or invalid', ['provider' => $providerName]);

                    return [
                        'success' => false,
                        'error' => 'SMS provider not configured',
                    ];
                }

                $provider = $this->getProvider($providerName);

                if ($providerName === 'smsmisr' && $filePath) {
                    Log::warning('SMSMISR does not support file attachments, sending text only');
                    $filePath = null;
                }

                return $provider->send($to, $message, $filePath);
            },
            operation: 'send',
            context: ['to' => $to],
            defaultValue: ['success' => false, 'error' => 'SMS sending failed']
        );
    }

    public function sendWithProvider(string $providerName, string $to, string $message, ?string $filePath = null): array
    {
        return $this->handleServiceOperation(
            callback: function () use ($providerName, $to, $message, $filePath) {
                if (! isset($this->providers[$providerName])) {
                    return [
                        'success' => false,
                        'error' => "Unknown provider: {$providerName}",
                    ];
                }

                $provider = $this->getProvider($providerName);

                if ($providerName === 'smsmisr' && $filePath) {
                    $filePath = null;
                }

                return $provider->send($to, $message, $filePath);
            },
            operation: 'sendWithProvider',
            context: ['provider' => $providerName, 'to' => $to],
            defaultValue: ['success' => false, 'error' => 'SMS sending failed']
        );
    }

    public function getProvider(string $name): SmsServiceInterface
    {
        return $this->handleServiceOperation(
            callback: function () use ($name) {
                if (! isset($this->providers[$name])) {
                    throw new \InvalidArgumentException("Unknown SMS provider: {$name}");
                }

                return ($this->providers[$name])();
            },
            operation: 'getProvider',
            context: ['provider' => $name]
        );
    }

    public function getAvailableProviders(): array
    {
        return [
            'none' => [
                'name' => 'None',
                'description' => 'SMS disabled',
                'supports_files' => false,
            ],
            '3shm' => [
                'name' => '3shm (WhatsApp)',
                'description' => 'WhatsApp Business API with file support',
                'supports_files' => true,
            ],
            'smsmisr' => [
                'name' => 'SMSMISR',
                'description' => 'SMS Egypt - Text messages only',
                'supports_files' => false,
            ],
        ];
    }

    public function isConfigured(?string $providerName = null): bool
    {
        return $this->handleServiceOperation(
            callback: function () use ($providerName) {
                $providerName = $providerName ?? $this->settings->getSmsProvider();

                if ($providerName === 'none' || ! isset($this->providers[$providerName])) {
                    return false;
                }

                return $this->getProvider($providerName)->isConfigured();
            },
            operation: 'isConfigured',
            context: ['provider' => $providerName],
            defaultValue: false
        );
    }

    public function testConnection(?string $providerName = null): array
    {
        return $this->handleServiceOperation(
            callback: function () use ($providerName) {
                $providerName = $providerName ?? $this->settings->getSmsProvider();

                if (! $this->isConfigured($providerName)) {
                    return [
                        'success' => false,
                        'error' => 'Provider not configured',
                    ];
                }

                return [
                    'success' => true,
                    'provider' => $providerName,
                    'message' => 'Configuration appears valid',
                ];
            },
            operation: 'testConnection',
            context: ['provider' => $providerName],
            defaultValue: ['success' => false, 'error' => 'Connection test failed']
        );
    }
}
