<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;
use App\Policies\Concerns\ChecksPermissions;

class VehiclePolicy
{
    use ChecksPermissions;

    public function vehiclesView(User $user): bool
    {
        return $this->has($user, 'motorcycle.vehicles.view');
    }

    public function vehiclesCreate(User $user): bool
    {
        return $this->has($user, 'motorcycle.vehicles.create');
    }

    public function vehiclesUpdate(User $user): bool
    {
        return $this->has($user, 'motorcycle.vehicles.update');
    }

    public function vehiclesDelete(User $user): bool
    {
        return $this->has($user, 'motorcycle.vehicles.delete');
    }

    public function contractsView(User $user): bool
    {
        return $this->has($user, 'motorcycle.contracts.view');
    }

    public function contractsCreate(User $user): bool
    {
        return $this->has($user, 'motorcycle.contracts.create');
    }

    public function contractsUpdate(User $user): bool
    {
        return $this->has($user, 'motorcycle.contracts.update');
    }

    public function contractsDeliver(User $user): bool
    {
        return $this->has($user, 'motorcycle.contracts.deliver');
    }

    public function warrantiesView(User $user): bool
    {
        return $this->has($user, 'motorcycle.warranties.view');
    }

    public function warrantiesCreate(User $user): bool
    {
        return $this->has($user, 'motorcycle.warranties.create');
    }

    public function warrantiesUpdate(User $user): bool
    {
        return $this->has($user, 'motorcycle.warranties.update');
    }
}
