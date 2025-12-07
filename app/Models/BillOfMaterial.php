<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class BillOfMaterial extends BaseModel
{
    use HasFactory, SoftDeletes;

    protected $table = 'bills_of_materials';

    protected $fillable = [
        'branch_id',
        'product_id',
        'bom_number',
        'name',
        'name_ar',
        'description',
        'quantity',
        'status',
        'scrap_percentage',
        'is_multi_level',
        'metadata',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'scrap_percentage' => 'decimal:2',
        'is_multi_level' => 'boolean',
        'metadata' => 'array',
    ];

    /**
     * Get the branch that owns the BOM.
     */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * Get the finished product.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the BOM items (components/materials).
     */
    public function items(): HasMany
    {
        return $this->hasMany(BomItem::class, 'bom_id');
    }

    /**
     * Get the BOM operations.
     */
    public function operations(): HasMany
    {
        return $this->hasMany(BomOperation::class, 'bom_id');
    }

    /**
     * Get production orders using this BOM.
     */
    public function productionOrders(): HasMany
    {
        return $this->hasMany(ProductionOrder::class, 'bom_id');
    }

    /**
     * Calculate total material cost for this BOM.
     */
    public function calculateMaterialCost(): float
    {
        $cost = 0.0;

        foreach ($this->items as $item) {
            $productCost = $item->product->cost ?? 0.0;
            $itemQuantity = (float) $item->quantity;
            $scrapFactor = 1 + ((float) $item->scrap_percentage / 100);
            
            $cost += $productCost * $itemQuantity * $scrapFactor;
        }

        // Apply BOM-level scrap
        $bomScrapFactor = 1 + ((float) $this->scrap_percentage / 100);
        
        return $cost * $bomScrapFactor;
    }

    /**
     * Calculate total labor cost for this BOM.
     */
    public function calculateLaborCost(): float
    {
        return $this->operations->sum(function ($operation) {
            $durationHours = (float) $operation->duration_minutes / 60;
            $costPerHour = (float) $operation->workCenter->cost_per_hour;
            return $durationHours * $costPerHour + (float) $operation->labor_cost;
        });
    }

    /**
     * Calculate total production cost.
     */
    public function calculateTotalCost(): float
    {
        return $this->calculateMaterialCost() + $this->calculateLaborCost();
    }

    /**
     * Scope: Active BOMs only.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope: Draft BOMs.
     */
    public function scopeDraft($query)
    {
        return $query->where('status', 'draft');
    }

    /**
     * Generate next BOM number.
     */
    public static function generateBomNumber(int $branchId): string
    {
        $prefix = 'BOM';
        $date = now()->format('Ym');
        
        $lastBom = static::where('branch_id', $branchId)
            ->where('bom_number', 'like', "{$prefix}-{$date}-%")
            ->orderByDesc('id')
            ->first();

        if ($lastBom) {
            $lastNumber = (int) substr($lastBom->bom_number, -4);
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        return sprintf('%s-%s-%04d', $prefix, $date, $newNumber);
    }
}
