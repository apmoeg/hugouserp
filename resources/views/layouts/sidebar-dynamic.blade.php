{{-- resources/views/layouts/sidebar-dynamic.blade.php --}}
{{-- DEPRECATED: This sidebar variant is not used. Use layouts/sidebar.blade.php instead. --}}
{{-- Database-driven Dynamic Sidebar --}}
@php
    $dir = app()->getLocale() === 'ar' ? 'rtl' : 'ltr';
    $currentRoute = request()->route()?->getName() ?? '';
    $user = auth()->user();
    $branchId = session('branch_id');
    
    // Get navigation from service
    $navigationService = app(\App\Services\ModuleNavigationService::class);
    $navigation = $navigationService->getNavigationForUser($user, $branchId);
    $quickActions = $navigationService->getQuickActionsForUser($user, $branchId);
    
    $isActive = function($route) use ($currentRoute) {
        if (!$route || !$currentRoute) {
            return false;
        }
        return str_starts_with($currentRoute, $route);
    };
    
    // Helper to render navigation items recursively
    $renderNavigationItem = null;
    $renderNavigationItem = function($item, $level = 0) use ($isActive, $dir, &$renderNavigationItem) {
        $hasChildren = !empty($item['children']);
        $isActiveItem = isset($item['route']) && $isActive($item['route']);
        $itemKey = $item['key'] ?? 'item-' . $item['id'];
        
        if ($hasChildren) {
            // Parent with children
            echo '<li>';
            echo '<button 
                    @click="expandedSections.includes(\'' . $itemKey . '\') ? expandedSections = expandedSections.filter(s => s !== \'' . $itemKey . '\') : expandedSections.push(\'' . $itemKey . '\')"
                    class="w-full flex items-center justify-between px-3 py-2 rounded-lg text-slate-300 hover:bg-slate-700 hover:text-white transition-all duration-200"
                  >';
            echo '<span class="flex items-center gap-2">';
            if (isset($item['icon'])) {
                echo '<span class="text-lg">' . $item['icon'] . '</span>';
            }
            echo '<span class="text-sm font-medium">' . $item['label'] . '</span>';
            echo '</span>';
            echo '<svg class="w-4 h-4 transition-transform duration-200" :class="expandedSections.includes(\'' . $itemKey . '\') ? \'rotate-180\' : \'\'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                  </svg>';
            echo '</button>';
            
            // Render children
            echo '<ul x-show="expandedSections.includes(\'' . $itemKey . '\')" 
                      x-transition:enter="transition ease-out duration-200"
                      x-transition:enter-start="opacity-0 -translate-y-2"
                      x-transition:enter-end="opacity-100 translate-y-0"
                      class="mt-1 space-y-1 ' . ($dir === 'rtl' ? 'mr-4' : 'ml-4') . '">';
            foreach ($item['children'] as $child) {
                $renderNavigationItem($child, $level + 1);
            }
            echo '</ul>';
            echo '</li>';
        } else {
            // Single item
            $route = isset($item['route']) ? route($item['route']) : '#';
            $activeClass = $isActiveItem ? 'bg-slate-700 text-white' : '';
            
            echo '<li>';
            echo '<a href="' . $route . '" 
                     class="flex items-center gap-2 px-3 py-2 rounded-lg text-slate-300 hover:bg-slate-700 hover:text-white transition-all duration-200 ' . $activeClass . '">';
            if (isset($item['icon'])) {
                echo '<span class="text-base">' . $item['icon'] . '</span>';
            }
            echo '<span class="text-sm">' . $item['label'] . '</span>';
            if ($isActiveItem) {
                echo '<span class="ms-auto w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>';
            }
            echo '</a>';
            echo '</li>';
        }
    };
@endphp

<aside
    class="hidden md:flex md:flex-col md:w-64 lg:w-72 bg-gradient-to-b from-slate-800 via-slate-900 to-slate-950 text-slate-100 shadow-xl z-20"
    :class="sidebarOpen ? 'block' : ''"
    x-data="{ 
        expandedSections: ['dashboard', 'inventory', 'pos', 'sales'],
        toggleSection(key) {
            if (this.expandedSections.includes(key)) {
                this.expandedSections = this.expandedSections.filter(s => s !== key);
            } else {
                this.expandedSections.push(key);
            }
        }
    }"
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
    @if(!empty($quickActions))
    <div class="px-3 py-3 border-b border-slate-700 bg-slate-800/50">
        <p class="text-xs uppercase tracking-wide text-slate-500 mb-2 px-1">{{ __('Quick Actions') }}</p>
        <div class="grid grid-cols-2 gap-2">
            @foreach($quickActions as $action)
            <a href="{{ route($action['route']) }}" 
               class="flex items-center gap-2 px-3 py-2 rounded-lg bg-gradient-to-r from-{{ $action['color'] }}-600 to-{{ $action['color'] }}-700 hover:from-{{ $action['color'] }}-500 hover:to-{{ $action['color'] }}-600 text-white text-xs font-medium transition-all duration-200 shadow-sm hover:shadow-md"
               title="{{ $action['label'] }}">
                <span>{{ $action['icon'] }}</span>
                <span class="truncate">{{ Str::limit($action['label'], 8) }}</span>
            </a>
            @endforeach
        </div>
    </div>
    @endif

    {{-- Dynamic Navigation from Database --}}
    <nav class="flex-1 overflow-y-auto py-3 px-2 space-y-1">
        @if(!empty($navigation))
        <ul class="space-y-1">
            @foreach($navigation as $item)
                @php
                    $renderNavigationItem($item, 0);
                @endphp
            @endforeach
        </ul>
        @else
        <div class="px-3 py-6 text-center text-slate-500">
            <p class="text-sm">{{ __('No navigation items available') }}</p>
        </div>
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
        <a href="{{ route('admin.settings') }}" class="w-full flex items-center gap-2 px-4 py-2 rounded-lg text-slate-300 hover:bg-slate-700 hover:text-white transition-all duration-300">
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
