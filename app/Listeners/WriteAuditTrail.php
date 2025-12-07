<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Models\AuditLog;
use Illuminate\Contracts\Queue\ShouldQueue;

class WriteAuditTrail implements ShouldQueue
{
    public function handle(object $event): void
    {
        // Generic fallback writer for domain events.
        try {
            $req = request();
            AuditLog::create([
                'user_id' => optional(auth()->user())->getKey(),
                'action' => class_basename($event),
                'subject_type' => method_exists($event, 'subjectType') ? $event->subjectType() : null,
                'subject_id' => method_exists($event, 'subjectId') ? $event->subjectId() : null,
                'ip' => $req?->ip(),
                'user_agent' => (string) $req?->userAgent(),
                'old_values' => method_exists($event, 'old') ? (array) $event->old() : [],
                'new_values' => method_exists($event, 'new') ? (array) $event->new() : [],
            ]);
        } catch (\Throwable) {
            // swallow errors
        }
    }
}
