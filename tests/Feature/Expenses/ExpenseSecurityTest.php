<?php

declare(strict_types=1);

namespace Tests\Feature\Expenses;

use App\Livewire\Expenses\Form;
use App\Models\Branch;
use App\Models\Expense;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class ExpenseSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_php_attachment_is_rejected_and_not_stored(): void
    {
        Storage::fake('local');
        Gate::define('expenses.manage', fn () => true);

        $branch = Branch::factory()->create();
        $user = User::factory()->create(['branch_id' => $branch->id]);

        Livewire::actingAs($user)
            ->test(Form::class)
            ->set('expense_date', now()->format('Y-m-d'))
            ->set('amount', 50)
            ->set('reference_number', 'EXP-TST-1')
            ->set('attachment', UploadedFile::fake()->create('payload.php', 1, 'text/x-php'))
            ->call('save')
            ->assertHasErrors(['attachment' => 'mimes']);

        Storage::disk('local')->assertDirectoryEmpty('expenses');
    }

    public function test_user_cannot_edit_expense_from_other_branch(): void
    {
        Gate::define('expenses.manage', fn () => true);

        $branchA = Branch::factory()->create();
        $branchB = Branch::factory()->create();
        $user = User::factory()->create(['branch_id' => $branchA->id]);

        $expense = Expense::create([
            'category_id' => null,
            'expense_date' => now(),
            'amount' => 100,
            'payment_method' => 'cash',
            'branch_id' => $branchB->id,
            'reference_number' => 'EXP-BR-B',
            'created_by' => $user->id,
        ]);

        try {
            Livewire::actingAs($user)
                ->test(Form::class, ['expense' => $expense]);
            $this->fail('Cross-branch edit should be forbidden.');
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
            $this->assertSame(403, $e->getStatusCode());
        }
    }

    public function test_expense_branch_id_is_preserved_on_update(): void
    {
        Gate::define('expenses.manage', fn () => true);

        $branch = Branch::factory()->create();
        $user = User::factory()->create(['branch_id' => $branch->id]);

        $expense = Expense::create([
            'category_id' => null,
            'expense_date' => now(),
            'amount' => 75,
            'payment_method' => 'card',
            'branch_id' => $branch->id,
            'reference_number' => 'EXP-BR-A',
            'created_by' => $user->id,
        ]);

        Livewire::actingAs($user)
            ->test(Form::class, ['expense' => $expense])
            ->set('description', 'Updated description')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertEquals($branch->id, $expense->fresh()->branch_id);
    }
}
