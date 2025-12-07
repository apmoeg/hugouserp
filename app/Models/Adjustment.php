<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;

class Adjustment extends BaseModel
{
    protected ?string $moduleKey = 'inventory';

    protected $fillable = ['branch_id', 'warehouse_id', 'reason', 'note', 'created_by', 'extra_attributes'];

    protected $casts = [
        'extra_attributes' => 'array',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(AdjustmentItem::class);
    }
}
