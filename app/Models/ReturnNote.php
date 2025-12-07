<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReturnNote extends BaseModel
{
    protected ?string $moduleKey = 'sales';

    protected $fillable = ['branch_id', 'sale_id', 'purchase_id', 'reason', 'total', 'created_by', 'extra_attributes'];

    protected $casts = ['total' => 'decimal:2'];

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    public function purchase(): BelongsTo
    {
        return $this->belongsTo(Purchase::class);
    }
}
