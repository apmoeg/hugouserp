<?php

declare(strict_types=1);

/**
 * Add Missing Columns to Sales Table
 * 
 * This migration adds columns that the application code expects for
 * external integrations but are missing from the sales table.
 * 
 * Added columns:
 * - channel: To track the source of the sale (pos, api, shopify, woocommerce, etc.)
 * - external_reference: To store external order ID from integrated platforms
 */

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
        if (Schema::hasTable('sales')) {
            Schema::table('sales', function (Blueprint $table) {
                // Add channel if it doesn't exist
                if (!Schema::hasColumn('sales', 'channel')) {
                    $table->string('channel', 50)->nullable()
                        ->after('type')
                        ->index()
                        ->comment('Source channel: pos, api, shopify, woocommerce, etc.');
                }
                
                // Add external_reference if it doesn't exist
                if (!Schema::hasColumn('sales', 'external_reference')) {
                    $table->string('external_reference', 255)->nullable()
                        ->after('reference_number')
                        ->index()
                        ->comment('External order ID from integrated platforms');
                }
            });
        }
        
        if (Schema::hasTable('purchases')) {
            Schema::table('purchases', function (Blueprint $table) {
                // Add channel if it doesn't exist
                if (!Schema::hasColumn('purchases', 'channel')) {
                    $table->string('channel', 50)->nullable()
                        ->after('type')
                        ->index()
                        ->comment('Source channel: manual, api, etc.');
                }
                
                // Add external_reference if it doesn't exist
                if (!Schema::hasColumn('purchases', 'external_reference')) {
                    $table->string('external_reference', 255)->nullable()
                        ->after('reference_number')
                        ->index()
                        ->comment('External order ID from integrated platforms');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('sales')) {
            Schema::table('sales', function (Blueprint $table) {
                if (Schema::hasColumn('sales', 'external_reference')) {
                    $table->dropIndex('sales_external_reference_index');
                    $table->dropColumn('external_reference');
                }
                
                if (Schema::hasColumn('sales', 'channel')) {
                    $table->dropIndex('sales_channel_index');
                    $table->dropColumn('channel');
                }
            });
        }
        
        if (Schema::hasTable('purchases')) {
            Schema::table('purchases', function (Blueprint $table) {
                if (Schema::hasColumn('purchases', 'external_reference')) {
                    $table->dropIndex('purchases_external_reference_index');
                    $table->dropColumn('external_reference');
                }
                
                if (Schema::hasColumn('purchases', 'channel')) {
                    $table->dropIndex('purchases_channel_index');
                    $table->dropColumn('channel');
                }
            });
        }
    }
};
