<?php

declare(strict_types=1);

namespace Tests\Feature\Security;

use App\Models\Branch;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BranchContextIsolationTest extends TestCase
{
    use RefreshDatabase;

    protected User $cairoUser;

    protected User $alexUser;

    protected Branch $cairoBranch;

    protected Branch $alexBranch;

    protected Product $cairoProduct;

    protected Product $alexProduct;

    protected function setUp(): void
    {
        parent::setUp();

        // Create Cairo branch and user
        $this->cairoBranch = Branch::factory()->create([
            'name' => 'Cairo Branch',
            'code' => 'CAI',
            'is_active' => true,
        ]);

        $this->cairoUser = User::factory()->create([
            'name' => 'Cairo Manager',
            'email' => 'cairo@example.com',
            'password' => bcrypt('password'),
            'branch_id' => $this->cairoBranch->id,
        ]);

        $this->cairoUser->branches()->attach($this->cairoBranch->id);

        // Create Alexandria branch and user
        $this->alexBranch = Branch::factory()->create([
            'name' => 'Alexandria Branch',
            'code' => 'ALX',
            'is_active' => true,
        ]);

        $this->alexUser = User::factory()->create([
            'name' => 'Alexandria Manager',
            'email' => 'alex@example.com',
            'password' => bcrypt('password'),
            'branch_id' => $this->alexBranch->id,
        ]);

        $this->alexUser->branches()->attach($this->alexBranch->id);

        // Create products for each branch
        $this->cairoProduct = Product::create([
            'name' => 'Cairo Product',
            'code' => 'CAI-PRD001',
            'sku' => 'CAI-SKU001',
            'type' => 'stock',
            'default_price' => 100,
            'cost' => 50,
            'branch_id' => $this->cairoBranch->id,
        ]);

        $this->alexProduct = Product::create([
            'name' => 'Alexandria Product',
            'code' => 'ALX-PRD001',
            'sku' => 'ALX-SKU001',
            'type' => 'stock',
            'default_price' => 100,
            'cost' => 50,
            'branch_id' => $this->alexBranch->id,
        ]);
    }

    public function test_user_branch_context_is_set_on_authentication(): void
    {
        $this->actingAs($this->cairoUser);

        $request = request();
        $request->setUserResolver(fn () => $this->cairoUser);

        // After middleware runs, branch context should be set
        $middleware = new \App\Http\Middleware\SetUserBranchContext();
        $middleware->handle($request, fn ($req) => response('OK'));

        $this->assertEquals($this->cairoBranch->id, $request->attributes->get('branch_id'));
        $this->assertEquals($this->cairoBranch->id, app('req.branch_id'));
    }

    public function test_products_are_scoped_to_user_branch(): void
    {
        $this->actingAs($this->cairoUser);

        // Cairo user should only see Cairo products
        $products = Product::forCurrentBranch($this->cairoUser)->get();

        $this->assertCount(1, $products);
        $this->assertEquals($this->cairoProduct->id, $products->first()->id);
        $this->assertNotContains($this->alexProduct->id, $products->pluck('id'));
    }

    public function test_alex_user_cannot_access_cairo_products(): void
    {
        $this->actingAs($this->alexUser);

        // Alexandria user should only see Alexandria products
        $products = Product::forCurrentBranch($this->alexUser)->get();

        $this->assertCount(1, $products);
        $this->assertEquals($this->alexProduct->id, $products->first()->id);
        $this->assertNotContains($this->cairoProduct->id, $products->pluck('id'));
    }

    public function test_branch_context_middleware_sets_context_for_web_routes(): void
    {
        $this->actingAs($this->cairoUser);

        // Simulate a web request
        $response = $this->get('/');

        // Branch context should be set in the request
        $this->assertEquals($this->cairoBranch->id, request()->attributes->get('branch_id'));
    }

    public function test_super_admin_can_access_all_branches(): void
    {
        $this->markTestSkipped('Requires Spatie permissions setup');

        // This test would verify that super admins can access all branches
        // Requires setting up roles and permissions which is beyond scope
    }
}
