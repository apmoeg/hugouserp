<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-800">{{ __('Stock Transfers') }}</h1>
            <p class="text-sm text-slate-500">{{ __('Manage stock transfers between warehouses') }}</p>
        </div>
        @can('warehouse.manage')
        <a href="{{ route('app.warehouse.transfers.create') }}" class="erp-btn erp-btn-primary">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            {{ __('New Transfer') }}
        </a>
        @endcan
    </div>

    <div class="erp-card p-6">
        <div class="text-center py-12">
            <div class="text-slate-400">
                <svg class="w-16 h-16 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/>
                </svg>
                <p class="text-lg font-medium">{{ __('No transfers found') }}</p>
                <p class="text-sm">{{ __('Create your first stock transfer') }}</p>
            </div>
        </div>
    </div>
</div>
