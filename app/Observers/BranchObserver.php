<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\AuditLog;
use App\Models\Branch;
use Illuminate\Support\Str;

class BranchObserver
{
    public function creating(Branch $branch): void
    {
        if (! $branch->getAttribute('code') && $branch->getAttribute('name')) {
            $branch->code = Str::upper(Str::slug((string) $branch->name, '_'));
        }
        if ($branch->getAttribute('is_active') === null) {
            $branch->is_active = true;
        }
    }

    public function created(Branch $branch): void
    {
        $this->audit('created', $branch);
    }

    public function updating(Branch $branch): void
    {
        // Keep code in sync with name if not manually maintained
        if ($branch->isDirty('name') && ! $branch->isDirty('code')) {
            $branch->code = Str::upper(Str::slug((string) $branch->name, '_'));
        }
    }

    public function updated(Branch $branch): void
    {
        $this->audit('updated', $branch, $branch->getChanges());
    }

    public function deleted(Branch $branch): void
    {
        $this->audit('deleted', $branch);
    }

    protected function audit(string $action, Branch $branch, array $changes = []): void
    {
        try {
            $req = request();
            AuditLog::create([
                'user_id' => optional(auth()->user())->getKey(),
                'action' => "Branch:{$action}",
                'subject_type' => Branch::class,
                'subject_id' => $branch->getKey(),
                'ip' => $req?->ip(),
                'user_agent' => (string) $req?->userAgent(),
                'old_values' => [],
                'new_values' => $changes ?: $branch->attributesToArray(),
            ]);
        } catch (\Throwable $e) {
            // Swallow observer errors to not break requests
        }
    }
}
