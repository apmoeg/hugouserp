<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Database Integrity Check Command
 *
 * Validates database schema, indexes, foreign keys, and data integrity.
 */
class CheckDatabaseIntegrity extends Command
{
    protected $signature = 'db:check-integrity {--fix : Attempt to fix issues automatically}';

    protected $description = 'Check database integrity including indexes, foreign keys, and data consistency';

    private array $issues = [];

    private array $warnings = [];

    private array $fixes = [];

    public function handle(): int
    {
        $this->info('Starting database integrity check...');
        $this->newLine();

        $this->checkTables();
        $this->checkIndexes();
        $this->checkForeignKeys();
        $this->checkDataIntegrity();

        $this->displayResults();

        if ($this->option('fix') && ! empty($this->fixes)) {
            $this->applyFixes();
        }

        return empty($this->issues) ? Command::SUCCESS : Command::FAILURE;
    }

    private function checkTables(): void
    {
        $this->info('🔍 Checking tables...');

        $requiredTables = [
            'users', 'branches', 'products', 'customers', 'suppliers',
            'sales', 'purchases', 'stock_movements', 'audit_logs',
        ];

        foreach ($requiredTables as $table) {
            if (! Schema::hasTable($table)) {
                $this->issues[] = "Missing required table: {$table}";
            }
        }

        $this->info('✓ Tables check completed');
    }

    private function checkIndexes(): void
    {
        $this->info('🔍 Checking indexes...');

        $indexChecks = [
            'sales' => ['customer_id', 'branch_id', 'sale_date', 'status'],
            'purchases' => ['supplier_id', 'branch_id', 'purchase_date', 'status'],
            'products' => ['branch_id', 'sku', 'status', 'category_id'],
            'stock_movements' => ['product_id', 'warehouse_id', 'movement_date'],
            'customers' => ['branch_id', 'email', 'phone'],
            'suppliers' => ['branch_id', 'email'],
        ];

        foreach ($indexChecks as $table => $columns) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            $tableIndexes = $this->getTableIndexes($table);

            foreach ($columns as $column) {
                if (! $this->hasIndexOnColumn($tableIndexes, $column)) {
                    $this->warnings[] = "Missing index on {$table}.{$column}";
                    $this->fixes[] = "ALTER TABLE {$table} ADD INDEX idx_{$column} ({$column})";
                }
            }
        }

        $this->info('✓ Indexes check completed');
    }

    private function checkForeignKeys(): void
    {
        $this->info('🔍 Checking foreign keys...');

        $foreignKeyChecks = [
            'sales' => [
                'customer_id' => 'customers',
                'branch_id' => 'branches',
            ],
            'purchases' => [
                'supplier_id' => 'suppliers',
                'branch_id' => 'branches',
            ],
            'products' => [
                'branch_id' => 'branches',
                'category_id' => 'product_categories',
            ],
            'sale_items' => [
                'sale_id' => 'sales',
                'product_id' => 'products',
            ],
            'purchase_items' => [
                'purchase_id' => 'purchases',
                'product_id' => 'products',
            ],
        ];

        foreach ($foreignKeyChecks as $table => $foreignKeys) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            foreach ($foreignKeys as $column => $referencedTable) {
                if (! Schema::hasTable($referencedTable)) {
                    continue;
                }

                $orphans = $this->findOrphanedRecords($table, $column, $referencedTable);

                if ($orphans > 0) {
                    $this->issues[] = "Found {$orphans} orphaned records in {$table}.{$column}";
                }
            }
        }

        $this->info('✓ Foreign keys check completed');
    }

    private function checkDataIntegrity(): void
    {
        $this->info('🔍 Checking data integrity...');

        // Check for duplicate emails in customers
        $this->checkDuplicates('customers', 'email', "email IS NOT NULL AND email != ''");

        // Check for duplicate SKUs in products
        $this->checkDuplicates('products', 'sku', "sku IS NOT NULL AND sku != ''");

        // Check for negative stock quantities
        $negativeStock = DB::table('products')
            ->where('stock_quantity', '<', 0)
            ->count();

        if ($negativeStock > 0) {
            $this->warnings[] = "Found {$negativeStock} products with negative stock";
        }

        // Check for sales with no items
        if (Schema::hasTable('sales') && Schema::hasTable('sale_items')) {
            $salesWithoutItems = DB::table('sales')
                ->leftJoin('sale_items', 'sales.id', '=', 'sale_items.sale_id')
                ->whereNull('sale_items.id')
                ->count();

            if ($salesWithoutItems > 0) {
                $this->issues[] = "Found {$salesWithoutItems} sales with no items";
            }
        }

        // Check for inconsistent totals
        $this->checkSaleTotals();

        $this->info('✓ Data integrity check completed');
    }

    private function checkDuplicates(string $table, string $column, string $where = ''): void
    {
        if (! Schema::hasTable($table)) {
            return;
        }

        $query = DB::table($table)
            ->select($column, DB::raw('COUNT(*) as count'))
            ->groupBy($column)
            ->having('count', '>', 1);

        if ($where) {
            $query->whereRaw($where);
        }

        $duplicates = $query->get();

        if ($duplicates->isNotEmpty()) {
            $this->warnings[] = "Found {$duplicates->count()} duplicate {$column} values in {$table}";
        }
    }

    private function checkSaleTotals(): void
    {
        if (! Schema::hasTable('sales') || ! Schema::hasTable('sale_items')) {
            return;
        }

        $inconsistentSales = DB::table('sales')
            ->select('sales.id', 'sales.total_amount', DB::raw('SUM(sale_items.qty * sale_items.price) as calculated_total'))
            ->leftJoin('sale_items', 'sales.id', '=', 'sale_items.sale_id')
            ->groupBy('sales.id', 'sales.total_amount')
            ->havingRaw('ABS(sales.total_amount - COALESCE(SUM(sale_items.qty * sale_items.price), 0)) > 0.01')
            ->count();

        if ($inconsistentSales > 0) {
            $this->warnings[] = "Found {$inconsistentSales} sales with inconsistent totals";
        }
    }

    private function getTableIndexes(string $table): array
    {
        try {
            $indexes = DB::select("SHOW INDEX FROM {$table}");

            return array_map(function ($index) {
                return [
                    'name' => $index->Key_name,
                    'column' => $index->Column_name,
                ];
            }, $indexes);
        } catch (\Exception $e) {
            return [];
        }
    }

    private function hasIndexOnColumn(array $indexes, string $column): bool
    {
        foreach ($indexes as $index) {
            if ($index['column'] === $column) {
                return true;
            }
        }

        return false;
    }

    private function findOrphanedRecords(string $table, string $column, string $referencedTable): int
    {
        try {
            return DB::table($table)
                ->leftJoin($referencedTable, "{$table}.{$column}", '=', "{$referencedTable}.id")
                ->whereNotNull("{$table}.{$column}")
                ->whereNull("{$referencedTable}.id")
                ->count();
        } catch (\Exception $e) {
            return 0;
        }
    }

    private function displayResults(): void
    {
        $this->newLine();
        $this->line('═══════════════════════════════════════════════════════');
        $this->info('📊 DATABASE INTEGRITY CHECK RESULTS');
        $this->line('═══════════════════════════════════════════════════════');
        $this->newLine();

        if (empty($this->issues) && empty($this->warnings)) {
            $this->info('✅ No issues found! Database integrity is good.');
        } else {
            if (! empty($this->issues)) {
                $this->error('❌ ISSUES FOUND:');
                foreach ($this->issues as $issue) {
                    $this->line("  • {$issue}");
                }
                $this->newLine();
            }

            if (! empty($this->warnings)) {
                $this->warn('⚠️  WARNINGS:');
                foreach ($this->warnings as $warning) {
                    $this->line("  • {$warning}");
                }
                $this->newLine();
            }

            if (! empty($this->fixes) && ! $this->option('fix')) {
                $this->info('💡 TIP: Run with --fix option to attempt automatic fixes');
            }
        }

        $this->line('═══════════════════════════════════════════════════════');
    }

    private function applyFixes(): void
    {
        $this->newLine();
        $this->info('🔧 Attempting to fix issues...');

        $fixed = 0;
        foreach ($this->fixes as $fix) {
            try {
                DB::statement($fix);
                $fixed++;
                $this->info("✓ Applied: {$fix}");
            } catch (\Exception $e) {
                $this->error("✗ Failed: {$fix}");
                $this->error("  Error: " . $e->getMessage());
            }
        }

        $this->newLine();
        $this->info("Fixed {$fixed} out of " . count($this->fixes) . ' issues');
    }
}
