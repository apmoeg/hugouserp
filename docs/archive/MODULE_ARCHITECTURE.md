# Enhanced Module Architecture Documentation

## Overview

The HugousERP system has been enhanced with a comprehensive modular architecture that provides advanced capabilities for managing modules, their operations, policies, and navigation. This document outlines the new features and how to use them.

## Core Concepts

### 1. Module Types

Modules are now classified into three types:

- **Data Modules**: Core data entities (e.g., Products, Customers, Employees)
- **Functional Modules**: Business operations (e.g., POS, Sales, Accounting)
- **Hybrid Modules**: Combination of both (e.g., HRM, Rentals)

```php
// Creating a data-oriented module
$module = Module::create([
    'key' => 'inventory',
    'name' => 'Inventory Management',
    'module_type' => 'data',
    'supports_reporting' => true,
    'supports_custom_fields' => true,
]);

// Query modules by type
$dataModules = Module::dataOriented()->active()->get();
$functionalModules = Module::functional()->active()->get();
```

### 2. Module Policies

Policies define system-wide or branch-specific rules for module operations.

```php
use App\Models\ModulePolicy;

// Create a stock validation policy
ModulePolicy::create([
    'module_id' => $inventoryModule->id,
    'branch_id' => null, // Global policy
    'policy_key' => 'stock_validation',
    'policy_name' => 'Stock Validation Policy',
    'policy_rules' => [
        'check_stock_before_sale' => true,
        'allow_negative_stock' => false,
    ],
    'scope' => 'global',
    'is_active' => true,
    'priority' => 100,
]);

// Evaluate policy against context
$context = ['check_stock_before_sale' => true, 'allow_negative_stock' => false];
$isValid = $policy->evaluate($context);
```

### 3. Module Operations

Operations define what actions can be performed within a module.

```php
use App\Models\ModuleOperation;

// Define a create operation
ModuleOperation::create([
    'module_id' => $module->id,
    'operation_key' => 'create',
    'operation_name' => 'Create Product',
    'operation_type' => 'create',
    'required_permissions' => ['inventory.create', 'products.create'],
    'operation_config' => [
        'requires_approval' => false,
        'max_batch_size' => 100,
    ],
    'is_active' => true,
]);

// Check if user can execute operation
$canExecute = $operation->userCanExecute($user);

// Get operation configuration
$maxBatchSize = $operation->getConfig('max_batch_size', 50);
```

### 4. Module Navigation

Dynamic navigation structure based on modules and permissions.

```php
use App\Models\ModuleNavigation;

// Create parent navigation
$parentNav = ModuleNavigation::create([
    'module_id' => $module->id,
    'nav_key' => 'inventory_main',
    'nav_label' => 'Inventory',
    'nav_label_ar' => 'المخزون',
    'icon' => 'package',
    'required_permissions' => ['inventory.view'],
    'is_active' => true,
    'sort_order' => 10,
]);

// Create child navigation
ModuleNavigation::create([
    'module_id' => $module->id,
    'parent_id' => $parentNav->id,
    'nav_key' => 'inventory_products',
    'nav_label' => 'Products',
    'route_name' => 'products.index',
    'icon' => 'box',
    'required_permissions' => ['products.view'],
    'visibility_conditions' => [
        'module_enabled' => true,
    ],
    'is_active' => true,
    'sort_order' => 10,
]);

// Check user access
$hasAccess = $navigation->userHasAccess($user, $branchId);
```

## Enhanced Models

### Module Model Enhancements

New fields and methods:

```php
// New fields
$module->module_type          // 'data', 'functional', or 'hybrid'
$module->operation_config     // JSON configuration for operations
$module->integration_hooks    // External marketplace integrations
$module->supports_reporting   // Boolean flag
$module->supports_custom_fields // Boolean flag

// New relationships
$module->policies()           // HasMany ModulePolicy
$module->operations()         // HasMany ModuleOperation
$module->navigation()         // HasMany ModuleNavigation

// New methods
$module->isDataOriented()     // Check if data module
$module->isFunctional()       // Check if functional module
$module->getOperationConfig('key', 'default')
$module->getIntegrationHook('woocommerce')
```

### BranchModule Enhancements

Enhanced branch-module pivot with constraints and inheritance:

```php
// New fields
$branchModule->activation_constraints  // JSON rules for activation
$branchModule->permission_overrides    // Branch-specific permissions
$branchModule->inherit_settings        // Whether to inherit default settings
$branchModule->activated_at           // Timestamp of activation

// New methods
$branchModule->constraintsSatisfied($context)  // Check constraints
$branchModule->getEffectiveSettings()          // Get settings with inheritance
$branchModule->getPermissionOverride('permission')
```

### ModuleSetting Enhancements

Settings with scope and inheritance:

```php
// New fields
$setting->scope                    // 'global', 'branch', or 'user'
$setting->is_inherited            // Whether inherited from parent
$setting->inherited_from_setting_id // Parent setting ID
$setting->is_system               // System-managed setting
$setting->priority                // Resolution priority

// New relationships
$setting->inheritedFromSetting()  // BelongsTo ModuleSetting
$setting->childSettings()         // HasMany ModuleSetting

// New scopes
ModuleSetting::byScope('branch')
ModuleSetting::system()
ModuleSetting::nonSystem()
ModuleSetting::ordered()
```

### ModuleField Enhancements

Advanced field capabilities:

```php
// New fields
$field->field_category         // Categorize fields
$field->validation_rules       // Extended validation
$field->computed_config        // For computed fields
$field->is_system             // System field
$field->is_searchable         // Searchable field
$field->supports_bulk_edit    // Bulk editing support
$field->dependencies          // Field dependencies

// New methods
$field->hasDependencies()
$field->dependenciesSatisfied($context)
$field->getComputedValue($data)

// New scopes
ModuleField::byCategory('basic')
ModuleField::system()
ModuleField::custom()
ModuleField::searchable()
ModuleField::bulkEditable()
```

## Enhanced Services

### ModuleService New Methods

```php
use App\Services\ModuleService;

$moduleService = app(ModuleService::class);

// Get modules by type
$dataModules = $moduleService->getModulesByType('data', $branchId);

// Get navigation for user
$navigation = $moduleService->getNavigationForUser($user, $branchId);

// Check operation permission
$canPerform = $moduleService->userCanPerformOperation($user, 'inventory', 'create');

// Get active policies
$policies = $moduleService->getActivePolicies($moduleId, $branchId);
```

## Database Schema

### New Tables

1. **module_policies**: System policies per module
   - module_id, branch_id, policy_key, policy_name
   - policy_rules (JSON), scope, is_active, priority

2. **module_operations**: Operation mappings per module
   - module_id, operation_key, operation_name, operation_type
   - operation_config (JSON), required_permissions (JSON)

3. **module_navigation**: Sidebar navigation hierarchy
   - module_id, parent_id, nav_key, nav_label, route_name
   - required_permissions (JSON), visibility_conditions (JSON)

### Enhanced Tables

1. **modules**:
   - module_type, operation_config, integration_hooks
   - supports_reporting, supports_custom_fields

2. **branch_modules**:
   - activation_constraints, permission_overrides
   - inherit_settings, activated_at

3. **module_settings**:
   - scope, is_inherited, inherited_from_setting_id
   - is_system, priority

4. **module_fields**:
   - field_category, validation_rules, computed_config
   - is_system, is_searchable, supports_bulk_edit, dependencies

## Usage Examples

### Example 1: Creating a Module with Full Configuration

```php
// Create module
$module = Module::create([
    'key' => 'inventory',
    'name' => 'Inventory Management',
    'module_type' => 'data',
    'supports_reporting' => true,
    'supports_custom_fields' => true,
    'operation_config' => [
        'max_batch_size' => 1000,
        'auto_reorder' => true,
    ],
    'integration_hooks' => [
        'woocommerce' => true,
        'shopify' => true,
    ],
]);

// Add operations
$createOp = ModuleOperation::create([
    'module_id' => $module->id,
    'operation_key' => 'create',
    'operation_name' => 'Create Product',
    'operation_type' => 'create',
    'required_permissions' => ['inventory.create'],
]);

// Add policies
$policy = ModulePolicy::create([
    'module_id' => $module->id,
    'policy_key' => 'stock_validation',
    'policy_name' => 'Stock Validation',
    'policy_rules' => ['allow_negative' => false],
    'scope' => 'global',
]);

// Add navigation
$nav = ModuleNavigation::create([
    'module_id' => $module->id,
    'nav_key' => 'inventory_main',
    'nav_label' => 'Inventory',
    'icon' => 'package',
    'required_permissions' => ['inventory.view'],
]);
```

### Example 2: Branch-Specific Module Configuration

```php
// Enable module for branch with constraints
$branch->modules()->attach($module->id, [
    'module_key' => $module->key,
    'enabled' => true,
    'inherit_settings' => true,
    'activation_constraints' => [
        'requires_warehouse' => true,
        'min_staff_count' => 2,
    ],
    'permission_overrides' => [
        'inventory.create' => true,
        'inventory.delete' => false,
    ],
    'settings' => [
        'low_stock_threshold' => 10,
        'enable_serial_tracking' => true,
    ],
]);

// Check if constraints are satisfied
$branchModule = BranchModule::where('branch_id', $branch->id)
    ->where('module_id', $module->id)
    ->first();

$context = [
    'requires_warehouse' => true,
    'min_staff_count' => 3,
];

if ($branchModule->constraintsSatisfied($context)) {
    // Activate module
}
```

### Example 3: Dynamic Navigation Generation

```php
// Get navigation for logged-in user
$user = auth()->user();
$branchId = session('branch_id');

$navigation = $moduleService->getNavigationForUser($user, $branchId);

// Returns hierarchical navigation structure:
// [
//     [
//         'id' => 1,
//         'key' => 'inventory_main',
//         'label' => 'Inventory',
//         'route' => null,
//         'icon' => 'package',
//         'children' => [
//             [
//                 'id' => 2,
//                 'key' => 'inventory_products',
//                 'label' => 'Products',
//                 'route' => 'products.index',
//                 'icon' => 'box',
//                 'children' => [],
//             ],
//         ],
//     ],
// ]
```

### Example 4: Module Policy Enforcement

```php
// Get active policies for a module
$policies = $moduleService->getActivePolicies($inventoryModule->id, $branchId);

// Evaluate policies before operation
foreach ($policies as $policyData) {
    $policy = ModulePolicy::where('policy_key', $policyData['key'])->first();
    
    $context = [
        'check_stock_before_sale' => true,
        'stock_level' => 5,
    ];
    
    if (!$policy->evaluate($context)) {
        throw new \Exception("Policy violation: {$policy->policy_name}");
    }
}
```

## Integration with Existing Systems

### Permissions Integration

The enhanced module system integrates seamlessly with the existing Spatie Laravel Permission system:

```php
// Module operations reference permissions
$operation = ModuleOperation::where('operation_key', 'create')->first();
$canExecute = $operation->userCanExecute($user); // Checks required_permissions

// Navigation items check permissions
$nav = ModuleNavigation::where('nav_key', 'inventory_main')->first();
$hasAccess = $nav->userHasAccess($user, $branchId);
```

### Branch System Integration

Enhanced branch-module relationships provide fine-grained control:

```php
// Enable module for branch
$moduleService->enableForBranch($branch, 'inventory', [
    'low_stock_threshold' => 20,
]);

// Disable module for branch
$moduleService->disableForBranch($branch, 'inventory');

// Get branch module configuration
$config = $moduleService->getBranchModulesConfig($branch);
```

## Seeding Initial Data

Run the seeder to populate initial module architecture data:

```bash
php artisan db:seed --class=ModuleArchitectureSeeder
```

This will:
- Classify existing modules by type
- Create standard CRUD operations for all modules
- Add sample policies for inventory and sales modules
- Create navigation structure for key modules

## Best Practices

1. **Module Types**: Choose the appropriate type when creating modules
   - Use 'data' for entity-focused modules
   - Use 'functional' for operation-focused modules
   - Use 'hybrid' when both aspects are significant

2. **Policies**: Use policies for business rules that may vary by branch or context

3. **Operations**: Define clear operations with required permissions for access control

4. **Navigation**: Structure navigation hierarchically for better UX

5. **Settings Inheritance**: Enable settings inheritance for consistency across branches

6. **Constraints**: Use activation constraints to ensure prerequisites are met

## Migration Guide

For existing installations:

1. Run the migration:
   ```bash
   php artisan migrate
   ```

2. Run the seeder:
   ```bash
   php artisan db:seed --class=ModuleArchitectureSeeder
   ```

3. Review and adjust module types based on your needs

4. Configure branch-specific settings and constraints

5. Update navigation to use the new system

## Testing

Comprehensive tests are included:

```bash
# Run all module tests
php artisan test --filter=Module

# Run specific test suites
php artisan test --filter=ModulePolicyTest
php artisan test --filter=ModuleOperationTest
php artisan test --filter=ModuleNavigationTest
php artisan test --filter=EnhancedModuleServiceTest
```

## Future Enhancements

Planned improvements include:

- Advanced policy rule engine with complex conditions
- Visual policy builder
- Module marketplace integration
- Dynamic module loading
- Module versioning and updates
- Enhanced formula evaluation for computed fields
- Module dependencies and installation workflows

## Support

For questions or issues related to the enhanced module architecture, please refer to the main documentation or contact the development team.
