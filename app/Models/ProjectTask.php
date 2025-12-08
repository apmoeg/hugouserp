<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProjectTask extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'project_id',
        'parent_id',
        'name',
        'description',
        'assigned_to',
        'status',
        'priority',
        'start_date',
        'due_date',
        'estimated_hours',
        'progress',
        'order',
        'notes',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'start_date' => 'date',
        'due_date' => 'date',
        'estimated_hours' => 'decimal:2',
        'progress' => 'integer',
        'order' => 'integer',
    ];

    // Relationships
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(ProjectTask::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(ProjectTask::class, 'parent_id');
    }

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function timeLogs(): HasMany
    {
        return $this->hasMany(ProjectTimeLog::class, 'task_id');
    }

    public function dependencies(): HasMany
    {
        return $this->hasMany(TaskDependency::class, 'task_id');
    }

    public function dependents(): HasMany
    {
        return $this->hasMany(TaskDependency::class, 'depends_on_task_id');
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
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeInProgress($query)
    {
        return $query->where('status', 'in_progress');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeOverdue($query)
    {
        return $query->where('due_date', '<', now())
                    ->whereNotIn('status', ['completed', 'cancelled']);
    }

    public function scopeHighPriority($query)
    {
        return $query->whereIn('priority', ['high', 'critical']);
    }

    // Business Methods
    public function getProgress(): int
    {
        return (int) $this->progress;
    }

    public function canBeStarted(): bool
    {
        // Check if all dependencies are completed
        $incompleteDependencies = $this->dependencies()
            ->join('project_tasks', 'project_tasks.id', '=', 'task_dependencies.depends_on_task_id')
            ->where('project_tasks.status', '!=', 'completed')
            ->count();

        return $incompleteDependencies === 0;
    }

    public function isOverdue(): bool
    {
        if (in_array($this->status, ['completed', 'cancelled'])) {
            return false;
        }

        return $this->due_date && $this->due_date->isPast();
    }

    public function getTimeSpent(): float
    {
        return (float) $this->timeLogs()->sum('hours');
    }

    public function getTimeRemaining(): float
    {
        $spent = $this->getTimeSpent();
        $estimated = (float) $this->estimated_hours;

        return max(0, $estimated - $spent);
    }

    public function isBlocking(): bool
    {
        // Check if this task is blocking any other tasks
        return $this->dependents()
            ->join('project_tasks', 'project_tasks.id', '=', 'task_dependencies.task_id')
            ->where('project_tasks.status', '!=', 'completed')
            ->exists();
    }

    public function getDependentTasks()
    {
        return ProjectTask::whereIn('id', function ($query) {
            $query->select('task_id')
                ->from('task_dependencies')
                ->where('depends_on_task_id', $this->id);
        })->get();
    }
}
