<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Models\Branch;
use App\Models\BranchAdmin;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class BranchPermissionsTest extends TestCase
{
    use RefreshDatabase;

    protected Branch $branch;
    protected User $superAdmin;
    protected User $branchAdmin;
    protected User $branchEmployee;

    protected function setUp(): void
    {
        parent::setUp();

        // Create branch
        $this->branch = Branch::factory()->create([
            'name' => 'Test Branch',
            'code' => 'TB001',
            'is_active' => true,
        ]);

        // Create permissions
        $permissions = [
            'dashboard.view',
            'branch.switch',
            'branch.data.view-all',
            'branch.employees.manage',
            'branch.settings.manage',
            'branch.reports.view',
            'employee.self.attendance',
            'employee.self.leave-request',
            'employee.self.payslip-view',
            'employee.self.profile-edit',
            'pos.use',
            'sales.view',
        ];

        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        // Create roles
        $superAdminRole = Role::findOrCreate('Super Admin', 'web');
        $superAdminRole->syncPermissions(Permission::where('guard_name', 'web')->get());

        $branchAdminRole = Role::findOrCreate('Branch Admin', 'web');
        $branchAdminRole->syncPermissions(['dashboard.view', 'branch.employees.manage', 'branch.settings.manage', 'branch.reports.view']);

        $branchEmployeeRole = Role::findOrCreate('Branch Employee', 'web');
        $branchEmployeeRole->syncPermissions(['dashboard.view', 'employee.self.attendance', 'employee.self.leave-request', 'employee.self.payslip-view', 'employee.self.profile-edit']);

        // Create users
        $this->superAdmin = User::factory()->create([
            'email' => 'super@test.com',
            'branch_id' => $this->branch->id,
            'is_active' => true,
        ]);
        $this->superAdmin->assignRole($superAdminRole);

        $this->branchAdmin = User::factory()->create([
            'email' => 'admin@test.com',
            'branch_id' => $this->branch->id,
            'is_active' => true,
        ]);
        $this->branchAdmin->assignRole($branchAdminRole);

        // Create BranchAdmin record
        BranchAdmin::create([
            'branch_id' => $this->branch->id,
            'user_id' => $this->branchAdmin->id,
            'can_manage_users' => true,
            'can_manage_roles' => false,
            'can_view_reports' => true,
            'can_export_data' => true,
            'can_manage_settings' => true,
            'is_primary' => true,
            'is_active' => true,
        ]);

        $this->branchEmployee = User::factory()->create([
            'email' => 'employee@test.com',
            'branch_id' => $this->branch->id,
            'is_active' => true,
        ]);
        $this->branchEmployee->assignRole($branchEmployeeRole);
    }

    public function test_super_admin_has_all_permissions(): void
    {
        $this->assertTrue($this->superAdmin->can('branch.switch'));
        $this->assertTrue($this->superAdmin->can('branch.data.view-all'));
        $this->assertTrue($this->superAdmin->can('branch.employees.manage'));
        $this->assertTrue($this->superAdmin->can('branch.settings.manage'));
    }

    public function test_branch_admin_has_limited_permissions(): void
    {
        $this->assertTrue($this->branchAdmin->can('dashboard.view'));
        $this->assertTrue($this->branchAdmin->can('branch.employees.manage'));
        $this->assertTrue($this->branchAdmin->can('branch.settings.manage'));
        $this->assertTrue($this->branchAdmin->can('branch.reports.view'));
        $this->assertFalse($this->branchAdmin->can('branch.switch'));
    }

    public function test_branch_employee_has_self_service_permissions(): void
    {
        $this->assertTrue($this->branchEmployee->can('dashboard.view'));
        $this->assertTrue($this->branchEmployee->can('employee.self.attendance'));
        $this->assertTrue($this->branchEmployee->can('employee.self.leave-request'));
        $this->assertTrue($this->branchEmployee->can('employee.self.payslip-view'));
        $this->assertFalse($this->branchEmployee->can('branch.employees.manage'));
    }

    public function test_user_is_branch_admin(): void
    {
        $this->assertTrue($this->branchAdmin->isBranchAdmin());
        $this->assertFalse($this->branchEmployee->isBranchAdmin());
    }

    public function test_user_can_manage_branch_users(): void
    {
        $this->assertTrue($this->superAdmin->canManageBranchUsers());
        $this->assertTrue($this->branchAdmin->canManageBranchUsers());
        $this->assertFalse($this->branchEmployee->canManageBranchUsers());
    }

    public function test_user_can_view_branch_reports(): void
    {
        $this->assertTrue($this->superAdmin->canViewBranchReports());
        $this->assertTrue($this->branchAdmin->canViewBranchReports());
    }

    public function test_user_can_manage_branch_settings(): void
    {
        $this->assertTrue($this->superAdmin->canManageBranchSettings());
        $this->assertTrue($this->branchAdmin->canManageBranchSettings());
        $this->assertFalse($this->branchEmployee->canManageBranchSettings());
    }

    public function test_branch_has_user(): void
    {
        $this->assertTrue($this->branch->hasUser($this->branchAdmin));
        $this->assertTrue($this->branch->hasUser($this->branchEmployee));
        
        // Create user in different branch
        $otherBranch = Branch::factory()->create(['code' => 'OB001']);
        $otherUser = User::factory()->create(['branch_id' => $otherBranch->id]);
        
        $this->assertFalse($this->branch->hasUser($otherUser));
    }

    public function test_branch_user_has_permission_in_branch(): void
    {
        // Super Admin should always have permission
        $this->assertTrue($this->branch->userHasPermissionInBranch($this->superAdmin, 'branch.employees.manage'));
        
        // Branch Admin with can_manage_users should have permission
        $this->assertTrue($this->branch->userHasPermissionInBranch($this->branchAdmin, 'branch.employees.manage'));
        
        // Regular employee without branch admin record should not have permission
        $this->assertFalse($this->branch->userHasPermissionInBranch($this->branchEmployee, 'branch.employees.manage'));
    }

    public function test_branch_active_employees_scope(): void
    {
        $employees = $this->branch->activeEmployees()->get();
        
        $this->assertGreaterThanOrEqual(3, $employees->count());
        $this->assertTrue($employees->contains($this->superAdmin));
        $this->assertTrue($employees->contains($this->branchAdmin));
        $this->assertTrue($employees->contains($this->branchEmployee));
    }

    public function test_get_branch_admin_record(): void
    {
        $record = $this->branchAdmin->getBranchAdminRecord();
        
        $this->assertNotNull($record);
        $this->assertTrue($record->can_manage_users);
        $this->assertTrue($record->can_view_reports);
        $this->assertTrue($record->can_manage_settings);
        $this->assertFalse($record->can_manage_roles);
    }

    public function test_is_branch_employee(): void
    {
        $this->assertTrue($this->branchEmployee->isBranchEmployee());
        $this->assertFalse($this->branchAdmin->isBranchEmployee());
        $this->assertFalse($this->superAdmin->isBranchEmployee());
    }

    public function test_employees_count_attribute(): void
    {
        $count = $this->branch->employees_count;
        
        $this->assertGreaterThanOrEqual(3, $count);
    }
}
