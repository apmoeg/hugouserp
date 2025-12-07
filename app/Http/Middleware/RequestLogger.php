<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequestLogger
{
    public function handle(Request $request, Closure $next): Response
    {
        $start = microtime(true);

        /** @var Response $response */
        $response = $next($request);

        $ms = (int) ((microtime(true) - $start) * 1000);
        $user = $request->user();
        $branchId = app()->has('req.branch_id') ? app('req.branch_id') : null;

        $payload = [
            'rid' => app()->has('req.id') ? app('req.id') : null,
            'cid' => app()->has('req.correlation_id') ? app('req.correlation_id') : null,
            'ip' => $request->ip(),
            'method' => $request->method(),
            'path' => '/'.ltrim($request->path(), '/'),
            'status' => $response->getStatusCode(),
            'ms' => $ms,
            'user' => $user ? ['id' => $user->getKey(), 'email' => $user->email ?? null] : null,
            'branch' => $branchId,
        ];

        $debug = app()->environment('local') || $request->route()?->getName() === 'debug_log';
        if ($debug) {
            $payload['input'] = $request->except(['password', 'password_confirmation', 'token']);
        }

        logger()->info('api.request', $payload);

        return $response;
    }
}
