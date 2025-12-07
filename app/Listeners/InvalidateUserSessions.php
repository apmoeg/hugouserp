<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\UserDisabled;
use Illuminate\Contracts\Queue\ShouldQueue;
use Laravel\Sanctum\PersonalAccessToken;

class InvalidateUserSessions implements ShouldQueue
{
    public function handle(UserDisabled $event): void
    {
        $user = $event->user;

        // Revoke Sanctum tokens
        if (class_exists(PersonalAccessToken::class)) {
            $user->tokens()->delete();
        }

        // Optionally: invalidate sessions if using web guard
        if (config('session.driver') === 'database') {
            \DB::table('sessions')->where('user_id', $user->getKey())->delete();
        }
    }
}
