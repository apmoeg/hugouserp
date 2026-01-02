<?php

declare(strict_types=1);

namespace Tests\Feature\Livewire;

use App\Models\Branch;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * Regression test to ensure Livewire and Alpine scripts are loaded exactly once.
 * 
 * This test prevents the bug where multiple instances of Livewire/Alpine
 * are initialized, causing console warnings and broken Livewire behavior.
 * 
 * In Livewire v4:
 * - Alpine.js is bundled with Livewire
 * - When inject_assets is false, we use manual @livewireScripts/@livewireStyles
 * - This ensures a single instance of both Livewire and Alpine
 * 
 * Root cause of duplicate issues (when misconfigured):
 * - inject_assets = true AND manual @livewireScripts/@livewireStyles = double injection
 * - Manual Alpine.js import in npm + Livewire's bundled Alpine = double Alpine
 * - Turbo.js conflicts with Livewire's wire:navigate feature
 */
class DuplicateScriptsRegressionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        
        // Create dashboard permission
        Permission::findOrCreate('dashboard.view', 'web');
    }

    /**
     * Test the Livewire config has inject_assets disabled.
     * 
     * This is the critical guard that prevents duplicate scripts.
     * 
     * When inject_assets is true in Livewire v4:
     * - Livewire automatically injects its scripts
     * - Alpine.js is also injected (bundled with Livewire)
     * 
     * Combined with manual @livewireScripts/@livewireStyles in layouts,
     * this causes the "Detected multiple instances" console errors.
     */
    public function test_livewire_inject_assets_is_disabled(): void
    {
        $this->assertFalse(
            config('livewire.inject_assets'),
            'Livewire inject_assets should be false. When true, Livewire auto-injects scripts which duplicates the manual @livewireScripts in layouts.'
        );
    }

    /**
     * Test that Livewire scripts/styles directives aren't duplicated in layouts.
     * 
     * When using @livewireScripts/@livewireStyles in layouts, they should only appear once.
     */
    public function test_livewire_directives_not_duplicated(): void
    {
        $branch = Branch::factory()->create();
        $user = User::factory()->create(['branch_id' => $branch->id]);
        $user->givePermissionTo('dashboard.view');

        $response = $this->actingAs($user)->get('/dashboard');
        $content = $response->getContent();

        // Check for duplicate Livewire script tags with src attribute
        // This pattern matches actual script tag loads, not inline mentions
        $livewireScriptTags = preg_match_all('/<script[^>]+livewire[^>]+src=[^>]+>/', $content);
        
        $this->assertLessThanOrEqual(1, $livewireScriptTags, 
            'Livewire script tag should appear at most once in the page.');
    }

    /**
     * Test that the app layout has livewire directives.
     * 
     * Ensures that @livewireStyles and @livewireScripts are present in the layout
     * (since inject_assets is disabled, we need manual inclusion).
     */
    public function test_app_layout_includes_livewire_directives(): void
    {
        $layoutPath = resource_path('views/layouts/app.blade.php');
        $layoutContent = file_get_contents($layoutPath);
        
        $this->assertStringContainsString('@livewireStyles', $layoutContent,
            'App layout should include @livewireStyles directive.');
        
        $this->assertStringContainsString('@livewireScripts', $layoutContent,
            'App layout should include @livewireScripts directive.');
    }

    /**
     * Test that the guest layout has livewire directives.
     */
    public function test_guest_layout_includes_livewire_directives(): void
    {
        $layoutPath = resource_path('views/layouts/guest.blade.php');
        $layoutContent = file_get_contents($layoutPath);
        
        $this->assertStringContainsString('@livewireStyles', $layoutContent,
            'Guest layout should include @livewireStyles directive.');
        
        $this->assertStringContainsString('@livewireScripts', $layoutContent,
            'Guest layout should include @livewireScripts directive.');
    }

    /**
     * Test that Alpine.js is not manually bundled via npm.
     * 
     * In Livewire v4, Alpine.js is bundled with Livewire. Having a separate
     * Alpine.js import would cause "Detected multiple instances of Alpine" errors.
     */
    public function test_alpine_not_manually_bundled(): void
    {
        $packageJson = json_decode(file_get_contents(base_path('package.json')), true);
        
        $hasDependency = isset($packageJson['dependencies']['alpinejs']);
        $hasDevDependency = isset($packageJson['devDependencies']['alpinejs']);
        
        $this->assertFalse(
            $hasDependency || $hasDevDependency,
            'Alpine.js should not be in package.json. Livewire v4 bundles Alpine.js internally.'
        );
    }

    /**
     * Test that Turbo.js is not included to avoid conflicts with Livewire navigate.
     * 
     * Livewire v4 has its own SPA-like navigation via wire:navigate.
     * Having Turbo.js would conflict and cause issues.
     */
    public function test_turbo_not_included(): void
    {
        $packageJson = json_decode(file_get_contents(base_path('package.json')), true);
        
        $hasDependency = isset($packageJson['dependencies']['@hotwired/turbo']);
        $hasDevDependency = isset($packageJson['devDependencies']['@hotwired/turbo']);
        
        $this->assertFalse(
            $hasDependency || $hasDevDependency,
            'Turbo.js should not be in package.json. Livewire v4 has built-in wire:navigate for SPA-like navigation.'
        );
    }

    /**
     * Test that Livewire v4 specific config options are present.
     */
    public function test_livewire_v4_config_options_present(): void
    {
        // smart_wire_keys is a Livewire v4 feature
        $this->assertTrue(
            config('livewire.smart_wire_keys'),
            'Livewire v4 smart_wire_keys config should be enabled.'
        );
    }
}
