<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\JournalEntry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class JournalEntryTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_returns_reference_as_string(): void
    {
        $entry = new JournalEntry;
        $entry->reference_number = 'JE-001';

        $this->assertEquals('JE-001', $entry->reference);
    }

    /** @test */
    public function it_returns_empty_string_when_reference_number_is_null(): void
    {
        $entry = new JournalEntry;
        $entry->reference_number = null;

        $this->assertEquals('', $entry->reference);
    }

    /** @test */
    public function reference_attribute_is_appended(): void
    {
        $entry = new JournalEntry;
        $entry->reference_number = 'JE-002';

        $array = $entry->toArray();

        $this->assertArrayHasKey('reference', $array);
        $this->assertEquals('JE-002', $array['reference']);
    }
}
