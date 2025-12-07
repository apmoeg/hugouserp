<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Models\LoginActivity;
use Illuminate\Auth\Events\Login;

class LogSuccessfulLogin
{
    public function handle(Login $event): void
    {
        $user = $event->user;
        $request = request();

        LoginActivity::logLogin(
            $user,
            $request->ip() ?? 'unknown',
            $request->userAgent() ?? 'unknown'
        );
    }
}
