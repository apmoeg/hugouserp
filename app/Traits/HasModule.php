<?php

declare(strict_types=1);

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;

trait HasModule
{
    public static function bootHasModule(): void
    {
        static::creating(function ($model): void {
            if ($model->isFillable('module_key') &&
                ! $model->getAttribute('module_key') &&
                method_exists($model, 'currentModuleKey')
            ) {
                $key = $model->currentModuleKey();
                if ($key) {
                    $model->setAttribute('module_key', $key);
                }
            }
        });
    }

    public function scopeForModule(Builder $q, ?string $moduleKey): Builder
    {
        return $moduleKey ? $q->where($this->getTable().'.module_key', $moduleKey) : $q;
    }

    public function scopeInRequestModule(Builder $q): Builder
    {
        $key = method_exists($this, 'currentModuleKey') ? $this->currentModuleKey() : null;

        return $this->scopeForModule($q, $key);
    }
}
