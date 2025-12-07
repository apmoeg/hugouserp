<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\Branch;
use App\Models\Notification;
// Models
use App\Models\Product;
use App\Models\Property;
use App\Models\Purchase;
use App\Models\RentalContract;
use App\Models\RentalInvoice;
use App\Models\RentalUnit;
use App\Models\Sale;
use App\Models\Tenant;
use App\Models\Vehicle;
use App\Policies\BranchPolicy;
use App\Policies\NotificationPolicy;
// Policies
use App\Policies\ProductPolicy;
use App\Policies\PurchasePolicy;
use App\Policies\RentalPolicy;
use App\Policies\SalePolicy;
use App\Policies\VehiclePolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     */
    protected $policies = [
        Branch::class => BranchPolicy::class,
        Product::class => ProductPolicy::class,
        Purchase::class => PurchasePolicy::class,
        Sale::class => SalePolicy::class,
        Vehicle::class => VehiclePolicy::class,
        Notification::class => NotificationPolicy::class,

        // Rental domain mapped to a generic policy handling multiple models
        RentalContract::class => RentalPolicy::class,
        RentalInvoice::class => RentalPolicy::class,
        Property::class => RentalPolicy::class,
        RentalUnit::class => RentalPolicy::class,
        Tenant::class => RentalPolicy::class,
    ];

    public function boot(): void
    {
        $this->registerPolicies();

        // Super Admin shortcut (works with spatie/permission)
        Gate::before(function ($user, $ability) {
            if (method_exists($user, 'hasRole') && $user->hasRole('Super Admin')) {
                return true;
            }

            return null;
        });

        // Ability for impersonation if you use it
        Gate::define('impersonate', function ($user) {
            return (method_exists($user, 'hasPermissionTo') && $user->hasPermissionTo('impersonate.users'))
                || (method_exists($user, 'hasRole') && $user->hasRole('Super Admin'));
        });
    }
}
