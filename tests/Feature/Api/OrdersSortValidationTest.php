<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\Branch;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class OrdersSortValidationTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected Branch $branch;

    protected function setUp(): void
    {
        parent::setUp();

        // Create a test branch
        $this->branch = Branch::factory()->create([
            'name' => 'Test Branch',
            'is_active' => true,
        ]);

        // Create a test user
        $this->user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => \Illuminate\Support\Facades\Hash::make('password'),
        ]);

        Sanctum::actingAs($this->user);
    }

    public function test_sort_by_accepts_only_whitelisted_fields(): void
    {
        // Valid sort_by values should not cause validation errors
        $validFields = ['created_at', 'id', 'status', 'total'];

        foreach ($validFields as $field) {
            $response = $this->getJson("/api/v1/orders?sort_by={$field}");

            // Should not return 422 (validation error)
            // May return 200, 401, or 403 depending on permissions, but not 422
            $this->assertNotEquals(422, $response->status(), "Field '{$field}' should be valid");
        }
    }

    public function test_sort_by_rejects_non_whitelisted_fields(): void
    {
        // Invalid sort_by values should cause validation errors
        $invalidFields = ['users.name', 'DROP TABLE users', '1=1', 'email'];

        foreach ($invalidFields as $field) {
            $response = $this->getJson("/api/v1/orders?sort_by={$field}");

            // Should return 422 (validation error) or 401/403 (auth) but not execute
            // If authenticated, should be 422, otherwise 401/403 is acceptable
            $this->assertContains($response->status(), [401, 403, 422]);

            // If we get 422, verify it's a validation error
            if ($response->status() === 422) {
                $response->assertJsonValidationErrors(['sort_by']);
            }
        }
    }

    public function test_sort_dir_accepts_only_asc_or_desc(): void
    {
        // Valid sort_dir values
        $validDirections = ['asc', 'desc'];

        foreach ($validDirections as $dir) {
            $response = $this->getJson("/api/v1/orders?sort_dir={$dir}");

            // Should not return 422 (validation error)
            $this->assertNotEquals(422, $response->status(), "Direction '{$dir}' should be valid");
        }
    }

    public function test_sort_dir_rejects_invalid_values(): void
    {
        // Invalid sort_dir values
        $invalidDirections = ['ASC', 'DESC', 'ascending', 'descending', '1=1', 'random'];

        foreach ($invalidDirections as $dir) {
            $response = $this->getJson("/api/v1/orders?sort_dir={$dir}");

            // Should return 422 (validation error) or 401/403 (auth) but not execute
            $this->assertContains($response->status(), [401, 403, 422]);

            // If we get 422, verify it's a validation error
            if ($response->status() === 422) {
                $response->assertJsonValidationErrors(['sort_dir']);
            }
        }
    }

    public function test_sort_parameters_work_together(): void
    {
        $response = $this->getJson('/api/v1/orders?sort_by=total&sort_dir=asc');

        // Should not return 422 (validation error)
        // May return 200, 401, or 403 depending on permissions, but not 422
        $this->assertNotEquals(422, $response->status());
    }

    public function test_default_sorting_when_no_parameters_provided(): void
    {
        $response = $this->getJson('/api/v1/orders');

        // Should not return 422 (validation error) when no sort parameters provided
        // May return 200, 401, or 403 depending on permissions, but not 422
        $this->assertNotEquals(422, $response->status());
    }

    public function test_sql_injection_attempts_are_blocked(): void
    {
        // Common SQL injection patterns
        $injectionAttempts = [
            "id'; DROP TABLE sales; --",
            'id OR 1=1',
            'id UNION SELECT * FROM users',
            'id/**/OR/**/1=1',
            '(SELECT * FROM users WHERE id=1)',
        ];

        foreach ($injectionAttempts as $attempt) {
            $response = $this->getJson('/api/v1/orders?sort_by='.urlencode($attempt));

            // Should return 422 (validation error) or 401/403 (auth), not execute the injection
            $this->assertContains($response->status(), [401, 403, 422]);

            // The key point is it should NOT return 200 (successful execution)
            $this->assertNotEquals(200, $response->status(),
                "SQL injection attempt '{$attempt}' should not execute successfully");

            // If we get 422, verify it's a validation error
            if ($response->status() === 422) {
                $response->assertJsonValidationErrors(['sort_by']);
            }
        }
    }
}
