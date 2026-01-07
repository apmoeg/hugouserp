<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Tax extends BaseModel
{
    protected ?string $moduleKey = 'pricing';

    protected $table = 'taxes';

    protected $fillable = ['branch_id', 'code', 'name', 'name_ar', 'description', 'rate', 'type', 'is_compound', 'is_inclusive', 'is_active', 'extra_attributes'];

    protected $casts = [
        'rate' => 'decimal:4',
        'is_compound' => 'bool',
        'is_inclusive' => 'bool',
        'is_active' => 'bool',
        'extra_attributes' => 'array',
    ];

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }
}
