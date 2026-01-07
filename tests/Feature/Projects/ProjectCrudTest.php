<?php

declare(strict_types=1);

namespace Tests\Feature\Projects;

use App\Models\Branch;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectCrudTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected Branch $branch;

    protected function setUp(): void
    {
        parent::setUp();

        $this->branch = Branch::create(['name' => 'Test Branch', 'code' => 'TB001']);
        $this->user = User::factory()->create(['branch_id' => $this->branch->id]);
    }

    public function test_can_create_project(): void
    {
        $project = Project::create([
            'name' => 'Test Project',
            'code' => 'PROJ001',
            'description' => 'Test description',
            'start_date' => now(),
            'status' => 'active',
            'branch_id' => $this->branch->id,
        ]);

        $this->assertDatabaseHas('projects', ['name' => 'Test Project']);
    }

    public function test_can_read_project(): void
    {
        $project = Project::create([
            'name' => 'Test Project',
            'code' => 'PROJ001',
            'start_date' => now(),
            'status' => 'active',
            'branch_id' => $this->branch->id,
        ]);

        $found = Project::find($project->id);
        $this->assertNotNull($found);
    }

    public function test_can_update_project(): void
    {
        $project = Project::create([
            'name' => 'Test Project',
            'code' => 'PROJ001',
            'start_date' => now(),
            'status' => 'active',
            'branch_id' => $this->branch->id,
        ]);

        $project->update(['status' => 'completed']);
        $this->assertDatabaseHas('projects', ['id' => $project->id, 'status' => 'completed']);
    }

    public function test_can_delete_project(): void
    {
        $project = Project::create([
            'name' => 'Test Project',
            'code' => 'PROJ001',
            'start_date' => now(),
            'status' => 'active',
            'branch_id' => $this->branch->id,
        ]);

        $project->delete();
        $this->assertSoftDeleted('projects', ['id' => $project->id]);
    }
}
