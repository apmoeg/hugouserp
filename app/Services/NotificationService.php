<?php

declare(strict_types=1);

namespace App\Services;

use App\Events\NotificationRead;
use App\Events\NotificationsMarkedAsRead;
use App\Events\UpdateNotificationCounters;
use App\Models\User;
use App\Notifications\InAppMessage;
use App\Services\Contracts\NotificationServiceInterface;
use App\Traits\HandlesServiceErrors;
use Illuminate\Support\Facades\DB;

class NotificationService implements NotificationServiceInterface
{
    use HandlesServiceErrors;

    public function inApp(int $userId, string $title, string $message, array $data = []): void
    {
        $this->handleServiceOperation(
            callback: function () use ($userId, $title, $message, $data) {
                $user = User::find($userId);
                if ($user) {
                    $user->notify(new InAppMessage($title, $message, $data));
                }
                event(new UpdateNotificationCounters($userId));
            },
            operation: 'inApp',
            context: ['user_id' => $userId, 'title' => $title]
        );
    }

    public function email(string $to, string $subject, string $view, array $data = []): void
    {
        $this->handleServiceOperation(
            callback: fn () => dispatch(new \App\Jobs\SendEmailNotificationJob($to, $subject, $view, $data)),
            operation: 'email',
            context: ['to' => $to, 'subject' => $subject, 'view' => $view]
        );
    }

    public function sms(string $toPhone, string $message): void
    {
        $this->handleServiceOperation(
            callback: fn () => dispatch(new \App\Jobs\SendSmsNotificationJob($toPhone, $message)),
            operation: 'sms',
            context: ['to_phone' => $toPhone]
        );
    }

    public function markRead(int $userId, string $notificationId): void
    {
        $this->handleServiceOperation(
            callback: function () use ($userId, $notificationId) {
                DB::table('notifications')
                    ->where('id', $notificationId)
                    ->where('notifiable_id', $userId)
                    ->update(['read_at' => now()]);
                event(new NotificationRead($userId, $notificationId));
                event(new UpdateNotificationCounters($userId));
            },
            operation: 'markRead',
            context: ['user_id' => $userId, 'notification_id' => $notificationId]
        );
    }

    public function markManyRead(int $userId, array $ids): int
    {
        return $this->handleServiceOperation(
            callback: function () use ($userId, $ids) {
                $count = DB::table('notifications')
                    ->whereIn('id', $ids)
                    ->where('notifiable_id', $userId)
                    ->update(['read_at' => now()]);
                event(new NotificationsMarkedAsRead($userId, $ids));
                event(new UpdateNotificationCounters($userId));

                return (int) $count;
            },
            operation: 'markManyRead',
            context: ['user_id' => $userId, 'ids_count' => count($ids)],
            defaultValue: 0
        );
    }
}
