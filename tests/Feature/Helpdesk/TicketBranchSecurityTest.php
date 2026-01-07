<?php

declare(strict_types=1);

namespace Tests\Feature\Helpdesk;

use App\Livewire\Helpdesk\TicketForm;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\Ticket;
use App\Models\TicketCategory;
use App\Models\TicketPriority;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

class TicketBranchSecurityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        if (! Schema::hasColumn('ticket_categories', 'deleted_at')) {
            Schema::table('ticket_categories', function (Blueprint $table): void {
                $table->softDeletes();
            });
        }

        if (! Schema::hasColumn('ticket_categories', 'sort_order')) {
            Schema::table('ticket_categories', function (Blueprint $table): void {
                $table->integer('sort_order')->default(0);
            });
        }
    }

    protected function createCategory(): TicketCategory
    {
        return TicketCategory::create([
            'name' => 'General',
            'slug' => 'general',
            'is_active' => true,
        ]);
    }

    protected function createPriority(): TicketPriority
    {
        return TicketPriority::create([
            'name' => 'Medium',
            'slug' => 'medium',
            'level' => 2,
            'color' => '#FFA500',
            'is_active' => true,
        ]);
    }

    public function test_user_cannot_edit_ticket_from_other_branch(): void
    {
        Gate::define('helpdesk.edit', fn () => true);

        $branchA = Branch::factory()->create();
        $branchB = Branch::factory()->create();
        $user = User::factory()->create(['branch_id' => $branchA->id]);
        $ticket = Ticket::create([
            'ticket_number' => 'TKT-000001',
            'subject' => 'Branch B Ticket',
            'description' => 'Test',
            'status' => 'new',
            'priority_id' => $this->createPriority()->id,
            'category_id' => $this->createCategory()->id,
            'branch_id' => $branchB->id,
        ]);

        try {
            Livewire::actingAs($user)
                ->test(TicketForm::class, ['ticket' => $ticket]);
            $this->fail('Cross-branch edit should be forbidden.');
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
            $this->assertSame(403, $e->getStatusCode());
        }
    }

    public function test_ticket_branch_is_not_changed_on_update(): void
    {
        Gate::define('helpdesk.edit', fn () => true);

        $branch = Branch::factory()->create();
        $user = User::factory()->create(['branch_id' => $branch->id]);
        $ticket = Ticket::create([
            'ticket_number' => 'TKT-000002',
            'subject' => 'Original',
            'description' => 'Test',
            'status' => 'new',
            'priority_id' => $this->createPriority()->id,
            'category_id' => $this->createCategory()->id,
            'branch_id' => $branch->id,
        ]);

        Livewire::actingAs($user)
            ->test(TicketForm::class, ['ticket' => $ticket])
            ->set('subject', 'Updated Subject')
            ->set('status', 'open')
            ->call('save');

        $this->assertEquals($branch->id, $ticket->fresh()->branch_id);
    }

    public function test_invalid_due_date_or_status_is_rejected(): void
    {
        Gate::define('helpdesk.create', fn () => true);

        $branch = Branch::factory()->create();
        $user = User::factory()->create(['branch_id' => $branch->id]);
        $category = $this->createCategory();
        $priority = $this->createPriority();

        Livewire::actingAs($user)
            ->test(TicketForm::class)
            ->set('subject', 'New Ticket')
            ->set('description', 'Desc')
            ->set('category_id', $category->id)
            ->set('priority_id', $priority->id)
            ->set('due_date', 'not-a-date')
            ->set('status', 'invalid')
            ->call('save')
            ->assertHasErrors(['due_date' => 'date_format', 'status' => 'in']);
    }

    public function test_ticket_form_scopes_customers_and_agents_to_branch(): void
    {
        Gate::define('helpdesk.create', fn () => true);

        $branchA = Branch::factory()->create();
        $branchB = Branch::factory()->create();
        $user = User::factory()->create(['branch_id' => $branchA->id]);

        Customer::create(['name' => 'Alice', 'branch_id' => $branchA->id, 'code' => 'CUST-A', 'uuid' => (string) Str::uuid()]);
        Customer::create(['name' => 'Bob', 'branch_id' => $branchB->id, 'code' => 'CUST-B', 'uuid' => (string) Str::uuid()]);

        $agentRole = \Spatie\Permission\Models\Role::create(['name' => 'Support Agent']);
        $agentA = User::factory()->create(['branch_id' => $branchA->id, 'is_active' => true]);
        $agentA->assignRole($agentRole);
        $agentB = User::factory()->create(['branch_id' => $branchB->id, 'is_active' => true]);
        $agentB->assignRole($agentRole);

        Livewire::actingAs($user)
            ->test(TicketForm::class)
            ->assertViewHas('customers', function ($customers) use ($branchA) {
                return $customers->count() === 1 && (int) $customers->first()->branch_id === $branchA->id;
            })
            ->assertViewHas('agents', function ($agents) use ($agentA) {
                return $agents->count() === 1 && $agents->first()->id === $agentA->id;
            });
    }
}
