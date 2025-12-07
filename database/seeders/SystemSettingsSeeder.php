<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\SystemSetting;
use Illuminate\Database\Seeder;

class SystemSettingsSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            [
                'key' => 'app_name',
                'value' => 'Ghanem ERP',
                'type' => 'string',
                'group' => 'system',
            ],
            [
                'key' => 'company_name',
                'value' => 'Ghanem LVJU Egypt',
                'type' => 'string',
                'group' => 'system',
            ],
            [
                'key' => 'currency',
                'value' => 'EGP',
                'type' => 'string',
                'group' => 'system',
            ],
            [
                'key' => 'barcodes_dir',
                'value' => 'barcodes',
                'type' => 'string',
                'group' => 'files',
            ],
            [
                'key' => 'backup_disk',
                'value' => 'local',
                'type' => 'string',
                'group' => 'backup',
            ],
            [
                'key' => 'backup_dir',
                'value' => 'backups',
                'type' => 'string',
                'group' => 'backup',
            ],
        ];

        foreach ($settings as $row) {
            SystemSetting::updateOrCreate(
                ['key' => $row['key']],
                [
                    'value' => $row['value'],
                    'type' => $row['type'] ?? 'string',
                    'group' => $row['group'] ?? null,
                    'is_public' => $row['is_public'] ?? false,
                ]
            );
        }

        $advancedSettings = [
            'inventory.enable_variations' => true,
        ];

        foreach ($advancedSettings as $key => $value) {
            \App\Models\SystemSetting::query()->updateOrCreate(
                ['key' => $key],
                ['value' => $value]
            );
        }
    }
}
