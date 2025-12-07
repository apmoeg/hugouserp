<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TransferItem extends BaseModel
{
    protected ?string $moduleKey = 'inventory';

    protected $fillable = ['transfer_id', 'product_id', 'qty', 'extra_attributes'];

    protected $casts = ['qty' => 'decimal:4'];

    public function transfer(): BelongsTo
    {
        return $this->belongsTo(Transfer::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
