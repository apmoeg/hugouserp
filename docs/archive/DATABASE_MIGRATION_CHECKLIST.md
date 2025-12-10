# Database Migration Checklist

This document lists all database schema issues that were identified and how they were resolved.

## Issues Found & Fixed

### 1. Stock Movements - Column Name Mismatch ✅ FIXED

**Issue:**
- Code referenced `stock_movements.type` 
- Actual column name is `direction`

**Values:**
- `'in'` - Stock incoming (purchases, returns, adjustments)
- `'out'` - Stock outgoing (sales, transfers, adjustments)

**Fixed In:**
- ✅ `app/Livewire/Warehouse/Index.php` - Already uses `direction`
- ✅ `app/Models/StockMovement.php` - Has `scopeIn()` and `scopeOut()`
- ✅ `app/Services/StockService.php` - All calculations use `direction`

**Migration:**
- No migration needed - column already exists with correct name

---

### 2. Sale Payments - Column Name Mismatch ✅ FIXED

**Issue:**
- Code referenced `sale_payments.method`
- Actual column name is `payment_method`

**Fixed In:**
- ✅ `app/Livewire/Dashboard/Index.php` - Line 158 uses `payment_method`
- ✅ `app/Livewire/Reports/SalesAnalytics.php` - Line 291 uses `payment_method`

**Migration:**
- No migration needed - column already exists with correct name

---

### 3. Products - Non-existent Quantity Column ✅ FIXED

**Issue:**
- Code tried to access `products.quantity`
- Products table doesn't have a quantity column
- Stock is calculated from `stock_movements` table

**Solution:**
- Use `StockService::getStockCalculationExpression()` for queries
- Use `StockService::getCurrentStock($productId)` for single product

**Fixed In:**
- ✅ `app/Livewire/Dashboard/Index.php` - Uses StockService
- ✅ `app/Livewire/Admin/Reports/InventoryChartsDashboard.php` - Uses StockService
- ✅ All dashboard and report queries

**Migration:**
- No migration needed - using calculated stock is the correct approach

---

### 4. Product Categories - Table Name ✅ FIXED

**Issue:**
- Code joined to `categories` table
- Actual table name is `product_categories`

**Fixed In:**
- ✅ `app/Livewire/Reports/SalesAnalytics.php` - Line 345 uses `product_categories`
- ✅ All sales analytics queries

**Migration:**
- No migration needed - table already exists with correct name

---

### 5. Branches - Non-existent name_ar Column ✅ NOT NEEDED

**Issue:**
- Some code referenced `branches.name_ar`
- Branches table only has `name` column

**Status:**
- `branches` table doesn't have `name_ar` in the database
- `app/Models/Branch.php` doesn't include `name_ar` in `$fillable`
- Only `WorkCenter` and `ProductCategory` have `name_ar` fields

**Fixed In:**
- No database queries actually select `branches.name_ar`
- Only one reference in `app/Livewire/Admin/Branches/Modules.php` which correctly references module's `name_ar`, not branch

**Migration:**
- No migration needed - branches don't need Arabic names

---

### 6. Work Centers - Column Name ✅ CORRECT

**Issue Reported:**
- Code supposedly used `capacity_per_day`
- Should be `capacity_per_hour`

**Actual Status:**
- ✅ `app/Models/WorkCenter.php` - Has `capacity_per_hour` in fillable
- ✅ `app/Livewire/Manufacturing/WorkCenters/Index.php` - Line 65 uses `capacity_per_hour`
- No references to `capacity_per_day` found in codebase

**Migration:**
- No migration needed - already correct

---

### 7. Products - Current Stock Column ⚠️ SAFETY MIGRATION EXISTS

**Status:**
- A "safety" migration added `current_stock` column to products table
- However, the application correctly uses StockService instead
- The column is not actively maintained or updated

**Recommendation:**
- Keep using StockService (calculation-based approach)
- Consider removing `current_stock` column if it causes confusion
- If kept, add triggers or observers to maintain it

**Current Approach:**
- ✅ All stock queries use StockService
- ✅ No direct access to `products.current_stock`
- ✅ Stock always calculated from `stock_movements`

---

## PostgreSQL Compatibility Issues ✅ ALL FIXED

### Issue: EXTRACT() Function

**Problem:**
```sql
extract(month from "sales"."created_at")
```

**Solution:**
```php
->whereMonth('sales.created_at', $month)
->whereYear('sales.created_at', $year)
```

**Fixed In:**
- ✅ All date filtering uses Laravel helpers
- ✅ `SalesAnalytics.php` uses driver detection for date formatting

---

### Issue: GROUP BY Requirements

**Problem:**
PostgreSQL requires all non-aggregated SELECT columns in GROUP BY

**Solution:**
```php
// Add all selected columns to groupBy
->select(['products.id', 'products.name', 'products.sku'])
->selectRaw('SUM(qty) as total_qty')
->groupBy('products.id', 'products.name', 'products.sku')
```

**Fixed In:**
- ✅ `Dashboard/Index.php` - All aggregation queries
- ✅ `Reports/SalesAnalytics.php` - All grouped queries
- ✅ `Warehouse/Index.php` - Statistics queries

---

## Migration Safety Checks

All migrations must pass these checks:

### 1. Column Existence
```bash
# Before adding/modifying column, check if it exists
if (Schema::hasColumn('table', 'column')) {
    // Modify
} else {
    // Add
}
```

### 2. Table Existence
```bash
if (Schema::hasTable('table')) {
    // Proceed
}
```

### 3. Index Safety
```php
// Use helper methods to check before adding/dropping
private function indexExists(string $table, string $index): bool
```

### 4. Foreign Key Safety
```php
// Check before dropping foreign key
private function foreignKeyExists(string $table, string $fk): bool
```

---

## Current Database State

### Verified Tables & Columns

**stock_movements:**
- ✅ `direction` (values: 'in', 'out')
- ✅ `product_id`
- ✅ `qty`
- ✅ `warehouse_id`

**sale_payments:**
- ✅ `payment_method` (not `method`)
- ✅ `amount`
- ✅ `sale_id`

**products:**
- ✅ `id`, `name`, `sku`, `barcode`
- ✅ `category_id` (references product_categories)
- ✅ `min_stock`, `reorder_point`
- ❌ No `quantity` column (correct - use stock_movements)
- ⚠️ `current_stock` (exists but not used)

**product_categories:**
- ✅ `id`, `name`, `name_ar`
- ✅ `parent_id`, `slug`
- ✅ `is_active`

**branches:**
- ✅ `id`, `name`, `code`
- ✅ `is_active`
- ❌ No `name_ar` column (correct - not needed)

**work_centers:**
- ✅ `capacity_per_hour` (not `capacity_per_day`)
- ✅ `cost_per_hour`
- ✅ `name`, `name_ar`

---

## Recommended Migrations

### Optional: Remove Unused Columns

If `products.current_stock` is confirmed unused:

```php
public function up()
{
    if (Schema::hasColumn('products', 'current_stock')) {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('current_stock');
        });
    }
}
```

### Optional: Add Database Indexes

For better performance:

```php
public function up()
{
    Schema::table('stock_movements', function (Blueprint $table) {
        $table->index(['product_id', 'direction']);
        $table->index(['warehouse_id', 'created_at']);
    });
    
    Schema::table('sale_payments', function (Blueprint $table) {
        $table->index('payment_method');
    });
    
    Schema::table('sales', function (Blueprint $table) {
        $table->index(['branch_id', 'created_at']);
        $table->index('status');
    });
}
```

---

## Testing Queries

### Verify Stock Movements
```sql
SELECT direction, COUNT(*) as count, SUM(qty) as total
FROM stock_movements
GROUP BY direction;
```

Expected: Shows 'in' and 'out' with counts

### Verify Sale Payments
```sql
SELECT payment_method, COUNT(*) as count, SUM(amount) as total
FROM sale_payments
GROUP BY payment_method;
```

Expected: Shows cash, bank_transfer, card, etc.

### Verify Product Categories
```sql
SELECT pc.name, COUNT(p.id) as product_count
FROM product_categories pc
LEFT JOIN products p ON p.category_id = pc.id
GROUP BY pc.id, pc.name;
```

Expected: Shows all categories with product counts

### Verify Stock Calculation
```sql
SELECT 
    p.id,
    p.name,
    COALESCE(
        (SELECT SUM(CASE WHEN direction = 'in' THEN qty ELSE -qty END)
         FROM stock_movements
         WHERE product_id = p.id),
        0
    ) as current_stock
FROM products p
LIMIT 10;
```

Expected: Shows products with calculated stock

---

## Conclusion

All critical database issues have been resolved:

1. ✅ Column name mismatches fixed
2. ✅ Non-existent columns removed from queries
3. ✅ PostgreSQL compatibility ensured
4. ✅ Stock calculations use service layer
5. ✅ All queries tested on MySQL, PostgreSQL, SQLite

The database schema is now stable and all application code references correct column names.
