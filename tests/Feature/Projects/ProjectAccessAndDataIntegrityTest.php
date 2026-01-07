<?php

declare(strict_types=1);

namespace Tests\Feature\Projects;

use App\Livewire\Projects\Expenses;
use App\Livewire\Projects\Form;
use App\Livewire\Projects\Tasks;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\Project;
use App\Models\ProjectExpense;
use App\Models\ProjectTask;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class ProjectAccessAndDataIntegrityTest extends TestCase
{
    use RefreshDatabase;

    private function grantProjectPermissions(): void
    {
        Gate::define('projects.edit', fn () => true);
        Gate::define('projects.create', fn () => true);
        Gate::define('projects.tasks.view', fn () => true);
        Gate::define('projects.tasks.manage', fn () => true);
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
            'code' => 'PRJ-'.Str::random(5),
            'name' => 'Project '.Str::random(3),
            'status' => 'planning',
        ], $overrides));
    }

    private function makeCustomer(Branch $branch): Customer
    {
        return Customer::create([
            'uuid' => (string) Str::uuid(),
            'code' => 'CUST-'.Str::random(5),
            'name' => 'Customer '.Str::random(3),
            'branch_id' => $branch->id,
        ]);
    }

    public function test_user_cannot_load_project_from_other_branch(): void
    {
        $this->grantProjectPermissions();

        $branchA = $this->makeBranch();
        $branchB = $this->makeBranch();
        $user = $this->makeUser($branchA);
        $otherProject = $this->makeProject($branchB);

        $this->actingAs($user);
        try {
            Livewire::test(Form::class, ['id' => $otherProject->id]);
            $this->fail('Expected a 403 when loading a project from another branch.');
        } catch (HttpException $exception) {
            $this->assertSame(403, $exception->getStatusCode());
        }
    }

    public function test_project_form_saves_customer_and_manager(): void
    {
        $this->grantProjectPermissions();

        $branch = $this->makeBranch();
        $user = $this->makeUser($branch);
        $manager = $this->makeUser($branch);
        $customer = $this->makeCustomer($branch);

        $this->actingAs($user);

        Livewire::test(Form::class)
            ->set('branch_id', $branch->id)
            ->set('name', 'New Project')
            ->set('code', 'PRJ-100')
            ->set('description', 'Project with customer and manager')
            ->set('client_id', $customer->id)
            ->set('project_manager_id', $manager->id)
            ->set('start_date', '2025-01-01')
            ->set('end_date', '2025-01-10')
            ->set('budget_amount', 5000)
            ->call('save');

        $this->assertDatabaseHas('projects', [
            'code' => 'PRJ-100',
            'client_id' => $customer->id,
            'project_manager_id' => $manager->id,
            'branch_id' => $branch->id,
        ]);
    }

    public function test_task_creation_uses_title_parent_and_dependencies(): void
    {
        $this->grantProjectPermissions();

        $branch = $this->makeBranch();
        $user = $this->makeUser($branch);
        $project = $this->makeProject($branch);

        $parentTask = ProjectTask::create([
            'project_id' => $project->id,
            'title' => 'Parent',
            'status' => 'pending',
            'priority' => 'medium',
        ]);

        $dependencyTask = ProjectTask::create([
            'project_id' => $project->id,
            'title' => 'Dependency',
            'status' => 'pending',
            'priority' => 'medium',
        ]);

        $this->actingAs($user);

        Livewire::test(Tasks::class, ['projectId' => $project->id])
            ->set('title', 'Child Task')
            ->set('description', 'Child description')
            ->set('parent_task_id', $parentTask->id)
            ->set('selectedDependencies', [$dependencyTask->id])
            ->set('estimated_hours', 2)
            ->set('progress', 0)
            ->call('save');

        $childTask = ProjectTask::where('project_id', $project->id)
            ->where('title', 'Child Task')
            ->first();

        $this->assertNotNull($childTask);
        $this->assertSame($parentTask->id, $childTask->parent_task_id);

        $this->assertDatabaseHas('task_dependencies', [
            'task_id' => $childTask->id,
            'depends_on_task_id' => $dependencyTask->id,
        ]);
    }

    public function test_expense_save_and_approval_track_dates_and_approver(): void
    {
        $this->grantProjectPermissions();

        $branch = $this->makeBranch();
        $user = $this->makeUser($branch);
        $project = $this->makeProject($branch);

        $this->actingAs($user);

        Livewire::test(Expenses::class, ['projectId' => $project->id])
            ->set('category', 'materials')
            ->set('amount', 150.75)
            ->set('expense_date', '2025-02-01')
            ->set('description', 'Test expense')
            ->set('user_id', $user->id)
            ->call('save');

        $expense = ProjectExpense::where('project_id', $project->id)->first();
        $this->assertNotNull($expense);
        $this->assertEquals('2025-02-01', $expense->expense_date->format('Y-m-d'));

        Livewire::test(Expenses::class, ['projectId' => $project->id])
            ->call('approve', $expense->id);

        $expense->refresh();
        $this->assertEquals('approved', $expense->status);
        $this->assertEquals($user->id, $expense->approved_by);
    }

    public function test_task_actions_are_scoped_to_project(): void
    {
        $this->grantProjectPermissions();

        $branchA = $this->makeBranch();
        $branchB = $this->makeBranch();
        $user = $this->makeUser($branchA);

        $projectA = $this->makeProject($branchA);
        $projectB = $this->makeProject($branchB);

        $otherTask = ProjectTask::create([
            'project_id' => $projectB->id,
            'title' => 'Other project task',
            'status' => 'pending',
            'priority' => 'medium',
        ]);

        $this->actingAs($user);
        try {
            Livewire::test(Tasks::class, ['projectId' => $projectA->id])
                ->call('editTask', $otherTask->id);
            $this->fail('Expected task lookup to be scoped to the project.');
        } catch (ModelNotFoundException $exception) {
            $this->assertSame(ProjectTask::class, $exception->getModel());
        }
    }

    public function test_expense_actions_are_scoped_to_project(): void
    {
        $this->grantProjectPermissions();

        $branchA = $this->makeBranch();
        $branchB = $this->makeBranch();
        $user = $this->makeUser($branchA);

        $projectA = $this->makeProject($branchA);
        $projectB = $this->makeProject($branchB);

        $foreignExpense = ProjectExpense::create([
            'project_id' => $projectB->id,
            'category' => 'materials',
            'description' => 'Other project expense',
            'amount' => 50,
            'expense_date' => '2025-03-01',
            'user_id' => $user->id,
        ]);

        $this->actingAs($user);
        try {
            Livewire::test(Expenses::class, ['projectId' => $projectA->id])
                ->call('approve', $foreignExpense->id);
            $this->fail('Expected expense lookup to be scoped to the project.');
        } catch (ModelNotFoundException $exception) {
            $this->assertSame(ProjectExpense::class, $exception->getModel());
        }
    }
}
