<?php

use App\Http\Controllers\Admin\Store\StoreOrdersExportController;
use App\Livewire\Accounting\Index as AccountingIndexPage;
use App\Livewire\Admin\Branches\Form as BranchFormPage;
use App\Livewire\Admin\Branches\Index as BranchesIndexPage;
use App\Livewire\Admin\Logs\Audit as AuditLogPage;
use App\Livewire\Admin\Modules\Form as ModuleFormPage;
use App\Livewire\Admin\Modules\Index as ModulesIndexPage;
use App\Livewire\Admin\Reports\InventoryChartsDashboard;
use App\Livewire\Admin\Reports\PosChartsDashboard;
use App\Livewire\Admin\Reports\ReportsHub;
use App\Livewire\Admin\Reports\ReportTemplatesManager;
use App\Livewire\Admin\Reports\ScheduledReportsManager;
use App\Livewire\Admin\Roles\Form as RoleFormPage;
use App\Livewire\Admin\Roles\Index as RolesIndexPage;
use App\Livewire\Admin\Settings\BranchSettings as BranchSettingsPage;
use App\Livewire\Admin\Settings\SystemSettings as SystemSettingsPage;
use App\Livewire\Admin\Store\OrdersDashboard;
use App\Livewire\Admin\Users\Form as UserFormPage;
use App\Livewire\Admin\Users\Index as UsersIndexPage;
use App\Livewire\Auth\ForgotPassword;
use App\Livewire\Auth\Login as LoginPage;
use App\Livewire\Auth\ResetPassword;
use App\Livewire\Auth\TwoFactorChallenge;
use App\Livewire\Auth\TwoFactorSetup;
use App\Livewire\Customers\Form as CustomerFormPage;
use App\Livewire\Customers\Index as CustomersIndexPage;
use App\Livewire\Dashboard\Index as DashboardPage;
use App\Livewire\Expenses\Form as ExpenseFormPage;
use App\Livewire\Expenses\Index as ExpensesIndexPage;
use App\Livewire\Hrm\Attendance\Index as HrmAttendanceIndex;
use App\Livewire\Hrm\Employees\Form as HrmEmployeeForm;
use App\Livewire\Hrm\Employees\Index as HrmEmployeesIndex;
use App\Livewire\Hrm\Payroll\Index as HrmPayrollIndex;
use App\Livewire\Hrm\Payroll\Run as HrmPayrollRun;
use App\Livewire\Hrm\Reports\Dashboard as HrmReportsDashboard;
use App\Livewire\Income\Index as IncomeIndexPage;
use App\Livewire\Inventory\Products\Form as ProductFormPage;
use App\Livewire\Inventory\Products\Index as ProductsIndexPage;
use App\Livewire\Notifications\Center as NotificationsCenter;
use App\Livewire\Pos\Reports\OfflineSales as PosOfflineSalesPage;
use App\Livewire\Pos\Terminal as PosTerminalPage;
use App\Livewire\Profile\Edit as ProfileEditPage;
use App\Livewire\Purchases\Index as PurchasesIndexPage;
use App\Livewire\Rental\Contracts\Form as RentalContractForm;
use App\Livewire\Rental\Contracts\Index as RentalContractsIndex;
use App\Livewire\Rental\Reports\Dashboard as RentalReportsDashboard;
use App\Livewire\Rental\Units\Form as RentalUnitForm;
use App\Livewire\Rental\Units\Index as RentalUnitsIndex;
use App\Livewire\Sales\Index as SalesIndexPage;
use App\Livewire\Suppliers\Form as SupplierFormPage;
use App\Livewire\Suppliers\Index as SuppliersIndexPage;
use App\Livewire\Warehouse\Index as WarehouseIndexPage;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    if (auth()->check()) {
        return redirect()->route('dashboard');
    }

    return redirect()->route('login');
});

Route::get('/health', function () {
    return response()->json([
        'success' => true,
        'status' => 'ok',
        'time' => now()->toIso8601String(),
    ]);
});

Route::get('/login', LoginPage::class)
    ->middleware('guest')
    ->name('login');

Route::get('/forgot-password', ForgotPassword::class)
    ->middleware('guest')
    ->name('password.request');

Route::get('/reset-password/{token}', ResetPassword::class)
    ->middleware('guest')
    ->name('password.reset');

Route::post('/logout', function () {
    auth()->logout();
    request()->session()->invalidate();
    request()->session()->regenerateToken();

    return redirect('/login');
})->middleware('auth')->name('logout');

Route::get('/2fa/challenge', TwoFactorChallenge::class)
    ->middleware('auth')
    ->name('2fa.challenge');

Route::get('/2fa/setup', TwoFactorSetup::class)
    ->middleware('auth')
    ->name('2fa.setup');

Route::middleware('auth')->group(function () {

    // Dashboard
    Route::get('/dashboard', DashboardPage::class)
        ->name('dashboard')
        ->middleware('can:'.config('screen_permissions.dashboard', 'dashboard.view'));

    // Profile
    Route::get('/profile', ProfileEditPage::class)
        ->name('profile.edit');

    // POS Terminal
    Route::get('/pos', PosTerminalPage::class)
        ->name('pos.terminal')
        ->middleware('can:'.config('screen_permissions.pos.terminal', 'pos.use'));

    // POS Offline sales report
    Route::get('/pos/offline-sales', PosOfflineSalesPage::class)
        ->name('pos.offline.report')
        ->middleware('can:pos.offline.report.view');

    // POS Daily Report
    Route::get('/pos/daily-report', \App\Livewire\Pos\DailyReport::class)
        ->name('pos.daily.report')
        ->middleware('can:pos.daily-report.view');

    // Notifications center
    Route::get('/notifications', NotificationsCenter::class)
        ->name('notifications.center')
        ->middleware('can:'.config('screen_permissions.notifications.center', 'system.view-notifications'));

    // Admin area
    Route::prefix('admin')->name('admin.')->group(function () {

        // Users
        Route::get('/users', UsersIndexPage::class)
            ->name('users.index')
            ->middleware('can:'.config('screen_permissions.admin.users.index', 'users.manage'));

        Route::get('/users/create', UserFormPage::class)
            ->name('users.create')
            ->middleware('can:'.config('screen_permissions.admin.users.index', 'users.manage'));

        Route::get('/users/{user}/edit', UserFormPage::class)
            ->name('users.edit')
            ->middleware('can:'.config('screen_permissions.admin.users.index', 'users.manage'));

        // Branches
        Route::get('/branches', BranchesIndexPage::class)
            ->name('branches.index')
            ->middleware('can:'.config('screen_permissions.admin.branches.index', 'branches.view'));

        Route::get('/branches/create', BranchFormPage::class)
            ->name('branches.create')
            ->middleware('can:'.config('screen_permissions.admin.branches.index', 'branches.view'));

        Route::get('/branches/{branch}/edit', BranchFormPage::class)
            ->name('branches.edit')
            ->middleware('can:'.config('screen_permissions.admin.branches.index', 'branches.view'));

        Route::get('/branches/{branch}/modules', \App\Livewire\Admin\Branches\Modules::class)
            ->name('branches.modules')
            ->middleware('can:branches.manage');

        // Settings
        Route::prefix('settings')->name('settings.')->group(function () {

            // System settings
            Route::get('/system', SystemSettingsPage::class)
                ->name('system')
                ->middleware('can:'.config('screen_permissions.admin.settings.system', 'settings.view'));

            // Branch settings
            Route::get('/branch', BranchSettingsPage::class)
                ->name('branch')
                ->middleware('can:'.config('screen_permissions.admin.settings.branch', 'settings.branch'));

            // Translation Manager
            Route::get('/translations', \App\Livewire\Admin\Settings\TranslationManager::class)
                ->name('translations')
                ->middleware('can:settings.translations.manage');

            // Advanced Settings (SMS, Security, Backup)
            Route::get('/advanced', \App\Livewire\Admin\Settings\AdvancedSettings::class)
                ->name('advanced')
                ->middleware('can:settings.view');
        });

        // Audit logs
        Route::get('/logs/audit', AuditLogPage::class)
            ->name('logs.audit')
            ->middleware('can:'.config('screen_permissions.logs.audit', 'logs.audit.view'));
    });

    // Roles management
    Route::get('/admin/roles', RolesIndexPage::class)
        ->name('admin.roles.index')
        ->middleware('can:roles.manage');

    Route::get('/admin/roles/create', RoleFormPage::class)
        ->name('admin.roles.create')
        ->middleware('can:roles.manage');

    Route::get('/admin/roles/{role}/edit', RoleFormPage::class)
        ->name('admin.roles.edit')
        ->middleware('can:roles.manage');

    // Modules management
    Route::get('/admin/modules', ModulesIndexPage::class)
        ->name('admin.modules.index')
        ->middleware('can:modules.manage');

    Route::get('/admin/modules/create', ModuleFormPage::class)
        ->name('admin.modules.create')
        ->middleware('can:modules.manage');

    Route::get('/admin/modules/{module}/edit', ModuleFormPage::class)
        ->name('admin.modules.edit')
        ->middleware('can:modules.manage');

    Route::get('/admin/modules/{module}/fields', \App\Livewire\Admin\Modules\Fields::class)
        ->name('admin.modules.fields')
        ->middleware('can:modules.manage');

    Route::get('/admin/modules/{module}/rental-periods', \App\Livewire\Admin\Modules\RentalPeriods::class)
        ->name('admin.modules.rental-periods')
        ->middleware('can:modules.manage');

    Route::get('/admin/modules/product-fields/{moduleId?}', \App\Livewire\Admin\Modules\ProductFields::class)
        ->name('admin.modules.product-fields')
        ->middleware('can:modules.manage');

    // Reports Center
    Route::get('/reports', \App\Livewire\Admin\Reports\Index::class)
        ->name('reports.index')
        ->middleware('can:reports.view');

    Route::get('/reports/aggregate', \App\Livewire\Admin\Reports\Aggregate::class)
        ->name('reports.aggregate')
        ->middleware('can:reports.aggregate');

    Route::get('/reports/module/{module}', \App\Livewire\Admin\Reports\ModuleReport::class)
        ->name('reports.module')
        ->middleware('can:reports.view');

    // Export Management
    Route::get('/export/customize', \App\Livewire\Admin\Export\CustomizeExport::class)
        ->name('export.customize')
        ->middleware('can:reports.export');

    // Customers
    Route::get('/customers', CustomersIndexPage::class)
        ->name('customers.index')
        ->middleware('can:customers.view');

    Route::get('/customers/create', CustomerFormPage::class)
        ->name('customers.create')
        ->middleware('can:customers.manage');

    Route::get('/customers/{customer}/edit', CustomerFormPage::class)
        ->name('customers.edit')
        ->middleware('can:customers.manage');

    // Suppliers
    Route::get('/suppliers', SuppliersIndexPage::class)
        ->name('suppliers.index')
        ->middleware('can:suppliers.view');

    Route::get('/suppliers/create', SupplierFormPage::class)
        ->name('suppliers.create')
        ->middleware('can:suppliers.manage');

    Route::get('/suppliers/{supplier}/edit', SupplierFormPage::class)
        ->name('suppliers.edit')
        ->middleware('can:suppliers.manage');

    // Sales
    Route::get('/sales', SalesIndexPage::class)
        ->name('sales.index')
        ->middleware('can:sales.view');

    Route::get('/sales/returns', \App\Livewire\Sales\Returns\Index::class)
        ->name('sales.returns')
        ->middleware('can:sales.return');

    // Purchases
    Route::get('/purchases', PurchasesIndexPage::class)
        ->name('purchases.index')
        ->middleware('can:purchases.view');

    Route::get('/purchases/returns', \App\Livewire\Purchases\Returns\Index::class)
        ->name('purchases.returns')
        ->middleware('can:purchases.return');

    Route::get('/purchases/create', \App\Livewire\Purchases\Form::class)
        ->name('purchases.create')
        ->middleware('can:purchases.manage');

    Route::get('/purchases/{purchase}/edit', \App\Livewire\Purchases\Form::class)
        ->name('purchases.edit')
        ->middleware('can:purchases.manage');

    // Expenses
    Route::get('/expenses', ExpensesIndexPage::class)
        ->name('expenses.index')
        ->middleware('can:expenses.view');

    Route::get('/expenses/create', ExpenseFormPage::class)
        ->name('expenses.create')
        ->middleware('can:expenses.manage');

    Route::get('/expenses/{expense}/edit', ExpenseFormPage::class)
        ->name('expenses.edit')
        ->middleware('can:expenses.manage');

    // Income
    Route::get('/income', IncomeIndexPage::class)
        ->name('income.index')
        ->middleware('can:income.view');

    Route::get('/income/create', \App\Livewire\Income\Form::class)
        ->name('income.create')
        ->middleware('can:income.manage');

    Route::get('/income/{income}/edit', \App\Livewire\Income\Form::class)
        ->name('income.edit')
        ->middleware('can:income.manage');

    // Accounting
    Route::get('/accounting', AccountingIndexPage::class)
        ->name('accounting.index')
        ->middleware('can:accounting.view');

    // Warehouse
    Route::get('/warehouse', WarehouseIndexPage::class)
        ->name('warehouse.index')
        ->middleware('can:warehouse.view');

    // Inventory
    Route::prefix('inventory')->name('inventory.')->group(function () {
        Route::get('/products', ProductsIndexPage::class)
            ->name('products.index')
            ->middleware('can:'.config('screen_permissions.inventory.products.index', 'inventory.products.view'));

        Route::get('/products/create', ProductFormPage::class)
            ->name('products.create')
            ->middleware('can:'.config('screen_permissions.inventory.products.index', 'inventory.products.view'));

        Route::get('/products/{product}/edit', ProductFormPage::class)
            ->name('products.edit')
            ->middleware('can:'.config('screen_permissions.inventory.products.index', 'inventory.products.view'));

        Route::get('/products/{product}/store-mappings', \App\Livewire\Inventory\ProductStoreMappings::class)
            ->name('products.store-mappings')
            ->middleware('can:inventory.products.view');
    });

    // HRM: Employees, Attendance, Payroll & Reports
    Route::prefix('hrm')->name('hrm.')->group(function () {
        // Employees list
        Route::get('/employees', HrmEmployeesIndex::class)
            ->name('employees.index')
            ->middleware('can:hrm.employees.view');

        // Create employee
        Route::get('/employees/create', HrmEmployeeForm::class)
            ->name('employees.create')
            ->middleware('can:hrm.employees.assign');

        // Edit employee
        Route::get('/employees/{employee}/edit', HrmEmployeeForm::class)
            ->name('employees.edit')
            ->middleware('can:hrm.employees.assign');

        // Attendance list
        Route::get('/attendance', HrmAttendanceIndex::class)
            ->name('attendance.index')
            ->middleware('can:hrm.attendance.view');

        // Payroll runs list
        Route::get('/payroll', HrmPayrollIndex::class)
            ->name('payroll.index')
            ->middleware('can:hrm.payroll.view');

        // Run payroll
        Route::get('/payroll/run', HrmPayrollRun::class)
            ->name('payroll.run')
            ->middleware('can:hrm.payroll.run');

        // HRM reports dashboard
        Route::get('/reports', HrmReportsDashboard::class)
            ->name('reports.dashboard')
            ->middleware('can:hr.view-reports');
    });

    // Rental: Units, Contracts, Properties, Tenants & Reports
    Route::prefix('rental')->name('rental.')->group(function () {
        // Rental units list
        Route::get('/units', RentalUnitsIndex::class)
            ->name('units.index')
            ->middleware('can:rental.units.view');

        // Create rental unit
        Route::get('/units/create', RentalUnitForm::class)
            ->name('units.create')
            ->middleware('can:rental.units.manage');

        // Edit rental unit
        Route::get('/units/{unit}/edit', RentalUnitForm::class)
            ->name('units.edit')
            ->middleware('can:rental.units.manage');

        // Rental contracts list
        Route::get('/contracts', RentalContractsIndex::class)
            ->name('contracts.index')
            ->middleware('can:rental.contracts.view');

        // Create rental contract
        Route::get('/contracts/create', RentalContractForm::class)
            ->name('contracts.create')
            ->middleware('can:rental.contracts.manage');

        // Edit rental contract
        Route::get('/contracts/{contract}/edit', RentalContractForm::class)
            ->name('contracts.edit')
            ->middleware('can:rental.contracts.manage');

        // Properties management
        Route::get('/properties', \App\Livewire\Rentals\Properties\Index::class)
            ->name('properties.index')
            ->middleware('can:rentals.view');

        // Tenants management
        Route::get('/tenants', \App\Livewire\Rentals\Tenants\Index::class)
            ->name('tenants.index')
            ->middleware('can:rentals.view');

        // Rental reports dashboard
        Route::get('/reports', RentalReportsDashboard::class)
            ->name('reports.dashboard')
            ->middleware('can:rental.view-reports');
    });

    // Manufacturing: BOMs, Production Orders, Work Centers
    Route::prefix('manufacturing')->name('manufacturing.')->group(function () {
        // Bills of Materials
        Route::get('/boms', \App\Livewire\Manufacturing\BillsOfMaterials\Index::class)
            ->name('boms.index')
            ->middleware('can:manufacturing.view');

        // TODO: Add Form and Show components later
        // Route::get('/boms/create', \App\Livewire\Manufacturing\BillsOfMaterials\Form::class)
        //     ->name('boms.create')
        //     ->middleware('can:manufacturing.create');
        // Route::get('/boms/{bom}/edit', \App\Livewire\Manufacturing\BillsOfMaterials\Form::class)
        //     ->name('boms.edit')
        //     ->middleware('can:manufacturing.edit');
        // Route::get('/boms/{bom}', \App\Livewire\Manufacturing\BillsOfMaterials\Show::class)
        //     ->name('boms.show')
        //     ->middleware('can:manufacturing.view');

        // Production Orders
        Route::get('/production-orders', \App\Livewire\Manufacturing\ProductionOrders\Index::class)
            ->name('production-orders.index')
            ->middleware('can:manufacturing.view');

        // TODO: Add Form and Show components later
        // Route::get('/production-orders/create', \App\Livewire\Manufacturing\ProductionOrders\Form::class)
        //     ->name('production-orders.create')
        //     ->middleware('can:manufacturing.create');
        // Route::get('/production-orders/{productionOrder}/edit', \App\Livewire\Manufacturing\ProductionOrders\Form::class)
        //     ->name('production-orders.edit')
        //     ->middleware('can:manufacturing.edit');
        // Route::get('/production-orders/{productionOrder}', \App\Livewire\Manufacturing\ProductionOrders\Show::class)
        //     ->name('production-orders.show')
        //     ->middleware('can:manufacturing.view');

        // Work Centers
        Route::get('/work-centers', \App\Livewire\Manufacturing\WorkCenters\Index::class)
            ->name('work-centers.index')
            ->middleware('can:manufacturing.view');

        // TODO: Add Form and Show components later
        // Route::get('/work-centers/create', \App\Livewire\Manufacturing\WorkCenters\Form::class)
        //     ->name('work-centers.create')
        //     ->middleware('can:manufacturing.create');
        // Route::get('/work-centers/{workCenter}/edit', \App\Livewire\Manufacturing\WorkCenters\Form::class)
        //     ->name('work-centers.edit')
        //     ->middleware('can:manufacturing.edit');
        // Route::get('/work-centers/{workCenter}', \App\Livewire\Manufacturing\WorkCenters\Show::class)
        //     ->name('work-centers.show')
        //     ->middleware('can:manufacturing.view');

        // TODO: Add Dashboard component later
        // Route::get('/dashboard', \App\Livewire\Manufacturing\Dashboard\Index::class)
        //     ->name('dashboard')
        //     ->middleware('can:manufacturing.view');
    });
});

// Scheduled reports manager
Route::middleware(['auth'])->group(function () {
    Route::get('/admin/reports/scheduled', ScheduledReportsManager::class)
        ->name('admin.reports.scheduled')
        ->middleware('can:reports.scheduled.manage');
});

// Report templates manager
Route::middleware(['auth'])->group(function () {
    Route::get('/admin/reports/templates', ReportTemplatesManager::class)
        ->name('admin.reports.templates')
        ->middleware('can:reports.templates.manage');
});

// Store dashboards & exports
Route::middleware(['auth'])->group(function () {
    Route::get('/admin/store/dashboard', OrdersDashboard::class)
        ->name('admin.store.dashboard')
        ->middleware('can:store.reports.dashboard');

    Route::get('/admin/stores', \App\Livewire\Admin\Store\Stores::class)
        ->name('admin.stores.index')
        ->middleware('can:stores.view');
});

Route::middleware(['auth'])->group(function () {
    Route::get('/admin/store/orders/export', StoreOrdersExportController::class)
        ->name('admin.store.orders.export')
        ->middleware('can:store.reports.dashboard');
});

// POS reports (charts)
Route::middleware(['auth'])->group(function () {
    Route::get('/admin/reports/pos-charts', PosChartsDashboard::class)
        ->name('admin.reports.pos.charts')
        ->middleware('can:reports.pos.charts');
});

Route::middleware(['auth'])->group(function () {
    Route::get('/admin/reports/inventory-charts', InventoryChartsDashboard::class)
        ->name('admin.reports.inventory.charts')
        ->middleware('can:reports.inventory.charts');
});

// Reports hub & exports
Route::middleware(['auth', 'can:reports.hub.view'])
    ->get('/admin/reports/hub', ReportsHub::class)
    ->name('admin.reports.hub');

Route::middleware(['auth', 'can:reports.pos.export'])
    ->get('/admin/reports/pos/export', \App\Http\Controllers\Admin\Reports\PosReportsExportController::class)
    ->name('admin.reports.pos.export');

Route::middleware(['auth', 'can:reports.inventory.export'])
    ->get('/admin/reports/inventory/export', \App\Http\Controllers\Admin\Reports\InventoryReportsExportController::class)
    ->name('admin.reports.inventory.export');

Route::middleware(['auth', 'can:reports.sales.view'])
    ->get('/reports/sales-analytics', \App\Livewire\Reports\SalesAnalytics::class)
    ->name('reports.sales-analytics');

// Low Stock Alerts
Route::middleware(['auth', 'can:inventory.stock.alerts.view'])
    ->get('/inventory/stock-alerts', \App\Livewire\Admin\Stock\LowStockAlerts::class)
    ->name('inventory.stock-alerts');

// Loyalty Program
Route::middleware(['auth', 'can:customers.loyalty.manage'])
    ->get('/loyalty', \App\Livewire\Admin\Loyalty\Index::class)
    ->name('loyalty.index');

// Installment Plans
Route::middleware(['auth', 'can:sales.installments.view'])
    ->get('/installments', \App\Livewire\Admin\Installments\Index::class)
    ->name('installments.index');

// Login Activity
Route::middleware(['auth', 'can:logs.login.view'])
    ->get('/admin/logs/login-activity', \App\Livewire\Admin\LoginActivity\Index::class)
    ->name('admin.logs.login-activity');

// User Preferences
Route::middleware(['auth'])
    ->get('/preferences', \App\Livewire\Admin\Settings\UserPreferences::class)
    ->name('preferences');

// Currency Rates Management
Route::middleware(['auth', 'can:settings.currency.manage'])
    ->get('/admin/settings/currency-rates', \App\Livewire\Admin\CurrencyRates::class)
    ->name('admin.settings.currency-rates');

// Currency Management (Add/Edit/Delete currencies)
Route::middleware(['auth', 'can:settings.currency.manage'])
    ->get('/admin/settings/currencies', \App\Livewire\Admin\CurrencyManager::class)
    ->name('admin.settings.currencies');

// Product Compatibility (Spare Parts)
Route::middleware(['auth', 'can:inventory.products.view'])
    ->get('/inventory/products/{product}/compatibility', \App\Livewire\Inventory\ProductCompatibility::class)
    ->name('inventory.products.compatibility');

// Scheduled Reports (new UI)
Route::middleware(['auth', 'can:reports.scheduled.manage'])
    ->get('/admin/reports/schedules', \App\Livewire\Reports\ScheduledReports::class)
    ->name('admin.reports.schedules');

// Vehicle Models Management (Spare Parts)
Route::middleware(['auth', 'can:spares.compatibility.manage'])
    ->get('/inventory/vehicle-models', \App\Livewire\Inventory\VehicleModels::class)
    ->name('inventory.vehicle-models');

// Product Categories Management
Route::middleware(['auth', 'can:inventory.categories.view'])
    ->get('/inventory/categories', \App\Livewire\Admin\Categories\Index::class)
    ->name('inventory.categories.index');

// Units of Measure Management
Route::middleware(['auth', 'can:inventory.units.view'])
    ->get('/inventory/units', \App\Livewire\Admin\UnitsOfMeasure\Index::class)
    ->name('inventory.units.index');

// Barcode/QR Printing
Route::middleware(['auth', 'can:inventory.products.view'])
    ->get('/inventory/barcode-print', \App\Livewire\Inventory\BarcodePrint::class)
    ->name('inventory.barcode-print');

// Item History
Route::middleware(['auth', 'can:inventory.products.view'])
    ->get('/inventory/products/{product}/history', \App\Livewire\Inventory\ProductHistory::class)
    ->name('inventory.products.history');
