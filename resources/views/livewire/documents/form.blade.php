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
                    
                    {{-- Drag and Drop Zone --}}
                    <div
                        x-data="{ 
                            isDragging: false,
                            handleDrop(e) {
                                this.isDragging = false;
                                if (e.dataTransfer.files.length) {
                                    const input = $refs.fileInput;
                                    input.files = e.dataTransfer.files;
                                    input.dispatchEvent(new Event('change', { bubbles: true }));
                                }
                            }
                        }"
                        @dragover.prevent="isDragging = true"
                        @dragleave.prevent="isDragging = false"
                        @drop.prevent="handleDrop($event)"
                        :class="{ 'border-blue-500 bg-blue-50': isDragging }"
                        class="border-2 border-dashed border-slate-300 rounded-lg p-6 text-center transition-colors cursor-pointer hover:border-slate-400"
                        @click="$refs.fileInput.click()"
                    >
                        <input 
                            type="file" 
                            wire:model="file" 
                            x-ref="fileInput"
                            class="hidden" 
                            required
                        >
                        <div class="space-y-2">
                            <svg class="mx-auto h-12 w-12 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                            </svg>
                            <p class="text-slate-600">
                                <span class="font-medium text-blue-600">{{ __('Click to upload') }}</span> {{ __('or drag and drop') }}
                            </p>
                            <p class="text-xs text-slate-500">{{ __('Supported formats: PDF, DOC, XLS, PPT, CSV, TXT, ZIP') }}</p>
                            <p class="text-xs text-slate-500">{{ __('Maximum file size: 50MB') }}</p>
                        </div>
                    </div>
                    
                    {{-- File Preview --}}
                    @if($file)
                        <div class="mt-3 p-3 bg-slate-50 rounded-lg flex items-center gap-3">
                            <svg class="h-8 w-8 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            <div class="flex-1">
                                <p class="text-sm font-medium text-slate-700">{{ $file->getClientOriginalName() }}</p>
                                <p class="text-xs text-slate-500">{{ number_format($file->getSize() / 1024, 1) }} KB</p>
                            </div>
                            <button type="button" wire:click="$set('file', null)" class="text-red-500 hover:text-red-700">
                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>
                    @endif
                    
                    <p class="text-xs text-amber-600 mt-2">{{ __('Note: For images, please use the Media Library') }}</p>
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
