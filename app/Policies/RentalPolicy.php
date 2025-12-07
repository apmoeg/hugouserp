<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\RentalInvoice;
use App\Models\User;
use App\Policies\Concerns\ChecksPermissions;

class RentalPolicy
{
    use ChecksPermissions;

    public function propertiesView(User $user): bool
    {
        return $this->has($user, 'rental.properties.view');
    }

    public function propertiesCreate(User $user): bool
    {
        return $this->has($user, 'rental.properties.create');
    }

    public function propertiesUpdate(User $user): bool
    {
        return $this->has($user, 'rental.properties.update');
    }

    public function unitsView(User $user): bool
    {
        return $this->has($user, 'rental.units.view');
    }

    public function unitsCreate(User $user): bool
    {
        return $this->has($user, 'rental.units.create');
    }

    public function unitsUpdate(User $user): bool
    {
        return $this->has($user, 'rental.units.update');
    }

    public function unitsStatus(User $user): bool
    {
        return $this->has($user, 'rental.units.status');
    }

    public function tenantsView(User $user): bool
    {
        return $this->has($user, 'rental.tenants.view');
    }

    public function tenantsCreate(User $user): bool
    {
        return $this->has($user, 'rental.tenants.create');
    }

    public function tenantsUpdate(User $user): bool
    {
        return $this->has($user, 'rental.tenants.update');
    }

    public function tenantsArchive(User $user): bool
    {
        return $this->has($user, 'rental.tenants.archive');
    }

    public function contractsView(User $user): bool
    {
        return $this->has($user, 'rental.contracts.view');
    }

    public function contractsCreate(User $user): bool
    {
        return $this->has($user, 'rental.contracts.create');
    }

    public function contractsUpdate(User $user): bool
    {
        return $this->has($user, 'rental.contracts.update');
    }

    public function contractsRenew(User $user): bool
    {
        return $this->has($user, 'rental.contracts.renew');
    }

    public function contractsTerminate(User $user): bool
    {
        return $this->has($user, 'rental.contracts.terminate');
    }

    public function invoicesView(User $user): bool
    {
        return $this->has($user, 'rental.invoices.view');
    }

    public function invoicesRunRecurring(User $user): bool
    {
        return $this->has($user, 'rental.invoices.runRecurring');
    }

    public function invoicesCollect(User $user, RentalInvoice $invoice): bool
    {
        return $this->has($user, 'rental.invoices.collect');
    }

    public function invoicesPenalty(User $user, RentalInvoice $invoice): bool
    {
        return $this->has($user, 'rental.invoices.penalty');
    }
}
