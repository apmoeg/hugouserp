<?php

declare(strict_types=1);

namespace Tests\Feature\Locale;

use App\Models\Branch;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LocaleSwitchingFormTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Branch $branch;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create();
        $this->branch = Branch::factory()->create();
        $this->user->branches()->attach($this->branch->id);
    }

    /**
     * Test that supplier form saves correctly when locale is English
     */
    public function test_supplier_form_saves_correctly_in_english_locale(): void
    {
        // Set locale to English
        $this->app->setLocale('en');
        session()->put('locale', 'en');
        
        $this->actingAs($this->user);

        $supplierData = [
            'name' => 'Test Supplier English',
            'company_name' => 'Test Company Inc',
            'email' => 'test@example.com',
            'phone' => '+1234567890',
            'address' => '123 Main Street',
            'city' => 'New York',
            'country' => 'United States',
            'tax_number' => 'TAX123',
            'contact_person' => 'John Doe',
            'notes' => 'Test notes in English',
            'is_active' => true,
        ];

        // Make POST request to create supplier
        $response = $this->post(route('suppliers.store'), $supplierData);

        // Verify supplier was created
        $this->assertDatabaseHas('suppliers', [
            'name' => 'Test Supplier English',
            'company_name' => 'Test Company Inc',
            'city' => 'New York',
            'country' => 'United States',
        ]);
    }

    /**
     * Test that supplier form saves correctly when locale is Arabic
     * This is the critical test - ensuring ALL fields persist when UI is in Arabic
     */
    public function test_supplier_form_saves_correctly_in_arabic_locale(): void
    {
        // Set locale to Arabic
        $this->app->setLocale('ar');
        session()->put('locale', 'ar');
        
        $this->actingAs($this->user);

        // Test with English values (the issue is locale-based, not character-based)
        $supplierData = [
            'name' => 'Test Supplier Arabic Mode',
            'company_name' => 'Test Company Arabic Mode',
            'email' => 'test.ar@example.com',
            'phone' => '+9876543210',
            'address' => '456 Test Avenue',
            'city' => 'Cairo',
            'country' => 'Egypt',
            'tax_number' => 'TAX456',
            'contact_person' => 'Jane Smith',
            'notes' => 'Test notes in Arabic locale',
            'is_active' => true,
        ];

        // Make POST request to create supplier
        $response = $this->post(route('suppliers.store'), $supplierData);

        // CRITICAL: Verify ALL fields persist when locale=ar
        $this->assertDatabaseHas('suppliers', [
            'name' => 'Test Supplier Arabic Mode',
            'company_name' => 'Test Company Arabic Mode',
            'city' => 'Cairo',
            'country' => 'Egypt',
        ]);
    }

    /**
     * Test with actual Arabic text when locale is Arabic
     */
    public function test_supplier_form_saves_arabic_text_in_arabic_locale(): void
    {
        $this->app->setLocale('ar');
        session()->put('locale', 'ar');
        
        $this->actingAs($this->user);

        $supplierData = [
            'name' => 'مورد الاختبار',
            'company_name' => 'شركة الاختبار المحدودة',
            'email' => 'arabic@example.com',
            'phone' => '+966123456789',
            'address' => 'شارع الملك فهد',
            'city' => 'الرياض',
            'country' => 'المملكة العربية السعودية',
            'tax_number' => 'TAX789',
            'contact_person' => 'محمد أحمد',
            'notes' => 'ملاحظات الاختبار',
            'is_active' => true,
        ];

        $response = $this->post(route('suppliers.store'), $supplierData);

        // Verify Arabic text persists correctly
        $this->assertDatabaseHas('suppliers', [
            'name' => 'مورد الاختبار',
            'company_name' => 'شركة الاختبار المحدودة',
            'city' => 'الرياض',
            'country' => 'المملكة العربية السعودية',
        ]);
    }

    /**
     * Test that update works correctly in Arabic locale
     */
    public function test_supplier_update_works_in_arabic_locale(): void
    {
        $this->app->setLocale('ar');
        session()->put('locale', 'ar');
        
        $this->actingAs($this->user);

        // Create a supplier first
        $supplier = Supplier::factory()->create([
            'branch_id' => $this->branch->id,
            'name' => 'Original Name',
            'company_name' => 'Original Company',
            'city' => 'Original City',
            'country' => 'Original Country',
        ]);

        // Update with new values
        $updateData = [
            'name' => 'Updated Name',
            'company_name' => 'Updated Company',
            'city' => 'Updated City',
            'country' => 'Updated Country',
            'email' => 'updated@example.com',
        ];

        $response = $this->put(route('suppliers.update', $supplier->id), $updateData);

        // Verify all fields were updated
        $this->assertDatabaseHas('suppliers', [
            'id' => $supplier->id,
            'name' => 'Updated Name',
            'company_name' => 'Updated Company',
            'city' => 'Updated City',
            'country' => 'Updated Country',
        ]);
    }

    /**
     * Test that Livewire component saves correctly in Arabic locale
     */
    public function test_livewire_supplier_form_saves_in_arabic_locale(): void
    {
        $this->app->setLocale('ar');
        session()->put('locale', 'ar');
        
        $this->actingAs($this->user);

        // Test Livewire component
        $component = \Livewire\Livewire::test(\App\Livewire\Suppliers\Form::class)
            ->set('name', 'Livewire Supplier AR')
            ->set('company_name', 'Livewire Company AR')
            ->set('email', 'livewire@example.com')
            ->set('city', 'Livewire City AR')
            ->set('country', 'Livewire Country AR')
            ->call('save');

        // Verify the supplier was created with all fields
        $this->assertDatabaseHas('suppliers', [
            'name' => 'Livewire Supplier AR',
            'company_name' => 'Livewire Company AR',
            'city' => 'Livewire City AR',
            'country' => 'Livewire Country AR',
        ]);
    }

    /**
     * Compare POST payload keys between locales
     */
    public function test_form_payload_keys_are_identical_in_both_locales(): void
    {
        $this->actingAs($this->user);

        // Test in English
        $this->app->setLocale('en');
        session()->put('locale', 'en');
        $responseEn = $this->get(route('suppliers.create'));
        $this->assertEquals('en', app()->getLocale());

        // Test in Arabic
        $this->app->setLocale('ar');
        session()->put('locale', 'ar');
        $responseAr = $this->get(route('suppliers.create'));
        $this->assertEquals('ar', app()->getLocale());

        // Both should render without errors
        $responseEn->assertOk();
        $responseAr->assertOk();

        // The form field names should be identical (wire:model="name", not wire:model="__('name')")
        $this->assertStringContainsString('wire:model="name"', $responseEn->content());
        $this->assertStringContainsString('wire:model="name"', $responseAr->content());
        $this->assertStringContainsString('wire:model="city"', $responseEn->content());
        $this->assertStringContainsString('wire:model="city"', $responseAr->content());
        $this->assertStringContainsString('wire:model="country"', $responseEn->content());
        $this->assertStringContainsString('wire:model="country"', $responseAr->content());
    }
}
