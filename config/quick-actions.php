<?php

declare(strict_types=1);

/**
 * Quick Actions Configuration
 *
 * Define role-based quick action buttons for the dashboard
 */

return [
    /**
     * Quick Actions for Sales/Cashier Role
     */
    'sales' => [
        [
            'label' => 'New Sale / POS',
            'icon' => '🧾',
            'route' => 'pos.terminal',
            'permission' => 'pos.use',
            'color' => 'amber',
            'description' => 'Open POS terminal for new sale',
        ],
        [
            'label' => 'New Customer',
            'icon' => '👤',
            'route' => 'customers.create',
            'permission' => 'customers.create',
            'color' => 'cyan',
            'description' => 'Add new customer',
        ],
        [
            'label' => 'Search Product',
            'icon' => '🔍',
            'route' => 'inventory.products.index',
            'permission' => 'inventory.products.view',
            'color' => 'teal',
            'description' => 'Search product catalog',
        ],
        [
            'label' => "Today's Sales Report",
            'icon' => '📊',
            'route' => 'pos.daily.report',
            'permission' => 'pos.daily-report.view',
            'color' => 'green',
            'description' => 'View daily sales report',
        ],
    ],

    /**
     * Quick Actions for Purchasing Role
     */
    'purchases' => [
        [
            'label' => 'Create Purchase Order',
            'icon' => '🛒',
            'route' => 'purchases.create',
            'permission' => 'purchases.create',
            'color' => 'purple',
            'description' => 'Create new purchase order',
        ],
        [
            'label' => 'Add Supplier',
            'icon' => '🏭',
            'route' => 'suppliers.create',
            'permission' => 'suppliers.create',
            'color' => 'violet',
            'description' => 'Add new supplier',
        ],
        [
            'label' => 'Low Stock Products',
            'icon' => '⚠️',
            'route' => 'inventory.stock-alerts',
            'permission' => 'inventory.stock.alerts.view',
            'color' => 'orange',
            'description' => 'View low stock alerts',
        ],
        [
            'label' => 'Pending Purchases',
            'icon' => '📋',
            'route' => 'purchases.index',
            'permission' => 'purchases.view',
            'color' => 'indigo',
            'description' => 'View pending purchase orders',
        ],
    ],

    /**
     * Quick Actions for Financial Manager
     */
    'manager' => [
        [
            'label' => "Today's Cash Position",
            'icon' => '💰',
            'route' => 'banking.accounts.index',
            'permission' => 'banking.view',
            'color' => 'emerald',
            'description' => 'View cash and bank balances',
        ],
        [
            'label' => 'Approve Journal Entries',
            'icon' => '✅',
            'route' => 'app.accounting.index',
            'permission' => 'accounting.view',
            'color' => 'sky',
            'description' => 'Review pending journal entries',
        ],
        [
            'label' => 'Payroll Summary',
            'icon' => '💼',
            'route' => 'hrm.employees.index',
            'permission' => 'hrm.employees.view',
            'color' => 'rose',
            'description' => 'View payroll overview',
        ],
        [
            'label' => 'AR / AP Aging',
            'icon' => '📈',
            'route' => 'admin.reports.hub',
            'permission' => 'reports.view',
            'color' => 'blue',
            'description' => 'Accounts receivable/payable aging',
        ],
    ],

    /**
     * Quick Actions for Inventory Manager
     */
    'inventory' => [
        [
            'label' => 'Add Product',
            'icon' => '📦',
            'route' => 'inventory.products.create',
            'permission' => 'inventory.products.create',
            'color' => 'teal',
            'description' => 'Add new product',
        ],
        [
            'label' => 'Stock Adjustment',
            'icon' => '⚖️',
            'route' => 'warehouse.index',
            'permission' => 'warehouse.view',
            'color' => 'orange',
            'description' => 'Create stock adjustment',
        ],
        [
            'label' => 'Stock Valuation',
            'icon' => '💎',
            'route' => 'admin.reports.inventory.charts',
            'permission' => 'reports.inventory.charts',
            'color' => 'purple',
            'description' => 'View inventory valuation',
        ],
        [
            'label' => 'Print Barcodes',
            'icon' => '🏷️',
            'route' => 'inventory.barcode-print',
            'permission' => 'inventory.products.view',
            'color' => 'slate',
            'description' => 'Print product barcodes',
        ],
    ],

    /**
     * Quick Actions for HR Manager
     */
    'hrm' => [
        [
            'label' => 'Add Employee',
            'icon' => '👔',
            'route' => 'hrm.employees.create',
            'permission' => 'hrm.employees.create',
            'color' => 'rose',
            'description' => 'Add new employee',
        ],
        [
            'label' => "Today's Attendance",
            'icon' => '📅',
            'route' => 'hrm.employees.index',
            'permission' => 'hrm.employees.view',
            'color' => 'blue',
            'description' => 'View today attendance',
        ],
        [
            'label' => 'Process Payroll',
            'icon' => '💰',
            'route' => 'hrm.employees.index',
            'permission' => 'hrm.employees.view',
            'color' => 'green',
            'description' => 'Process employee payroll',
        ],
        [
            'label' => 'Leave Requests',
            'icon' => '🏖️',
            'route' => 'hrm.employees.index',
            'permission' => 'hrm.employees.view',
            'color' => 'amber',
            'description' => 'Review leave requests',
        ],
    ],

    /**
     * Quick Actions for Admin/Super Admin
     */
    'admin' => [
        [
            'label' => 'System Settings',
            'icon' => '⚙️',
            'route' => 'admin.settings.system',
            'permission' => 'settings.view',
            'color' => 'slate',
            'description' => 'Configure system settings',
        ],
        [
            'label' => 'Manage Users',
            'icon' => '👥',
            'route' => 'admin.users.index',
            'permission' => 'users.manage',
            'color' => 'pink',
            'description' => 'Manage system users',
        ],
        [
            'label' => 'Audit Logs',
            'icon' => '📋',
            'route' => 'admin.logs.audit',
            'permission' => 'logs.audit.view',
            'color' => 'indigo',
            'description' => 'View system audit logs',
        ],
        [
            'label' => 'Module Management',
            'icon' => '🧩',
            'route' => 'admin.modules.index',
            'permission' => 'modules.manage',
            'color' => 'fuchsia',
            'description' => 'Enable/disable modules',
        ],
    ],
];
