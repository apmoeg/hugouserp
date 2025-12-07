<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Purchase;
use App\Models\User;
use App\Policies\Concerns\ChecksPermissions;

class PurchasePolicy
{
    use ChecksPermissions;

    public function viewAny(User $user): bool
    {
        return $this->has($user, 'purchases.view');
    }

    public function view(User $user, Purchase $purchase): bool
    {
        return $this->has($user, 'purchases.view');
    }

    public function create(User $user): bool
    {
        return $this->has($user, 'purchases.create');
    }

    public function update(User $user, Purchase $purchase): bool
    {
        return $this->has($user, 'purchases.update');
    }

    public function approve(User $user, Purchase $purchase): bool
    {
        return $this->has($user, 'purchases.approve');
    }

    public function receive(User $user, Purchase $purchase): bool
    {
        return $this->has($user, 'purchases.receive');
    }

    public function pay(User $user, Purchase $purchase): bool
    {
        return $this->has($user, 'purchases.pay');
    }

    public function return(User $user, Purchase $purchase): bool
    {
        return $this->has($user, 'purchases.return');
    }

    public function cancel(User $user, Purchase $purchase): bool
    {
        return $this->has($user, 'purchases.cancel');
    }

    public function delete(User $user, Purchase $purchase): bool
    {
        return $this->has($user, 'purchases.delete');
    }
}
