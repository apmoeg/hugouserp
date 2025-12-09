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
        // Add foreign key constraint for store_orders.branch_id → branches.id
        Schema::table('store_orders', function (Blueprint $table) {
            // Check if the foreign key doesn't already exist
            $sm = Schema::getConnection()->getDoctrineSchemaManager();
            $indexesFound = $sm->listTableIndexes('store_orders');
            
            if (!isset($indexesFound['store_orders_branch_id_foreign'])) {
                $table->foreign('branch_id')
                    ->references('id')
                    ->on('branches')
                    ->nullOnDelete();
            }
        });

        // Add store_order_id column to sales if it doesn't exist, then add foreign key
        Schema::table('sales', function (Blueprint $table) {
            if (!Schema::hasColumn('sales', 'store_order_id')) {
                $table->unsignedBigInteger('store_order_id')->nullable()->after('warehouse_id');
            }
        });

        Schema::table('sales', function (Blueprint $table) {
            // Check if the foreign key doesn't already exist
            $sm = Schema::getConnection()->getDoctrineSchemaManager();
            $indexesFound = $sm->listTableIndexes('sales');
            
            if (!isset($indexesFound['sales_store_order_id_foreign'])) {
                $table->foreign('store_order_id')
                    ->references('id')
                    ->on('store_orders')
                    ->nullOnDelete();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropForeign(['store_order_id']);
            $table->dropColumn('store_order_id');
        });

        Schema::table('store_orders', function (Blueprint $table) {
            $table->dropForeign(['branch_id']);
        });
    }
};
