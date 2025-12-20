<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\Branch;
use App\Models\Document;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DocumentCanBeAccessedByTest extends TestCase
{
    use RefreshDatabase;

    protected Branch $branchA;
    protected Branch $branchB;
    protected User $userBranchA;
    protected User $userBranchB;

    protected function setUp(): void
    {
        parent::setUp();

        // Create two branches
        $this->branchA = Branch::create(['name' => 'Branch A', 'code' => 'BRA']);
        $this->branchB = Branch::create(['name' => 'Branch B', 'code' => 'BRB']);

        // Create users in different branches
        $this->userBranchA = User::factory()->create(['branch_id' => $this->branchA->id]);
        $this->userBranchB = User::factory()->create(['branch_id' => $this->branchB->id]);
    }

    public function test_public_document_cannot_be_accessed_by_user_from_different_branch(): void
    {
        // Create a public document in Branch A
        $document = Document::create([
            'title' => 'Public Document Branch A',
            'code' => 'DOC-PUB-A',
            'file_name' => 'test.pdf',
            'file_path' => 'documents/test.pdf',
            'file_size' => 1024,
            'file_type' => 'pdf',
            'mime_type' => 'application/pdf',
            'status' => 'published',
            'is_public' => true,
            'access_level' => 'public',
            'branch_id' => $this->branchA->id,
            'uploaded_by' => $this->userBranchA->id,
        ]);

        // User from Branch B should NOT be able to access it
        $this->assertFalse(
            $document->canBeAccessedBy($this->userBranchB),
            'User from Branch B should not access public document from Branch A'
        );
    }

    public function test_public_document_can_be_accessed_by_user_from_same_branch(): void
    {
        // Create a public document in Branch A
        $document = Document::create([
            'title' => 'Public Document Branch A',
            'code' => 'DOC-PUB-A2',
            'file_name' => 'test.pdf',
            'file_path' => 'documents/test.pdf',
            'file_size' => 1024,
            'file_type' => 'pdf',
            'mime_type' => 'application/pdf',
            'status' => 'published',
            'is_public' => true,
            'access_level' => 'public',
            'branch_id' => $this->branchA->id,
            'uploaded_by' => $this->userBranchA->id,
        ]);

        // User from Branch A should be able to access it
        $this->assertTrue(
            $document->canBeAccessedBy($this->userBranchA),
            'User from Branch A should access public document from Branch A'
        );
    }

    public function test_private_document_cannot_be_accessed_by_user_from_different_branch(): void
    {
        // Create a private document in Branch A
        $document = Document::create([
            'title' => 'Private Document Branch A',
            'code' => 'DOC-PRIV-A',
            'file_name' => 'test.pdf',
            'file_path' => 'documents/test.pdf',
            'file_size' => 1024,
            'file_type' => 'pdf',
            'mime_type' => 'application/pdf',
            'status' => 'published',
            'is_public' => false,
            'access_level' => 'private',
            'branch_id' => $this->branchA->id,
            'uploaded_by' => $this->userBranchA->id,
        ]);

        // User from Branch B should NOT be able to access it
        $this->assertFalse(
            $document->canBeAccessedBy($this->userBranchB),
            'User from Branch B should not access private document from Branch A'
        );
    }

    public function test_owner_can_access_their_own_document(): void
    {
        // Create a private document in Branch A by userBranchA
        $document = Document::create([
            'title' => 'Owner Document',
            'code' => 'DOC-OWNER-A',
            'file_name' => 'test.pdf',
            'file_path' => 'documents/test.pdf',
            'file_size' => 1024,
            'file_type' => 'pdf',
            'mime_type' => 'application/pdf',
            'status' => 'published',
            'is_public' => false,
            'access_level' => 'private',
            'branch_id' => $this->branchA->id,
            'uploaded_by' => $this->userBranchA->id,
        ]);

        // Owner should be able to access their own document
        $this->assertTrue(
            $document->canBeAccessedBy($this->userBranchA),
            'Owner should access their own document'
        );
    }

    public function test_shared_document_cannot_be_accessed_by_user_from_different_branch(): void
    {
        // Create a private document in Branch A
        $document = Document::create([
            'title' => 'Shared Document Branch A',
            'code' => 'DOC-SHARED-A',
            'file_name' => 'test.pdf',
            'file_path' => 'documents/test.pdf',
            'file_size' => 1024,
            'file_type' => 'pdf',
            'mime_type' => 'application/pdf',
            'status' => 'published',
            'is_public' => false,
            'access_level' => 'private',
            'branch_id' => $this->branchA->id,
            'uploaded_by' => $this->userBranchA->id,
        ]);

        // Share with user from Branch B
        $document->shares()->create([
            'shared_with_user_id' => $this->userBranchB->id,
            'shared_by' => $this->userBranchA->id,
            'permissions' => ['view'],
        ]);

        // User from Branch B should still NOT be able to access due to branch isolation
        $this->assertFalse(
            $document->canBeAccessedBy($this->userBranchB),
            'User from Branch B should not access shared document from Branch A (branch isolation)'
        );
    }

    public function test_document_without_branch_id_can_be_accessed(): void
    {
        // Create a public document without branch_id (legacy or global document)
        $document = Document::create([
            'title' => 'Global Public Document',
            'code' => 'DOC-GLOBAL',
            'file_name' => 'test.pdf',
            'file_path' => 'documents/test.pdf',
            'file_size' => 1024,
            'file_type' => 'pdf',
            'mime_type' => 'application/pdf',
            'status' => 'published',
            'is_public' => true,
            'access_level' => 'public',
            'branch_id' => null,
            'uploaded_by' => $this->userBranchA->id,
        ]);

        // Any user should be able to access it
        $this->assertTrue(
            $document->canBeAccessedBy($this->userBranchB),
            'User should access public document without branch_id'
        );
    }
}
