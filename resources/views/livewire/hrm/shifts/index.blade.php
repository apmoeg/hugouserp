<div class="space-y-4">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
        <div>
            <h1 class="text-lg font-semibold text-slate-800 dark:text-slate-100">
                {{ __('Shifts') }}
            </h1>
            <p class="text-sm text-slate-500 dark:text-slate-400">
                {{ __('Manage work shifts for employees.') }}
            </p>
        </div>

        <div class="flex flex-col sm:flex-row items-center gap-2">
            <div class="w-full sm:w-56">
                <input type="search"
                       wire:model.live.debounce.500ms="search"
                       placeholder="{{ __('Search shifts...') }}"
                       class="erp-input rounded-full">
            </div>

            <select wire:model.live="status" class="erp-input text-xs w-32">
                <option value="">{{ __('All statuses') }}</option>
                <option value="active">{{ __('Active') }}</option>
                <option value="inactive">{{ __('Inactive') }}</option>
            </select>
        </div>
    </div>

    <div class="overflow-x-auto rounded-2xl border border-slate-200 dark:border-slate-700 bg-white/80 dark:bg-slate-900/80 shadow-sm shadow-emerald-500/10">
        <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-700 text-sm">
            <thead class="bg-slate-50 dark:bg-slate-800/80">
                <tr>
                    <th class="px-3 py-2 text-start text-xs font-semibold text-slate-500 dark:text-slate-300">
                        {{ __('Name') }}
                    </th>
                    <th class="px-3 py-2 text-start text-xs font-semibold text-slate-500 dark:text-slate-300">
                        {{ __('Code') }}
                    </th>
                    <th class="px-3 py-2 text-start text-xs font-semibold text-slate-500 dark:text-slate-300">
                        {{ __('Time') }}
                    </th>
                    <th class="px-3 py-2 text-start text-xs font-semibold text-slate-500 dark:text-slate-300">
                        {{ __('Duration') }}
                    </th>
                    <th class="px-3 py-2 text-start text-xs font-semibold text-slate-500 dark:text-slate-300">
                        {{ __('Working Days') }}
                    </th>
                    <th class="px-3 py-2 text-start text-xs font-semibold text-slate-500 dark:text-slate-300">
                        {{ __('Status') }}
                    </th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
                @forelse($shifts as $shift)
                    <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/50">
                        <td class="px-3 py-2 text-slate-700 dark:text-slate-200">
                            {{ $shift->name }}
                        </td>
                        <td class="px-3 py-2 text-slate-600 dark:text-slate-300">
                            {{ $shift->code }}
                        </td>
                        <td class="px-3 py-2 text-slate-600 dark:text-slate-300">
                            {{ $shift->start_time }} - {{ $shift->end_time }}
                        </td>
                        <td class="px-3 py-2 text-slate-600 dark:text-slate-300">
                            {{ number_format($shift->shift_duration, 1) }} hrs
                        </td>
                        <td class="px-3 py-2 text-slate-600 dark:text-slate-300">
                            @if($shift->working_days)
                                {{ implode(', ', array_map('ucfirst', $shift->working_days)) }}
                            @else
                                {{ __('All days') }}
                            @endif
                        </td>
                        <td class="px-3 py-2">
                            @if($shift->is_active)
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                                    {{ __('Active') }}
                                </span>
                            @else
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-slate-100 text-slate-800 dark:bg-slate-700 dark:text-slate-300">
                                    {{ __('Inactive') }}
                                </span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-3 py-6 text-center text-slate-500 dark:text-slate-400">
                            {{ __('No shifts found.') }}
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $shifts->links() }}
    </div>
</div>
