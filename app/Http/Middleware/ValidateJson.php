<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ValidateJson
{
    public function handle(Request $request, Closure $next): Response
    {
        if (in_array($request->method(), ['POST', 'PUT', 'PATCH'], true)) {
            $type = $request->headers->get('Content-Type', '');
            if (! str_starts_with(strtolower($type), 'application/json')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid Content-Type. Expect application/json.',
                ], 415);
            }
        }

        return $next($request);
    }
}
