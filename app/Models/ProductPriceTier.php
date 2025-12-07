<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductPriceTier extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'branch_id',
        'tier_name',
        'tier_name_ar',
        'min_quantity',
        'max_quantity',
        'cost_price',
        'selling_price',
        'wholesale_price',
        'is_active',
    ];

    protected $casts = [
        'min_quantity' => 'decimal:4',
        'max_quantity' => 'decimal:4',
        'cost_price' => 'decimal:4',
        'selling_price' => 'decimal:4',
        'wholesale_price' => 'decimal:4',
        'is_active' => 'boolean',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function getLocalizedNameAttribute(): string
    {
        $locale = app()->getLocale();

        return $locale === 'ar' && $this->tier_name_ar ? $this->tier_name_ar : $this->tier_name;
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForBranch($query, $branchId)
    {
        return $query->where(function ($q) use ($branchId) {
            $q->where('branch_id', $branchId)
                ->orWhereNull('branch_id');
        });
    }

    public function scopeForQuantity($query, $quantity)
    {
        return $query->where('min_quantity', '<=', $quantity)
            ->where(function ($q) use ($quantity) {
                $q->whereNull('max_quantity')
                    ->orWhere('max_quantity', '>=', $quantity);
            });
    }
}
