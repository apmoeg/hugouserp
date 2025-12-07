<?php

declare(strict_types=1);

namespace App\Models;

class Tax extends BaseModel
{
    protected ?string $moduleKey = 'pricing';

    protected $fillable = ['name', 'rate', 'type', 'is_inclusive', 'extra_attributes'];

    protected $casts = ['rate' => 'decimal:4', 'is_inclusive' => 'bool'];
}
