<?php

declare(strict_types=1);

namespace Tests\Feature\Livewire;

use App\Livewire\Profile\Edit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class ProfileAvatarTest extends TestCase
{
    use RefreshDatabase;

    public function test_updating_avatar_replaces_previous_file(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();
        $this->actingAs($user);

        $first = UploadedFile::fake()->image('avatar1.png');
        $second = UploadedFile::fake()->image('avatar2.png');

        Livewire::test(Edit::class)
            ->set('avatar', $first)
            ->call('updateAvatar');

        $firstPath = $user->fresh()->avatar;
        $this->assertNotNull($firstPath);
        Storage::disk('public')->assertExists($firstPath);

        Livewire::test(Edit::class)
            ->set('avatar', $second)
            ->call('updateAvatar');

        $secondPath = $user->fresh()->avatar;
        $this->assertNotNull($secondPath);
        Storage::disk('public')->assertExists($secondPath);
        Storage::disk('public')->assertMissing($firstPath);
    }
}
