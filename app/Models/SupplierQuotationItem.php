<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupplierQuotationItem extends BaseModel
{
    protected ?string $moduleKey = 'purchases';

    protected $table = 'supplier_quotation_items';

    protected $fillable = [
        'quotation_id', 'product_id', 'quantity',
        'unit_price', 'tax_percent', 'line_total', 'notes',
        'extra_attributes', 'created_by', 'updated_by',
    ];

    protected $casts = [
        'quantity' => 'decimal:4',
        'unit_price' => 'decimal:4',
        'tax_percent' => 'decimal:2',
        'line_total' => 'decimal:4',
        'extra_attributes' => 'array',
    ];

    // Relationships
    public function quotation(): BelongsTo
    {
        return $this->belongsTo(SupplierQuotation::class, 'quotation_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    // Backward compatibility accessors for qty
    public function getQtyAttribute()
    {
        return $this->quantity;
    }

    public function setQtyAttribute($value): void
    {
        $this->attributes['quantity'] = $value;
    }

    // Backward compatibility for unit_cost -> unit_price
    public function getUnitCostAttribute()
    {
        return $this->unit_price;
    }

    public function setUnitCostAttribute($value): void
    {
        $this->attributes['unit_price'] = $value;
    }

    // Backward compatibility for tax_rate -> tax_percent
    public function getTaxRateAttribute()
    {
        return $this->tax_percent;
    }

    public function setTaxRateAttribute($value): void
    {
        $this->attributes['tax_percent'] = $value;
    }
}
