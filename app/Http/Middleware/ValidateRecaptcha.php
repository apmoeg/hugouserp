<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Services\SettingsService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class ValidateRecaptcha
{
    public function __construct(protected SettingsService $settingsService) {}

    public function handle(Request $request, Closure $next): Response
    {
        $isEnabled = $this->settingsService->get('security.recaptcha_enabled', false);

        if (! $isEnabled) {
            return $next($request);
        }

        $token = $request->input('g-recaptcha-response') ?? $request->input('recaptcha_token');

        if (! $token) {
            return $this->failResponse($request, __('reCAPTCHA verification required'));
        }

        $secretKey = $this->settingsService->getDecrypted('security.recaptcha_secret_key');

        if (! $secretKey) {
            Log::error('reCAPTCHA enabled but secret key not configured - blocking request');

            return $this->failResponse($request, __('Security verification temporarily unavailable. Please try again later.'));
        }

        try {
            $response = Http::asForm()->post('https://www.google.com/recaptcha/api/siteverify', [
                'secret' => $secretKey,
                'response' => $token,
                'remoteip' => $request->ip(),
            ]);

            $result = $response->json();

            if (! ($result['success'] ?? false)) {
                Log::warning('reCAPTCHA verification failed', [
                    'errors' => $result['error-codes'] ?? [],
                    'ip' => $request->ip(),
                ]);

                return $this->failResponse($request, __('reCAPTCHA verification failed'));
            }

            if (isset($result['score']) && $result['score'] < 0.5) {
                Log::warning('reCAPTCHA score too low', [
                    'score' => $result['score'],
                    'ip' => $request->ip(),
                ]);

                return $this->failResponse($request, __('Security verification failed'));
            }

        } catch (\Exception $e) {
            Log::error('reCAPTCHA verification error', [
                'error' => $e->getMessage(),
                'ip' => $request->ip(),
            ]);

            return $this->failResponse($request, __('Security verification temporarily unavailable. Please try again later.'));
        }

        return $next($request);
    }

    protected function failResponse(Request $request, string $message): Response
    {
        if ($request->expectsJson()) {
            return response()->json(['error' => $message], 422);
        }

        return back()->withErrors(['recaptcha' => $message]);
    }
}
