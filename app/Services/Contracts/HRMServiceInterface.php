<?php

declare(strict_types=1);

namespace App\Services\Contracts;

use App\Models\Attendance;
use Illuminate\Database\Eloquent\Collection;

interface HRMServiceInterface
{
    /** @return Collection<int, \App\Models\HREmployee> */
    public function employees(bool $activeOnly = true);

    public function logAttendance(int $employeeId, string $type, string $at): Attendance;

    public function approveAttendance(int $attendanceId): Attendance;

    public function runPayroll(string $period): int;
}
