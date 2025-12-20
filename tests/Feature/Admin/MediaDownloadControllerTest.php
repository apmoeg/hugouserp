<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Models\Media;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class MediaDownloadControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        Permission::create(['name' => 'media.view', 'guard_name' => 'web']);
        Permission::create(['name' => 'media.view-others', 'guard_name' => 'web']);
    }

    public function test_user_can_stream_own_media_from_local_disk(): void
    {
        Storage::fake('local');

        $user = User::factory()->create();
        $user->givePermissionTo(['media.view', 'media.view-others']);

        $path = 'media/test-file.txt';
        Storage::disk('local')->put($path, 'file contents');

        $media = Media::create([
            'name' => 'Test File',
            'original_name' => 'test-file.txt',
            'file_path' => $path,
            'mime_type' => 'text/plain',
            'extension' => 'txt',
            'size' => 14,
            'disk' => 'local',
            'collection' => 'general',
            'user_id' => $user->id,
        ]);

        $response = $this->actingAs($user)->get(route('app.media.download', $media));

        $response->assertOk();
        $response->assertSee('file contents');
        $response->assertHeader('content-type', 'text/plain');
    }

    public function test_user_without_view_others_permission_cannot_access_foreign_media(): void
    {
        Storage::fake('local');

        $owner = User::factory()->create();
        $owner->givePermissionTo(['media.view', 'media.view-others']);

        $viewer = User::factory()->create();
        $viewer->givePermissionTo(['media.view']);

        $path = 'media/secret.txt';
        Storage::disk('local')->put($path, 'top secret');

        $media = Media::create([
            'name' => 'Secret',
            'original_name' => 'secret.txt',
            'file_path' => $path,
            'mime_type' => 'text/plain',
            'extension' => 'txt',
            'size' => 10,
            'disk' => 'local',
            'collection' => 'general',
            'user_id' => $owner->id,
        ]);

        $this->actingAs($viewer)
            ->get(route('app.media.download', $media))
            ->assertForbidden();
    }
}
