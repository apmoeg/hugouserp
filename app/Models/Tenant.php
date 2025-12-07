<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;

class Tenant extends BaseModel
{
    protected ?string $moduleKey = 'rentals';

    protected $fillable = ['branch_id', 'name', 'email', 'phone', 'address', 'is_active', 'extra_attributes'];

    protected $casts = ['is_active' => 'bool', 'extra_attributes' => 'array'];

    public function contracts(): HasMany
    {
        return $this->hasMany(RentalContract::class);
    }
}
