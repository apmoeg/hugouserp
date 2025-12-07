<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;
use App\Policies\Concerns\ChecksPermissions;

class POSPolicy
{
    use ChecksPermissions;

    public function checkout(User $user): bool
    {
        return $this->has($user, 'pos.checkout');
    }

    public function hold(User $user): bool
    {
        return $this->has($user, 'pos.hold');
    }

    public function resume(User $user): bool
    {
        return $this->has($user, 'pos.resume');
    }

    public function closeDay(User $user): bool
    {
        return $this->has($user, 'pos.close');
    }

    public function reprint(User $user): bool
    {
        return $this->has($user, 'pos.reprint');
    }

    public function xReport(User $user): bool
    {
        return $this->has($user, 'pos.xReport');
    }

    public function zReport(User $user): bool
    {
        return $this->has($user, 'pos.zReport');
    }
}
