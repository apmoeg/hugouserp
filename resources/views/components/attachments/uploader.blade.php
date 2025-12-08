{{-- resources/views/components/attachments/uploader.blade.php --}}
@props([
    'model' => null,
    'modelType' => null,
    'modelId' => null,
    'multiple' => true,
    'acceptedTypes' => 'image/*,.pdf,.doc,.docx,.xls,.xlsx',
    'maxSize' => 10, // MB
    'existingAttachments' => [],
])

<div x-data="attachmentUploader()" class="space-y-4">
    {{-- Upload Area --}}
    <div class="border-2 border-dashed border-slate-300 dark:border-slate-600 rounded-lg p-6 text-center hover:border-emerald-500 transition-colors cursor-pointer"
         @dragover.prevent="isDragging = true"
         @dragleave.prevent="isDragging = false"
         @drop.prevent="handleDrop($event)"
         :class="isDragging ? 'border-emerald-500 bg-emerald-50 dark:bg-emerald-900/20' : ''"
         @click="$refs.fileInput.click()">
        
        <input type="file"
               x-ref="fileInput"
               @change="handleFiles($event.target.files)"
               {{ $multiple ? 'multiple' : '' }}
               accept="{{ $acceptedTypes }}"
               class="hidden" />
        
        <div class="flex flex-col items-center">
            <svg class="w-12 h-12 text-slate-400 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
            </svg>
            
            <p class="text-sm text-slate-600 dark:text-slate-400 mb-1">
                <span class="font-semibold text-emerald-600 dark:text-emerald-400">{{ __('Click to upload') }}</span>
                {{ __('or drag and drop') }}
            </p>
            
            <p class="text-xs text-slate-500 dark:text-slate-500">
                {{ __('Maximum file size') }}: {{ $maxSize }}MB
            </p>
        </div>
    </div>

    {{-- Files Queue --}}
    <div x-show="files.length > 0" class="space-y-2">
        <h4 class="text-sm font-medium text-slate-700 dark:text-slate-300">{{ __('Files to upload') }}</h4>
        
        <template x-for="(file, index) in files" :key="index">
            <div class="flex items-center gap-3 p-3 bg-slate-50 dark:bg-slate-800 rounded-lg">
                {{-- File Icon --}}
                <div class="flex-shrink-0">
                    <template x-if="file.type.startsWith('image/')">
                        <img :src="file.preview" class="w-12 h-12 object-cover rounded" />
                    </template>
                    <template x-if="!file.type.startsWith('image/')">
                        <div class="w-12 h-12 bg-slate-200 dark:bg-slate-700 rounded flex items-center justify-center">
                            <span class="text-xl" x-text="getFileIcon(file.type)"></span>
                        </div>
                    </template>
                </div>
                
                {{-- File Info --}}
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium text-slate-900 dark:text-slate-100 truncate" x-text="file.name"></p>
                    <p class="text-xs text-slate-500 dark:text-slate-400" x-text="formatFileSize(file.size)"></p>
                    
                    {{-- Optional Note --}}
                    <input type="text"
                           x-model="file.note"
                           placeholder="{{ __('Add a note (optional)') }}"
                           class="mt-2 w-full text-xs px-2 py-1 border border-slate-300 dark:border-slate-600 rounded dark:bg-slate-700 dark:text-slate-100" />
                </div>
                
                {{-- Upload Progress --}}
                <div x-show="file.uploading" class="flex-shrink-0">
                    <svg class="animate-spin h-5 w-5 text-emerald-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                </div>
                
                {{-- Remove Button --}}
                <button type="button"
                        @click="removeFile(index)"
                        x-show="!file.uploading"
                        class="flex-shrink-0 text-red-600 hover:text-red-800 dark:text-red-400">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
        </template>
        
        {{-- Upload Button --}}
        <div class="flex justify-end">
            <x-ui.button @click="uploadFiles()" :loading="isUploading" variant="primary">
                {{ __('Upload Files') }}
            </x-ui.button>
        </div>
    </div>

    {{-- Existing Attachments --}}
    @if(!empty($existingAttachments))
    <div class="space-y-2">
        <h4 class="text-sm font-medium text-slate-700 dark:text-slate-300">{{ __('Existing Attachments') }}</h4>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
            @foreach($existingAttachments as $attachment)
            <div class="flex items-center gap-3 p-3 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg">
                {{-- Thumbnail/Icon --}}
                <div class="flex-shrink-0">
                    @if($attachment->isImage())
                    <img src="{{ $attachment->url }}" alt="{{ $attachment->original_filename }}" class="w-12 h-12 object-cover rounded">
                    @elseif($attachment->isPdf())
                    <div class="w-12 h-12 bg-red-100 dark:bg-red-900/20 rounded flex items-center justify-center">
                        <span class="text-xl">📄</span>
                    </div>
                    @else
                    <div class="w-12 h-12 bg-slate-100 dark:bg-slate-700 rounded flex items-center justify-center">
                        <span class="text-xl">📎</span>
                    </div>
                    @endif
                </div>
                
                {{-- File Info --}}
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium text-slate-900 dark:text-slate-100 truncate">
                        {{ $attachment->original_filename }}
                    </p>
                    <p class="text-xs text-slate-500 dark:text-slate-400">
                        {{ $attachment->human_size }}
                    </p>
                    @if($attachment->description)
                    <p class="text-xs text-slate-600 dark:text-slate-400 mt-1">
                        {{ $attachment->description }}
                    </p>
                    @endif
                </div>
                
                {{-- Actions --}}
                <div class="flex-shrink-0 flex gap-2">
                    <a href="{{ $attachment->url }}" download class="text-emerald-600 hover:text-emerald-800 dark:text-emerald-400">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                        </svg>
                    </a>
                    @if($attachment->isImage())
                    <a href="{{ $attachment->url }}" target="_blank" class="text-blue-600 hover:text-blue-800 dark:text-blue-400">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                        </svg>
                    </a>
                    @endif
                </div>
            </div>
            @endforeach
        </div>
    </div>
    @endif
</div>

@push('scripts')
<script>
function attachmentUploader() {
    return {
        files: [],
        isDragging: false,
        isUploading: false,
        maxSize: {{ $maxSize }} * 1024 * 1024, // Convert MB to bytes
        
        handleFiles(fileList) {
            for (let file of fileList) {
                if (file.size > this.maxSize) {
                    alert(`File ${file.name} is too large. Maximum size is {{ $maxSize }}MB`);
                    continue;
                }
                
                const fileObj = {
                    file: file,
                    name: file.name,
                    size: file.size,
                    type: file.type,
                    note: '',
                    uploading: false,
                    preview: null
                };
                
                // Generate preview for images
                if (file.type.startsWith('image/')) {
                    const reader = new FileReader();
                    reader.onload = (e) => {
                        fileObj.preview = e.target.result;
                    };
                    reader.readAsDataURL(file);
                }
                
                this.files.push(fileObj);
            }
            this.isDragging = false;
        },
        
        handleDrop(e) {
            const files = e.dataTransfer.files;
            this.handleFiles(files);
        },
        
        removeFile(index) {
            this.files.splice(index, 1);
        },
        
        formatFileSize(bytes) {
            if (bytes === 0) return '0 Bytes';
            const k = 1024;
            const sizes = ['Bytes', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i];
        },
        
        getFileIcon(type) {
            if (type.includes('pdf')) return '📄';
            if (type.includes('word')) return '📝';
            if (type.includes('excel') || type.includes('sheet')) return '📊';
            if (type.includes('zip') || type.includes('rar')) return '📦';
            return '📎';
        }
    };
}
</script>
@endpush
