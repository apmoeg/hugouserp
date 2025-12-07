<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('module_product_fields', function (Blueprint $table) {
            $table->id();
            $table->foreignId('module_id')->constrained('modules')->onDelete('cascade');
            $table->string('field_key', 100);
            $table->string('field_label', 255);
            $table->string('field_label_ar', 255)->nullable();
            $table->enum('field_type', ['text', 'textarea', 'number', 'decimal', 'date', 'datetime', 'select', 'multiselect', 'checkbox', 'radio', 'file', 'image', 'color', 'url', 'email', 'phone'])->default('text');
            $table->json('field_options')->nullable();
            $table->string('placeholder')->nullable();
            $table->string('placeholder_ar')->nullable();
            $table->text('default_value')->nullable();
            $table->string('validation_rules')->nullable();
            $table->boolean('is_required')->default(false);
            $table->boolean('is_searchable')->default(false);
            $table->boolean('is_filterable')->default(false);
            $table->boolean('show_in_list')->default(true);
            $table->boolean('show_in_form')->default(true);
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->string('field_group')->nullable();
            $table->timestamps();

            $table->unique(['module_id', 'field_key']);
        });

        Schema::create('product_field_values', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade');
            $table->foreignId('module_product_field_id')->constrained('module_product_fields')->onDelete('cascade');
            $table->text('value')->nullable();
            $table->timestamps();

            $table->unique(['product_id', 'module_product_field_id']);
        });

        Schema::create('product_price_tiers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade');
            $table->foreignId('branch_id')->nullable()->constrained('branches')->onDelete('cascade');
            $table->string('tier_name', 100);
            $table->string('tier_name_ar', 100)->nullable();
            $table->decimal('min_quantity', 15, 4)->default(1);
            $table->decimal('max_quantity', 15, 4)->nullable();
            $table->decimal('cost_price', 15, 4)->nullable();
            $table->decimal('selling_price', 15, 4)->nullable();
            $table->decimal('wholesale_price', 15, 4)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('rental_periods', function (Blueprint $table) {
            $table->id();
            $table->foreignId('module_id')->constrained('modules')->onDelete('cascade');
            $table->string('period_key', 50);
            $table->string('period_name', 100);
            $table->string('period_name_ar', 100)->nullable();
            $table->enum('period_type', ['hourly', 'daily', 'weekly', 'monthly', 'quarterly', 'yearly', 'custom'])->default('monthly');
            $table->integer('duration_value')->default(1);
            $table->enum('duration_unit', ['hours', 'days', 'weeks', 'months', 'years'])->default('months');
            $table->decimal('price_multiplier', 8, 4)->default(1);
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['module_id', 'period_key']);
        });

        Schema::create('branch_admins', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained('branches')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->boolean('can_manage_users')->default(true);
            $table->boolean('can_manage_roles')->default(false);
            $table->boolean('can_view_reports')->default(true);
            $table->boolean('can_export_data')->default(true);
            $table->boolean('can_manage_settings')->default(false);
            $table->boolean('is_primary')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['branch_id', 'user_id']);
        });

        Schema::create('report_definitions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('module_id')->nullable()->constrained('modules')->onDelete('set null');
            $table->string('report_key', 100)->unique();
            $table->string('report_name', 255);
            $table->string('report_name_ar', 255)->nullable();
            $table->text('description')->nullable();
            $table->text('description_ar')->nullable();
            $table->string('report_type', 50)->default('table');
            $table->json('available_columns')->nullable();
            $table->json('default_columns')->nullable();
            $table->json('available_filters')->nullable();
            $table->json('default_filters')->nullable();
            $table->json('available_groupings')->nullable();
            $table->json('chart_options')->nullable();
            $table->string('data_source')->nullable();
            $table->text('query_template')->nullable();
            $table->boolean('supports_export')->default(true);
            $table->json('export_formats')->nullable();
            $table->boolean('supports_scheduling')->default(false);
            $table->boolean('is_branch_specific')->default(true);
            $table->boolean('is_system')->default(false);
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('export_layouts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('report_definition_id')->nullable()->constrained('report_definitions')->onDelete('cascade');
            $table->string('layout_name', 255);
            $table->string('entity_type', 100);
            $table->json('selected_columns');
            $table->json('column_order')->nullable();
            $table->json('column_labels')->nullable();
            $table->string('export_format', 20)->default('xlsx');
            $table->boolean('include_headers')->default(true);
            $table->string('date_format', 50)->default('Y-m-d');
            $table->string('number_format', 50)->nullable();
            $table->boolean('is_default')->default(false);
            $table->boolean('is_shared')->default(false);
            $table->timestamps();
        });

        Schema::create('module_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('module_id')->constrained('modules')->onDelete('cascade');
            $table->foreignId('branch_id')->nullable()->constrained('branches')->onDelete('cascade');
            $table->string('setting_key', 100);
            $table->text('setting_value')->nullable();
            $table->string('setting_type', 50)->default('string');
            $table->timestamps();

            $table->unique(['module_id', 'branch_id', 'setting_key']);
        });

        // Add module-specific columns to modules table if not exists
        Schema::table('modules', function (Blueprint $table) {
            if (! Schema::hasColumn('modules', 'pricing_type')) {
                $table->enum('pricing_type', ['buy_sell', 'sell_only', 'cost_only', 'no_pricing'])->default('buy_sell');
            }
            if (! Schema::hasColumn('modules', 'has_variations')) {
                $table->boolean('has_variations')->default(false);
            }
            if (! Schema::hasColumn('modules', 'has_inventory')) {
                $table->boolean('has_inventory')->default(true);
            }
            if (! Schema::hasColumn('modules', 'has_serial_numbers')) {
                $table->boolean('has_serial_numbers')->default(false);
            }
            if (! Schema::hasColumn('modules', 'has_expiry_dates')) {
                $table->boolean('has_expiry_dates')->default(false);
            }
            if (! Schema::hasColumn('modules', 'has_batch_numbers')) {
                $table->boolean('has_batch_numbers')->default(false);
            }
            if (! Schema::hasColumn('modules', 'is_rental')) {
                $table->boolean('is_rental')->default(false);
            }
            if (! Schema::hasColumn('modules', 'is_service')) {
                $table->boolean('is_service')->default(false);
            }
            if (! Schema::hasColumn('modules', 'category')) {
                $table->string('category', 50)->nullable();
            }
        });

        // Add product-module columns to products table if not exists
        Schema::table('products', function (Blueprint $table) {
            if (! Schema::hasColumn('products', 'module_id')) {
                $table->unsignedBigInteger('module_id')->nullable();
            }
            if (! Schema::hasColumn('products', 'product_type')) {
                $table->enum('product_type', ['physical', 'service', 'rental', 'digital'])->default('physical');
            }
            if (! Schema::hasColumn('products', 'has_variations')) {
                $table->boolean('has_variations')->default(false);
            }
            if (! Schema::hasColumn('products', 'parent_product_id')) {
                $table->unsignedBigInteger('parent_product_id')->nullable();
            }
            if (! Schema::hasColumn('products', 'variation_attributes')) {
                $table->json('variation_attributes')->nullable();
            }
            if (! Schema::hasColumn('products', 'custom_fields')) {
                $table->json('custom_fields')->nullable();
            }
        });
    }

    public function down(): void
    {
        // Note: Module and product columns are now defined in base migrations
        // so we don't drop them here to maintain rollback safety

        Schema::dropIfExists('module_settings');
        Schema::dropIfExists('export_layouts');
        Schema::dropIfExists('report_definitions');
        Schema::dropIfExists('branch_admins');
        Schema::dropIfExists('rental_periods');
        Schema::dropIfExists('product_price_tiers');
        Schema::dropIfExists('product_field_values');
        Schema::dropIfExists('module_product_fields');
    }
};
