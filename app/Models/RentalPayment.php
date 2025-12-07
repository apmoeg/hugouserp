<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RentalPayment extends BaseModel
{
    protected ?string $moduleKey = 'rentals';

    protected $fillable = ['contract_id', 'invoice_id', 'method', 'amount', 'paid_at', 'reference', 'extra_attributes'];

    protected $casts = ['amount' => 'decimal:2', 'paid_at' => 'datetime'];

    public function contract(): BelongsTo
    {
        return $this->belongsTo(RentalContract::class, 'contract_id');
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(RentalInvoice::class, 'invoice_id');
    }
}
