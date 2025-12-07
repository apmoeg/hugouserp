<?php

declare(strict_types=1);

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait HasBranch
{
    public static function bootHasBranch(): void
    {
        static::creating(function (Model $model): void {
            if (! $model->getAttribute('branch_id') && method_exists($model, 'currentBranchId')) {
                $branchId = $model->currentBranchId();
                if ($branchId) {
                    $model->setAttribute('branch_id', $branchId);
                }
            }
        });
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Branch::class);
    }

    public function scopeForBranch(Builder $q, $branch): Builder
    {
        $id = is_object($branch) ? $branch->getKey() : $branch;

        return $q->where($this->getTable().'.branch_id', $id);
    }

    public function scopeInRequestBranch(Builder $q): Builder
    {
        $id = method_exists($this, 'currentBranchId') ? $this->currentBranchId() : null;

        return $id ? $q->where($this->getTable().'.branch_id', $id) : $q;
    }
}
