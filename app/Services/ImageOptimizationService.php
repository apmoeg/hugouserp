<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ImageOptimizationService
{
    /**
     * Image processing driver to use ('imagick' or 'gd')
     * Automatically detected based on availability
     */
    protected string $driver;

    /**
     * Maximum dimensions for different contexts
     */
    protected array $maxDimensions = [
        'thumbnail' => ['width' => 150, 'height' => 150],
        'logo' => ['width' => 400, 'height' => 200],
        'favicon' => ['width' => 64, 'height' => 64],
        'product' => ['width' => 800, 'height' => 800],
        'document' => ['width' => 1200, 'height' => 1200],
        'general' => ['width' => 1920, 'height' => 1080],
    ];

    /**
     * Quality settings for different contexts
     */
    protected array $qualitySettings = [
        'thumbnail' => 70,
        'logo' => 85,
        'favicon' => 100,
        'product' => 80,
        'document' => 85,
        'general' => 80,
    ];

    /**
     * Initialize the service and detect available image processing library
     */
    public function __construct()
    {
        // Prefer Imagick for better quality and performance, fallback to GD
        $this->driver = extension_loaded('imagick') ? 'imagick' : 'gd';

        Log::info('ImageOptimizationService initialized', ['driver' => $this->driver]);
    }

    /**
     * Optimize an uploaded image using Imagick or GD (with automatic fallback)
     */
    public function optimizeUploadedFile(
        UploadedFile $file,
        string $context = 'general',
        ?string $disk = 'local'
    ): array {
        if (! $this->isImage($file)) {
            return $this->storeWithoutOptimization($file, $disk);
        }

        try {
            // Try Imagick first for better quality, fallback to GD if it fails
            if ($this->driver === 'imagick') {
                return $this->optimizeWithImagick($file, $context, $disk);
            } else {
                return $this->optimizeWithGD($file, $context, $disk);
            }
        } catch (\Exception $e) {
            Log::error('Image optimization failed with '.$this->driver.': '.$e->getMessage());

            // If Imagick failed, try GD as fallback
            if ($this->driver === 'imagick') {
                Log::info('Falling back to GD for image optimization');
                try {
                    return $this->optimizeWithGD($file, $context, $disk);
                } catch (\Exception $gdException) {
                    Log::error('GD fallback also failed: '.$gdException->getMessage());
                }
            }

            return $this->storeWithoutOptimization($file, $disk);
        }
    }

    /**
     * Optimize image using Imagick library
     */
    protected function optimizeWithImagick(
        UploadedFile $file,
        string $context,
        string $disk
    ): array {
        $originalSize = $file->getSize();

        // Get dimensions
        $maxWidth = $this->maxDimensions[$context]['width'] ?? 1920;
        $maxHeight = $this->maxDimensions[$context]['height'] ?? 1080;
        $quality = $this->qualitySettings[$context] ?? 80;

        // Create Imagick instance
        $image = new \Imagick($file->getRealPath());

        // Get original dimensions
        $originalWidth = $image->getImageWidth();
        $originalHeight = $image->getImageHeight();

        // Calculate new dimensions maintaining aspect ratio
        $newWidth = $originalWidth;
        $newHeight = $originalHeight;

        if ($originalWidth > $maxWidth || $originalHeight > $maxHeight) {
            $ratio = min($maxWidth / $originalWidth, $maxHeight / $originalHeight);
            $newWidth = (int) round($originalWidth * $ratio);
            $newHeight = (int) round($originalHeight * $ratio);

            // Resize image with high-quality algorithm
            $image->resizeImage($newWidth, $newHeight, \Imagick::FILTER_LANCZOS, 1);
        }

        // Set compression quality
        $image->setImageCompressionQuality($quality);

        // Strip metadata to reduce file size
        $image->stripImage();

        // Generate unique filename
        $extension = strtolower($file->getClientOriginalExtension());
        $filename = uniqid().'_'.time().'.'.$extension;
        $path = 'media/'.date('Y/m').'/'.$filename;

        // Get image blob and store
        $imageBlob = $image->getImageBlob();
        Storage::disk($disk)->put($path, $imageBlob);

        $optimizedSize = Storage::disk($disk)->size($path);

        // Generate thumbnail
        $thumbnailPath = $this->generateThumbnailWithImagick($image, $disk);

        // Clean up
        $image->clear();
        $image->destroy();

        return [
            'file_path' => $path,
            'thumbnail_path' => $thumbnailPath,
            'size' => $originalSize,
            'optimized_size' => $optimizedSize,
            'width' => $newWidth,
            'height' => $newHeight,
            'mime_type' => $file->getMimeType(),
            'extension' => $extension,
        ];
    }

    /**
     * Optimize image using GD library
     */
    protected function optimizeWithGD(
        UploadedFile $file,
        string $context,
        string $disk
    ): array {
        $originalSize = $file->getSize();

        // Get dimensions
        $maxWidth = $this->maxDimensions[$context]['width'] ?? 1920;
        $maxHeight = $this->maxDimensions[$context]['height'] ?? 1080;
        $quality = $this->qualitySettings[$context] ?? 80;

        // Load image using GD
        $sourceImage = $this->createImageFromFileGD($file);
        if (! $sourceImage) {
            return $this->storeWithoutOptimization($file, $disk);
        }

        $originalWidth = imagesx($sourceImage);
        $originalHeight = imagesy($sourceImage);

        // Calculate new dimensions maintaining aspect ratio
        $newWidth = $originalWidth;
        $newHeight = $originalHeight;

        if ($originalWidth > $maxWidth || $originalHeight > $maxHeight) {
            $ratio = min($maxWidth / $originalWidth, $maxHeight / $originalHeight);
            $newWidth = (int) round($originalWidth * $ratio);
            $newHeight = (int) round($originalHeight * $ratio);
        }

        // Create resized image
        $resizedImage = imagecreatetruecolor($newWidth, $newHeight);

        // Preserve transparency for PNG and GIF
        if ($file->getMimeType() === 'image/png' || $file->getMimeType() === 'image/gif') {
            imagealphablending($resizedImage, false);
            imagesavealpha($resizedImage, true);
            $transparent = imagecolorallocatealpha($resizedImage, 0, 0, 0, 127);
            imagefilledrectangle($resizedImage, 0, 0, $newWidth, $newHeight, $transparent);
        }

        imagecopyresampled($resizedImage, $sourceImage, 0, 0, 0, 0, $newWidth, $newHeight, $originalWidth, $originalHeight);

        // Generate unique filename
        $extension = strtolower($file->getClientOriginalExtension());
        $filename = uniqid().'_'.time().'.'.$extension;
        $path = 'media/'.date('Y/m').'/'.$filename;

        // Save to temporary file
        $tempPath = sys_get_temp_dir().'/'.$filename;
        $this->saveImageGD($resizedImage, $tempPath, $extension, $quality);

        // Store the optimized image
        Storage::disk($disk)->put($path, file_get_contents($tempPath));
        unlink($tempPath);

        $optimizedSize = Storage::disk($disk)->size($path);

        // Generate thumbnail
        $thumbnailPath = $this->generateThumbnailWithGD($resizedImage, $disk);

        // Clean up
        imagedestroy($sourceImage);
        imagedestroy($resizedImage);

        return [
            'file_path' => $path,
            'thumbnail_path' => $thumbnailPath,
            'size' => $originalSize,
            'optimized_size' => $optimizedSize,
            'width' => $newWidth,
            'height' => $newHeight,
            'mime_type' => $file->getMimeType(),
            'extension' => $extension,
        ];
    }

    /**
     * Generate a thumbnail for an image using Imagick
     */
    protected function generateThumbnailWithImagick(\Imagick $sourceImage, string $disk): string
    {
        $thumbWidth = $this->maxDimensions['thumbnail']['width'];
        $thumbHeight = $this->maxDimensions['thumbnail']['height'];

        // Clone the image to create thumbnail
        $thumbnail = clone $sourceImage;

        // Crop and resize to fit exactly (cover mode)
        $thumbnail->cropThumbnailImage($thumbWidth, $thumbHeight);

        // Set compression quality
        $thumbnail->setImageCompressionQuality(70);
        $thumbnail->setImageFormat('jpeg');

        $thumbnailFilename = 'thumb_'.uniqid().'_'.time().'.jpg';
        $thumbnailPath = 'media/thumbnails/'.date('Y/m').'/'.$thumbnailFilename;

        // Store thumbnail
        Storage::disk($disk)->put($thumbnailPath, $thumbnail->getImageBlob());

        // Clean up
        $thumbnail->clear();
        $thumbnail->destroy();

        return $thumbnailPath;
    }

    /**
     * Generate a thumbnail for an image using GD
     */
    protected function generateThumbnailWithGD($sourceImage, string $disk): string
    {
        $thumbWidth = $this->maxDimensions['thumbnail']['width'];
        $thumbHeight = $this->maxDimensions['thumbnail']['height'];

        $originalWidth = imagesx($sourceImage);
        $originalHeight = imagesy($sourceImage);

        // Calculate dimensions to fit and center crop
        $ratio = max($thumbWidth / $originalWidth, $thumbHeight / $originalHeight);
        $resizedWidth = (int) round($originalWidth * $ratio);
        $resizedHeight = (int) round($originalHeight * $ratio);

        // Create intermediate resized image
        $resized = imagecreatetruecolor($resizedWidth, $resizedHeight);
        imagecopyresampled($resized, $sourceImage, 0, 0, 0, 0, $resizedWidth, $resizedHeight, $originalWidth, $originalHeight);

        // Create thumbnail with crop
        $thumbnail = imagecreatetruecolor($thumbWidth, $thumbHeight);
        $cropX = (int) round(($resizedWidth - $thumbWidth) / 2);
        $cropY = (int) round(($resizedHeight - $thumbHeight) / 2);

        imagecopy($thumbnail, $resized, 0, 0, $cropX, $cropY, $thumbWidth, $thumbHeight);

        $thumbnailFilename = 'thumb_'.uniqid().'_'.time().'.jpg';
        $thumbnailPath = 'media/thumbnails/'.date('Y/m').'/'.$thumbnailFilename;

        // Save to temporary file
        $tempPath = sys_get_temp_dir().'/'.$thumbnailFilename;
        imagejpeg($thumbnail, $tempPath, 70);

        // Store thumbnail
        Storage::disk($disk)->put($thumbnailPath, file_get_contents($tempPath));
        unlink($tempPath);

        // Clean up
        imagedestroy($resized);
        imagedestroy($thumbnail);

        return $thumbnailPath;
    }

    /**
     * Create GD image resource from uploaded file
     *
     * @return \GdImage|false Returns GD image resource on success, false on failure
     */
    protected function createImageFromFileGD(UploadedFile $file)
    {
        $mimeType = $file->getMimeType();
        $filePath = $file->getRealPath();

        try {
            switch ($mimeType) {
                case 'image/jpeg':
                case 'image/jpg':
                    return imagecreatefromjpeg($filePath);
                case 'image/png':
                    return imagecreatefrompng($filePath);
                case 'image/gif':
                    return imagecreatefromgif($filePath);
                case 'image/webp':
                    return imagecreatefromwebp($filePath);
                case 'image/bmp':
                case 'image/x-ms-bmp':
                    return imagecreatefrombmp($filePath);
                default:
                    return null;
            }
        } catch (\Exception $e) {
            Log::error('Failed to create image from file with GD: '.$e->getMessage());

            return null;
        }
    }

    /**
     * PNG quality conversion divisor (PNG uses 0-9 scale, we use 0-100)
     */
    private const PNG_QUALITY_DIVISOR = 11;

    /**
     * Save GD image resource to file
     *
     * @param  \GdImage  $image  The GD image resource to save
     * @param  string  $path  Target file path
     * @param  string  $extension  File extension (determines output format)
     * @param  int  $quality  Output quality (0-100)
     * @return bool Returns true on success, false on failure
     */
    protected function saveImageGD($image, string $path, string $extension, int $quality): bool
    {
        try {
            switch (strtolower($extension)) {
                case 'jpg':
                case 'jpeg':
                    return imagejpeg($image, $path, $quality);
                case 'png':
                    // PNG quality is 0-9, convert from 0-100
                    $pngQuality = (int) round((100 - $quality) / self::PNG_QUALITY_DIVISOR);

                    return imagepng($image, $path, $pngQuality);
                case 'gif':
                    return imagegif($image, $path);
                case 'webp':
                    return imagewebp($image, $path, $quality);
                case 'bmp':
                    return imagebmp($image, $path);
                default:
                    return false;
            }
        } catch (\Exception $e) {
            Log::error('Failed to save image with GD: '.$e->getMessage());

            return false;
        }
    }

    /**
     * Store file without optimization (for non-images)
     */
    protected function storeWithoutOptimization(UploadedFile $file, string $disk): array
    {
        $extension = $file->getClientOriginalExtension();
        $filename = uniqid().'_'.time().'.'.$extension;
        $path = 'media/'.date('Y/m').'/'.$filename;

        Storage::disk($disk)->put($path, file_get_contents($file->getRealPath()));

        return [
            'file_path' => $path,
            'thumbnail_path' => null,
            'size' => $file->getSize(),
            'optimized_size' => $file->getSize(),
            'width' => null,
            'height' => null,
            'mime_type' => $file->getMimeType(),
            'extension' => $extension,
        ];
    }

    /**
     * Check if file is an image
     */
    protected function isImage(UploadedFile $file): bool
    {
        return str_starts_with($file->getMimeType(), 'image/');
    }

    /**
     * Optimize for specific context (logo, favicon, etc.)
     */
    public function optimizeForContext(
        UploadedFile $file,
        string $context,
        ?string $disk = 'local'
    ): array {
        return $this->optimizeUploadedFile($file, $context, $disk);
    }

    /**
     * Get supported image formats
     */
    public function getSupportedFormats(): array
    {
        return ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp'];
    }

    /**
     * Get maximum file size in bytes
     */
    public function getMaxFileSize(): int
    {
        return 10 * 1024 * 1024; // 10MB
    }

    /**
     * Get the current image processing driver being used
     *
     * @return string 'imagick' or 'gd'
     */
    public function getDriver(): string
    {
        return $this->driver;
    }
}
