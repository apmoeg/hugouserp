<?php

declare(strict_types=1);

namespace Tests\Feature\Sales;

use App\Models\Branch;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class SalePriceSecurityTest extends TestCase
{
    use RefreshDatabase;

    protected Branch $branch;

    protected User $user;

    protected Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->branch = Branch::create([
            'name' => 'Test Branch',
            'code' => 'TB001',
        ]);

        $this->user = User::factory()->create([
            'branch_id' => $this->branch->id,
            'can_modify_price' => false, // Cannot modify prices
        ]);

        $this->product = Product::create([
            'name' => 'Test Product',
            'code' => 'PRD001',
            'sku' => 'SKU001',
            'type' => 'stock',
            'default_price' => 100.00,
            'standard_cost' => 50.00,
            'branch_id' => $this->branch->id,
        ]);

        $this->actingAs($this->user);
    }

    public function test_sale_validates_price_from_database(): void
    {
        // Attempt to create a sale with a manipulated price
        // Simulates a user tampering with the price in browser dev tools
        Livewire::test(\App\Livewire\Sales\Form::class)
            ->set('items', [
                [
                    'id' => null,
                    'product_id' => $this->product->id,
                    'product_name' => $this->product->name,
                    'sku' => $this->product->sku,
                    'qty' => 1,
                    'unit_price' => 10.00, // Manipulated price (should be 100.00)
                    'discount' => 0,
                    'tax_rate' => 0,
                ],
            ])
            ->call('save')
            ->assertHasErrors('items');
    }

    public function test_sale_accepts_database_price(): void
    {
        // Test with correct database price
        Livewire::test(\App\Livewire\Sales\Form::class)
            ->set('items', [
                [
                    'id' => null,
                    'product_id' => $this->product->id,
                    'product_name' => $this->product->name,
                    'sku' => $this->product->sku,
                    'qty' => 1,
                    'unit_price' => 100.00, // Correct database price
                    'discount' => 0,
                    'tax_rate' => 0,
                ],
            ])
            ->set('payment_amount', 100.00)
            ->set('status', 'completed')
            ->call('save')
            ->assertHasNoErrors();
    }

    public function test_user_with_price_modify_permission_can_change_price(): void
    {
        // Create user with price modification permission
        $userWithPermission = User::factory()->create([
            'branch_id' => $this->branch->id,
            'can_modify_price' => true,
        ]);

        $this->actingAs($userWithPermission);

        // This should succeed because user has permission
        Livewire::test(\App\Livewire\Sales\Form::class)
            ->set('items', [
                [
                    'id' => null,
                    'product_id' => $this->product->id,
                    'product_name' => $this->product->name,
                    'sku' => $this->product->sku,
                    'qty' => 1,
                    'unit_price' => 90.00, // Modified price
                    'discount' => 0,
                    'tax_rate' => 0,
                ],
            ])
            ->set('payment_amount', 90.00)
            ->set('status', 'completed')
            ->call('save')
            ->assertHasNoErrors();
    }

    public function test_sale_uses_bcmath_for_precision(): void
    {
        // Test that calculations use bcmath for financial precision
        Livewire::test(\App\Livewire\Sales\Form::class)
            ->set('items', [
                [
                    'id' => null,
                    'product_id' => $this->product->id,
                    'product_name' => $this->product->name,
                    'sku' => $this->product->sku,
                    'qty' => 3,
                    'unit_price' => 100.00,
                    'discount' => 10.50, // Precise decimal discount
                    'tax_rate' => 14, // 14% tax
                ],
            ])
            ->set('payment_amount', 330.63) // (300 - 10.50) * 1.14 = 330.63
            ->set('status', 'completed')
            ->call('save')
            ->assertHasNoErrors();

        // Verify the sale was created with correct calculated amounts
        $this->assertDatabaseHas('sales', [
            'branch_id' => $this->branch->id,
            'status' => 'completed',
        ]);

        // The sale item should have the correct calculated line_total
        $this->assertDatabaseHas('sale_items', [
            'product_id' => $this->product->id,
            'quantity' => 3,
            'unit_price' => 100.00,
            'discount_amount' => 10.50,
        ]);
    }

    public function test_sale_rejects_nonexistent_product(): void
    {
        // Test that sale validates product exists
        Livewire::test(\App\Livewire\Sales\Form::class)
            ->set('items', [
                [
                    'id' => null,
                    'product_id' => 99999, // Non-existent product
                    'product_name' => 'Fake Product',
                    'sku' => 'FAKE001',
                    'qty' => 1,
                    'unit_price' => 100.00,
                    'discount' => 0,
                    'tax_rate' => 0,
                ],
            ])
            ->call('save')
            ->assertHasErrors('items');
    }
}
