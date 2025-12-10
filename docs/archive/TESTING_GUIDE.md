# ERP Testing Guide - Database Compatibility & Performance

This guide helps you test the ERP system across different database engines and verify all fixes are working correctly.

## Prerequisites

- PHP 8.2+
- Composer dependencies installed
- Laravel configured
- Test databases ready (MySQL 8.4, PostgreSQL 15+, SQLite)

## Database Compatibility Testing

### 1. MySQL 8.4+ Testing

```bash
# In .env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=hugouserp_test
DB_USERNAME=root
DB_PASSWORD=your_password

# Run migrations
php artisan migrate:fresh --seed

# Key queries to test:
# - Dashboard statistics
# - Low stock products
# - Sales analytics
# - Warehouse stock totals
# - Payment method breakdowns
```

**Verify:**
- No `SQLSTATE[42S22]` errors (unknown column)
- No `SQLSTATE[42000]` errors (GROUP BY issues)
- All aggregates work correctly
- Stock calculations match expectations

### 2. PostgreSQL Testing

```bash
# In .env
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=hugouserp_test
DB_USERNAME=postgres
DB_PASSWORD=your_password

# Run migrations
php artisan migrate:fresh --seed

# Key queries to test:
# - Same as MySQL above
# - Pay special attention to:
#   - Date/time functions
#   - Case sensitivity
#   - GROUP BY requirements (all non-aggregated columns)
```

**Common PostgreSQL Issues (All Fixed):**
- ✅ `extract(month from ...)` replaced with `whereMonth()`
- ✅ `"table"."column"` quoting handled by Laravel
- ✅ GROUP BY includes all selected non-aggregated columns
- ✅ `ILIKE` not used (Laravel's `like` works everywhere)

### 3. SQLite Testing

```bash
# In .env
DB_CONNECTION=sqlite
DB_DATABASE=/path/to/database.sqlite

# Create empty database
touch database/database.sqlite

# Run migrations
php artisan migrate:fresh --seed

# SQLite is most restrictive, so if it works here, it likely works everywhere
```

## Performance Testing Checklist

### Dashboard (`/dashboard`)

**Expected:**
- ✅ Page loads in < 2 seconds
- ✅ All statistics cached (300s TTL)
- ✅ Low stock query uses StockService
- ✅ Charts load without delay

**Test:**
```bash
# Check query count
php artisan debugbar:clear
# Visit /dashboard
# Check debugbar for N+1 queries
```

### Warehouse (`/app/warehouse`)

**Expected:**
- ✅ Statistics cached (300s TTL)
- ✅ Stock calculations use `direction` field
- ✅ Pagination works (15 items per page)

**Test:**
```sql
-- Verify stock_movements uses direction correctly
SELECT direction, COUNT(*) 
FROM stock_movements 
GROUP BY direction;
-- Should show 'in' and 'out' values
```

### Sales Analytics (`/app/sales/analytics`)

**Expected:**
- ✅ Uses `product_categories` table (not categories)
- ✅ Payment method uses `payment_method` column
- ✅ Date grouping works on all DB engines
- ✅ No DB-specific SQL unless wrapped with driver detection

**Test:**
```php
// Check SalesAnalytics.php lines 176-219
// Verify driver detection for date formatting:
$driver = DB::getDriverName();
$isPostgres = $driver === 'pgsql';
```

### Inventory Reports (`/admin/reports/inventory`)

**Expected:**
- ✅ Uses StockService for current_stock
- ✅ No direct `products.current_stock` column access
- ✅ All stock calculations via CASE expressions

**Test:**
```bash
# Search for any direct current_stock access
grep -r "products.current_stock" app/Livewire/
# Should return no results
```

### Purchase Returns (`/app/purchases/returns`)

**Expected:**
- ✅ Fast page load (< 1 second)
- ✅ Returns paginated (15 per page)
- ✅ No unnecessary eager loading

**Test before fix:**
- Loaded 50 purchases on every page load (slow)

**Test after fix:**
- Only loads return notes with pagination
- Purchases loaded lazily when opening modal

### Translation Manager (`/admin/settings?tab=translations`)

**Expected:**
- ✅ Paginated (50 translations per page)
- ✅ Search works across all fields
- ✅ Add/Edit modal forms work
- ✅ Fast load even with 1000+ translations

**Test:**
```bash
# Add 500 test translations
php artisan tinker
> for ($i = 0; $i < 500; $i++) {
>   $key = "test.key.$i";
>   $translations = json_decode(File::get(lang_path('ar.json')), true);
>   $translations[$key] = "Arabic $i";
>   File::put(lang_path('ar.json'), json_encode($translations, JSON_UNESCAPED_UNICODE));
> }

# Visit /admin/settings?tab=translations
# Should load quickly with pagination
```

### Expenses (`/app/expenses/create`)

**Expected:**
- ✅ "Add Category" link opens `/app/expenses/categories` in new tab
- ✅ Category dropdown populates correctly
- ✅ Form submission works

**Test:**
- Click "Add Category" link
- Verify it opens the correct route
- Add a category
- Return to expense form
- Refresh and verify new category appears

## Query Compatibility Verification

### Stock Calculations

All stock calculations should use StockService:

```php
// ✅ CORRECT - Portable across all DBs
$stockExpr = StockService::getStockCalculationExpression();
$product->selectRaw("{$stockExpr} as current_stock");

// ❌ WRONG - Not portable
DB::raw("products.current_stock") // Column doesn't exist
DB::raw("products.quantity") // Column doesn't exist
```

### Date Filtering

```php
// ✅ CORRECT - Works on MySQL, PostgreSQL, SQLite
$query->whereMonth('created_at', $month);
$query->whereYear('created_at', $year);

// ❌ WRONG - PostgreSQL-specific
DB::raw("extract(month from created_at) = ?", [$month])
```

### GROUP BY Clauses

```php
// ✅ CORRECT - PostgreSQL compatible
->select(['products.id', 'products.name', 'products.sku'])
->selectRaw('SUM(qty) as total_qty')
->groupBy('products.id', 'products.name', 'products.sku');

// ❌ WRONG - Fails on PostgreSQL with ONLY_FULL_GROUP_BY
->select(['products.id', 'products.name', 'products.sku'])
->selectRaw('SUM(qty) as total_qty')
->groupBy('products.id'); // Missing name and sku!
```

### Column References

```php
// ✅ CORRECT
->where('stock_movements.direction', 'in');
->where('sale_payments.payment_method', 'cash');
->where('product_categories.name', 'like', '%search%');

// ❌ WRONG - Columns don't exist
->where('stock_movements.type', 'in'); // Should be 'direction'
->where('sale_payments.method', 'cash'); // Should be 'payment_method'
->where('categories.name', 'like', '%search%'); // Should be 'product_categories'
```

## Known Working Queries

These queries have been verified to work on all three DB engines:

1. **Dashboard low stock:**
```php
whereRaw('COALESCE((SELECT SUM(CASE WHEN direction = \'in\' THEN qty ELSE -qty END) 
          FROM stock_movements WHERE stock_movements.product_id = products.id), 0) <= min_stock')
```

2. **Warehouse stock totals:**
```php
StockMovement::where('direction', 'in')->sum('qty') - 
StockMovement::where('direction', 'out')->sum('qty')
```

3. **Sales by category:**
```php
SaleItem::join('sales', 'sale_items.sale_id', '=', 'sales.id')
    ->join('products', 'sale_items.product_id', '=', 'products.id')
    ->leftJoin('product_categories', 'products.category_id', '=', 'product_categories.id')
    ->select('product_categories.name')
    ->selectRaw('SUM(sale_items.line_total) as revenue')
    ->groupBy('product_categories.name')
```

## Regression Testing

After any changes, run these critical paths:

1. **Dashboard load:** Should show all stats without errors
2. **Create sale:** Should work with stock updates
3. **Create purchase:** Should work with stock updates
4. **Stock alerts:** Should calculate correctly
5. **Sales reports:** Should group by categories correctly
6. **Warehouse movements:** Should filter by direction
7. **Expense with category:** Should allow adding category

## Performance Benchmarks

**Target Performance (production with cache enabled):**

- Dashboard: < 1s (with 10k+ products)
- Warehouse index: < 1s
- Sales analytics: < 2s (for 1 month of data)
- Inventory charts: < 2s
- Purchase returns: < 500ms
- Translation manager: < 500ms (with 1000+ translations)

**Query Counts (ideal):**

- Dashboard: < 15 queries (with caching)
- List pages: < 10 queries (with pagination)
- Detail pages: < 8 queries (with eager loading)

## Troubleshooting

### SQLSTATE[42S22]: Column not found

**Check:**
- Is the column name spelled correctly?
- Does the column exist in the migration?
- Are you using the right table name?

**Common fixes:**
- `type` → `direction` (stock_movements)
- `method` → `payment_method` (sale_payments)
- `quantity` → Use StockService
- `categories` → `product_categories`

### SQLSTATE[42803]: Must appear in GROUP BY

**Fix:**
Add all non-aggregated selected columns to GROUP BY:

```php
// Before
->select(['id', 'name', 'sku'])
->selectRaw('COUNT(*) as count')
->groupBy('id');

// After
->select(['id', 'name', 'sku'])
->selectRaw('COUNT(*) as count')
->groupBy('id', 'name', 'sku');
```

### Slow queries

**Check:**
1. Missing indexes on foreign keys
2. Missing pagination
3. N+1 queries (missing eager loading)
4. Unnecessary data loading

**Fix:**
- Add indexes in migrations
- Use `paginate()` instead of `get()`
- Use `with()` for relationships
- Use `select()` to limit columns

## Conclusion

This ERP system is now compatible with MySQL 8.4+, PostgreSQL 15+, and SQLite. All critical runtime errors have been fixed, and performance has been optimized through caching, pagination, and efficient queries.
