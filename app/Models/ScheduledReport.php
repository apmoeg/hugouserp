<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ScheduledReport extends Model
{
    use HasFactory;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'report_template_id',
        'route_name',
        'cron_expression',
        'filters',
        'recipient_email',
        'last_status',
        'last_run_at',
        'last_error',
        'runs_count',
        'failures_count',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'filters' => 'array',
        'last_run_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function template()
    {
        return $this->belongsTo(ReportTemplate::class, 'report_template_id');
    }
}
