<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class HomeRouteTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_with_dashboard_permission_redirects_to_dashboard(): void
    {
        // Create a branch and user
        $branch = Branch::create(['name' => 'Test Branch', 'code' => 'TB001']);
        $user = User::factory()->create(['branch_id' => $branch->id]);

        // Create and assign dashboard permission
        $permission = Permission::create(['name' => 'dashboard.view']);
        $user->givePermissionTo($permission);

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

    public function test_authenticated_user_without_dashboard_permission_redirects_to_profile(): void
    {
        // Create a branch and user
        $branch = Branch::create(['name' => 'Test Branch', 'code' => 'TB001']);
        $user = User::factory()->create(['branch_id' => $branch->id]);

        // Do NOT assign dashboard permission

        // Act as authenticated user
        $response = $this->actingAs($user)->get('/');

        // Assert authenticated
        $this->assertAuthenticated();

        // Assert redirect to profile edit
        $response->assertStatus(302);
        $response->assertRedirect(route('profile.edit'));
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

    public function test_intended_redirect_takes_precedence(): void
    {
        // Create a branch and user
        $branch = Branch::create(['name' => 'Test Branch', 'code' => 'TB001']);
        $user = User::factory()->create(['branch_id' => $branch->id]);

        // Create and assign dashboard permission
        $permission = Permission::create(['name' => 'dashboard.view']);
        $user->givePermissionTo($permission);

        // Set an intended URL in the session
        session()->put('url.intended', url('/some-intended-url'));

        // Act as authenticated user
        $response = $this->actingAs($user)->get('/');

        // Assert redirects to intended URL
        $response->assertStatus(302);
        $response->assertRedirect('/some-intended-url');
    }
}
