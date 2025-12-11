<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-slate-800">
                {{ $isEditing ? __('Edit Fixed Asset') : __('Create Fixed Asset') }}
            </h1>
            <p class="text-sm text-slate-500">{{ __('Asset information and depreciation settings') }}</p>
        </div>
        <a href="{{ route('app.fixed-assets.index') }}" class="erp-btn erp-btn-secondary">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
            {{ __('Back to List') }}
        </a>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-slate-200">
        <form wire:submit="save" class="p-6 space-y-6">
            {{-- Basic Information Section --}}
            <div>
                <h3 class="text-lg font-semibold text-slate-800 mb-4 pb-2 border-b border-slate-200">
                    {{ __('Basic Information') }}
                </h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    {{-- Asset Name --}}
                    <div class="md:col-span-2">
                        <label for="name" class="block text-sm font-medium text-slate-700 mb-2">
                            {{ __('Asset Name') }} <span class="text-red-500">*</span>
                        </label>
                        <input type="text" wire:model="name" id="name" class="erp-input @error('name') border-red-500 @enderror" placeholder="{{ __('e.g., Dell Laptop Model XPS') }}">
                        @error('name') <span class="text-sm text-red-500">{{ $message }}</span> @enderror
                    </div>

                    {{-- Category --}}
                    <div>
                        <label for="category" class="block text-sm font-medium text-slate-700 mb-2">
                            {{ __('Asset Category') }} <span class="text-red-500">*</span>
                        </label>
                        <input type="text" wire:model="category" id="category" class="erp-input @error('category') border-red-500 @enderror" placeholder="{{ __('e.g., Computer Equipment') }}" list="categories">
                        <datalist id="categories">
                            <option value="Computer Equipment">
                            <option value="Office Furniture">
                            <option value="Machinery">
                            <option value="Vehicles">
                            <option value="Buildings">
                        </datalist>
                        @error('category') <span class="text-sm text-red-500">{{ $message }}</span> @enderror
                    </div>

                    {{-- Location --}}
                    <div>
                        <label for="location" class="block text-sm font-medium text-slate-700 mb-2">
                            {{ __('Location') }}
                        </label>
                        <input type="text" wire:model="location" id="location" class="erp-input @error('location') border-red-500 @enderror" placeholder="{{ __('e.g., Main Office - 2nd Floor') }}">
                        @error('location') <span class="text-sm text-red-500">{{ $message }}</span> @enderror
                    </div>

                    {{-- Description --}}
                    <div class="md:col-span-2">
                        <label for="description" class="block text-sm font-medium text-slate-700 mb-2">
                            {{ __('Description') }}
                        </label>
                        <textarea wire:model="description" id="description" rows="3" class="erp-input @error('description') border-red-500 @enderror" placeholder="{{ __('Additional details about the asset') }}"></textarea>
                        @error('description') <span class="text-sm text-red-500">{{ $message }}</span> @enderror
                    </div>
                </div>
            </div>

            {{-- Purchase Information Section --}}
            <div>
                <h3 class="text-lg font-semibold text-slate-800 mb-4 pb-2 border-b border-slate-200">
                    {{ __('Purchase Information') }}
                </h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    {{-- Purchase Date --}}
                    <div>
                        <label for="purchase_date" class="block text-sm font-medium text-slate-700 mb-2">
                            {{ __('Purchase Date') }} <span class="text-red-500">*</span>
                        </label>
                        <input type="date" wire:model="purchase_date" id="purchase_date" class="erp-input @error('purchase_date') border-red-500 @enderror">
                        @error('purchase_date') <span class="text-sm text-red-500">{{ $message }}</span> @enderror
                    </div>

                    {{-- Purchase Cost --}}
                    <div>
                        <label for="purchase_cost" class="block text-sm font-medium text-slate-700 mb-2">
                            {{ __('Purchase Cost') }} <span class="text-red-500">*</span>
                        </label>
                        <input type="number" step="0.01" min="0" wire:model="purchase_cost" id="purchase_cost" class="erp-input @error('purchase_cost') border-red-500 @enderror">
                        @error('purchase_cost') <span class="text-sm text-red-500">{{ $message }}</span> @enderror
                    </div>

                    {{-- Supplier --}}
                    <div>
                        <label for="supplier_id" class="block text-sm font-medium text-slate-700 mb-2">
                            {{ __('Supplier') }}
                        </label>
                        <select wire:model="supplier_id" id="supplier_id" class="erp-input @error('supplier_id') border-red-500 @enderror">
                            <option value="">{{ __('Select a supplier') }}</option>
                            @foreach($suppliers as $supplier)
                                <option value="{{ $supplier->id }}">{{ $supplier->name }}</option>
                            @endforeach
                        </select>
                        @error('supplier_id') <span class="text-sm text-red-500">{{ $message }}</span> @enderror
                    </div>

                    {{-- Serial Number --}}
                    <div>
                        <label for="serial_number" class="block text-sm font-medium text-slate-700 mb-2">
                            {{ __('Serial Number') }}
                        </label>
                        <input type="text" wire:model="serial_number" id="serial_number" class="erp-input @error('serial_number') border-red-500 @enderror">
                        @error('serial_number') <span class="text-sm text-red-500">{{ $message }}</span> @enderror
                    </div>

                    {{-- Model --}}
                    <div>
                        <label for="model" class="block text-sm font-medium text-slate-700 mb-2">
                            {{ __('Model') }}
                        </label>
                        <input type="text" wire:model="model" id="model" class="erp-input @error('model') border-red-500 @enderror">
                        @error('model') <span class="text-sm text-red-500">{{ $message }}</span> @enderror
                    </div>

                    {{-- Manufacturer --}}
                    <div>
                        <label for="manufacturer" class="block text-sm font-medium text-slate-700 mb-2">
                            {{ __('Manufacturer') }}
                        </label>
                        <input type="text" wire:model="manufacturer" id="manufacturer" class="erp-input @error('manufacturer') border-red-500 @enderror">
                        @error('manufacturer') <span class="text-sm text-red-500">{{ $message }}</span> @enderror
                    </div>
                </div>
            </div>

            {{-- Depreciation Settings Section --}}
            <div>
                <h3 class="text-lg font-semibold text-slate-800 mb-4 pb-2 border-b border-slate-200">
                    {{ __('Depreciation Settings') }}
                </h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    {{-- Depreciation Method --}}
                    <div>
                        <label for="depreciation_method" class="block text-sm font-medium text-slate-700 mb-2">
                            {{ __('Depreciation Method') }} <span class="text-red-500">*</span>
                        </label>
                        <select wire:model.live="depreciation_method" id="depreciation_method" class="erp-input @error('depreciation_method') border-red-500 @enderror">
                            <option value="straight_line">{{ __('Straight Line') }}</option>
                            <option value="declining_balance">{{ __('Declining Balance') }}</option>
                            <option value="units_of_production">{{ __('Units of Production') }}</option>
                        </select>
                        @error('depreciation_method') <span class="text-sm text-red-500">{{ $message }}</span> @enderror
                    </div>

                    {{-- Depreciation Rate (for declining balance) --}}
                    @if($depreciation_method === 'declining_balance')
                    <div>
                        <label for="depreciation_rate" class="block text-sm font-medium text-slate-700 mb-2">
                            {{ __('Depreciation Rate') }} (%) <span class="text-red-500">*</span>
                        </label>
                        <input type="number" step="0.01" min="0" max="100" wire:model="depreciation_rate" id="depreciation_rate" class="erp-input @error('depreciation_rate') border-red-500 @enderror">
                        @error('depreciation_rate') <span class="text-sm text-red-500">{{ $message }}</span> @enderror
                        <p class="text-xs text-slate-500 mt-1">{{ __('Annual depreciation rate (e.g., 20 for 20%)') }}</p>
                    </div>
                    @endif

                    {{-- Useful Life Years --}}
                    <div>
                        <label for="useful_life_years" class="block text-sm font-medium text-slate-700 mb-2">
                            {{ __('Useful Life') }} ({{ __('Years') }}) <span class="text-red-500">*</span>
                        </label>
                        <input type="number" min="1" wire:model="useful_life_years" id="useful_life_years" class="erp-input @error('useful_life_years') border-red-500 @enderror">
                        @error('useful_life_years') <span class="text-sm text-red-500">{{ $message }}</span> @enderror
                    </div>

                    {{-- Useful Life Months --}}
                    <div>
                        <label for="useful_life_months" class="block text-sm font-medium text-slate-700 mb-2">
                            {{ __('Additional Months') }}
                        </label>
                        <input type="number" min="0" max="11" wire:model="useful_life_months" id="useful_life_months" class="erp-input @error('useful_life_months') border-red-500 @enderror">
                        @error('useful_life_months') <span class="text-sm text-red-500">{{ $message }}</span> @enderror
                        <p class="text-xs text-slate-500 mt-1">{{ __('0-11 additional months') }}</p>
                    </div>

                    {{-- Salvage Value --}}
                    <div>
                        <label for="salvage_value" class="block text-sm font-medium text-slate-700 mb-2">
                            {{ __('Salvage Value') }} <span class="text-red-500">*</span>
                        </label>
                        <input type="number" step="0.01" min="0" wire:model="salvage_value" id="salvage_value" class="erp-input @error('salvage_value') border-red-500 @enderror">
                        @error('salvage_value') <span class="text-sm text-red-500">{{ $message }}</span> @enderror
                        <p class="text-xs text-slate-500 mt-1">{{ __('Expected value at end of useful life') }}</p>
                    </div>
                </div>
            </div>

            {{-- Assignment & Warranty Section --}}
            <div>
                <h3 class="text-lg font-semibold text-slate-800 mb-4 pb-2 border-b border-slate-200">
                    {{ __('Assignment & Warranty') }}
                </h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    {{-- Assigned To --}}
                    <div>
                        <label for="assigned_to" class="block text-sm font-medium text-slate-700 mb-2">
                            {{ __('Assigned To') }}
                        </label>
                        <select wire:model="assigned_to" id="assigned_to" class="erp-input @error('assigned_to') border-red-500 @enderror">
                            <option value="">{{ __('Not assigned') }}</option>
                            @foreach($users as $user)
                                <option value="{{ $user->id }}">{{ $user->name }}</option>
                            @endforeach
                        </select>
                        @error('assigned_to') <span class="text-sm text-red-500">{{ $message }}</span> @enderror
                    </div>

                    {{-- Warranty Expiry --}}
                    <div>
                        <label for="warranty_expiry" class="block text-sm font-medium text-slate-700 mb-2">
                            {{ __('Warranty End') }}
                        </label>
                        <input type="date" wire:model="warranty_expiry" id="warranty_expiry" class="erp-input @error('warranty_expiry') border-red-500 @enderror">
                        @error('warranty_expiry') <span class="text-sm text-red-500">{{ $message }}</span> @enderror
                    </div>

                    {{-- Notes --}}
                    <div class="md:col-span-2">
                        <label for="notes" class="block text-sm font-medium text-slate-700 mb-2">
                            {{ __('Notes') }}
                        </label>
                        <textarea wire:model="notes" id="notes" rows="3" class="erp-input @error('notes') border-red-500 @enderror" placeholder="{{ __('Any additional information') }}"></textarea>
                        @error('notes') <span class="text-sm text-red-500">{{ $message }}</span> @enderror
                    </div>
                </div>
            </div>

            {{-- Form Actions --}}
            <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-200">
                <a href="{{ route('app.fixed-assets.index') }}" class="erp-btn erp-btn-secondary">
                    {{ __('Cancel') }}
                </a>
                <button type="submit" class="erp-btn erp-btn-primary" wire:loading.attr="disabled">
                    <span wire:loading.remove>
                        {{ $isEditing ? __('Update Asset') : __('Create Asset') }}
                    </span>
                    <span wire:loading>
                        {{ __('Saving...') }}
                    </span>
                </button>
            </div>
        </form>
    </div>

    @if(!$isEditing)
    <div class="bg-blue-50 border border-blue-200 rounded-xl p-4">
        <p class="text-sm text-blue-800">
            <strong>{{ __('Note') }}:</strong>
            {{ __('After creating the asset, depreciation will start automatically based on the purchase date and depreciation settings.') }}
        </p>
    </div>
    @endif
</div>
