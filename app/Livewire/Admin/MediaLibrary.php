<?php

declare(strict_types=1);

namespace App\Livewire\Admin;

use App\Models\Media;
use App\Services\ImageOptimizationService;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('layouts.app')]
class MediaLibrary extends Component
{
    use WithFileUploads;

    public $files = [];
    public string $search = '';
    public string $filterType = 'all'; // all, images, documents
    public string $filterOwner = 'all'; // all, mine
    public string $sortOrder = 'newest'; // newest, oldest, name, size
    
    // Load more pagination
    public int $perPage = 24;
    public int $page = 1;
    public bool $hasMorePages = false;
    public array $loadedMedia = [];
    public bool $isLoading = false;

    protected $queryString = ['search', 'filterType', 'filterOwner', 'sortOrder'];
    
    /**
     * Get all allowed extensions from config
     */
    protected function getAllowedExtensions(): array
    {
        $imageExtensions = config('media.image_extensions', ['jpg', 'jpeg', 'png', 'gif', 'webp', 'ico']);
        $documentExtensions = config('media.document_extensions', ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'csv', 'txt']);
        
        return array_merge($imageExtensions, $documentExtensions);
    }
    
    /**
     * Get all allowed MIME types from config
     */
    protected function getAllowedMimeTypes(): array
    {
        $imageMimes = config('media.image_mimes', [
            'image/jpeg',
            'image/png',
            'image/gif',
            'image/webp',
            'image/x-icon',
            'image/vnd.microsoft.icon',
        ]);
        
        $documentMimes = config('media.document_mimes', [
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'application/vnd.ms-powerpoint',
            'application/vnd.openxmlformats-officedocument.presentationml.presentation',
            'text/csv',
            'text/plain',
        ]);
        
        return array_merge($imageMimes, $documentMimes);
    }
    
    /**
     * Get accept attribute for file input
     */
    public function getAcceptAttribute(): string
    {
        $extensions = $this->getAllowedExtensions();
        return implode(',', array_map(fn($ext) => '.' . $ext, $extensions));
    }

    public function mount(): void
    {
        $user = auth()->user();
        if (!$user || !$user->can('media.view')) {
            abort(403, __('Unauthorized access to media library'));
        }
        
        $this->loadMedia();
    }

    public function updatingSearch(): void
    {
        $this->page = 1;
        $this->loadedMedia = [];
    }
    
    public function updatedSearch(): void
    {
        $this->loadMedia();
    }
    
    public function updatedFilterType(): void
    {
        $this->page = 1;
        $this->loadedMedia = [];
        $this->loadMedia();
    }
    
    public function updatedFilterOwner(): void
    {
        $this->page = 1;
        $this->loadedMedia = [];
        $this->loadMedia();
    }
    
    public function updatedSortOrder(): void
    {
        $this->page = 1;
        $this->loadedMedia = [];
        $this->loadMedia();
    }
    
    public function loadMedia(): void
    {
        $user = auth()->user();
        if (!$user || !$user->can('media.view')) {
            return;
        }
        
        $canBypassBranch = !$user->branch_id || $user->can('media.manage-all');

        $query = Media::query()
            ->with('user')
            ->when($user->branch_id && !$canBypassBranch, fn ($q) => $q->forBranch($user->branch_id))
            ->when($this->filterType === 'images', fn ($q) => $q->images())
            ->when($this->filterType === 'documents', fn ($q) => $q->documents())
            ->when(
                $this->filterOwner === 'mine' || !$user->can('media.view-others'),
                fn ($q) => $q->forUser($user->id)
            )
            ->when($this->search, function ($query) {
                $search = "%{$this->search}%";
                $query->where(function ($searchQuery) use ($search) {
                    $searchQuery->where('name', 'like', $search)
                        ->orWhere('original_name', 'like', $search);
                });
            });
        
        // Apply sorting
        switch ($this->sortOrder) {
            case 'oldest':
                $query->orderBy('created_at', 'asc');
                break;
            case 'name':
                $query->orderBy('original_name', 'asc');
                break;
            case 'size':
                $query->orderBy('size', 'desc');
                break;
            case 'newest':
            default:
                $query->orderBy('created_at', 'desc');
                break;
        }

        $results = $query->paginate($this->perPage, ['*'], 'page', $this->page);
        
        $newItems = collect($results->items())->map(fn ($m) => $this->formatMediaItem($m))->toArray();
        
        if ($this->page === 1) {
            $this->loadedMedia = $newItems;
        } else {
            $this->loadedMedia = array_merge($this->loadedMedia, $newItems);
        }
        
        $this->hasMorePages = $results->hasMorePages();
    }
    
    public function loadMore(): void
    {
        if (!$this->hasMorePages) {
            return;
        }
        
        $this->isLoading = true;
        try {
            $this->page++;
            $this->loadMedia();
        } finally {
            $this->isLoading = false;
        }
    }
    
    protected function formatMediaItem(Media $media): array
    {
        return [
            'id' => $media->id,
            'name' => $media->name,
            'original_name' => $media->original_name,
            'url' => $media->url,
            'thumbnail_url' => $media->thumbnail_url,
            'mime_type' => $media->mime_type,
            'extension' => $media->extension,
            'size' => $media->size,
            'human_size' => $media->human_size,
            'optimized_human_size' => $media->optimized_human_size,
            'compression_ratio' => $media->compression_ratio,
            'width' => $media->width,
            'height' => $media->height,
            'is_image' => $media->isImage(),
            'created_at' => $media->created_at?->format('Y-m-d H:i'),
            'user_id' => $media->user_id,
            'user_name' => $media->user?->name ?? __('Unknown'),
        ];
    }

    public function updatedFiles(): void
    {
        $allowedExtensions = $this->getAllowedExtensions();
        $allowedMimeTypes = $this->getAllowedMimeTypes();
        $maxSize = config('media.max_upload_size', 10240);
        
        $this->validate([
            'files.*' => 'file|max:' . $maxSize . '|mimes:' . implode(',', $allowedExtensions) .
                '|mimetypes:' . implode(',', $allowedMimeTypes),
        ]);

        $user = auth()->user();
        if (!$user->can('media.upload')) {
            session()->flash('error', __('You do not have permission to upload files'));
            return;
        }

        $optimizationService = app(ImageOptimizationService::class);
        $disk = config('media.default_disk', config('filesystems.media_disk', 'local'));

        foreach ($this->files as $file) {
            $this->guardAgainstHtmlPayload($file);
            $result = $optimizationService->optimizeUploadedFile($file, 'general', $disk);

            Media::create([
                'name' => pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME),
                'original_name' => $file->getClientOriginalName(),
                'file_path' => $result['file_path'],
                'thumbnail_path' => $result['thumbnail_path'],
                'mime_type' => $result['mime_type'],
                'extension' => $result['extension'],
                'size' => $result['size'],
                'optimized_size' => $result['optimized_size'],
                'width' => $result['width'],
                'height' => $result['height'],
                'disk' => $disk,
                'collection' => 'general',
                'user_id' => $user->id,
                'branch_id' => $user->branch_id,
            ]);
        }

        $this->files = [];
        
        // Refresh the media list
        $this->page = 1;
        $this->loadedMedia = [];
        $this->loadMedia();
        
        session()->flash('success', __('Files uploaded successfully'));
    }

    public function delete(int $id): void
    {
        $user = auth()->user();
        $canBypassBranch = !$user->branch_id || $user->can('media.manage-all');
        $media = Media::query()
            ->when($user->branch_id && ! $canBypassBranch, fn ($q) => $q->forBranch($user->branch_id))
            ->findOrFail($id);

        // Check permissions
        $canDelete = $user->can('media.manage') ||
                     ($user->can('media.delete') && $media->user_id === $user->id);

        if (!$canDelete) {
            session()->flash('error', __('You do not have permission to delete this file'));
            return;
        }

        // Delete files from storage
        Storage::disk($media->disk)->delete($media->file_path);
        if ($media->thumbnail_path) {
            Storage::disk($media->disk)->delete($media->thumbnail_path);
        }

        $media->delete();
        
        // Remove from loadedMedia array
        $this->loadedMedia = array_filter($this->loadedMedia, fn ($item) => $item['id'] !== $id);
        $this->loadedMedia = array_values($this->loadedMedia);
        
        session()->flash('success', __('File deleted successfully'));
    }

    public function render()
    {
        return view('livewire.admin.media-library', [
            'media' => $this->loadedMedia,
        ])->layout('layouts.app', ['title' => __('Media Library')]);
    }

    protected function guardAgainstHtmlPayload($file): void
    {
        // Only read the first 8KB for HTML detection (efficient for large files)
        $handle = fopen($file->getRealPath(), 'r');
        if (!$handle) {
            abort(422, __('Unable to verify file content. Upload rejected.'));
        }
        
        try {
            $contents = strtolower((string) fread($handle, 8192));
            $patterns = ['<script', '<iframe', '<html', '<object', '<embed', '&lt;script'];

            if (collect($patterns)->contains(fn ($needle) => str_contains($contents, $needle))) {
                abort(422, __('Uploaded file contains HTML content and was rejected.'));
            }
        } finally {
            fclose($handle);
        }
    }
}
