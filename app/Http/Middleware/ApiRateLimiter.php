<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Cache\RateLimiter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ApiRateLimiter
{
    public function __construct(
        protected RateLimiter $limiter
    ) {}

    public function handle(Request $request, Closure $next, string $key = 'api', int $maxAttempts = 60, int $decayMinutes = 1): Response
    {
        $identifier = $this->resolveRequestSignature($request, $key);

        if ($this->limiter->tooManyAttempts($identifier, $maxAttempts)) {
            return $this->buildResponse($identifier, $maxAttempts);
        }

        $this->limiter->hit($identifier, $decayMinutes * 60);

        $response = $next($request);

        return $this->addHeaders(
            $response,
            $maxAttempts,
            $this->calculateRemainingAttempts($identifier, $maxAttempts)
        );
    }

    protected function resolveRequestSignature(Request $request, string $key): string
    {
        $userId = $request->user()?->id ?? 'guest';
        $ip = $request->ip();

        return sha1("{$key}|{$userId}|{$ip}");
    }

    protected function buildResponse(string $key, int $maxAttempts): JsonResponse
    {
        $retryAfter = $this->limiter->availableIn($key);

        return response()->json([
            'success' => false,
            'message' => __('Too many requests. Please try again later.'),
            'retry_after' => $retryAfter,
        ], 429)->withHeaders([
            'Retry-After' => $retryAfter,
            'X-RateLimit-Limit' => $maxAttempts,
            'X-RateLimit-Remaining' => 0,
        ]);
    }

    protected function addHeaders(Response $response, int $maxAttempts, int $remainingAttempts): Response
    {
        $response->headers->add([
            'X-RateLimit-Limit' => $maxAttempts,
            'X-RateLimit-Remaining' => $remainingAttempts,
        ]);

        return $response;
    }

    protected function calculateRemainingAttempts(string $key, int $maxAttempts): int
    {
        return max(0, $maxAttempts - $this->limiter->attempts($key));
    }
}
