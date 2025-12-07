<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Models\LoginActivity;
use Illuminate\Auth\Events\Failed;

class LogFailedLogin
{
    public function handle(Failed $event): void
    {
        $request = request();
        $email = $event->credentials['email'] ?? $event->credentials['username'] ?? 'unknown';

        LoginActivity::logFailedAttempt(
            $email,
            $request->ip() ?? 'unknown',
            $request->userAgent() ?? 'unknown',
            __('Invalid credentials')
        );
    }
}
