<?php

declare(strict_types=1);

namespace Tests\Feature\Media;

use App\Livewire\Admin\MediaLibrary;
use App\Models\Branch;
use App\Models\Media;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class MediaDeletionBranchIsolationTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminBranchA;
    protected User $adminBranchB;
    protected Branch $branchA;
    protected Branch $branchB;
    protected Media $mediaBranchA;
    protected Media $mediaBranchB;

    protected function setUp(): void
    {
        parent::setUp();

        // Create two branches
        $this->branchA = Branch::create(['name' => 'Branch A', 'code' => 'BRA']);
        $this->branchB = Branch::create(['name' => 'Branch B', 'code' => 'BRB']);

        // Create permissions
        Permission::create(['name' => 'media.view', 'guard_name' => 'web']);
        Permission::create(['name' => 'media.manage', 'guard_name' => 'web']);
        Permission::create(['name' => 'media.delete', 'guard_name' => 'web']);
        Permission::create(['name' => 'media.upload', 'guard_name' => 'web']);

        // Create role with media management permissions
        $role = Role::create(['name' => 'Media Manager', 'guard_name' => 'web']);
        $role->givePermissionTo(['media.view', 'media.manage', 'media.delete', 'media.upload']);

        // Create admin users in different branches
        $this->adminBranchA = User::factory()->create(['branch_id' => $this->branchA->id]);
        $this->adminBranchA->assignRole($role);

        $this->adminBranchB = User::factory()->create(['branch_id' => $this->branchB->id]);
        $this->adminBranchB->assignRole($role);

        // Create media in each branch
        $this->mediaBranchA = Media::create([
            'name' => 'Image A',
            'original_name' => 'image-a.jpg',
            'file_path' => 'media/image-a.jpg',
            'mime_type' => 'image/jpeg',
            'extension' => 'jpg',
            'size' => 102400,
            'disk' => 'public',
            'collection' => 'general',
            'user_id' => $this->adminBranchA->id,
            'branch_id' => $this->branchA->id,
        ]);

        $this->mediaBranchB = Media::create([
            'name' => 'Image B',
            'original_name' => 'image-b.jpg',
            'file_path' => 'media/image-b.jpg',
            'mime_type' => 'image/jpeg',
            'extension' => 'jpg',
            'size' => 102400,
            'disk' => 'public',
            'collection' => 'general',
            'user_id' => $this->adminBranchB->id,
            'branch_id' => $this->branchB->id,
        ]);
    }

    public function test_admin_from_branch_a_cannot_delete_media_from_branch_b(): void
    {
        $this->actingAs($this->adminBranchA);

        Livewire::test(MediaLibrary::class)
            ->call('delete', $this->mediaBranchB->id)
            ->assertStatus(404); // Should throw ModelNotFoundException wrapped in 404

        // Verify media from Branch B still exists
        $this->assertDatabaseHas('media', [
            'id' => $this->mediaBranchB->id,
            'branch_id' => $this->branchB->id,
        ]);
    }

    public function test_admin_from_branch_a_can_delete_media_from_branch_a(): void
    {
        $this->actingAs($this->adminBranchA);

        Livewire::test(MediaLibrary::class)
            ->call('delete', $this->mediaBranchA->id)
            ->assertHasNoErrors()
            ->assertSessionHas('success', __('File deleted successfully'));

        // Verify media from Branch A was deleted
        $this->assertDatabaseMissing('media', [
            'id' => $this->mediaBranchA->id,
        ]);
    }

    public function test_admin_from_branch_b_cannot_delete_media_from_branch_a(): void
    {
        $this->actingAs($this->adminBranchB);

        Livewire::test(MediaLibrary::class)
            ->call('delete', $this->mediaBranchA->id)
            ->assertStatus(404); // Should throw ModelNotFoundException wrapped in 404

        // Verify media from Branch A still exists
        $this->assertDatabaseHas('media', [
            'id' => $this->mediaBranchA->id,
            'branch_id' => $this->branchA->id,
        ]);
    }

    public function test_admin_from_branch_b_can_delete_media_from_branch_b(): void
    {
        $this->actingAs($this->adminBranchB);

        Livewire::test(MediaLibrary::class)
            ->call('delete', $this->mediaBranchB->id)
            ->assertHasNoErrors()
            ->assertSessionHas('success', __('File deleted successfully'));

        // Verify media from Branch B was deleted
        $this->assertDatabaseMissing('media', [
            'id' => $this->mediaBranchB->id,
        ]);
    }
}
