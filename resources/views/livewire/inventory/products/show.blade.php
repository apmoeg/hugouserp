<div class="container mx-auto px-4 py-6">
    <div class="mb-6 flex justify-between items-center">
        <h1 class="text-3xl font-bold text-gray-900 dark:text-white">{{ $product->name }}</h1>
        <a href="{{ route('app.inventory.products.edit', $product) }}" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
            {{ __('Edit Product') }}
        </a>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
            <h2 class="text-xl font-semibold mb-4">{{ __('Product Information') }}</h2>
            
            <dl class="space-y-3">
                <div>
                    <dt class="text-sm font-medium text-gray-500">{{ __('SKU') }}</dt>
                    <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ $product->sku ?? __('N/A') }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500">{{ __('Barcode') }}</dt>
                    <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ $product->barcode ?? __('N/A') }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500">{{ __('Category') }}</dt>
                    <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ $product->category->name ?? __('Uncategorized') }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500">{{ __('Unit') }}</dt>
                    <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ $product->unit->name ?? __('N/A') }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500">{{ __('Price') }}</dt>
                    <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ number_format($product->default_price ?? 0, 2) }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500">{{ __('Cost') }}</dt>
                    <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ number_format($product->cost ?? 0, 2) }}</dd>
                </div>
            </dl>
        </div>

        <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
            <h2 class="text-xl font-semibold mb-4">{{ __('Stock Information') }}</h2>
            
            <dl class="space-y-3">
                <div>
                    <dt class="text-sm font-medium text-gray-500">{{ __('Min Stock') }}</dt>
                    <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ $product->min_stock ?? 0 }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500">{{ __('Max Stock') }}</dt>
                    <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ $product->max_stock ?? __('N/A') }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500">{{ __('Status') }}</dt>
                    <dd class="mt-1">
                        <span class="px-2 py-1 text-xs font-semibold rounded-full
                            {{ $product->is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                            {{ $product->is_active ? __('Active') : __('Inactive') }}
                        </span>
                    </dd>
                </div>
            </dl>
        </div>
    </div>

    @if($product->description)
    <div class="mt-6 bg-white dark:bg-gray-800 shadow rounded-lg p-6">
        <h2 class="text-xl font-semibold mb-4">{{ __('Description') }}</h2>
        <p class="text-gray-700 dark:text-gray-300">{{ $product->description }}</p>
    </div>
    @endif

    <div class="mt-6">
        <a href="{{ route('app.inventory.products.index') }}" class="px-4 py-2 bg-gray-600 text-white rounded-md hover:bg-gray-700">
            {{ __('Back to Products') }}
        </a>
        <a href="{{ route('app.inventory.products.history', $product) }}" class="ml-2 px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
            {{ __('View History') }}
        </a>
    </div>
</div>
