# Locale-Based Form Submission Analysis

## Problem Statement

The Arabic issue is **NOT just about character encoding**. The problem occurs when:
- The system locale is switched to Arabic (`app()->setLocale('ar')`)
- Forms fail to save certain fields even with English text
- This suggests locale-dependent code differences

## Investigation Points

### 1. Request Payload Differences

**Hypothesis**: POST request keys might differ between locales
- Check if form input names change based on locale
- Verify wire:model bindings remain consistent
- Confirm no translated field names in forms

**Finding**: Supplier form uses `wire:model="city"` consistently - ✓ SAFE

### 2. Validation Rule Differences

**Hypothesis**: Validation might be locale-dependent
- Check for locale-conditional validation rules
- Verify no locale-based field exclusions
- Confirm validation error messages don't affect field acceptance

**Finding**: Validation rules in `Form::rules()` are static - ✓ SAFE

### 3. RTL Layout Impact

**Hypothesis**: RTL CSS might affect form submission
- Check if dir="rtl" causes field name changes
- Verify JavaScript doesn't alter payload in RTL mode
- Confirm Livewire wire:model works with RTL

**Finding**: Only `phone` field has `dir="ltr"` override - ✓ SAFE

### 4. Middleware/Service Layer

**Hypothesis**: Locale-based data processing
- Check if `SetLocale` middleware alters requests
- Verify no locale-dependent sanitization
- Confirm no conditional field filtering

**Finding**: `SetLocale` only sets locale, doesn't modify request - ✓ SAFE

### 5. Missing DB Columns (Original Issue)

**Root Cause Confirmed**: 
- The supplier migration lacked `city`, `country`, `company_name` columns
- This caused silent data loss in BOTH locales
- Already fixed in commit e9dcf66

## Test Suite Created

### LocaleSwitchingFormTest.php

**6 comprehensive tests**:

1. `test_supplier_form_saves_correctly_in_english_locale()`
   - Verifies baseline: English locale works correctly
   
2. `test_supplier_form_saves_correctly_in_arabic_locale()`  
   - **CRITICAL TEST**: Ensures all fields persist when locale=ar
   - Uses English values to isolate locale vs character issues
   
3. `test_supplier_form_saves_arabic_text_in_arabic_locale()`
   - Combines Arabic locale + Arabic characters
   - Tests full Unicode support
   
4. `test_supplier_update_works_in_arabic_locale()`
   - Verifies update operations work in Arabic mode
   
5. `test_livewire_supplier_form_saves_in_arabic_locale()`
   - Direct Livewire component test
   - Ensures wire:model bindings work in Arabic locale
   
6. `test_form_payload_keys_are_identical_in_both_locales()`
   - Compares HTML output between locales
   - Verifies field names don't change

## Recommendations

### Immediate Actions

1. **Run the test suite**:
   ```bash
   php artisan test tests/Feature/Locale/LocaleSwitchingFormTest.php
   ```

2. **Manual verification**:
   - Switch to Arabic: `?lang=ar`
   - Create supplier with English text
   - Verify city/country/company_name persist
   - Switch to English: `?lang=en`
   - Repeat and compare

3. **Check other forms**:
   - Apply same testing pattern to customers, products, etc.
   - Look for locale-conditional logic in other modules

### Long-term Improvements

1. **Add locale-switching CI tests**:
   - Run all CRUD tests in both locales
   - Catch locale-dependent regressions early

2. **Audit for locale-conditional code**:
   ```php
   // AVOID patterns like:
   if (app()->getLocale() === 'ar') {
       // different field mapping
   }
   ```

3. **Document locale requirements**:
   - Forms must be locale-agnostic
   - Field names must not be translated
   - Validation must be locale-independent

## Expected Test Results

If tests pass → No locale-specific form submission issues
If tests fail → Specific failure will pinpoint the locale-dependent code

## Next Steps

1. Run `LocaleSwitchingFormTest`
2. If failures detected, investigate specific failing assertions
3. Fix any locale-conditional code found
4. Extend tests to other modules (customers, products, etc.)
5. Document findings and update best practices
