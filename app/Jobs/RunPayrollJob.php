<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\HREmployee;
use App\Models\Payroll;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class RunPayrollJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public $timeout = 300;

    public function __construct(public string $period /* e.g. 2025-11 */) {}

    public function handle(): void
    {
        $employees = HREmployee::query()->where('is_active', true)->get();
        foreach ($employees as $emp) {
            $exists = Payroll::query()->where('employee_id', $emp->getKey())->where('period', $this->period)->exists();
            if ($exists) {
                continue;
            }

            $basic = (float) $emp->salary;
            $allowances = 0.0; // plug allowance rules
            $deductions = 0.0; // plug deduction rules
            $net = $basic + $allowances - $deductions;

            Payroll::query()->create([
                'employee_id' => $emp->getKey(),
                'period' => $this->period,
                'basic' => $basic,
                'allowances' => $allowances,
                'deductions' => $deductions,
                'net' => $net,
                'status' => 'pending',
            ]);
        }
    }

    public function tags(): array
    {
        return ['hrm', 'payroll', 'period:'.$this->period];
    }
}
