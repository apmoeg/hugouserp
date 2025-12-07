<?php

namespace App\Models;

class PurchaseItem extends BaseModel
{
    protected ?string $moduleKey = 'purchases';

    protected $table = 'purchase_items';

    protected $with = ['product'];

    protected $fillable = [
        'purchase_id', 'product_id', 'branch_id', 'tax_id',
        'qty', 'uom', 'unit_cost', 'discount', 'tax_rate', 'line_total',
        'extra_attributes', 'created_by', 'updated_by',
    ];

    protected $casts = [
        'qty' => 'decimal:4',
        'unit_cost' => 'decimal:4',
        'discount' => 'decimal:4',
        'tax_rate' => 'decimal:4',
        'line_total' => 'decimal:4',
        'extra_attributes' => 'array',
    ];

    public function purchase()
    {
        return $this->belongsTo(Purchase::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function tax()
    {
        return $this->belongsTo(Tax::class, 'tax_id');
    }
}
