<?php

declare(strict_types=1);

namespace App\Livewire\Expenses;

use App\Livewire\Concerns\HandlesErrors;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Component;
use Livewire\WithFileUploads;

class Form extends Component
{
    use AuthorizesRequests;
    use HandlesErrors;
    use WithFileUploads;

    public ?Expense $expense = null;

    public bool $editMode = false;

    public string $category_id = '';

    public string $reference_number = '';

    public string $expense_date = '';

    public float $amount = 0;

    public string $payment_method = 'cash';

    public string $description = '';

    public $attachment;

    public bool $is_recurring = false;

    public string $recurrence_interval = '';

    protected function rules(): array
    {
        return [
            'category_id' => 'nullable|exists:expense_categories,id',
            'reference_number' => 'nullable|string|max:100',
            'expense_date' => 'required|date',
            'amount' => 'required|numeric|min:0',
            'payment_method' => 'nullable|string|max:50',
            'description' => 'nullable|string',
            'attachment' => 'nullable|file|max:5120',
            'is_recurring' => 'boolean',
            'recurrence_interval' => 'nullable|string|max:50',
        ];
    }

    public function mount(?Expense $expense = null): void
    {
        $this->authorize('expenses.manage');

        $this->expense_date = now()->format('Y-m-d');

        if ($expense && $expense->exists) {
            $this->expense = $expense;
            $this->editMode = true;
            $this->fill($expense->toArray());
            $this->expense_date = $expense->expense_date->format('Y-m-d');
        }
    }

    public function save(): void
    {
        $validated = $this->validate();
        $validated['branch_id'] = auth()->user()->branches()->first()?->id;
        $validated['created_by'] = auth()->id();

        if ($this->attachment) {
            $validated['attachment'] = $this->attachment->store('expenses', 'public');
        }

        $this->handleOperation(
            operation: function () use ($validated) {
                if ($this->editMode) {
                    $this->expense->update($validated);
                } else {
                    Expense::create($validated);
                }
            },
            successMessage: $this->editMode ? __('Expense updated successfully') : __('Expense created successfully'),
            redirectRoute: 'app.expenses.index'
        );
    }

    public function render()
    {
        $categories = ExpenseCategory::active()->get();

        return view('livewire.expenses.form', [
            'categories' => $categories,
        ])->layout('layouts.app', ['title' => $this->editMode ? __('Edit Expense') : __('Add Expense')]);
    }
}
