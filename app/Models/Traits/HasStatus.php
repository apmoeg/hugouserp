<?php

declare(strict_types=1);

namespace App\Models\Traits;

use Illuminate\Database\Eloquent\Builder;

trait HasStatus
{
    public function scopeActive(Builder $query): Builder
    {
        $statusColumn = $this->getStatusColumn();

        return $query->where($statusColumn, true);
    }

    public function scopeInactive(Builder $query): Builder
    {
        $statusColumn = $this->getStatusColumn();

        return $query->where($statusColumn, false);
    }

    public function activate(): self
    {
        $statusColumn = $this->getStatusColumn();
        $this->{$statusColumn} = true;
        $this->save();

        return $this;
    }

    public function deactivate(): self
    {
        $statusColumn = $this->getStatusColumn();
        $this->{$statusColumn} = false;
        $this->save();

        return $this;
    }

    public function toggleStatus(): self
    {
        $statusColumn = $this->getStatusColumn();
        $this->{$statusColumn} = ! $this->{$statusColumn};
        $this->save();

        return $this;
    }

    public function isActive(): bool
    {
        $statusColumn = $this->getStatusColumn();

        return (bool) $this->{$statusColumn};
    }

    public function isInactive(): bool
    {
        return ! $this->isActive();
    }

    protected function getStatusColumn(): string
    {
        return property_exists($this, 'statusColumn') ? $this->statusColumn : 'is_active';
    }
}
