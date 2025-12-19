<?php

declare(strict_types=1);

namespace Tests\Feature\Inventory;

use App\Livewire\Inventory\Products\Index;
use App\Models\Branch;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;
use Tests\TestCase;

class ProductExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_export_uses_valid_columns(): void
    {
        Gate::define('inventory.products.view', fn () => true);

        $branch = Branch::factory()->create();
        $user = User::factory()->create(['branch_id' => $branch->id]);

        Product::create([
            'name' => 'Exportable Product',
            'sku' => 'EXP-001',
            'default_price' => 50,
            'branch_id' => $branch->id,
        ]);

        Livewire::actingAs($user)
            ->test(Index::class)
            ->set('selectedExportColumns', ['products.id', 'products.name'])
            ->set('exportFormat', 'csv')
            ->call('export')
            ->assertSessionHas('export_file');
    }
}
