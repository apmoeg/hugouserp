<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-slate-800">
                {{ $editMode ? __('Edit BOM') : __('Create BOM') }}
            </h1>
            <p class="text-sm text-slate-500">{{ __('Bill of Materials configuration') }}</p>
        </div>
        <a href="{{ route('app.manufacturing.boms.index') }}" class="erp-btn erp-btn-secondary">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
            {{ __('Back to List') }}
        </a>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-slate-200">
        <form wire:submit="save" class="p-6 space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                {{-- Product Selection --}}
                <div class="md:col-span-2">
                    <label for="product_id" class="block text-sm font-medium text-slate-700 mb-2">
                        {{ __('Finished Product') }} <span class="text-red-500">*</span>
                    </label>
                    <select wire:model="product_id" id="product_id" class="erp-input @error('product_id') border-red-500 @enderror">
                        <option value="">{{ __('Select a product') }}</option>
                        @foreach($products as $product)
                            <option value="{{ $product->id }}">{{ $product->name }} ({{ $product->sku }})</option>
                        @endforeach
                    </select>
                    @error('product_id') <span class="text-sm text-red-500">{{ $message }}</span> @enderror
                </div>

                {{-- BOM Name --}}
                <div>
                    <label for="name" class="block text-sm font-medium text-slate-700 mb-2">
                        {{ __('BOM Name') }} <span class="text-red-500">*</span>
                    </label>
                    <input type="text" wire:model="name" id="name" class="erp-input @error('name') border-red-500 @enderror" placeholder="{{ __('e.g., Standard Production BOM') }}">
                    @error('name') <span class="text-sm text-red-500">{{ $message }}</span> @enderror
                </div>

                {{-- BOM Name (Arabic) --}}
                <div>
                    <label for="name_ar" class="block text-sm font-medium text-slate-700 mb-2">
                        {{ __('BOM Name (Arabic)') }}
                    </label>
                    <input type="text" wire:model="name_ar" id="name_ar" class="erp-input @error('name_ar') border-red-500 @enderror" placeholder="{{ __('Optional') }}" dir="rtl">
                    @error('name_ar') <span class="text-sm text-red-500">{{ $message }}</span> @enderror
                </div>

                {{-- Quantity --}}
                <div>
                    <label for="quantity" class="block text-sm font-medium text-slate-700 mb-2">
                        {{ __('Production Quantity') }} <span class="text-red-500">*</span>
                    </label>
                    <input type="number" step="0.01" min="0.01" wire:model="quantity" id="quantity" class="erp-input @error('quantity') border-red-500 @enderror">
                    @error('quantity') <span class="text-sm text-red-500">{{ $message }}</span> @enderror
                    <p class="text-xs text-slate-500 mt-1">{{ __('How many units this BOM produces') }}</p>
                </div>

                {{-- Status --}}
                <div>
                    <label for="status" class="block text-sm font-medium text-slate-700 mb-2">
                        {{ __('Status') }} <span class="text-red-500">*</span>
                    </label>
                    <select wire:model="status" id="status" class="erp-input @error('status') border-red-500 @enderror">
                        <option value="draft">{{ __('Draft') }}</option>
                        <option value="active">{{ __('Active') }}</option>
                        <option value="archived">{{ __('Archived') }}</option>
                    </select>
                    @error('status') <span class="text-sm text-red-500">{{ $message }}</span> @enderror
                </div>

                {{-- Scrap Percentage --}}
                <div>
                    <label for="scrap_percentage" class="block text-sm font-medium text-slate-700 mb-2">
                        {{ __('Scrap Percentage') }} <span class="text-red-500">*</span>
                    </label>
                    <input type="number" step="0.01" min="0" max="100" wire:model="scrap_percentage" id="scrap_percentage" class="erp-input @error('scrap_percentage') border-red-500 @enderror">
                    @error('scrap_percentage') <span class="text-sm text-red-500">{{ $message }}</span> @enderror
                    <p class="text-xs text-slate-500 mt-1">{{ __('Expected material waste %') }}</p>
                </div>

                {{-- Multi-Level --}}
                <div>
                    <label class="flex items-center gap-2">
                        <input type="checkbox" wire:model="is_multi_level" class="erp-checkbox">
                        <span class="text-sm font-medium text-slate-700">{{ __('Multi-Level BOM') }}</span>
                    </label>
                    <p class="text-xs text-slate-500 mt-1">{{ __('Check if this BOM contains sub-assemblies') }}</p>
                </div>

                {{-- Description --}}
                <div class="md:col-span-2">
                    <label for="description" class="block text-sm font-medium text-slate-700 mb-2">
                        {{ __('Description') }}
                    </label>
                    <textarea wire:model="description" id="description" rows="3" class="erp-input @error('description') border-red-500 @enderror" placeholder="{{ __('Additional notes or details') }}"></textarea>
                    @error('description') <span class="text-sm text-red-500">{{ $message }}</span> @enderror
                </div>
            </div>

            {{-- Form Actions --}}
            <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-200">
                <a href="{{ route('app.manufacturing.boms.index') }}" class="erp-btn erp-btn-secondary">
                    {{ __('Cancel') }}
                </a>
                <button type="submit" class="erp-btn erp-btn-primary" wire:loading.attr="disabled">
                    <span wire:loading.remove>
                        {{ $editMode ? __('Update BOM') : __('Create BOM') }}
                    </span>
                    <span wire:loading>
                        {{ __('Saving...') }}
                    </span>
                </button>
            </div>
        </form>
    </div>

    @if($editMode && $bom)
    <div class="bg-blue-50 border border-blue-200 rounded-xl p-4">
        <p class="text-sm text-blue-800">
            <strong>{{ __('Note') }}:</strong>
            {{ __('After saving the BOM, you can add materials/components and operations from the BOM details page.') }}
        </p>
    </div>
    @endif
</div>
