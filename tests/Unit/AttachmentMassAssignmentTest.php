<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\Attachment;
use Tests\TestCase;

class AttachmentMassAssignmentTest extends TestCase
{
    public function test_disk_and_path_are_not_mass_assignable(): void
    {
        $attachment = new Attachment;

        $this->assertFalse($attachment->isFillable('disk'));
        $this->assertFalse($attachment->isFillable('path'));
        $this->assertFalse($attachment->isFillable('mime_type'));

        $attachment->fill([
            'attachable_type' => 'App\\Models\\Note',
            'attachable_id' => 1,
            'filename' => 'file.txt',
            'original_filename' => 'file.txt',
            'mime_type' => 'text/plain',
            'size' => 10,
            'disk' => 'local',
            'path' => '../../.env',
            'type' => 'document',
        ]);

        $this->assertArrayNotHasKey('disk', $attachment->getAttributes());
        $this->assertArrayNotHasKey('path', $attachment->getAttributes());
        $this->assertArrayNotHasKey('mime_type', $attachment->getAttributes());
    }
}
