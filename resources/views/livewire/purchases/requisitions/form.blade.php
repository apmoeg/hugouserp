<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-800">{{ __('Purchase Requisition') }}</h1>
            <p class="text-sm text-slate-500">{{ __('Create or edit purchase requisition') }}</p>
        </div>
    </div>

    <div class="erp-card p-6">
        <form wire:submit="save">
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">{{ __('Subject') }} *</label>
                    <input type="text" wire:model="subject" class="erp-input" required>
                    @error('subject') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">{{ __('Priority') }} *</label>
                        <select wire:model="priority" class="erp-input" required>
                            <option value="low">{{ __('Low') }}</option>
                            <option value="normal">{{ __('Normal') }}</option>
                            <option value="high">{{ __('High') }}</option>
                        </select>
                        @error('priority') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">{{ __('Required Date') }}</label>
                        <input type="date" wire:model="required_date" class="erp-input">
                        @error('required_date') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">{{ __('Description') }}</label>
                    <textarea wire:model="description" rows="4" class="erp-input"></textarea>
                    @error('description') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                </div>
            </div>

            <div class="mt-6 flex justify-end gap-3">
                <a href="{{ route('app.purchases.requisitions.index') }}" class="erp-btn erp-btn-secondary">
                    {{ __('Cancel') }}
                </a>
                <button type="submit" class="erp-btn erp-btn-primary">
                    {{ __('Save') }}
                </button>
            </div>
        </form>
    </div>
</div>
