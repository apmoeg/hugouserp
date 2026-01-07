<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PurchaseRequisition extends BaseModel
{
    protected ?string $moduleKey = 'purchases';

    protected $table = 'purchase_requisitions';

    protected $fillable = [
        'code', 'branch_id', 'department_id', 'requested_by',
        'status', 'priority', 'required_date', 'justification', 'notes',
        'estimated_total', 'approved_by', 'approved_at', 'rejection_reason',
        'is_converted', 'converted_to_po_id', 'extra_attributes',
        'created_by', 'updated_by',
    ];

    protected $casts = [
        'required_date' => 'date',
        'estimated_total' => 'decimal:4',
        'approved_at' => 'datetime',
        'is_converted' => 'boolean',
        'extra_attributes' => 'array',
    ];

    protected static function booted(): void
    {
        parent::booted();

        static::creating(function ($model) {
            if (! $model->code) {
                $model->code = 'REQ-'.date('Ymd').'-'.str_pad((string) (static::whereDate('created_at', today())->count() + 1), 5, '0', STR_PAD_LEFT);
            }
        });
    }

    // Relationships
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseRequisitionItem::class, 'requisition_id');
    }

    public function purchase(): BelongsTo
    {
        return $this->belongsTo(Purchase::class, 'converted_to_po_id');
    }

    public function quotations(): HasMany
    {
        return $this->hasMany(SupplierQuotation::class, 'requisition_id');
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
    public function scopePendingApproval(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('status', 'pending_approval');
    }

    public function scopeApproved(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('status', 'approved');
    }

    public function scopeNotConverted(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('is_converted', false);
    }

    public function scopeUrgent(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
    {
        return $query->whereIn('priority', ['high', 'urgent']);
    }

    // Business Logic
    public function approve(int $approvedBy): void
    {
        $this->update([
            'status' => 'approved',
            'approved_by' => $approvedBy,
            'approved_at' => now(),
        ]);
    }

    public function reject(int $rejectedBy, string $reason): void
    {
        $this->update([
            'status' => 'rejected',
            'approved_by' => $rejectedBy,
            'approved_at' => now(),
            'rejection_reason' => $reason,
        ]);
    }

    public function convertToPO(int $purchaseId): void
    {
        $this->update([
            'is_converted' => true,
            'converted_to_po_id' => $purchaseId,
        ]);
    }

    public function canBeApproved(): bool
    {
        return in_array($this->status, ['draft', 'pending_approval']);
    }

    public function canBeConverted(): bool
    {
        return $this->status === 'approved' && ! $this->is_converted;
    }
}
