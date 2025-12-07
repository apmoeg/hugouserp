<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalePayment extends Model
{
    protected $fillable = [
        'sale_id',
        'branch_id',
        'payment_method',
        'amount',
        'currency',
        'exchange_rate',
        'reference_no',
        'card_type',
        'card_last_four',
        'bank_name',
        'cheque_number',
        'cheque_date',
        'notes',
        'status',
        'created_by',
    ];

    protected $casts = [
        'amount' => 'decimal:4',
        'exchange_rate' => 'decimal:6',
        'cheque_date' => 'date',
    ];

    public const METHOD_CASH = 'cash';

    public const METHOD_CARD = 'card';

    public const METHOD_TRANSFER = 'transfer';

    public const METHOD_CHEQUE = 'cheque';

    public static function paymentMethods(): array
    {
        return [
            self::METHOD_CASH => __('Cash'),
            self::METHOD_CARD => __('Card'),
            self::METHOD_TRANSFER => __('Bank Transfer'),
            self::METHOD_CHEQUE => __('Cheque'),
        ];
    }

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
