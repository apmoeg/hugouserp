<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Sale;
use App\Models\User;
use App\Policies\Concerns\ChecksPermissions;

class SalePolicy
{
    use ChecksPermissions;

    public function viewAny(User $user): bool
    {
        return $this->has($user, 'sales.view');
    }

    public function view(User $user, Sale $sale): bool
    {
        return $this->has($user, 'sales.view');
    }

    public function create(User $user): bool
    {
        return $this->has($user, 'sales.create');
    }

    public function update(User $user, Sale $sale): bool
    {
        return $this->has($user, 'sales.update');
    }

    public function delete(User $user, Sale $sale): bool
    {
        return $this->has($user, 'sales.delete');
    }

    public function return(User $user, Sale $sale): bool
    {
        return $this->has($user, 'sales.return');
    }

    public function void(User $user, Sale $sale): bool
    {
        return $this->has($user, 'sales.void');
    }

    public function print(User $user, Sale $sale): bool
    {
        return $this->has($user, 'sales.print');
    }
}
