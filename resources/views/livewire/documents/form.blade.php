<div class="space-y-6">
    <div>
        <h1 class="text-2xl font-bold text-slate-800">{{ $isEdit ? __('Edit Document') : __('Upload Document') }}</h1>
        <p class="text-sm text-slate-500">{{ $isEdit ? __('Update document details') : __('Upload a new document') }}</p>
    </div>

    <form wire:submit="save" class="bg-white rounded-xl shadow-sm p-6 space-y-6">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-slate-700 mb-2">{{ __('Title') }} <span class="text-red-500">*</span></label>
                <input type="text" wire:model="title" class="erp-input w-full" required>
                @error('title') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
            </div>

            @if(!$isEdit)
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-slate-700 mb-2">{{ __('File') }} <span class="text-red-500">*</span></label>
                    <input type="file" wire:model="file" class="erp-input w-full" required>
                    <p class="text-xs text-slate-500 mt-1">{{ __('Maximum file size: 50MB') }}</p>
                    @error('file') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                </div>
            @endif

            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-slate-700 mb-2">{{ __('Description') }}</label>
                <textarea wire:model="description" rows="4" class="erp-input w-full"></textarea>
                @error('description') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700 mb-2">{{ __('Folder') }}</label>
                <input type="text" wire:model="folder" class="erp-input w-full" placeholder="e.g., Contracts, Invoices">
                @error('folder') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700 mb-2">{{ __('Category') }}</label>
                <input type="text" wire:model="category" class="erp-input w-full" placeholder="e.g., Legal, Financial">
                @error('category') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
            </div>

            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-slate-700 mb-2">{{ __('Tags') }}</label>
                <div class="grid grid-cols-3 md:grid-cols-5 gap-2">
                    @foreach($tags as $tag)
                        <label class="flex items-center gap-2 p-2 border rounded cursor-pointer {{ in_array($tag->id, $selectedTags) ? 'bg-blue-50 border-blue-300' : '' }}">
                            <input type="checkbox" wire:model="selectedTags" value="{{ $tag->id }}" class="rounded">
                            <span class="text-sm">{{ $tag->name }}</span>
                        </label>
                    @endforeach
                </div>
            </div>

            <div class="md:col-span-2">
                <label class="flex items-center gap-2">
                    <input type="checkbox" wire:model="is_public" class="rounded">
                    <span class="text-sm text-slate-700">{{ __('Make this document public') }}</span>
                </label>
            </div>
        </div>

        <div class="flex items-center justify-end gap-3 pt-6 border-t">
            <a href="{{ route('app.documents.index') }}" class="erp-btn erp-btn-secondary">{{ __('Cancel') }}</a>
            <button type="submit" class="erp-btn erp-btn-primary">
                {{ $isEdit ? __('Update Document') : __('Upload Document') }}
            </button>
        </div>
    </form>
</div>
