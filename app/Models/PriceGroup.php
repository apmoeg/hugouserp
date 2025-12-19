<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PriceGroup extends BaseModel
{
    protected ?string $moduleKey = 'pricing';

    protected $fillable = ['branch_id', 'code', 'name', 'description', 'is_active', 'extra_attributes'];

    protected $casts = [
        'is_active' => 'bool',
        'extra_attributes' => 'array',
    ];

    public function customers(): HasMany
    {
        return $this->hasMany(Customer::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }
}
