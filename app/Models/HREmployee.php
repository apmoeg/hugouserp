<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class HREmployee extends BaseModel
{
    protected $table = 'hr_employees';

    protected ?string $moduleKey = 'hr';

    protected $fillable = ['branch_id', 'user_id', 'code', 'name', 'position', 'salary', 'is_active', 'extra_attributes'];

    protected $casts = ['salary' => 'decimal:2', 'is_active' => 'bool'];

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class, 'employee_id');
    }

    public function payrolls(): HasMany
    {
        return $this->hasMany(Payroll::class, 'employee_id');
    }

    public function employeeShifts(): HasMany
    {
        return $this->hasMany(EmployeeShift::class, 'employee_id');
    }

    public function shifts()
    {
        return $this->belongsToMany(Shift::class, 'employee_shifts', 'employee_id', 'shift_id')
            ->withPivot(['start_date', 'end_date', 'is_active'])
            ->withTimestamps();
    }

    public function currentShift()
    {
        return $this->employeeShifts()
            ->where('is_active', true)
            ->where('start_date', '<=', now()->toDateString())
            ->where(function ($q) {
                $q->whereNull('end_date')
                    ->orWhere('end_date', '>=', now()->toDateString());
            })
            ->with('shift')
            ->first();
    }
}
