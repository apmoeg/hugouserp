{{-- resources/views/layouts/navbar.blade.php --}}
<header class="erp-navbar border-b border-emerald-100/70 bg-white/80 dark:bg-slate-900/90 backdrop-blur">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="flex h-14 items-center justify-between gap-3">

            <div class="flex items-center gap-2">
                {{-- Mobile Menu Toggle --}}
                <button type="button"
                        class="erp-menu-toggle"
                        @click="sidebarOpen = !sidebarOpen"
                        aria-label="{{ __('Toggle menu') }}">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                    </svg>
                </button>

                @hasSection('page-title')
                    <h1 class="text-sm sm:text-base font-semibold text-slate-800">
                        @yield('page-title')
                    </h1>
                @else
                    <h1 class="text-sm sm:text-base font-semibold text-slate-800">
                        {{ __('Dashboard') }}
                    </h1>
                @endif
            </div>

            <div class="hidden md:flex flex-1 justify-center">
                <div class="w-full max-w-lg">
                    @livewire('shared.global-search')
                </div>
            </div>

            <div class="flex items-center gap-2">
                {{-- Language Switcher --}}
                <div class="flex items-center gap-1 border border-slate-200 rounded-lg p-0.5 bg-slate-50">
                    <a href="?lang=ar" class="px-2 py-1 rounded text-xs font-medium {{ app()->getLocale() === 'ar' ? 'bg-emerald-500 text-white shadow-sm' : 'text-slate-600 hover:bg-slate-100' }} transition-all">
                        AR
                    </a>
                    <a href="?lang=en" class="px-2 py-1 rounded text-xs font-medium {{ app()->getLocale() === 'en' ? 'bg-emerald-500 text-white shadow-sm' : 'text-slate-600 hover:bg-slate-100' }} transition-all">
                        EN
                    </a>
                </div>

                @livewire('notifications.dropdown')

                {{-- User Menu Dropdown --}}
                <div class="relative" x-data="{ open: false }" @click.away="open = false">
                    <button type="button" @click="open = !open"
                            class="inline-flex items-center gap-2 rounded-full border border-emerald-100 bg-white px-2.5 py-1.5 text-xs sm:text-sm text-slate-700 shadow-sm shadow-emerald-500/20 hover:bg-emerald-50">
                        <span class="inline-flex h-7 w-7 items-center justify-center rounded-full bg-gradient-to-br from-emerald-500 to-emerald-600 text-white text-xs font-semibold shadow shadow-emerald-500/40">
                            {{ strtoupper(mb_substr(auth()->user()->name ?? 'U', 0, 1)) }}
                        </span>
                        <span class="hidden sm:inline max-w-[120px] truncate">
                            {{ auth()->user()->name ?? __('User') }}
                        </span>
                        <svg class="w-4 h-4 text-slate-400" :class="{ 'rotate-180': open }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </button>

                    <div x-show="open" 
                         x-transition:enter="transition ease-out duration-100"
                         x-transition:enter-start="transform opacity-0 scale-95"
                         x-transition:enter-end="transform opacity-100 scale-100"
                         x-transition:leave="transition ease-in duration-75"
                         x-transition:leave-start="transform opacity-100 scale-100"
                         x-transition:leave-end="transform opacity-0 scale-95"
                         class="absolute {{ app()->getLocale() === 'ar' ? 'left-0' : 'right-0' }} mt-2 w-56 rounded-xl border border-slate-200 bg-white shadow-lg z-popover"
                         style="display: none;">
                        <div class="p-3 border-b border-slate-100">
                            <p class="text-sm font-medium text-slate-800">{{ auth()->user()->name ?? __('User') }}</p>
                            <p class="text-xs text-slate-500">{{ auth()->user()->email ?? '' }}</p>
                        </div>
                        <div class="p-2 space-y-1">
                            <a href="{{ route('profile.edit') }}" class="flex items-center gap-2 px-3 py-2 text-sm text-slate-700 rounded-lg hover:bg-slate-100">
                                <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                </svg>
                                {{ __('My Profile') }}
                            </a>
                            <a href="{{ route('admin.settings') }}" class="flex items-center gap-2 px-3 py-2 text-sm text-slate-700 rounded-lg hover:bg-slate-100">
                                <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                </svg>
                                {{ __('Settings') }}
                            </a>
                        </div>
                        <div class="p-2 border-t border-slate-100">
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="flex items-center gap-2 w-full px-3 py-2 text-sm text-red-600 rounded-lg hover:bg-red-50">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                                    </svg>
                                    {{ __('Logout') }}
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</header>
