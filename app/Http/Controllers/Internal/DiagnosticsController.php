<?php

declare(strict_types=1);

namespace App\Http\Controllers\Internal;

use App\Http\Controllers\Controller;
use App\Services\DiagnosticsService;
use Illuminate\Http\JsonResponse;

class DiagnosticsController extends Controller
{
    public function __construct(
        private readonly DiagnosticsService $diagnosticsService
    ) {}

    /**
     * Get system diagnostics
     */
    public function index(): JsonResponse
    {
        $results = $this->diagnosticsService->runAll();

        $hasErrors = collect($results)->contains(fn ($result) => $result['status'] === 'error');
        $hasWarnings = collect($results)->contains(fn ($result) => $result['status'] === 'warning');

        $overallStatus = $hasErrors ? 'error' : ($hasWarnings ? 'warning' : 'ok');

        return response()->json([
            'success' => ! $hasErrors,
            'status' => $overallStatus,
            'timestamp' => now()->toIso8601String(),
            'checks' => $results,
        ], $hasErrors ? 503 : 200);
    }
}
