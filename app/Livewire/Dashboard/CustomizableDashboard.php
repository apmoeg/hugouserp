<?php

declare(strict_types=1);

namespace App\Livewire\Dashboard;

use App\Livewire\Concerns\LoadsDashboardData;
use App\Models\SystemSetting;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Customizable Dashboard with Drag-and-Drop Widget System
 * 
 * Features:
 * - Reorderable widgets via drag-and-drop
 * - Show/hide widgets per user preference
 * - Multiple layout options
 * - Saved user preferences
 * 
 * Uses shared LoadsDashboardData trait for optimized data loading.
 */
class CustomizableDashboard extends Component
{
    use LoadsDashboardData;

    #[Layout('layouts.app')]
    
    // Dashboard configuration
    public array $widgets = [];
    public array $widgetOrder = [];
    public array $hiddenWidgets = [];
    public string $layoutMode = 'default'; // default, compact, expanded
    
    // Data
    public array $stats = [];
    public array $salesChartData = [];
    public array $inventoryChartData = [];
    public array $paymentMethodsData = [];
    public array $lowStockProducts = [];
    public array $recentSales = [];
    public array $trendIndicators = [];
    
    // UI state
    public bool $isEditing = false;

    /**
     * Available widgets configuration
     */
    protected array $availableWidgets = [
        'quick_actions' => [
            'title' => 'Quick Actions',
            'icon' => 'zap',
            'size' => 'full',
            'default_enabled' => true,
            'permission' => null,
        ],
        'stats_cards' => [
            'title' => 'Stats Overview',
            'icon' => 'bar-chart-2',
            'size' => 'full',
            'default_enabled' => true,
            'permission' => 'dashboard.view',
        ],
        'performance' => [
            'title' => 'Performance Insights',
            'icon' => 'trending-up',
            'size' => 'full',
            'default_enabled' => true,
            'permission' => 'dashboard.view',
        ],
        'sales_chart' => [
            'title' => 'Sales Trend',
            'icon' => 'line-chart',
            'size' => 'large',
            'default_enabled' => true,
            'permission' => 'sales.view',
        ],
        'inventory_chart' => [
            'title' => 'Inventory Status',
            'icon' => 'pie-chart',
            'size' => 'medium',
            'default_enabled' => true,
            'permission' => 'inventory.products.view',
        ],
        'payment_mix' => [
            'title' => 'Payment Methods',
            'icon' => 'credit-card',
            'size' => 'medium',
            'default_enabled' => true,
            'permission' => 'sales.view',
        ],
        'low_stock' => [
            'title' => 'Low Stock Alerts',
            'icon' => 'alert-triangle',
            'size' => 'half',
            'default_enabled' => true,
            'permission' => 'inventory.products.view',
        ],
        'recent_sales' => [
            'title' => 'Recent Sales',
            'icon' => 'shopping-cart',
            'size' => 'half',
            'default_enabled' => true,
            'permission' => 'sales.view',
        ],
        'quick_stats' => [
            'title' => 'Quick Stats',
            'icon' => 'activity',
            'size' => 'full',
            'default_enabled' => true,
            'permission' => 'dashboard.view',
        ],
    ];

    public function mount(): void
    {
        $user = Auth::user();
        if (!$user || !$user->can('dashboard.view')) {
            abort(403);
        }

        $this->initializeDashboardContext();

        // Load user's dashboard preferences
        $this->loadUserPreferences();
        
        // Load all data using the shared trait
        $this->loadAllDashboardData();
    }

    /**
     * Load user's dashboard preferences
     */
    protected function loadUserPreferences(): void
    {
        $user = Auth::user();
        $preferences = $user->preferences ?? [];
        
        // Get saved widget order or use defaults
        $this->widgetOrder = $preferences['dashboard_widget_order'] ?? array_keys($this->availableWidgets);
        $this->hiddenWidgets = $preferences['dashboard_hidden_widgets'] ?? [];
        $this->layoutMode = $preferences['dashboard_layout_mode'] ?? 'default';
        
        // Build widgets array with visibility
        $this->widgets = [];
        foreach ($this->widgetOrder as $widgetKey) {
            if (isset($this->availableWidgets[$widgetKey])) {
                $widget = $this->availableWidgets[$widgetKey];
                $widget['key'] = $widgetKey;
                $widget['visible'] = !in_array($widgetKey, $this->hiddenWidgets);
                
                // Check permission
                if ($widget['permission'] && !Auth::user()->can($widget['permission'])) {
                    continue; // Skip widgets user doesn't have permission for
                }
                
                $this->widgets[] = $widget;
            }
        }
        
        // Add any new widgets not in saved order
        foreach ($this->availableWidgets as $key => $widget) {
            if (!in_array($key, $this->widgetOrder)) {
                if ($widget['permission'] && !Auth::user()->can($widget['permission'])) {
                    continue;
                }
                $widget['key'] = $key;
                $widget['visible'] = $widget['default_enabled'];
                $this->widgets[] = $widget;
            }
        }
    }

    /**
     * Toggle edit mode for dashboard customization
     */
    public function toggleEditMode(): void
    {
        $this->isEditing = !$this->isEditing;
    }

    /**
     * Update widget order (called from drag-drop JS)
     */
    public function updateWidgetOrder(array $order): void
    {
        $this->widgetOrder = $order;
        $this->saveUserPreferences();
        $this->loadUserPreferences();
    }

    /**
     * Toggle widget visibility
     */
    public function toggleWidget(string $widgetKey): void
    {
        if (in_array($widgetKey, $this->hiddenWidgets)) {
            $this->hiddenWidgets = array_values(array_diff($this->hiddenWidgets, [$widgetKey]));
        } else {
            $this->hiddenWidgets[] = $widgetKey;
        }
        
        $this->saveUserPreferences();
        $this->loadUserPreferences();
    }

    /**
     * Change layout mode
     */
    public function setLayoutMode(string $mode): void
    {
        if (in_array($mode, ['default', 'compact', 'expanded'])) {
            $this->layoutMode = $mode;
            $this->saveUserPreferences();
        }
    }

    /**
     * Reset dashboard to defaults
     */
    public function resetDashboard(): void
    {
        $this->widgetOrder = array_keys($this->availableWidgets);
        $this->hiddenWidgets = [];
        $this->layoutMode = 'default';
        $this->saveUserPreferences();
        $this->loadUserPreferences();
    }

    /**
     * Save user preferences
     */
    protected function saveUserPreferences(): void
    {
        $user = Auth::user();
        $preferences = $user->preferences ?? [];
        
        $preferences['dashboard_widget_order'] = $this->widgetOrder;
        $preferences['dashboard_hidden_widgets'] = $this->hiddenWidgets;
        $preferences['dashboard_layout_mode'] = $this->layoutMode;
        
        $user->preferences = $preferences;
        $user->save();
    }

    /**
     * Refresh data (clear cache and reload)
     */
    public function refreshData(): void
    {
        $this->refreshDashboardData();
    }

    public function render(): View
    {
        return view('livewire.dashboard.customizable-dashboard');
    }
}
