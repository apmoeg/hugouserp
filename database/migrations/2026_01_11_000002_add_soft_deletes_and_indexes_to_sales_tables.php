<?php

declare(strict_types=1);

/**
 * Add soft deletes to sale_items and additional performance indexes
 * 
 * Fixes:
 * - Bug #3: Soft Delete Inconsistency - adds SoftDeletes to sale_items
 * - Bug #4: Missing Database Indexes - adds composite indexes for performance
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add soft deletes to sale_items for consistency with sales table
        Schema::table('sale_items', function (Blueprint $table) {
            $table->softDeletes();
        });

        // Add additional performance indexes to sales table
        Schema::table('sales', function (Blueprint $table) {
            // Composite index for customer sales history queries
            $table->index(['customer_id', 'created_at'], 'idx_sales_customer_created');
            
            // Composite index for warehouse sales queries
            $table->index(['warehouse_id', 'created_at'], 'idx_sales_warehouse_created');
        });

        // Add performance index to inventory_movements if table exists
        if (Schema::hasTable('inventory_movements')) {
            Schema::table('inventory_movements', function (Blueprint $table) {
                // Check if index doesn't already exist
                if (!$this->indexExists('inventory_movements', 'idx_inv_movements_branch_created')) {
                    $table->index(['branch_id', 'created_at'], 'idx_inv_movements_branch_created');
                }
            });
        }
    }

    public function down(): void
    {
        // Remove soft deletes from sale_items
        Schema::table('sale_items', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        // Remove indexes from sales table
        Schema::table('sales', function (Blueprint $table) {
            $table->dropIndex('idx_sales_customer_created');
            $table->dropIndex('idx_sales_warehouse_created');
        });

        // Remove index from inventory_movements if table exists
        if (Schema::hasTable('inventory_movements')) {
            Schema::table('inventory_movements', function (Blueprint $table) {
                if ($this->indexExists('inventory_movements', 'idx_inv_movements_branch_created')) {
                    $table->dropIndex('idx_inv_movements_branch_created');
                }
            });
        }
    }

    /**
     * Check if an index exists on a table
     */
    private function indexExists(string $table, string $indexName): bool
    {
        $indexes = Schema::getConnection()
            ->getDoctrineSchemaManager()
            ->listTableIndexes($table);
        
        return isset($indexes[$indexName]);
    }
};
