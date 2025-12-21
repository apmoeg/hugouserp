<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductCompatibility extends Model
{
    protected $table = 'product_compatibilities';

    protected $fillable = [
        'product_id',
        'vehicle_model_id',
        'oem_number',
        'position',
        'notes',
        'is_verified',
    ];

    protected $casts = [
        'is_verified' => 'boolean',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function vehicleModel(): BelongsTo
    {
        return $this->belongsTo(VehicleModel::class);
    }

    public function scopeVerified($query)
    {
        return $query->where('is_verified', true);
    }

    public function scopeForProduct($query, int $productId)
    {
        return $query->where('product_id', $productId);
    }

    public function scopeForVehicle($query, int $vehicleModelId)
    {
        return $query->where('vehicle_model_id', $vehicleModelId);
    }

    public function scopeByOem($query, string $oemNumber)
    {
        return $query->where('oem_number', 'like', "%{$oemNumber}%");
    }
}
