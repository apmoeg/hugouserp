<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SentryContext
{
    public function handle(Request $request, Closure $next): Response
    {
        if (class_exists(\Sentry\SentrySdk::class)) {
            $hub = \Sentry\SentrySdk::getCurrentHub();
            $scope = $hub->getScope();

            if ($scope) {
                $user = $request->user();
                if ($user) {
                    $scope->setUser([
                        'id' => (string) $user->getKey(),
                        'email' => $user->email ?? null,
                    ]);
                }
                $scope->setTag('branch_id', (string) (app()->has('req.branch_id') ? app('req.branch_id') : ''));
                $scope->setTag('request_id', (string) (app()->has('req.id') ? app('req.id') : ''));
                $scope->setTag('correlation_id', (string) (app()->has('req.correlation_id') ? app('req.correlation_id') : ''));
                $scope->setTag('locale', (string) (app()->has('req.locale') ? app('req.locale') : ''));
            }
        }

        return $next($request);
    }
}
