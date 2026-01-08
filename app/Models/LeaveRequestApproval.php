<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Leave Request Approval Model
 * 
 * Tracks multi-level approval workflow for leave requests.
 */
class LeaveRequestApproval extends Model
{
    use HasFactory;

    protected $fillable = [
        'leave_request_id',
        'approval_level',
        'approver_id',
        'status',
        'comments',
        'responded_at',
    ];

    protected $casts = [
        'approval_level' => 'integer',
        'responded_at' => 'datetime',
    ];

    // Status constants
    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';

    // Relationships

    public function leaveRequest(): BelongsTo
    {
        return $this->belongsTo(LeaveRequest::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approver_id');
    }

    // Status helper methods

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isApproved(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }

    public function isRejected(): bool
    {
        return $this->status === self::STATUS_REJECTED;
    }

    public function isResponded(): bool
    {
        return !is_null($this->responded_at);
    }

    // Scopes

    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeApproved($query)
    {
        return $query->where('status', self::STATUS_APPROVED);
    }

    public function scopeRejected($query)
    {
        return $query->where('status', self::STATUS_REJECTED);
    }

    public function scopeByApprover($query, int $approverId)
    {
        return $query->where('approver_id', $approverId);
    }

    public function scopeByLevel($query, int $level)
    {
        return $query->where('approval_level', $level);
    }

    public function scopeForLeaveRequest($query, int $leaveRequestId)
    {
        return $query->where('leave_request_id', $leaveRequestId);
    }
}
