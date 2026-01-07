<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\Branch;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->configureRateLimiting();

        // Parameter patterns
        Route::pattern('id', '[0-9]+');

        // Simple binding for {branch}
        Route::bind('branch', function ($value) {
            return Branch::query()->findOrFail($value);
        });
    }

    /**
     * Configure rate limiting for different contexts
     */
    protected function configureRateLimiting(): void
    {
        // General API rate limiting - 120 requests per minute
        RateLimiter::for('api', function (Request $request) {
            $key = optional($request->user())->getKey() ?: $request->ip();

            return Limit::perMinute(120)->by($key);
        });

        // Authentication endpoints - stricter limits to prevent brute force
        RateLimiter::for('auth', function (Request $request) {
            return Limit::perMinute(10)->by($request->ip());
        });

        // Export/Report endpoints - limited to prevent abuse
        RateLimiter::for('exports', function (Request $request) {
            $key = optional($request->user())->getKey() ?: $request->ip();

            return Limit::perMinute(10)->by($key);
        });

        // Bulk operations - very limited
        RateLimiter::for('bulk', function (Request $request) {
            $key = optional($request->user())->getKey() ?: $request->ip();

            return Limit::perMinute(5)->by($key);
        });

        // Uploads - moderate limits
        RateLimiter::for('uploads', function (Request $request) {
            $key = optional($request->user())->getKey() ?: $request->ip();

            return Limit::perMinute(30)->by($key);
        });
    }
}
