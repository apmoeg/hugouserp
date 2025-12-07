<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Vehicle extends BaseModel
{
    protected ?string $moduleKey = 'vehicles';

    protected $fillable = ['branch_id', 'vin', 'plate', 'brand', 'model', 'year', 'color', 'status', 'sale_price', 'cost', 'extra_attributes'];

    protected $casts = ['year' => 'int', 'sale_price' => 'decimal:2', 'cost' => 'decimal:2', 'extra_attributes' => 'array'];

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function contracts(): HasMany
    {
        return $this->hasMany(VehicleContract::class);
    }

    public function warranties(): HasMany
    {
        return $this->hasMany(Warranty::class);
    }
}
