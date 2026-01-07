<?php

declare(strict_types=1);

namespace App\Livewire\Inventory\Batches;

use App\Models\InventoryBatch;
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

    public string $sortField = 'created_at';

    public string $sortDirection = 'desc';

    public function mount(): void
    {
        $this->authorize('inventory.products.view');
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

        $stats = InventoryBatch::where('branch_id', $branchId)
            ->selectRaw('
                COUNT(*) as total_batches,
                COUNT(CASE WHEN status = ? THEN 1 END) as active_batches,
                COUNT(CASE WHEN expiry_date IS NOT NULL AND expiry_date <= ? THEN 1 END) as expired_batches,
                SUM(CASE WHEN status = ? THEN quantity ELSE 0 END) as total_quantity
            ', ['active', now()->addDays(30), 'active'])
            ->first();

        return [
            'total_batches' => $stats->total_batches ?? 0,
            'active_batches' => $stats->active_batches ?? 0,
            'expired_batches' => $stats->expired_batches ?? 0,
            'total_quantity' => $stats->total_quantity ?? 0,
        ];
    }

    #[Layout('layouts.app')]
    public function render()
    {
        $branchId = auth()->user()->branch_id;

        $query = InventoryBatch::where('branch_id', $branchId)
            ->with(['product', 'warehouse']);

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('batch_number', 'like', "%{$this->search}%")
                    ->orWhereHas('product', function ($pq) {
                        $pq->where('name', 'like', "%{$this->search}%");
                    });
            });
        }

        if ($this->status) {
            $query->where('status', $this->status);
        }

        $query->orderBy($this->sortField, $this->sortDirection);

        $batches = $query->paginate(15);
        $statistics = $this->getStatistics();

        return view('livewire.inventory.batches.index', [
            'batches' => $batches,
            'statistics' => $statistics,
        ]);
    }
}
