<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductionOrder extends BaseModel
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'branch_id',
        'order_number',
        'bom_id',
        'product_id',
        'warehouse_id',
        'quantity_planned',
        'quantity_produced',
        'quantity_scrapped',
        'status',
        'priority',
        'planned_start_date',
        'planned_end_date',
        'actual_start_date',
        'actual_end_date',
        'created_by',
        'approved_by',
        'notes',
        'estimated_cost',
        'actual_cost',
        'sale_id',
        'metadata',
    ];

    protected $casts = [
        'quantity_planned' => 'decimal:2',
        'quantity_produced' => 'decimal:2',
        'quantity_scrapped' => 'decimal:2',
        'estimated_cost' => 'decimal:2',
        'actual_cost' => 'decimal:2',
        'planned_start_date' => 'date',
        'planned_end_date' => 'date',
        'actual_start_date' => 'datetime',
        'actual_end_date' => 'datetime',
        'metadata' => 'array',
    ];

    /**
     * Get the branch that owns the production order.
     */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * Get the BOM used.
     */
    public function bom(): BelongsTo
    {
        return $this->belongsTo(BillOfMaterial::class, 'bom_id');
    }

    /**
     * Get the product being manufactured.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the warehouse for finished goods.
     */
    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    /**
     * Get the creator.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the approver.
     */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Get the linked sale (if make-to-order).
     */
    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    /**
     * Get the order items (materials).
     */
    public function items(): HasMany
    {
        return $this->hasMany(ProductionOrderItem::class);
    }

    /**
     * Get the order operations.
     */
    public function operations(): HasMany
    {
        return $this->hasMany(ProductionOrderOperation::class);
    }

    /**
     * Get manufacturing transactions.
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(ManufacturingTransaction::class);
    }

    /**
     * Calculate completion percentage.
     */
    public function getCompletionPercentageAttribute(): float
    {
        if ($this->quantity_planned == 0) {
            return 0.0;
        }

        return ($this->quantity_produced / $this->quantity_planned) * 100;
    }

    /**
     * Calculate remaining quantity to produce.
     */
    public function getRemainingQuantityAttribute(): float
    {
        return $this->quantity_planned - $this->quantity_produced - $this->quantity_scrapped;
    }

    /**
     * Scope: By status.
     */
    public function scopeStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope: In progress.
     */
    public function scopeInProgress($query)
    {
        return $query->whereIn('status', ['released', 'in_progress']);
    }

    /**
     * Scope: Completed.
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    /**
     * Scope: By priority.
     */
    public function scopePriority($query, string $priority)
    {
        return $query->where('priority', $priority);
    }

    /**
     * Generate next production order number.
     */
    public static function generateOrderNumber(int $branchId): string
    {
        $prefix = 'PRO';
        $date = now()->format('Ym');
        
        $lastOrder = static::where('branch_id', $branchId)
            ->where('order_number', 'like', "{$prefix}-{$date}-%")
            ->orderByDesc('id')
            ->first();

        if ($lastOrder) {
            $lastNumber = (int) substr($lastOrder->order_number, -4);
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        return sprintf('%s-%s-%04d', $prefix, $date, $newNumber);
    }

    /**
     * Start production.
     */
    public function start(): void
    {
        $this->update([
            'status' => 'in_progress',
            'actual_start_date' => now(),
        ]);
    }

    /**
     * Complete production.
     */
    public function complete(): void
    {
        $this->update([
            'status' => 'completed',
            'actual_end_date' => now(),
        ]);
    }
}
