<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. HRM Module
        if (Schema::hasTable('hr_employees')) {
            Schema::table('hr_employees', function (Blueprint $table) {
                if (!Schema::hasColumn('hr_employees', 'extra_attributes')) {
                    $table->json('extra_attributes')->nullable()->after('is_active');
                }
            });
        }

        if (Schema::hasTable('payrolls')) {
            Schema::table('payrolls', function (Blueprint $table) {
                if (!Schema::hasColumn('payrolls', 'extra_attributes')) {
                    $table->json('extra_attributes')->nullable()->after('status');
                }
            });
        }

        if (Schema::hasTable('attendances')) {
            Schema::table('attendances', function (Blueprint $table) {
                if (!Schema::hasColumn('attendances', 'extra_attributes')) {
                    $table->json('extra_attributes')->nullable()->after('approved_at');
                }
            });
        }

        // 2. Rental Module
        if (Schema::hasTable('rental_contracts')) {
            Schema::table('rental_contracts', function (Blueprint $table) {
                if (!Schema::hasColumn('rental_contracts', 'rental_period_id')) {
                    $table->unsignedBigInteger('rental_period_id')->nullable()->after('tenant_id');
                    $table->foreign('rental_period_id')->references('id')->on('rental_periods')->onDelete('set null');
                }
                if (!Schema::hasColumn('rental_contracts', 'custom_days')) {
                    $table->integer('custom_days')->nullable()->after('rental_period_id');
                }
                if (!Schema::hasColumn('rental_contracts', 'extra_attributes')) {
                    $table->json('extra_attributes')->nullable()->after('status');
                }
            });
        }

        // 3. Other tables needing extra_attributes
        $tables = [
            'adjustments',
            'price_groups',
            'taxes',
            'warranties',
            'vehicle_contracts',
            'vehicle_payments',
            'transfers',
            'transfer_items',
            'deliveries',
            'return_notes',
        ];

        foreach ($tables as $tableName) {
            if (Schema::hasTable($tableName)) {
                Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                    if (!Schema::hasColumn($tableName, 'extra_attributes')) {
                        $table->json('extra_attributes')->nullable();
                    }
                });
            }
        }
    }

    public function down(): void
    {
        // HRM
        $hrmTables = ['hr_employees', 'payrolls', 'attendances'];
        foreach ($hrmTables as $tableName) {
            if (Schema::hasTable($tableName) && Schema::hasColumn($tableName, 'extra_attributes')) {
                Schema::table($tableName, function (Blueprint $table) {
                    $table->dropColumn('extra_attributes');
                });
            }
        }

        // Rental
        if (Schema::hasTable('rental_contracts')) {
            Schema::table('rental_contracts', function (Blueprint $table) {
                if (Schema::hasColumn('rental_contracts', 'extra_attributes')) {
                    $table->dropColumn('extra_attributes');
                }
                if (Schema::hasColumn('rental_contracts', 'custom_days')) {
                    $table->dropColumn('custom_days');
                }
                if (Schema::hasColumn('rental_contracts', 'rental_period_id')) {
                    $table->dropForeign(['rental_period_id']);
                    $table->dropColumn('rental_period_id');
                }
            });
        }

        // Other tables
        $tables = [
            'adjustments',
            'price_groups',
            'taxes',
            'warranties',
            'vehicle_contracts',
            'vehicle_payments',
            'transfers',
            'transfer_items',
            'deliveries',
            'return_notes',
        ];

        foreach ($tables as $tableName) {
            if (Schema::hasTable($tableName) && Schema::hasColumn($tableName, 'extra_attributes')) {
                Schema::table($tableName, function (Blueprint $table) {
                    $table->dropColumn('extra_attributes');
                });
            }
        }
    }
};
