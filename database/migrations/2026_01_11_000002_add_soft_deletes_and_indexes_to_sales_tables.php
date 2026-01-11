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
                // Use try-catch to handle cases where index might already exist
                try {
                    $table->index(['branch_id', 'created_at'], 'idx_inv_movements_branch_created');
                } catch (\Exception $e) {
                    // Index already exists, ignore
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
                try {
                    $table->dropIndex('idx_inv_movements_branch_created');
                } catch (\Exception $e) {
                    // Index doesn't exist, ignore
                }
            });
        }
    }
};
