{{-- resources/views/livewire/rental/contracts/form.blade.php --}}
<div class="space-y-4">
    <div class="flex items-center justify-between gap-2">
        <div>
            <h1 class="text-lg font-semibold text-slate-800 dark:text-slate-100">
                {{ $contractId ? __('Edit rental contract') : __('Create rental contract') }}
            </h1>
            <p class="text-sm text-slate-500 dark:text-slate-400">
                {{ __('Link a tenant to a rental unit with start/end dates and pricing.') }}
            </p>
        </div>
    </div>

    <form wire:submit.prevent="save" class="space-y-6 max-w-3xl">
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            <div class="space-y-1">
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-200">
                    {{ __('Unit') }}
                </label>
                <select wire:model="form.unit_id" class="erp-input">
                    @foreach($availableUnits as $option)
                        <option value="{{ $option['id'] }}">{{ $option['label'] }}</option>
                    @endforeach
                </select>
                @error('form.unit_id')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="space-y-1">
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-200">
                    {{ __('Tenant') }}
                </label>
                <select wire:model="form.tenant_id" class="erp-input">
                    @foreach($availableTenants as $option)
                        <option value="{{ $option['id'] }}">{{ $option['label'] }}</option>
                    @endforeach
                </select>
                @error('form.tenant_id')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="space-y-1">
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-200">
                    {{ __('Status') }}
                </label>
                <select wire:model="form.status" class="erp-input">
                    <option value="draft">{{ __('Draft') }}</option>
                    <option value="active">{{ __('Active') }}</option>
                    <option value="ended">{{ __('Ended') }}</option>
                    <option value="cancelled">{{ __('Cancelled') }}</option>
                </select>
                @error('form.status')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="space-y-1">
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-200">
                    {{ __('Rental Period') }}
                </label>
                <select wire:model.live="form.rental_period_id" class="erp-input">
                    <option value="">{{ __('Select period...') }}</option>
                    @foreach($availablePeriods as $period)
                        <option value="{{ $period['id'] }}">{{ $period['label'] }}</option>
                    @endforeach
                </select>
                @error('form.rental_period_id')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>

            @if($showCustomDays)
            <div class="space-y-1">
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-200">
                    {{ __('Number of Days') }}
                </label>
                <input type="number" min="1" max="365" wire:model.live="form.custom_days" class="erp-input" placeholder="{{ __('Enter number of days') }}">
                @error('form.custom_days')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>
            @endif

            <div class="space-y-1">
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-200">
                    {{ __('Start date') }}
                </label>
                <input type="date" wire:model.live="form.start_date" class="erp-input">
                @error('form.start_date')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="space-y-1">
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-200">
                    {{ __('End date') }}
                    <span class="text-xs text-slate-500">({{ __('auto-calculated') }})</span>
                </label>
                <input type="date" wire:model="form.end_date" class="erp-input bg-slate-100" readonly>
                @error('form.end_date')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="space-y-1">
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-200">
                    {{ __('Monthly rent') }}
                </label>
                <input type="number" step="0.01" min="0" wire:model="form.rent" class="erp-input">
                @error('form.rent')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="space-y-1">
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-200">
                    {{ __('Security deposit') }}
                </label>
                <input type="number" step="0.01" min="0" wire:model="form.deposit" class="erp-input">
                @error('form.deposit')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>

            @if (! empty($dynamicSchema))
                <div class="sm:col-span-2 lg:col-span-3 space-y-2">
                    <h2 class="text-sm font-semibold text-slate-700 dark:text-slate-200">
                        {{ __('Additional fields') }}
                    </h2>
                    <livewire:shared.dynamic-form
                        :schema="$dynamicSchema"
                        :data="$dynamicData"
                        wire:key="rental-contract-dynamic-form-{{ $contractId ?? 'new' }}"
                    />
                </div>
            @endif
        </div>

        <div class="flex justify-end gap-2">
            <a href="{{ route('app.rental.contracts.index') }}"
               class="inline-flex items-center rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 px-3 py-2 text-xs font-semibold text-slate-700 dark:text-slate-200 shadow-sm hover:bg-slate-50 dark:hover:bg-slate-800">
                {{ __('Cancel') }}
            </a>
            <button type="submit" class="erp-btn-primary">
                {{ $contractId ? __('Save changes') : __('Create contract') }}
            </button>
        </div>
    </form>
</div>
