<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * AssignGuard
 *
 * - Dynamically selects the auth guard (default: api).
 * - Works with Sanctum (driver: sanctum) or JWT if you toggle later.
 *
 * Usage alias in routes: 'assign.guard:api'
 */
class AssignGuard
{
    public function handle(Request $request, Closure $next, string $guard = 'api'): Response
    {
        Auth::shouldUse($guard);

        return $next($request);
    }
}
