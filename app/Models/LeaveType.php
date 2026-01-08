<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Leave Type Model
 * 
 * Defines different types of leave (annual, sick, casual, etc.) with configuration.
 */
class LeaveType extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'code',
        'description',
        'unit',
        'default_annual_quota',
        'is_paid',
        'requires_approval',
        'requires_document',
        'max_consecutive_days',
        'min_notice_days',
        'max_carry_forward',
        'carry_forward_expires',
        'carry_forward_expiry_months',
        'is_active',
        'sort_order',
        'color',
        'created_by',
    ];

    protected $casts = [
        'default_annual_quota' => 'decimal:2',
        'is_paid' => 'boolean',
        'requires_approval' => 'boolean',
        'requires_document' => 'boolean',
        'max_consecutive_days' => 'integer',
        'min_notice_days' => 'integer',
        'max_carry_forward' => 'integer',
        'carry_forward_expires' => 'boolean',
        'carry_forward_expiry_months' => 'integer',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    // Unit constants
    public const UNIT_DAYS = 'days';
    public const UNIT_HOURS = 'hours';

    // Relationships

    public function balances(): HasMany
    {
        return $this->hasMany(LeaveBalance::class);
    }

    public function accrualRules(): HasMany
    {
        return $this->hasMany(LeaveAccrualRule::class);
    }

    public function adjustments(): HasMany
    {
        return $this->hasMany(LeaveAdjustment::class);
    }

    public function encashments(): HasMany
    {
        return $this->hasMany(LeaveEncashment::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // Helper methods

    public function isDaysUnit(): bool
    {
        return $this->unit === self::UNIT_DAYS;
    }

    public function isHoursUnit(): bool
    {
        return $this->unit === self::UNIT_HOURS;
    }

    public function allowsCarryForward(): bool
    {
        return !is_null($this->max_carry_forward) && $this->max_carry_forward > 0;
    }

    public function hasExpiry(): bool
    {
        return $this->carry_forward_expires && !is_null($this->carry_forward_expiry_months);
    }

    // Scopes

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeInactive($query)
    {
        return $query->where('is_active', false);
    }

    public function scopePaid($query)
    {
        return $query->where('is_paid', true);
    }

    public function scopeUnpaid($query)
    {
        return $query->where('is_paid', false);
    }

    public function scopeRequiringApproval($query)
    {
        return $query->where('requires_approval', true);
    }

    public function scopeRequiringDocument($query)
    {
        return $query->where('requires_document', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }
}
