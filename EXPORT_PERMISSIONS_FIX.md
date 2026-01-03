# Export Permissions Fix - Documentation

## Problem Fixed

Users were receiving "403 Access Denied" errors when trying to access export functionality across various modules (sales, customers, suppliers, expenses, income, inventory, HRM). This was caused by:

1. **Missing Export Permissions**: Several modules had export functionality but no corresponding permissions in the database seeder
2. **Inadequate Role Definitions**: Non-Super Admin roles had minimal permissions and couldn't access basic features
3. **No Dashboard Access**: Most roles lacked `dashboard.view` permission, preventing users from accessing the system

## Solution Implemented

### 1. Added Missing Export Permissions

The following export and import permissions were added to `RolesAndPermissionsSeeder`:

- `customers.export` and `customers.import`
- `suppliers.export` and `suppliers.import`
- `expenses.export` and `expenses.import`
- `income.export` and `income.import`
- `inventory.products.export` and `inventory.products.import`
- `inventory.export` and `inventory.import`
- `hrm.employees.export` and `hrm.employees.import`

### 2. Enhanced Existing Roles

Updated four existing roles with better permission sets:

#### HR Manager
- Dashboard access
- Full HRM module permissions
- Employee export/import capabilities
- Attendance and payroll management

#### Rental Manager
- Dashboard access
- Full rental management permissions
- Units, tenants, and contracts management
- Rental reports access

#### Inventory Manager
- Dashboard access
- Inventory viewing and reporting
- Product export/import capabilities
- POS offline report access

#### POS Cashier
- Dashboard access
- POS terminal usage
- Session management
- Daily reports and sales viewing

### 3. Created New Functional Roles

Added four new roles for common business scenarios:

#### Sales Manager (13 permissions)
- Dashboard and sales module access
- Customer management
- Sales export and returns
- Sales reports

**Use Case**: For users who manage sales operations and customer relationships.

#### Purchase Manager (13 permissions)
- Dashboard and purchase module access
- Supplier management
- Purchase export and returns
- Purchase reports

**Use Case**: For users who handle procurement and supplier relationships.

#### Accountant (12 permissions)
- Dashboard and accounting module access
- Expense and income management
- All export capabilities
- Financial reporting

**Use Case**: For users who handle financial transactions and accounting.

#### Manager (23 permissions)
- Dashboard access
- View and export across all major modules
- Comprehensive reporting access
- No create/edit/delete permissions (view-only management)

**Use Case**: For supervisors who need to monitor all operations without making changes.

### 4. Super Admin

The Super Admin role automatically receives **all 203 web permissions**, providing complete system access.

## Usage Instructions

### After Running Migrations

When you run `php artisan migrate:fresh --seed`, the system will automatically:

1. Create all required permissions (203 web permissions total)
2. Create 9 roles with appropriate permission assignments
3. Create a Super Admin user with full access

### Assigning Roles to Users

To assign a role to a user:

```php
$user = User::find($userId);
$user->assignRole('Sales Manager');
```

Available roles:
- `Super Admin` - Full system access
- `Sales Manager` - Sales and customer management
- `Purchase Manager` - Purchase and supplier management
- `Accountant` - Financial management
- `Manager` - View-only across all modules
- `HR Manager` - Human resources management
- `Rental Manager` - Rental property management
- `Inventory Manager` - Inventory and stock management
- `POS Cashier` - Point of sale operations

### Checking Permissions

To check if a user has a specific permission:

```php
// In blade templates
@can('sales.export')
    <button>Export Sales</button>
@endcan

// In controllers/components
$this->authorize('sales.export');

// In code
if ($user->can('sales.export')) {
    // Allow export
}
```

### Export Permissions by Module

| Module | View Permission | Export Permission |
|--------|----------------|------------------|
| Sales | `sales.view` | `sales.export` |
| Customers | `customers.view` | `customers.export` |
| Suppliers | `suppliers.view` | `suppliers.export` |
| Purchases | `purchases.view` | `purchases.export` |
| Expenses | `expenses.view` | `expenses.export` |
| Income | `income.view` | `income.export` |
| Inventory Products | `inventory.products.view` | `inventory.products.export` |
| Inventory | `inventory.view` | `inventory.export` |
| HRM Employees | `hrm.employees.view` | `hrm.employees.export` |
| Reports | `reports.view` | `reports.export` |

## Security Notes

1. **Export Endpoint Security**: The `/download/export` route only verifies that the authenticated user owns the export file. Permission checks happen when creating the export, not when downloading it.

2. **File Ownership**: Users can only download exports they created themselves. The system validates:
   - User ID matches the export creator
   - File path is within the allowed exports directory
   - File hasn't expired (5-minute expiration)
   - No path traversal attempts

3. **Permission Hierarchy**: Users with a role get ONLY the permissions explicitly assigned to that role. Super Admin gets all permissions automatically.

## Testing

All export authorization tests pass:

```bash
php artisan test tests/Feature/Web/ExportDownloadAuthorizationTest.php
```

Tests verify:
- ✅ Users without session data cannot download (404)
- ✅ Users can download their own exports (200)
- ✅ Users cannot download other users' exports (403)
- ✅ Path traversal attempts are rejected (403)
- ✅ Expired exports are rejected (410)

## Troubleshooting

### Users Can't Access Dashboard

**Solution**: Ensure the role has `dashboard.view` permission. All roles now include this by default.

### Users Can't Export Data

**Check**:
1. Does the user have the view permission for the module? (e.g., `sales.view`)
2. Does the user have the export permission? (e.g., `sales.export`)
3. Run: `php artisan permission:cache-reset` to clear permission cache

### 403 Error on Export Download

This is expected behavior in the following cases:
- User trying to download another user's export
- Invalid or malicious file path
- File has expired (>5 minutes old)

Check Laravel logs for specific error messages:
```bash
tail -f storage/logs/laravel.log
```

## Migration from Previous Setup

If you have existing users with old role assignments:

1. **Backup your database first**
2. Run the fresh migration with seeder: `php artisan migrate:fresh --seed`
3. Reassign roles to users as needed
4. Clear permission cache: `php artisan permission:cache-reset`

Note: Running `migrate:fresh` will delete all existing data. In production, you may want to run the seeder separately without dropping tables:

```bash
php artisan db:seed --class=RolesAndPermissionsSeeder
```

## Summary of Changes

| Change Type | Count | Details |
|------------|-------|---------|
| New Permissions | 12 | Export and import permissions for 6 modules |
| Enhanced Roles | 4 | HR Manager, Rental Manager, Inventory Manager, POS Cashier |
| New Roles | 4 | Sales Manager, Purchase Manager, Accountant, Manager |
| Total Permissions (Web) | 203 | Complete permission set |
| Total Roles (Web) | 9 | Comprehensive role coverage |

All roles now include `dashboard.view` as a baseline permission, and export permissions are properly defined for all modules that support export functionality.
