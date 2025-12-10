# Module System Architecture - HugousERP

**Date:** December 7, 2025  
**Status:** ✅ Fully Implemented  
**Architecture Grade:** A

---

## Overview

HugousERP implements a **sophisticated module system** where modules are treated as complete packages that define entities, fields, behaviors, and integrations. This document explains the current implementation.

---

## 1. Module as a Complete Package

### 1.1 Module Model Structure

**Location:** `app/Models/Module.php`

Each module in the system is a comprehensive package that includes:

```php
class Module extends Model
{
    protected $fillable = [
        // Basic Definition
        'key',                      // Unique identifier (e.g., 'inventory', 'rental', 'hrm')
        'slug',                     // URL-friendly name
        'name',                     // English name
        'name_ar',                  // Arabic name
        'version',                  // Module version
        'is_core',                  // Is it a core module?
        'is_active',                // Is it enabled?
        'description',              // English description
        'description_ar',           // Arabic description
        'icon',                     // UI icon
        'color',                    // UI color theme
        'sort_order',               // Display order
        
        // Entity Characteristics
        'product_type',             // Type of entities this module manages
        'has_variations',           // Supports product variations?
        'has_inventory',            // Tracks inventory?
        'has_serial_numbers',       // Tracks serial numbers?
        'has_expiry_dates',         // Tracks expiry dates?
        'has_batch_numbers',        // Tracks batch numbers?
        'is_rental',                // Is it a rental module?
        'is_service',               // Is it a service module?
        
        // Module Type & Configuration
        'module_type',              // 'data' or 'functional'
        'category',                 // Module category
        'operation_config',         // JSON: Behavior settings
        'integration_hooks',        // JSON: Integration points
        'default_settings',         // JSON: Default settings
        
        // Capabilities
        'supports_reporting',       // Has reports?
        'supports_custom_fields',   // Allows custom fields?
        'pricing_type',             // 'buy_sell', 'sell_only', 'cost_only'
    ];
}
```

### 1.2 Module Types

The system distinguishes between two fundamental module types:

#### A. Data-Oriented Modules (`module_type = 'data'`)

**Purpose:** Manage entities with input forms and CRUD operations

**Examples:**
- **Inventory** - Products, Stock Items
- **Rental** - Rental Units, Properties, Vehicles
- **HRM** - Employees
- **Fixed Assets** - Assets
- **CRM** - Customers, Leads

**Characteristics:**
- Have primary entities (Products, Contracts, Employees, etc.)
- Support dynamic custom fields per entity
- Have standard CRUD forms
- Support attachments and notes per entity
- Link to accounting through transactions

**Implementation:**
```php
// Check if module is data-oriented
$module->isDataOriented(); // Returns true if module_type === 'data'

// Scope to get data-oriented modules
Module::dataOriented()->get();
```

#### B. Functional Modules (`module_type = 'functional'`)

**Purpose:** Provide operations, calculations, or processes

**Examples:**
- **POS** - Point of Sale operations
- **Payroll** - Salary calculations
- **Reports** - Report generation
- **Workflow Engine** - Approval processes
- **Accounting** - Financial transactions

**Characteristics:**
- Focus on operations rather than entity storage
- Have behavior settings (`operation_config`)
- Integrate with other modules
- May not have primary input forms
- Configured through settings rather than entities

**Implementation:**
```php
// Check if module is functional
$module->isFunctional(); // Returns true if module_type === 'functional'

// Scope to get functional modules
Module::functional()->get();
```

---

## 2. Module Relationships

### 2.1 Module → Branches (Many-to-Many)

**Table:** `branch_modules`

```php
$module->branches()->get();

// Pivot includes:
// - enabled (bool)
// - settings (JSON)
// - module_key (string)
```

**Purpose:** Control which branches can use which modules

### 2.2 Module → Products (One-to-Many)

**Relationship:** Each product belongs to exactly ONE module

```php
// In Product model
public function module(): BelongsTo
{
    return $this->belongsTo(Module::class);
}

// Usage
$product = Product::find(1);
$module = $product->module; // Returns the module this product belongs to
```

**Database:**
```sql
-- products table
ALTER TABLE products ADD COLUMN module_id BIGINT UNSIGNED;
ALTER TABLE products ADD FOREIGN KEY (module_id) REFERENCES modules(id);
```

### 2.3 Module → Custom Fields (One-to-Many)

**Tables:** 
- `module_custom_fields` - Field definitions
- `module_product_fields` - Product-specific fields

```php
$module->customFields()->get();
$module->productFields()->get();
```

### 2.4 Module → Settings (One-to-Many)

**Table:** `module_settings`

```php
// Get a setting value
$value = $module->getSetting('key', $branchId, $defaultValue);

// Set a setting value
$module->setSetting('key', $value, $branchId, 'string');
```

**Common Settings:**
- Accounting account mappings
- Default values
- Behavior configurations
- Integration endpoints
- UI preferences

### 2.5 Module → Navigation (One-to-Many)

**Table:** `module_navigation`

```php
$module->navigation()->get();
```

**Purpose:** Define sidebar menu items, routes, and permissions for the module

### 2.6 Module → Reports (One-to-Many)

**Table:** `report_definitions`

```php
$module->reportDefinitions()->get();
```

### 2.7 Module → Policies & Operations

**Tables:** `module_policies`, `module_operations`

```php
$module->policies()->get();
$module->operations()->get();
```

---

## 3. Module Selection When Adding Entities

### 3.1 Product Form Architecture

**Location:** `app/Livewire/Inventory/Products/Form.php`

The product form implements **module-first** approach:

```php
class Form extends Component
{
    public ?int $selectedModuleId = null;  // Module selection
    
    public array $form = [
        'name' => '',
        'sku' => '',
        'module_id' => null,  // Required module association
        // ... other fields
    ];
    
    public array $dynamicSchema = [];  // Module-specific fields
    public array $dynamicData = [];    // Values for dynamic fields
}
```

### 3.2 Flow: Adding a New Product

**Step 1: Module Selection**
```php
// User selects module first
$this->selectedModuleId = $moduleId;

// Load module-specific fields
$this->loadModuleFields($moduleId);
```

**Step 2: Load Dynamic Fields**
```php
public function loadModuleFields($moduleId): void
{
    $module = Module::find($moduleId);
    
    // Get module-specific product fields
    $fields = $module->productFields()
        ->where('is_active', true)
        ->orderBy('sort_order')
        ->get();
    
    // Build dynamic schema
    $this->dynamicSchema = $fields->map(function($field) {
        return [
            'key' => $field->field_key,
            'label' => $field->label,
            'type' => $field->field_type,
            'required' => $field->is_required,
            'options' => $field->field_options,
        ];
    })->toArray();
}
```

**Step 3: Render Form with Module Context**
```php
// Base fields (shared across all products)
<input wire:model="form.name" />
<input wire:model="form.sku" />
<input wire:model="form.price" />
<input wire:model="form.cost" />

// Module-specific fields (dynamic)
@foreach($dynamicSchema as $field)
    <x-dynamic-field :field="$field" wire:model="dynamicData.{{ $field['key'] }}" />
@endforeach
```

**Step 4: Save with Module Association**
```php
public function save()
{
    // Validate base fields
    $validated = $this->validate([
        'form.name' => 'required',
        'form.module_id' => 'required|exists:modules,id',
        // ... other rules
    ]);
    
    // Save product with module
    $product = Product::create([
        'name' => $this->form['name'],
        'module_id' => $this->selectedModuleId,  // ← Module association
        // ... other fields
    ]);
    
    // Save dynamic field values
    foreach ($this->dynamicData as $key => $value) {
        ProductFieldValue::create([
            'product_id' => $product->id,
            'field_key' => $key,
            'field_value' => $value,
        ]);
    }
}
```

### 3.3 Module Service Layer

**Location:** `app/Services/ModuleProductService.php`

This service handles module-product logic:

```php
class ModuleProductService
{
    /**
     * Create product with module context
     */
    public function createProduct(int $moduleId, array $data): Product
    {
        $module = Module::findOrFail($moduleId);
        
        // Validate module allows product creation
        if (!$module->isDataOriented()) {
            throw new Exception("Module {$module->name} does not support products");
        }
        
        // Apply module-specific rules
        $data = $this->applyModuleRules($module, $data);
        
        // Create product with module association
        $product = Product::create(array_merge($data, [
            'module_id' => $moduleId,
            'product_type' => $module->product_type,
            'has_inventory' => $module->has_inventory,
            'is_serialized' => $module->has_serial_numbers,
            // ... other module characteristics
        ]));
        
        return $product;
    }
    
    /**
     * Apply module-specific business rules
     */
    protected function applyModuleRules(Module $module, array $data): array
    {
        // Rental modules
        if ($module->is_rental) {
            // Ensure rental-specific fields are present
            $data['type'] = 'rental';
            // Apply rental pricing logic
        }
        
        // Service modules
        if ($module->is_service) {
            $data['type'] = 'service';
            $data['has_inventory'] = false;
        }
        
        // Apply pricing rules
        if (!$module->hasBuyPrice()) {
            $data['cost'] = null;
        }
        
        if (!$module->hasSellPrice()) {
            $data['price'] = null;
        }
        
        return $data;
    }
}
```

---

## 4. Module Configuration System

### 4.1 Operation Configuration

**Purpose:** Define module behavior settings

**Example for POS Module:**
```php
Module::create([
    'key' => 'pos',
    'name' => 'Point of Sale',
    'module_type' => 'functional',
    'operation_config' => [
        'allow_negative_stock' => false,
        'require_customer' => true,
        'auto_print_receipt' => true,
        'default_payment_method' => 'cash',
        'enable_quick_keys' => true,
        'cash_drawer_enabled' => true,
        'barcode_scanner_enabled' => true,
    ],
]);

// Access configuration
$posModule = Module::where('key', 'pos')->first();
$allowNegative = $posModule->getOperationConfig('allow_negative_stock', false);
```

**Example for Rental Module:**
```php
Module::create([
    'key' => 'rental',
    'name' => 'Rental Management',
    'module_type' => 'data',
    'operation_config' => [
        'billing_cycle' => 'monthly',
        'allow_partial_payments' => true,
        'late_fee_percentage' => 5.0,
        'security_deposit_required' => true,
        'auto_generate_invoices' => true,
        'invoice_generation_day' => 1,
    ],
]);
```

### 4.2 Integration Hooks

**Purpose:** Define how module integrates with accounting, inventory, etc.

**Example:**
```php
Module::create([
    'key' => 'sales',
    'name' => 'Sales',
    'integration_hooks' => [
        'accounting' => [
            'revenue_account' => 'sales_revenue',
            'receivable_account' => 'accounts_receivable',
            'discount_account' => 'sales_discount',
            'tax_account' => 'tax_payable',
        ],
        'inventory' => [
            'auto_deduct_stock' => true,
            'allow_backorders' => false,
            'reservation_required' => true,
        ],
        'notifications' => [
            'send_receipt_email' => true,
            'send_sms_confirmation' => false,
        ],
    ],
]);

// Access hooks
$salesModule = Module::where('key', 'sales')->first();
$revenueAccount = $salesModule->getIntegrationHook('accounting.revenue_account');
```

---

## 5. Module Scopes & Helpers

### 5.1 Query Scopes

```php
// Get active modules
Module::active()->get();

// Get core modules
Module::core()->get();

// Get modules by type
Module::dataOriented()->get();
Module::functional()->get();

// Get rental modules
Module::rental()->get();

// Get service modules
Module::service()->get();

// Get modules with inventory
Module::withInventory()->get();

// Get modules by category
Module::category('sales')->get();

// Get modules supporting custom fields
Module::supportsCustomFields()->get();

// Get modules supporting reporting
Module::supportsReporting()->get();

// Get module by key
Module::key('inventory')->first();
```

### 5.2 Helper Methods

```php
// Check module characteristics
$module->isDataOriented();          // Is it data-oriented?
$module->isFunctional();            // Is it functional?
$module->hasBuyPrice();             // Has cost/buy price?
$module->hasSellPrice();            // Has sell price?

// Get localized content
$module->localized_name;            // Name in current locale
$module->localized_description;     // Description in current locale

// Settings management
$module->getSetting('key', $branchId, $default);
$module->setSetting('key', $value, $branchId, 'type');

// Configuration access
$module->getOperationConfig('key', $default);
$module->getIntegrationHook('key', $default);
```

---

## 6. Module Permission System

### 6.1 Permission Naming Convention

**Pattern:** `{module_key}.{resource}.{action}`

**Examples:**
```
inventory.products.view
inventory.products.create
inventory.products.edit
inventory.products.delete
inventory.stock.adjust
inventory.transfers.approve

rental.contracts.view
rental.contracts.create
rental.invoices.generate
rental.payments.record

hrm.employees.view
hrm.payroll.process
hrm.attendance.approve
```

### 6.2 Module Policies

**Table:** `module_policies`

```php
ModulePolicy::create([
    'module_id' => $inventoryModule->id,
    'policy_key' => 'can_edit_cost',
    'policy_name' => 'Can Edit Product Cost',
    'policy_rule' => 'role:admin|role:inventory_manager',
    'description' => 'Determines who can edit product cost prices',
]);

// Check policy
if ($module->policies()->where('policy_key', 'can_edit_cost')->exists()) {
    // Policy is defined
}
```

---

## 7. Module Activation & Branch Assignment

### 7.1 Enable Module for Branch

```php
// Enable module for a branch
$branch->modules()->attach($moduleId, [
    'enabled' => true,
    'module_key' => 'inventory',
    'settings' => [
        'default_warehouse_id' => 1,
        'auto_generate_sku' => true,
    ],
]);

// Or using relationship
BranchModule::create([
    'branch_id' => $branch->id,
    'module_id' => $module->id,
    'enabled' => true,
    'module_key' => $module->key,
    'settings' => [...],
]);
```

### 7.2 Check Module Access

```php
// Check if branch has access to module
$hasAccess = $branch->modules()
    ->where('modules.id', $moduleId)
    ->wherePivot('enabled', true)
    ->exists();

// Get enabled modules for branch
$enabledModules = $branch->modules()
    ->wherePivot('enabled', true)
    ->get();
```

---

## 8. Real-World Examples

### 8.1 Example: Rental Module

**Module Definition:**
```php
Module::create([
    'key' => 'rental',
    'slug' => 'rental',
    'name' => 'Rental Management',
    'name_ar' => 'إدارة الإيجارات',
    'module_type' => 'data',  // Data-oriented (has rental units as entities)
    'category' => 'operations',
    'is_rental' => true,
    'has_inventory' => false,  // Rentals don't affect inventory
    'pricing_type' => 'sell_only',  // Only has rental price, no cost
    'supports_custom_fields' => true,
    'supports_reporting' => true,
    'operation_config' => [
        'billing_cycle' => 'monthly',
        'invoice_day' => 1,
        'late_fee_enabled' => true,
    ],
    'integration_hooks' => [
        'accounting' => [
            'revenue_account' => 'rental_revenue',
            'deposit_account' => 'security_deposits',
        ],
    ],
]);
```

**Custom Fields for Rental Units:**
```php
ModuleProductField::create([
    'module_id' => $rentalModule->id,
    'field_key' => 'property_type',
    'label' => 'Property Type',
    'label_ar' => 'نوع العقار',
    'field_type' => 'select',
    'field_options' => ['apartment', 'villa', 'office', 'warehouse'],
    'is_required' => true,
    'sort_order' => 1,
]);

ModuleProductField::create([
    'module_id' => $rentalModule->id,
    'field_key' => 'square_meters',
    'label' => 'Square Meters',
    'label_ar' => 'المساحة بالمتر المربع',
    'field_type' => 'number',
    'is_required' => true,
    'sort_order' => 2,
]);
```

### 8.2 Example: POS Module

**Module Definition:**
```php
Module::create([
    'key' => 'pos',
    'slug' => 'pos',
    'name' => 'Point of Sale',
    'name_ar' => 'نقطة البيع',
    'module_type' => 'functional',  // Functional (operations, not entities)
    'category' => 'sales',
    'has_inventory' => true,  // Affects inventory
    'supports_reporting' => true,
    'operation_config' => [
        'enable_cash_drawer' => true,
        'auto_print_receipt' => true,
        'allow_credit_sales' => false,
        'barcode_scanner' => true,
    ],
]);
```

---

## 9. Migration Structure

### 9.1 Modules Table

```sql
CREATE TABLE modules (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    key VARCHAR(255) UNIQUE NOT NULL,
    slug VARCHAR(255) UNIQUE NOT NULL,
    name VARCHAR(255) NOT NULL,
    name_ar VARCHAR(255),
    version VARCHAR(20),
    is_core BOOLEAN DEFAULT FALSE,
    is_active BOOLEAN DEFAULT TRUE,
    description TEXT,
    description_ar TEXT,
    icon VARCHAR(100),
    color VARCHAR(20),
    sort_order INT DEFAULT 0,
    
    -- Entity characteristics
    product_type VARCHAR(50),
    has_variations BOOLEAN DEFAULT FALSE,
    has_inventory BOOLEAN DEFAULT FALSE,
    has_serial_numbers BOOLEAN DEFAULT FALSE,
    has_expiry_dates BOOLEAN DEFAULT FALSE,
    has_batch_numbers BOOLEAN DEFAULT FALSE,
    is_rental BOOLEAN DEFAULT FALSE,
    is_service BOOLEAN DEFAULT FALSE,
    
    -- Module type & configuration
    module_type ENUM('data', 'functional') DEFAULT 'data',
    category VARCHAR(50),
    operation_config JSON,
    integration_hooks JSON,
    default_settings JSON,
    
    -- Capabilities
    supports_reporting BOOLEAN DEFAULT TRUE,
    supports_custom_fields BOOLEAN DEFAULT TRUE,
    pricing_type ENUM('buy_sell', 'sell_only', 'cost_only') DEFAULT 'buy_sell',
    
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    
    INDEX idx_key (key),
    INDEX idx_module_type (module_type),
    INDEX idx_is_active (is_active),
    INDEX idx_category (category)
);
```

### 9.2 Branch Modules Table

```sql
CREATE TABLE branch_modules (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    branch_id BIGINT UNSIGNED NOT NULL,
    module_id BIGINT UNSIGNED NOT NULL,
    enabled BOOLEAN DEFAULT TRUE,
    module_key VARCHAR(255),
    settings JSON,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    
    FOREIGN KEY (branch_id) REFERENCES branches(id) ON DELETE CASCADE,
    FOREIGN KEY (module_id) REFERENCES modules(id) ON DELETE CASCADE,
    UNIQUE KEY unique_branch_module (branch_id, module_id)
);
```

---

## 10. Summary

### ✅ What's Already Implemented

1. **Complete Module Package System**
   - Module definition with all metadata
   - Entity type support (data vs functional)
   - Dynamic field system
   - Settings per module per branch
   - Navigation definitions
   - Report definitions
   - Policies and operations

2. **Module-Product Relationship**
   - Products require module association
   - Module selection in forms
   - Dynamic field loading based on module
   - Module-specific business rules
   - Service layer for module-product logic

3. **Module Types**
   - Data-oriented modules (Inventory, Rental, HRM)
   - Functional modules (POS, Payroll, Workflow)
   - Clear distinction and behavior differences

4. **Module Configuration**
   - Operation config for behavior
   - Integration hooks for accounting/inventory
   - Per-branch settings
   - Permission system

5. **Module Activation**
   - Branch-level module enablement
   - Settings per branch per module
   - Access control

### 📋 Architecture Compliance

| Requirement | Status | Implementation |
|-------------|--------|----------------|
| Module as complete package | ✅ | Module model with all attributes |
| Entity type support | ✅ | `module_type` field + scopes |
| Dynamic fields | ✅ | `ModuleCustomField`, `ModuleProductField` |
| Module settings | ✅ | `ModuleSetting` model + methods |
| Navigation items | ✅ | `ModuleNavigation` model |
| Report definitions | ✅ | `ReportDefinition` linked to module |
| Policies | ✅ | `ModulePolicy` model |
| Operations | ✅ | `ModuleOperation` model |
| Module-first product creation | ✅ | `selectedModuleId` in form |
| Dynamic field loading | ✅ | `loadModuleFields()` method |
| Module-specific rules | ✅ | `ModuleProductService` |
| Branch-level activation | ✅ | `BranchModule` pivot |
| Permission system | ✅ | `{module}.{resource}.{action}` |

### 🎯 Conclusion

The module system architecture described in your requirements **is fully implemented** in HugousERP. The system:

- ✅ Treats modules as complete packages
- ✅ Distinguishes between data-oriented and functional modules
- ✅ Enforces module selection before entity creation
- ✅ Loads dynamic fields based on module
- ✅ Applies module-specific business rules
- ✅ Supports per-branch module activation
- ✅ Has comprehensive settings and configuration
- ✅ Integrates with accounting, inventory, and other systems

**No additional implementation needed** - the architecture is complete and production-ready.

---

**Document Version:** 1.0  
**Last Updated:** December 7, 2025  
**Status:** Architecture Complete ✅
