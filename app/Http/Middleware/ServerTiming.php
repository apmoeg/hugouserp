<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ServerTiming
{
    public function handle(Request $request, Closure $next): Response
    {
        $start = microtime(true);
        $response = $next($request);
        $ms = (int) ((microtime(true) - $start) * 1000);

        $response->headers->set('Server-Timing', "app;dur={$ms}");
        $response->headers->set('X-Response-Time', "{$ms}ms");
        app()->instance('req.time_ms', $ms);

        return $response;
    }
}
