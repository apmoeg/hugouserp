<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Add missing uuid and code columns required by BaseModel.
 *
 * The BaseModel auto-generates uuid and code fields on creating event,
 * but some tables were missing these columns causing test failures.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Add uuid and code to products table if not exists
        if (! Schema::hasColumn('products', 'uuid')) {
            Schema::table('products', function (Blueprint $table) {
                $table->uuid('uuid')->nullable()->after('id');
            });
        }
        if (! Schema::hasColumn('products', 'code')) {
            Schema::table('products', function (Blueprint $table) {
                $table->string('code', 100)->nullable()->after('uuid');
            });
        }

        // Add uuid and code to warehouses table if not exists
        if (! Schema::hasColumn('warehouses', 'uuid')) {
            Schema::table('warehouses', function (Blueprint $table) {
                $table->uuid('uuid')->nullable()->after('id');
            });
        }

        // Add uuid and code to bills_of_materials table if not exists
        if (Schema::hasTable('bills_of_materials')) {
            if (! Schema::hasColumn('bills_of_materials', 'uuid')) {
                Schema::table('bills_of_materials', function (Blueprint $table) {
                    $table->uuid('uuid')->nullable()->after('id');
                });
            }
        }

        // Add uuid and code to production_orders table if not exists
        if (Schema::hasTable('production_orders')) {
            if (! Schema::hasColumn('production_orders', 'uuid')) {
                Schema::table('production_orders', function (Blueprint $table) {
                    $table->uuid('uuid')->nullable()->after('id');
                });
            }
        }

        // Add uuid to customers table if not exists
        if (Schema::hasTable('customers')) {
            if (! Schema::hasColumn('customers', 'uuid')) {
                Schema::table('customers', function (Blueprint $table) {
                    $table->uuid('uuid')->nullable()->after('id');
                });
            }
        }

        // Add uuid to sales table if not exists
        if (Schema::hasTable('sales')) {
            if (! Schema::hasColumn('sales', 'uuid')) {
                Schema::table('sales', function (Blueprint $table) {
                    $table->uuid('uuid')->nullable()->after('id');
                });
            }
        }

        // Add deleted_at to stock_movements table if not exists
        // BaseModel uses SoftDeletes but stock_movements migration didn't include it
        if (Schema::hasTable('stock_movements')) {
            if (! Schema::hasColumn('stock_movements', 'deleted_at')) {
                Schema::table('stock_movements', function (Blueprint $table) {
                    $table->softDeletes();
                });
            }
            if (! Schema::hasColumn('stock_movements', 'updated_at')) {
                Schema::table('stock_movements', function (Blueprint $table) {
                    $table->timestamp('updated_at')->nullable();
                });
            }
        }

        // Add missing columns to production_order_items
        // Model expects quantity_required, quantity_consumed, warehouse_id, is_issued, issued_at, total_cost, unit_id
        // Migration has required_quantity (NOT NULL), consumed_quantity - need to make required_quantity nullable
        if (Schema::hasTable('production_order_items')) {
            // Make original required_quantity nullable so we can insert without it
            if (Schema::hasColumn('production_order_items', 'required_quantity')) {
                Schema::table('production_order_items', function (Blueprint $table) {
                    $table->decimal('required_quantity', 18, 4)->nullable()->default(0)->change();
                });
            }
            if (! Schema::hasColumn('production_order_items', 'quantity_required')) {
                Schema::table('production_order_items', function (Blueprint $table) {
                    $table->decimal('quantity_required', 18, 4)->default(0)->after('product_id');
                });
            }
            if (! Schema::hasColumn('production_order_items', 'quantity_consumed')) {
                Schema::table('production_order_items', function (Blueprint $table) {
                    $table->decimal('quantity_consumed', 18, 4)->default(0)->after('quantity_required');
                });
            }
            if (! Schema::hasColumn('production_order_items', 'unit_id')) {
                Schema::table('production_order_items', function (Blueprint $table) {
                    $table->foreignId('unit_id')->nullable()->constrained('units_of_measure')->nullOnDelete();
                });
            }
            if (! Schema::hasColumn('production_order_items', 'warehouse_id')) {
                Schema::table('production_order_items', function (Blueprint $table) {
                    $table->foreignId('warehouse_id')->nullable()->constrained()->nullOnDelete();
                });
            }
            if (! Schema::hasColumn('production_order_items', 'is_issued')) {
                Schema::table('production_order_items', function (Blueprint $table) {
                    $table->boolean('is_issued')->default(false);
                });
            }
            if (! Schema::hasColumn('production_order_items', 'issued_at')) {
                Schema::table('production_order_items', function (Blueprint $table) {
                    $table->timestamp('issued_at')->nullable();
                });
            }
            if (! Schema::hasColumn('production_order_items', 'total_cost')) {
                Schema::table('production_order_items', function (Blueprint $table) {
                    $table->decimal('total_cost', 18, 4)->default(0);
                });
            }
            if (! Schema::hasColumn('production_order_items', 'deleted_at')) {
                Schema::table('production_order_items', function (Blueprint $table) {
                    $table->softDeletes();
                });
            }
        }

        // Add missing deleted_at to production_order_operations
        if (Schema::hasTable('production_order_operations')) {
            if (! Schema::hasColumn('production_order_operations', 'deleted_at')) {
                Schema::table('production_order_operations', function (Blueprint $table) {
                    $table->softDeletes();
                });
            }
        }

        // Add missing deleted_at to bom_items
        if (Schema::hasTable('bom_items')) {
            if (! Schema::hasColumn('bom_items', 'deleted_at')) {
                Schema::table('bom_items', function (Blueprint $table) {
                    $table->softDeletes();
                });
            }
        }
    }

    public function down(): void
    {
        // Remove uuid and code columns if they exist
        $tables = ['products', 'warehouses', 'bills_of_materials', 'production_orders', 'customers', 'sales'];
        
        foreach ($tables as $tableName) {
            if (Schema::hasTable($tableName)) {
                Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                    if (Schema::hasColumn($tableName, 'uuid')) {
                        $table->dropColumn('uuid');
                    }
                    if (Schema::hasColumn($tableName, 'code') && $tableName === 'products') {
                        $table->dropColumn('code');
                    }
                });
            }
        }
    }
};
