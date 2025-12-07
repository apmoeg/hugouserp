<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Module;
use App\Models\ModuleNavigation;
use App\Models\ModuleOperation;
use App\Models\ModulePolicy;
use Illuminate\Database\Seeder;

class ModuleArchitectureSeeder extends Seeder
{
    /**
     * Seed the enhanced module architecture data
     */
    public function run(): void
    {
        $this->seedModuleTypes();
        $this->seedModuleOperations();
        $this->seedModulePolicies();
        $this->seedModuleNavigation();
    }

    protected function seedModuleTypes(): void
    {
        // Update existing modules with types
        $dataModules = ['inventory', 'products', 'customers', 'suppliers', 'employees'];
        Module::whereIn('key', $dataModules)->update([
            'module_type' => 'data',
            'supports_reporting' => true,
            'supports_custom_fields' => true,
        ]);

        $functionalModules = ['pos', 'sales', 'purchases', 'accounting', 'reports'];
        Module::whereIn('key', $functionalModules)->update([
            'module_type' => 'functional',
            'supports_reporting' => true,
            'supports_custom_fields' => false,
        ]);

        $hybridModules = ['hrm', 'rental', 'stores'];
        Module::whereIn('key', $hybridModules)->update([
            'module_type' => 'hybrid',
            'supports_reporting' => true,
            'supports_custom_fields' => true,
        ]);
    }

    protected function seedModuleOperations(): void
    {
        $modules = Module::all();

        foreach ($modules as $module) {
            // Standard CRUD operations for all modules
            $operations = [
                [
                    'operation_key' => 'create',
                    'operation_name' => 'Create',
                    'operation_type' => 'create',
                    'required_permissions' => [$module->key.'.create'],
                    'is_active' => true,
                ],
                [
                    'operation_key' => 'read',
                    'operation_name' => 'View',
                    'operation_type' => 'read',
                    'required_permissions' => [$module->key.'.view'],
                    'is_active' => true,
                ],
                [
                    'operation_key' => 'update',
                    'operation_name' => 'Update',
                    'operation_type' => 'update',
                    'required_permissions' => [$module->key.'.edit'],
                    'is_active' => true,
                ],
                [
                    'operation_key' => 'delete',
                    'operation_name' => 'Delete',
                    'operation_type' => 'delete',
                    'required_permissions' => [$module->key.'.delete'],
                    'is_active' => true,
                ],
            ];

            if ($module->supports_reporting) {
                $operations[] = [
                    'operation_key' => 'export',
                    'operation_name' => 'Export',
                    'operation_type' => 'export',
                    'required_permissions' => [$module->key.'.export'],
                    'is_active' => true,
                ];
            }

            foreach ($operations as $operationData) {
                ModuleOperation::updateOrCreate(
                    [
                        'module_id' => $module->id,
                        'operation_key' => $operationData['operation_key'],
                    ],
                    $operationData
                );
            }
        }
    }

    protected function seedModulePolicies(): void
    {
        $inventoryModule = Module::where('key', 'inventory')->first();
        if ($inventoryModule) {
            ModulePolicy::updateOrCreate(
                [
                    'module_id' => $inventoryModule->id,
                    'policy_key' => 'stock_validation',
                    'branch_id' => null,
                ],
                [
                    'policy_name' => 'Stock Validation Policy',
                    'policy_description' => 'Validates stock levels before operations',
                    'policy_rules' => [
                        'check_stock_before_sale' => true,
                        'allow_negative_stock' => false,
                    ],
                    'scope' => 'global',
                    'is_active' => true,
                    'priority' => 100,
                ]
            );
        }

        $salesModule = Module::where('key', 'sales')->first();
        if ($salesModule) {
            ModulePolicy::updateOrCreate(
                [
                    'module_id' => $salesModule->id,
                    'policy_key' => 'discount_limits',
                    'branch_id' => null,
                ],
                [
                    'policy_name' => 'Discount Limits Policy',
                    'policy_description' => 'Controls discount permissions',
                    'policy_rules' => [
                        'max_discount_percent' => 20,
                        'requires_manager_approval' => true,
                    ],
                    'scope' => 'branch',
                    'is_active' => true,
                    'priority' => 100,
                ]
            );
        }
    }

    protected function seedModuleNavigation(): void
    {
        $inventoryModule = Module::where('key', 'inventory')->first();
        if ($inventoryModule) {
            $inventoryNav = ModuleNavigation::updateOrCreate(
                [
                    'module_id' => $inventoryModule->id,
                    'nav_key' => 'inventory_main',
                ],
                [
                    'nav_label' => 'Inventory',
                    'nav_label_ar' => 'المخزون',
                    'icon' => 'package',
                    'required_permissions' => ['inventory.view'],
                    'is_active' => true,
                    'sort_order' => 10,
                ]
            );

            ModuleNavigation::updateOrCreate(
                [
                    'module_id' => $inventoryModule->id,
                    'nav_key' => 'inventory_products',
                ],
                [
                    'parent_id' => $inventoryNav->id,
                    'nav_label' => 'Products',
                    'nav_label_ar' => 'المنتجات',
                    'route_name' => 'products.index',
                    'icon' => 'box',
                    'required_permissions' => ['products.view'],
                    'is_active' => true,
                    'sort_order' => 10,
                ]
            );
        }

        $salesModule = Module::where('key', 'sales')->first();
        if ($salesModule) {
            $salesNav = ModuleNavigation::updateOrCreate(
                [
                    'module_id' => $salesModule->id,
                    'nav_key' => 'sales_main',
                ],
                [
                    'nav_label' => 'Sales',
                    'nav_label_ar' => 'المبيعات',
                    'icon' => 'shopping-cart',
                    'required_permissions' => ['sales.view'],
                    'is_active' => true,
                    'sort_order' => 20,
                ]
            );

            ModuleNavigation::updateOrCreate(
                [
                    'module_id' => $salesModule->id,
                    'nav_key' => 'sales_orders',
                ],
                [
                    'parent_id' => $salesNav->id,
                    'nav_label' => 'Sales Orders',
                    'nav_label_ar' => 'أوامر المبيعات',
                    'route_name' => 'sales.index',
                    'icon' => 'file-text',
                    'required_permissions' => ['sales.view'],
                    'is_active' => true,
                    'sort_order' => 10,
                ]
            );
        }

        $hrmModule = Module::where('key', 'hrm')->first();
        if ($hrmModule) {
            $hrmNav = ModuleNavigation::updateOrCreate(
                [
                    'module_id' => $hrmModule->id,
                    'nav_key' => 'hrm_main',
                ],
                [
                    'nav_label' => 'Human Resources',
                    'nav_label_ar' => 'الموارد البشرية',
                    'icon' => 'users',
                    'required_permissions' => ['hrm.view'],
                    'is_active' => true,
                    'sort_order' => 30,
                ]
            );
        }
    }
}
