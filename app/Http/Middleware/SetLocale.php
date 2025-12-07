<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    public function handle(Request $request, Closure $next, ?string $fallback = null): Response
    {
        $locale = $this->resolveLocale($request, $fallback);
        App::setLocale($locale);

        app()->instance('req.locale', $locale);

        return $next($request);
    }

    protected function resolveLocale(Request $request, ?string $fallback): string
    {
        if ($lang = $request->query('lang')) {
            if ($this->isValidLocale($lang)) {
                Session::put('locale', $lang);

                return $lang;
            }
        }

        if (Session::has('locale')) {
            $sessionLocale = Session::get('locale');
            if ($this->isValidLocale($sessionLocale)) {
                return $sessionLocale;
            }
        }

        $x = $request->headers->get('X-Locale');
        if ($x && $this->isValidLocale($x)) {
            return $x;
        }

        $accept = $request->headers->get('Accept-Language');
        if ($accept) {
            $lang = Str::of($accept)->before(',')->before(';')->lower()->value();
            if ($this->isValidLocale($lang)) {
                return $lang;
            }
        }

        if ($user = $request->user()) {
            $pref = $user->locale ?? null;
            if ($pref && $this->isValidLocale($pref)) {
                return $pref;
            }
        }

        return $fallback ?: (string) config('app.locale', 'ar');
    }

    protected function isValidLocale(string $locale): bool
    {
        return in_array($locale, ['ar', 'en'], true);
    }
}
