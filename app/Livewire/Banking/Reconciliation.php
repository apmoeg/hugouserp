<?php

declare(strict_types=1);

namespace App\Livewire\Banking;

use App\Models\BankAccount;
use Livewire\Component;
use Livewire\Attributes\Layout;

#[Layout('layouts.app')]
class Reconciliation extends Component
{
    public $accountId;
    public $startDate;
    public $endDate;

    public function mount(): void
    {
        $this->authorize('banking.reconcile');
        $this->startDate = now()->startOfMonth()->toDateString();
        $this->endDate = now()->endOfMonth()->toDateString();
    }

    public function render()
    {
        $accounts = BankAccount::where('branch_id', auth()->user()->branch_id)
            ->orderBy('account_name')
            ->get();

        return view('livewire.banking.reconciliation', [
            'accounts' => $accounts,
        ]);
    }
}
