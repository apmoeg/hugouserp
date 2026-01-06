<div class="space-y-6">
    {{-- Page Header --}}
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ __('My Attendance') }}</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('View your attendance records') }}</p>
        </div>
    </div>

    {{-- Statistics Cards --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-5">
        <div class="rounded-lg bg-white p-4 shadow dark:bg-gray-800">
            <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('Total Days') }}</p>
            <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $statistics['total_days'] }}</p>
        </div>
        <div class="rounded-lg bg-white p-4 shadow dark:bg-gray-800">
            <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('Present') }}</p>
            <p class="text-2xl font-bold text-green-600">{{ $statistics['present'] }}</p>
        </div>
        <div class="rounded-lg bg-white p-4 shadow dark:bg-gray-800">
            <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('Absent') }}</p>
            <p class="text-2xl font-bold text-red-600">{{ $statistics['absent'] }}</p>
        </div>
        <div class="rounded-lg bg-white p-4 shadow dark:bg-gray-800">
            <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('Late') }}</p>
            <p class="text-2xl font-bold text-yellow-600">{{ $statistics['late'] }}</p>
        </div>
        <div class="rounded-lg bg-white p-4 shadow dark:bg-gray-800">
            <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('Early Leave') }}</p>
            <p class="text-2xl font-bold text-orange-600">{{ $statistics['early_leave'] }}</p>
        </div>
    </div>

    {{-- Filters --}}
    <div class="flex flex-wrap gap-4 rounded-lg bg-white p-4 shadow dark:bg-gray-800">
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('From Date') }}</label>
            <input type="date" wire:model.live="fromDate" class="mt-1 rounded-md border-gray-300 shadow-sm dark:border-gray-600 dark:bg-gray-700">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('To Date') }}</label>
            <input type="date" wire:model.live="toDate" class="mt-1 rounded-md border-gray-300 shadow-sm dark:border-gray-600 dark:bg-gray-700">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Status') }}</label>
            <select wire:model.live="status" class="mt-1 rounded-md border-gray-300 shadow-sm dark:border-gray-600 dark:bg-gray-700">
                <option value="">{{ __('All') }}</option>
                <option value="present">{{ __('Present') }}</option>
                <option value="absent">{{ __('Absent') }}</option>
                <option value="late">{{ __('Late') }}</option>
            </select>
        </div>
    </div>

    {{-- Attendance Records Table --}}
    <div class="overflow-hidden rounded-lg bg-white shadow dark:bg-gray-800">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
            <thead class="bg-gray-50 dark:bg-gray-900">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Date') }}</th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Check In') }}</th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Check Out') }}</th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Status') }}</th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Hours') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 bg-white dark:divide-gray-700 dark:bg-gray-800">
                @forelse($records as $record)
                    <tr>
                        <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-900 dark:text-white">
                            {{ $record->date?->format('Y-m-d') ?? '-' }}
                        </td>
                        <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-500 dark:text-gray-400">
                            {{ $record->check_in?->format('H:i') ?? '-' }}
                        </td>
                        <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-500 dark:text-gray-400">
                            {{ $record->check_out?->format('H:i') ?? '-' }}
                        </td>
                        <td class="whitespace-nowrap px-6 py-4">
                            <span class="inline-flex rounded-full px-2 text-xs font-semibold leading-5 
                                @if($record->status === 'present') bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200
                                @elseif($record->status === 'absent') bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200
                                @else bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200
                                @endif">
                                {{ __(ucfirst($record->status ?? 'Unknown')) }}
                            </span>
                        </td>
                        <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-500 dark:text-gray-400">
                            {{ $record->total_hours ?? '-' }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-6 py-8 text-center text-gray-500 dark:text-gray-400">
                            {{ __('No attendance records found.') }}
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Pagination --}}
    @if($records instanceof \Illuminate\Pagination\LengthAwarePaginator && $records->hasPages())
        <div class="mt-4">
            {{ $records->links() }}
        </div>
    @endif
</div>
