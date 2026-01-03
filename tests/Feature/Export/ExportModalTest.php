<?php

declare(strict_types=1);

namespace Tests\Feature\Export;

use App\Livewire\Customers\Index as CustomersIndex;
use App\Livewire\Expenses\Index as ExpensesIndex;
use App\Livewire\Income\Index as IncomeIndex;
use App\Livewire\Inventory\Products\Index as ProductsIndex;
use App\Livewire\Purchases\Index as PurchasesIndex;
use App\Livewire\Sales\Index as SalesIndex;
use App\Livewire\Suppliers\Index as SuppliersIndex;
use App\Models\Branch;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class ExportModalTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        
        // Create necessary permissions for all pages
        Permission::findOrCreate('customers.view', 'web');
        Permission::findOrCreate('customers.manage.all', 'web');
        Permission::findOrCreate('expenses.view', 'web');
        Permission::findOrCreate('income.view', 'web');
        Permission::findOrCreate('inventory.products.view', 'web');
        Permission::findOrCreate('purchases.view', 'web');
        Permission::findOrCreate('sales.view', 'web');
        Permission::findOrCreate('suppliers.view', 'web');
    }

    /**
     * Test that the export modal opens correctly on the Customers page
     */
    public function test_export_modal_opens_on_customers_page(): void
    {
        Gate::define('customers.view', fn () => true);
        
        $branch = Branch::factory()->create();
        $user = User::factory()->create(['branch_id' => $branch->id]);
        $user->givePermissionTo('customers.view');

        Livewire::actingAs($user)
            ->test(CustomersIndex::class)
            ->assertSet('showExportModal', false)
            ->call('openExportModal')
            ->assertSet('showExportModal', true)
            ->assertSet('exportColumns', function ($columns) {
                return is_array($columns) && !empty($columns);
            });
    }

    /**
     * Test that the export modal opens correctly on the Expenses page
     */
    public function test_export_modal_opens_on_expenses_page(): void
    {
        Gate::define('expenses.view', fn () => true);
        
        $branch = Branch::factory()->create();
        $user = User::factory()->create(['branch_id' => $branch->id]);
        $user->givePermissionTo('expenses.view');

        Livewire::actingAs($user)
            ->test(ExpensesIndex::class)
            ->assertSet('showExportModal', false)
            ->call('openExportModal')
            ->assertSet('showExportModal', true)
            ->assertSet('exportColumns', function ($columns) {
                return is_array($columns) && !empty($columns);
            });
    }

    /**
     * Test that the export modal opens correctly on the Income page
     */
    public function test_export_modal_opens_on_income_page(): void
    {
        Gate::define('income.view', fn () => true);
        
        $branch = Branch::factory()->create();
        $user = User::factory()->create(['branch_id' => $branch->id]);
        $user->givePermissionTo('income.view');

        Livewire::actingAs($user)
            ->test(IncomeIndex::class)
            ->assertSet('showExportModal', false)
            ->call('openExportModal')
            ->assertSet('showExportModal', true)
            ->assertSet('exportColumns', function ($columns) {
                return is_array($columns) && !empty($columns);
            });
    }

    /**
     * Test that the export modal opens correctly on the Products page
     */
    public function test_export_modal_opens_on_products_page(): void
    {
        Gate::define('inventory.products.view', fn () => true);
        
        $branch = Branch::factory()->create();
        $user = User::factory()->create(['branch_id' => $branch->id]);
        $user->givePermissionTo('inventory.products.view');

        Livewire::actingAs($user)
            ->test(ProductsIndex::class)
            ->assertSet('showExportModal', false)
            ->call('openExportModal')
            ->assertSet('showExportModal', true)
            ->assertSet('exportColumns', function ($columns) {
                return is_array($columns) && !empty($columns);
            });
    }

    /**
     * Test that the export modal opens correctly on the Purchases page
     */
    public function test_export_modal_opens_on_purchases_page(): void
    {
        Gate::define('purchases.view', fn () => true);
        
        $branch = Branch::factory()->create();
        $user = User::factory()->create(['branch_id' => $branch->id]);
        $user->givePermissionTo('purchases.view');

        Livewire::actingAs($user)
            ->test(PurchasesIndex::class)
            ->assertSet('showExportModal', false)
            ->call('openExportModal')
            ->assertSet('showExportModal', true)
            ->assertSet('exportColumns', function ($columns) {
                return is_array($columns) && !empty($columns);
            });
    }

    /**
     * Test that the export modal opens correctly on the Sales page
     */
    public function test_export_modal_opens_on_sales_page(): void
    {
        Gate::define('sales.view', fn () => true);
        
        $branch = Branch::factory()->create();
        $user = User::factory()->create(['branch_id' => $branch->id]);
        $user->givePermissionTo('sales.view');

        Livewire::actingAs($user)
            ->test(SalesIndex::class)
            ->assertSet('showExportModal', false)
            ->call('openExportModal')
            ->assertSet('showExportModal', true)
            ->assertSet('exportColumns', function ($columns) {
                return is_array($columns) && !empty($columns);
            });
    }

    /**
     * Test that the export modal opens correctly on the Suppliers page
     */
    public function test_export_modal_opens_on_suppliers_page(): void
    {
        Gate::define('suppliers.view', fn () => true);
        
        $branch = Branch::factory()->create();
        $user = User::factory()->create(['branch_id' => $branch->id]);
        $user->givePermissionTo('suppliers.view');

        Livewire::actingAs($user)
            ->test(SuppliersIndex::class)
            ->assertSet('showExportModal', false)
            ->call('openExportModal')
            ->assertSet('showExportModal', true)
            ->assertSet('exportColumns', function ($columns) {
                return is_array($columns) && !empty($columns);
            });
    }

    /**
     * Test that the export modal can be closed
     */
    public function test_export_modal_can_be_closed(): void
    {
        Gate::define('customers.view', fn () => true);
        
        $branch = Branch::factory()->create();
        $user = User::factory()->create(['branch_id' => $branch->id]);
        $user->givePermissionTo('customers.view');

        Livewire::actingAs($user)
            ->test(CustomersIndex::class)
            ->call('openExportModal')
            ->assertSet('showExportModal', true)
            ->call('closeExportModal')
            ->assertSet('showExportModal', false);
    }

    /**
     * Test that export columns are initialized correctly
     */
    public function test_export_columns_are_initialized_on_mount(): void
    {
        Gate::define('sales.view', fn () => true);
        
        $branch = Branch::factory()->create();
        $user = User::factory()->create(['branch_id' => $branch->id]);
        $user->givePermissionTo('sales.view');

        Livewire::actingAs($user)
            ->test(SalesIndex::class)
            ->assertSet('exportColumns', function ($columns) {
                // Should have sales-specific columns
                return is_array($columns) 
                    && isset($columns['reference'])
                    && isset($columns['customer_name'])
                    && isset($columns['grand_total']);
            })
            ->assertSet('selectedExportColumns', function ($selected) {
                // All columns should be selected by default
                return is_array($selected) && !empty($selected);
            });
    }
}
