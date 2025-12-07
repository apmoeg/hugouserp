<?php

declare(strict_types=1);

namespace App\Livewire\Pos\Reports;

use App\Models\Branch;
use App\Models\Sale;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

class OfflineSales extends Component
{
    use WithPagination;

    public ?int $branchId = null;

    public ?string $dateFrom = null;

    public ?string $dateTo = null;

    #[Layout('layouts.app')]
    public function mount(): void
    {
        $user = Auth::user();
        if (! $user || ! $user->can('pos.offline.report.view')) {
            abort(403);
        }
    }

    public function updating($name, $value): void
    {
        if (in_array($name, ['branchId', 'dateFrom', 'dateTo'], true)) {
            $this->resetPage();
        }
    }

    public function render()
    {
        $query = Sale::query()
            ->where('notes', 'LIKE', '%Synced from offline POS%');

        if ($this->branchId) {
            $query->where('branch_id', $this->branchId);
        }

        if ($this->dateFrom) {
            $query->whereDate('created_at', '>=', $this->dateFrom);
        }

        if ($this->dateTo) {
            $query->whereDate('created_at', '<=', $this->dateTo);
        }

        $sales = $query->latest()->paginate(25);

        $branches = Branch::query()
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('livewire.pos.reports.offline-sales', [
            'sales' => $sales,
            'branches' => $branches,
        ]);
    }
}
