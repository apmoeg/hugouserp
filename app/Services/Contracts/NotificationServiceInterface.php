<?php

declare(strict_types=1);

namespace App\Services\Contracts;

interface NotificationServiceInterface
{
    public function inApp(int $userId, string $title, string $message, array $data = []): void;

    public function email(string $to, string $subject, string $view, array $data = []): void;

    public function sms(string $toPhone, string $message): void;

    public function markRead(int $userId, string $notificationId): void;

    /** @param string[] $ids */
    public function markManyRead(int $userId, array $ids): int;
}
