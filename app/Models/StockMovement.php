<?php

namespace App\Models;

use Illuminate\Support\Str;

class StockMovement extends BaseModel
{
    protected ?string $moduleKey = 'inventory';

    protected $table = 'stock_movements';

    protected $fillable = [
        'uuid', 'code', 'branch_id', 'warehouse_id', 'product_id',
        'direction', 'qty', 'uom', 'unit_cost', 'cost_currency',
        'valuated_amount',
        'reference_type', 'reference_id',
        'batch_no', 'serial_no', 'expires_at',
        'status', 'notes', 'extra_attributes', 'created_by', 'updated_by',
    ];

    protected $casts = [
        'qty' => 'decimal:4',
        'unit_cost' => 'decimal:4',
        'valuated_amount' => 'decimal:4',
        'expires_at' => 'datetime',
        'extra_attributes' => 'array',
    ];

    protected static function booted(): void
    {
        parent::booted();

        static::creating(function ($m) {
            $m->uuid = $m->uuid ?: (string) Str::uuid();
            $m->code = $m->code ?: 'STM-'.Str::upper(Str::random(8));
            if ($m->valuated_amount === null) {
                $m->valuated_amount = (string) ((float) $m->qty * (float) $m->unit_cost);
            }
        });
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    // Polymorphic source document: PurchaseItem, SaleItem, AdjustmentItem, TransferItem, etc.
    public function source()
    {
        return $this->morphTo(__FUNCTION__, 'reference_type', 'reference_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function scopeIn($q)
    {
        return $q->where('direction', 'in');
    }

    public function scopeOut($q)
    {
        return $q->where('direction', 'out');
    }

    public function scopeForProduct($q, $id)
    {
        return $q->where('product_id', $id);
    }
}
