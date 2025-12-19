{{-- Dynamic Sidebar Navigation --}}
@php
    use Illuminate\Support\Str;
    use Illuminate\Support\Facades\Route;
    use App\Models\Module;
    use App\Services\ModuleNavigationService;

    $dir = app()->getLocale() === 'ar' ? 'rtl' : 'ltr';
    $currentRoute = request()->route()?->getName() ?? '';
    $user = auth()->user();

    $isActive = function ($routes) use ($currentRoute) {
        $routes = (array) $routes;

        foreach ($routes as $route) {
            if ($route && str_starts_with($currentRoute, $route)) {
                return true;
            }
        }

        return false;
    };

    $routeExists = function (?string $route) {
        return $route && Route::has($route);
    };

    $safeRoute = function (?string $route) use ($routeExists) {
        return $routeExists($route) ? route($route) : '#';
    };

    $canAccess = function ($permission) use ($user) {
        if (! $permission) {
            return true;
        }

        if (! $user) {
            return false;
        }

        if ($user->hasRole('Super Admin')) {
            return true;
        }

        if (is_array($permission)) {
            foreach ($permission as $perm) {
                if ($perm && ! $user->can($perm)) {
                    return false;
                }
            }

            return true;
        }

        return $user->can($permission);
    };

    $mapNavItem = null;
    $mapNavItem = function (array $item) use (&$mapNavItem) {
        $children = collect($item['children'] ?? [])->map($mapNavItem)->values()->all();

        return [
            'route' => $item['route'] ?? null,
            'icon' => $item['icon'] ?? '🧭',
            'label' => $item['label'] ?? __('Navigation'),
            'permission' => $item['permissions'] ?? ($item['permission'] ?? null),
            'children' => $children,
        ];
    };

    $navigationService = app(ModuleNavigationService::class);
    $dynamicNavigation = $user ? $navigationService->getNavigationForUser($user, $user->branch_id) : [];

    $dynamicSections = collect($dynamicNavigation)
        ->groupBy('module_id')
        ->map(function ($items, $moduleId) use ($mapNavItem) {
            $first = $items->first();
            $moduleName = optional(Module::find($moduleId))->name
                ?? Str::headline($first['module_key'] ?? 'Module');

            return [
                'title' => $moduleName,
                'icon' => '🧩',
                'items' => $items->map($mapNavItem)->values()->all(),
                'is_dynamic' => true,
            ];
        })
        ->values()
        ->all();

    $baseSections = [
        [
            'title' => __('Workspace'),
            'icon' => '🌐',
            'items' => [
                ['route' => 'dashboard', 'icon' => '📊', 'label' => __('Dashboard'), 'permission' => 'dashboard.view'],
                ['route' => 'pos.terminal', 'icon' => '🧾', 'label' => __('POS Terminal'), 'permission' => 'pos.use', 'children' => [
                    ['route' => 'pos.daily.report', 'icon' => '📑', 'label' => __('Daily Report'), 'permission' => 'pos.daily-report.view'],
                ]],
                ['route' => 'admin.reports.index', 'icon' => '📈', 'label' => __('Reports Hub'), 'permission' => 'reports.view'],
            ],
        ],
        [
            'title' => __('Sales & Purchases'),
            'icon' => '💼',
            'items' => [
                ['route' => 'app.sales.index', 'icon' => '💰', 'label' => __('Sales'), 'permission' => 'sales.view', 'children' => [
                    ['route' => 'app.sales.create', 'icon' => '➕', 'label' => __('New Sale'), 'permission' => 'sales.manage'],
                    ['route' => 'app.sales.returns.index', 'icon' => '↩️', 'label' => __('Returns'), 'permission' => 'sales.return'],
                    ['route' => 'app.sales.analytics', 'icon' => '📈', 'label' => __('Analytics'), 'permission' => 'sales.view'],
                ]],
                ['route' => 'app.purchases.index', 'icon' => '🛒', 'label' => __('Purchases'), 'permission' => 'purchases.view', 'children' => [
                    ['route' => 'app.purchases.create', 'icon' => '➕', 'label' => __('New Purchase'), 'permission' => 'purchases.manage'],
                    ['route' => 'app.purchases.returns.index', 'icon' => '↩️', 'label' => __('Returns'), 'permission' => 'purchases.return'],
                    ['route' => 'app.purchases.requisitions.index', 'icon' => '🗒️', 'label' => __('Requisitions'), 'permission' => 'purchases.requisitions.view'],
                    ['route' => 'app.purchases.quotations.index', 'icon' => '📑', 'label' => __('Quotations'), 'permission' => 'purchases.view'],
                    ['route' => 'app.purchases.grn.index', 'icon' => '📦', 'label' => __('Goods Received'), 'permission' => 'purchases.view'],
                ]],
                ['route' => 'customers.index', 'icon' => '👤', 'label' => __('Customers'), 'permission' => 'customers.view', 'children' => [
                    ['route' => 'customers.create', 'icon' => '➕', 'label' => __('Add Customer'), 'permission' => 'customers.manage'],
                ]],
                ['route' => 'suppliers.index', 'icon' => '🏭', 'label' => __('Suppliers'), 'permission' => 'suppliers.view', 'children' => [
                    ['route' => 'suppliers.create', 'icon' => '➕', 'label' => __('Add Supplier'), 'permission' => 'suppliers.manage'],
                ]],
            ],
        ],
        [
            'title' => __('Inventory & Warehouse'),
            'icon' => '📦',
            'items' => [
                ['route' => 'app.inventory.products.index', 'icon' => '📦', 'label' => __('Products'), 'permission' => 'inventory.products.view', 'children' => [
                    ['route' => 'app.inventory.products.create', 'icon' => '➕', 'label' => __('Add Product'), 'permission' => 'inventory.products.view'],
                    ['route' => 'app.inventory.categories.index', 'icon' => '📂', 'label' => __('Categories'), 'permission' => 'inventory.products.view'],
                    ['route' => 'app.inventory.units.index', 'icon' => '📏', 'label' => __('Units'), 'permission' => 'inventory.products.view'],
                    ['route' => 'app.inventory.stock-alerts', 'icon' => '⚠️', 'label' => __('Stock Alerts'), 'permission' => 'inventory.stock.alerts.view'],
                    ['route' => 'app.inventory.barcodes', 'icon' => '🏷️', 'label' => __('Barcodes'), 'permission' => 'inventory.products.view'],
                    ['route' => 'app.inventory.batches.index', 'icon' => '📦', 'label' => __('Batches'), 'permission' => 'inventory.products.view'],
                    ['route' => 'app.inventory.serials.index', 'icon' => '🔢', 'label' => __('Serial Numbers'), 'permission' => 'inventory.products.view'],
                    ['route' => 'app.inventory.vehicle-models', 'icon' => '🚗', 'label' => __('Vehicle Models'), 'permission' => 'spares.compatibility.manage'],
                ]],
                ['route' => 'app.inventory.index', 'icon' => '📊', 'label' => __('Inventory Overview'), 'permission' => 'inventory.products.view'],
                ['route' => 'app.warehouse.index', 'icon' => '🏭', 'label' => __('Warehouse'), 'permission' => 'warehouse.view', 'children' => [
                    ['route' => 'app.warehouse.locations.index', 'icon' => '📍', 'label' => __('Locations'), 'permission' => 'warehouse.view'],
                    ['route' => 'app.warehouse.movements.index', 'icon' => '🔄', 'label' => __('Movements'), 'permission' => 'warehouse.view'],
                    ['route' => 'app.warehouse.transfers.index', 'icon' => '🚚', 'label' => __('Transfers'), 'permission' => 'warehouse.view'],
                    ['route' => 'app.warehouse.transfers.create', 'icon' => '➕', 'label' => __('New Transfer'), 'permission' => 'warehouse.manage'],
                    ['route' => 'app.warehouse.adjustments.index', 'icon' => '⚖️', 'label' => __('Adjustments'), 'permission' => 'warehouse.view'],
                    ['route' => 'app.warehouse.adjustments.create', 'icon' => '🛠️', 'label' => __('New Adjustment'), 'permission' => 'warehouse.manage'],
                ]],
            ],
        ],
        [
            'title' => __('Finance & Banking'),
            'icon' => '💵',
            'items' => [
                ['route' => 'app.accounting.index', 'icon' => '🧮', 'label' => __('Accounting'), 'permission' => 'accounting.view', 'children' => [
                    ['route' => 'app.accounting.accounts.create', 'icon' => '➕', 'label' => __('Add Account'), 'permission' => 'accounting.create'],
                    ['route' => 'app.accounting.journal-entries.create', 'icon' => '📝', 'label' => __('Journal Entry'), 'permission' => 'accounting.create'],
                ]],
                ['route' => 'app.expenses.index', 'icon' => '💳', 'label' => __('Expenses'), 'permission' => 'expenses.view', 'children' => [
                    ['route' => 'app.expenses.create', 'icon' => '➕', 'label' => __('New Expense'), 'permission' => 'expenses.manage'],
                    ['route' => 'app.expenses.categories.index', 'icon' => '📂', 'label' => __('Categories'), 'permission' => 'expenses.manage'],
                ]],
                ['route' => 'app.income.index', 'icon' => '💰', 'label' => __('Income'), 'permission' => 'income.view', 'children' => [
                    ['route' => 'app.income.create', 'icon' => '➕', 'label' => __('New Income'), 'permission' => 'income.manage'],
                    ['route' => 'app.income.categories.index', 'icon' => '🗂️', 'label' => __('Categories'), 'permission' => 'income.manage'],
                ]],
                ['route' => 'app.banking.accounts.index', 'icon' => '🏦', 'label' => __('Banking'), 'permission' => 'banking.view', 'children' => [
                    ['route' => 'app.banking.accounts.create', 'icon' => '➕', 'label' => __('Add Account'), 'permission' => 'banking.create'],
                    ['route' => 'app.banking.transactions.index', 'icon' => '🔁', 'label' => __('Transactions'), 'permission' => 'banking.view'],
                    ['route' => 'app.banking.reconciliation', 'icon' => '📘', 'label' => __('Reconciliation'), 'permission' => 'banking.reconcile'],
                ]],
                ['route' => 'admin.branches.index', 'icon' => '🏢', 'label' => __('Branches'), 'permission' => 'branches.view'],
            ],
        ],
        [
            'title' => __('People & HR'),
            'icon' => '👥',
            'items' => [
                ['route' => 'app.hrm.index', 'icon' => '🧑‍💼', 'label' => __('Human Resources'), 'permission' => 'hrm.employees.view', 'children' => [
                    ['route' => 'app.hrm.employees.index', 'icon' => '🧑‍💻', 'label' => __('Employees'), 'permission' => 'hrm.employees.view'],
                    ['route' => 'app.hrm.attendance.index', 'icon' => '🕒', 'label' => __('Attendance'), 'permission' => 'hrm.attendance.manage'],
                    ['route' => 'app.hrm.payroll.index', 'icon' => '💵', 'label' => __('Payroll'), 'permission' => 'hrm.payroll.manage'],
                    ['route' => 'app.hrm.shifts.index', 'icon' => '📅', 'label' => __('Shifts'), 'permission' => 'hrm.shifts.manage'],
                    ['route' => 'app.hrm.reports', 'icon' => '📊', 'label' => __('Reports'), 'permission' => 'hr.view-reports'],
                ]],
            ],
        ],
        [
            'title' => __('Operations'),
            'icon' => '⚙️',
            'items' => [
                ['route' => 'app.rental.index', 'icon' => '🏠', 'label' => __('Rental'), 'permission' => 'rental.units.view', 'children' => [
                    ['route' => 'app.rental.units.index', 'icon' => '📦', 'label' => __('Units'), 'permission' => 'rental.units.view'],
                    ['route' => 'app.rental.properties.index', 'icon' => '🏢', 'label' => __('Properties'), 'permission' => 'rental.properties.view'],
                    ['route' => 'app.rental.tenants.index', 'icon' => '🧑‍🤝‍🧑', 'label' => __('Tenants'), 'permission' => 'rental.tenants.view'],
                    ['route' => 'app.rental.contracts.index', 'icon' => '📝', 'label' => __('Contracts'), 'permission' => 'rental.contracts.view'],
                    ['route' => 'app.rental.reports', 'icon' => '📈', 'label' => __('Reports'), 'permission' => 'rental.view-reports'],
                ]],
                ['route' => 'app.manufacturing.index', 'icon' => '🏭', 'label' => __('Manufacturing'), 'permission' => 'manufacturing.view', 'children' => [
                    ['route' => 'app.manufacturing.boms.index', 'icon' => '🧾', 'label' => __('BOMs'), 'permission' => 'manufacturing.view'],
                    ['route' => 'app.manufacturing.orders.index', 'icon' => '🛠️', 'label' => __('Production Orders'), 'permission' => 'manufacturing.view'],
                    ['route' => 'app.manufacturing.work-centers.index', 'icon' => '🏗️', 'label' => __('Work Centers'), 'permission' => 'manufacturing.view'],
                ]],
                ['route' => 'app.fixed-assets.index', 'icon' => '🏛️', 'label' => __('Fixed Assets'), 'permission' => 'fixed-assets.view', 'children' => [
                    ['route' => 'app.fixed-assets.create', 'icon' => '➕', 'label' => __('Add Asset'), 'permission' => 'fixed-assets.view'],
                    ['route' => 'app.fixed-assets.depreciation', 'icon' => '📉', 'label' => __('Depreciation'), 'permission' => 'fixed-assets.view'],
                ]],
                ['route' => 'app.projects.index', 'icon' => '📂', 'label' => __('Projects'), 'permission' => 'projects.view', 'children' => [
                    ['route' => 'app.projects.create', 'icon' => '➕', 'label' => __('New Project'), 'permission' => 'projects.view'],
                ]],
                ['route' => 'app.documents.index', 'icon' => '📄', 'label' => __('Documents'), 'permission' => 'documents.view', 'children' => [
                    ['route' => 'app.documents.create', 'icon' => '⬆️', 'label' => __('Upload Document'), 'permission' => 'documents.view'],
                ]],
                ['route' => 'app.helpdesk.index', 'icon' => '🎫', 'label' => __('Helpdesk'), 'permission' => 'helpdesk.view', 'children' => [
                    ['route' => 'app.helpdesk.tickets.index', 'icon' => '🎟️', 'label' => __('Tickets'), 'permission' => 'helpdesk.view'],
                    ['route' => 'app.helpdesk.tickets.create', 'icon' => '➕', 'label' => __('New Ticket'), 'permission' => 'helpdesk.view'],
                    ['route' => 'app.helpdesk.categories.index', 'icon' => '📚', 'label' => __('Categories'), 'permission' => 'helpdesk.view'],
                ]],
            ],
        ],
        [
            'title' => __('Reporting'),
            'icon' => '📑',
            'items' => [
                ['route' => 'admin.reports.index', 'icon' => '📊', 'label' => __('Reports Hub'), 'permission' => 'reports.view', 'children' => [
                    ['route' => 'admin.reports.sales', 'icon' => '💰', 'label' => __('Sales'), 'permission' => 'sales.view-reports'],
                    ['route' => 'admin.reports.inventory', 'icon' => '📦', 'label' => __('Inventory'), 'permission' => 'inventory.view-reports'],
                    ['route' => 'admin.reports.pos', 'icon' => '🧾', 'label' => __('POS'), 'permission' => 'pos.view-reports'],
                    ['route' => 'admin.reports.aggregate', 'icon' => '🧮', 'label' => __('Aggregate'), 'permission' => 'reports.aggregate'],
                    ['route' => 'admin.reports.scheduled', 'icon' => '📅', 'label' => __('Scheduled'), 'permission' => 'reports.schedule'],
                    ['route' => 'admin.reports.templates', 'icon' => '📋', 'label' => __('Templates'), 'permission' => 'reports.templates'],
                ]],
            ],
        ],
        [
            'title' => __('Administration'),
            'icon' => '🛠️',
            'items' => [
                ['route' => 'admin.settings', 'icon' => '⚙️', 'label' => __('Settings'), 'permission' => 'settings.view'],
                ['route' => 'admin.users.index', 'icon' => '👥', 'label' => __('Users'), 'permission' => 'users.manage'],
                ['route' => 'admin.roles.index', 'icon' => '🔐', 'label' => __('Roles'), 'permission' => 'roles.manage'],
                ['route' => 'admin.branches.index', 'icon' => '🏢', 'label' => __('Branches'), 'permission' => 'branches.view'],
                ['route' => 'admin.modules.index', 'icon' => '🧩', 'label' => __('Modules'), 'permission' => 'modules.manage', 'children' => [
                    ['route' => 'admin.modules.create', 'icon' => '➕', 'label' => __('Add Module'), 'permission' => 'modules.manage'],
                    ['route' => 'admin.modules.product-fields', 'icon' => '🧬', 'label' => __('Product Fields'), 'permission' => 'modules.manage'],
                ]],
                ['route' => 'admin.stores.index', 'icon' => '🛍️', 'label' => __('Store Integrations'), 'permission' => 'stores.view', 'children' => [
                    ['route' => 'admin.stores.orders', 'icon' => '📦', 'label' => __('Store Orders'), 'permission' => 'stores.view'],
                    ['route' => 'admin.api-docs', 'icon' => '📖', 'label' => __('API Docs'), 'permission' => 'stores.view'],
                ]],
                ['route' => 'admin.translations.index', 'icon' => '🌍', 'label' => __('Translations'), 'permission' => 'settings.view'],
                ['route' => 'admin.currencies.index', 'icon' => '💱', 'label' => __('Currencies'), 'permission' => 'settings.view', 'children' => [
                    ['route' => 'admin.currency-rates.index', 'icon' => '📈', 'label' => __('Exchange Rates'), 'permission' => 'settings.view'],
                ]],
                ['route' => 'admin.media.index', 'icon' => '🖼️', 'label' => __('Media Library'), 'permission' => 'media.view'],
                ['route' => 'admin.logs.audit', 'icon' => '📜', 'label' => __('Audit Logs'), 'permission' => 'logs.audit.view', 'children' => [
                    ['route' => 'admin.activity-log', 'icon' => '🗒️', 'label' => __('Activity Log'), 'permission' => 'logs.audit.view'],
                ]],
            ],
        ],
    ];

    $menuSections = array_values(array_merge($baseSections, $dynamicSections));

    // Filter sections based on permissions and route availability
    $menuSections = collect($menuSections)->map(function ($section) use ($canAccess, $routeExists) {
        $items = collect($section['items'] ?? [])->map(function ($item) use ($canAccess, $routeExists) {
            if (! $canAccess($item['permission'] ?? null) || ! $routeExists($item['route'] ?? null)) {
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
    })->filter(fn ($section) => ! empty($section['items']))->values()->all();

    // Build search index for Alpine filtering and suggestions
    $searchIndex = [];
    $searchEntries = [];
    foreach ($menuSections as $sectionIndex => $section) {
        $sectionKey = "section_{$sectionIndex}";
        $sectionLabels = [$section['title']];

        foreach ($section['items'] as $itemIndex => $item) {
            $itemKey = "{$sectionKey}_item_{$itemIndex}";
            $childLabels = collect($item['children'] ?? [])->pluck('label')->all();
            $searchIndex[$itemKey] = Str::lower(trim(implode(' ', [
                __($section['title'] ?? ''),
                __($item['label'] ?? ''),
                implode(' ', $childLabels),
            ])));

            $searchEntries[] = [
                'key' => $itemKey,
                'label' => __($item['label'] ?? ''),
                'section' => __($section['title'] ?? ''),
                'icon' => $item['icon'] ?? '•',
                'url' => $safeRoute($item['route'] ?? null),
                'search' => Str::lower(__(($section['title'] ?? '').' '.($item['label'] ?? ''))),
            ];

            foreach ($item['children'] ?? [] as $childIndex => $child) {
                $childKey = "{$itemKey}_child_{$childIndex}";
                $searchIndex[$childKey] = Str::lower(trim(implode(' ', [
                    __($section['title'] ?? ''),
                    __($item['label'] ?? ''),
                    __($child['label'] ?? ''),
                ])));

                $searchEntries[] = [
                    'key' => $childKey,
                    'label' => __($child['label'] ?? ''),
                    'section' => __($section['title'] ?? ''),
                    'icon' => $child['icon'] ?? $item['icon'] ?? '•',
                    'url' => $safeRoute($child['route'] ?? null),
                    'search' => Str::lower(__(($section['title'] ?? '').' '.($item['label'] ?? '').' '.($child['label'] ?? ''))),
                ];
            }

            $sectionLabels[] = $item['label'];
            $sectionLabels = array_merge($sectionLabels, $childLabels);
        }

        $searchIndex[$sectionKey] = Str::lower(implode(' ', array_filter($sectionLabels)));
    }
@endphp

<aside
    class="sidebar-enhanced fixed md:relative inset-y-0 {{ $dir === 'rtl' ? 'right-0' : 'left-0' }} w-72 lg:w-80 bg-slate-950/95 text-slate-100 shadow-2xl z-50 flex flex-col transform transition-transform duration-300 ease-out"
    :class="sidebarOpen ? 'translate-x-0' : '{{ $dir === 'rtl' ? 'translate-x-full' : '-translate-x-full' }} md:translate-x-0'"
    x-cloak
    x-data="sidebarState(@js($searchIndex), @js($searchEntries))"
>
    {{-- Logo & User Section (Fixed at top) --}}
    <div class="sidebar-header flex-shrink-0 flex items-center justify-between px-4 py-4 border-b border-slate-800/80 bg-slate-900/60 backdrop-blur-xl">
        <a href="{{ route('dashboard') }}" class="flex items-center gap-3 group">
            <span class="inline-flex h-10 w-10 items-center justify-center rounded-2xl bg-gradient-to-br from-emerald-500 to-emerald-600 text-white font-bold text-lg shadow-md group-hover:shadow-emerald-500/40 transition-all duration-300">
                {{ strtoupper(mb_substr(config('app.name', 'G'), 0, 1)) }}
            </span>
            <div class="flex flex-col min-w-0">
                <span class="text-sm font-semibold truncate text-white">{{ $user->name ?? 'User' }}</span>
                <span class="text-xs text-emerald-200 truncate">{{ $user?->roles?->first()?->name ?? __('User') }}</span>
            </div>
        </a>

        {{-- Mobile Close Button --}}
        <button @click="sidebarOpen = false" class="md:hidden p-2 rounded-lg hover:bg-slate-800 transition-colors" aria-label="Close sidebar">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
            </svg>
        </button>
    </div>

    {{-- Inline search --}}
    <div class="px-4 pt-3 pb-4 border-b border-slate-800/80 bg-slate-900/60 backdrop-blur-xl">
        <label class="block text-xs font-semibold text-slate-300 tracking-wide">
            {{ __('Search menu') }}
            <div class="relative mt-2">
                <div class="absolute inset-y-0 {{ $dir === 'rtl' ? 'right-3' : 'left-3' }} flex items-center pointer-events-none text-slate-400">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                </div>
                <input
                    type="text"
                    x-model.debounce.200ms="searchTerm"
                    placeholder="{{ __('Find a page...') }}"
                    class="w-full rounded-xl bg-slate-800/70 border border-white/5 text-slate-100 placeholder:text-slate-400 text-sm py-2.5 pl-9 pr-9 focus:border-emerald-400 focus:ring-emerald-400/40 focus:ring-2 focus:outline-none transition"
                />
                <button
                    x-show="query"
                    @click="resetSearch"
                    type="button"
                    class="absolute inset-y-0 {{ $dir === 'rtl' ? 'left-2.5' : 'right-2.5' }} flex items-center justify-center text-slate-400 hover:text-white transition"
                    aria-label="{{ __('Clear search') }}"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
            <p x-show="query" class="mt-2 text-[11px] text-slate-400">
                {{ __('Filtering sidebar items for') }} <span class="font-semibold text-emerald-300" x-text="searchTerm"></span>
            </p>
        </label>

        <div
            x-show="query"
            class="mt-3 space-y-1"
        >
            <template x-if="filteredSuggestions.length">
                <div class="rounded-2xl border border-emerald-500/20 bg-slate-900/70 shadow-lg divide-y divide-slate-800/80">
                    <template x-for="item in filteredSuggestions" :key="item.key">
                        <a
                            :href="item.url"
                            @click="sidebarOpen = false"
                            class="flex items-center gap-3 px-3 py-2 text-sm text-slate-100 hover:bg-emerald-500/10 transition"
                        >
                            <span class="text-base" x-text="item.icon"></span>
                            <div class="flex-1 min-w-0">
                                <p class="font-semibold truncate" x-text="item.label"></p>
                                <p class="text-[11px] text-slate-400 truncate" x-text="item.section"></p>
                            </div>
                            <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                            </svg>
                        </a>
                    </template>
                </div>
            </template>
            <p
                x-show="!filteredSuggestions.length"
                class="text-xs text-slate-400 px-2"
            >
                {{ __('No quick matches yet — keep typing to search the full menu.') }}
            </p>
        </div>
    </div>

    {{-- Scrollable Navigation (Independent scroll) --}}
    <nav class="sidebar-nav flex-1 overflow-y-auto py-4 px-3 space-y-3 custom-scrollbar">
        <template x-if="query && !hasResults">
            <div class="text-center text-sm text-slate-400 bg-slate-900/60 border border-dashed border-slate-800 rounded-xl py-6">
                {{ __('No matching pages found') }}
            </div>
        </template>

        @foreach($menuSections as $sectionIndex => $section)
            @php
                $sectionKey = 'section_' . $sectionIndex;
                $hasActive = false;
                foreach ($section['items'] as $item) {
                    if ($isActive($item['route'])) {
                        $hasActive = true;
                        break;
                    }
                    foreach ($item['children'] ?? [] as $child) {
                        if ($isActive($child['route'])) {
                            $hasActive = true;
                            break 2;
                        }
                    }
                }
            @endphp

            <div
                class="sidebar-section"
                x-init="groups['{{ $sectionKey }}'] = groups['{{ $sectionKey }}'] ?? {{ $hasActive ? 'true' : 'false' }}"
                x-show="shouldShowSection('{{ $sectionKey }}')"
                x-effect="if (query) { groups['{{ $sectionKey }}'] = shouldShowSection('{{ $sectionKey }}'); }"
            >
                <button
                    @click="toggle('{{ $sectionKey }}')"
                    class="sidebar-section__header"
                    type="button"
                    :aria-expanded="groups['{{ $sectionKey }}']"
                >
                    <div class="flex items-center gap-2">
                        <span class="text-lg">{{ $section['icon'] }}</span>
                        <span class="font-semibold text-sm">{{ $section['title'] }}</span>
                    </div>
                    <svg class="w-4 h-4 transition-transform duration-200" :class="groups['{{ $sectionKey }}'] ? 'rotate-180' : 'rotate-0'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </button>

                <ul
                    x-show="groups['{{ $sectionKey }}']"
                    x-transition:enter="transition ease-out duration-200"
                    x-transition:enter-start="opacity-0 -translate-y-2"
                    x-transition:enter-end="opacity-100 translate-y-0"
                    x-transition:leave="transition ease-in duration-150"
                    x-transition:leave-start="opacity-100 translate-y-0"
                    x-transition:leave-end="opacity-0 -translate-y-2"
                    class="space-y-1 mt-2"
                >
                    @foreach($section['items'] as $itemIndex => $item)
                        <li
                            x-data="{ open: {{ $isActive($item['route']) ? 'true' : 'false' }} }"
                            class="sidebar-item"
                            x-show="shouldShowItem('{{ $sectionKey }}', '{{ $itemIndex }}')"
                            x-effect="if (query && shouldShowItem('{{ $sectionKey }}', '{{ $itemIndex }}')) { open = true; }"
                        >
                            @if(!empty($item['children']))
                                <button
                                    type="button"
                                    @click="open = !open"
                                    class="sidebar-link"
                                    :aria-expanded="open"
                                >
                                    <span class="text-lg">{{ $item['icon'] }}</span>
                                    <div class="flex-1 flex items-center justify-between gap-2">
                                        <span class="text-sm font-medium">{{ $item['label'] }}</span>
                                        <svg class="w-4 h-4 transition-transform duration-200" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                        </svg>
                                    </div>
                                    @if($isActive($item['route']))
                                        <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                                    @endif
                                </button>

                                <ul x-show="open" x-transition class="sidebar-children mt-1 space-y-0.5">
                                    @foreach($item['children'] as $childIndex => $child)
                                        <li x-show="shouldShowChild('{{ $sectionKey }}', '{{ $itemIndex }}', '{{ $childIndex }}')">
                                            <a
                                                href="{{ $safeRoute($child['route']) }}"
                                                @click="sidebarOpen = false"
                                                class="sidebar-link-secondary {{ $isActive($child['route']) ? 'active' : '' }}"
                                            >
                                                <span class="text-base">{{ $child['icon'] }}</span>
                                                <span class="text-sm">{{ $child['label'] }}</span>
                                                @if($isActive($child['route']))
                                                    <span class="ml-auto rtl:mr-auto rtl:ml-0 w-1.5 h-1.5 rounded-full bg-emerald-400"></span>
                                                @endif
                                            </a>
                                        </li>
                                    @endforeach
                                </ul>
                            @else
                                <a
                                    href="{{ $safeRoute($item['route']) }}"
                                    @click="sidebarOpen = false"
                                    class="sidebar-link {{ $isActive($item['route']) ? 'active' : '' }}"
                                >
                                    <span class="text-lg">{{ $item['icon'] }}</span>
                                    <span class="text-sm font-medium">{{ $item['label'] }}</span>
                                    @if($isActive($item['route']))
                                        <span class="ml-auto rtl:mr-auto rtl:ml-0 w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                                    @endif
                                </a>
                            @endif
                        </li>
                    @endforeach
                </ul>
            </div>
        @endforeach
    </nav>

    {{-- Footer Section (Fixed at bottom) --}}
    <div class="sidebar-footer flex-shrink-0 border-t border-slate-800 bg-slate-900/70 backdrop-blur-xl">
        <div class="px-3 py-3 space-y-2">
            <div class="flex items-center justify-between text-[13px] text-slate-300">
                <span class="inline-flex items-center gap-2">
                    <span class="w-2 h-2 rounded-full bg-emerald-400"></span>
                    {{ __('Ready to work') }}
                </span>
                <span class="text-slate-400">LTR/RTL</span>
            </div>

            <div class="grid grid-cols-2 gap-2">
                @if($canAccess('modules.manage') && Route::has('admin.modules.create'))
                    <a href="{{ route('admin.modules.create') }}" @click="sidebarOpen = false" class="sidebar-chip">
                        <span>🧩</span>
                        <span class="text-xs font-semibold">{{ __('Add Module') }}</span>
                    </a>
                @endif
                @if($canAccess('profile.update') && Route::has('profile.edit'))
                    <a href="{{ route('profile.edit') }}" @click="sidebarOpen = false" class="sidebar-chip">
                        <span>👤</span>
                        <span class="text-xs font-semibold">{{ __('My Profile') }}</span>
                    </a>
                @endif
                @if(Route::has('notifications.center'))
                    <a href="{{ route('notifications.center') }}" @click="sidebarOpen = false" class="sidebar-chip">
                        <span>🔔</span>
                        <span class="text-xs font-semibold">{{ __('Alerts') }}</span>
                    </a>
                @endif
                @if(Route::has('support.center'))
                    <a href="{{ route('support.center') }}" @click="sidebarOpen = false" class="sidebar-chip">
                        <span>💬</span>
                        <span class="text-xs font-semibold">{{ __('Support') }}</span>
                    </a>
                @endif
            </div>
        </div>
    </div>
</aside>

<style>
    .sidebar-enhanced {
        height: 100vh;
        height: 100dvh;
    }

    .sidebar-section {
        @apply rounded-2xl bg-slate-900/60 border border-white/5 p-3 shadow-lg shadow-black/20;
    }

    .sidebar-section__header {
        @apply w-full flex items-center justify-between gap-2 px-2 py-1.5 text-slate-200 hover:text-white rounded-xl transition-all duration-200;
    }

    .sidebar-link {
        @apply flex items-center gap-3 px-3 py-2.5 rounded-xl text-white bg-gradient-to-r from-slate-800/80 via-slate-800/60 to-slate-900/60 border border-white/5 shadow-sm transition-all duration-300 ease-out;
    }

    .sidebar-link:hover {
        @apply shadow-lg -translate-y-0.5 ring-1 ring-emerald-500/30;
    }

    .sidebar-link.active {
        @apply ring-2 ring-emerald-400/40 shadow-lg scale-[1.01] bg-emerald-500/20 text-emerald-100;
    }

    .sidebar-link-secondary {
        @apply flex items-center gap-2 px-3 py-2 rounded-lg text-slate-300 hover:bg-slate-800/80 hover:text-white transition-all duration-200;
    }

    .sidebar-link-secondary.active {
        @apply bg-emerald-500/20 text-emerald-100 border border-emerald-400/30;
    }

    .sidebar-chip {
        @apply flex items-center gap-2 px-3 py-2 rounded-xl bg-slate-800/60 text-slate-100 border border-white/5 shadow-sm hover:shadow-md hover:-translate-y-0.5 transition-all duration-200;
    }

    .sidebar-children {
        border-inline-start: 1px solid rgba(51, 65, 85, 0.7);
        padding-inline-start: 0.75rem;
        margin-inline-start: 0.75rem;
    }

    .custom-scrollbar::-webkit-scrollbar { width: 8px; }
    .custom-scrollbar::-webkit-scrollbar-track { background: rgba(15, 23, 42, 0.35); border-radius: 9999px; }
    .custom-scrollbar::-webkit-scrollbar-thumb { background: rgba(148, 163, 184, 0.35); border-radius: 9999px; }
    .custom-scrollbar::-webkit-scrollbar-thumb:hover { background: rgba(148, 163, 184, 0.6); }
    .custom-scrollbar { scrollbar-width: thin; scrollbar-color: rgba(148,163,184,0.35) rgba(15,23,42,0.35); }

    html[dir="rtl"] .sidebar-enhanced { border-left: 1px solid rgb(30 41 59); border-right: none; }
    html[dir="rtl"] .sidebar-section__header { justify-content: space-between; }

    @media (max-width: 768px) {
        .sidebar-enhanced { position: fixed; width: 85vw; max-width: 340px; }
        .sidebar-link, .sidebar-link-secondary { min-height: 44px; touch-action: manipulation; }
        .sidebar-nav { -webkit-overflow-scrolling: touch; }
    }
</style>

<script>
    window.sidebarState = function (index, entries) {
        return {
            groups: {},
            searchTerm: '',
            index,
            entries,
            get query() {
                return this.searchTerm.trim().toLowerCase();
            },
            get hasResults() {
                if (!this.query) {
                    return true;
                }

                return Object.values(this.index).some(text => (text || '').includes(this.query));
            },
            get filteredSuggestions() {
                if (!this.query) {
                    return [];
                }

                return this.entries
                    .filter(item => (item.search || '').includes(this.query))
                    .slice(0, 8);
            },
            init() {
                Object.keys(localStorage)
                    .filter(key => key.startsWith('sidebar_section_'))
                    .forEach(key => this.groups[key.replace('sidebar_section_', '')] = localStorage.getItem(key) === 'true');
            },
            toggle(key) {
                this.groups[key] = !this.groups[key];
                localStorage.setItem('sidebar_section_' + key, this.groups[key]);
            },
            matches(key) {
                if (!this.query) {
                    return true;
                }

                return (this.index[key] ?? '').includes(this.query);
            },
            shouldShowSection(sectionKey) {
                if (!this.query) {
                    return true;
                }

                return Object.keys(this.index).some(key => key.startsWith(sectionKey) && this.matches(key));
            },
            shouldShowItem(sectionKey, itemKey) {
                if (!this.query) {
                    return true;
                }

                const prefix = `${sectionKey}_item_${itemKey}`;
                return Object.keys(this.index).some(key => key.startsWith(prefix) && this.matches(key));
            },
            shouldShowChild(sectionKey, itemKey, childKey) {
                if (!this.query) {
                    return true;
                }

                return this.matches(`${sectionKey}_item_${itemKey}_child_${childKey}`);
            },
            resetSearch() {
                this.searchTerm = '';
            }
        }
    };

    document.addEventListener('DOMContentLoaded', function() {
        setTimeout(() => {
            const activeItem = document.querySelector('.sidebar-link.active') || document.querySelector('.sidebar-link-secondary.active');
            if (activeItem) {
                const sidebarNav = document.querySelector('.sidebar-nav');
                if (sidebarNav) {
                    const navRect = sidebarNav.getBoundingClientRect();
                    const itemRect = activeItem.getBoundingClientRect();
                    const offset = itemRect.top - navRect.top - (navRect.height / 2) + (itemRect.height / 2);
                    sidebarNav.scrollBy({ top: offset, behavior: 'smooth' });
                }
            }
        }, 200);
    });
</script>
