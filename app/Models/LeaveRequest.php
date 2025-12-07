<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeaveRequest extends BaseModel
{
    protected ?string $moduleKey = 'hr';

    protected $fillable = ['employee_id', 'from_date', 'to_date', 'type', 'status', 'reason', 'approved_by', 'approved_at', 'extra_attributes'];

    protected $casts = ['from_date' => 'date', 'to_date' => 'date', 'approved_at' => 'datetime'];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(HREmployee::class, 'employee_id');
    }
}
