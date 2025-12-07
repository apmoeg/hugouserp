<?php

declare(strict_types=1);

namespace App\Livewire\Suppliers;

use App\Models\Supplier;
use App\Traits\HasExport;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use HasExport;
    use WithPagination;

    public string $search = '';

    public string $sortField = 'created_at';

    public string $sortDirection = 'desc';

    protected $queryString = ['search'];

    public function mount(): void
    {
        $this->initializeExport('suppliers');
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
        $this->authorize('suppliers.manage');
        Supplier::findOrFail($id)->delete();
        session()->flash('success', __('Supplier deleted successfully'));
    }

    public function render()
    {
        $suppliers = Supplier::query()
            ->when($this->search, fn ($q) => $q->where('name', 'like', "%{$this->search}%")
                ->orWhere('email', 'like', "%{$this->search}%")
                ->orWhere('phone', 'like', "%{$this->search}%"))
            ->orderBy($this->sortField, $this->sortDirection)
            ->paginate(15);

        return view('livewire.suppliers.index', [
            'suppliers' => $suppliers,
        ])->layout('layouts.app', ['title' => __('Suppliers')]);
    }

    public function export()
    {
        $data = Supplier::query()
            ->when($this->search, fn ($q) => $q->where('name', 'like', "%{$this->search}%")
                ->orWhere('email', 'like', "%{$this->search}%")
                ->orWhere('phone', 'like', "%{$this->search}%"))
            ->orderBy($this->sortField, $this->sortDirection)
            ->select(['id', 'name', 'email', 'phone', 'address', 'balance', 'created_at'])
            ->get();

        return $this->performExport('suppliers', $data, __('Suppliers Export'));
    }
}
