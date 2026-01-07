<?php

declare(strict_types=1);

namespace Tests\Feature;

use Tests\TestCase;

class TranslationCompletenessTest extends TestCase
{
    /**
     * Minimum required translation coverage percentage.
     */
    private const MIN_COVERAGE_PERCENT = 85.0;

    /**
     * Cached translation arrays.
     */
    private ?array $enJson = null;

    private ?array $arJson = null;

    /**
     * Get English translations array.
     */
    private function getEnglishTranslations(): array
    {
        if ($this->enJson === null) {
            $this->enJson = json_decode(file_get_contents(lang_path('en.json')), true);
        }

        return $this->enJson;
    }

    /**
     * Get Arabic translations array.
     */
    private function getArabicTranslations(): array
    {
        if ($this->arJson === null) {
            $this->arJson = json_decode(file_get_contents(lang_path('ar.json')), true);
        }

        return $this->arJson;
    }

    /**
     * Test that all translation keys used in the app exist in both English and Arabic.
     */
    public function test_all_translation_keys_exist_in_both_languages(): void
    {
        $enJson = $this->getEnglishTranslations();
        $arJson = $this->getArabicTranslations();

        $this->assertIsArray($enJson, 'English JSON translations should be valid');
        $this->assertIsArray($arJson, 'Arabic JSON translations should be valid');

        $enKeys = array_keys($enJson);
        $arKeys = array_keys($arJson);

        $missingInArabic = array_diff($enKeys, $arKeys);
        $missingInEnglish = array_diff($arKeys, $enKeys);

        $this->assertEmpty(
            $missingInArabic,
            'Missing translations in Arabic: '.implode(', ', array_slice($missingInArabic, 0, 10))
        );

        $this->assertEmpty(
            $missingInEnglish,
            'Missing translations in English: '.implode(', ', array_slice($missingInEnglish, 0, 10))
        );
    }

    /**
     * Test that Arabic translations are not empty or same as English.
     */
    public function test_arabic_translations_are_properly_translated(): void
    {
        $enJson = $this->getEnglishTranslations();
        $arJson = $this->getArabicTranslations();

        $untranslated = [];

        foreach ($enJson as $key => $enValue) {
            if (isset($arJson[$key])) {
                // Check if Arabic translation is empty or exactly same as English
                if (empty($arJson[$key]) || $arJson[$key] === $enValue) {
                    // Allow some technical terms to be the same
                    if (! $this->isTechnicalTerm($key)) {
                        $untranslated[] = $key;
                    }
                }
            }
        }

        // Calculate coverage
        $totalKeys = count($enJson);
        $untranslatedCount = count($untranslated);
        $coverage = ($totalKeys - $untranslatedCount) / $totalKeys * 100;

        // Require minimum coverage (allowing for technical strings and code snippets)
        $this->assertGreaterThanOrEqual(
            self::MIN_COVERAGE_PERCENT,
            $coverage,
            sprintf(
                'Arabic translation coverage is %.1f%% (below %.1f%%). Untranslated: %s',
                $coverage,
                self::MIN_COVERAGE_PERCENT,
                implode(', ', array_slice($untranslated, 0, 10))
            )
        );
    }

    /**
     * Test that sidebar labels are all translatable.
     */
    public function test_sidebar_labels_are_translatable(): void
    {
        $sidebarFile = resource_path('views/components/sidebar/main.blade.php');
        $this->assertFileExists($sidebarFile);

        $content = file_get_contents($sidebarFile);

        // Check that all label attributes use translation
        preg_match_all('/label="([^"]+)"/', $content, $matches);

        $enJson = $this->getEnglishTranslations();
        $arJson = $this->getArabicTranslations();

        $missingLabels = [];

        foreach ($matches[1] as $label) {
            if (! isset($enJson[$label]) || ! isset($arJson[$label])) {
                $missingLabels[] = $label;
            }
        }

        $this->assertEmpty(
            $missingLabels,
            'Sidebar labels missing translations: '.implode(', ', $missingLabels)
        );
    }

    /**
     * Test that section headers in sidebar are translatable.
     */
    public function test_sidebar_section_headers_are_translatable(): void
    {
        $sidebarFile = resource_path('views/components/sidebar/main.blade.php');
        $this->assertFileExists($sidebarFile);

        $content = file_get_contents($sidebarFile);

        // Extract section headers
        preg_match_all("/__\('([^']+)'\)/", $content, $matches);

        $enJson = $this->getEnglishTranslations();
        $arJson = $this->getArabicTranslations();

        $missingHeaders = [];

        foreach ($matches[1] as $header) {
            if (! isset($enJson[$header]) || ! isset($arJson[$header])) {
                $missingHeaders[] = $header;
            }
        }

        $this->assertEmpty(
            $missingHeaders,
            'Section headers missing translations: '.implode(', ', $missingHeaders)
        );
    }

    /**
     * Test that common UI strings exist in translations.
     */
    public function test_common_ui_strings_exist(): void
    {
        $commonStrings = [
            'Save', 'Cancel', 'Delete', 'Edit', 'Create', 'Search',
            'Actions', 'Status', 'Active', 'Inactive', 'Dashboard',
            'Settings', 'Reports', 'Users', 'Yes', 'No',
        ];

        $enJson = $this->getEnglishTranslations();
        $arJson = $this->getArabicTranslations();

        $missing = [];

        foreach ($commonStrings as $string) {
            if (! isset($enJson[$string]) || ! isset($arJson[$string])) {
                $missing[] = $string;
            }
        }

        $this->assertEmpty(
            $missing,
            'Common UI strings missing: '.implode(', ', $missing)
        );
    }

    /**
     * Smoke test: Check that Arabic UI doesn't contain common English UI tokens.
     * This helps prevent regressions where English strings leak into Arabic locale.
     */
    public function test_arabic_locale_smoke_test(): void
    {
        $arJson = $this->getArabicTranslations();

        // Common English UI tokens that should NOT appear in Arabic translations
        // (except as part of technical terms or placeholders)
        $englishTokens = [
            'WORKSPACE', 'SALES & PURCHASES', 'Business Modules',
        ];

        $violations = [];

        foreach ($englishTokens as $token) {
            // Check if this exact English token exists as a value in Arabic translations
            // where it shouldn't (i.e., the Arabic value equals the English token)
            if (isset($arJson[$token]) && $arJson[$token] === $token) {
                $violations[] = $token;
            }
        }

        $this->assertEmpty(
            $violations,
            'Arabic translations contain untranslated English UI tokens: '.implode(', ', $violations)
        );
    }

    /**
     * Smoke test: Check that English UI doesn't contain Arabic text.
     * This helps ensure proper locale separation.
     */
    public function test_english_locale_smoke_test(): void
    {
        $enJson = $this->getEnglishTranslations();

        $arabicPattern = '/[\x{0600}-\x{06FF}]/u'; // Arabic Unicode range
        $violations = [];

        foreach ($enJson as $key => $value) {
            if (is_string($value) && preg_match($arabicPattern, $value)) {
                $violations[] = $key;
            }
        }

        $this->assertEmpty(
            $violations,
            'English translations contain Arabic text: '.implode(', ', array_slice($violations, 0, 10))
        );
    }

    /**
     * Test that critical UI elements mentioned in issue have translations.
     * These are specific strings that were reported as showing in wrong language.
     */
    public function test_critical_ui_elements_are_translated(): void
    {
        $enJson = $this->getEnglishTranslations();
        $arJson = $this->getArabicTranslations();

        // Critical strings mentioned in the issue that must be translated
        $criticalStrings = [
            'Position / title',
            'Linked user (optional)',
            'Not linked',
            'Multi-Level BOM',
            'POS Daily Report',
            'New Return',
            'Manage product returns and refunds',
            'Business Modules',
            'Contacts',
            'Operations',
            'Administration',
            'Select...',
            'No data found',
            'No results found',
        ];

        $missing = [];
        $untranslated = [];

        foreach ($criticalStrings as $string) {
            if (! isset($enJson[$string])) {
                $missing[] = "$string (missing in EN)";
            }
            if (! isset($arJson[$string])) {
                $missing[] = "$string (missing in AR)";
            }
            // Check if Arabic translation is different from English (i.e., actually translated)
            if (isset($enJson[$string]) && isset($arJson[$string]) && $arJson[$string] === $enJson[$string]) {
                $untranslated[] = $string;
            }
        }

        $this->assertEmpty(
            $missing,
            'Critical UI strings missing: '.implode(', ', $missing)
        );

        $this->assertEmpty(
            $untranslated,
            'Critical UI strings not translated to Arabic: '.implode(', ', $untranslated)
        );
    }

    /**
     * Test that dropdown/select related strings are translated.
     * Dropdowns are a common source of mixed-language UI.
     */
    public function test_dropdown_strings_are_translated(): void
    {
        $enJson = $this->getEnglishTranslations();
        $arJson = $this->getArabicTranslations();

        // Common dropdown-related strings
        $dropdownStrings = [
            'Select...',
            'Please select',
            'Select Option',
            'Select All',
            'Select One',
            'None',
            'All',
            'Optional',
            'Choose',
        ];

        foreach ($dropdownStrings as $string) {
            if (isset($enJson[$string]) && isset($arJson[$string])) {
                $this->assertNotEquals(
                    $enJson[$string],
                    $arJson[$string],
                    "Dropdown string '$string' should be translated to Arabic"
                );
            }
        }
    }

    /**
     * Test that form-related strings are translated.
     */
    public function test_form_strings_are_translated(): void
    {
        $enJson = $this->getEnglishTranslations();
        $arJson = $this->getArabicTranslations();

        // Common form-related strings
        $formStrings = [
            'Save',
            'Cancel',
            'Submit',
            'Reset',
            'Required',
            'Optional',
            'Name',
            'Email',
            'Phone',
            'Address',
            'Description',
            'Notes',
        ];

        $untranslated = [];

        foreach ($formStrings as $string) {
            if (isset($enJson[$string]) && isset($arJson[$string])) {
                if ($arJson[$string] === $enJson[$string]) {
                    $untranslated[] = $string;
                }
            }
        }

        $this->assertEmpty(
            $untranslated,
            'Form strings not translated to Arabic: '.implode(', ', $untranslated)
        );
    }

    /**
     * Test that button/action strings are translated.
     */
    public function test_button_strings_are_translated(): void
    {
        $enJson = $this->getEnglishTranslations();
        $arJson = $this->getArabicTranslations();

        // Common button/action strings
        $buttonStrings = [
            'Add',
            'Edit',
            'Delete',
            'Create',
            'Update',
            'Save',
            'Cancel',
            'Close',
            'Confirm',
            'Apply',
            'Clear',
            'Search',
            'Filter',
            'Export',
            'Import',
            'Print',
            'Download',
            'Upload',
            'Refresh',
            'Back',
            'Next',
            'Previous',
            'Submit',
            'View',
            'New',
        ];

        $untranslated = [];

        foreach ($buttonStrings as $string) {
            if (isset($enJson[$string]) && isset($arJson[$string])) {
                if ($arJson[$string] === $enJson[$string]) {
                    $untranslated[] = $string;
                }
            }
        }

        $this->assertEmpty(
            $untranslated,
            'Button strings not translated to Arabic: '.implode(', ', $untranslated)
        );
    }

    /**
     * Check if a key represents a technical term that can be untranslated.
     */
    private function isTechnicalTerm(string $key): bool
    {
        // Extended list of technical terms that are acceptable to remain in English
        $technicalTerms = [
            // Technical acronyms
            'ERP', 'API', 'SMS', 'POS', 'SKU', 'N/A', 'OK', 'URL', 'HTTP', 'HTTPS',
            'CSS', 'HTML', 'JSON', 'XML', 'PDF', 'CSV', 'ID', 'UUID', 'URI', 'FTP',
            'VAPID', 'reCAPTCHA', 'ISO', 'VIP', 'GRN', 'FEFO', 'BOM', 'HRM', 'TTL',
            'VAT', 'SLA', 'CRUD', 'JWT', '2FA', 'QR',
            // Brand names and product names that should stay in English
            'Laravel', 'Sanctum', 'Shopify', 'WooCommerce', 'Amazon', 'S3',
            'Firebase', 'Turbo', 'WordPress', 'Livewire', 'Alpine',
            // Technical prefixes/patterns
            'validation.', 'permission.', 'permission_group.', 'role.', 'notifications.',
            'e.g.', 'i.e.',
            // Vehicle types (commonly kept in English)
            'SUV', 'Sedan',
            // Technical patterns (code snippets, examples, placeholders)
            ':', '{', '}', '(', ')', '[', ']', '->', '=>', '//', '/*',
            'example.com', '@example', 'email@', 'http://', 'https://',
            // Technical configuration strings
            'Cron', '* * *', 'env', 'config', 'cache',
            // Common technical phrases
            'Found :count', 'Showing :from', 'For example',
            // File extensions and paths
            '.php', '.json', '.csv', '.xlsx', '.pdf', '.doc',
        ];

        foreach ($technicalTerms as $term) {
            if (str_contains($key, $term)) {
                return true;
            }
        }

        // Also allow very short strings (1-2 chars) and pure numbers
        if (strlen($key) <= 2 || is_numeric($key)) {
            return true;
        }

        // Allow strings that look like code examples or technical references
        // Note: Hyphen at end of character class to avoid range interpretation
        if (preg_match('/^[A-Z0-9_.\/-]+$/', $key)) {
            return true;
        }

        // Allow strings that contain email-like patterns
        if (str_contains($key, '@') && str_contains($key, '.')) {
            return true;
        }

        return false;
    }

    /**
     * Test that all critical UI sections have proper translations.
     * This is a comprehensive test covering all major areas of the application.
     */
    public function test_comprehensive_ui_section_translations(): void
    {
        $enJson = $this->getEnglishTranslations();
        $arJson = $this->getArabicTranslations();

        // Categories of critical UI strings organized by section
        $criticalSections = [
            'Sidebar Navigation' => [
                'Workspace', 'Sales & Purchases', 'Inventory & Warehouse',
                'Finance & Banking', 'People & HR', 'Operations', 'Administration',
                'Dashboard', 'POS Terminal', 'Reports Hub', 'Sales', 'Purchases',
                'Customers', 'Suppliers', 'Products', 'Warehouse', 'Accounting',
                'Expenses', 'Income', 'Banking', 'Human Resources', 'Rental',
                'Manufacturing', 'Fixed Assets', 'Projects', 'Documents', 'Helpdesk',
                'Settings', 'Users', 'Roles', 'Branches', 'Modules', 'Audit Logs',
            ],
            'Form Labels' => [
                'Name', 'Email', 'Phone', 'Address', 'City', 'Country',
                'Description', 'Notes', 'Status', 'Type', 'Code', 'Date',
                'Amount', 'Quantity', 'Price', 'Total', 'Branch', 'Category',
                'Position / title', 'Linked user (optional)', 'Base salary',
            ],
            'Buttons & Actions' => [
                'Save', 'Cancel', 'Delete', 'Edit', 'Create', 'Update',
                'Add', 'Remove', 'Close', 'Confirm', 'Apply', 'Clear',
                'Search', 'Filter', 'Export', 'Import', 'Print', 'Download',
                'Upload', 'Refresh', 'Back', 'Next', 'Previous', 'Submit',
                'View', 'New Return', 'Process Return',
            ],
            'Status Labels' => [
                'Active', 'Inactive', 'Pending', 'Completed', 'Approved',
                'Rejected', 'Draft', 'Open', 'Closed', 'Cancelled',
                'In Progress', 'On Hold', 'Not linked',
            ],
            'Table Headers' => [
                'ID', 'Actions', 'Created At', 'Updated At', 'Date',
                'Customer', 'Supplier', 'Product', 'Warehouse', 'Total',
                'Status', 'Cashier', 'Code', 'Invoice', 'Reason',
            ],
            'Dropdown Defaults' => [
                'Select...', 'All', 'None', 'Optional',
                'All Branches', 'All Categories', 'All Status',
            ],
            'Empty States' => [
                'No data found', 'No results found', 'No returns found',
                'No sales found for this date',
            ],
            'Reports & Analytics' => [
                'Total Sales', 'Total Purchases', 'Total Revenue', 'Total Expenses',
                'Daily Report', 'POS Daily Report', 'Sales Analytics',
                'Payment Methods Breakdown', 'Sessions',
            ],
        ];

        $missing = [];
        $untranslated = [];

        foreach ($criticalSections as $section => $strings) {
            foreach ($strings as $string) {
                if (! isset($enJson[$string])) {
                    $missing[] = "[$section] '$string' missing in EN";
                } elseif (! isset($arJson[$string])) {
                    $missing[] = "[$section] '$string' missing in AR";
                } elseif ($arJson[$string] === $enJson[$string] && ! $this->isTechnicalTerm($string)) {
                    $untranslated[] = "[$section] '$string' not translated to Arabic";
                }
            }
        }

        $this->assertEmpty(
            $missing,
            "Critical UI strings missing:\n".implode("\n", $missing)
        );

        // Allow a small percentage of untranslated strings for technical terms
        $this->assertLessThan(
            5,
            count($untranslated),
            "Too many untranslated critical UI strings:\n".implode("\n", $untranslated)
        );
    }

    /**
     * Test that validation attribute names are properly translated.
     * These appear in validation error messages.
     */
    public function test_validation_attributes_are_translated(): void
    {
        $enJson = $this->getEnglishTranslations();
        $arJson = $this->getArabicTranslations();

        // Common validation attribute names
        $attributes = [
            'name', 'email', 'phone', 'password', 'address', 'city',
            'country', 'description', 'notes', 'amount', 'quantity',
            'price', 'date', 'status', 'type', 'code',
        ];

        // These may appear as form labels or in validation messages
        $missing = [];
        foreach ($attributes as $attr) {
            $ucfirst = ucfirst($attr);
            if (isset($enJson[$ucfirst]) && isset($arJson[$ucfirst])) {
                if ($arJson[$ucfirst] === $enJson[$ucfirst]) {
                    $missing[] = $ucfirst;
                }
            }
        }

        $this->assertEmpty(
            $missing,
            'Validation attributes not translated: '.implode(', ', $missing)
        );
    }

    /**
     * Test that modal/dialog strings are translated.
     */
    public function test_modal_dialog_strings_are_translated(): void
    {
        $enJson = $this->getEnglishTranslations();
        $arJson = $this->getArabicTranslations();

        // Common modal/dialog strings
        $modalStrings = [
            'Confirm', 'Cancel', 'Yes', 'No', 'OK', 'Close',
            'Are you sure?', 'Confirm Delete', 'Warning',
            'Success', 'Error', 'Info', 'Loading...',
        ];

        $untranslated = [];
        foreach ($modalStrings as $string) {
            if (isset($enJson[$string]) && isset($arJson[$string])) {
                if ($arJson[$string] === $enJson[$string] && ! $this->isTechnicalTerm($string)) {
                    $untranslated[] = $string;
                }
            }
        }

        $this->assertEmpty(
            $untranslated,
            'Modal/dialog strings not translated: '.implode(', ', $untranslated)
        );
    }

    /**
     * Test that the sidebar-new.blade.php file uses translation functions.
     */
    public function test_sidebar_new_uses_translation_functions(): void
    {
        $sidebarFile = resource_path('views/layouts/sidebar-new.blade.php');
        if (! file_exists($sidebarFile)) {
            $this->markTestSkipped('sidebar-new.blade.php not found');
        }

        $content = file_get_contents($sidebarFile);

        // Check that section titles use __() function
        preg_match_all("/'title'\s*=>\s*__\('([^']+)'\)/", $content, $titleMatches);
        $this->assertGreaterThan(0, count($titleMatches[1]), 'Sidebar section titles should use __() function');

        // Check that menu item labels use __() function
        preg_match_all("/'label'\s*=>\s*__\('([^']+)'\)/", $content, $labelMatches);
        $this->assertGreaterThan(0, count($labelMatches[1]), 'Sidebar menu labels should use __() function');

        // Verify all found labels exist in translations
        $enJson = $this->getEnglishTranslations();
        $arJson = $this->getArabicTranslations();
        $missing = [];

        foreach (array_merge($titleMatches[1], $labelMatches[1]) as $label) {
            if (! isset($enJson[$label]) || ! isset($arJson[$label])) {
                $missing[] = $label;
            }
        }

        $this->assertEmpty(
            $missing,
            'Sidebar labels missing translations: '.implode(', ', $missing)
        );
    }
}
