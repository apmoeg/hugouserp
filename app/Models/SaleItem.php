<?php

namespace App\Models;

class SaleItem extends BaseModel
{
    protected ?string $moduleKey = 'sales';

    protected $table = 'sale_items';

    protected $with = ['product'];

    protected $fillable = [
        'sale_id', 'product_id', 'branch_id', 'tax_id',
        'qty', 'uom', 'unit_price', 'discount', 'tax_rate', 'line_total',
        'extra_attributes', 'created_by', 'updated_by',
    ];

    protected $casts = [
        'qty' => 'decimal:4',
        'unit_price' => 'decimal:4',
        'discount' => 'decimal:4',
        'tax_rate' => 'decimal:4',
        'line_total' => 'decimal:4',
        'extra_attributes' => 'array',
    ];

    public function sale()
    {
        return $this->belongsTo(Sale::class);
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
