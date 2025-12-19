<?php

declare(strict_types=1);

namespace App\Livewire\Dashboard;

use App\Models\Branch;
use App\Models\Product;
use App\Models\Sale;
use App\Models\User;
use App\Services\StockService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Component;

class Index extends Component
{
    #[Layout('layouts.app')]
    public array $stats = [];

    public array $salesChartData = [];

    public array $inventoryChartData = [];

    public array $paymentMethodsData = [];

    public array $lowStockProducts = [];

    public array $recentSales = [];

    public ?int $branchId = null;

    public bool $isAdmin = false;

    public array $trendIndicators = [];

    protected int $cacheTtl = 300;

    public function mount(): void
    {
        $user = Auth::user();
        if (! $user || ! $user->can('dashboard.view')) {
            abort(403);
        }

        $this->branchId = $user->branch_id;
        $this->isAdmin = $user->hasRole('super-admin') || $user->hasRole('admin');
        
        // Load cache TTL from system settings
        $this->cacheTtl = (int) (\App\Models\SystemSetting::where('key', 'advanced.cache_ttl')->value('value') ?? 300);

        $this->loadStats();
        $this->loadChartData();
        $this->loadLowStockProducts();
        $this->loadRecentSales();
        $this->loadTrendIndicators();
    }

    public function refreshData(): void
    {
        $cacheKey = $this->getCachePrefix();
        Cache::forget("{$cacheKey}:stats");
        Cache::forget("{$cacheKey}:chart_data");
        Cache::forget("{$cacheKey}:low_stock");
        Cache::forget("{$cacheKey}:recent_sales");
        Cache::forget("{$cacheKey}:trends");

        $this->loadStats();
        $this->loadChartData();
        $this->loadLowStockProducts();
        $this->loadRecentSales();
        $this->loadTrendIndicators();
    }

    protected function getCachePrefix(): string
    {
        return "dashboard:branch_{$this->branchId}:admin_{$this->isAdmin}";
    }

    protected function scopeSalesQuery($query)
    {
        if (! $this->isAdmin && $this->branchId) {
            return $query->where('branch_id', $this->branchId);
        }

        return $query;
    }

    protected function scopeProductsQuery($query)
    {
        if (! $this->isAdmin && $this->branchId) {
            return $query->where('branch_id', $this->branchId);
        }

        return $query;
    }

    protected function loadStats(): void
    {
        $cacheKey = "{$this->getCachePrefix()}:stats";

        $this->stats = Cache::remember($cacheKey, $this->cacheTtl, function () {
            $today = now()->startOfDay();
            $startOfMonth = now()->startOfMonth();

            $salesQuery = $this->scopeSalesQuery(Sale::query());
            $productsQuery = $this->scopeProductsQuery(Product::query());

            $todaySales = (clone $salesQuery)->whereDate('created_at', $today)->sum('grand_total') ?? 0;
            $monthSales = (clone $salesQuery)->where('created_at', '>=', $startOfMonth)->sum('grand_total') ?? 0;
            $openInvoices = (clone $salesQuery)->where('status', 'pending')->count();

            $activeBranches = $this->isAdmin
                ? Branch::where('is_active', true)->count()
                : 1;

            $activeUsers = $this->isAdmin
                ? User::where('is_active', true)->count()
                : User::where('is_active', true)->where('branch_id', $this->branchId)->count();

            $totalProducts = (clone $productsQuery)->count();
            $lowStockCount = (clone $productsQuery)
                ->whereNotNull('min_stock')
                ->where('min_stock', '>', 0)
                ->whereRaw('COALESCE((SELECT SUM(CASE WHEN direction = \'in\' THEN qty ELSE -qty END) FROM stock_movements WHERE stock_movements.product_id = products.id), 0) <= min_stock')
                ->count();

            return [
                'today_sales' => number_format($todaySales, 2),
                'month_sales' => number_format($monthSales, 2),
                'open_invoices' => $openInvoices,
                'active_branches' => $activeBranches,
                'active_users' => $activeUsers,
                'total_products' => $totalProducts,
                'low_stock_count' => $lowStockCount,
            ];
        });
    }

    protected function loadChartData(): void
    {
        $cacheKey = "{$this->getCachePrefix()}:chart_data";

        $chartData = Cache::remember($cacheKey, $this->cacheTtl, function () {
            $labels = [];
            $salesData = [];

            for ($i = 6; $i >= 0; $i--) {
                $date = now()->subDays($i);
                $labels[] = $date->format('D');
                $salesData[] = (float) $this->scopeSalesQuery(Sale::query())->whereDate('created_at', $date)->sum('grand_total');
            }

            $salesChartData = [
                'labels' => $labels,
                'data' => $salesData,
            ];

            $paymentMethodsRaw = DB::table('sale_payments')
                ->join('sales', 'sale_payments.sale_id', '=', 'sales.id')
                ->whereMonth('sales.created_at', now()->month)
                ->when(! $this->isAdmin && $this->branchId, fn ($q) => $q->where('sales.branch_id', $this->branchId))
                ->whereNull('sales.deleted_at')
                ->select('sale_payments.payment_method', DB::raw('COUNT(*) as count'), DB::raw('SUM(sale_payments.amount) as total'))
                ->groupBy('sale_payments.payment_method')
                ->get();

            $paymentMethodsData = [
                'labels' => $paymentMethodsRaw->pluck('payment_method')->map(fn ($m) => ucfirst($m ?? 'cash'))->toArray(),
                'data' => $paymentMethodsRaw->pluck('count')->toArray(),
                'totals' => $paymentMethodsRaw->pluck('total')->toArray(),
            ];

            $productsQuery = $this->scopeProductsQuery(Product::query());

            $inventoryChartData = [
                'labels' => [__('In Stock'), __('Low Stock'), __('Out of Stock')],
                'data' => [
                    DB::table('products')
                        ->whereNull('deleted_at')
                        ->when(! $this->isAdmin && $this->branchId, fn ($q) => $q->where('branch_id', $this->branchId))
                        ->whereRaw('COALESCE((SELECT SUM(CASE WHEN direction = \'in\' THEN qty ELSE -qty END) FROM stock_movements WHERE stock_movements.product_id = products.id), 0) > COALESCE(min_stock, 0)')
                        ->count(),
                    DB::table('products')
                        ->whereNull('deleted_at')
                        ->when(! $this->isAdmin && $this->branchId, fn ($q) => $q->where('branch_id', $this->branchId))
                        ->whereNotNull('min_stock')
                        ->where('min_stock', '>', 0)
                        ->whereRaw('COALESCE((SELECT SUM(CASE WHEN direction = \'in\' THEN qty ELSE -qty END) FROM stock_movements WHERE stock_movements.product_id = products.id), 0) <= min_stock')
                        ->whereRaw('COALESCE((SELECT SUM(CASE WHEN direction = \'in\' THEN qty ELSE -qty END) FROM stock_movements WHERE stock_movements.product_id = products.id), 0) > 0')
                        ->count(),
                    DB::table('products')
                        ->whereNull('deleted_at')
                        ->when(! $this->isAdmin && $this->branchId, fn ($q) => $q->where('branch_id', $this->branchId))
                        ->whereRaw('COALESCE((SELECT SUM(CASE WHEN direction = \'in\' THEN qty ELSE -qty END) FROM stock_movements WHERE stock_movements.product_id = products.id), 0) <= 0')
                        ->count(),
                ],
            ];

            return [
                'sales' => $salesChartData,
                'payment' => $paymentMethodsData,
                'inventory' => $inventoryChartData,
            ];
        });

        $this->salesChartData = $chartData['sales'];
        $this->paymentMethodsData = $chartData['payment'];
        $this->inventoryChartData = $chartData['inventory'];
    }

    protected function loadLowStockProducts(): void
    {
        $cacheKey = "{$this->getCachePrefix()}:low_stock";

        $this->lowStockProducts = Cache::remember($cacheKey, $this->cacheTtl, function () {
            $stockExpr = StockService::getStockCalculationExpression();
            
            return $this->scopeProductsQuery(Product::query())
                ->select('products.*')
                ->selectRaw("{$stockExpr} as current_quantity")
                ->with('category')
                ->whereRaw("{$stockExpr} <= products.min_stock")
                ->where('products.min_stock', '>', 0)
                ->where('products.track_stock_alerts', true)
                ->orderByRaw($stockExpr)
                ->limit(5)
                ->get()
                ->map(fn ($p) => [
                    'id' => $p->id,
                    'name' => $p->name,
                    'quantity' => $p->current_quantity ?? 0,
                    'min_stock' => $p->min_stock,
                    'category' => $p->category?->name ?? '-',
                ])
                ->toArray();
        });
    }

    protected function loadRecentSales(): void
    {
        $cacheKey = "{$this->getCachePrefix()}:recent_sales";

        $this->recentSales = Cache::remember($cacheKey, 60, function () {
            return $this->scopeSalesQuery(Sale::query())
                ->with(['user', 'customer'])
                ->latest()
                ->limit(5)
                ->get()
                ->map(fn ($s) => [
                    'id' => $s->id,
                    'reference' => $s->reference_no ?? "#{$s->id}",
                    'customer' => $s->customer?->name ?? __('Walk-in'),
                    'total' => number_format($s->grand_total ?? 0, 2),
                    'status' => $s->status,
                    'date' => $s->created_at->format('Y-m-d H:i'),
                ])
                ->toArray();
        });
    }

    protected function loadTrendIndicators(): void
    {
        $cacheKey = "{$this->getCachePrefix()}:trends";

        $this->trendIndicators = Cache::remember($cacheKey, $this->cacheTtl, function () {
            $salesQuery = $this->scopeSalesQuery(Sale::query());

            $currentWeekSales = (clone $salesQuery)
                ->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])
                ->sum('grand_total') ?? 0;

            $previousWeekSales = (clone $salesQuery)
                ->whereBetween('created_at', [now()->subWeek()->startOfWeek(), now()->subWeek()->endOfWeek()])
                ->sum('grand_total') ?? 0;

            $invoiceTotal = (clone $salesQuery)->count();
            $invoiceCleared = (clone $salesQuery)->where('status', 'completed')->count();
            $inventoryHealth = $this->stats['total_products'] ?? 0;
            $lowStock = $this->stats['low_stock_count'] ?? 0;

            return [
                'weekly_sales' => [
                    'current' => number_format($currentWeekSales, 2),
                    'previous' => number_format($previousWeekSales, 2),
                    'change' => $this->calculatePercentageChange($currentWeekSales, $previousWeekSales),
                ],
                'invoice_clear_rate' => $invoiceTotal > 0
                    ? round(($invoiceCleared / $invoiceTotal) * 100, 1)
                    : 0,
                'inventory_health' => $inventoryHealth > 0
                    ? round(max(0, min(100, 100 - (($lowStock / $inventoryHealth) * 100))), 1)
                    : 100,
            ];
        });
    }

    protected function calculatePercentageChange(float $current, float $previous): float
    {
        if ($previous <= 0 && $current > 0) {
            return 100.0;
        }

        if ($previous === 0.0) {
            return 0.0;
        }

        return round((($current - $previous) / $previous) * 100, 1);
    }

    public function render()
    {
        return view('livewire.dashboard.index');
    }
}
