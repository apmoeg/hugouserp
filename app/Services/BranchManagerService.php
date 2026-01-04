<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Branch;
use App\Models\Product;
use App\Models\Sale;
use App\Models\Customer;
use App\Models\Purchase;
use App\Models\Supplier;
use App\Traits\HandlesServiceErrors;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * BranchManagerService - Simplified operations for branch managers
 * 
 * PURPOSE: Provides easy-to-use methods for branch managers
 * FEATURES:
 *   - Quick statistics and reports
 *   - Simplified CRUD operations
 *   - Branch-scoped data access
 *   - Common business operations
 */
class BranchManagerService
{
    use HandlesServiceErrors;

    protected const CACHE_TTL = 300; // 5 minutes

    /**
     * Get branch dashboard summary
     */
    public function getDashboardSummary(int $branchId): array
    {
        return $this->handleServiceOperation(
            callback: function () use ($branchId) {
                $cacheKey = "branch_summary:{$branchId}";
                
                return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($branchId) {
                    return [
                        'sales' => $this->getSalesSummary($branchId),
                        'inventory' => $this->getInventorySummary($branchId),
                        'customers' => $this->getCustomersSummary($branchId),
                        'purchases' => $this->getPurchasesSummary($branchId),
                    ];
                });
            },
            operation: 'getDashboardSummary',
            context: ['branch_id' => $branchId],
            defaultValue: []
        );
    }

    /**
     * Get sales summary for branch
     */
    public function getSalesSummary(int $branchId): array
    {
        return [
            'today' => Sale::forBranch($branchId)->today()->sum('grand_total') ?? 0,
            'today_count' => Sale::forBranch($branchId)->today()->count(),
            'this_week' => Sale::forBranch($branchId)->thisWeek()->sum('grand_total') ?? 0,
            'this_month' => Sale::forBranch($branchId)->thisMonth()->sum('grand_total') ?? 0,
            'pending' => Sale::forBranch($branchId)->byStatus('pending')->count(),
            'recent' => Sale::forBranch($branchId)
                ->with('customer:id,name')
                ->recent(5)
                ->get(['id', 'code', 'customer_id', 'grand_total', 'status', 'created_at']),
        ];
    }

    /**
     * Get inventory summary for branch
     */
    public function getInventorySummary(int $branchId): array
    {
        $query = Product::forBranch($branchId);
        
        return [
            'total_products' => (clone $query)->count(),
            'active_products' => (clone $query)->active()->count(),
            'low_stock' => (clone $query)->lowStock()->count(),
            'out_of_stock' => (clone $query)->outOfStock()->count(),
            'expiring_soon' => (clone $query)->expiringSoon(30)->count(),
            'total_value' => (clone $query)
                ->selectRaw('SUM(stock_quantity * COALESCE(cost, 0)) as total')
                ->value('total') ?? 0,
        ];
    }

    /**
     * Get customers summary for branch
     */
    public function getCustomersSummary(int $branchId): array
    {
        $query = Customer::forBranch($branchId);
        
        return [
            'total' => (clone $query)->count(),
            'active' => (clone $query)->active()->count(),
            'new_this_month' => (clone $query)->thisMonth()->count(),
        ];
    }

    /**
     * Get purchases summary for branch
     */
    public function getPurchasesSummary(int $branchId): array
    {
        $query = Purchase::forBranch($branchId);
        
        return [
            'this_month' => (clone $query)->thisMonth()->sum('grand_total') ?? 0,
            'pending' => (clone $query)->byStatus('pending')->count(),
            'received' => (clone $query)->byStatus('received')->thisMonth()->count(),
        ];
    }

    /**
     * Quick search across all entities
     */
    public function quickSearch(int $branchId, string $keyword): array
    {
        return $this->handleServiceOperation(
            callback: function () use ($branchId, $keyword) {
                return [
                    'products' => Product::forBranch($branchId)
                        ->searchKeyword($keyword, ['name', 'sku', 'barcode'])
                        ->limit(5)
                        ->get(['id', 'name', 'sku', 'default_price', 'stock_quantity']),
                    'customers' => Customer::forBranch($branchId)
                        ->searchKeyword($keyword, ['name', 'email', 'phone'])
                        ->limit(5)
                        ->get(['id', 'name', 'email', 'phone']),
                    'sales' => Sale::forBranch($branchId)
                        ->searchKeyword($keyword, ['code', 'reference_no'])
                        ->limit(5)
                        ->get(['id', 'code', 'grand_total', 'status', 'created_at']),
                ];
            },
            operation: 'quickSearch',
            context: ['branch_id' => $branchId, 'keyword' => $keyword],
            defaultValue: ['products' => [], 'customers' => [], 'sales' => []]
        );
    }

    /**
     * Get select options for dropdowns (cached)
     */
    public function getSelectOptions(int $branchId, string $entity): array
    {
        return $this->handleServiceOperation(
            callback: function () use ($branchId, $entity) {
                $cacheKey = "select_options:{$branchId}:{$entity}";
                
                return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($branchId, $entity) {
                    return match($entity) {
                        'customers' => Customer::forBranch($branchId)
                            ->active()
                            ->orderBy('name')
                            ->pluck('name', 'id')
                            ->toArray(),
                        'suppliers' => Supplier::forBranch($branchId)
                            ->active()
                            ->orderBy('name')
                            ->pluck('name', 'id')
                            ->toArray(),
                        'products' => Product::forBranch($branchId)
                            ->active()
                            ->orderBy('name')
                            ->get(['id', 'name', 'sku', 'default_price'])
                            ->mapWithKeys(fn($p) => [$p->id => "{$p->name} ({$p->sku})"])
                            ->toArray(),
                        'categories' => \App\Models\ProductCategory::forBranch($branchId)
                            ->orderBy('name')
                            ->pluck('name', 'id')
                            ->toArray(),
                        default => [],
                    };
                });
            },
            operation: 'getSelectOptions',
            context: ['branch_id' => $branchId, 'entity' => $entity],
            defaultValue: []
        );
    }

    /**
     * Clear branch caches
     */
    public function clearBranchCache(int $branchId): void
    {
        $this->handleServiceOperation(
            callback: function () use ($branchId) {
                Cache::forget("branch_summary:{$branchId}");
                Cache::forget("select_options:{$branchId}:customers");
                Cache::forget("select_options:{$branchId}:suppliers");
                Cache::forget("select_options:{$branchId}:products");
                Cache::forget("select_options:{$branchId}:categories");
            },
            operation: 'clearBranchCache',
            context: ['branch_id' => $branchId]
        );
    }

    /**
     * Get quick actions available for branch manager
     */
    public function getQuickActions(int $branchId): array
    {
        return [
            [
                'key' => 'new_sale',
                'label' => __('New Sale'),
                'icon' => 'shopping-cart',
                'route' => 'sales.create',
                'color' => 'emerald',
            ],
            [
                'key' => 'new_purchase',
                'label' => __('New Purchase'),
                'icon' => 'truck',
                'route' => 'purchases.create',
                'color' => 'blue',
            ],
            [
                'key' => 'add_product',
                'label' => __('Add Product'),
                'icon' => 'package',
                'route' => 'inventory.products.create',
                'color' => 'purple',
            ],
            [
                'key' => 'add_customer',
                'label' => __('Add Customer'),
                'icon' => 'user-plus',
                'route' => 'crm.customers.create',
                'color' => 'pink',
            ],
            [
                'key' => 'pos',
                'label' => __('POS Terminal'),
                'icon' => 'monitor',
                'route' => 'pos',
                'color' => 'orange',
            ],
            [
                'key' => 'reports',
                'label' => __('Reports'),
                'icon' => 'bar-chart-2',
                'route' => 'reports.index',
                'color' => 'slate',
            ],
        ];
    }

    /**
     * Get recent activity for branch
     */
    public function getRecentActivity(int $branchId, int $limit = 20): Collection
    {
        return $this->handleServiceOperation(
            callback: function () use ($branchId, $limit) {
                return \App\Models\AuditLog::where('branch_id', $branchId)
                    ->with('user:id,name')
                    ->orderByDesc('created_at')
                    ->limit($limit)
                    ->get()
                    ->map(fn($log) => [
                        'id' => $log->id,
                        'action' => $log->action,
                        'entity' => $log->entity,
                        'entity_id' => $log->entity_id,
                        'user' => $log->user?->name ?? __('System'),
                        'description' => $log->description,
                        'created_at' => $log->created_at->diffForHumans(),
                    ]);
            },
            operation: 'getRecentActivity',
            context: ['branch_id' => $branchId, 'limit' => $limit],
            defaultValue: collect()
        );
    }
}
