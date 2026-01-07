<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AlertRule extends BaseModel
{
    use HasFactory;

    protected $fillable = [
        'branch_id',
        'name',
        'name_ar',
        'description',
        'category',
        'alert_type',
        'severity',
        'conditions',
        'thresholds',
        'metric_type',
        'check_frequency_minutes',
        'is_active',
        'send_email',
        'send_notification',
        'recipient_roles',
        'recipient_users',
        'last_checked_at',
        'last_triggered_at',
    ];

    protected $casts = [
        'conditions' => 'array',
        'thresholds' => 'array',
        'check_frequency_minutes' => 'integer',
        'is_active' => 'boolean',
        'send_email' => 'boolean',
        'send_notification' => 'boolean',
        'recipient_roles' => 'array',
        'recipient_users' => 'array',
        'last_checked_at' => 'datetime',
        'last_triggered_at' => 'datetime',
    ];

    /**
     * Get the branch.
     */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * Get alert instances.
     */
    public function instances(): HasMany
    {
        return $this->hasMany(AlertInstance::class);
    }

    /**
     * Scope: Active rules.
     */
    public function scopeActive(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: By category.
     */
    public function scopeCategory(\Illuminate\Database\Eloquent\Builder $query, string $category): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('category', $category);
    }

    /**
     * Scope: By alert type.
     */
    public function scopeType(\Illuminate\Database\Eloquent\Builder $query, string $type): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('alert_type', $type);
    }

    /**
     * Scope: Due for check.
     */
    public function scopeDueForCheck(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where(function ($q) {
            $q->whereNull('last_checked_at')
                ->orWhereRaw('last_checked_at < DATE_SUB(NOW(), INTERVAL check_frequency_minutes MINUTE)');
        });
    }

    /**
     * Check if rule should be checked now.
     */
    public function isDueForCheck(): bool
    {
        if (! $this->last_checked_at) {
            return true;
        }

        return $this->last_checked_at->addMinutes($this->check_frequency_minutes)->isPast();
    }

    /**
     * Mark as checked.
     */
    public function markChecked(): void
    {
        $this->update(['last_checked_at' => now()]);
    }

    /**
     * Mark as triggered.
     */
    public function markTriggered(): void
    {
        $this->update(['last_triggered_at' => now()]);
    }
}
