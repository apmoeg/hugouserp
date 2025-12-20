<?php

declare(strict_types=1);

namespace Tests\Feature\Documents;

use App\Models\Branch;
use App\Models\Document;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class DocumentPublicAccessBranchIsolationTest extends TestCase
{
    use RefreshDatabase;

    protected User $userBranchA;
    protected User $userBranchB;
    protected Branch $branchA;
    protected Branch $branchB;
    protected Document $publicDocumentBranchA;

    protected function setUp(): void
    {
        parent::setUp();

        // Create two branches
        $this->branchA = Branch::create(['name' => 'Branch A', 'code' => 'BRA']);
        $this->branchB = Branch::create(['name' => 'Branch B', 'code' => 'BRB']);

        // Create permissions
        Permission::create(['name' => 'documents.view', 'guard_name' => 'web']);
        Permission::create(['name' => 'documents.download', 'guard_name' => 'web']);

        // Create role with document permissions
        $role = Role::create(['name' => 'Document Viewer', 'guard_name' => 'web']);
        $role->givePermissionTo(['documents.view', 'documents.download']);

        // Create users in different branches
        $this->userBranchA = User::factory()->create(['branch_id' => $this->branchA->id]);
        $this->userBranchA->assignRole($role);

        $this->userBranchB = User::factory()->create(['branch_id' => $this->branchB->id]);
        $this->userBranchB->assignRole($role);

        // Create a PUBLIC document in Branch A
        $this->publicDocumentBranchA = Document::create([
            'title' => 'Public Document in Branch A',
            'code' => 'DOC-PUB-A-001',
            'file_name' => 'public-test.pdf',
            'file_path' => 'documents/public-test.pdf',
            'file_size' => 1024,
            'file_type' => 'pdf',
            'mime_type' => 'application/pdf',
            'status' => 'published',
            'is_public' => true,
            'access_level' => 'public',
            'branch_id' => $this->branchA->id,
            'uploaded_by' => $this->userBranchA->id,
        ]);
    }

    public function test_user_from_branch_b_cannot_view_public_document_from_branch_a(): void
    {
        $this->actingAs($this->userBranchB);

        // Try to view public document from Branch A - should get 403
        $response = $this->get(route('app.documents.show', $this->publicDocumentBranchA->id));

        $response->assertStatus(403);
    }

    public function test_user_from_branch_b_cannot_download_public_document_from_branch_a(): void
    {
        $this->actingAs($this->userBranchB);

        // Try to download public document from Branch A - should get 403
        $response = $this->get(route('app.documents.download', $this->publicDocumentBranchA->id));

        $response->assertStatus(403);
    }

    public function test_user_from_branch_a_can_view_public_document_from_branch_a(): void
    {
        $this->actingAs($this->userBranchA);

        // View public document from same Branch A - should succeed
        $response = $this->get(route('app.documents.show', $this->publicDocumentBranchA->id));

        $response->assertStatus(200);
    }

    public function test_another_user_from_branch_a_can_access_public_document(): void
    {
        // Create another user in Branch A
        $anotherUserBranchA = User::factory()->create(['branch_id' => $this->branchA->id]);
        $role = Role::findByName('Document Viewer');
        $anotherUserBranchA->assignRole($role);

        $this->actingAs($anotherUserBranchA);

        // Should be able to view public document from same branch
        $response = $this->get(route('app.documents.show', $this->publicDocumentBranchA->id));

        $response->assertStatus(200);
    }
}
