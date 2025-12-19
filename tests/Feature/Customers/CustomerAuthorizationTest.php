<?php

declare(strict_types=1);

namespace Tests\Feature\Customers;

use App\Livewire\Customers\Form;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;
use Tests\TestCase;

class CustomerAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_cannot_edit_customer_from_other_branch(): void
    {
        Gate::define('customers.manage', fn () => true);

        $branchA = Branch::factory()->create();
        $branchB = Branch::factory()->create();
        $user = User::factory()->create(['branch_id' => $branchA->id]);

        $customer = Customer::create([
            'name' => 'Branch B Customer',
            'branch_id' => $branchB->id,
        ]);

        try {
            Livewire::actingAs($user)
                ->test(Form::class, ['customer' => $customer]);
            $this->fail('Cross-branch edit should be forbidden.');
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
            $this->assertSame(403, $e->getStatusCode());
        }
    }

    public function test_new_customer_uses_user_branch(): void
    {
        Gate::define('customers.manage', fn () => true);

        $branch = Branch::factory()->create();
        $user = User::factory()->create(['branch_id' => $branch->id]);

        Livewire::actingAs($user)
            ->test(Form::class)
            ->set('name', 'New Customer')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('customers', [
            'name' => 'New Customer',
            'branch_id' => $branch->id,
        ]);
    }
}
