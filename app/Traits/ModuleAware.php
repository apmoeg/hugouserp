<?php

declare(strict_types=1);

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;

trait ModuleAware
{
    /**
     * Override in the model if you need a different column name.
     */
    protected string $moduleColumn = 'module_key';

    public function getModuleKey(): ?string
    {
        if (property_exists($this, 'moduleKey') && isset($this->moduleKey)) {
            return $this->moduleKey;
        }

        if (method_exists($this, 'moduleKey')) {
            return (string) $this->moduleKey();
        }

        // Fallback: try simple map based on class name (can be extended via config if needed)
        return null;
    }

    public function scopeInModule(Builder $query, string $key): Builder
    {
        $column = $this->moduleColumn ?? 'module_key';

        return $query->where($this->getTable().'.'.$column, $key);
    }
}
