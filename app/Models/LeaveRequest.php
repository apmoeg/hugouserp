<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class LeaveRequest extends BaseModel
{
    use SoftDeletes;

    protected ?string $moduleKey = 'hr';

    protected $fillable = [
        'employee_id',
        'leave_type',
        'start_date',
        'end_date',
        'days_count',
        'status',
        'reason',
        'rejection_reason',
        'attachment',
        'approved_by',
        'approved_at',
        'extra_attributes',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'days_count' => 'decimal:2',
        'approved_at' => 'datetime',
    ];

    // Aliases for backward compatibility
    public function getFromDateAttribute()
    {
        return $this->start_date;
    }

    public function getToDateAttribute()
    {
        return $this->end_date;
    }

    public function getTypeAttribute()
    {
        return $this->leave_type;
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(HREmployee::class, 'employee_id');
    }

    public function approvedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }
}
