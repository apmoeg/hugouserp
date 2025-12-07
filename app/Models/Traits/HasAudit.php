<?php

declare(strict_types=1);

namespace App\Models\Traits;

use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait HasAudit
{
    public static function bootHasAudit(): void
    {
        static::creating(function ($model) {
            $userId = static::resolveAuditUserId();
            if ($userId && ! $model->created_by) {
                $model->created_by = $userId;
            }
        });

        static::updating(function ($model) {
            $userId = static::resolveAuditUserId();
            if ($userId) {
                $model->updated_by = $userId;
            }
        });
    }

    protected static function resolveAuditUserId(): ?int
    {
        if (! function_exists('auth')) {
            return null;
        }

        try {
            return auth()->check() ? auth()->id() : null;
        } catch (\Exception $e) {
            return null;
        }
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function getCreatedByNameAttribute(): ?string
    {
        return $this->creator?->name;
    }

    public function getUpdatedByNameAttribute(): ?string
    {
        return $this->updater?->name;
    }

    public function wasCreatedBy(int $userId): bool
    {
        return $this->created_by === $userId;
    }

    public function wasLastUpdatedBy(int $userId): bool
    {
        return $this->updated_by === $userId;
    }
}
