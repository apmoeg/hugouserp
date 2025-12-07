<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Services\SettingsService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class Require2FA
{
    public function __construct(protected SettingsService $settingsService) {}

    public function handle(Request $request, Closure $next): Response
    {
        if (! Auth::check()) {
            return $next($request);
        }

        $user = Auth::user();

        $is2FAEnabled = $this->settingsService->get('security.2fa_enabled', false);
        $is2FARequired = $this->settingsService->get('security.2fa_required', false);

        if (! $is2FAEnabled) {
            return $next($request);
        }

        if ($is2FARequired && ! $user->hasTwoFactorEnabled()) {
            if (! $request->routeIs('2fa.setup', '2fa.setup.*', 'logout')) {
                return redirect()->route('2fa.setup')
                    ->with('warning', __('Two-factor authentication is required. Please set it up to continue.'));
            }
        }

        if ($user->hasTwoFactorEnabled() && ! session('2fa_verified')) {
            if (! $request->routeIs('2fa.challenge', '2fa.challenge.*', 'logout')) {
                return redirect()->route('2fa.challenge');
            }
        }

        return $next($request);
    }
}
