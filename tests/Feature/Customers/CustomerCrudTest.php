<?php

declare(strict_types=1);

namespace Tests\Feature\Customers;

use App\Models\Branch;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerCrudTest extends TestCase
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

    public function test_can_create_customer(): void
    {
        $customer = Customer::create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'phone' => '1234567890',
            'branch_id' => $this->branch->id,
        ]);

        $this->assertDatabaseHas('customers', ['name' => 'John Doe']);
    }

    public function test_can_read_customer(): void
    {
        $customer = Customer::create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'branch_id' => $this->branch->id,
        ]);

        $found = Customer::find($customer->id);
        $this->assertNotNull($found);
    }

    public function test_can_update_customer(): void
    {
        $customer = Customer::create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'branch_id' => $this->branch->id,
        ]);

        $customer->update(['name' => 'Jane Doe']);
        $this->assertDatabaseHas('customers', ['id' => $customer->id, 'name' => 'Jane Doe']);
    }

    public function test_can_delete_customer(): void
    {
        $customer = Customer::create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'branch_id' => $this->branch->id,
        ]);

        $customer->delete();
        $this->assertSoftDeleted('customers', ['id' => $customer->id]);
    }
}
