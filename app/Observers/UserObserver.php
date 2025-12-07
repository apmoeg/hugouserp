<?php

declare(strict_types=1);

namespace App\Observers;

use App\Events\UserDisabled;
use App\Models\AuditLog;
use App\Models\User;

class UserObserver
{
    public function updated(User $user): void
    {
        $changes = $user->getChanges();

        // If user got disabled, dispatch domain event to revoke tokens etc.
        if (array_key_exists('is_active', $changes) && (int) $user->is_active === 0) {
            event(new UserDisabled($user));
        }

        $this->audit('updated', $user, $changes);
    }

    public function created(User $user): void
    {
        $this->audit('created', $user);
    }

    public function deleted(User $user): void
    {
        $this->audit('deleted', $user);
    }

    protected function audit(string $action, User $user, array $changes = []): void
    {
        try {
            $req = request();
            AuditLog::create([
                'user_id' => optional(auth()->user())->getKey(),
                'action' => "User:{$action}",
                'subject_type' => User::class,
                'subject_id' => $user->getKey(),
                'ip' => $req?->ip(),
                'user_agent' => (string) $req?->userAgent(),
                'old_values' => [],
                'new_values' => $changes ?: $user->attributesToArray(),
            ]);
        } catch (\Throwable $e) {
            // ignore failures
        }
    }
}
