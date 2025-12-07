<?php

namespace App\Models;

use Illuminate\Support\Str;

class Sale extends BaseModel
{
    protected ?string $moduleKey = 'sales';

    protected $table = 'sales';

    protected $with = ['customer', 'createdBy'];

    protected $fillable = [
        'uuid', 'code', 'branch_id', 'warehouse_id', 'customer_id',
        'status', 'channel', 'currency',
        'sub_total', 'discount_total', 'tax_total', 'shipping_total', 'grand_total',
        'paid_total', 'due_total',
        'reference_no', 'posted_at',
        'notes', 'extra_attributes', 'created_by', 'updated_by',
    ];

    protected $casts = [
        'sub_total' => 'decimal:4',
        'discount_total' => 'decimal:4',
        'tax_total' => 'decimal:4',
        'shipping_total' => 'decimal:4',
        'grand_total' => 'decimal:4',
        'paid_total' => 'decimal:4',
        'due_total' => 'decimal:4',
        'posted_at' => 'datetime',
        'extra_attributes' => 'array',
    ];

    protected static function booted(): void
    {
        parent::booted();

        static::creating(function ($m) {
            $m->uuid = $m->uuid ?: (string) Str::uuid();
            $m->code = $m->code ?: 'SO-'.Str::upper(Str::random(8));
        });
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function items()
    {
        return $this->hasMany(SaleItem::class);
    }

    public function receipts()
    {
        return $this->hasMany(Receipt::class);
    }

    public function deliveries()
    {
        return $this->hasMany(Delivery::class);
    }

    public function returnNotes()
    {
        return $this->hasMany(ReturnNote::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function payments()
    {
        return $this->hasMany(SalePayment::class);
    }

    public function scopePosted($q)
    {
        return $q->where('status', 'posted');
    }

    public function getTotalPaidAttribute(): float
    {
        return (float) $this->payments()->sum('amount');
    }

    public function getRemainingAmountAttribute(): float
    {
        return max(0, (float) $this->grand_total - $this->total_paid);
    }

    public function isPaid(): bool
    {
        return $this->remaining_amount <= 0;
    }

    public function storeOrder()
    {
        return $this->belongsTo(StoreOrder::class);
    }
}
