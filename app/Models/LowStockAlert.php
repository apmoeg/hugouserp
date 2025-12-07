<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Traits\HasBranch;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LowStockAlert extends Model
{
    use HasBranch;

    protected $fillable = [
        'product_id',
        'branch_id',
        'warehouse_id',
        'current_qty',
        'min_qty',
        'status',
        'acknowledged_by',
        'acknowledged_at',
        'resolved_by',
        'resolved_at',
    ];

    protected $casts = [
        'acknowledged_at' => 'datetime',
        'resolved_at' => 'datetime',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function acknowledgedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'acknowledged_by');
    }

    public function resolvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }

    public function acknowledge(int $userId): void
    {
        $this->update([
            'status' => 'acknowledged',
            'acknowledged_by' => $userId,
            'acknowledged_at' => now(),
        ]);
    }

    public function resolve(int $userId): void
    {
        $this->update([
            'status' => 'resolved',
            'resolved_by' => $userId,
            'resolved_at' => now(),
        ]);
    }

    public function isCritical(): bool
    {
        return $this->current_qty <= $this->min_qty * 0.25;
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }

    public function scopeUnresolved(Builder $query): Builder
    {
        return $query->whereIn('status', ['active', 'acknowledged']);
    }
}
