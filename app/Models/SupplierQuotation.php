<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SupplierQuotation extends BaseModel
{
    protected ?string $moduleKey = 'purchases';

    protected $table = 'supplier_quotations';

    protected $fillable = [
        'code', 'branch_id', 'supplier_id', 'requisition_id',
        'status', 'quotation_date', 'valid_until', 'currency',
        'sub_total', 'discount_total', 'tax_total', 'shipping_total', 'grand_total',
        'payment_terms', 'delivery_terms', 'delivery_days',
        'terms_and_conditions', 'notes', 'rejection_reason',
        'extra_attributes', 'created_by', 'updated_by',
    ];

    protected $casts = [
        'quotation_date' => 'date',
        'valid_until' => 'date',
        'sub_total' => 'decimal:4',
        'discount_total' => 'decimal:4',
        'tax_total' => 'decimal:4',
        'shipping_total' => 'decimal:4',
        'grand_total' => 'decimal:4',
        'extra_attributes' => 'array',
    ];

    protected static function booted(): void
    {
        parent::booted();

        static::creating(function ($model) {
            if (! $model->code) {
                $model->code = 'QT-'.date('Ymd').'-'.str_pad((string) (static::whereDate('created_at', today())->count() + 1), 5, '0', STR_PAD_LEFT);
            }
        });
    }

    // Relationships
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function requisition(): BelongsTo
    {
        return $this->belongsTo(PurchaseRequisition::class, 'requisition_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(SupplierQuotationItem::class, 'quotation_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    // Scopes
    public function scopeActive(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('status', '!=', 'expired')
            ->where('status', '!=', 'rejected')
            ->where(function ($q) {
                $q->whereNull('valid_until')
                    ->orWhere('valid_until', '>=', now());
            });
    }

    public function scopeExpired(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('valid_until', '<', now())
            ->where('status', '!=', 'accepted');
    }

    public function scopeAccepted(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('status', 'accepted');
    }

    // Business Logic
    public function accept(): void
    {
        $this->update(['status' => 'accepted']);
    }

    public function reject(string $reason): void
    {
        $this->update([
            'status' => 'rejected',
            'rejection_reason' => $reason,
        ]);
    }

    public function isExpired(): bool
    {
        return $this->valid_until && $this->valid_until->isPast();
    }

    public function canBeAccepted(): bool
    {
        return in_array($this->status, ['sent', 'received']) && ! $this->isExpired();
    }
}
