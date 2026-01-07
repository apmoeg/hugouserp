<?php

declare(strict_types=1);

namespace Tests\Feature\Livewire;

use App\Livewire\Documents\Show;
use App\Models\Branch;
use App\Models\Document;
use App\Models\DocumentShare;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class DocumentShowSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_view_only_user_does_not_receive_shareable_users(): void
    {
        $branch = Branch::factory()->create();
        $owner = User::factory()->create(['branch_id' => $branch->id]);
        $viewer = User::factory()->create(['branch_id' => $branch->id]);
        $document = $this->createDocument($owner, $branch);

        DocumentShare::create([
            'document_id' => $document->id,
            'shared_with_user_id' => $viewer->id,
            'shared_by' => $owner->id,
            'permission' => 'view',
            'expires_at' => null,
            'access_count' => 0,
        ]);

        Gate::define('documents.view', fn () => true);
        Gate::define('documents.share', fn () => false);

        Livewire::actingAs($viewer)
            ->test(Show::class, ['document' => $document])
            ->assertViewHas('users', fn ($users) => $users->isEmpty());
    }

    public function test_shareable_users_are_limited(): void
    {
        $branch = Branch::factory()->create();
        $owner = User::factory()->create(['branch_id' => $branch->id]);
        $document = $this->createDocument($owner, $branch);

        User::factory()->count(60)->create(['branch_id' => $branch->id]);

        Gate::define('documents.view', fn () => true);
        Gate::define('documents.share', fn () => true);
        Gate::define('documents.manage', fn () => true);

        Livewire::actingAs($owner)
            ->test(Show::class, ['document' => $document])
            ->assertViewHas('users', fn ($users) => $users->count() === 50);
    }

    public function test_user_cannot_download_document_from_other_branch(): void
    {
        $branchA = Branch::factory()->create();
        $branchB = Branch::factory()->create();
        $user = User::factory()->create(['branch_id' => $branchA->id]);
        $owner = User::factory()->create(['branch_id' => $branchB->id]);
        $document = $this->createDocument($owner, $branchB);

        Gate::define('documents.view', fn () => true);
        Gate::define('documents.download', fn () => true);

        $this->actingAs($user);
        $this->expectException(HttpException::class);

        Livewire::test(Show::class, ['document' => $document])
            ->call('download');
    }

    private function createDocument(User $owner, Branch $branch): Document
    {
        return Document::forceCreate([
            'code' => 'DOC-'.uniqid(),
            'title' => 'Secure Doc',
            'description' => 'Test document',
            'file_name' => 'doc.pdf',
            'file_path' => 'documents/doc.pdf',
            'file_size' => 1024,
            'file_type' => 'pdf',
            'mime_type' => 'application/pdf',
            'uploaded_by' => $owner->id,
            'branch_id' => $branch->id,
            'status' => 'draft',
            'access_level' => 'private',
            'version' => 1,
        ]);
    }
}
