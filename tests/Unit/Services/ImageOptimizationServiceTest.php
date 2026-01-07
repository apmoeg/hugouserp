<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Services\ImageOptimizationService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ImageOptimizationServiceTest extends TestCase
{
    private ImageOptimizationService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new ImageOptimizationService;
        Storage::fake('local');
    }

    public function test_optimizes_jpeg_image(): void
    {
        $file = UploadedFile::fake()->image('test.jpg', 2000, 2000);

        $result = $this->service->optimizeUploadedFile($file, 'general', 'local');

        $this->assertArrayHasKey('file_path', $result);
        $this->assertArrayHasKey('thumbnail_path', $result);
        $this->assertArrayHasKey('width', $result);
        $this->assertArrayHasKey('height', $result);

        // Verify image was resized (should be max 1920x1080 for 'general' context)
        $this->assertLessThanOrEqual(1920, $result['width']);
        $this->assertLessThanOrEqual(1080, $result['height']);

        // Verify files exist
        Storage::disk('local')->assertExists($result['file_path']);
        Storage::disk('local')->assertExists($result['thumbnail_path']);
    }

    public function test_optimizes_png_image(): void
    {
        $file = UploadedFile::fake()->image('test.png', 1000, 1000);

        $result = $this->service->optimizeUploadedFile($file, 'product', 'local');

        $this->assertArrayHasKey('file_path', $result);
        $this->assertArrayHasKey('thumbnail_path', $result);

        // Product context should have max 800x800
        $this->assertLessThanOrEqual(800, $result['width']);
        $this->assertLessThanOrEqual(800, $result['height']);

        Storage::disk('local')->assertExists($result['file_path']);
    }

    public function test_stores_non_image_without_optimization(): void
    {
        $file = UploadedFile::fake()->create('document.pdf', 100);

        $result = $this->service->optimizeUploadedFile($file, 'general', 'local');

        $this->assertArrayHasKey('file_path', $result);
        $this->assertNull($result['thumbnail_path']);
        $this->assertNull($result['width']);
        $this->assertNull($result['height']);

        Storage::disk('local')->assertExists($result['file_path']);
    }

    public function test_returns_supported_formats(): void
    {
        $formats = $this->service->getSupportedFormats();

        $this->assertIsArray($formats);
        $this->assertContains('jpg', $formats);
        $this->assertContains('png', $formats);
        $this->assertContains('gif', $formats);
        $this->assertContains('webp', $formats);
    }

    public function test_returns_max_file_size(): void
    {
        $maxSize = $this->service->getMaxFileSize();

        $this->assertEquals(10 * 1024 * 1024, $maxSize); // 10MB
    }

    public function test_thumbnail_has_correct_dimensions(): void
    {
        $file = UploadedFile::fake()->image('test.jpg', 1000, 1000);

        $result = $this->service->optimizeUploadedFile($file, 'general', 'local');

        // Verify thumbnail was created
        $this->assertNotNull($result['thumbnail_path']);
        Storage::disk('local')->assertExists($result['thumbnail_path']);

        // Thumbnail should be 150x150 based on configuration
        $thumbnailPath = Storage::disk('local')->path($result['thumbnail_path']);
        $size = getimagesize($thumbnailPath);

        $this->assertNotFalse($size, 'Thumbnail should be a valid image');
        $this->assertEquals(150, $size[0]); // width
        $this->assertEquals(150, $size[1]); // height
    }

    public function test_detects_correct_driver(): void
    {
        $driver = $this->service->getDriver();

        $this->assertContains($driver, ['imagick', 'gd']);

        // If Imagick is available, it should be preferred
        if (extension_loaded('imagick')) {
            $this->assertEquals('imagick', $driver);
        } else {
            $this->assertEquals('gd', $driver);
        }
    }

    public function test_imagick_produces_better_compression(): void
    {
        // Only run this test if Imagick is available
        if (! extension_loaded('imagick')) {
            $this->markTestSkipped('Imagick extension not available');
        }

        $file = UploadedFile::fake()->image('test.jpg', 1000, 1000);

        $result = $this->service->optimizeUploadedFile($file, 'general', 'local');

        // Imagick should produce smaller files with better quality
        $this->assertLessThan($result['size'], $result['optimized_size'] * 2);
        $this->assertArrayHasKey('file_path', $result);
        Storage::disk('local')->assertExists($result['file_path']);
    }
}
