# Module-Product Integration Documentation

**Date:** December 8, 2025  
**Status:** ✅ Implemented  
**Version:** 1.0

---

## Overview

This document describes the unified module-aware product management system in HugousERP. The system ensures that all product creation and management flows through a centralized service layer with module-specific validation and field handling.

---

## Architecture

### 1. Module Support Flag

All modules now have a `supports_items` boolean flag that indicates whether the module can manage items/products.

```php
// Check if module supports items
$module = Module::find($moduleId);
if ($module->supportsItems()) {
    // Can create products
}

// Query only modules that support items
$itemModules = Module::active()->supportsItems()->get();
```

**Modules that support items:**
- Inventory
- Rental
- POS
- Sales  
- Purchases
- Spare Parts
- Any module with `has_inventory = true`

---

## 2. Unified ProductService

### Core Methods

#### createProductForModule()
Creates a product with module-aware validation and field handling.

```php
use App\Services\ProductService;

$productService = app(ProductService::class);
$module = Module::findOrFail($moduleId);

$data = [
    'name' => 'Product Name',
    'sku' => 'SKU001',
    'price' => 100.00,
    'cost' => 50.00,
    'branch_id' => auth()->user()->branch_id,
    'custom_fields' => [
        'wood_type' => 'Oak',
        'dimensions' => '100x50x30',
    ],
];

$product = $productService->createProductForModule(
    $module,
    $data,
    $thumbnailFile // Optional UploadedFile
);
```

**Features:**
- Validates module supports items
- Automatically sets product type based on module (service vs stock)
- Handles thumbnail upload and storage
- Saves custom fields if module supports them
- Sets created_by automatically
- Wrapped in database transaction

#### updateProductForModule()
Updates an existing product with module-aware handling.

```php
$product = Product::findOrFail($productId);

$data = [
    'name' => 'Updated Name',
    'price' => 120.00,
    'custom_fields' => [...],
];

$updatedProduct = $productService->updateProductForModule(
    $product,
    $data,
    $newThumbnailFile // Optional
);
```

**Features:**
- Cleans up old thumbnail before uploading new one
- Updates custom fields atomically
- Sets updated_by automatically
- Transaction-safe

---

## 3. Livewire Products Form Flow

### UI Flow

1. **Module Selection (Required for new products)**
   - Only modules with `supports_items = true` are shown
   - User must select a module before proceeding
   - Module selection triggers dynamic field loading

2. **Basic Fields**
   - Name, SKU, Barcode (standard across all modules)
   - Price, Cost (with currency selection)
   - Type (auto-set based on module: service vs stock)
   - Status

3. **Thumbnail Upload**
   - Optional image upload with preview
   - 2MB max size validation
   - Stored in `public/products/thumbnails`

4. **Module-Specific Custom Fields**
   - Loaded dynamically based on selected module
   - Field types: text, number, date, select, checkbox, etc.
   - Validated according to module field configuration

### Code Structure

```php
// app/Livewire/Inventory/Products/Form.php

class Form extends Component
{
    use WithFileUploads;
    
    protected ProductService $productService;
    
    public function save(): void
    {
        // Validates module selection for new products
        if (!$this->productId && !$this->form['module_id']) {
            $this->addError('form.module_id', 'Module selection required');
            return;
        }
        
        // Uses ProductService for unified creation
        $module = Module::findOrFail($this->form['module_id']);
        $product = $this->productService->createProductForModule(
            $module,
            $this->prepareData(),
            $this->thumbnailFile
        );
    }
}
```

---

## 4. Custom Fields Integration

Custom fields are automatically handled based on module configuration:

```php
// Module defines fields
ModuleProductField::create([
    'module_id' => $module->id,
    'field_key' => 'wood_type',
    'field_label' => 'Wood Type',
    'field_type' => 'select',
    'field_options' => ['Oak', 'Pine', 'Mahogany'],
    'is_required' => true,
]);

// ProductService saves field values
$product = $productService->createProductForModule($module, [
    'name' => 'Wooden Chair',
    'custom_fields' => [
        'wood_type' => 'Oak',
    ],
]);

// Retrieve field values
$woodType = $product->getFieldValue('wood_type');
```

---

## 5. Module Integration Patterns

### For Each Module Using Products

**Step 1: Ensure Module Has supports_items Flag**
```sql
UPDATE modules SET supports_items = true WHERE key = 'your_module_key';
```

**Step 2: Use ProductService in Your Controllers/Livewire**
```php
use App\Services\ProductService;

public function createItem(Request $request, ProductService $productService)
{
    $module = Module::key('your_module')->firstOrFail();
    
    $product = $productService->createProductForModule(
        $module,
        $request->validated(),
        $request->file('thumbnail')
    );
    
    // Additional module-specific logic here
}
```

**Step 3: Query Products for Your Module**
```php
// Get all products for a specific module
$products = Product::where('module_id', $module->id)
    ->active()
    ->get();

// With custom fields
$products = Product::with(['fieldValues.field'])
    ->where('module_id', $module->id)
    ->get();
```

---

## 6. Option Cycle Tracking

### Inventory Module
- **Route:** `inventory.products.create`
- **Livewire:** `App\Livewire\Inventory\Products\Form`
- **Service:** `ProductService::createProductForModule()`
- **Flow:** Route → Livewire → ProductService → Repository → Model → DB

### Sales/POS Module  
- **Route:** Sales order creation
- **Service:** Uses existing products, no direct creation
- **Flow:** Order → Product fetch → Stock validation

### Rental Module
- **Route:** `rentals.items.create`
- **Service:** `ProductService::createProductForModule()` with rental flag
- **Flow:** Similar to inventory with rental-specific fields

### Purchases Module
- **Route:** Purchase order creation
- **Service:** May create products if "add missing items" enabled
- **Flow:** PO → Product lookup/create → Inventory update

---

## 7. Best Practices

### DO's ✅
- Always use `ProductService::createProductForModule()` for new products
- Check module supports items before product creation
- Include custom_fields in data array for module-specific fields
- Use thumbnail parameter for image uploads
- Let the service handle transactions and field saving

### DON'Ts ❌
- Don't create products directly with `Product::create()`
- Don't bypass module validation
- Don't handle thumbnail upload outside ProductService
- Don't save custom fields manually
- Don't write raw queries for product creation

---

## 8. Migration Guide

### Updating Existing Code

**Before:**
```php
$product = Product::create([
    'name' => $request->name,
    'sku' => $request->sku,
    'default_price' => $request->price,
    'branch_id' => auth()->user()->branch_id,
]);

if ($request->hasFile('thumbnail')) {
    $product->thumbnail = $request->file('thumbnail')->store('products');
    $product->save();
}
```

**After:**
```php
$module = Module::find($request->module_id);
$product = app(ProductService::class)->createProductForModule(
    $module,
    $request->only(['name', 'sku', 'price', 'branch_id', 'custom_fields']),
    $request->file('thumbnail')
);
```

---

## 9. Testing

### Test Scenarios

1. **Create product with module that supports items**
   - ✅ Should succeed
   - ✅ Custom fields saved
   - ✅ Thumbnail uploaded

2. **Attempt create without module selection**
   - ✅ Should show validation error
   - ✅ Form remains filled

3. **Module dropdown filtering**
   - ✅ Only shows modules with supports_items=true
   - ✅ Filters by branch if applicable

4. **Update existing product**
   - ✅ Can change basic fields
   - ✅ Can update thumbnail (old one deleted)
   - ✅ Custom fields preserved/updated

---

## 10. Future Enhancements

- [ ] Bulk import through ProductService
- [ ] Product templates per module
- [ ] Advanced field validation rules
- [ ] Field visibility based on user permissions
- [ ] Product versioning/history
- [ ] Multi-image gallery support (already in DB)

---

## Support

For issues or questions:
1. Check this documentation first
2. Review `MODULE_SYSTEM_ARCHITECTURE.md`
3. Inspect `app/Services/ProductService.php`
4. Contact development team

**Last Updated:** December 8, 2025
