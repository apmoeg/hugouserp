<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-800">{{ __('Stock Adjustments') }}</h1>
            <p class="text-sm text-slate-500">{{ __('Manage inventory adjustments') }}</p>
        </div>
        @can('warehouse.manage')
        <a href="{{ route('app.warehouse.adjustments.create') }}" class="erp-btn erp-btn-primary">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            {{ __('New Adjustment') }}
        </a>
        @endcan
    </div>

    <div class="erp-card p-6">
        <div class="text-center py-12">
            <div class="text-slate-400">
                <svg class="w-16 h-16 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                </svg>
                <p class="text-lg font-medium">{{ __('No adjustments found') }}</p>
                <p class="text-sm">{{ __('Create your first stock adjustment') }}</p>
            </div>
        </div>
    </div>
</div>
