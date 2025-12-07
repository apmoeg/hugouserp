<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\Branch;
use App\Models\BranchModule;
use App\Models\Module;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * EnsureModuleEnabled
 *
 * - Checks that the module identified by key is enabled for the current branch.
 * - Accepts parameter form: module.enabled:{moduleKey}
 *   OR picks from request attribute 'module.key' set by SetModuleContext.
 *
 * Usage alias: 'module.enabled'
 */
class EnsureModuleEnabled
{
    public function handle(Request $request, Closure $next, ?string $moduleKey = null): Response
    {
        /** @var Branch|null $branch */
        $branch = $request->attributes->get('branch');
        $key = $moduleKey ?: (string) $request->attributes->get('module.key', '');

        if (! $branch) {
            return $this->error('Branch context missing.', 422);
        }
        if ($key === '') {
            return $this->error('Module key is required for this route.', 422);
        }

        // Check pivot table branch_modules (or settings table) for enablement
        $exists = class_exists(BranchModule::class)
            ? BranchModule::query()
                ->where('branch_id', $branch->getKey())
                ->when(class_exists(Module::class), fn ($q) => $q->whereHas('module', fn ($w) => $w->where('key', $key)))
                ->when(! class_exists(Module::class), fn ($q) => $q->where('module_key', $key)) // fallback schema
                ->exists()
            : true; // If schema absent, don't block development flows

        if (! $exists) {
            return $this->error("Module [$key] is not enabled for this branch.", 403);
        }

        return $next($request);
    }

    protected function error(string $message, int $status): Response
    {
        return response()->json(['success' => false, 'message' => $message], $status);
    }
}
