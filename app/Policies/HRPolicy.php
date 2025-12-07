<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;
use App\Policies\Concerns\ChecksPermissions;

class HRPolicy
{
    use ChecksPermissions;

    public function employeesView(User $user): bool
    {
        return $this->has($user, 'hrm.employees.view');
    }

    public function employeesAssign(User $user): bool
    {
        return $this->has($user, 'hrm.employees.assign');
    }

    public function employeesUnassign(User $user): bool
    {
        return $this->has($user, 'hrm.employees.unassign');
    }

    public function attendanceView(User $user): bool
    {
        return $this->has($user, 'hrm.attendance.view');
    }

    public function attendanceCreate(User $user): bool
    {
        return $this->has($user, 'hrm.attendance.create');
    }

    public function attendanceUpdate(User $user): bool
    {
        return $this->has($user, 'hrm.attendance.update');
    }

    public function attendanceApprove(User $user): bool
    {
        return $this->has($user, 'hrm.attendance.approve');
    }

    public function attendanceDeactivate(User $user): bool
    {
        return $this->has($user, 'hrm.attendance.deactivate');
    }

    public function payrollView(User $user): bool
    {
        return $this->has($user, 'hrm.payroll.view');
    }

    public function payrollRun(User $user): bool
    {
        return $this->has($user, 'hrm.payroll.run');
    }

    public function payrollApprove(User $user): bool
    {
        return $this->has($user, 'hrm.payroll.approve');
    }

    public function payrollPay(User $user): bool
    {
        return $this->has($user, 'hrm.payroll.pay');
    }
}
