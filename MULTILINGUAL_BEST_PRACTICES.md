# System-Wide Arabic/Unicode Support - Best Practices Guide

**Date**: 2026-01-02  
**Status**: Implementation Complete  
**Scope**: Entire hugouserp application

## Executive Summary

This document provides guidelines and best practices for ensuring proper Arabic/Unicode support across all forms and modules in the hugouserp ERP system. Following these practices prevents data loss and ensures multilingual functionality.

## Key Principles

### 1. Never Use `alpha` or `ascii` Validation

❌ **WRONG** - Blocks non-Latin characters:
```php
'name' => ['required', 'string', 'alpha'],
'code' => ['required', 'ascii'],
```

✅ **CORRECT** - Allows Unicode:
```php
use App\Http\Requests\Traits\HasMultilingualValidation;

'name' => $this->multilingualString(required: true, max: 255),
'code' => $this->alphanumericCode(required: true, max: 50),
```

### 2. Use Unicode-Aware Regex

When you need pattern validation, always use the `/u` flag and Unicode character classes:

❌ **WRONG**:
```php
'code' => ['regex:/^[a-zA-Z]+$/']  // Only Latin letters
```

✅ **CORRECT**:
```php
'code' => ['regex:/^[\p{L}\p{M}]+$/u']  // All Unicode letters + marks
```

### 3. Database Schema Requirements

All string/text columns must use utf8mb4:

```php
Schema::create('your_table', function (Blueprint $table) {
    $table->engine = 'InnoDB';
    $table->charset = 'utf8mb4';
    $table->collation = 'utf8mb4_unicode_ci';
    
    $table->string('name');  // Will use utf8mb4
    $table->text('description');  // Will use utf8mb4
});
```

### 4. Model Configuration

Ensure all text fields are in `$fillable`:

```php
protected $fillable = [
    'name',
    'name_ar',  // Arabic name field
    'description',
    'notes',
    // ... all text fields
];
```

## Using HasMultilingualValidation Trait

### Installation

The trait is located at: `app/Http/Requests/Traits/HasMultilingualValidation.php`

### Usage in Form Requests

```php
<?php

namespace App\Http\Requests;

use App\Http\Requests\Traits\HasMultilingualValidation;
use Illuminate\Foundation\Http\FormRequest;

class YourFormRequest extends FormRequest
{
    use HasMultilingualValidation;

    public function rules(): array
    {
        return [
            // Basic multilingual string
            'name' => $this->multilingualString(required: true, max: 255),
            
            // Optional text field
            'description' => $this->multilingualText(required: false, max: 2000),
            
            // Code with Unicode letters only
            'code' => $this->unicodeLettersOnly(required: true, length: 3),
            
            // Code with letters and numbers
            'sku' => $this->alphanumericCode(required: true, max: 50),
            
            // Code with separators (-, _, space)
            'reference' => $this->flexibleCode(required: true, max: 50),
            
            // Arabic-specific field
            'name_ar' => $this->arabicName(required: false, max: 255),
            
            // Most permissive (notes, long text)
            'notes' => $this->unicodeText(required: false, max: 5000),
        ];
    }
}
```

### Usage in Livewire Components

For Livewire validation, you can use the trait methods directly:

```php
<?php

namespace App\Livewire\YourModule;

use App\Http\Requests\Traits\HasMultilingualValidation;
use Livewire\Component;

class Form extends Component
{
    use HasMultilingualValidation;

    public string $name = '';
    public string $nameAr = '';
    public string $code = '';

    protected function rules(): array
    {
        return [
            'name' => $this->multilingualString(required: true, max: 255),
            'nameAr' => $this->arabicName(required: false, max: 255),
            'code' => $this->alphanumericCode(required: true, length: 10),
        ];
    }
}
```

## Available Validation Methods

### `multilingualString($required, $max, $min = null)`
For general string fields (names, titles, labels).
- Accepts: Any Unicode text
- Use for: Name, title, label, category name

### `multilingualText($required, $max = 5000)`
For longer text content.
- Accepts: Any Unicode text
- Use for: Descriptions, summaries

### `unicodeLettersOnly($required, $length = null, $max = null)`
Only letters from any script (no numbers or symbols).
- Accepts: `\p{L}` + `\p{M}` (letters + diacritics)
- Use for: Currency codes, language codes
- Example: SAR, USD, ريال

### `alphanumericCode($required, $length = null, $max = null)`
Letters and numbers from any script.
- Accepts: `\p{L}` + `\p{M}` + `\p{N}`
- Use for: SKU, product codes, reference numbers
- Example: ABC123, محمد٢٣

### `flexibleCode($required, $max = 50)`
Letters, numbers, and common separators.
- Accepts: Letters + numbers + space + underscore + hyphen
- Use for: Reference numbers, IDs with formatting
- Example: REF-2024-001, مرجع_٢٠٢٤

### `arabicName($required, $max = 255)`
Arabic letters and spaces only.
- Accepts: `\p{Arabic}` + spaces
- Use for: Arabic-specific fields
- Example: محمد أحمد

### `unicodeText($required, $max = 5000)`
Most permissive - any Unicode text.
- Accepts: Any characters
- Use for: Notes, comments, free-form text

## Testing Requirements

### 1. Include Arabic Tests for All Forms

Every form with text input should have tests covering:

```php
public function test_form_accepts_arabic_text(): void
{
    $entity = YourModel::create([
        'name' => 'اسم باللغة العربية',
        'description' => 'وصف مفصل باللغة العربية',
        'branch_id' => $this->branch->id,
    ]);

    $this->assertDatabaseHas('your_table', [
        'name' => 'اسم باللغة العربية',
        'description' => 'وصف مفصل باللغة العربية',
    ]);

    $retrieved = YourModel::find($entity->id);
    $this->assertEquals('اسم باللغة العربية', $retrieved->name);
}
```

### 2. Test Arabic Special Characters

```php
public function test_arabic_special_characters(): void
{
    $text = 'ء ئ ؤ أ إ آ ة ى التشكيل: َ ُ ِ ّ ْ';
    
    $entity = YourModel::create([
        'name' => $text,
        'branch_id' => $this->branch->id,
    ]);

    $this->assertEquals($text, $entity->fresh()->name);
}
```

### 3. Test Mixed Arabic/English

```php
public function test_mixed_arabic_english(): void
{
    $entity = YourModel::create([
        'name' => 'Product - منتج',
        'description' => 'Description in English - وصف بالعربية',
        'branch_id' => $this->branch->id,
    ]);

    $this->assertDatabaseHas('your_table', [
        'name' => 'Product - منتج',
    ]);
}
```

## Common Issues and Solutions

### Issue 1: "Arabic text not saving"

**Symptom**: Arabic text appears empty or garbled after saving.

**Causes**:
1. ❌ Database column using latin1 charset
2. ❌ Validation rules using `alpha` or `ascii`
3. ❌ Missing column in database (data silently discarded)
4. ❌ Field not in Model `$fillable`

**Solutions**:
1. ✅ Ensure migration uses utf8mb4_unicode_ci
2. ✅ Use Unicode-aware validation (no `alpha`/`ascii`)
3. ✅ Verify column exists in database
4. ✅ Add field to Model `$fillable` array

### Issue 2: "Validation fails for Arabic input"

**Symptom**: Form validation fails when Arabic text is entered.

**Cause**: ❌ Using `alpha`, `ascii`, or Latin-only regex

**Solution**: ✅ Use `HasMultilingualValidation` trait methods

### Issue 3: "Arabic text works in some forms but not others"

**Symptom**: Inconsistent behavior across forms.

**Cause**: ❌ Inconsistent validation rules

**Solution**: ✅ Apply `HasMultilingualValidation` trait consistently

### Issue 4: "Currency/language codes rejected"

**Symptom**: Non-Latin currency codes (ريال) or language codes rejected.

**Cause**: ❌ Using `alpha` validation

**Solution**: ✅ Use `unicodeLettersOnly()` method

## Migration Checklist

When adding a new table or column for text data:

- [ ] Set table charset to utf8mb4
- [ ] Set table collation to utf8mb4_unicode_ci
- [ ] Use appropriate column types (string, text, not binary)
- [ ] Add column to Model `$fillable`
- [ ] Use multilingual validation rules
- [ ] Add Arabic tests

## Form Request Checklist

When creating/updating a Form Request:

- [ ] Import `HasMultilingualValidation` trait
- [ ] Replace `alpha` with `unicodeLettersOnly()`
- [ ] Replace `ascii` with appropriate method
- [ ] Use `multilingualString()` for names
- [ ] Use `unicodeText()` for notes/descriptions
- [ ] Test with Arabic input

## Livewire Component Checklist

When creating/updating a Livewire Form:

- [ ] Import `HasMultilingualValidation` trait
- [ ] Use trait methods in `rules()`
- [ ] Ensure property names match DB columns
- [ ] Add all properties to Model `$fillable`
- [ ] Test form with Arabic input

## Examples from Codebase

### ✅ Good Example: Supplier Form (Fixed)

```php
// app/Livewire/Suppliers/Form.php
protected function rules(): array
{
    return [
        'name' => ['required', 'string', 'max:255', 'min:2'],  // ✅ No alpha
        'company_name' => ['nullable', 'string', 'max:255'],   // ✅ Allows Unicode
        'city' => ['nullable', 'string', 'max:100'],          // ✅ Allows Arabic cities
        'country' => ['nullable', 'string', 'max:100'],       // ✅ Allows Arabic countries
    ];
}
```

### ✅ Good Example: Currency Form (Fixed)

```php
// app/Livewire/Admin/Currency/Form.php
protected function rules(): array
{
    return [
        // ✅ Unicode-aware regex instead of 'alpha'
        'code' => ['required', 'string', 'size:3', 'regex:/^[\p{L}\p{M}]+$/u'],
        'name' => ['required', 'string', 'max:100'],  // ✅ Allows Unicode
        'nameAr' => ['nullable', 'string', 'max:100'],  // ✅ Arabic name
    ];
}
```

### ❌ Bad Example (Hypothetical - Don't Do This)

```php
protected function rules(): array
{
    return [
        'name' => ['required', 'alpha'],  // ❌ Blocks Arabic!
        'code' => ['required', 'ascii'],  // ❌ Blocks Arabic!
        'phone' => ['regex:/^[0-9]+$/'],  // ⚠️ Blocks Arabic-Indic numerals
    ];
}
```

## Resources

- **Laravel Validation Docs**: https://laravel.com/docs/validation
- **Unicode Character Classes**: https://www.regular-expressions.info/unicode.html
- **Arabic Unicode Range**: U+0600 to U+06FF, U+0750 to U+077F

## Support

For questions or issues with multilingual support:
1. Check this guide first
2. Review existing tests in `tests/Feature/GlobalArabic/`
3. Refer to `HasMultilingualValidation` trait for examples
4. Check `GLOBAL_ARABIC_FORMS_AUDIT.md` for system-wide audit results

---

**Last Updated**: 2026-01-02  
**Maintained By**: Development Team  
**Version**: 1.0
