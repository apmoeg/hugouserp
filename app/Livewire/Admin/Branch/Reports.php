<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Branch;

use App\Models\Branch;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Sale;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Branch Reports - Branch Admin Page
 * Allows branch admins/managers to view branch-specific reports
 */
class Reports extends Component
{
    public ?Branch $branch = null;

    public string $period = 'month'; // day, week, month, year

    public ?string $fromDate = null;

    public ?string $toDate = null;

    public function mount(): void
    {
        $user = Auth::user();

        if (! $user || ! $user->can('branch.reports.view')) {
            abort(403);
        }

        $this->branch = $user->branch;

        if (! $this->branch) {
            abort(403, __('No branch assigned to this user.'));
        }

        // Set default date range
        $this->fromDate = now()->startOfMonth()->format('Y-m-d');
        $this->toDate = now()->endOfMonth()->format('Y-m-d');
    }

    public function updatedPeriod(): void
    {
        switch ($this->period) {
            case 'day':
                $this->fromDate = now()->format('Y-m-d');
                $this->toDate = now()->format('Y-m-d');
                break;
            case 'week':
                $this->fromDate = now()->startOfWeek()->format('Y-m-d');
                $this->toDate = now()->endOfWeek()->format('Y-m-d');
                break;
            case 'month':
                $this->fromDate = now()->startOfMonth()->format('Y-m-d');
                $this->toDate = now()->endOfMonth()->format('Y-m-d');
                break;
            case 'year':
                $this->fromDate = now()->startOfYear()->format('Y-m-d');
                $this->toDate = now()->endOfYear()->format('Y-m-d');
                break;
        }
    }

    /**
     * Get sales statistics for the branch
     */
    public function getSalesStats(): array
    {
        $query = Sale::where('branch_id', $this->branch->id)
            ->whereBetween('created_at', [$this->fromDate.' 00:00:00', $this->toDate.' 23:59:59']);

        return [
            'total_sales' => (clone $query)->count(),
            'total_amount' => (clone $query)->sum('total'),
            'average_sale' => (clone $query)->avg('total') ?? 0,
            'paid_amount' => (clone $query)->sum('paid'),
            'due_amount' => (clone $query)->sum('due_total'),
        ];
    }

    /**
     * Get inventory statistics for the branch
     */
    public function getInventoryStats(): array
    {
        $query = Product::where('branch_id', $this->branch->id);

        return [
            'total_products' => (clone $query)->count(),
            'total_value' => (clone $query)->sum(DB::raw('COALESCE(default_price, 0) * COALESCE(stock_quantity, 0)')),
            'low_stock' => (clone $query)
                ->whereNotNull('min_stock')
                ->where('min_stock', '>', 0)
                ->whereColumn('stock_quantity', '<=', 'min_stock')
                ->count(),
            'out_of_stock' => (clone $query)->where('stock_quantity', '<=', 0)->count(),
        ];
    }

    /**
     * Get customer statistics for the branch
     */
    public function getCustomerStats(): array
    {
        $query = Customer::where('branch_id', $this->branch->id);

        $newCustomers = (clone $query)
            ->whereBetween('created_at', [$this->fromDate.' 00:00:00', $this->toDate.' 23:59:59'])
            ->count();

        return [
            'total_customers' => (clone $query)->count(),
            'new_customers' => $newCustomers,
            'active_customers' => (clone $query)->where('is_active', true)->count(),
        ];
    }

    /**
     * Get top selling products for the branch
     */
    public function getTopProducts(): array
    {
        return DB::table('sale_items')
            ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
            ->join('products', 'sale_items.product_id', '=', 'products.id')
            ->where('sales.branch_id', $this->branch->id)
            ->whereBetween('sales.created_at', [$this->fromDate.' 00:00:00', $this->toDate.' 23:59:59'])
            ->select('products.name', DB::raw('SUM(sale_items.quantity) as total_qty'), DB::raw('SUM(sale_items.total) as total_amount'))
            ->groupBy('sale_items.product_id', 'products.name')
            ->orderByDesc('total_qty')
            ->limit(10)
            ->get()
            ->toArray();
    }

    /**
     * Get daily sales for chart
     */
    public function getDailySales(): array
    {
        return Sale::where('branch_id', $this->branch->id)
            ->whereBetween('created_at', [$this->fromDate.' 00:00:00', $this->toDate.' 23:59:59'])
            ->select(DB::raw('DATE(created_at) as date'), DB::raw('SUM(total) as total'))
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->toArray();
    }

    #[Layout('layouts.app')]
    public function render()
    {
        $user = Auth::user();

        if (! $user || ! $user->can('branch.reports.view')) {
            abort(403);
        }

        return view('livewire.admin.branch.reports', [
            'salesStats' => $this->getSalesStats(),
            'inventoryStats' => $this->getInventoryStats(),
            'customerStats' => $this->getCustomerStats(),
            'topProducts' => $this->getTopProducts(),
            'dailySales' => $this->getDailySales(),
        ]);
    }
}
