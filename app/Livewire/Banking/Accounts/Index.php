<?php

declare(strict_types=1);

namespace App\Livewire\Banking\Accounts;

use App\Models\BankAccount;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use AuthorizesRequests;
    use WithPagination;

    #[Url]
    public string $search = '';

    #[Url]
    public string $status = '';

    #[Url]
    public string $currency = '';

    public string $sortField = 'created_at';

    public string $sortDirection = 'desc';

    public function mount(): void
    {
        $this->authorize('banking.view');
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

    public function getStatistics(): array
    {
        $branchId = auth()->user()->branch_id;

        // Optimize with single query using conditional aggregations
        $stats = BankAccount::where('branch_id', $branchId)
            ->selectRaw('
                COUNT(*) as total_accounts,
                COUNT(CASE WHEN status = ? THEN 1 END) as active_accounts,
                SUM(CASE WHEN status = ? THEN current_balance ELSE 0 END) as total_balance,
                COUNT(DISTINCT currency) as currencies
            ', ['active', 'active'])
            ->first();

        return [
            'total_accounts' => $stats->total_accounts ?? 0,
            'active_accounts' => $stats->active_accounts ?? 0,
            'total_balance' => $stats->total_balance ?? 0,
            'currencies' => $stats->currencies ?? 0,
        ];
    }

    #[Layout('layouts.app')]
    public function render()
    {
        $branchId = auth()->user()->branch_id;

        $query = BankAccount::where('branch_id', $branchId);

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('account_name', 'like', "%{$this->search}%")
                    ->orWhere('account_number', 'like', "%{$this->search}%")
                    ->orWhere('bank_name', 'like', "%{$this->search}%");
            });
        }

        if ($this->status) {
            $query->where('status', $this->status);
        }

        if ($this->currency) {
            $query->where('currency', $this->currency);
        }

        $query->orderBy($this->sortField, $this->sortDirection);

        $accounts = $query->paginate(15);
        $statistics = $this->getStatistics();

        $currencies = BankAccount::where('branch_id', $branchId)
            ->select('currency')
            ->distinct()
            ->pluck('currency');

        return view('livewire.banking.accounts.index', [
            'accounts' => $accounts,
            'statistics' => $statistics,
            'currencies' => $currencies,
        ]);
    }
}
