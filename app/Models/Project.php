<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Carbon\Carbon;

class Project extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'code',
        'name',
        'description',
        'client_id',
        'branch_id',
        'manager_id',
        'status',
        'priority',
        'start_date',
        'end_date',
        'budget',
        'currency_id',
        'progress',
        'notes',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'budget' => 'decimal:2',
        'progress' => 'integer',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($project) {
            if (empty($project->code)) {
                $project->code = 'PRJ-' . date('Ymd') . '-' . str_pad(
                    static::whereDate('created_at', Carbon::today())->count() + 1,
                    6,
                    '0',
                    STR_PAD_LEFT
                );
            }
        });
    }

    // Relationships
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'client_id');
    }

    public function manager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'manager_id');
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(ProjectTask::class);
    }

    public function milestones(): HasMany
    {
        return $this->hasMany(ProjectMilestone::class);
    }

    public function timeLogs(): HasMany
    {
        return $this->hasMany(ProjectTimeLog::class);
    }

    public function expenses(): HasMany
    {
        return $this->hasMany(ProjectExpense::class);
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
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeOverBudget($query)
    {
        return $query->whereRaw('COALESCE((SELECT SUM(hours * hourly_rate) FROM project_time_logs WHERE project_id = projects.id), 0) + 
                                 (SELECT COALESCE(SUM(amount), 0) FROM project_expenses WHERE project_id = projects.id AND status = "approved") > budget');
    }

    public function scopeOverdue($query)
    {
        return $query->where('end_date', '<', Carbon::now())
                    ->whereNotIn('status', ['completed', 'cancelled']);
    }

    // Business Methods
    public function getProgress(): int
    {
        $totalTasks = $this->tasks()->count();
        
        if ($totalTasks === 0) {
            return 0;
        }

        $completedTasks = $this->tasks()->where('status', 'completed')->count();
        
        return (int) round(($completedTasks / $totalTasks) * 100);
    }

    public function getTotalBudget(): float
    {
        return (float) $this->budget;
    }

    public function getTotalActualCost(): float
    {
        $timeLogsCost = $this->timeLogs()
            ->selectRaw('SUM(hours * hourly_rate) as total')
            ->value('total') ?? 0;

        $expensesCost = $this->expenses()
            ->where('status', 'approved')
            ->sum('amount') ?? 0;

        return (float) ($timeLogsCost + $expensesCost);
    }

    public function getBudgetVariance(): float
    {
        return $this->getTotalBudget() - $this->getTotalActualCost();
    }

    public function isOverBudget(): bool
    {
        return $this->getTotalActualCost() > $this->getTotalBudget();
    }

    public function isOverdue(): bool
    {
        if (in_array($this->status, ['completed', 'cancelled'])) {
            return false;
        }

        return $this->end_date && $this->end_date->isPast();
    }

    public function getTeamMembers(): array
    {
        // Get unique users from tasks, time logs
        $taskAssignees = $this->tasks()->pluck('assigned_to')->unique()->filter();
        $timeLoggers = $this->timeLogs()->pluck('employee_id')->unique()->filter();
        
        return $taskAssignees->merge($timeLoggers)->unique()->values()->toArray();
    }

    public function getRemainingDays(): ?int
    {
        if (!$this->end_date || in_array($this->status, ['completed', 'cancelled'])) {
            return null;
        }

        return (int) Carbon::now()->diffInDays($this->end_date, false);
    }
}
