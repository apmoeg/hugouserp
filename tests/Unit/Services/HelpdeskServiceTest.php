<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Models\Branch;
use App\Models\Ticket;
use App\Models\TicketCategory;
use App\Models\TicketPriority;
use App\Models\User;
use App\Services\HelpdeskService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HelpdeskServiceTest extends TestCase
{
    use RefreshDatabase;

    protected HelpdeskService $service;
    protected Branch $branch;
    protected TicketPriority $priority;
    protected TicketCategory $category;
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(HelpdeskService::class);

        $this->branch = Branch::create([
            'name' => 'Test Branch',
            'code' => 'TB001',
        ]);

        $this->user = User::factory()->create([
            'branch_id' => $this->branch->id,
        ]);

        $this->priority = TicketPriority::create([
            'name' => 'Medium',
            'slug' => 'medium',
            'level' => 2,
            'color' => '#FFA500',
            'is_active' => true,
        ]);

        $this->category = TicketCategory::create([
            'name' => 'General',
            'slug' => 'general',
            'is_active' => true,
        ]);
    }

    protected function createTicketData(array $overrides = []): array
    {
        return array_merge([
            'subject' => 'Test Ticket',
            'description' => 'Test Description',
            'status' => 'new',
            'priority_id' => $this->priority->id,
            'category_id' => $this->category->id,
            'branch_id' => $this->branch->id,
        ], $overrides);
    }

    protected function createTicket(array $overrides = []): Ticket
    {
        static $counter = 0;
        $counter++;
        return Ticket::create(array_merge($this->createTicketData(), [
            'ticket_number' => 'TKT-' . str_pad((string) $counter, 6, '0', STR_PAD_LEFT),
        ], $overrides));
    }

    public function test_can_get_ticket_stats(): void
    {
        $this->createTicket();
        $this->createTicket(['status' => 'closed']);

        $stats = $this->service->getTicketStats($this->branch->id);

        $this->assertIsArray($stats);
        $this->assertArrayHasKey('total', $stats);
    }

    public function test_can_calculate_sla(): void
    {
        $ticket = $this->createTicket();

        $sla = $this->service->calculateSLA($ticket);

        $this->assertIsArray($sla);
        // Check that SLA returns expected structure
        $this->assertTrue(count($sla) > 0);
    }
}
