# Consolidated Database Migrations

This directory contains consolidated migrations optimized for **MySQL 8.4+** with improved performance, proper indexing, and better organization.

## Migration Files

| File | Description | Tables |
|------|-------------|--------|
| `000001_create_core_tables.php` | Core system tables | cache, jobs, sessions, branches, users, currencies |
| `000002_create_permissions_and_modules_tables.php` | Permissions & modules | roles, permissions, modules, module_* |
| `000003_create_inventory_tables.php` | Inventory management | products, categories, warehouses, stock_movements |
| `000004_create_crm_tables.php` | CRM | customers, suppliers, attachments, notes |
| `000005_create_sales_purchases_tables.php` | Sales & Purchases | sales, purchases, GRN, returns |
| `000006_create_hr_payroll_tables.php` | HR & Payroll | employees, attendance, payroll, shifts |
| `000007_create_accounting_tables.php` | Accounting | accounts, journal_entries, bank_*, expenses |
| `000008_create_pos_retail_tables.php` | POS & Retail | pos_sessions, stores, loyalty |
| `000009_create_manufacturing_tables.php` | Manufacturing | BOM, production_orders, work_centers |
| `000010_create_rental_tables.php` | Rental | properties, units, vehicles, contracts |
| `000011_create_projects_documents_support_tables.php` | Projects & Support | projects, documents, tickets |
| `000012_create_audit_notification_analytics_tables.php` | Analytics & Audit | audit_logs, reports, workflows |

## MySQL 8.4 Optimizations

### Character Set & Collation
- Uses `utf8mb4_0900_ai_ci` collation (MySQL 8.0+ optimized)
- Full Unicode support including emojis
- Better sorting performance than legacy collations

### Index Strategies
1. **Primary Keys**: All tables use auto-incrementing `BIGINT` primary keys
2. **Foreign Keys**: Proper cascading with `ON DELETE` rules
3. **Composite Indexes**: For common query patterns
4. **Full-Text Indexes**: For searchable text fields
5. **Covering Indexes**: For frequently accessed column combinations

### JSON Columns
- Used for flexible data: `settings`, `custom_fields`, `metadata`
- MySQL 8.4 JSON functions for efficient querying

## How to Use

### Fresh Installation
```bash
# Move to consolidated migrations
mv database/migrations database/migrations_old
mv database/migrations_consolidated database/migrations

# Run migrations
php artisan migrate:fresh
```

### Existing Database
⚠️ **Warning**: Requires careful planning and data migration scripts.

```bash
# 1. Backup existing database
mysqldump -u root -p database_name > backup.sql

# 2. Export data using custom scripts
php artisan db:export-data

# 3. Apply new schema
php artisan migrate:fresh --force

# 4. Import data
php artisan db:import-data
```

## Performance Recommendations

### MySQL 8.4 Configuration
```ini
[mysqld]
# InnoDB settings
innodb_buffer_pool_size = 1G
innodb_log_file_size = 256M
innodb_flush_log_at_trx_commit = 2
innodb_flush_method = O_DIRECT

# Query optimization
optimizer_switch = 'index_merge_intersection=on'
optimizer_trace_max_mem_size = 1048576

# Full-text search
ft_min_word_len = 2
innodb_ft_min_token_size = 2
```

### Laravel Configuration
```php
// config/database.php
'mysql' => [
    'driver' => 'mysql',
    'strict' => true,
    'engine' => 'InnoDB',
    'options' => [
        PDO::ATTR_PERSISTENT => true,
        PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true,
    ],
],
```

## Table Count Summary

- **Total Tables**: 171
- **Consolidated Files**: 12
- **Original Files**: 121

## Migration Dependencies

```
000001 (Core)
    ├── 000002 (Permissions & Modules)
    ├── 000003 (Inventory) → depends on 000002
    ├── 000004 (CRM) → depends on 000003
    ├── 000005 (Sales/Purchases) → depends on 000003, 000004
    ├── 000006 (HR) → depends on 000001
    ├── 000007 (Accounting) → depends on 000003
    ├── 000008 (POS) → depends on 000004, 000005
    ├── 000009 (Manufacturing) → depends on 000003
    ├── 000010 (Rental) → depends on 000002, 000004
    ├── 000011 (Projects) → depends on 000004, 000007
    └── 000012 (Analytics) → depends on all above
```

## Notes

1. All timestamps use MySQL's native `TIMESTAMP` type for timezone awareness
2. Soft deletes are implemented where business logic requires audit trails
3. Branch-scoping is applied to multi-tenant tables
4. JSON columns replace multiple one-to-many relationships for flexible data
