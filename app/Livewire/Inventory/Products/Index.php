<?php

declare(strict_types=1);

namespace App\Livewire\Inventory\Products;

use App\Models\Product;
use App\Traits\HasExport;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use HasExport;
    use WithPagination;

    public string $search = '';

    public ?string $status = null;

    public ?string $type = null;

    #[Layout('layouts.app')]
    public function mount(): void
    {
        $user = Auth::user();
        if (! $user || ! $user->can('inventory.products.view')) {
            abort(403);
        }

        $this->initializeExport('products');
    }

    public function render()
    {
        $user = auth()->user();
        $branchId = $user?->branch_id;

        $query = Product::query()
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->when($this->search !== '', function ($q) {
                $term = '%'.$this->search.'%';

                $q->where(function ($inner) use ($term) {
                    $inner->where('name', 'like', $term)
                        ->orWhere('sku', 'like', $term)
                        ->orWhere('barcode', 'like', $term);
                });
            })
            ->when($this->status !== null && $this->status !== '', fn ($q) => $q->where('status', $this->status))
            ->when($this->type !== null && $this->type !== '', fn ($q) => $q->where('type', $this->type))
            ->orderByDesc('id');

        $products = $query->paginate(15);

        return view('livewire.inventory.products.index', [
            'products' => $products,
        ]);
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatus(): void
    {
        $this->resetPage();
    }

    public function updatingType(): void
    {
        $this->resetPage();
    }

    public function export()
    {
        $user = auth()->user();
        $branchId = $user?->branch_id;

        $data = Product::query()
            ->leftJoin('modules', 'products.module_id', '=', 'modules.id')
            ->leftJoin('branches', 'products.branch_id', '=', 'branches.id')
            ->when($branchId, fn ($q) => $q->where('products.branch_id', $branchId))
            ->when($this->search !== '', function ($q) {
                $term = '%'.$this->search.'%';
                $q->where(function ($inner) use ($term) {
                    $inner->where('products.name', 'like', $term)
                        ->orWhere('products.sku', 'like', $term)
                        ->orWhere('products.barcode', 'like', $term);
                });
            })
            ->when($this->status !== null && $this->status !== '', fn ($q) => $q->where('products.status', $this->status))
            ->when($this->type !== null && $this->type !== '', fn ($q) => $q->where('products.type', $this->type))
            ->select([
                'products.id',
                'products.code',
                'products.name',
                'products.sku',
                'products.barcode',
                'products.type',
                'products.cost as standard_cost',
                'products.price as default_price',
                'products.min_stock',
                'products.status',
                'modules.name as module_name',
                'branches.name as branch_name',
                'products.created_at',
            ])
            ->orderByDesc('products.id')
            ->get();

        return $this->performExport('products', $data, __('Products Export'));
    }
}
