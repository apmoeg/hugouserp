<?php

declare(strict_types=1);

namespace App\Livewire\Banking\Transactions;

use App\Models\BankTransaction;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class Index extends Component
{
    use AuthorizesRequests;
    use WithPagination;

    public function mount(): void
    {
        $this->authorize('banking.view');
    }

    public function render()
    {
        $transactions = BankTransaction::with(['account', 'createdBy'])
            ->when(auth()->user()?->branch_id, fn ($q, $branchId) => $q->where('branch_id', $branchId))
            ->latest()
            ->paginate(20);

        return view('livewire.banking.transactions.index', [
            'transactions' => $transactions,
        ]);
    }
}
