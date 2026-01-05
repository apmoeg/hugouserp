<div class="space-y-6">
    {{-- Page Header --}}
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ __('Branch Reports') }}</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400">{{ $branch?->name }}</p>
        </div>
    </div>

    {{-- Period Selector --}}
    <div class="flex flex-wrap gap-4 rounded-lg bg-white p-4 shadow dark:bg-gray-800">
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Period') }}</label>
            <select wire:model.live="period" class="mt-1 rounded-md border-gray-300 shadow-sm dark:border-gray-600 dark:bg-gray-700">
                <option value="day">{{ __('Today') }}</option>
                <option value="week">{{ __('This Week') }}</option>
                <option value="month">{{ __('This Month') }}</option>
                <option value="year">{{ __('This Year') }}</option>
            </select>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('From') }}</label>
            <input type="date" wire:model.live="fromDate" class="mt-1 rounded-md border-gray-300 shadow-sm dark:border-gray-600 dark:bg-gray-700">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('To') }}</label>
            <input type="date" wire:model.live="toDate" class="mt-1 rounded-md border-gray-300 shadow-sm dark:border-gray-600 dark:bg-gray-700">
        </div>
    </div>

    {{-- Sales Statistics --}}
    <div class="rounded-lg bg-white p-6 shadow dark:bg-gray-800">
        <h2 class="mb-4 text-lg font-medium text-gray-900 dark:text-white">{{ __('Sales Statistics') }}</h2>
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-5">
            <div class="rounded-lg bg-blue-50 p-4 dark:bg-blue-900/20">
                <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('Total Sales') }}</p>
                <p class="text-2xl font-bold text-blue-600">{{ $salesStats['total_sales'] }}</p>
            </div>
            <div class="rounded-lg bg-green-50 p-4 dark:bg-green-900/20">
                <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('Total Amount') }}</p>
                <p class="text-2xl font-bold text-green-600">{{ number_format($salesStats['total_amount'], 2) }}</p>
            </div>
            <div class="rounded-lg bg-purple-50 p-4 dark:bg-purple-900/20">
                <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('Average Sale') }}</p>
                <p class="text-2xl font-bold text-purple-600">{{ number_format($salesStats['average_sale'], 2) }}</p>
            </div>
            <div class="rounded-lg bg-teal-50 p-4 dark:bg-teal-900/20">
                <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('Paid') }}</p>
                <p class="text-2xl font-bold text-teal-600">{{ number_format($salesStats['paid_amount'], 2) }}</p>
            </div>
            <div class="rounded-lg bg-red-50 p-4 dark:bg-red-900/20">
                <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('Due Amount') }}</p>
                <p class="text-2xl font-bold text-red-600">{{ number_format($salesStats['due_amount'], 2) }}</p>
            </div>
        </div>
    </div>

    {{-- Inventory & Customer Stats Row --}}
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
        {{-- Inventory Statistics --}}
        <div class="rounded-lg bg-white p-6 shadow dark:bg-gray-800">
            <h2 class="mb-4 text-lg font-medium text-gray-900 dark:text-white">{{ __('Inventory Statistics') }}</h2>
            <div class="grid grid-cols-2 gap-4">
                <div class="rounded-lg bg-gray-50 p-4 dark:bg-gray-900/50">
                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('Total Products') }}</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $inventoryStats['total_products'] }}</p>
                </div>
                <div class="rounded-lg bg-gray-50 p-4 dark:bg-gray-900/50">
                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('Total Value') }}</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ number_format($inventoryStats['total_value'], 2) }}</p>
                </div>
                <div class="rounded-lg bg-yellow-50 p-4 dark:bg-yellow-900/20">
                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('Low Stock') }}</p>
                    <p class="text-2xl font-bold text-yellow-600">{{ $inventoryStats['low_stock'] }}</p>
                </div>
                <div class="rounded-lg bg-red-50 p-4 dark:bg-red-900/20">
                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('Out of Stock') }}</p>
                    <p class="text-2xl font-bold text-red-600">{{ $inventoryStats['out_of_stock'] }}</p>
                </div>
            </div>
        </div>

        {{-- Customer Statistics --}}
        <div class="rounded-lg bg-white p-6 shadow dark:bg-gray-800">
            <h2 class="mb-4 text-lg font-medium text-gray-900 dark:text-white">{{ __('Customer Statistics') }}</h2>
            <div class="grid grid-cols-3 gap-4">
                <div class="rounded-lg bg-gray-50 p-4 dark:bg-gray-900/50">
                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('Total') }}</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $customerStats['total_customers'] }}</p>
                </div>
                <div class="rounded-lg bg-green-50 p-4 dark:bg-green-900/20">
                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('New') }}</p>
                    <p class="text-2xl font-bold text-green-600">{{ $customerStats['new_customers'] }}</p>
                </div>
                <div class="rounded-lg bg-blue-50 p-4 dark:bg-blue-900/20">
                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('Active') }}</p>
                    <p class="text-2xl font-bold text-blue-600">{{ $customerStats['active_customers'] }}</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Top Products --}}
    <div class="rounded-lg bg-white p-6 shadow dark:bg-gray-800">
        <h2 class="mb-4 text-lg font-medium text-gray-900 dark:text-white">{{ __('Top Selling Products') }}</h2>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead>
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">#</th>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Product') }}</th>
                        <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Qty Sold') }}</th>
                        <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Total Amount') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($topProducts as $index => $product)
                        <tr>
                            <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-500 dark:text-gray-400">{{ $index + 1 }}</td>
                            <td class="px-4 py-3 text-sm text-gray-900 dark:text-white">{{ $product->name }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-right text-sm text-gray-900 dark:text-white">{{ number_format($product->total_qty) }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-right text-sm text-gray-900 dark:text-white">{{ number_format($product->total_amount, 2) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">
                                {{ __('No data available.') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
