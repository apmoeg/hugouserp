<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * EnsurePermission
 *
 * - Enforces permissions using spatie/laravel-permission (or Laravel policies).
 * - Signature supports:
 *     perm:action                  -> user must have 'action'
 *     perm:action1|action2         -> ANY of them (default 'any')
 *     perm:action1&action2,all     -> ALL of them
 *     perm:action1|action2,any     -> explicit ANY
 *     perm:!delete                 -> NEGATION, user must NOT have 'delete'
 *     perm:view,edit,all           -> supports commas as separators
 */
class EnsurePermission
{
    public function handle(Request $request, Closure $next, string $abilities, string $mode = 'any'): Response
    {
        $user = $request->user();
        if (! $user) {
            return $this->error('Unauthenticated.', 401);
        }

        // allow commas as separators
        $abilities = str_replace(',', '|', $abilities);

        // negation support
        $negated = false;
        if (str_starts_with($abilities, '!')) {
            $negated = true;
            $abilities = ltrim($abilities, '!');
        }

        // Parse abilities list: support 'a|b|c' or 'a&b&c'
        if (str_contains($abilities, '&')) {
            $ops = array_filter(array_map('trim', explode('&', $abilities)));
            $mode = 'all';
        } else {
            $ops = array_filter(array_map('trim', explode('|', $abilities)));
        }

        if (empty($ops)) {
            return $this->error('Permission(s) not specified.', 500);
        }

        $checker = static function ($ability) use ($user): bool {
            if (method_exists($user, 'hasPermissionTo') && $user->hasPermissionTo($ability)) {
                return true;
            }
            if (method_exists($user, 'can') && $user->can($ability)) {
                return true;
            }

            return false;
        };

        $result = $mode === 'all'
            ? collect($ops)->every($checker)
            : collect($ops)->contains($checker);

        // apply negation if requested
        $result = $negated ? ! $result : $result;

        if (! $result) {
            return $this->error('You do not have the required permission(s).', 403, [
                'required' => $ops,
                'mode' => $mode,
                'negated' => $negated,
            ]);
        }

        return $next($request);
    }

    protected function error(string $message, int $status, array $meta = []): Response
    {
        // Get the current request - using the Request facade to get the actual request
        // being processed through the middleware
        $request = request();

        // Check if the request expects JSON (API request)
        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json(['success' => false, 'message' => $message, 'meta' => $meta], $status);
        }

        // For web requests, return a proper HTML response
        abort($status, $message);
    }
}
