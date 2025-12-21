<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Fix final column mismatches between models and database schema.
 * 
 * This migration addresses missing columns in:
 * - customers: address, city, country, company, external_id
 * 
 * The Customer model expects these columns in the fillable array, but they
 * were not present in the original migration. The customers table only has
 * billing_address and shipping_address, but the model expects a generic
 * 'address' field as well.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Fix customers table - add address, city, country, company, external_id columns
        if (Schema::hasTable('customers')) {
            Schema::table('customers', function (Blueprint $table): void {
                // Add general address column if it doesn't exist (model expects this)
                if (!Schema::hasColumn('customers', 'address')) {
                    $table->text('address')->nullable()->after('shipping_address')->comment('General address');
                }
            });

            // Run in separate statement to ensure address column exists before adding columns after it
            Schema::table('customers', function (Blueprint $table): void {
                // Add city after address
                if (!Schema::hasColumn('customers', 'city')) {
                    $table->string('city', 100)->nullable()->after('address')->comment('Customer city');
                }
                // Add country after city
                if (!Schema::hasColumn('customers', 'country')) {
                    $table->string('country', 100)->nullable()->after('city')->comment('Customer country');
                }
                // Add company after country
                if (!Schema::hasColumn('customers', 'company')) {
                    $table->string('company')->nullable()->after('country')->comment('Company name');
                }
                // Add external_id after company
                if (!Schema::hasColumn('customers', 'external_id')) {
                    $table->string('external_id')->nullable()->after('company')->comment('External system ID');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('customers')) {
            Schema::table('customers', function (Blueprint $table): void {
                $columnsToDrop = ['address', 'city', 'country', 'company', 'external_id'];
                foreach ($columnsToDrop as $column) {
                    if (Schema::hasColumn('customers', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }
    }
};
