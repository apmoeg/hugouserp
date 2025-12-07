<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductVariation extends Model
{
    use HasFactory;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'product_id',
        'sku',
        'name',
        'attributes',
        'price',
        'cost_price',
        'current_stock',
        'is_active',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'attributes' => 'array',
        'current_stock' => 'float',
        'is_active' => 'bool',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
