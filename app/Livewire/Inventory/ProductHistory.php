<?php

declare(strict_types=1);

namespace App\Livewire\Inventory;

use App\Models\AuditLog;
use App\Models\Product;
use App\Models\StockMovement;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class ProductHistory extends Component
{
    use WithPagination;

    public ?Product $product = null;

    protected int $branchId;

    public string $filterType = 'all';

    public ?string $dateFrom = null;

    public ?string $dateTo = null;

    public function mount(?int $product = null): void
    {
        $user = Auth::user();
        if (! $user || ! $user->can('inventory.products.view')) {
            abort(403);
        }

        if (! $user->branch_id) {
            abort(403);
        }

        $this->branchId = (int) $user->branch_id;

        if ($product) {
            $this->product = Product::where('branch_id', $this->branchId)
                ->find($product);
            if (! $this->product) {
                abort(403);
            }
        }
    }

    public function render()
    {
        $stockMovements = collect();
        $auditLogs = collect();
        $currentStock = 0;

        if ($this->product) {
            $movementQuery = StockMovement::where('product_id', $this->product->id)
                ->where('branch_id', $this->branchId)
                ->with(['user', 'warehouse'])
                ->when($this->filterType !== 'all' && $this->filterType !== 'audit', fn ($q) => $q->where('type', $this->filterType))
                ->when($this->dateFrom, fn ($q) => $q->whereDate('created_at', '>=', $this->dateFrom))
                ->when($this->dateTo, fn ($q) => $q->whereDate('created_at', '<=', $this->dateTo))
                ->orderByDesc('created_at');

            if ($this->filterType !== 'audit') {
                $stockMovements = $movementQuery->paginate(20);
            }

            if ($this->filterType === 'all' || $this->filterType === 'audit') {
                $auditLogs = AuditLog::where('auditable_type', Product::class)
                    ->where('auditable_id', $this->product->id)
                    ->where('branch_id', $this->branchId)
                    ->with('user')
                    ->when($this->dateFrom, fn ($q) => $q->whereDate('created_at', '>=', $this->dateFrom))
                    ->when($this->dateTo, fn ($q) => $q->whereDate('created_at', '<=', $this->dateTo))
                    ->orderByDesc('created_at')
                    ->limit(50)
                    ->get();
            }

            $currentStock = StockMovement::where('product_id', $this->product->id)
                ->where('branch_id', $this->branchId)
                ->selectRaw("SUM(CASE WHEN direction = 'in' THEN qty ELSE -qty END) as stock")
                ->value('stock') ?? 0;
        }

        $movementTypes = [
            'all' => __('All Activity'),
            'sale' => __('Sales'),
            'purchase' => __('Purchases'),
            'transfer' => __('Transfers'),
            'adjustment' => __('Adjustments'),
            'return' => __('Returns'),
            'audit' => __('Audit Logs'),
        ];

        return view('livewire.inventory.product-history', [
            'stockMovements' => $stockMovements,
            'auditLogs' => $auditLogs,
            'currentStock' => $currentStock,
            'movementTypes' => $movementTypes,
        ]);
    }

    public function updatedFilterType(): void
    {
        $this->resetPage();
    }

    public function updatedDateFrom(): void
    {
        $this->resetPage();
    }

    public function updatedDateTo(): void
    {
        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->filterType = 'all';
        $this->dateFrom = null;
        $this->dateTo = null;
        $this->resetPage();
    }
}
