<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Purchase extends BaseModel
{
    use LogsActivity;
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
            // Use configurable purchase order prefix from settings
            $prefix = setting('purchases.purchase_order_prefix', 'PO-');
            $m->code = $m->code ?: $prefix.Str::upper(Str::random(8));
        });
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseItem::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Receipt::class);
    }

    public function returnNotes(): HasMany
    {
        return $this->hasMany(ReturnNote::class);
    }

    public function requisitions(): HasMany
    {
        return $this->hasMany(PurchaseRequisition::class, 'converted_to_po_id');
    }

    public function grns(): HasMany
    {
        return $this->hasMany(GoodsReceivedNote::class, 'purchase_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    // Business Logic
    public function getTotalQuantityReceived(): float
    {
        return $this->grns()->where('status', 'approved')->get()->sum(function ($grn) {
            return $grn->getTotalQuantityAccepted();
        });
    }

    public function isFullyReceived(): bool
    {
        $orderedQty = $this->items->sum('qty');
        $receivedQty = $this->getTotalQuantityReceived();

        return $receivedQty >= $orderedQty;
    }

    public function isPartiallyReceived(): bool
    {
        $receivedQty = $this->getTotalQuantityReceived();

        return $receivedQty > 0 && !$this->isFullyReceived();
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

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['code', 'status', 'grand_total', 'paid_total', 'supplier_id', 'branch_id'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn(string $eventName) => "Purchase {$this->code} was {$eventName}");
    }
}
