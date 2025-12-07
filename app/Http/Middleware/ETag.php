<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ETag
{
    public function handle(Request $request, Closure $next): Response
    {
        /** @var Response $response */
        $response = $next($request);

        // Skip for non-200 or streamed/binary responses
        if ($response->getStatusCode() !== 200 || $response->headers->has('Content-Range')) {
            return $response;
        }

        $etag = '"'.hash('xxh128', (string) $response->getContent()).'"';
        $response->headers->set('ETag', $etag);

        $ifNoneMatch = $request->headers->get('If-None-Match');
        if ($ifNoneMatch && trim($ifNoneMatch) === $etag) {
            $response->setStatusCode(304);
            $response->setContent(null);
        }

        return $response;
    }
}
