<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Leave Balance Model
 * 
 * Tracks employee leave balances by type and year with accrual tracking.
 */
class LeaveBalance extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'leave_type_id',
        'year',
        'opening_balance',
        'annual_quota',
        'accrued',
        'used',
        'pending',
        'available',
        'carry_forward_from_previous',
        'carry_forward_expiry_date',
        'notes',
    ];

    protected $casts = [
        'year' => 'integer',
        'opening_balance' => 'decimal:2',
        'annual_quota' => 'decimal:2',
        'accrued' => 'decimal:2',
        'used' => 'decimal:2',
        'pending' => 'decimal:2',
        'available' => 'decimal:2',
        'carry_forward_from_previous' => 'decimal:2',
        'carry_forward_expiry_date' => 'date',
    ];

    // Relationships

    public function employee(): BelongsTo
    {
        return $this->belongsTo(HrEmployee::class);
    }

    public function leaveType(): BelongsTo
    {
        return $this->belongsTo(LeaveType::class);
    }

    // Helper methods

    /**
     * Calculate and update available balance
     */
    public function calculateAvailable(): float
    {
        return max(0, $this->opening_balance + $this->annual_quota + $this->accrued + $this->carry_forward_from_previous - $this->used - $this->pending);
    }

    /**
     * Update available balance
     */
    public function updateAvailable(): self
    {
        $this->available = $this->calculateAvailable();
        $this->save();
        return $this;
    }

    /**
     * Check if sufficient balance exists
     */
    public function hasSufficientBalance(float $requestedDays): bool
    {
        return $this->available >= $requestedDays;
    }

    /**
     * Check if carry forward has expired
     */
    public function isCarryForwardExpired(): bool
    {
        return $this->carry_forward_from_previous > 0 
            && !is_null($this->carry_forward_expiry_date) 
            && now()->isAfter($this->carry_forward_expiry_date);
    }

    /**
     * Get total balance (available + pending)
     */
    public function getTotalBalance(): float
    {
        return $this->available + $this->pending;
    }

    // Scopes

    public function scopeForYear($query, int $year)
    {
        return $query->where('year', $year);
    }

    public function scopeCurrentYear($query)
    {
        return $query->where('year', now()->year);
    }

    public function scopeByEmployee($query, int $employeeId)
    {
        return $query->where('employee_id', $employeeId);
    }

    public function scopeByLeaveType($query, int $leaveTypeId)
    {
        return $query->where('leave_type_id', $leaveTypeId);
    }

    public function scopeWithAvailableBalance($query)
    {
        return $query->where('available', '>', 0);
    }

    public function scopeExpiredCarryForward($query)
    {
        return $query->where('carry_forward_from_previous', '>', 0)
            ->whereNotNull('carry_forward_expiry_date')
            ->where('carry_forward_expiry_date', '<', now());
    }
}
