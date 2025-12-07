<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PaginationSanitizer
{
    public function handle(Request $request, Closure $next, int $max = 100): Response
    {
        $page = max(1, (int) $request->query('page', 1));
        $per = (int) $request->query('per_page', 20);
        $per = $per > 0 ? min($per, $max) : 20;

        $request->merge(['page' => $page, 'per_page' => $per]);

        return $next($request);
    }
}
