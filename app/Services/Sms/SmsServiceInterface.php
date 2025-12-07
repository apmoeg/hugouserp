<?php

declare(strict_types=1);

namespace App\Services\Sms;

interface SmsServiceInterface
{
    public function send(string $to, string $message, ?string $filePath = null): array;

    public function isConfigured(): bool;

    public function getProviderName(): string;
}
