<?php

namespace App\Models;

use App\Models\Traits\HasBranch;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Supplier Performance Metric Model
 * 
 * Tracks supplier KPIs including on-time delivery, quality, and return rates.
 * Used for supplier scorecards and performance management.
 */
class SupplierPerformanceMetric extends Model
{
    use HasFactory, HasBranch;

    protected $fillable = [
        'supplier_id',
        'branch_id',
        'period',
        'total_orders',
        'on_time_deliveries',
        'late_deliveries',
        'on_time_delivery_rate',
        'total_ordered_qty',
        'total_received_qty',
        'total_rejected_qty',
        'quality_acceptance_rate',
        'total_returns',
        'return_rate',
        'total_purchase_value',
        'average_order_value',
        'average_lead_time_days',
        'performance_score',
        'notes',
        'calculated_at',
    ];

    protected $casts = [
        'total_orders' => 'integer',
        'on_time_deliveries' => 'integer',
        'late_deliveries' => 'integer',
        'on_time_delivery_rate' => 'decimal:2',
        'total_ordered_qty' => 'decimal:3',
        'total_received_qty' => 'decimal:3',
        'total_rejected_qty' => 'decimal:3',
        'quality_acceptance_rate' => 'decimal:2',
        'total_returns' => 'integer',
        'return_rate' => 'decimal:2',
        'total_purchase_value' => 'decimal:2',
        'average_order_value' => 'decimal:2',
        'average_lead_time_days' => 'decimal:2',
        'performance_score' => 'decimal:2',
        'calculated_at' => 'datetime',
    ];

    // Relationships

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    // Helper methods

    /**
     * Calculate and update performance score based on metrics
     */
    public function calculatePerformanceScore(): float
    {
        $score = 100.0;

        // On-time delivery (40% weight)
        $score -= (100 - $this->on_time_delivery_rate) * 0.4;

        // Quality acceptance rate (40% weight)
        $score -= (100 - $this->quality_acceptance_rate) * 0.4;

        // Return rate penalty (20% weight)
        $score -= $this->return_rate * 0.2;

        return max(0, min(100, round($score, 2)));
    }

    /**
     * Get performance rating based on score
     */
    public function getPerformanceRating(): string
    {
        if ($this->performance_score >= 90) {
            return 'Excellent';
        } elseif ($this->performance_score >= 75) {
            return 'Good';
        } elseif ($this->performance_score >= 60) {
            return 'Satisfactory';
        } elseif ($this->performance_score >= 40) {
            return 'Poor';
        } else {
            return 'Very Poor';
        }
    }

    /**
     * Check if supplier meets performance threshold
     */
    public function meetsThreshold(float $threshold = 70.0): bool
    {
        return $this->performance_score >= $threshold;
    }

    // Scopes

    public function scopeForPeriod($query, string $period)
    {
        return $query->where('period', $period);
    }

    public function scopeBySupplier($query, int $supplierId)
    {
        return $query->where('supplier_id', $supplierId);
    }

    public function scopeExcellentPerformers($query)
    {
        return $query->where('performance_score', '>=', 90);
    }

    public function scopePoorPerformers($query)
    {
        return $query->where('performance_score', '<', 60);
    }

    public function scopeHighReturnRate($query, float $threshold = 5.0)
    {
        return $query->where('return_rate', '>', $threshold);
    }

    public function scopeLowQuality($query, float $threshold = 95.0)
    {
        return $query->where('quality_acceptance_rate', '<', $threshold);
    }
}
