<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Notification;
use App\Models\User;
use App\Policies\Concerns\ChecksPermissions;

class NotificationPolicy
{
    use ChecksPermissions;

    public function viewAny(User $user): bool
    {
        return $this->has($user, 'notifications.view');
    }

    public function view(User $user, Notification $n): bool
    {
        return $this->has($user, 'notifications.view');
    }

    public function update(User $user, Notification $n): bool
    {
        return $this->has($user, 'notifications.update');
    }

    public function delete(User $user, Notification $n): bool
    {
        return $this->has($user, 'notifications.delete');
    }

    public function subscribe(User $user): bool
    {
        return $this->has($user, 'notifications.subscribe');
    }
}
