<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Reports;

use App\Models\Product;
use App\Services\StockService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Component;

class InventoryChartsDashboard extends Component
{
    public ?int $branchId = null;

    #[Layout('layouts.app')]
    public function render()
    {
        $user = Auth::user();

        if (! $user || ! $user->can('reports.inventory.charts')) {
            abort(403);
        }

        $query = Product::query()
            ->select('products.id', 'products.sku', 'products.name')
            ->selectRaw(StockService::getStockCalculationExpression().' as current_stock');

        if ($this->branchId) {
            $query->where('branch_id', $this->branchId);
        }

        $products = $query->orderBy('current_stock')->get();

        $totalProducts = $products->count();
        $totalStock = (float) $products->sum('current_stock');

        $lowStock = $products->sortBy('current_stock')->take(20);

        $labels = [];
        $values = [];

        foreach ($lowStock as $product) {
            $labels[] = $product->sku ?: $product->name;
            $values[] = (float) $product->current_stock;
        }

        $chartLowStock = [
            'labels' => $labels,
            'values' => $values,
        ];

        $this->dispatch('inventory-charts-update', chartData: [
            'lowStock' => $chartLowStock,
        ]);

        return view('livewire.admin.reports.inventory-charts-dashboard', [
            'totalProducts' => $totalProducts,
            'totalStock' => $totalStock,
        ]);
    }
}
