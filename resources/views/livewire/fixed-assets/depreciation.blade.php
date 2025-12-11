<div class="space-y-4">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
        <div>
            <h1 class="text-lg font-semibold text-slate-800 dark:text-slate-100">
                {{ __('Asset Depreciation') }}
            </h1>
            <p class="text-sm text-slate-500 dark:text-slate-400">
                {{ __('Track depreciation schedule for fixed assets.') }}
            </p>
        </div>

        <div class="flex flex-col sm:flex-row items-center gap-2">
            <div class="w-full sm:w-56">
                <input type="search"
                       wire:model.live.debounce.500ms="search"
                       placeholder="{{ __('Search assets...') }}"
                       class="erp-input rounded-full">
            </div>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div class="rounded-2xl border border-slate-200 dark:border-slate-700 bg-white/80 dark:bg-slate-900/80 p-4">
            <div class="text-xs font-medium text-slate-500 dark:text-slate-400">{{ __('Total Assets') }}</div>
            <div class="mt-1 text-2xl font-semibold text-slate-800 dark:text-slate-100">
                {{ number_format($stats->total_assets ?? 0) }}
            </div>
        </div>
        <div class="rounded-2xl border border-slate-200 dark:border-slate-700 bg-white/80 dark:bg-slate-900/80 p-4">
            <div class="text-xs font-medium text-slate-500 dark:text-slate-400">{{ __('Purchase Cost') }}</div>
            <div class="mt-1 text-2xl font-semibold text-slate-800 dark:text-slate-100">
                {{ number_format($stats->total_purchase_cost ?? 0, 2) }}
            </div>
        </div>
        <div class="rounded-2xl border border-slate-200 dark:border-slate-700 bg-white/80 dark:bg-slate-900/80 p-4">
            <div class="text-xs font-medium text-slate-500 dark:text-slate-400">{{ __('Accumulated Depreciation') }}</div>
            <div class="mt-1 text-2xl font-semibold text-red-600 dark:text-red-400">
                {{ number_format($stats->total_depreciation ?? 0, 2) }}
            </div>
        </div>
        <div class="rounded-2xl border border-slate-200 dark:border-slate-700 bg-white/80 dark:bg-slate-900/80 p-4">
            <div class="text-xs font-medium text-slate-500 dark:text-slate-400">{{ __('Current Book Value') }}</div>
            <div class="mt-1 text-2xl font-semibold text-green-600 dark:text-green-400">
                {{ number_format($stats->total_book_value ?? 0, 2) }}
            </div>
        </div>
    </div>

    <!-- Assets Table -->
    <div class="overflow-x-auto rounded-2xl border border-slate-200 dark:border-slate-700 bg-white/80 dark:bg-slate-900/80 shadow-sm shadow-emerald-500/10">
        <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-700 text-sm">
            <thead class="bg-slate-50 dark:bg-slate-800/80">
                <tr>
                    <th class="px-3 py-2 text-start text-xs font-semibold text-slate-500 dark:text-slate-300">
                        {{ __('Asset') }}
                    </th>
                    <th class="px-3 py-2 text-start text-xs font-semibold text-slate-500 dark:text-slate-300">
                        {{ __('Category') }}
                    </th>
                    <th class="px-3 py-2 text-end text-xs font-semibold text-slate-500 dark:text-slate-300">
                        {{ __('Purchase Cost') }}
                    </th>
                    <th class="px-3 py-2 text-end text-xs font-semibold text-slate-500 dark:text-slate-300">
                        {{ __('Accumulated Dep.') }}
                    </th>
                    <th class="px-3 py-2 text-end text-xs font-semibold text-slate-500 dark:text-slate-300">
                        {{ __('Book Value') }}
                    </th>
                    <th class="px-3 py-2 text-end text-xs font-semibold text-slate-500 dark:text-slate-300">
                        {{ __('Monthly Dep.') }}
                    </th>
                    <th class="px-3 py-2 text-start text-xs font-semibold text-slate-500 dark:text-slate-300">
                        {{ __('Method') }}
                    </th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
                @forelse($assets as $asset)
                    <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/50">
                        <td class="px-3 py-2">
                            <div class="text-slate-700 dark:text-slate-200 font-medium">{{ $asset->name }}</div>
                            <div class="text-xs text-slate-500 dark:text-slate-400">{{ $asset->asset_code }}</div>
                        </td>
                        <td class="px-3 py-2 text-slate-600 dark:text-slate-300">
                            {{ $asset->category }}
                        </td>
                        <td class="px-3 py-2 text-end text-slate-600 dark:text-slate-300">
                            {{ number_format($asset->purchase_cost, 2) }}
                        </td>
                        <td class="px-3 py-2 text-end text-red-600 dark:text-red-400">
                            {{ number_format($asset->accumulated_depreciation, 2) }}
                        </td>
                        <td class="px-3 py-2 text-end font-medium text-slate-700 dark:text-slate-200">
                            {{ number_format($asset->book_value, 2) }}
                        </td>
                        <td class="px-3 py-2 text-end text-slate-600 dark:text-slate-300">
                            {{ number_format($asset->getMonthlyDepreciation(), 2) }}
                        </td>
                        <td class="px-3 py-2 text-slate-600 dark:text-slate-300">
                            {{ ucfirst($asset->depreciation_method ?? 'straight-line') }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-3 py-6 text-center text-slate-500 dark:text-slate-400">
                            {{ __('No assets found.') }}
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $assets->links() }}
    </div>
</div>
