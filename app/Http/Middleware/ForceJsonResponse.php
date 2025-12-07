<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * ForceJsonResponse
 *
 * - Forces all API responses to be JSON.
 * - Normalises Accept / Content-Type headers.
 * - Safe for Laravel 12.x where API stack is configured in bootstrap/app.php.
 */
class ForceJsonResponse
{
    public function handle(Request $request, Closure $next): Response
    {
        // Ensure downstream expects JSON
        $request->headers->set('Accept', 'application/json');

        /** @var Response $response */
        $response = $next($request);

        // Force JSON content-type when possible
        if ($response instanceof JsonResponse) {
            $response->headers->set('Content-Type', 'application/json; charset=utf-8');

            return $response;
        }

        // If response isn't JsonResponse, don't mutate body; just set header if empty
        if (! $response->headers->has('Content-Type')) {
            $response->headers->set('Content-Type', 'application/json; charset=utf-8');
        }

        return $response;
    }
}
