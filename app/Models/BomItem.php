<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BomItem extends BaseModel
{
    use HasFactory;

    protected $fillable = [
        'bom_id',
        'product_id',
        'quantity',
        'unit_id',
        'scrap_percentage',
        'sort_order',
        'notes',
        'is_alternative',
        'alternative_group_id',
        'metadata',
    ];

    protected $casts = [
        'quantity' => 'decimal:4',
        'scrap_percentage' => 'decimal:2',
        'sort_order' => 'integer',
        'is_alternative' => 'boolean',
        'metadata' => 'array',
    ];

    /**
     * Get the BOM that owns the item.
     */
    public function bom(): BelongsTo
    {
        return $this->belongsTo(BillOfMaterial::class, 'bom_id');
    }

    /**
     * Get the product (component/material).
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the unit of measure.
     */
    public function unit(): BelongsTo
    {
        return $this->belongsTo(UnitOfMeasure::class, 'unit_id');
    }

    /**
     * Calculate effective quantity including scrap.
     */
    public function getEffectiveQuantityAttribute(): float
    {
        $baseQuantity = (float) $this->quantity;
        $scrapFactor = 1 + ((float) $this->scrap_percentage / 100);
        
        return $baseQuantity * $scrapFactor;
    }
}
