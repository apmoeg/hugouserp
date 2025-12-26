<?php

declare(strict_types=1);

namespace App\Livewire\Components;

use App\Models\Media;
use App\Services\ImageOptimizationService;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

/**
 * Reusable Media Library Picker Component
 * 
 * Usage in Blade (listen for events in parent component):
 * <livewire:components.media-picker 
 *     :value="$branding_logo_id"
 *     :accept-types="['image']"
 *     :max-size="2048"
 *     :constraints="['maxWidth' => 400, 'maxHeight' => 100]"
 *     field-id="logo-picker"
 * />
 * 
 * Parent component should listen for events:
 * #[On('media-selected')] public function handleMediaSelected(string $fieldId, int $mediaId, array $media)
 * #[On('media-cleared')] public function handleMediaCleared(string $fieldId)
 */
class MediaPicker extends Component
{
    use WithFileUploads, WithPagination;

    // Modal state
    public bool $showModal = false;
    
    // Selected media
    public ?int $selectedMediaId = null;
    public ?array $selectedMedia = null;
    
    // Upload
    public $uploadFile = null;
    
    // Search and filters
    public string $search = '';
    public string $filterType = 'all';
    
    // Constraints passed from parent
    public array $acceptTypes = ['image']; // ['image', 'document', 'all']
    public int $maxSize = 10240; // KB
    public array $constraints = []; // ['maxWidth' => 400, 'maxHeight' => 100, 'aspectRatio' => '16:9']
    
    // Field identification
    public string $fieldId = 'media-picker';
    
    // Current preview URL (for display outside modal)
    public ?string $previewUrl = null;
    public ?string $previewName = null;

    // NOTE: These extension lists mirror those in MediaLibrary.php and Media model.
    // Consider centralizing to config/media.php in future refactor.
    private const ALLOWED_IMAGE_EXTENSIONS = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'ico'];
    private const ALLOWED_DOCUMENT_EXTENSIONS = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'txt', 'csv'];

    protected $listeners = ['openMediaPicker'];

    public function mount(
        ?int $value = null,
        array $acceptTypes = ['image'],
        int $maxSize = 10240,
        array $constraints = [],
        string $fieldId = 'media-picker'
    ): void {
        $this->selectedMediaId = $value;
        $this->acceptTypes = $acceptTypes;
        $this->maxSize = $maxSize;
        $this->constraints = $constraints;
        $this->fieldId = $fieldId;
        
        // Load existing media if ID provided
        if ($this->selectedMediaId) {
            $this->loadSelectedMedia();
        }
    }

    public function loadSelectedMedia(): void
    {
        if (!$this->selectedMediaId) {
            $this->selectedMedia = null;
            $this->previewUrl = null;
            $this->previewName = null;
            return;
        }

        $media = Media::find($this->selectedMediaId);
        if ($media) {
            $this->selectedMedia = [
                'id' => $media->id,
                'name' => $media->name,
                'original_name' => $media->original_name,
                'url' => $media->url,
                'thumbnail_url' => $media->thumbnail_url,
                'mime_type' => $media->mime_type,
                'size' => $media->size,
                'human_size' => $media->human_size,
                'width' => $media->width,
                'height' => $media->height,
                'is_image' => $media->isImage(),
            ];
            $this->previewUrl = $media->isImage() ? ($media->thumbnail_url ?? $media->url) : null;
            $this->previewName = $media->original_name;
        }
    }

    public function openModal(): void
    {
        $user = auth()->user();
        if (!$user || !$user->can('media.view')) {
            session()->flash('error', __('You do not have permission to access the media library'));
            return;
        }
        
        $this->showModal = true;
        $this->search = '';
        $this->filterType = 'all';
    }

    public function closeModal(): void
    {
        $this->showModal = false;
        $this->uploadFile = null;
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatedUploadFile(): void
    {
        // Check permission first before processing the file
        $user = auth()->user();
        if (!$user || !$user->can('media.upload')) {
            $this->uploadFile = null;
            session()->flash('error', __('You do not have permission to upload files'));
            return;
        }

        $allowedExtensions = $this->getAllowedExtensions();
        
        $this->validate([
            'uploadFile' => 'file|max:' . $this->maxSize . '|mimes:' . implode(',', $allowedExtensions),
        ]);

        $optimizationService = app(ImageOptimizationService::class);
        $disk = config('filesystems.media_disk', 'local');

        $this->guardAgainstHtmlPayload($this->uploadFile);
        $result = $optimizationService->optimizeUploadedFile($this->uploadFile, 'general', $disk);

        $media = Media::create([
            'name' => pathinfo($this->uploadFile->getClientOriginalName(), PATHINFO_FILENAME),
            'original_name' => $this->uploadFile->getClientOriginalName(),
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

        // Auto-select the newly uploaded file
        $this->selectMedia($media->id);
        $this->uploadFile = null;
        
        session()->flash('upload-success', __('File uploaded successfully'));
    }

    public function selectMedia(int $mediaId): void
    {
        $user = auth()->user();
        $canBypassBranch = !$user->branch_id || $user->can('media.manage-all');
        
        $media = Media::query()
            ->when($user->branch_id && !$canBypassBranch, fn ($q) => $q->forBranch($user->branch_id))
            ->find($mediaId);

        if (!$media) {
            session()->flash('error', __('Media not found'));
            return;
        }

        // Check constraints
        if (!$this->checkConstraints($media)) {
            return;
        }

        $this->selectedMediaId = $media->id;
        $this->selectedMedia = [
            'id' => $media->id,
            'name' => $media->name,
            'original_name' => $media->original_name,
            'url' => $media->url,
            'thumbnail_url' => $media->thumbnail_url,
            'mime_type' => $media->mime_type,
            'size' => $media->size,
            'human_size' => $media->human_size,
            'width' => $media->width,
            'height' => $media->height,
            'is_image' => $media->isImage(),
        ];
        $this->previewUrl = $media->isImage() ? ($media->thumbnail_url ?? $media->url) : null;
        $this->previewName = $media->original_name;

        // Dispatch event to parent with the selected media
        $this->dispatch('media-selected', 
            fieldId: $this->fieldId,
            mediaId: $media->id,
            media: $this->selectedMedia
        );

        $this->closeModal();
    }

    public function confirmSelection(): void
    {
        if ($this->selectedMediaId) {
            $this->dispatch('media-selected', 
                fieldId: $this->fieldId,
                mediaId: $this->selectedMediaId,
                media: $this->selectedMedia
            );
        }
        $this->closeModal();
    }

    public function clearSelection(): void
    {
        $this->selectedMediaId = null;
        $this->selectedMedia = null;
        $this->previewUrl = null;
        $this->previewName = null;
        
        $this->dispatch('media-cleared', fieldId: $this->fieldId);
    }

    protected function checkConstraints(Media $media): bool
    {
        // Check file type
        if (!empty($this->acceptTypes) && !in_array('all', $this->acceptTypes)) {
            $isImage = $media->isImage();
            $isDocument = $media->isDocument();
            
            if (in_array('image', $this->acceptTypes) && !$isImage) {
                session()->flash('error', __('Please select an image file'));
                return false;
            }
            if (in_array('document', $this->acceptTypes) && !$isDocument) {
                session()->flash('error', __('Please select a document file'));
                return false;
            }
        }

        // Check dimension constraints for images
        if ($media->isImage() && !empty($this->constraints)) {
            if (isset($this->constraints['maxWidth']) && $media->width > $this->constraints['maxWidth']) {
                session()->flash('error', __('Image width should not exceed :width pixels', ['width' => $this->constraints['maxWidth']]));
                return false;
            }
            if (isset($this->constraints['maxHeight']) && $media->height > $this->constraints['maxHeight']) {
                session()->flash('error', __('Image height should not exceed :height pixels', ['height' => $this->constraints['maxHeight']]));
                return false;
            }
            if (isset($this->constraints['minWidth']) && $media->width < $this->constraints['minWidth']) {
                session()->flash('error', __('Image width should be at least :width pixels', ['width' => $this->constraints['minWidth']]));
                return false;
            }
            if (isset($this->constraints['minHeight']) && $media->height < $this->constraints['minHeight']) {
                session()->flash('error', __('Image height should be at least :height pixels', ['height' => $this->constraints['minHeight']]));
                return false;
            }
        }

        return true;
    }

    protected function getAllowedExtensions(): array
    {
        $extensions = [];
        
        if (in_array('all', $this->acceptTypes)) {
            return array_merge(self::ALLOWED_IMAGE_EXTENSIONS, self::ALLOWED_DOCUMENT_EXTENSIONS);
        }
        
        if (in_array('image', $this->acceptTypes)) {
            $extensions = array_merge($extensions, self::ALLOWED_IMAGE_EXTENSIONS);
        }
        
        if (in_array('document', $this->acceptTypes)) {
            $extensions = array_merge($extensions, self::ALLOWED_DOCUMENT_EXTENSIONS);
        }
        
        return $extensions ?: self::ALLOWED_IMAGE_EXTENSIONS;
    }

    protected function guardAgainstHtmlPayload($file): void
    {
        // Only read the first 8KB for HTML detection (efficient for large files)
        $handle = fopen($file->getRealPath(), 'r');
        if (!$handle) {
            // If we can't read the file, reject it for security
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

    public function render()
    {
        $user = auth()->user();
        $media = collect();

        if ($this->showModal && $user && $user->can('media.view')) {
            $canBypassBranch = !$user->branch_id || $user->can('media.manage-all');

            $query = Media::query()
                ->with('user')
                ->when($user->branch_id && !$canBypassBranch, fn ($q) => $q->forBranch($user->branch_id));

            // Filter by type based on acceptTypes
            if (in_array('image', $this->acceptTypes) && !in_array('document', $this->acceptTypes) && !in_array('all', $this->acceptTypes)) {
                $query->images();
            } elseif (in_array('document', $this->acceptTypes) && !in_array('image', $this->acceptTypes) && !in_array('all', $this->acceptTypes)) {
                $query->documents();
            } elseif ($this->filterType === 'images') {
                $query->images();
            } elseif ($this->filterType === 'documents') {
                $query->documents();
            }

            // Apply permission filter
            if (!$user->can('media.view-others')) {
                $query->forUser($user->id);
            }

            // Apply search
            if ($this->search) {
                $search = "%{$this->search}%";
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', $search)
                      ->orWhere('original_name', 'like', $search);
                });
            }

            $media = $query->orderBy('created_at', 'desc')->paginate(12);
        }

        return view('livewire.components.media-picker', [
            'media' => $media,
            'allowedExtensions' => $this->getAllowedExtensions(),
        ]);
    }
}
