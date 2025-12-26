<div class="inline-block">
    {{-- Preview/Trigger Button --}}
    <div class="relative">
        @if($selectedMedia && $selectedMedia['is_image'])
            <div class="relative group">
                <img 
                    src="{{ $previewUrl }}" 
                    alt="{{ $previewName }}" 
                    class="h-20 w-auto object-contain rounded-lg border border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-700"
                >
                <div class="absolute inset-0 bg-black bg-opacity-50 opacity-0 group-hover:opacity-100 transition-opacity rounded-lg flex items-center justify-center gap-2">
                    <button 
                        type="button" 
                        wire:click="openModal"
                        class="p-1.5 bg-white rounded-full hover:bg-gray-100 transition"
                        title="{{ __('Change') }}"
                    >
                        <svg class="w-4 h-4 text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                    </button>
                    <button 
                        type="button" 
                        wire:click="clearSelection"
                        class="p-1.5 bg-white rounded-full hover:bg-red-100 transition"
                        title="{{ __('Remove') }}"
                    >
                        <svg class="w-4 h-4 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
            </div>
        @elseif($selectedMedia)
            <div class="flex items-center gap-3 p-3 bg-gray-50 dark:bg-gray-700 rounded-lg border border-gray-200 dark:border-gray-600">
                <svg class="h-10 w-10 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                </svg>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium text-gray-900 dark:text-white truncate">{{ $previewName }}</p>
                    <p class="text-xs text-gray-500">{{ $selectedMedia['human_size'] ?? '' }}</p>
                </div>
                <div class="flex gap-1">
                    <button 
                        type="button" 
                        wire:click="openModal"
                        class="p-1.5 text-gray-500 hover:text-gray-700 dark:hover:text-gray-300"
                        title="{{ __('Change') }}"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/>
                        </svg>
                    </button>
                    <button 
                        type="button" 
                        wire:click="clearSelection"
                        class="p-1.5 text-red-500 hover:text-red-700"
                        title="{{ __('Remove') }}"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
            </div>
        @else
            <button 
                type="button" 
                wire:click="openModal"
                class="flex items-center gap-2 px-4 py-2 border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-lg hover:border-emerald-500 hover:bg-emerald-50 dark:hover:bg-emerald-900/20 transition-colors"
            >
                <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                </svg>
                <span class="text-sm text-gray-600 dark:text-gray-400">{{ __('Select from Media Library') }}</span>
            </button>
        @endif
    </div>

    {{-- Modal --}}
    @if($showModal)
    <div 
        class="fixed inset-0 z-modal flex items-center justify-center p-4"
        x-init="document.body.classList.add('overflow-hidden'); $el.addEventListener('close-modal', () => document.body.classList.remove('overflow-hidden'))"
        @keydown.escape.window="$wire.closeModal()"
    >
        {{-- Backdrop --}}
        <div 
            class="absolute inset-0 bg-black/60 backdrop-blur-sm"
            wire:click="closeModal"
        ></div>

        {{-- Modal Content --}}
        <div class="relative bg-white dark:bg-gray-800 rounded-2xl shadow-2xl w-full max-w-4xl max-h-[90vh] flex flex-col overflow-hidden">
            {{-- Header --}}
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <div>
                    <h2 class="text-xl font-bold text-gray-900 dark:text-white">{{ __('Media Library') }}</h2>
                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('Select or upload a file') }}</p>
                </div>
                <button 
                    type="button" 
                    wire:click="closeModal"
                    class="p-2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            {{-- Alerts --}}
            @if(session()->has('error'))
                <div class="mx-6 mt-4 p-3 bg-red-50 dark:bg-red-900/20 text-red-700 dark:text-red-300 rounded-lg text-sm">
                    {{ session('error') }}
                </div>
            @endif
            @if(session()->has('upload-success'))
                <div class="mx-6 mt-4 p-3 bg-emerald-50 dark:bg-emerald-900/20 text-emerald-700 dark:text-emerald-300 rounded-lg text-sm">
                    {{ session('upload-success') }}
                </div>
            @endif

            {{-- Upload Section --}}
            @can('media.upload')
            <div class="px-6 pt-4">
                <div 
                    class="border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-lg p-4 text-center hover:border-emerald-500 transition-colors cursor-pointer"
                    x-data="{ dragging: false }"
                    @dragover.prevent="dragging = true"
                    @dragleave.prevent="dragging = false"
                    @drop.prevent="dragging = false"
                    :class="{ 'border-emerald-500 bg-emerald-50 dark:bg-emerald-900/20': dragging }"
                >
                    <input 
                        type="file" 
                        wire:model="uploadFile" 
                        class="hidden" 
                        id="media-upload-{{ $fieldId }}"
                        accept="{{ implode(',', array_map(fn($ext) => '.' . $ext, $allowedExtensions)) }}"
                    >
                    <label for="media-upload-{{ $fieldId }}" class="cursor-pointer block">
                        <svg class="mx-auto h-8 w-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                        </svg>
                        <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                            {{ __('Click to upload or drag and drop') }}
                        </p>
                        <p class="text-xs text-gray-500">{{ __('Max') }}: {{ round($maxSize / 1024, 1) }} MB</p>
                    </label>
                </div>
                <div wire:loading wire:target="uploadFile" class="mt-2 text-center">
                    <div class="inline-flex items-center gap-2 text-emerald-600">
                        <svg class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <span class="text-sm">{{ __('Uploading...') }}</span>
                    </div>
                </div>
            </div>
            @endcan

            {{-- Search & Filter --}}
            <div class="px-6 py-3 flex gap-3">
                <div class="flex-1">
                    <input 
                        type="text" 
                        wire:model.live.debounce.300ms="search" 
                        placeholder="{{ __('Search...') }}"
                        class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-sm bg-white dark:bg-gray-700 focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500"
                    >
                </div>
                @if(in_array('all', $acceptTypes) || (in_array('image', $acceptTypes) && in_array('document', $acceptTypes)))
                <select 
                    wire:model.live="filterType" 
                    class="px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-sm bg-white dark:bg-gray-700 focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500"
                >
                    <option value="all">{{ __('All Files') }}</option>
                    <option value="images">{{ __('Images') }}</option>
                    <option value="documents">{{ __('Documents') }}</option>
                </select>
                @endif
            </div>

            {{-- Media Grid --}}
            <div class="flex-1 overflow-y-auto px-6 pb-4">
                <div class="grid grid-cols-3 sm:grid-cols-4 md:grid-cols-5 gap-3">
                    @forelse($media as $item)
                        <button
                            type="button"
                            wire:click="selectMedia({{ $item->id }})"
                            class="group relative aspect-square rounded-lg overflow-hidden border-2 transition-all
                                {{ $selectedMediaId === $item->id 
                                    ? 'border-emerald-500 ring-2 ring-emerald-500/30' 
                                    : 'border-gray-200 dark:border-gray-600 hover:border-emerald-400' }}"
                        >
                            @if($item->isImage() && $item->thumbnail_path)
                                <img 
                                    src="{{ $item->thumbnail_url }}" 
                                    alt="{{ $item->name }}" 
                                    class="w-full h-full object-cover"
                                >
                            @elseif($item->isImage())
                                <img 
                                    src="{{ $item->url }}" 
                                    alt="{{ $item->name }}" 
                                    class="w-full h-full object-cover"
                                >
                            @else
                                <div class="w-full h-full flex items-center justify-center bg-gray-100 dark:bg-gray-700">
                                    <svg class="h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                                    </svg>
                                </div>
                            @endif
                            
                            {{-- Overlay with info --}}
                            <div class="absolute inset-0 bg-gradient-to-t from-black/70 via-transparent to-transparent opacity-0 group-hover:opacity-100 transition-opacity flex flex-col justify-end p-2">
                                <p class="text-xs text-white truncate font-medium">{{ $item->original_name }}</p>
                                <p class="text-xs text-gray-300">{{ $item->human_size }}</p>
                            </div>

                            {{-- Selected checkmark --}}
                            @if($selectedMediaId === $item->id)
                                <div class="absolute top-2 right-2 w-6 h-6 bg-emerald-500 rounded-full flex items-center justify-center">
                                    <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/>
                                    </svg>
                                </div>
                            @endif
                        </button>
                    @empty
                        <div class="col-span-full text-center py-12">
                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                            <p class="mt-2 text-sm text-gray-500">{{ __('No media files found') }}</p>
                        </div>
                    @endforelse
                </div>

                {{-- Pagination --}}
                @if($media instanceof \Illuminate\Pagination\LengthAwarePaginator && $media->hasPages())
                    <div class="mt-4">
                        {{ $media->links() }}
                    </div>
                @endif
            </div>

            {{-- Footer --}}
            <div class="flex items-center justify-end gap-3 px-6 py-4 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/50">
                <button 
                    type="button"
                    wire:click="closeModal"
                    class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition"
                >
                    {{ __('Cancel') }}
                </button>
                <button 
                    type="button"
                    wire:click="confirmSelection"
                    class="px-4 py-2 text-sm font-medium text-white bg-emerald-600 hover:bg-emerald-700 rounded-lg transition disabled:opacity-50 disabled:cursor-not-allowed"
                    {{ $selectedMediaId ? '' : 'disabled' }}
                >
                    {{ __('Select') }}
                </button>
            </div>
        </div>
    </div>
    @endif
</div>
