<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HomeRouteTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_users_are_redirected_to_dashboard(): void
    {
        // Create a branch and user
        $branch = Branch::create(['name' => 'Test Branch', 'code' => 'TB001']);
        $user = User::factory()->create(['branch_id' => $branch->id]);

        // Act as authenticated user
        $response = $this->actingAs($user)->get('/');

        // Assert authenticated
        $this->assertAuthenticated();

        // Assert redirect to dashboard
        $response->assertStatus(302);
        $response->assertRedirect(route('dashboard'));
        
        // Assert Location header is present and points to dashboard
        $response->assertHeader('Location');
        $this->assertStringContainsString('dashboard', $response->headers->get('Location'));
    }

    public function test_guest_users_are_redirected_to_login(): void
    {
        // Request home route without authentication
        $response = $this->get('/');

        // Assert guest status
        $this->assertGuest();

        // Assert redirect to login
        $response->assertStatus(302);
        $response->assertRedirect(route('login'));
        
        // Assert Location header is present and points to login
        $response->assertHeader('Location');
        $loginPath = route('login');
        $location = $response->headers->get('Location');
        
        // Verify the login path is present in the redirect target
        $this->assertStringContainsString('login', $location);
    }
}
