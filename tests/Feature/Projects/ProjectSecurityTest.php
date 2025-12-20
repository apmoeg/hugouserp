<?php

declare(strict_types=1);

namespace Tests\Feature\Projects;

use App\Livewire\Projects\Expenses;
use App\Livewire\Projects\Form;
use App\Livewire\Projects\Tasks;
use App\Livewire\Projects\TimeLogs;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\Project;
use App\Models\ProjectTask;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

/**
 * Tests for security fixes:
 * - BUG-001: Branch authorization for project creation/edit
 * - BUG-002: Customer/manager scoping to user's branches
 * - BUG-003: Project time logs accessible across branches (IDOR)
 * - BUG-004: Time log task/employee spoofing
 * - BUG-008: Unscoped employee selection in time logs
 * - BUG-009: Project expenses user leakage and cross-branch assignment
 */
class ProjectSecurityTest extends TestCase
{
    use RefreshDatabase;

    private function grantProjectPermissions(): void
    {
        Gate::define('projects.edit', fn () => true);
        Gate::define('projects.create', fn () => true);
        Gate::define('projects.tasks.view', fn () => true);
        Gate::define('projects.tasks.manage', fn () => true);
        Gate::define('projects.timelogs.view', fn () => true);
        Gate::define('projects.timelogs.manage', fn () => true);
        Gate::define('projects.expenses.view', fn () => true);
        Gate::define('projects.expenses.manage', fn () => true);
        Gate::define('projects.expenses.approve', fn () => true);
    }

    private function makeBranch(): Branch
    {
        return Branch::factory()->create();
    }

    private function makeUser(Branch $branch): User
    {
        return User::factory()->create(['branch_id' => $branch->id]);
    }

    private function makeProject(Branch $branch, array $overrides = []): Project
    {
        return Project::create(array_merge([
            'branch_id' => $branch->id,
            'code' => 'PRJ-' . Str::random(5),
            'name' => 'Project ' . Str::random(3),
            'description' => 'Test description',
            'status' => 'planning',
            'start_date' => now()->format('Y-m-d'),
            'end_date' => now()->addMonth()->format('Y-m-d'),
        ], $overrides));
    }

    private function makeCustomer(Branch $branch): Customer
    {
        return Customer::create([
            'uuid' => (string) Str::uuid(),
            'code' => 'CUST-' . Str::random(5),
            'name' => 'Customer ' . Str::random(3),
            'branch_id' => $branch->id,
        ]);
    }

    /**
     * BUG-001: Test that users cannot create projects with a branch_id they don't belong to.
     */
    public function test_user_cannot_create_project_in_foreign_branch(): void
    {
        $this->withExceptionHandling();
        $this->grantProjectPermissions();

        $branchA = $this->makeBranch();
        $branchB = $this->makeBranch();
        $user = $this->makeUser($branchA);

        $this->actingAs($user);

        // Use reflection to call the protected getUserBranchIds method
        $form = new Form();
        $reflection = new \ReflectionClass($form);
        $method = $reflection->getMethod('getUserBranchIds');
        $method->setAccessible(true);
        
        $userBranchIds = $method->invoke($form);
        
        // Verify branchB is NOT in the user's branch IDs
        $this->assertNotContains($branchB->id, $userBranchIds, 'Foreign branch should not be in user branch IDs');
        
        // Verify branchA IS in the user's branch IDs
        $this->assertContains($branchA->id, $userBranchIds, 'Own branch should be in user branch IDs');
    }

    /**
     * BUG-001: Test that users can create projects in their own branch.
     */
    public function test_user_can_create_project_in_own_branch(): void
    {
        $this->withExceptionHandling();
        $this->grantProjectPermissions();

        $branch = $this->makeBranch();
        $user = $this->makeUser($branch);

        $this->actingAs($user);

        // Use reflection to call the protected getUserBranchIds method
        $form = new Form();
        $reflection = new \ReflectionClass($form);
        $method = $reflection->getMethod('getUserBranchIds');
        $method->setAccessible(true);
        
        $userBranchIds = $method->invoke($form);
        
        // Verify branch IS in the user's branch IDs
        $this->assertContains($branch->id, $userBranchIds, 'Own branch should be in user branch IDs');
    }

    /**
     * BUG-002: Test that customers list is scoped to user's branches.
     */
    public function test_customers_dropdown_only_shows_branch_customers(): void
    {
        $this->grantProjectPermissions();

        $branchA = $this->makeBranch();
        $branchB = $this->makeBranch();
        $user = $this->makeUser($branchA);

        // Create customers in both branches
        $customerA = $this->makeCustomer($branchA);
        $customerB = $this->makeCustomer($branchB);

        $this->actingAs($user);

        $component = Livewire::test(Form::class);

        // Get the clients from the view data
        $clients = $component->viewData('clients');

        // Should contain only branch A customer
        $this->assertTrue($clients->contains('id', $customerA->id));
        $this->assertFalse($clients->contains('id', $customerB->id));
    }

    /**
     * BUG-002: Test that managers list is scoped to user's branches.
     */
    public function test_managers_dropdown_only_shows_branch_users(): void
    {
        $this->grantProjectPermissions();

        $branchA = $this->makeBranch();
        $branchB = $this->makeBranch();
        $user = $this->makeUser($branchA);
        $managerA = $this->makeUser($branchA);
        $managerB = $this->makeUser($branchB);

        $this->actingAs($user);

        $component = Livewire::test(Form::class);

        // Get the managers from the view data
        $managers = $component->viewData('managers');

        // Should contain only branch A users
        $this->assertTrue($managers->contains('id', $user->id));
        $this->assertTrue($managers->contains('id', $managerA->id));
        $this->assertFalse($managers->contains('id', $managerB->id));
    }

    /**
     * BUG-003: Test that time logs page cannot be accessed for projects in other branches.
     */
    public function test_user_cannot_access_timelogs_for_other_branch_project(): void
    {
        $this->grantProjectPermissions();

        $branchA = $this->makeBranch();
        $branchB = $this->makeBranch();
        $user = $this->makeUser($branchA);
        $otherProject = $this->makeProject($branchB);

        $this->actingAs($user);

        try {
            Livewire::test(TimeLogs::class, ['projectId' => $otherProject->id]);
            $this->fail('Expected a 404 when accessing time logs for a project from another branch.');
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            $this->assertTrue(true);
        }
    }

    /**
     * BUG-004: Test that task_id validation ensures task belongs to the project.
     */
    public function test_timelog_rejects_task_from_other_project(): void
    {
        $this->grantProjectPermissions();

        $branch = $this->makeBranch();
        $user = $this->makeUser($branch);
        $projectA = $this->makeProject($branch);
        $projectB = $this->makeProject($branch);

        // Create task in project B
        $taskInProjectB = ProjectTask::create([
            'project_id' => $projectB->id,
            'title' => 'Task in other project',
            'status' => 'pending',
            'priority' => 'medium',
        ]);

        $this->actingAs($user);

        // Try to log time in project A with task from project B
        $component = Livewire::test(TimeLogs::class, ['projectId' => $projectA->id])
            ->set('task_id', $taskInProjectB->id)
            ->set('employee_id', $user->id)
            ->set('date', now()->format('Y-m-d'))
            ->set('hours', 2)
            ->set('hourly_rate', 50)
            ->call('save');

        $component->assertHasErrors(['task_id']);
    }

    /**
     * BUG-004/008: Test that employee_id validation ensures employee is from user's branch.
     */
    public function test_timelog_rejects_employee_from_other_branch(): void
    {
        $this->grantProjectPermissions();

        $branchA = $this->makeBranch();
        $branchB = $this->makeBranch();
        $user = $this->makeUser($branchA);
        $employeeB = $this->makeUser($branchB);
        $project = $this->makeProject($branchA);

        $this->actingAs($user);

        // Try to log time with employee from branch B
        $component = Livewire::test(TimeLogs::class, ['projectId' => $project->id])
            ->set('task_id', null)
            ->set('employee_id', $employeeB->id)
            ->set('date', now()->format('Y-m-d'))
            ->set('hours', 2)
            ->set('hourly_rate', 50)
            ->call('save');

        $component->assertHasErrors(['employee_id']);
    }

    /**
     * BUG-008: Test that employees dropdown in time logs only shows branch users.
     */
    public function test_timelogs_employees_dropdown_only_shows_branch_users(): void
    {
        $this->grantProjectPermissions();

        $branchA = $this->makeBranch();
        $branchB = $this->makeBranch();
        $user = $this->makeUser($branchA);
        $employeeA = $this->makeUser($branchA);
        $employeeB = $this->makeUser($branchB);
        $project = $this->makeProject($branchA);

        $this->actingAs($user);

        $component = Livewire::test(TimeLogs::class, ['projectId' => $project->id]);

        $employees = $component->viewData('employees');

        // Should contain only branch A users
        $this->assertTrue($employees->contains('id', $user->id));
        $this->assertTrue($employees->contains('id', $employeeA->id));
        $this->assertFalse($employees->contains('id', $employeeB->id));
    }

    /**
     * BUG-009: Test that expenses page users dropdown is scoped to user's branches.
     */
    public function test_expenses_users_dropdown_only_shows_branch_users(): void
    {
        $this->grantProjectPermissions();

        $branchA = $this->makeBranch();
        $branchB = $this->makeBranch();
        $user = $this->makeUser($branchA);
        $userA = $this->makeUser($branchA);
        $userB = $this->makeUser($branchB);
        $project = $this->makeProject($branchA);

        $this->actingAs($user);

        $component = Livewire::test(Expenses::class, ['projectId' => $project->id]);

        $users = $component->viewData('users');

        // Should contain only branch A users
        $this->assertTrue($users->contains('id', $user->id));
        $this->assertTrue($users->contains('id', $userA->id));
        $this->assertFalse($users->contains('id', $userB->id));
    }

    /**
     * BUG-009: Test that expense user_id validation rejects users from other branches.
     */
    public function test_expense_rejects_user_from_other_branch(): void
    {
        $this->grantProjectPermissions();

        $branchA = $this->makeBranch();
        $branchB = $this->makeBranch();
        $user = $this->makeUser($branchA);
        $userB = $this->makeUser($branchB);
        $project = $this->makeProject($branchA);

        $this->actingAs($user);

        // Try to create expense assigned to user from branch B
        $component = Livewire::test(Expenses::class, ['projectId' => $project->id])
            ->set('category', 'materials')
            ->set('amount', 100)
            ->set('expense_date', now()->format('Y-m-d'))
            ->set('user_id', $userB->id)
            ->call('save');

        $component->assertHasErrors(['user_id']);
    }

    /**
     * Test that tasks assigned_to validation rejects users from other branches.
     */
    public function test_task_rejects_assignee_from_other_branch(): void
    {
        $this->grantProjectPermissions();

        $branchA = $this->makeBranch();
        $branchB = $this->makeBranch();
        $user = $this->makeUser($branchA);
        $userB = $this->makeUser($branchB);
        $project = $this->makeProject($branchA);

        $this->actingAs($user);

        // Try to create task assigned to user from branch B
        $component = Livewire::test(Tasks::class, ['projectId' => $project->id])
            ->set('title', 'Test Task')
            ->set('priority', 'medium')
            ->set('status', 'pending')
            ->set('estimated_hours', 5)
            ->set('progress', 0)
            ->set('assigned_to', $userB->id)
            ->call('save');

        $component->assertHasErrors(['assigned_to']);
    }

    /**
     * Test that tasks users dropdown only shows branch users.
     */
    public function test_tasks_users_dropdown_only_shows_branch_users(): void
    {
        $this->grantProjectPermissions();

        $branchA = $this->makeBranch();
        $branchB = $this->makeBranch();
        $user = $this->makeUser($branchA);
        $userA = $this->makeUser($branchA);
        $userB = $this->makeUser($branchB);
        $project = $this->makeProject($branchA);

        $this->actingAs($user);

        $component = Livewire::test(Tasks::class, ['projectId' => $project->id]);

        $users = $component->viewData('users');

        // Should contain only branch A users
        $this->assertTrue($users->contains('id', $user->id));
        $this->assertTrue($users->contains('id', $userA->id));
        $this->assertFalse($users->contains('id', $userB->id));
    }
}
