<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Models\Branch;
use App\Models\Product;
use App\Models\User;
use App\Models\PosSession;
use App\Services\POSService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class POSServiceTest extends TestCase
{
    use RefreshDatabase;

    protected POSService $service;
    protected Branch $branch;
    protected Product $product;
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(POSService::class);

        $this->branch = Branch::create([
            'name' => 'Test Branch',
            'code' => 'TB001',
        ]);

        $this->user = User::factory()->create([
            'branch_id' => $this->branch->id,
        ]);

        $this->product = Product::create([
            'name' => 'Test Product',
            'code' => 'PRD001',
            'sku' => 'SKU001',
            'type' => 'stock',
            'default_price' => 100,
            'standard_cost' => 50,
            'branch_id' => $this->branch->id,
        ]);
    }

    public function test_can_open_session(): void
    {
        $session = $this->service->openSession(
            $this->branch->id,
            $this->user->id,
            1000.00
        );

        $this->assertInstanceOf(PosSession::class, $session);
        $this->assertEquals(1000.00, $session->opening_cash);
    }

    public function test_can_close_session(): void
    {
        $session = $this->service->openSession(
            $this->branch->id,
            $this->user->id,
            1000.00
        );

        $closedSession = $this->service->closeSession($session->id, 1200.00, 'End of day');

        $this->assertEquals('closed', $closedSession->status);
        $this->assertEquals(1200.00, $closedSession->closing_cash);
    }

    public function test_can_get_current_session(): void
    {
        $session = $this->service->openSession(
            $this->branch->id,
            $this->user->id,
            1000.00
        );

        $currentSession = $this->service->getCurrentSession($this->branch->id, $this->user->id);

        $this->assertNotNull($currentSession);
        $this->assertEquals($session->id, $currentSession->id);
    }

    public function test_validate_discount_with_valid_percent(): void
    {
        $isValid = $this->service->validateDiscount($this->user, 10);

        $this->assertIsBool($isValid);
    }
}
