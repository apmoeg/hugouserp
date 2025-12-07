<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ModuleNavigation extends Model
{
    use HasFactory;

    protected $fillable = [
        'module_id',
        'parent_id',
        'nav_key',
        'nav_label',
        'nav_label_ar',
        'route_name',
        'icon',
        'required_permissions',
        'visibility_conditions',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'required_permissions' => 'array',
        'visibility_conditions' => 'array',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function module(): BelongsTo
    {
        return $this->belongsTo(Module::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(ModuleNavigation::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(ModuleNavigation::class, 'parent_id');
    }

    /**
     * Get localized label
     */
    public function getLocalizedLabelAttribute(): string
    {
        return app()->getLocale() === 'ar' && $this->nav_label_ar
            ? $this->nav_label_ar
            : $this->nav_label;
    }

    /**
     * Scope query to active navigation items
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope query to specific module
     */
    public function scopeForModule($query, int $moduleId)
    {
        return $query->where('module_id', $moduleId);
    }

    /**
     * Scope query to root navigation items
     */
    public function scopeRootItems($query)
    {
        return $query->whereNull('parent_id');
    }

    /**
     * Scope query ordered by sort order
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('nav_label');
    }

    /**
     * Check if user has access to this navigation item
     */
    public function userHasAccess($user, ?int $branchId = null): bool
    {
        if (! $this->is_active) {
            return false;
        }

        // Check required permissions
        if (! empty($this->required_permissions)) {
            foreach ($this->required_permissions as $permission) {
                if (! $user->can($permission)) {
                    return false;
                }
            }
        }

        // Check visibility conditions
        if (! empty($this->visibility_conditions)) {
            if (! $this->evaluateVisibilityConditions($this->visibility_conditions, $user, $branchId)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Evaluate visibility conditions
     */
    protected function evaluateVisibilityConditions(array $conditions, $user, ?int $branchId): bool
    {
        // Simple condition evaluation - can be extended
        foreach ($conditions as $key => $value) {
            switch ($key) {
                case 'branch_required':
                    if ($value && ! $branchId) {
                        return false;
                    }
                    break;
                case 'module_enabled':
                    if ($value && $branchId) {
                        $moduleService = app(\App\Services\ModuleService::class);
                        if (! $moduleService->isEnabled($this->module->key, $branchId)) {
                            return false;
                        }
                    }
                    break;
            }
        }

        return true;
    }

    /**
     * Get all children recursively
     */
    public function getAllChildren(): \Illuminate\Support\Collection
    {
        $children = collect();

        foreach ($this->children as $child) {
            $children->push($child);
            $children = $children->merge($child->getAllChildren());
        }

        return $children;
    }
}
