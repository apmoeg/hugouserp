<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class UserDashboardLayout extends BaseModel
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'branch_id',
        'name',
        'is_default',
        'layout_config',
    ];

    protected $casts = [
        'is_default' => 'boolean',
        'layout_config' => 'array',
    ];

    /**
     * Get the user.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the branch.
     */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * Get the widgets in this layout.
     */
    public function widgets(): HasMany
    {
        return $this->hasMany(UserDashboardWidget::class);
    }

    /**
     * Get visible widgets.
     */
    public function visibleWidgets()
    {
        return $this->widgets()->where('is_visible', true)->orderBy('sort_order');
    }

    /**
     * Scope: Default layouts.
     */
    public function scopeDefault(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('is_default', true);
    }

    /**
     * Make this layout the default for the user.
     */
    public function makeDefault(): void
    {
        // Set all other layouts to non-default
        static::where('user_id', $this->user_id)
            ->where('id', '!=', $this->id)
            ->update(['is_default' => false]);

        $this->update(['is_default' => true]);
    }
}
