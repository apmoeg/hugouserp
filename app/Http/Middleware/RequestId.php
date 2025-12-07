<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Ramsey\Uuid\Uuid;
use Symfony\Component\HttpFoundation\Response;

class RequestId
{
    public function handle(Request $request, Closure $next): Response
    {
        $rid = $request->headers->get('X-Request-Id') ?: Uuid::uuid4()->toString();
        $request->headers->set('X-Request-Id', $rid);
        app()->instance('req.id', $rid);

        /** @var Response $response */
        $response = $next($request);
        $response->headers->set('X-Request-Id', $rid);

        return $response;
    }
}
