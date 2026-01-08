<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * Service for managing role templates and creating pre-configured roles
 */
class RoleTemplateService
{
    /**
     * Get available role templates
     */
    public function getTemplates(): array
    {
        return [
            'super_admin' => [
                'name' => 'Super Administrator',
                'name_ar' => 'مدير النظام الأعلى',
                'description' => 'Full system access with all permissions',
                'description_ar' => 'صلاحيات كاملة على النظام',
                'permissions' => ['*'], // All permissions
                'guard_name' => 'web',
            ],
            'admin' => [
                'name' => 'Administrator',
                'name_ar' => 'مدير النظام',
                'description' => 'System administrator with most permissions',
                'description_ar' => 'مدير النظام بمعظم الصلاحيات',
                'permissions' => $this->getAdminPermissions(),
                'guard_name' => 'web',
            ],
            'branch_manager' => [
                'name' => 'Branch Manager',
                'name_ar' => 'مدير الفرع',
                'description' => 'Manages all operations within a branch',
                'description_ar' => 'إدارة جميع العمليات داخل الفرع',
                'permissions' => $this->getBranchManagerPermissions(),
                'guard_name' => 'web',
            ],
            'sales_manager' => [
                'name' => 'Sales Manager',
                'name_ar' => 'مدير المبيعات',
                'description' => 'Manages sales operations and team',
                'description_ar' => 'إدارة عمليات المبيعات والفريق',
                'permissions' => $this->getSalesManagerPermissions(),
                'guard_name' => 'web',
            ],
            'cashier' => [
                'name' => 'Cashier',
                'name_ar' => 'كاشير',
                'description' => 'Handles point of sale transactions',
                'description_ar' => 'يتعامل مع نقطة البيع',
                'permissions' => $this->getCashierPermissions(),
                'guard_name' => 'web',
            ],
            'sales_user' => [
                'name' => 'Sales User',
                'name_ar' => 'موظف مبيعات',
                'description' => 'Creates and manages sales orders',
                'description_ar' => 'إنشاء وإدارة طلبات المبيعات',
                'permissions' => $this->getSalesUserPermissions(),
                'guard_name' => 'web',
            ],
            'warehouse_manager' => [
                'name' => 'Warehouse Manager',
                'name_ar' => 'مدير المستودع',
                'description' => 'Manages inventory and warehouse operations',
                'description_ar' => 'إدارة المخزون والمستودع',
                'permissions' => $this->getWarehouseManagerPermissions(),
                'guard_name' => 'web',
            ],
            'inventory_clerk' => [
                'name' => 'Inventory Clerk',
                'name_ar' => 'موظف مخزون',
                'description' => 'Handles inventory transactions',
                'description_ar' => 'يتعامل مع حركات المخزون',
                'permissions' => $this->getInventoryClerkPermissions(),
                'guard_name' => 'web',
            ],
            'accountant' => [
                'name' => 'Accountant',
                'name_ar' => 'محاسب',
                'description' => 'Manages financial records and accounting',
                'description_ar' => 'إدارة السجلات المالية والمحاسبة',
                'permissions' => $this->getAccountantPermissions(),
                'guard_name' => 'web',
            ],
            'hr_manager' => [
                'name' => 'HR Manager',
                'name_ar' => 'مدير الموارد البشرية',
                'description' => 'Manages human resources and payroll',
                'description_ar' => 'إدارة الموارد البشرية والرواتب',
                'permissions' => $this->getHRManagerPermissions(),
                'guard_name' => 'web',
            ],
            'employee' => [
                'name' => 'Employee',
                'name_ar' => 'موظف',
                'description' => 'Basic employee with self-service access',
                'description_ar' => 'موظف عادي بصلاحيات الخدمة الذاتية',
                'permissions' => $this->getEmployeePermissions(),
                'guard_name' => 'web',
            ],
            'viewer' => [
                'name' => 'Viewer',
                'name_ar' => 'مستعرض',
                'description' => 'Read-only access to reports and data',
                'description_ar' => 'الاطلاع فقط على التقارير والبيانات',
                'permissions' => $this->getViewerPermissions(),
                'guard_name' => 'web',
            ],
        ];
    }

    /**
     * Create role from template
     */
    public function createFromTemplate(string $templateKey, ?string $customName = null): Role
    {
        $templates = $this->getTemplates();

        if (! isset($templates[$templateKey])) {
            throw new \InvalidArgumentException("Template '{$templateKey}' not found");
        }

        $template = $templates[$templateKey];
        $roleName = $customName ?? $template['name'];

        return DB::transaction(function () use ($roleName, $template) {
            // Create role
            $role = Role::create([
                'name' => $roleName,
                'guard_name' => $template['guard_name'],
            ]);

            // Assign permissions
            $this->assignPermissionsToRole($role, $template['permissions']);

            return $role;
        });
    }

    /**
     * Assign permissions to role
     */
    protected function assignPermissionsToRole(Role $role, array $permissions): void
    {
        if (in_array('*', $permissions)) {
            // Assign all permissions
            $allPermissions = Permission::all();
            $role->syncPermissions($allPermissions);
        } else {
            // Assign specific permissions
            $permissionModels = [];
            foreach ($permissions as $permissionName) {
                // Support wildcard patterns
                if (str_contains($permissionName, '*')) {
                    $pattern = str_replace('*', '%', $permissionName);
                    $matchingPermissions = Permission::where('name', 'like', $pattern)->get();
                    $permissionModels = array_merge($permissionModels, $matchingPermissions->all());
                } else {
                    $permission = Permission::where('name', $permissionName)->first();
                    if ($permission) {
                        $permissionModels[] = $permission;
                    }
                }
            }
            $role->syncPermissions($permissionModels);
        }
    }

    /**
     * Get admin permissions
     */
    protected function getAdminPermissions(): array
    {
        return [
            // Admin
            'settings.*',
            'users.*',
            'roles.*',
            'branches.*',
            'modules.*',
            'logs.*',
            
            // All business operations
            'dashboard.*',
            'sales.*',
            'purchases.*',
            'inventory.*',
            'warehouse.*',
            'customers.*',
            'suppliers.*',
            'accounting.*',
            'expenses.*',
            'income.*',
            'hrm.*',
            'rental.*',
            'reports.*',
            'pos.*',
            'manufacturing.*',
            'banking.*',
            'fixed-assets.*',
            'projects.*',
            'documents.*',
            'helpdesk.*',
        ];
    }

    /**
     * Get branch manager permissions
     */
    protected function getBranchManagerPermissions(): array
    {
        return [
            // Dashboard
            'dashboard.view',
            
            // Sales & POS
            'pos.*',
            'sales.*',
            'customers.*',
            
            // Inventory
            'inventory.products.view',
            'inventory.stock.*',
            'warehouse.*',
            
            // Purchases
            'purchases.view',
            'purchases.create',
            'purchases.update',
            'suppliers.view',
            
            // Financial
            'expenses.view',
            'expenses.create',
            'income.view',
            
            // HR
            'hrm.employees.view',
            'hrm.attendance.*',
            
            // Reports
            'reports.view',
            'branch.reports.view',
            
            // Branch settings
            'branch.settings.manage',
            'branch.employees.manage',
        ];
    }

    /**
     * Get sales manager permissions
     */
    protected function getSalesManagerPermissions(): array
    {
        return [
            'dashboard.view',
            'sales.*',
            'customers.*',
            'inventory.products.view',
            'pos.*',
            'reports.view',
            'sales.view-reports',
        ];
    }

    /**
     * Get cashier permissions
     */
    protected function getCashierPermissions(): array
    {
        return [
            'pos.use',
            'pos.create-sale',
            'pos.daily-report.view',
            'sales.view',
            'customers.view',
            'customers.create',
            'inventory.products.view',
        ];
    }

    /**
     * Get sales user permissions
     */
    protected function getSalesUserPermissions(): array
    {
        return [
            'dashboard.view',
            'sales.view',
            'sales.create',
            'sales.update',
            'customers.view',
            'customers.create',
            'inventory.products.view',
        ];
    }

    /**
     * Get warehouse manager permissions
     */
    protected function getWarehouseManagerPermissions(): array
    {
        return [
            'dashboard.view',
            'inventory.*',
            'warehouse.*',
            'purchases.view',
            'stock.movements.*',
            'stock.transfers.*',
            'stock.adjustments.*',
            'suppliers.view',
            'reports.view',
            'inventory.view-reports',
        ];
    }

    /**
     * Get inventory clerk permissions
     */
    protected function getInventoryClerkPermissions(): array
    {
        return [
            'inventory.products.view',
            'inventory.stock.view',
            'warehouse.view',
            'stock.movements.view',
            'stock.movements.create',
        ];
    }

    /**
     * Get accountant permissions
     */
    protected function getAccountantPermissions(): array
    {
        return [
            'dashboard.view',
            'accounting.*',
            'expenses.*',
            'income.*',
            'banking.*',
            'sales.view',
            'purchases.view',
            'reports.view',
            'reports.financial',
        ];
    }

    /**
     * Get HR manager permissions
     */
    protected function getHRManagerPermissions(): array
    {
        return [
            'dashboard.view',
            'hrm.*',
            'employees.*',
            'attendance.*',
            'payroll.*',
            'leave.*',
            'reports.view',
            'hrm.view-reports',
        ];
    }

    /**
     * Get employee permissions
     */
    protected function getEmployeePermissions(): array
    {
        return [
            'dashboard.view',
            'employee.self.attendance',
            'employee.self.leave-request',
            'employee.self.payslip-view',
            'employee.self.profile',
        ];
    }

    /**
     * Get viewer permissions
     */
    protected function getViewerPermissions(): array
    {
        return [
            'dashboard.view',
            'sales.view',
            'inventory.products.view',
            'customers.view',
            'reports.view',
        ];
    }

    /**
     * Get template details
     */
    public function getTemplateDetails(string $templateKey): ?array
    {
        $templates = $this->getTemplates();

        return $templates[$templateKey] ?? null;
    }

    /**
     * Compare role with template
     */
    public function compareRoleWithTemplate(Role $role, string $templateKey): array
    {
        $template = $this->getTemplateDetails($templateKey);

        if (! $template) {
            return [
                'error' => 'Template not found',
            ];
        }

        $rolePermissions = $role->permissions->pluck('name')->toArray();
        $templatePermissions = $this->expandPermissions($template['permissions']);

        return [
            'matching' => array_intersect($rolePermissions, $templatePermissions),
            'missing_from_role' => array_diff($templatePermissions, $rolePermissions),
            'extra_in_role' => array_diff($rolePermissions, $templatePermissions),
            'match_percentage' => empty($templatePermissions) ? 0 : 
                round((count(array_intersect($rolePermissions, $templatePermissions)) / count($templatePermissions)) * 100, 2),
        ];
    }

    /**
     * Expand wildcard permissions
     */
    protected function expandPermissions(array $permissions): array
    {
        if (in_array('*', $permissions)) {
            return Permission::all()->pluck('name')->toArray();
        }

        $expanded = [];
        $wildcardPatterns = [];
        
        // Separate wildcards and regular permissions
        foreach ($permissions as $permission) {
            if (str_contains($permission, '*')) {
                $wildcardPatterns[] = str_replace('*', '%', $permission);
            } else {
                $expanded[] = $permission;
            }
        }
        
        // Batch query for all wildcard patterns
        if (!empty($wildcardPatterns)) {
            $query = Permission::query();
            foreach ($wildcardPatterns as $index => $pattern) {
                if ($index === 0) {
                    $query->where('name', 'like', $pattern);
                } else {
                    $query->orWhere('name', 'like', $pattern);
                }
            }
            $matching = $query->pluck('name')->toArray();
            $expanded = array_merge($expanded, $matching);
        }

        return array_unique($expanded);
    }
}
