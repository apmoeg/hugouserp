<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->index('status', 'idx_sales_status');
            $table->index('posted_at', 'idx_sales_posted_at');
            $table->index(['branch_id', 'status'], 'idx_sales_branch_status');
        });

        Schema::table('stock_movements', function (Blueprint $table) {
            $table->index('direction', 'idx_stock_movements_direction');
            $table->index('created_at', 'idx_stock_movements_created_at');
            $table->index(['product_id', 'warehouse_id'], 'idx_stock_movements_product_warehouse');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->index('status', 'idx_products_status');
            $table->index('product_type', 'idx_products_type');
            $table->index(['branch_id', 'status'], 'idx_products_branch_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropIndex('idx_sales_status');
            $table->dropIndex('idx_sales_posted_at');
            $table->dropIndex('idx_sales_branch_status');
        });

        Schema::table('stock_movements', function (Blueprint $table) {
            $table->dropIndex('idx_stock_movements_direction');
            $table->dropIndex('idx_stock_movements_created_at');
            $table->dropIndex('idx_stock_movements_product_warehouse');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex('idx_products_status');
            $table->dropIndex('idx_products_type');
            $table->dropIndex('idx_products_branch_status');
        });
    }
};
