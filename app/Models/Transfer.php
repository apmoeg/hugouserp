<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Transfer extends BaseModel
{
    protected ?string $moduleKey = 'inventory';

    protected $fillable = ['branch_id', 'from_warehouse_id', 'to_warehouse_id', 'status', 'note', 'created_by', 'extra_attributes'];

    protected $casts = [
        'extra_attributes' => 'array',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(TransferItem::class);
    }

    public function fromWarehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'from_warehouse_id');
    }

    public function toWarehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'to_warehouse_id');
    }
}
