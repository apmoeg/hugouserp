<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Services\SettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SettingsServiceTest extends TestCase
{
    use RefreshDatabase;

    private SettingsService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new SettingsService;
    }

    /**
     * Test encrypted array settings round-trip correctly.
     */
    public function test_encrypted_array_round_trip(): void
    {
        $key = 'test.encrypted.array';
        $arrayValue = [
            'option1' => 'value1',
            'option2' => 'value2',
            'nested' => [
                'key' => 'nested_value',
            ],
        ];

        // Set encrypted array
        $this->service->set($key, $arrayValue, [
            'is_encrypted' => true,
            'type' => 'array',
            'group' => 'test',
        ]);

        // Retrieve and verify structure is maintained
        $retrieved = $this->service->getDecrypted($key);

        $this->assertIsArray($retrieved);
        $this->assertEquals($arrayValue, $retrieved);
        $this->assertEquals('value1', $retrieved['option1']);
        $this->assertEquals('value2', $retrieved['option2']);
        $this->assertIsArray($retrieved['nested']);
        $this->assertEquals('nested_value', $retrieved['nested']['key']);
    }

    /**
     * Test encrypted string values still work correctly.
     */
    public function test_encrypted_string_round_trip(): void
    {
        $key = 'test.encrypted.string';
        $stringValue = 'sensitive_api_key_12345';

        // Set encrypted string
        $this->service->set($key, $stringValue, [
            'is_encrypted' => true,
            'type' => 'string',
            'group' => 'test',
        ]);

        // Retrieve and verify it's still a string
        $retrieved = $this->service->getDecrypted($key);

        $this->assertIsString($retrieved);
        $this->assertEquals($stringValue, $retrieved);
    }

    /**
     * Test non-encrypted values are returned correctly.
     */
    public function test_non_encrypted_value_retrieval(): void
    {
        $key = 'test.plain.value';
        $value = 'plain_text_value';

        // Set non-encrypted value
        $this->service->set($key, $value, [
            'is_encrypted' => false,
            'type' => 'string',
            'group' => 'test',
        ]);

        // Retrieve and verify
        $retrieved = $this->service->get($key);

        $this->assertEquals($value, $retrieved);
    }

    /**
     * Test default value is returned when key doesn't exist.
     */
    public function test_default_value_when_key_missing(): void
    {
        $default = ['default' => 'array'];
        $retrieved = $this->service->getDecrypted('non.existent.key', $default);

        $this->assertEquals($default, $retrieved);
    }

    /**
     * Test non-encrypted array settings round-trip correctly without data loss.
     */
    public function test_non_encrypted_array_round_trip(): void
    {
        $key = 'test.plain.array';
        $arrayValue = [
            'option1' => 'value1',
            'option2' => 'value2',
            'option3' => 'value3',
        ];

        // Set non-encrypted array
        $this->service->set($key, $arrayValue, [
            'is_encrypted' => false,
            'type' => 'array',
            'group' => 'test',
        ]);

        // Retrieve and verify full array is preserved
        $retrieved = $this->service->getDecrypted($key);

        $this->assertIsArray($retrieved);
        $this->assertCount(3, $retrieved);
        $this->assertEquals($arrayValue, $retrieved);
        $this->assertEquals('value1', $retrieved['option1']);
        $this->assertEquals('value2', $retrieved['option2']);
        $this->assertEquals('value3', $retrieved['option3']);
    }

    /**
     * Test getByGroup decodes encrypted arrays correctly.
     */
    public function test_get_by_group_decodes_encrypted_arrays(): void
    {
        $group = 'test-group';
        $key = 'test.group.encrypted.array';
        $arrayValue = [
            'setting1' => 'value1',
            'setting2' => 'value2',
            'nested' => ['key' => 'value'],
        ];

        // Set encrypted array setting
        $this->service->set($key, $arrayValue, [
            'is_encrypted' => true,
            'type' => 'array',
            'group' => $group,
        ]);

        // Get by group
        $groupSettings = $this->service->getByGroup($group);

        $this->assertIsArray($groupSettings);
        $this->assertArrayHasKey($key, $groupSettings);
        $this->assertIsArray($groupSettings[$key]);
        $this->assertEquals($arrayValue, $groupSettings[$key]);
    }

    /**
     * Test getByCategory handles encrypted and plain values correctly.
     */
    public function test_get_by_category_handles_encrypted_and_plain_values(): void
    {
        $category = 'notifications';

        // Set plain string setting
        $plainKey = 'notifications.email.from';
        $plainValue = 'noreply@example.com';
        $this->service->set($plainKey, $plainValue, [
            'is_encrypted' => false,
            'type' => 'string',
            'group' => 'notifications',
            'category' => $category,
        ]);

        // Set encrypted array setting
        $encryptedKey = 'notifications.sms.config';
        $encryptedValue = [
            'api_key' => 'secret_key_123',
            'sender' => 'MySender',
        ];
        $this->service->set($encryptedKey, $encryptedValue, [
            'is_encrypted' => true,
            'type' => 'array',
            'group' => 'notifications',
            'category' => $category,
        ]);

        // Get by category
        $categorySettings = $this->service->getByCategory($category);

        $this->assertIsArray($categorySettings);
        $this->assertArrayHasKey($plainKey, $categorySettings);
        $this->assertArrayHasKey($encryptedKey, $categorySettings);

        // Verify plain value
        $this->assertEquals($plainValue, $categorySettings[$plainKey]);

        // Verify encrypted array was decoded
        $this->assertIsArray($categorySettings[$encryptedKey]);
        $this->assertEquals($encryptedValue, $categorySettings[$encryptedKey]);
    }
}
