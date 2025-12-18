<?php

declare(strict_types=1);

namespace App\Livewire\Sales;

use App\Models\Sale;
use App\Traits\HasExport;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Cache;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use AuthorizesRequests;
    use HasExport;
    use WithPagination;

    #[Url]
    public string $search = '';

    public function mount(): void
    {
        $this->authorize('sales.view');
        $this->initializeExport('sales');
    }

    #[Url]
    public string $status = '';

    #[Url]
    public string $dateFrom = '';

    #[Url]
    public string $dateTo = '';

    public string $sortField = 'created_at';

    public string $sortDirection = 'desc';

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
        $user = auth()->user();
        $cacheKey = 'sales_stats_'.($user?->branch_id ?? 'all');

        return Cache::remember($cacheKey, 300, function () use ($user) {
            $query = Sale::query();

            if ($user && $user->branch_id) {
                $query->where('branch_id', $user->branch_id);
            }

            $sales = $query->get();
            
            return [
                'total_sales' => $sales->count(),
                'total_revenue' => $sales->reduce(fn($carry, $sale) => bcadd((string)$carry, (string)($sale->grand_total ?? 0), 2), '0'),
                'total_paid' => $sales->reduce(fn($carry, $sale) => bcadd((string)$carry, (string)($sale->paid_total ?? 0), 2), '0'),
                'total_due' => $sales->reduce(fn($carry, $sale) => bcadd((string)$carry, (string)($sale->due_total ?? 0), 2), '0'),
            ];
        });
    }

    #[Layout('layouts.app')]
    public function render()
    {
        $user = auth()->user();

        $sales = Sale::query()
            ->with(['customer', 'branch', 'warehouse', 'createdBy'])
            ->when($user && $user->branch_id, fn ($q) => $q->where('branch_id', $user->branch_id))
            ->when($this->search, fn ($q) => $q->where(function ($query) {
                $query->where('code', 'like', "%{$this->search}%")
                    ->orWhere('reference_no', 'like', "%{$this->search}%")
                    ->orWhereHas('customer', fn ($c) => $c->where('name', 'like', "%{$this->search}%"));
            }))
            ->when($this->status, fn ($q) => $q->where('status', $this->status))
            ->when($this->dateFrom, fn ($q) => $q->whereDate('created_at', '>=', $this->dateFrom))
            ->when($this->dateTo, fn ($q) => $q->whereDate('created_at', '<=', $this->dateTo))
            ->orderBy($this->sortField, $this->sortDirection)
            ->paginate(15);

        $stats = $this->getStatistics();

        return view('livewire.sales.index', [
            'sales' => $sales,
            'stats' => $stats,
        ]);
    }

    public function export()
    {
        $user = auth()->user();

        $data = Sale::query()
            ->leftJoin('customers', 'sales.customer_id', '=', 'customers.id')
            ->leftJoin('branches', 'sales.branch_id', '=', 'branches.id')
            ->when($user && $user->branch_id, fn ($q) => $q->where('sales.branch_id', $user->branch_id))
            ->when($this->search, fn ($q) => $q->where(function ($query) {
                $query->where('sales.code', 'like', "%{$this->search}%")
                    ->orWhere('sales.reference_no', 'like', "%{$this->search}%")
                    ->orWhere('customers.name', 'like', "%{$this->search}%");
            }))
            ->when($this->status, fn ($q) => $q->where('sales.status', $this->status))
            ->when($this->dateFrom, fn ($q) => $q->whereDate('sales.created_at', '>=', $this->dateFrom))
            ->when($this->dateTo, fn ($q) => $q->whereDate('sales.created_at', '<=', $this->dateTo))
            ->orderBy('sales.'.$this->sortField, $this->sortDirection)
            ->select([
                'sales.id',
                'sales.code as reference',
                'sales.created_at as sale_date',
                'customers.name as customer_name',
                'sales.grand_total',
                'sales.paid_total as amount_paid',
                'sales.due_total as amount_due',
                'sales.status',
                'branches.name as branch_name',
                'sales.created_at',
            ])
            ->get();

        return $this->performExport('sales', $data, __('Sales Export'));
    }
}
