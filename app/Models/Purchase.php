<?php

namespace App\Models;

use Illuminate\Support\Str;

class Purchase extends BaseModel
{
    protected ?string $moduleKey = 'purchases';

    protected $table = 'purchases';

    protected $with = ['supplier', 'createdBy'];

    protected $fillable = [
        'uuid', 'code', 'branch_id', 'warehouse_id', 'supplier_id',
        'status', 'currency', 'sub_total', 'discount_total', 'tax_total', 'shipping_total', 'grand_total',
        'paid_total', 'due_total', 'reference_no', 'posted_at',
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
            $m->code = $m->code ?: 'PO-'.Str::upper(Str::random(8));
        });
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function items()
    {
        return $this->hasMany(PurchaseItem::class);
    }

    public function payments()
    {
        return $this->hasMany(Receipt::class);
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
}
