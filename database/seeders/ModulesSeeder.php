<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\BranchModule;
use App\Models\Module;
use Illuminate\Database\Seeder;

class ModulesSeeder extends Seeder
{
    public function run(): void
    {
        $modules = [
            ['key' => 'inventory',      'name' => 'Inventory',          'version' => '1.0.0', 'is_core' => true],
            ['key' => 'sales',          'name' => 'Sales',              'version' => '1.0.0', 'is_core' => true],
            ['key' => 'purchases',      'name' => 'Purchases',          'version' => '1.0.0', 'is_core' => true],
            ['key' => 'pos',            'name' => 'Point of Sale',      'version' => '1.0.0', 'is_core' => true],
            ['key' => 'manufacturing',  'name' => 'Manufacturing',      'version' => '1.0.0', 'is_core' => false],
            ['key' => 'rental',         'name' => 'Rental',             'version' => '1.0.0', 'is_core' => false],
            ['key' => 'motorcycle',     'name' => 'Motorcycle',         'version' => '1.0.0', 'is_core' => false],
            ['key' => 'spares',         'name' => 'Spares',             'version' => '1.0.0', 'is_core' => false],
            ['key' => 'wood',           'name' => 'Wood',               'version' => '1.0.0', 'is_core' => false],
            ['key' => 'hrm',            'name' => 'HRM',                'version' => '1.0.0', 'is_core' => false],
            ['key' => 'reports',        'name' => 'Reports',            'version' => '1.0.0', 'is_core' => true],
        ];

        $createdModules = [];

        foreach ($modules as $row) {
            $module = Module::query()->updateOrCreate(
                ['key' => $row['key']],
                [
                    'name' => $row['name'],
                    'version' => $row['version'],
                    'is_core' => $row['is_core'],
                    'is_active' => true,
                    'description' => $row['name'].' module',
                ]
            );

            $createdModules[$row['key']] = $module;
        }

        /** @var Branch|null $branch */
        $branch = Branch::query()->where('is_main', true)->first() ?? Branch::query()->first();

        if (! $branch) {
            return;
        }

        foreach ($createdModules as $key => $module) {
            BranchModule::query()->updateOrCreate(
                [
                    'branch_id' => $branch->id,
                    'module_key' => $key,
                ],
                [
                    'module_id' => $module->id,
                    'enabled' => true,
                    'settings' => [],
                ]
            );
        }
    }
}
