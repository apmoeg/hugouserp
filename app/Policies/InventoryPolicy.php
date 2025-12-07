<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;
use App\Policies\Concerns\ChecksPermissions;

class InventoryPolicy
{
    use ChecksPermissions;

    public function view(User $user): bool
    {
        return $this->has($user, 'stock.view');
    }

    public function adjust(User $user): bool
    {
        return $this->has($user, 'stock.adjust');
    }

    public function transfer(User $user): bool
    {
        return $this->has($user, 'stock.transfer');
    }
}
