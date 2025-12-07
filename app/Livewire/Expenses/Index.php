<?php

declare(strict_types=1);

namespace App\Livewire\Expenses;

use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Traits\HasExport;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use AuthorizesRequests;
    use HasExport;
    use WithPagination;

    public string $search = '';

    public string $categoryId = '';

    public string $dateFrom = '';

    public string $dateTo = '';

    public string $sortField = 'expense_date';

    public string $sortDirection = 'desc';

    protected $queryString = ['search', 'categoryId'];

    public function mount(): void
    {
        $this->initializeExport('expenses');
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function sortBy(string $field): void
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }
    }

    public function delete(int $id): void
    {
        $this->authorize('expenses.manage');
        Expense::findOrFail($id)->delete();
        session()->flash('success', __('Expense deleted successfully'));
    }

    public function render()
    {
        $expenses = Expense::query()
            ->with(['category', 'branch', 'creator'])
            ->when($this->search, fn ($q) => $q->where('description', 'like', "%{$this->search}%")
                ->orWhere('reference_number', 'like', "%{$this->search}%"))
            ->when($this->categoryId, fn ($q) => $q->where('category_id', $this->categoryId))
            ->when($this->dateFrom, fn ($q) => $q->whereDate('expense_date', '>=', $this->dateFrom))
            ->when($this->dateTo, fn ($q) => $q->whereDate('expense_date', '<=', $this->dateTo))
            ->orderBy($this->sortField, $this->sortDirection)
            ->paginate(15);

        $categories = ExpenseCategory::active()->get();

        return view('livewire.expenses.index', [
            'expenses' => $expenses,
            'categories' => $categories,
        ])->layout('layouts.app', ['title' => __('Expenses')]);
    }
}
