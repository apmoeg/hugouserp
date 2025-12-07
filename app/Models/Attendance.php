<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Attendance extends BaseModel
{
    protected ?string $moduleKey = 'hr';

    protected $fillable = ['branch_id', 'employee_id', 'date', 'check_in', 'check_out', 'status', 'approved_by', 'approved_at', 'extra_attributes'];

    protected $casts = ['date' => 'date', 'check_in' => 'datetime', 'check_out' => 'datetime', 'approved_at' => 'datetime'];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(HREmployee::class, 'employee_id');
    }
}
