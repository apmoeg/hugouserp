<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Module;
use App\Models\ModuleNavigation;
use Illuminate\Database\Seeder;

class ModuleNavigationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Clear existing navigation
        ModuleNavigation::query()->delete();

        // Get modules
        $modules = Module::all()->keyBy('key');

        // Define comprehensive navigation structure
        $navigationStructure = $this->getNavigationStructure($modules);

        foreach ($navigationStructure as $parentData) {
            $this->createNavigationItem($parentData, null);
        }
    }

    /**
     * Create navigation item recursively
     */
    protected function createNavigationItem(array $data, ?int $parentId): void
    {
        $children = $data['children'] ?? [];
        unset($data['children']);

        $data['parent_id'] = $parentId;

        $navigation = ModuleNavigation::create($data);

        // Create children recursively
        foreach ($children as $childData) {
            $this->createNavigationItem($childData, $navigation->id);
        }
    }

    /**
     * Get comprehensive navigation structure
     */
    protected function getNavigationStructure($modules): array
    {
        return [
            // Dashboard
            [
                'module_id' => $modules['reports']->id ?? null,
                'nav_key' => 'dashboard',
                'nav_label' => 'Dashboard',
                'nav_label_ar' => 'لوحة التحكم',
                'route_name' => 'dashboard',
                'icon' => '📊',
                'required_permissions' => ['dashboard.view'],
                'is_active' => true,
                'sort_order' => 10,
            ],

            // Inventory & Products
            [
                'module_id' => $modules['inventory']->id ?? null,
                'nav_key' => 'inventory',
                'nav_label' => 'Inventory Management',
                'nav_label_ar' => 'إدارة المخزون',
                'icon' => '📦',
                'required_permissions' => ['inventory.products.view'],
                'is_active' => true,
                'sort_order' => 20,
                'children' => [
                    [
                        'module_id' => $modules['inventory']->id ?? null,
                        'nav_key' => 'inventory_products',
                        'nav_label' => 'Products',
                        'nav_label_ar' => 'المنتجات',
                        'route_name' => 'inventory.products.index',
                        'icon' => '📦',
                        'required_permissions' => ['inventory.products.view'],
                        'is_active' => true,
                        'sort_order' => 10,
                    ],
                    [
                        'module_id' => $modules['inventory']->id ?? null,
                        'nav_key' => 'inventory_categories',
                        'nav_label' => 'Categories',
                        'nav_label_ar' => 'التصنيفات',
                        'route_name' => 'inventory.categories.index',
                        'icon' => '📂',
                        'required_permissions' => ['inventory.products.view'],
                        'is_active' => true,
                        'sort_order' => 20,
                    ],
                    [
                        'module_id' => $modules['inventory']->id ?? null,
                        'nav_key' => 'inventory_units',
                        'nav_label' => 'Units of Measure',
                        'nav_label_ar' => 'وحدات القياس',
                        'route_name' => 'inventory.units.index',
                        'icon' => '📏',
                        'required_permissions' => ['inventory.products.view'],
                        'is_active' => true,
                        'sort_order' => 30,
                    ],
                    [
                        'module_id' => $modules['inventory']->id ?? null,
                        'nav_key' => 'inventory_alerts',
                        'nav_label' => 'Low Stock Alerts',
                        'nav_label_ar' => 'تنبيهات المخزون',
                        'route_name' => 'inventory.stock-alerts',
                        'icon' => '⚠️',
                        'required_permissions' => ['inventory.stock.alerts.view'],
                        'is_active' => true,
                        'sort_order' => 40,
                    ],
                    [
                        'module_id' => $modules['spares']->id ?? null,
                        'nav_key' => 'inventory_vehicle_models',
                        'nav_label' => 'Vehicle Models',
                        'nav_label_ar' => 'موديلات المركبات',
                        'route_name' => 'inventory.vehicle-models',
                        'icon' => '🚗',
                        'required_permissions' => ['spares.compatibility.manage'],
                        'is_active' => true,
                        'sort_order' => 50,
                    ],
                    [
                        'module_id' => $modules['inventory']->id ?? null,
                        'nav_key' => 'inventory_barcode',
                        'nav_label' => 'Print Barcodes',
                        'nav_label_ar' => 'طباعة الباركود',
                        'route_name' => 'inventory.barcode-print',
                        'icon' => '🏷️',
                        'required_permissions' => ['inventory.products.view'],
                        'is_active' => true,
                        'sort_order' => 60,
                    ],
                ],
            ],

            // Manufacturing
            [
                'module_id' => $modules['manufacturing']->id ?? null,
                'nav_key' => 'manufacturing',
                'nav_label' => 'Manufacturing',
                'nav_label_ar' => 'التصنيع',
                'icon' => '🏭',
                'required_permissions' => ['manufacturing.view'],
                'is_active' => true,
                'sort_order' => 25,
                'children' => [
                    [
                        'module_id' => $modules['manufacturing']->id ?? null,
                        'nav_key' => 'manufacturing_boms',
                        'nav_label' => 'Bills of Materials',
                        'nav_label_ar' => 'قوائم المواد',
                        'route_name' => 'app.manufacturing.boms.index',
                        'icon' => '📋',
                        'required_permissions' => ['manufacturing.view'],
                        'is_active' => true,
                        'sort_order' => 10,
                    ],
                    [
                        'module_id' => $modules['manufacturing']->id ?? null,
                        'nav_key' => 'manufacturing_production_orders',
                        'nav_label' => 'Production Orders',
                        'nav_label_ar' => 'أوامر الإنتاج',
                        'route_name' => 'app.manufacturing.orders.index',
                        'icon' => '🏭',
                        'required_permissions' => ['manufacturing.view'],
                        'is_active' => true,
                        'sort_order' => 20,
                    ],
                    [
                        'module_id' => $modules['manufacturing']->id ?? null,
                        'nav_key' => 'manufacturing_work_centers',
                        'nav_label' => 'Work Centers',
                        'nav_label_ar' => 'مراكز العمل',
                        'route_name' => 'app.manufacturing.work-centers.index',
                        'icon' => '⚙️',
                        'required_permissions' => ['manufacturing.view'],
                        'is_active' => true,
                        'sort_order' => 30,
                    ],
                ],
            ],

            // Sales & POS
            [
                'module_id' => $modules['pos']->id ?? null,
                'nav_key' => 'pos',
                'nav_label' => 'Point of Sale',
                'nav_label_ar' => 'نقطة البيع',
                'icon' => '🧾',
                'required_permissions' => ['pos.use'],
                'is_active' => true,
                'sort_order' => 30,
                'children' => [
                    [
                        'module_id' => $modules['pos']->id ?? null,
                        'nav_key' => 'pos_terminal',
                        'nav_label' => 'POS Terminal',
                        'nav_label_ar' => 'شاشة البيع',
                        'route_name' => 'pos.terminal',
                        'icon' => '🏪',
                        'required_permissions' => ['pos.use'],
                        'is_active' => true,
                        'sort_order' => 10,
                    ],
                    [
                        'module_id' => $modules['pos']->id ?? null,
                        'nav_key' => 'pos_daily_report',
                        'nav_label' => 'Daily Report',
                        'nav_label_ar' => 'تقرير يومي',
                        'route_name' => 'pos.daily.report',
                        'icon' => '📑',
                        'required_permissions' => ['pos.daily-report.view'],
                        'is_active' => true,
                        'sort_order' => 20,
                    ],
                ],
            ],

            // Sales Management
            [
                'module_id' => $modules['sales']->id ?? null,
                'nav_key' => 'sales',
                'nav_label' => 'Sales Management',
                'nav_label_ar' => 'إدارة المبيعات',
                'icon' => '💰',
                'required_permissions' => ['sales.view'],
                'is_active' => true,
                'sort_order' => 40,
                'children' => [
                    [
                        'module_id' => $modules['sales']->id ?? null,
                        'nav_key' => 'sales_index',
                        'nav_label' => 'All Sales',
                        'nav_label_ar' => 'كل المبيعات',
                        'route_name' => 'sales.index',
                        'icon' => '📋',
                        'required_permissions' => ['sales.view'],
                        'is_active' => true,
                        'sort_order' => 10,
                    ],
                    [
                        'module_id' => $modules['sales']->id ?? null,
                        'nav_key' => 'sales_returns',
                        'nav_label' => 'Sales Returns',
                        'nav_label_ar' => 'مرتجعات المبيعات',
                        'route_name' => 'sales.returns',
                        'icon' => '↩️',
                        'required_permissions' => ['sales.return'],
                        'is_active' => true,
                        'sort_order' => 20,
                    ],
                ],
            ],

            // Purchases
            [
                'module_id' => $modules['purchases']->id ?? null,
                'nav_key' => 'purchases',
                'nav_label' => 'Purchases',
                'nav_label_ar' => 'المشتريات',
                'icon' => '🛒',
                'required_permissions' => ['purchases.view'],
                'is_active' => true,
                'sort_order' => 50,
                'children' => [
                    [
                        'module_id' => $modules['purchases']->id ?? null,
                        'nav_key' => 'purchases_index',
                        'nav_label' => 'All Purchases',
                        'nav_label_ar' => 'كل المشتريات',
                        'route_name' => 'purchases.index',
                        'icon' => '📋',
                        'required_permissions' => ['purchases.view'],
                        'is_active' => true,
                        'sort_order' => 10,
                    ],
                    [
                        'module_id' => $modules['purchases']->id ?? null,
                        'nav_key' => 'purchases_returns',
                        'nav_label' => 'Purchase Returns',
                        'nav_label_ar' => 'مرتجعات المشتريات',
                        'route_name' => 'purchases.returns',
                        'icon' => '↩️',
                        'required_permissions' => ['purchases.return'],
                        'is_active' => true,
                        'sort_order' => 20,
                    ],
                ],
            ],

            // Customers
            [
                'module_id' => $modules['sales']->id ?? null,
                'nav_key' => 'customers',
                'nav_label' => 'Customers',
                'nav_label_ar' => 'العملاء',
                'route_name' => 'customers.index',
                'icon' => '👤',
                'required_permissions' => ['customers.view'],
                'is_active' => true,
                'sort_order' => 60,
            ],

            // Suppliers
            [
                'module_id' => $modules['purchases']->id ?? null,
                'nav_key' => 'suppliers',
                'nav_label' => 'Suppliers',
                'nav_label_ar' => 'الموردين',
                'route_name' => 'suppliers.index',
                'icon' => '🏭',
                'required_permissions' => ['suppliers.view'],
                'is_active' => true,
                'sort_order' => 70,
            ],

            // Warehouse
            [
                'module_id' => $modules['inventory']->id ?? null,
                'nav_key' => 'warehouse',
                'nav_label' => 'Warehouse',
                'nav_label_ar' => 'المستودع',
                'route_name' => 'warehouse.index',
                'icon' => '🏭',
                'required_permissions' => ['warehouse.view'],
                'is_active' => true,
                'sort_order' => 80,
            ],

            // Expenses
            [
                'module_id' => null, // Assuming there's no specific module for expenses yet
                'nav_key' => 'expenses',
                'nav_label' => 'Expenses',
                'nav_label_ar' => 'المصروفات',
                'route_name' => 'expenses.index',
                'icon' => '📋',
                'required_permissions' => ['expenses.view'],
                'is_active' => true,
                'sort_order' => 90,
            ],

            // Income
            [
                'module_id' => null,
                'nav_key' => 'income',
                'nav_label' => 'Income',
                'nav_label_ar' => 'الإيرادات',
                'route_name' => 'income.index',
                'icon' => '💵',
                'required_permissions' => ['income.view'],
                'is_active' => true,
                'sort_order' => 100,
            ],

            // Accounting
            [
                'module_id' => null,
                'nav_key' => 'accounting',
                'nav_label' => 'Accounting',
                'nav_label_ar' => 'المحاسبة',
                'route_name' => 'app.accounting.index',
                'icon' => '🧮',
                'required_permissions' => ['accounting.view'],
                'is_active' => true,
                'sort_order' => 110,
            ],

            // Human Resources
            [
                'module_id' => $modules['hrm']->id ?? null,
                'nav_key' => 'hrm',
                'nav_label' => 'Human Resources',
                'nav_label_ar' => 'الموارد البشرية',
                'route_name' => 'hrm.employees.index',
                'icon' => '👔',
                'required_permissions' => ['hrm.employees.view'],
                'is_active' => true,
                'sort_order' => 120,
            ],

            // Rental Management
            [
                'module_id' => $modules['rental']->id ?? null,
                'nav_key' => 'rental',
                'nav_label' => 'Rental Management',
                'nav_label_ar' => 'إدارة التأجير',
                'icon' => '🏠',
                'required_permissions' => ['rental.units.view'],
                'is_active' => true,
                'sort_order' => 130,
                'children' => [
                    [
                        'module_id' => $modules['rental']->id ?? null,
                        'nav_key' => 'rental_units',
                        'nav_label' => 'Rental Units',
                        'nav_label_ar' => 'وحدات التأجير',
                        'route_name' => 'rental.units.index',
                        'icon' => '🏠',
                        'required_permissions' => ['rental.units.view'],
                        'is_active' => true,
                        'sort_order' => 10,
                    ],
                    [
                        'module_id' => $modules['rental']->id ?? null,
                        'nav_key' => 'rental_properties',
                        'nav_label' => 'Properties',
                        'nav_label_ar' => 'العقارات',
                        'route_name' => 'rental.properties.index',
                        'icon' => '🏢',
                        'required_permissions' => ['rentals.view'],
                        'is_active' => true,
                        'sort_order' => 20,
                    ],
                    [
                        'module_id' => $modules['rental']->id ?? null,
                        'nav_key' => 'rental_tenants',
                        'nav_label' => 'Tenants',
                        'nav_label_ar' => 'المستأجرين',
                        'route_name' => 'rental.tenants.index',
                        'icon' => '👥',
                        'required_permissions' => ['rentals.view'],
                        'is_active' => true,
                        'sort_order' => 30,
                    ],
                    [
                        'module_id' => $modules['rental']->id ?? null,
                        'nav_key' => 'rental_contracts',
                        'nav_label' => 'Contracts',
                        'nav_label_ar' => 'العقود',
                        'route_name' => 'rental.contracts.index',
                        'icon' => '📄',
                        'required_permissions' => ['rental.contracts.view'],
                        'is_active' => true,
                        'sort_order' => 40,
                    ],
                ],
            ],

            // Administration Section
            [
                'module_id' => null,
                'nav_key' => 'admin_section',
                'nav_label' => 'Administration',
                'nav_label_ar' => 'الإدارة',
                'icon' => '⚙️',
                'required_permissions' => ['settings.view', 'users.manage', 'roles.manage', 'modules.manage'],
                'is_active' => true,
                'sort_order' => 200,
                'children' => [
                    [
                        'module_id' => null,
                        'nav_key' => 'admin_branches',
                        'nav_label' => 'Branch Management',
                        'nav_label_ar' => 'إدارة الفروع',
                        'route_name' => 'admin.branches.index',
                        'icon' => '🏢',
                        'required_permissions' => ['branches.view'],
                        'is_active' => true,
                        'sort_order' => 10,
                    ],
                    [
                        'module_id' => null,
                        'nav_key' => 'admin_users',
                        'nav_label' => 'User Management',
                        'nav_label_ar' => 'إدارة المستخدمين',
                        'route_name' => 'admin.users.index',
                        'icon' => '👥',
                        'required_permissions' => ['users.manage'],
                        'is_active' => true,
                        'sort_order' => 20,
                    ],
                    [
                        'module_id' => null,
                        'nav_key' => 'admin_roles',
                        'nav_label' => 'Role Management',
                        'nav_label_ar' => 'إدارة الصلاحيات',
                        'route_name' => 'admin.roles.index',
                        'icon' => '🔐',
                        'required_permissions' => ['roles.manage'],
                        'is_active' => true,
                        'sort_order' => 30,
                    ],
                    [
                        'module_id' => null,
                        'nav_key' => 'admin_modules',
                        'nav_label' => 'Module Management',
                        'nav_label_ar' => 'إدارة الموديولات',
                        'route_name' => 'admin.modules.index',
                        'icon' => '🧩',
                        'required_permissions' => ['modules.manage'],
                        'is_active' => true,
                        'sort_order' => 40,
                    ],
                    [
                        'module_id' => null,
                        'nav_key' => 'admin_stores',
                        'nav_label' => 'Store Integrations',
                        'nav_label_ar' => 'ربط المتاجر',
                        'route_name' => 'admin.stores.index',
                        'icon' => '🔗',
                        'required_permissions' => ['store.manage'],
                        'is_active' => true,
                        'sort_order' => 50,
                    ],
                    [
                        'module_id' => null,
                        'nav_key' => 'admin_settings',
                        'nav_label' => 'System Settings',
                        'nav_label_ar' => 'إعدادات النظام',
                        'icon' => '⚙️',
                        'required_permissions' => ['settings.view'],
                        'is_active' => true,
                        'sort_order' => 60,
                        'children' => [
                            [
                                'module_id' => null,
                                'nav_key' => 'admin_settings_system',
                                'nav_label' => 'System Settings',
                                'nav_label_ar' => 'الإعدادات العامة',
                                'route_name' => 'admin.settings.system',
                                'icon' => '⚙️',
                                'required_permissions' => ['settings.view'],
                                'is_active' => true,
                                'sort_order' => 10,
                            ],
                            [
                                'module_id' => null,
                                'nav_key' => 'admin_settings_advanced',
                                'nav_label' => 'Advanced Settings',
                                'nav_label_ar' => 'إعدادات متقدمة',
                                'route_name' => 'admin.settings.advanced',
                                'icon' => '🔒',
                                'required_permissions' => ['settings.view'],
                                'is_active' => true,
                                'sort_order' => 20,
                            ],
                            [
                                'module_id' => null,
                                'nav_key' => 'admin_settings_translations',
                                'nav_label' => 'Translation Manager',
                                'nav_label_ar' => 'إدارة الترجمات',
                                'route_name' => 'admin.settings.translations',
                                'icon' => '🌍',
                                'required_permissions' => ['settings.translations.manage'],
                                'is_active' => true,
                                'sort_order' => 30,
                            ],
                            [
                                'module_id' => null,
                                'nav_key' => 'admin_settings_currencies',
                                'nav_label' => 'Currency Management',
                                'nav_label_ar' => 'إدارة العملات',
                                'route_name' => 'admin.settings.currencies',
                                'icon' => '💰',
                                'required_permissions' => ['settings.currency.manage'],
                                'is_active' => true,
                                'sort_order' => 40,
                            ],
                            [
                                'module_id' => null,
                                'nav_key' => 'admin_settings_exchange_rates',
                                'nav_label' => 'Exchange Rates',
                                'nav_label_ar' => 'أسعار الصرف',
                                'route_name' => 'admin.settings.currency-rates',
                                'icon' => '💱',
                                'required_permissions' => ['settings.currency.manage'],
                                'is_active' => true,
                                'sort_order' => 50,
                            ],
                        ],
                    ],
                ],
            ],

            // Reports & Analytics
            [
                'module_id' => $modules['reports']->id ?? null,
                'nav_key' => 'reports_section',
                'nav_label' => 'Reports & Analytics',
                'nav_label_ar' => 'التقارير والتحليلات',
                'icon' => '📊',
                'required_permissions' => ['reports.view', 'reports.hub.view'],
                'is_active' => true,
                'sort_order' => 300,
                'children' => [
                    [
                        'module_id' => $modules['reports']->id ?? null,
                        'nav_key' => 'reports_hub',
                        'nav_label' => 'Reports Hub',
                        'nav_label_ar' => 'مركز التقارير',
                        'route_name' => 'admin.reports.hub',
                        'icon' => '📊',
                        'required_permissions' => ['reports.hub.view'],
                        'is_active' => true,
                        'sort_order' => 10,
                    ],
                    [
                        'module_id' => $modules['sales']->id ?? null,
                        'nav_key' => 'reports_sales',
                        'nav_label' => 'Sales Report',
                        'nav_label_ar' => 'تقرير المبيعات',
                        'route_name' => 'admin.reports.pos.charts',
                        'icon' => '📈',
                        'required_permissions' => ['reports.pos.charts'],
                        'is_active' => true,
                        'sort_order' => 20,
                    ],
                    [
                        'module_id' => $modules['inventory']->id ?? null,
                        'nav_key' => 'reports_inventory',
                        'nav_label' => 'Inventory Report',
                        'nav_label_ar' => 'تقرير المخزون',
                        'route_name' => 'admin.reports.inventory.charts',
                        'icon' => '📦',
                        'required_permissions' => ['reports.inventory.charts'],
                        'is_active' => true,
                        'sort_order' => 30,
                    ],
                    [
                        'module_id' => $modules['sales']->id ?? null,
                        'nav_key' => 'reports_analytics',
                        'nav_label' => 'Sales Analytics',
                        'nav_label_ar' => 'تحليلات المبيعات',
                        'route_name' => 'reports.sales-analytics',
                        'icon' => '📊',
                        'required_permissions' => ['reports.sales.view'],
                        'is_active' => true,
                        'sort_order' => 40,
                    ],
                    [
                        'module_id' => null,
                        'nav_key' => 'reports_store_dashboard',
                        'nav_label' => 'Store Dashboard',
                        'nav_label_ar' => 'لوحة المتجر',
                        'route_name' => 'admin.store.dashboard',
                        'icon' => '🏪',
                        'required_permissions' => ['store.reports.dashboard'],
                        'is_active' => true,
                        'sort_order' => 50,
                    ],
                    [
                        'module_id' => null,
                        'nav_key' => 'reports_audit_logs',
                        'nav_label' => 'Audit Logs',
                        'nav_label_ar' => 'سجلات المراجعة',
                        'route_name' => 'admin.logs.audit',
                        'icon' => '📋',
                        'required_permissions' => ['logs.audit.view'],
                        'is_active' => true,
                        'sort_order' => 60,
                    ],
                    [
                        'module_id' => $modules['reports']->id ?? null,
                        'nav_key' => 'reports_scheduled',
                        'nav_label' => 'Scheduled Reports',
                        'nav_label_ar' => 'التقارير المجدولة',
                        'route_name' => 'admin.reports.schedules',
                        'icon' => '📅',
                        'required_permissions' => ['reports.scheduled.manage'],
                        'is_active' => true,
                        'sort_order' => 70,
                    ],
                ],
            ],
        ];
    }
}
