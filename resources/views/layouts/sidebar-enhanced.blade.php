{{-- resources/views/layouts/sidebar-enhanced.blade.php --}}
{{-- Enhanced Hierarchical Sidebar with proper HTML structure --}}
@php
    $dir = app()->getLocale() === 'ar' ? 'rtl' : 'ltr';
    $currentRoute = request()->route()?->getName() ?? '';
    $user = auth()->user();
    
    $isActive = function($routes) use ($currentRoute) {
        if (!$currentRoute || !$routes) {
            return false;
        }
        if (is_string($routes)) {
            return str_starts_with($currentRoute, $routes);
        }
        foreach ($routes as $route) {
            if ($route && str_starts_with($currentRoute, $route)) {
                return true;
            }
        }
        return false;
    };
    
    $canAccess = function($permission) use ($user) {
        if (!$user) return false;
        if ($user->hasRole('Super Admin')) return true;
        return $user->can($permission);
    };
    
    // Define navigation structure with modules
    $navStructure = [
        [
            'key' => 'dashboard',
            'label' => __('Dashboard'),
            'icon' => '📊',
            'route' => 'dashboard',
            'permission' => 'dashboard.view',
            'color' => 'from-red-500 to-red-600',
        ],
        [
            'key' => 'pos',
            'label' => __('Point of Sale'),
            'icon' => '🧾',
            'permission' => 'pos.use',
            'color' => 'from-amber-500 to-amber-600',
            'children' => [
                [
                    'label' => __('POS Terminal'),
                    'route' => 'pos.terminal',
                    'permission' => 'pos.use',
                    'icon' => '🏪',
                ],
                [
                    'label' => __('Daily Report'),
                    'route' => 'pos.daily.report',
                    'permission' => 'pos.daily-report.view',
                    'icon' => '📑',
                ],
            ],
        ],
        [
            'key' => 'sales',
            'label' => __('Sales Management'),
            'icon' => '💰',
            'permission' => 'sales.view',
            'color' => 'from-green-500 to-green-600',
            'children' => [
                [
                    'label' => __('All Sales'),
                    'route' => 'sales.index',
                    'permission' => 'sales.view',
                    'icon' => '📋',
                ],
                [
                    'label' => __('Sales Returns'),
                    'route' => 'sales.returns',
                    'permission' => 'sales.return',
                    'icon' => '↩️',
                ],
            ],
        ],
        [
            'key' => 'purchases',
            'label' => __('Purchases'),
            'icon' => '🛒',
            'permission' => 'purchases.view',
            'color' => 'from-purple-500 to-purple-600',
            'children' => [
                [
                    'label' => __('All Purchases'),
                    'route' => 'purchases.index',
                    'permission' => 'purchases.view',
                    'icon' => '📋',
                ],
                [
                    'label' => __('Purchase Returns'),
                    'route' => 'purchases.returns',
                    'permission' => 'purchases.return',
                    'icon' => '↩️',
                ],
            ],
        ],
        [
            'key' => 'inventory',
            'label' => __('Inventory Management'),
            'icon' => '📦',
            'permission' => 'inventory.products.view',
            'color' => 'from-teal-500 to-teal-600',
            'children' => [
                [
                    'label' => __('Products'),
                    'route' => 'inventory.products.index',
                    'permission' => 'inventory.products.view',
                    'icon' => '📦',
                ],
                [
                    'label' => __('Categories'),
                    'route' => 'inventory.categories.index',
                    'permission' => 'inventory.products.view',
                    'icon' => '📂',
                ],
                [
                    'label' => __('Units of Measure'),
                    'route' => 'inventory.units.index',
                    'permission' => 'inventory.products.view',
                    'icon' => '📏',
                ],
                [
                    'label' => __('Low Stock Alerts'),
                    'route' => 'inventory.stock-alerts',
                    'permission' => 'inventory.stock.alerts.view',
                    'icon' => '⚠️',
                ],
                [
                    'label' => __('Vehicle Models'),
                    'route' => 'inventory.vehicle-models',
                    'permission' => 'spares.compatibility.manage',
                    'icon' => '🚗',
                ],
                [
                    'label' => __('Print Barcodes'),
                    'route' => 'inventory.barcode-print',
                    'permission' => 'inventory.products.view',
                    'icon' => '🏷️',
                ],
            ],
        ],
        [
            'key' => 'customers',
            'label' => __('Customers'),
            'icon' => '👤',
            'route' => 'customers.index',
            'permission' => 'customers.view',
            'color' => 'from-cyan-500 to-cyan-600',
        ],
        [
            'key' => 'suppliers',
            'label' => __('Suppliers'),
            'icon' => '🏭',
            'route' => 'suppliers.index',
            'permission' => 'suppliers.view',
            'color' => 'from-violet-500 to-violet-600',
        ],
        [
            'key' => 'warehouse',
            'label' => __('Warehouse'),
            'icon' => '🏭',
            'route' => 'warehouse.index',
            'permission' => 'warehouse.view',
            'color' => 'from-orange-500 to-orange-600',
        ],
        [
            'key' => 'accounting',
            'label' => __('Accounting'),
            'icon' => '🧮',
            'route' => 'accounting.index',
            'permission' => 'accounting.view',
            'color' => 'from-indigo-500 to-indigo-600',
        ],
        [
            'key' => 'expenses',
            'label' => __('Expenses'),
            'icon' => '📋',
            'route' => 'expenses.index',
            'permission' => 'expenses.view',
            'color' => 'from-slate-500 to-slate-600',
        ],
        [
            'key' => 'income',
            'label' => __('Income'),
            'icon' => '💵',
            'route' => 'income.index',
            'permission' => 'income.view',
            'color' => 'from-emerald-500 to-emerald-600',
        ],
        [
            'key' => 'hrm',
            'label' => __('Human Resources'),
            'icon' => '👔',
            'route' => 'hrm.employees.index',
            'permission' => 'hrm.employees.view',
            'color' => 'from-rose-500 to-rose-600',
        ],
        [
            'key' => 'rental',
            'label' => __('Rental Management'),
            'icon' => '🏠',
            'permission' => 'rental.units.view',
            'color' => 'from-lime-500 to-lime-600',
            'children' => [
                [
                    'label' => __('Rental Units'),
                    'route' => 'rental.units.index',
                    'permission' => 'rental.units.view',
                    'icon' => '🏠',
                ],
                [
                    'label' => __('Properties'),
                    'route' => 'rental.properties.index',
                    'permission' => 'rentals.view',
                    'icon' => '🏢',
                ],
                [
                    'label' => __('Tenants'),
                    'route' => 'rental.tenants.index',
                    'permission' => 'rentals.view',
                    'icon' => '👥',
                ],
                [
                    'label' => __('Contracts'),
                    'route' => 'rental.contracts.index',
                    'permission' => 'rental.contracts.view',
                    'icon' => '📄',
                ],
            ],
        ],
    ];
    
    $adminSection = [
        [
            'key' => 'branches',
            'label' => __('Branch Management'),
            'icon' => '🏢',
            'route' => 'admin.branches.index',
            'permission' => 'branches.view',
            'color' => 'from-blue-500 to-blue-600',
        ],
        [
            'key' => 'users',
            'label' => __('User Management'),
            'icon' => '👥',
            'route' => 'admin.users.index',
            'permission' => 'users.manage',
            'color' => 'from-pink-500 to-pink-600',
        ],
        [
            'key' => 'roles',
            'label' => __('Role Management'),
            'icon' => '🔐',
            'route' => 'admin.roles.index',
            'permission' => 'roles.manage',
            'color' => 'from-violet-500 to-violet-600',
        ],
        [
            'key' => 'modules',
            'label' => __('Module Management'),
            'icon' => '🧩',
            'route' => 'admin.modules.index',
            'permission' => 'modules.manage',
            'color' => 'from-fuchsia-500 to-fuchsia-600',
        ],
        [
            'key' => 'stores',
            'label' => __('Store Integrations'),
            'icon' => '🔗',
            'route' => 'admin.stores.index',
            'permission' => 'store.manage',
            'color' => 'from-indigo-500 to-indigo-600',
        ],
        [
            'key' => 'settings',
            'label' => __('System Settings'),
            'icon' => '⚙️',
            'permission' => 'settings.view',
            'color' => 'from-sky-500 to-sky-600',
            'children' => [
                [
                    'label' => __('System Settings'),
                    'route' => 'admin.settings.system',
                    'permission' => 'settings.view',
                    'icon' => '⚙️',
                ],
                [
                    'label' => __('Advanced Settings'),
                    'route' => 'admin.settings.advanced',
                    'permission' => 'settings.view',
                    'icon' => '🔒',
                ],
                [
                    'label' => __('Translation Manager'),
                    'route' => 'admin.settings.translations',
                    'permission' => 'settings.translations.manage',
                    'icon' => '🌍',
                ],
                [
                    'label' => __('Currency Management'),
                    'route' => 'admin.settings.currencies',
                    'permission' => 'settings.currency.manage',
                    'icon' => '💰',
                ],
                [
                    'label' => __('Exchange Rates'),
                    'route' => 'admin.settings.currency-rates',
                    'permission' => 'settings.currency.manage',
                    'icon' => '💱',
                ],
            ],
        ],
    ];
    
    $reportsSection = [
        [
            'label' => __('Reports Hub'),
            'route' => 'admin.reports.hub',
            'permission' => 'reports.hub.view',
            'icon' => '📊',
        ],
        [
            'label' => __('Sales Report'),
            'route' => 'admin.reports.pos.charts',
            'permission' => 'reports.pos.charts',
            'icon' => '📈',
        ],
        [
            'label' => __('Inventory Report'),
            'route' => 'admin.reports.inventory.charts',
            'permission' => 'reports.inventory.charts',
            'icon' => '📦',
        ],
        [
            'label' => __('Sales Analytics'),
            'route' => 'reports.sales-analytics',
            'permission' => 'reports.sales.view',
            'icon' => '📊',
        ],
        [
            'label' => __('Store Dashboard'),
            'route' => 'admin.store.dashboard',
            'permission' => 'store.reports.dashboard',
            'icon' => '🏪',
        ],
        [
            'label' => __('Audit Logs'),
            'route' => 'admin.logs.audit',
            'permission' => 'logs.audit.view',
            'icon' => '📋',
        ],
        [
            'label' => __('Scheduled Reports'),
            'route' => 'admin.reports.schedules',
            'permission' => 'reports.scheduled.manage',
            'icon' => '📅',
        ],
    ];
@endphp

<aside
    class="hidden md:flex md:flex-col md:w-64 lg:w-72 bg-gradient-to-b from-slate-800 via-slate-900 to-slate-950 text-slate-100 shadow-xl z-20"
    :class="sidebarOpen ? 'block' : ''"
    x-data="{ expandedSections: ['dashboard', 'pos', 'sales', 'inventory'] }"
>
    {{-- Logo & User --}}
    <div class="flex items-center justify-between px-4 py-4 border-b border-slate-700">
        <a href="{{ route('dashboard') }}" class="flex items-center gap-3 group">
            <span class="inline-flex h-10 w-10 items-center justify-center rounded-xl bg-gradient-to-br from-emerald-500 to-emerald-600 text-white font-bold text-lg shadow-md group-hover:shadow-emerald-500/50 transition-all duration-300">
                {{ strtoupper(mb_substr(config('app.name', 'E'), 0, 1)) }}
            </span>
            <div class="flex flex-col">
                <span class="text-sm font-semibold truncate text-white">{{ $user->name ?? 'User' }}</span>
                <span class="text-xs text-slate-400">{{ $user?->roles?->first()?->name ?? __('User') }}</span>
            </div>
        </a>
    </div>

    {{-- Quick Actions --}}
    <div class="px-3 py-3 border-b border-slate-700 bg-slate-800/50">
        <p class="text-xs uppercase tracking-wide text-slate-500 mb-2 px-1">{{ __('Quick Actions') }}</p>
        <div class="grid grid-cols-2 gap-2">
            @if($canAccess('sales.create'))
            <a href="{{ route('pos.terminal') }}" class="flex items-center gap-2 px-3 py-2 rounded-lg bg-gradient-to-r from-emerald-600 to-emerald-700 hover:from-emerald-500 hover:to-emerald-600 text-white text-xs font-medium transition-all duration-200 shadow-sm hover:shadow-md">
                <span>🧾</span>
                <span>{{ __('New Sale') }}</span>
            </a>
            @endif
            @if($canAccess('inventory.products.view'))
            <a href="{{ route('inventory.products.create') }}" class="flex items-center gap-2 px-3 py-2 rounded-lg bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-500 hover:to-blue-600 text-white text-xs font-medium transition-all duration-200 shadow-sm hover:shadow-md">
                <span>📦</span>
                <span>{{ __('New Product') }}</span>
            </a>
            @endif
            @if($canAccess('purchases.create'))
            <a href="{{ route('purchases.create') }}" class="flex items-center gap-2 px-3 py-2 rounded-lg bg-gradient-to-r from-purple-600 to-purple-700 hover:from-purple-500 hover:to-purple-600 text-white text-xs font-medium transition-all duration-200 shadow-sm hover:shadow-md">
                <span>🛒</span>
                <span>{{ __('New Purchase') }}</span>
            </a>
            @endif
            @if($canAccess('customers.create'))
            <a href="{{ route('customers.create') }}" class="flex items-center gap-2 px-3 py-2 rounded-lg bg-gradient-to-r from-cyan-600 to-cyan-700 hover:from-cyan-500 hover:to-cyan-600 text-white text-xs font-medium transition-all duration-200 shadow-sm hover:shadow-md">
                <span>👤</span>
                <span>{{ __('New Customer') }}</span>
            </a>
            @endif
        </div>
    </div>

    {{-- Main Navigation --}}
    <nav class="flex-1 overflow-y-auto py-3 px-2 space-y-1">
        <ul class="space-y-1">
            @foreach($navStructure as $item)
                @if($canAccess($item['permission'] ?? 'dashboard.view'))
                <li>
                    @if(!empty($item['children']))
                        {{-- Parent with children --}}
                        <button 
                            @click="expandedSections.includes('{{ $item['key'] }}') ? expandedSections = expandedSections.filter(s => s !== '{{ $item['key'] }}') : expandedSections.push('{{ $item['key'] }}')"
                            class="w-full sidebar-link {{ isset($item['color']) ? 'bg-gradient-to-r ' . $item['color'] : '' }} flex items-center justify-between"
                        >
                            <span class="flex items-center gap-2">
                                <span class="text-lg">{{ $item['icon'] }}</span>
                                <span class="text-sm font-medium">{{ $item['label'] }}</span>
                            </span>
                            <svg 
                                class="w-4 h-4 transition-transform duration-200"
                                :class="expandedSections.includes('{{ $item['key'] }}') ? 'rotate-180' : ''"
                                fill="none" 
                                stroke="currentColor" 
                                viewBox="0 0 24 24"
                            >
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </button>
                        <ul 
                            x-show="expandedSections.includes('{{ $item['key'] }}')"
                            x-transition:enter="transition ease-out duration-200"
                            x-transition:enter-start="opacity-0 -translate-y-2"
                            x-transition:enter-end="opacity-100 translate-y-0"
                            x-transition:leave="transition ease-in duration-150"
                            x-transition:leave-start="opacity-100 translate-y-0"
                            x-transition:leave-end="opacity-0 -translate-y-2"
                            class="mt-1 space-y-1 {{ $dir === 'rtl' ? 'mr-4' : 'ml-4' }}"
                        >
                            @foreach($item['children'] as $child)
                                @if($canAccess($child['permission'] ?? 'dashboard.view'))
                                <li>
                                    <a href="{{ isset($child['route']) ? route($child['route']) : '#' }}"
                                       class="sidebar-link-secondary {{ $isActive($child['route'] ?? '') ? 'active' : '' }}">
                                        <span class="text-base">{{ $child['icon'] }}</span>
                                        <span class="text-sm">{{ $child['label'] }}</span>
                                    </a>
                                </li>
                                @endif
                            @endforeach
                        </ul>
                    @else
                        {{-- Single item without children --}}
                        <a href="{{ isset($item['route']) ? route($item['route']) : '#' }}"
                           class="sidebar-link {{ isset($item['color']) ? 'bg-gradient-to-r ' . $item['color'] : '' }} {{ $isActive($item['route'] ?? '') ? 'active ring-2 ring-white/30' : '' }}">
                            <span class="text-lg">{{ $item['icon'] }}</span>
                            <span class="text-sm font-medium">{{ $item['label'] }}</span>
                            @if($isActive($item['route'] ?? ''))
                                <span class="ms-auto w-2 h-2 rounded-full bg-white animate-pulse"></span>
                            @endif
                        </a>
                    @endif
                </li>
                @endif
            @endforeach
        </ul>

        {{-- Administration Section --}}
        @if($canAccess('settings.view') || $canAccess('users.manage') || $canAccess('roles.manage') || $canAccess('modules.manage'))
        <div class="my-3 border-t border-slate-700"></div>
        <p class="px-3 text-xs uppercase tracking-wide text-slate-500 mb-2">{{ __('Administration') }}</p>
        <ul class="space-y-1">
            @foreach($adminSection as $item)
                @if($canAccess($item['permission'] ?? 'dashboard.view'))
                <li>
                    @if(!empty($item['children']))
                        <button 
                            @click="expandedSections.includes('{{ $item['key'] }}') ? expandedSections = expandedSections.filter(s => s !== '{{ $item['key'] }}') : expandedSections.push('{{ $item['key'] }}')"
                            class="w-full sidebar-link {{ isset($item['color']) ? 'bg-gradient-to-r ' . $item['color'] : '' }} flex items-center justify-between"
                        >
                            <span class="flex items-center gap-2">
                                <span class="text-lg">{{ $item['icon'] }}</span>
                                <span class="text-sm font-medium">{{ $item['label'] }}</span>
                            </span>
                            <svg 
                                class="w-4 h-4 transition-transform duration-200"
                                :class="expandedSections.includes('{{ $item['key'] }}') ? 'rotate-180' : ''"
                                fill="none" 
                                stroke="currentColor" 
                                viewBox="0 0 24 24"
                            >
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </button>
                        <ul 
                            x-show="expandedSections.includes('{{ $item['key'] }}')"
                            x-transition
                            class="mt-1 space-y-1 {{ $dir === 'rtl' ? 'mr-4' : 'ml-4' }}"
                        >
                            @foreach($item['children'] as $child)
                                @if($canAccess($child['permission'] ?? 'dashboard.view'))
                                <li>
                                    <a href="{{ isset($child['route']) ? route($child['route']) : '#' }}"
                                       class="sidebar-link-secondary {{ $isActive($child['route'] ?? '') ? 'active' : '' }}">
                                        <span class="text-base">{{ $child['icon'] }}</span>
                                        <span class="text-sm">{{ $child['label'] }}</span>
                                    </a>
                                </li>
                                @endif
                            @endforeach
                        </ul>
                    @else
                        <a href="{{ isset($item['route']) ? route($item['route']) : '#' }}"
                           class="sidebar-link {{ isset($item['color']) ? 'bg-gradient-to-r ' . $item['color'] : '' }} {{ $isActive($item['route'] ?? '') ? 'active ring-2 ring-white/30' : '' }}">
                            <span class="text-lg">{{ $item['icon'] }}</span>
                            <span class="text-sm font-medium">{{ $item['label'] }}</span>
                            @if($isActive($item['route'] ?? ''))
                                <span class="ms-auto w-2 h-2 rounded-full bg-white animate-pulse"></span>
                            @endif
                        </a>
                    @endif
                </li>
                @endif
            @endforeach
        </ul>
        @endif

        {{-- Reports Section --}}
        @if($canAccess('reports.view') || $canAccess('reports.hub.view') || $canAccess('logs.audit.view'))
        <div class="my-3 border-t border-slate-700"></div>
        <p class="px-3 text-xs uppercase tracking-wide text-slate-500 mb-2">{{ __('Reports & Analytics') }}</p>
        <ul class="space-y-1">
            @foreach($reportsSection as $item)
                @if($canAccess($item['permission'] ?? 'reports.view'))
                <li>
                    <a href="{{ isset($item['route']) ? route($item['route']) : '#' }}"
                       class="sidebar-link-secondary {{ $isActive($item['route'] ?? '') ? 'active' : '' }}">
                        <span class="text-base">{{ $item['icon'] }}</span>
                        <span class="text-sm">{{ $item['label'] }}</span>
                    </a>
                </li>
                @endif
            @endforeach
        </ul>
        @endif
    </nav>

    {{-- Language Switcher --}}
    <div class="border-t border-slate-700 p-3">
        <div class="flex items-center justify-center gap-2">
            <a href="?lang=ar" class="px-4 py-2 rounded-lg text-sm font-medium transition-all duration-300 {{ app()->getLocale() === 'ar' ? 'bg-emerald-500 text-white shadow-lg shadow-emerald-500/30' : 'bg-slate-700 text-slate-300 hover:bg-slate-600 hover:text-white' }}">
                العربية
            </a>
            <a href="?lang=en" class="px-4 py-2 rounded-lg text-sm font-medium transition-all duration-300 {{ app()->getLocale() === 'en' ? 'bg-emerald-500 text-white shadow-lg shadow-emerald-500/30' : 'bg-slate-700 text-slate-300 hover:bg-slate-600 hover:text-white' }}">
                English
            </a>
        </div>
    </div>

    {{-- User Profile Section --}}
    <div class="border-t border-slate-700 p-3 space-y-2">
        <a href="{{ route('profile.edit') }}" class="w-full flex items-center gap-2 px-4 py-2 rounded-lg text-slate-300 hover:bg-slate-700 hover:text-white transition-all duration-300">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
            </svg>
            <span class="text-sm font-medium">{{ __('My Profile') }}</span>
        </a>
        <a href="{{ route('preferences') }}" class="w-full flex items-center gap-2 px-4 py-2 rounded-lg text-slate-300 hover:bg-slate-700 hover:text-white transition-all duration-300">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
            </svg>
            <span class="text-sm font-medium">{{ __('Preferences') }}</span>
        </a>
    </div>

    {{-- Logout --}}
    <div class="border-t border-slate-700 p-3">
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="w-full flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl bg-red-500/10 text-red-400 hover:bg-red-500/20 hover:text-red-300 transition-all duration-300">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                </svg>
                <span class="text-sm font-medium">{{ __('Logout') }}</span>
            </button>
        </form>
    </div>
</aside>
