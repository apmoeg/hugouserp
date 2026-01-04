<?php

declare(strict_types=1);

namespace App\Services;

use App\Events\NotificationRead;
use App\Events\NotificationsMarkedAsRead;
use App\Events\RealTimeNotification;
use App\Events\UpdateNotificationCounters;
use App\Models\User;
use App\Notifications\InAppMessage;
use App\Services\Contracts\NotificationServiceInterface;
use App\Traits\HandlesServiceErrors;
use Illuminate\Support\Facades\DB;

class NotificationService implements NotificationServiceInterface
{
    use HandlesServiceErrors;

    /**
     * Send an in-app notification to a user
     * Also broadcasts a real-time notification if broadcasting is enabled
     */
    public function inApp(int $userId, string $title, string $message, array $data = []): void
    {
        $this->handleServiceOperation(
            callback: function () use ($userId, $title, $message, $data) {
                $user = User::find($userId);
                if ($user) {
                    $user->notify(new InAppMessage($title, $message, $data));
                    
                    // Broadcast real-time notification
                    event(new RealTimeNotification(
                        userId: $userId,
                        title: $title,
                        message: $message,
                        type: $data['type'] ?? 'info',
                        link: $data['link'] ?? null,
                        data: $data
                    ));
                }
                event(new UpdateNotificationCounters($userId));
            },
            operation: 'inApp',
            context: ['user_id' => $userId, 'title' => $title]
        );
    }

    /**
     * Send an in-app notification to multiple users
     */
    public function inAppToMany(array $userIds, string $title, string $message, array $data = []): void
    {
        $this->handleServiceOperation(
            callback: function () use ($userIds, $title, $message, $data) {
                foreach ($userIds as $userId) {
                    $this->inApp($userId, $title, $message, $data);
                }
            },
            operation: 'inAppToMany',
            context: ['user_ids' => $userIds, 'title' => $title]
        );
    }

    /**
     * Broadcast a real-time notification without storing it
     * Useful for transient notifications like typing indicators
     */
    public function broadcast(int $userId, string $title, string $message, string $type = 'info', array $data = []): void
    {
        $this->handleServiceOperation(
            callback: function () use ($userId, $title, $message, $type, $data) {
                event(new RealTimeNotification(
                    userId: $userId,
                    title: $title,
                    message: $message,
                    type: $type,
                    link: $data['link'] ?? null,
                    data: $data
                ));
            },
            operation: 'broadcast',
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

    /**
     * Get unread notification count for a user
     */
    public function getUnreadCount(int $userId): int
    {
        return $this->handleServiceOperation(
            callback: function () use ($userId) {
                return DB::table('notifications')
                    ->where('notifiable_id', $userId)
                    ->whereNull('read_at')
                    ->count();
            },
            operation: 'getUnreadCount',
            context: ['user_id' => $userId],
            defaultValue: 0
        );
    }

    /**
     * Get recent notifications for a user
     */
    public function getRecent(int $userId, int $limit = 10): array
    {
        return $this->handleServiceOperation(
            callback: function () use ($userId, $limit) {
                return DB::table('notifications')
                    ->where('notifiable_id', $userId)
                    ->orderByDesc('created_at')
                    ->limit($limit)
                    ->get()
                    ->map(function ($notification) {
                        $data = json_decode($notification->data, true) ?? [];
                        return [
                            'id' => $notification->id,
                            'type' => $data['type'] ?? 'info',
                            'title' => $data['title'] ?? '',
                            'message' => $data['message'] ?? '',
                            'link' => $data['link'] ?? null,
                            'read_at' => $notification->read_at,
                            'created_at' => $notification->created_at,
                        ];
                    })
                    ->toArray();
            },
            operation: 'getRecent',
            context: ['user_id' => $userId, 'limit' => $limit],
            defaultValue: []
        );
    }
}
