<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CorrelationId
{
    public function handle(Request $request, Closure $next): Response
    {
        $cid = (string) ($request->headers->get('X-Correlation-Id')
            ?: $request->headers->get('X-Request-Id', ''));

        if ($cid === '') {
            $cid = app()->bound('req.id') ? (string) app('req.id') : substr(hash('xxh128', microtime(true).random_int(1, PHP_INT_MAX)), 0, 32);
        }

        $request->headers->set('X-Correlation-Id', $cid);
        app()->instance('req.correlation_id', $cid);

        $response = $next($request);
        $response->headers->set('X-Correlation-Id', $cid);

        return $response;
    }
}
