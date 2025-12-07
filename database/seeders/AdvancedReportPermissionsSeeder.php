<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class AdvancedReportPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [

            'store.api',
            'store.api.products',
            'store.api.orders',
            'reports.scheduled.manage',
            'reports.templates.manage',
            'store.reports.dashboard',
            'reports.pos.charts',
            'reports.inventory.charts',
            'reports.hub.view',
            'reports.pos.export',
            'reports.inventory.export'];

        foreach ($permissions as $name) {
            Permission::firstOrCreate([
                'name' => $name,
                'guard_name' => 'web',
            ]);
        }

        $admin = Role::where('name', 'admin')->first();

        if ($admin) {
            $admin->givePermissionTo($permissions);
        }
    }
}
