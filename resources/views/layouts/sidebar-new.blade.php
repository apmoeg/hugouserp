{{-- 
    New Sidebar Component - Professional, Responsive, RTL/LTR Ready
    Built from scratch following best practices:
    - Responsive: Desktop fixed, Mobile drawer/off-canvas
    - RTL/LTR: Full support using CSS logical properties
    - One level expand/collapse only
    - Active state based on route name
    - Auto-expand parent for child routes
--}}
@php
    use Illuminate\Support\Str;
    use Illuminate\Support\Facades\Route;

    $locale = app()->getLocale();
    $dir = $locale === 'ar' ? 'rtl' : 'ltr';
    $currentRoute = request()->route()?->getName() ?? '';
    $user = auth()->user();

    // Helper to check if route is active
    $isActive = function ($routes) use ($currentRoute) {
        $routes = (array) $routes;
        foreach ($routes as $route) {
            if ($route && str_starts_with($currentRoute, $route)) {
                return true;
            }
        }
        return false;
    };

    // Helper to check if route exists
    $routeExists = function (?string $route) {
        return $route && Route::has($route);
    };

    // Helper to safely get route URL
    $safeRoute = function (?string $route) use ($routeExists) {
        return $routeExists($route) ? route($route) : '#';
    };

    // Helper to check permissions
    $canAccess = function ($permission) use ($user) {
        if (!$permission) return true;
        if (!$user) return false;
        if ($user->hasRole('Super Admin')) return true;
        
        if (is_array($permission)) {
            foreach ($permission as $perm) {
                if ($perm && !$user->can($perm)) return false;
            }
            return true;
        }
        return $user->can($permission);
    };

    // Sidebar menu structure with icons (SVG paths)
    $menuSections = [
        [
            'key' => 'workspace',
            'title' => __('Workspace'),
            'items' => [
                [
                    'route' => 'dashboard',
                    'label' => __('Dashboard'),
                    'permission' => 'dashboard.view',
                    'icon' => 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6',
                ],
                [
                    'route' => 'pos.terminal',
                    'label' => __('POS Terminal'),
                    'permission' => 'pos.use',
                    'icon' => 'M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z',
                    'children' => [
                        ['route' => 'pos.daily.report', 'label' => __('Daily Report'), 'permission' => 'pos.daily-report.view'],
                    ],
                ],
                [
                    'route' => 'admin.reports.index',
                    'label' => __('Reports Hub'),
                    'permission' => 'reports.view',
                    'icon' => 'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z',
                ],
            ],
        ],
        [
            'key' => 'sales_purchases',
            'title' => __('Sales & Purchases'),
            'items' => [
                [
                    'route' => 'app.sales.index',
                    'label' => __('Sales'),
                    'permission' => 'sales.view',
                    'icon' => 'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
                    'children' => [
                        ['route' => 'app.sales.create', 'label' => __('New Sale'), 'permission' => 'sales.manage'],
                        ['route' => 'app.sales.returns.index', 'label' => __('Returns'), 'permission' => 'sales.return'],
                        ['route' => 'app.sales.analytics', 'label' => __('Analytics'), 'permission' => 'sales.view'],
                    ],
                ],
                [
                    'route' => 'app.purchases.index',
                    'label' => __('Purchases'),
                    'permission' => 'purchases.view',
                    'icon' => 'M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z',
                    'children' => [
                        ['route' => 'app.purchases.create', 'label' => __('New Purchase'), 'permission' => 'purchases.manage'],
                        ['route' => 'app.purchases.returns.index', 'label' => __('Returns'), 'permission' => 'purchases.return'],
                        ['route' => 'app.purchases.requisitions.index', 'label' => __('Requisitions'), 'permission' => 'purchases.requisitions.view'],
                    ],
                ],
                [
                    'route' => 'customers.index',
                    'label' => __('Customers'),
                    'permission' => 'customers.view',
                    'icon' => 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z',
                    'children' => [
                        ['route' => 'customers.create', 'label' => __('Add Customer'), 'permission' => 'customers.manage'],
                    ],
                ],
                [
                    'route' => 'suppliers.index',
                    'label' => __('Suppliers'),
                    'permission' => 'suppliers.view',
                    'icon' => 'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4',
                    'children' => [
                        ['route' => 'suppliers.create', 'label' => __('Add Supplier'), 'permission' => 'suppliers.manage'],
                    ],
                ],
            ],
        ],
        [
            'key' => 'inventory',
            'title' => __('Inventory & Warehouse'),
            'items' => [
                [
                    'route' => 'app.inventory.products.index',
                    'label' => __('Products'),
                    'permission' => 'inventory.products.view',
                    'icon' => 'M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4',
                    'children' => [
                        ['route' => 'app.inventory.products.create', 'label' => __('Add Product'), 'permission' => 'inventory.products.view'],
                        ['route' => 'app.inventory.categories.index', 'label' => __('Categories'), 'permission' => 'inventory.products.view'],
                        ['route' => 'app.inventory.units.index', 'label' => __('Units'), 'permission' => 'inventory.products.view'],
                        ['route' => 'app.inventory.stock-alerts', 'label' => __('Stock Alerts'), 'permission' => 'inventory.stock.alerts.view'],
                    ],
                ],
                [
                    'route' => 'app.inventory.index',
                    'label' => __('Inventory Overview'),
                    'permission' => 'inventory.products.view',
                    'icon' => 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01',
                ],
                [
                    'route' => 'app.warehouse.index',
                    'label' => __('Warehouse'),
                    'permission' => 'warehouse.view',
                    'icon' => 'M8 14v3m4-3v3m4-3v3M3 21h18M3 10h18M3 7l9-4 9 4M4 10h16v11H4V10z',
                    'children' => [
                        ['route' => 'app.warehouse.locations.index', 'label' => __('Locations'), 'permission' => 'warehouse.view'],
                        ['route' => 'app.warehouse.transfers.index', 'label' => __('Transfers'), 'permission' => 'warehouse.view'],
                        ['route' => 'app.warehouse.adjustments.index', 'label' => __('Adjustments'), 'permission' => 'warehouse.view'],
                    ],
                ],
            ],
        ],
        [
            'key' => 'finance',
            'title' => __('Finance & Banking'),
            'items' => [
                [
                    'route' => 'app.accounting.index',
                    'label' => __('Accounting'),
                    'permission' => 'accounting.view',
                    'icon' => 'M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z',
                    'children' => [
                        ['route' => 'app.accounting.accounts.create', 'label' => __('Add Account'), 'permission' => 'accounting.create'],
                        ['route' => 'app.accounting.journal-entries.create', 'label' => __('Journal Entry'), 'permission' => 'accounting.create'],
                    ],
                ],
                [
                    'route' => 'app.expenses.index',
                    'label' => __('Expenses'),
                    'permission' => 'expenses.view',
                    'icon' => 'M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z',
                    'children' => [
                        ['route' => 'app.expenses.create', 'label' => __('New Expense'), 'permission' => 'expenses.manage'],
                        ['route' => 'app.expenses.categories.index', 'label' => __('Categories'), 'permission' => 'expenses.manage'],
                    ],
                ],
                [
                    'route' => 'app.income.index',
                    'label' => __('Income'),
                    'permission' => 'income.view',
                    'icon' => 'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
                    'children' => [
                        ['route' => 'app.income.create', 'label' => __('New Income'), 'permission' => 'income.manage'],
                    ],
                ],
                [
                    'route' => 'app.banking.accounts.index',
                    'label' => __('Banking'),
                    'permission' => 'banking.view',
                    'icon' => 'M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z',
                    'children' => [
                        ['route' => 'app.banking.accounts.create', 'label' => __('Add Account'), 'permission' => 'banking.create'],
                        ['route' => 'app.banking.transactions.index', 'label' => __('Transactions'), 'permission' => 'banking.view'],
                    ],
                ],
            ],
        ],
        [
            'key' => 'hr',
            'title' => __('People & HR'),
            'items' => [
                [
                    'route' => 'app.hrm.index',
                    'label' => __('Human Resources'),
                    'permission' => 'hrm.employees.view',
                    'icon' => 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z',
                    'children' => [
                        ['route' => 'app.hrm.employees.index', 'label' => __('Employees'), 'permission' => 'hrm.employees.view'],
                        ['route' => 'app.hrm.attendance.index', 'label' => __('Attendance'), 'permission' => 'hrm.attendance.manage'],
                        ['route' => 'app.hrm.payroll.index', 'label' => __('Payroll'), 'permission' => 'hrm.payroll.manage'],
                    ],
                ],
            ],
        ],
        [
            'key' => 'operations',
            'title' => __('Operations'),
            'items' => [
                [
                    'route' => 'app.rental.index',
                    'label' => __('Rental'),
                    'permission' => 'rental.units.view',
                    'icon' => 'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4',
                    'children' => [
                        ['route' => 'app.rental.units.index', 'label' => __('Units'), 'permission' => 'rental.units.view'],
                        ['route' => 'app.rental.contracts.index', 'label' => __('Contracts'), 'permission' => 'rental.contracts.view'],
                    ],
                ],
                [
                    'route' => 'app.manufacturing.index',
                    'label' => __('Manufacturing'),
                    'permission' => 'manufacturing.view',
                    'icon' => 'M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z',
                    'children' => [
                        ['route' => 'app.manufacturing.boms.index', 'label' => __('BOMs'), 'permission' => 'manufacturing.view'],
                        ['route' => 'app.manufacturing.orders.index', 'label' => __('Production Orders'), 'permission' => 'manufacturing.view'],
                    ],
                ],
                [
                    'route' => 'app.fixed-assets.index',
                    'label' => __('Fixed Assets'),
                    'permission' => 'fixed-assets.view',
                    'icon' => 'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4',
                ],
                [
                    'route' => 'app.projects.index',
                    'label' => __('Projects'),
                    'permission' => 'projects.view',
                    'icon' => 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z',
                ],
                [
                    'route' => 'app.helpdesk.index',
                    'label' => __('Helpdesk'),
                    'permission' => 'helpdesk.view',
                    'icon' => 'M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192l-3.536 3.536M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-5 0a4 4 0 11-8 0 4 4 0 018 0z',
                ],
            ],
        ],
        [
            'key' => 'admin',
            'title' => __('Administration'),
            'items' => [
                [
                    'route' => 'admin.settings',
                    'label' => __('Settings'),
                    'permission' => 'settings.view',
                    'icon' => 'M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z',
                ],
                [
                    'route' => 'admin.users.index',
                    'label' => __('Users'),
                    'permission' => 'users.manage',
                    'icon' => 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z',
                ],
                [
                    'route' => 'admin.roles.index',
                    'label' => __('Roles'),
                    'permission' => 'roles.manage',
                    'icon' => 'M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z',
                ],
                [
                    'route' => 'admin.branches.index',
                    'label' => __('Branches'),
                    'permission' => 'branches.view',
                    'icon' => 'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4',
                ],
                [
                    'route' => 'admin.modules.index',
                    'label' => __('Modules'),
                    'permission' => 'modules.manage',
                    'icon' => 'M11 4a2 2 0 114 0v1a1 1 0 001 1h3a1 1 0 011 1v3a1 1 0 01-1 1h-1a2 2 0 100 4h1a1 1 0 011 1v3a1 1 0 01-1 1h-3a1 1 0 01-1-1v-1a2 2 0 10-4 0v1a1 1 0 01-1 1H7a1 1 0 01-1-1v-3a1 1 0 00-1-1H4a2 2 0 110-4h1a1 1 0 001-1V7a1 1 0 011-1h3a1 1 0 001-1V4z',
                ],
                [
                    'route' => 'admin.logs.audit',
                    'label' => __('Audit Logs'),
                    'permission' => 'logs.audit.view',
                    'icon' => 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01',
                ],
            ],
        ],
    ];

    // Filter sections based on permissions and route availability
    $filteredSections = collect($menuSections)->map(function ($section) use ($canAccess, $routeExists) {
        $items = collect($section['items'] ?? [])->map(function ($item) use ($canAccess, $routeExists) {
            if (!$canAccess($item['permission'] ?? null) || !$routeExists($item['route'] ?? null)) {
                return null;
            }

            $children = collect($item['children'] ?? [])->filter(function ($child) use ($canAccess, $routeExists) {
                return $canAccess($child['permission'] ?? null) && $routeExists($child['route'] ?? null);
            })->values()->all();

            $item['children'] = $children;
            return $item;
        })->filter()->values()->all();

        $section['items'] = $items;
        return $section;
    })->filter(fn ($section) => !empty($section['items']))->values()->all();
@endphp

{{-- Sidebar Overlay (Mobile only) --}}
<div 
    class="erp-sidebar-overlay"
    :class="sidebarOpen ? 'active' : ''"
    @click="sidebarOpen = false"
    x-cloak
></div>

{{-- Main Sidebar --}}
<aside 
    class="erp-sidebar"
    :class="sidebarOpen ? 'open' : ''"
    x-cloak
    x-data="{
        expandedSections: {},
        init() {
            // Auto-expand sections with active items
            @foreach($filteredSections as $sectionIndex => $section)
                @foreach($section['items'] as $itemIndex => $item)
                    @if($isActive($item['route']) || collect($item['children'] ?? [])->contains(fn($c) => $isActive($c['route'])))
                        this.expandedSections['{{ $section['key'] }}_{{ $itemIndex }}'] = true;
                    @endif
                @endforeach
            @endforeach
        },
        toggle(key) {
            this.expandedSections[key] = !this.expandedSections[key];
        },
        isExpanded(key) {
            return this.expandedSections[key] ?? false;
        }
    }"
>
    {{-- Sidebar Header --}}
    <div class="erp-sidebar-header">
        <a href="{{ route('dashboard') }}" class="flex items-center gap-3">
            <span class="inline-flex h-10 w-10 items-center justify-center rounded-xl bg-gradient-to-br from-emerald-500 to-emerald-600 text-white font-bold text-lg shadow-lg shadow-emerald-500/30">
                {{ strtoupper(mb_substr(config('app.name', 'G'), 0, 1)) }}
            </span>
            <div class="flex flex-col min-w-0">
                <span class="text-sm font-semibold truncate text-white">{{ $user->name ?? __('User') }}</span>
                <span class="text-xs text-emerald-300 truncate">{{ $user?->roles?->first()?->name ?? __('User') }}</span>
            </div>
        </a>

        {{-- Mobile Close Button --}}
        <button 
            @click="sidebarOpen = false" 
            class="lg:hidden p-2 rounded-lg hover:bg-slate-800 transition-colors text-slate-400 hover:text-white"
            aria-label="{{ __('Close sidebar') }}"
        >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
            </svg>
        </button>
    </div>

    {{-- Sidebar Navigation --}}
    <nav class="erp-sidebar-nav">
        @foreach($filteredSections as $sectionIndex => $section)
            <div class="erp-sidebar-section">
                {{-- Section Header --}}
                <div class="px-3 py-2">
                    <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">
                        {{ $section['title'] }}
                    </span>
                </div>

                {{-- Section Items --}}
                <div class="erp-sidebar-items">
                    @foreach($section['items'] as $itemIndex => $item)
                        @php
                            $itemKey = $section['key'] . '_' . $itemIndex;
                            $hasChildren = !empty($item['children']);
                            $itemIsActive = $isActive($item['route']);
                            $hasActiveChild = collect($item['children'] ?? [])->contains(fn($c) => $isActive($c['route']));
                        @endphp

                        @if($hasChildren)
                            {{-- Item with children (expandable) --}}
                            <div>
                                <button 
                                    type="button"
                                    @click="toggle('{{ $itemKey }}')"
                                    class="erp-sidebar-item w-full {{ $itemIsActive || $hasActiveChild ? 'active' : '' }}"
                                    :aria-expanded="isExpanded('{{ $itemKey }}')"
                                >
                                    <span class="erp-sidebar-item-icon">
                                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="{{ $item['icon'] }}"/>
                                        </svg>
                                    </span>
                                    <span class="flex-1 text-start">{{ $item['label'] }}</span>
                                    <svg 
                                        class="w-4 h-4 transition-transform duration-200"
                                        :class="isExpanded('{{ $itemKey }}') ? 'rotate-180' : ''"
                                        fill="none" 
                                        stroke="currentColor" 
                                        viewBox="0 0 24 24"
                                    >
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                    </svg>
                                </button>

                                {{-- Sub Items --}}
                                <div 
                                    x-show="isExpanded('{{ $itemKey }}')"
                                    x-collapse
                                    class="erp-sidebar-subitems"
                                >
                                    @foreach($item['children'] as $child)
                                        <a 
                                            href="{{ $safeRoute($child['route']) }}"
                                            @click="sidebarOpen = false"
                                            class="erp-sidebar-subitem {{ $isActive($child['route']) ? 'active' : '' }}"
                                        >
                                            {{ $child['label'] }}
                                        </a>
                                    @endforeach
                                </div>
                            </div>
                        @else
                            {{-- Simple item (no children) --}}
                            <a 
                                href="{{ $safeRoute($item['route']) }}"
                                @click="sidebarOpen = false"
                                class="erp-sidebar-item {{ $itemIsActive ? 'active' : '' }}"
                            >
                                <span class="erp-sidebar-item-icon">
                                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="{{ $item['icon'] }}"/>
                                    </svg>
                                </span>
                                <span>{{ $item['label'] }}</span>
                            </a>
                        @endif
                    @endforeach
                </div>
            </div>
        @endforeach
    </nav>

    {{-- Sidebar Footer --}}
    <div class="erp-sidebar-footer">
        <div class="flex items-center justify-between text-xs text-slate-500">
            <span class="flex items-center gap-2">
                <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                {{ __('Ready') }}
            </span>
            <span>{{ $dir === 'rtl' ? 'RTL' : 'LTR' }}</span>
        </div>
    </div>
</aside>
