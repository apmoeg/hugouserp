<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ModuleOperation extends Model
{
    use HasFactory;

    protected $fillable = [
        'module_id',
        'operation_key',
        'operation_name',
        'description',
        'operation_type',
        'operation_config',
        'required_permissions',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'operation_config' => 'array',
        'required_permissions' => 'array',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function module(): BelongsTo
    {
        return $this->belongsTo(Module::class);
    }

    /**
     * Scope query to active operations
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
     * Scope query by operation type
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('operation_type', $type);
    }

    /**
     * Scope query ordered by sort order
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('operation_name');
    }

    /**
     * Check if user has required permissions for this operation
     */
    public function userCanExecute($user): bool
    {
        if (! $this->is_active) {
            return false;
        }

        if (empty($this->required_permissions)) {
            return true;
        }

        foreach ($this->required_permissions as $permission) {
            if (! $user->can($permission)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Get operation configuration value
     */
    public function getConfig(string $key, $default = null)
    {
        return $this->operation_config[$key] ?? $default;
    }
}
