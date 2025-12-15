<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-800">{{ __('Stock Transfer') }}</h1>
            <p class="text-sm text-slate-500">{{ __('Create or edit stock transfer') }}</p>
        </div>
    </div>

    <div class="erp-card p-6">
        <form wire:submit="save">
            <div class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">{{ __('From Warehouse') }} *</label>
                        <select wire:model="from_warehouse_id" class="erp-input" required>
                            <option value="">{{ __('Select warehouse') }}</option>
                        </select>
                        @error('from_warehouse_id') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">{{ __('To Warehouse') }} *</label>
                        <select wire:model="to_warehouse_id" class="erp-input" required>
                            <option value="">{{ __('Select warehouse') }}</option>
                        </select>
                        @error('to_warehouse_id') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">{{ __('Notes') }}</label>
                    <textarea wire:model="notes" rows="3" class="erp-input"></textarea>
                    @error('notes') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                </div>
            </div>

            <div class="mt-6 flex justify-end gap-3">
                <a href="{{ route('app.warehouse.transfers.index') }}" class="erp-btn erp-btn-secondary">
                    {{ __('Cancel') }}
                </a>
                <button type="submit" class="erp-btn erp-btn-primary">
                    {{ __('Save') }}
                </button>
            </div>
        </form>
    </div>
</div>
