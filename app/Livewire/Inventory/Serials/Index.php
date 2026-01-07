<?php

declare(strict_types=1);

namespace App\Livewire\Inventory\Serials;

use App\Models\InventorySerial;
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

        $stats = InventorySerial::where('branch_id', $branchId)
            ->selectRaw('
                COUNT(*) as total_serials,
                COUNT(CASE WHEN status = ? THEN 1 END) as in_stock,
                COUNT(CASE WHEN status = ? THEN 1 END) as sold,
                COUNT(CASE WHEN warranty_end IS NOT NULL AND warranty_end >= ? THEN 1 END) as under_warranty
            ', ['in_stock', 'sold', now()])
            ->first();

        return [
            'total_serials' => $stats->total_serials ?? 0,
            'in_stock' => $stats->in_stock ?? 0,
            'sold' => $stats->sold ?? 0,
            'under_warranty' => $stats->under_warranty ?? 0,
        ];
    }

    #[Layout('layouts.app')]
    public function render()
    {
        $branchId = auth()->user()->branch_id;

        $query = InventorySerial::where('branch_id', $branchId)
            ->with(['product', 'warehouse', 'customer']);

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('serial_number', 'like', "%{$this->search}%")
                    ->orWhereHas('product', function ($pq) {
                        $pq->where('name', 'like', "%{$this->search}%");
                    });
            });
        }

        if ($this->status) {
            $query->where('status', $this->status);
        }

        $query->orderBy($this->sortField, $this->sortDirection);

        $serials = $query->paginate(15);
        $statistics = $this->getStatistics();

        return view('livewire.inventory.serials.index', [
            'serials' => $serials,
            'statistics' => $statistics,
        ]);
    }
}
