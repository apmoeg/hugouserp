<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Leave Accrual Rule Model
 * 
 * Defines how leave accrues for employees (monthly, quarterly, etc.).
 */
class LeaveAccrualRule extends Model
{
    use HasFactory;

    protected $fillable = [
        'leave_type_id',
        'accrual_frequency',
        'accrual_amount',
        'prorate_on_joining',
        'prorate_on_leaving',
        'waiting_period_months',
        'effective_from',
        'effective_to',
        'is_active',
        'created_by',
    ];

    protected $casts = [
        'accrual_amount' => 'decimal:2',
        'prorate_on_joining' => 'boolean',
        'prorate_on_leaving' => 'boolean',
        'waiting_period_months' => 'integer',
        'effective_from' => 'date',
        'effective_to' => 'date',
        'is_active' => 'boolean',
    ];

    // Frequency constants
    public const FREQUENCY_MONTHLY = 'monthly';
    public const FREQUENCY_QUARTERLY = 'quarterly';
    public const FREQUENCY_SEMI_ANNUALLY = 'semi_annually';
    public const FREQUENCY_ANNUALLY = 'annually';
    public const FREQUENCY_PER_PAY_PERIOD = 'per_pay_period';

    // Relationships

    public function leaveType(): BelongsTo
    {
        return $this->belongsTo(LeaveType::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // Helper methods

    public function isMonthly(): bool
    {
        return $this->accrual_frequency === self::FREQUENCY_MONTHLY;
    }

    public function isQuarterly(): bool
    {
        return $this->accrual_frequency === self::FREQUENCY_QUARTERLY;
    }

    public function isSemiAnnually(): bool
    {
        return $this->accrual_frequency === self::FREQUENCY_SEMI_ANNUALLY;
    }

    public function isAnnually(): bool
    {
        return $this->accrual_frequency === self::FREQUENCY_ANNUALLY;
    }

    public function isPerPayPeriod(): bool
    {
        return $this->accrual_frequency === self::FREQUENCY_PER_PAY_PERIOD;
    }

    public function isEffective(?string $date = null): bool
    {
        $checkDate = $date ? now()->parse($date) : now();

        if (!is_null($this->effective_from) && $checkDate->isBefore($this->effective_from)) {
            return false;
        }

        if (!is_null($this->effective_to) && $checkDate->isAfter($this->effective_to)) {
            return false;
        }

        return $this->is_active;
    }

    /**
     * Get accrual periods per year based on frequency
     */
    public function getPeriodsPerYear(): int
    {
        return match ($this->accrual_frequency) {
            self::FREQUENCY_MONTHLY => 12,
            self::FREQUENCY_QUARTERLY => 4,
            self::FREQUENCY_SEMI_ANNUALLY => 2,
            self::FREQUENCY_ANNUALLY => 1,
            self::FREQUENCY_PER_PAY_PERIOD => 12, // Assume monthly by default
            default => 12,
        };
    }

    /**
     * Calculate prorated accrual for joining employee
     */
    public function calculateProratedAccrual(string $joinDate, string $periodEndDate): float
    {
        if (!$this->prorate_on_joining) {
            return $this->accrual_amount;
        }

        $join = now()->parse($joinDate);
        $periodEnd = now()->parse($periodEndDate);
        $totalDays = $periodEnd->diffInDays($periodEnd->copy()->startOfMonth());
        $workedDays = $periodEnd->diffInDays($join);

        if ($totalDays == 0) {
            return 0;
        }

        return round(($workedDays / $totalDays) * $this->accrual_amount, 2);
    }

    // Scopes

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForLeaveType($query, int $leaveTypeId)
    {
        return $query->where('leave_type_id', $leaveTypeId);
    }

    public function scopeEffectiveOn($query, ?string $date = null)
    {
        $checkDate = $date ?? now()->toDateString();
        
        return $query->where('is_active', true)
            ->where(function ($q) use ($checkDate) {
                $q->whereNull('effective_from')
                    ->orWhere('effective_from', '<=', $checkDate);
            })
            ->where(function ($q) use ($checkDate) {
                $q->whereNull('effective_to')
                    ->orWhere('effective_to', '>=', $checkDate);
            });
    }

    public function scopeByFrequency($query, string $frequency)
    {
        return $query->where('accrual_frequency', $frequency);
    }
}
