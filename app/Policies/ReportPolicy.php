<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;
use App\Policies\Concerns\ChecksPermissions;

class ReportPolicy
{
    use ChecksPermissions;

    public function branch(User $user): bool
    {
        return $this->has($user, 'reports.branch');
    }

    public function finance(User $user): bool
    {
        return $this->has($user, 'reports.finance');
    }

    public function usage(User $user): bool
    {
        return $this->has($user, 'reports.usage');
    }
}
