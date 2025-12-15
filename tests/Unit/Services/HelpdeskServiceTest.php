<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Models\Branch;
use App\Models\Ticket;
use App\Models\TicketCategory;
use App\Models\TicketPriority;
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

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(HelpdeskService::class);

        $this->branch = Branch::create([
            'name' => 'Test Branch',
            'code' => 'TB001',
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

    public function test_can_create_ticket(): void
    {
        $data = $this->createTicketData();

        $ticket = $this->service->createTicket($data);

        $this->assertInstanceOf(Ticket::class, $ticket);
        $this->assertEquals('Test Ticket', $ticket->subject);
    }

    public function test_can_assign_ticket(): void
    {
        $ticket = Ticket::create($this->createTicketData(['subject' => 'Test', 'description' => 'Test']));

        $assigned = $this->service->assignTicket($ticket->id, 1);

        $this->assertTrue($assigned);
    }

    public function test_can_update_ticket_status(): void
    {
        $ticket = Ticket::create($this->createTicketData(['subject' => 'Test', 'description' => 'Test']));

        $updated = $this->service->updateTicketStatus($ticket->id, 'in_progress');

        $this->assertTrue($updated);
    }

    public function test_can_add_ticket_reply(): void
    {
        $ticket = Ticket::create($this->createTicketData(['subject' => 'Test', 'description' => 'Test']));

        $reply = $this->service->addReply($ticket->id, [
            'message' => 'Test Reply',
            'user_id' => 1,
        ]);

        $this->assertNotNull($reply);
    }
}
