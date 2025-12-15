<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-800">{{ __('Stock Adjustment') }}</h1>
            <p class="text-sm text-slate-500">{{ __('Create or edit stock adjustment') }}</p>
        </div>
    </div>

    <div class="erp-card p-6">
        <form wire:submit="save">
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">{{ __('Reason') }} *</label>
                    <input type="text" wire:model="reason" class="erp-input" required>
                    @error('reason') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">{{ __('Notes') }}</label>
                    <textarea wire:model="notes" rows="3" class="erp-input"></textarea>
                    @error('notes') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                </div>
            </div>

            <div class="mt-6 flex justify-end gap-3">
                <a href="{{ route('app.warehouse.adjustments.index') }}" class="erp-btn erp-btn-secondary">
                    {{ __('Cancel') }}
                </a>
                <button type="submit" class="erp-btn erp-btn-primary">
                    {{ __('Save') }}
                </button>
            </div>
        </form>
    </div>
</div>
