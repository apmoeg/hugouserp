<?php

use App\Http\Controllers\Admin\AuditLogController;
// ===== Controllers =====
use App\Http\Controllers\Admin\BranchController;
use App\Http\Controllers\Admin\BranchModuleController;
use App\Http\Controllers\Admin\HrmCentral\AttendanceController as CentralAttendanceController;
use App\Http\Controllers\Admin\HrmCentral\EmployeeController as CentralEmployeeController;
use App\Http\Controllers\Admin\HrmCentral\LeaveController as CentralLeaveController;
use App\Http\Controllers\Admin\HrmCentral\PayrollController as CentralPayrollController;
use App\Http\Controllers\Admin\ModuleCatalogController;
use App\Http\Controllers\Admin\ModuleFieldController;
use App\Http\Controllers\Admin\PermissionController;
use App\Http\Controllers\Admin\ReportsController as AdminReportsController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\SystemSettingController;
use App\Http\Controllers\Admin\UserController;
use Illuminate\Support\Facades\Route;

// ========== Admin namespace (خاضعة بالفعل لـ api-core + api-auth + impersonate من api.php) ==========
Route::prefix('admin')->group(function () {

    // -------------------- Branches --------------------
    Route::prefix('branches')->group(function () {
        Route::get('/', [BranchController::class, 'index'])->middleware('perm:branches.view');
        Route::post('/', [BranchController::class, 'store'])->middleware('perm:branches.create');
        Route::get('{branch}', [BranchController::class, 'show'])->middleware('perm:branches.view');
        Route::match(['put', 'patch'], '{branch}', [BranchController::class, 'update'])->middleware('perm:branches.update');
        Route::delete('{branch}', [BranchController::class, 'destroy'])->middleware('perm:branches.delete');

        // ✅ مضاف حسب الجدول: archive
        Route::post('{branch}/archive', [BranchController::class, 'archive'])->middleware('perm:branches.archive');
    });

    // -------------------- Modules Catalog --------------------
    Route::prefix('modules')->group(function () {
        // CRUD كاملة (لتغطية النقص في الثاني)
        Route::get('/', [ModuleCatalogController::class, 'index'])->middleware('perm:modules.view');
        Route::post('/', [ModuleCatalogController::class, 'store'])->middleware('perm:modules.create');
        Route::get('{module}', [ModuleCatalogController::class, 'show'])->middleware('perm:modules.view');
        Route::match(['put', 'patch'], '{module}', [ModuleCatalogController::class, 'update'])->middleware('perm:modules.update');
        Route::delete('{module}', [ModuleCatalogController::class, 'destroy'])->middleware('perm:modules.delete');
    });

    // -------------------- Branch ⇄ Modules --------------------
    Route::prefix('branch-modules')->group(function () {
        Route::get('/', [BranchModuleController::class, 'index'])->middleware('perm:branch.modules.view');
        Route::post('/', [BranchModuleController::class, 'attach'])->middleware('perm:branch.modules.attach');
        Route::delete('/', [BranchModuleController::class, 'detach'])->middleware('perm:branch.modules.detach');

        // ✅ مضاف: updateSettings (ناقص في الأول)
        Route::post('update-settings', [BranchModuleController::class, 'updateSettings'])
            ->middleware('perm:branch.modules.settings');

        // ✅ مسارات مُسطّحة توافقية قديمة (ناقص في الثاني)
        Route::post('enable', [BranchModuleController::class, 'enable'])->middleware('perm:branch.modules.attach');
        Route::post('disable', [BranchModuleController::class, 'disable'])->middleware('perm:branch.modules.detach');
    });

    // -------------------- Module Fields --------------------
    Route::prefix('module-fields')->group(function () {
        Route::get('/', [ModuleFieldController::class, 'index'])->middleware('perm:module.fields.manage');
        Route::post('/', [ModuleFieldController::class, 'store'])->middleware('perm:module.fields.manage');
        Route::get('{field}', [ModuleFieldController::class, 'show'])->middleware('perm:module.fields.manage');     // ✅ تأكيد show
        Route::match(['put', 'patch'], '{field}', [ModuleFieldController::class, 'update'])->middleware('perm:module.fields.manage');
        Route::delete('{field}', [ModuleFieldController::class, 'destroy'])->middleware('perm:module.fields.manage');

        // ✅ مضاف: reorder (ناقص في الأول)
        Route::post('reorder', [ModuleFieldController::class, 'reorder'])->middleware('perm:module.fields.manage');
    });

    // -------------------- Users --------------------
    Route::apiResource('users', UserController::class)->middleware('perm:users.manage');

    // ✅ مضاف: activate / deactivate / reset-password (ناقص في الأول)
    Route::post('users/{user}/activate', [UserController::class, 'activate'])->middleware('perm:users.activate');
    Route::post('users/{user}/deactivate', [UserController::class, 'deactivate'])->middleware('perm:users.deactivate');
    Route::post('users/{user}/reset-password', [UserController::class, 'resetPassword'])->middleware('perm:users.reset.password');

    // -------------------- Roles & Permissions --------------------
    Route::apiResource('roles', RoleController::class)->middleware('perm:roles.manage');
    Route::apiResource('permissions', PermissionController::class)->middleware('perm:permissions.manage');

    // ✅ مضاف: syncPermissions للـ Role (ناقص في الأول)
    Route::post('roles/{role}/sync-permissions', [RoleController::class, 'syncPermissions'])->middleware('perm:roles.sync.permissions');

    // -------------------- System Settings --------------------
    Route::get('settings', [SystemSettingController::class, 'index'])->middleware('perm:settings.view');
    // ✅ POST update (مطلوب في الثاني)
    Route::post('settings', [SystemSettingController::class, 'update'])->middleware('perm:settings.update');

    // -------------------- Audit Logs --------------------
    Route::get('audit-logs', [AuditLogController::class, 'index'])->middleware('perm:audit.view');
    // ✅ مضاف: show (ناقص في الثاني)
    Route::get('audit-logs/{id}', [AuditLogController::class, 'show'])->middleware('perm:audit.view');

    // -------------------- Reports --------------------
    Route::prefix('reports')->group(function () {
        // ✅ usage/performance/errors (كانت عندنا — مطلوبة في الثاني)
        Route::get('usage', [AdminReportsController::class, 'usage'])->middleware('perm:reports.admin');
        Route::get('performance', [AdminReportsController::class, 'performance'])->middleware('perm:reports.admin');
        Route::get('errors', [AdminReportsController::class, 'errors'])->middleware('perm:reports.admin');

        // ✅ إضافة “جميع التقارير المالية” (ناقص في الأول)
        Route::get('finance/sales', [AdminReportsController::class, 'financeSales'])->middleware('perm:reports.finance');
        Route::get('finance/purchases', [AdminReportsController::class, 'financePurchases'])->middleware('perm:reports.finance');
        Route::get('finance/pnl', [AdminReportsController::class, 'financePnl'])->middleware('perm:reports.finance');
        Route::get('finance/cashflow', [AdminReportsController::class, 'financeCashflow'])->middleware('perm:reports.finance');
        Route::get('finance/aging', [AdminReportsController::class, 'financeAging'])->middleware('perm:reports.finance');
    });

    // -------------------- HRM (Central) --------------------
    Route::prefix('hrm')->group(function () {

        // Employees (central)
        Route::get('employees', [CentralEmployeeController::class, 'index'])->middleware('perm:hrm.central.view');
        Route::get('employees/{employee}', [CentralEmployeeController::class, 'show'])->middleware('perm:hrm.central.view');

        // Attendance (central) — قراءة + إنشاء/تحديث/تعطيل (مطلوب حسب الجدول)
        Route::get('attendance', [CentralAttendanceController::class, 'index'])->middleware('perm:hrm.central.view');
        Route::post('attendance', [CentralAttendanceController::class, 'store'])->middleware('perm:hrm.central.manage');          // ✅ create
        Route::match(['put', 'patch'], 'attendance/{attendance}', [CentralAttendanceController::class, 'update'])->middleware('perm:hrm.central.manage'); // ✅ update
        Route::post('attendance/{attendance}/deactivate', [CentralAttendanceController::class, 'deactivate'])->middleware('perm:hrm.central.manage'); // ✅ deactivate

        // Payroll (central)
        Route::get('payroll', [CentralPayrollController::class, 'index'])->middleware('perm:hrm.central.view');
        Route::get('payroll/{payroll}', [CentralPayrollController::class, 'show'])->middleware('perm:hrm.central.view');
        Route::post('payroll/run', [CentralPayrollController::class, 'run'])->middleware('perm:hrm.central.run');
        Route::post('payroll/{payroll}/approve', [CentralPayrollController::class, 'approve'])->middleware('perm:hrm.central.approve');
        Route::post('payroll/{payroll}/pay', [CentralPayrollController::class, 'pay'])->middleware('perm:hrm.central.pay');

        // Leaves (central)
        Route::get('leaves', [CentralLeaveController::class, 'index'])->middleware('perm:hrm.central.view');
        Route::post('leaves/{leave}/approve', [CentralLeaveController::class, 'approve'])->middleware('perm:hrm.central.approve');
        Route::post('leaves/{leave}/reject', [CentralLeaveController::class, 'reject'])->middleware('perm:hrm.central.approve');
    });
});
