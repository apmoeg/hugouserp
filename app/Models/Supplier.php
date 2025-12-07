<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Supplier extends BaseModel
{
    protected ?string $moduleKey = 'suppliers';

    protected $fillable = ['branch_id', 'name', 'email', 'phone', 'address', 'tax_number', 'is_active', 'extra_attributes'];

    protected $casts = ['is_active' => 'bool', 'extra_attributes' => 'array'];

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function purchases(): HasMany
    {
        return $this->hasMany(Purchase::class);
    }
}
