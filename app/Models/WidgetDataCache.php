<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WidgetDataCache extends BaseModel
{
    use HasFactory;

    protected $table = 'widget_data_cache';

    protected $fillable = [
        'user_id',
        'dashboard_widget_id',
        'widget_id',
        'branch_id',
        'cache_key',
        'data',
        'cached_at',
        'expires_at',
    ];

    protected $casts = [
        'data' => 'array',
        'cached_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    /**
     * Get the user.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the widget.
     */
    public function widget(): BelongsTo
    {
        return $this->belongsTo(DashboardWidget::class, 'dashboard_widget_id');
    }

    /**
     * Get the branch.
     */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * Check if cache is expired.
     */
    public function isExpired(): bool
    {
        if (! $this->expires_at) {
            return false;
        }

        return now()->isAfter($this->expires_at);
    }

    /**
     * Scope: Not expired.
     */
    public function scopeValid(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where(function ($q) {
            $q->whereNull('expires_at')
                ->orWhere('expires_at', '>', now());
        });
    }

    /**
     * Get cached data for widget.
     */
    public static function getCached(int $userId, int $widgetId, ?int $branchId = null): ?array
    {
        $cache = static::where('user_id', $userId)
            ->where('dashboard_widget_id', $widgetId)
            ->where('branch_id', $branchId)
            ->valid()
            ->first();

        return $cache?->data;
    }

    /**
     * Store widget data in cache.
     */
    public static function store(int $userId, int $widgetId, array $data, ?int $branchId = null, ?int $ttlMinutes = 30): void
    {
        static::updateOrCreate(
            [
                'user_id' => $userId,
                'dashboard_widget_id' => $widgetId,
                'branch_id' => $branchId,
            ],
            [
                'data' => $data,
                'cached_at' => now(),
                'expires_at' => $ttlMinutes ? now()->addMinutes($ttlMinutes) : null,
            ]
        );
    }
}
