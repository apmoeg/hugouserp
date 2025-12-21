<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Property extends BaseModel
{
    protected ?string $moduleKey = 'rentals';

    protected $table = 'properties';

    protected $fillable = ['branch_id', 'name', 'address', 'notes', 'extra_attributes'];

    protected $casts = ['extra_attributes' => 'array'];

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function units(): HasMany
    {
        return $this->hasMany(RentalUnit::class, 'property_id');
    }
}
