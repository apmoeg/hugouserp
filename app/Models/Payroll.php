<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payroll extends BaseModel
{
    protected ?string $moduleKey = 'hr';

    protected $fillable = ['employee_id', 'period', 'basic', 'allowances', 'deductions', 'net', 'status', 'paid_at', 'extra_attributes'];

    protected $casts = ['basic' => 'decimal:2', 'allowances' => 'decimal:2', 'deductions' => 'decimal:2', 'net' => 'decimal:2', 'paid_at' => 'datetime'];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(HREmployee::class, 'employee_id');
    }
}
