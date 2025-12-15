<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-800">{{ __('Goods Received Note') }}</h1>
            <p class="text-sm text-slate-500">{{ __('Create or edit GRN') }}</p>
        </div>
    </div>

    <div class="erp-card p-6">
        <form wire:submit="save">
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">{{ __('Purchase Order') }} *</label>
                    <select wire:model="purchase_id" class="erp-input" required>
                        <option value="">{{ __('Select purchase order') }}</option>
                    </select>
                    @error('purchase_id') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">{{ __('Received Date') }} *</label>
                        <input type="date" wire:model="received_date" class="erp-input" required>
                        @error('received_date') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">{{ __('Reference #') }}</label>
                        <input type="text" wire:model="reference_number" class="erp-input">
                        @error('reference_number') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">{{ __('Notes') }}</label>
                    <textarea wire:model="notes" rows="3" class="erp-input"></textarea>
                    @error('notes') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                </div>
            </div>

            <div class="mt-6 flex justify-end gap-3">
                <a href="{{ route('app.purchases.grn.index') }}" class="erp-btn erp-btn-secondary">
                    {{ __('Cancel') }}
                </a>
                <button type="submit" class="erp-btn erp-btn-primary">
                    {{ __('Save') }}
                </button>
            </div>
        </form>
    </div>
</div>
