<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectTimeLog extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'project_id',
        'task_id',
        'user_id',
        'employee_id',
        'log_date',
        'date',
        'hours',
        'hourly_rate',
        'billable',
        'is_billable',
        'description',
        'notes',
        'metadata',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'log_date' => 'date',
        'date' => 'date',
        'hours' => 'decimal:2',
        'hourly_rate' => 'decimal:2',
        'billable' => 'boolean',
        'is_billable' => 'boolean',
        'metadata' => 'array',
    ];

    // Relationships
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function task(): BelongsTo
    {
        return $this->belongsTo(ProjectTask::class, 'task_id');
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'employee_id');
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
    public function scopeBillable($query)
    {
        return $query->where('is_billable', true);
    }

    public function scopeNonBillable($query)
    {
        return $query->where('is_billable', false);
    }

    public function scopeForDate($query, $date)
    {
        return $query->whereDate('date', $date);
    }

    public function scopeForEmployee($query, $employeeId)
    {
        return $query->where('employee_id', $employeeId);
    }

    // Business Methods
    public function getCost(): float
    {
        return (float) ($this->hours * $this->hourly_rate);
    }

    public function isBillable(): bool
    {
        return (bool) $this->is_billable;
    }

    // Helper methods for backwards compatibility
    public function getLogDateAttribute($value)
    {
        return $value ?? ($this->attributes['date'] ?? null);
    }

    public function getUserIdAttribute($value)
    {
        return $value ?? ($this->attributes['employee_id'] ?? null);
    }
}
