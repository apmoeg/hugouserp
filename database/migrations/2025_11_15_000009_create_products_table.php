<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';
            $table->bigIncrements('id')->comment('id');
            $table->unsignedBigInteger('module_id')->nullable()->comment('module_id');
            $table->enum('product_type', ['physical', 'service', 'rental', 'digital'])->default('physical')->comment('product_type');
            $table->boolean('has_variations')->default(false)->index()->comment('has_variations');
            $table->boolean('has_variants')->default(false)->index()->comment('has_variants');
            $table->unsignedBigInteger('parent_product_id')->nullable()->comment('parent_product_id');
            $table->json('variation_attributes')->nullable()->comment('variation_attributes');
            $table->json('custom_fields')->nullable()->comment('custom_fields');
            $table->uuid('uuid')->unique()->comment('uuid');
            $table->string('code')->unique()->comment('code');
            $table->string('name')->comment('name');
            $table->string('sku')->nullable()->comment('sku');
            $table->string('barcode')->nullable()->comment('barcode');
            $table->string('type')->default('product')->comment('type');
            $table->string('uom')->nullable()->comment('uom');
            $table->decimal('uom_factor', 14, 4)->default(1)->comment('uom_factor');
            $table->string('cost_method')->nullable()->comment('cost_method');
            $table->string('cost_currency', 3)->nullable()->comment('cost_currency');
            $table->decimal('standard_cost', 18, 4)->default(0)->comment('standard_cost');
            $table->decimal('cost', 18, 4)->default(0)->comment('cost');
            $table->unsignedBigInteger('tax_id')->nullable()->comment('tax_id');
            $table->unsignedBigInteger('price_list_id')->nullable()->comment('price_list_id');
            $table->decimal('default_price', 18, 4)->default(0)->comment('default_price');
            $table->decimal('price', 18, 4)->default(0)->comment('price');
            $table->string('price_currency', 3)->nullable()->comment('price_currency');
            $table->decimal('min_stock', 18, 4)->default(0)->comment('min_stock');
            $table->decimal('reorder_point', 18, 4)->default(0)->comment('reorder_point');
            $table->decimal('reorder_qty', 18, 4)->default(0)->comment('reorder_qty');
            $table->boolean('is_serialized')->default(false)->comment('is_serialized');
            $table->boolean('is_batch_tracked')->default(false)->comment('is_batch_tracked');
            $table->boolean('track_stock_alerts')->default(true)->comment('track_stock_alerts');
            $table->decimal('hourly_rate', 12, 2)->nullable()->comment('hourly_rate');
            $table->integer('service_duration')->nullable()->comment('service_duration');
            $table->string('duration_unit', 20)->nullable()->comment('duration_unit');
            $table->string('status')->default('active')->comment('status');
            $table->text('notes')->nullable()->comment('notes');
            $table->json('extra_attributes')->nullable()->comment('extra_attributes');
            $table->unsignedBigInteger('branch_id')->comment('branch_id');
            $table->unsignedBigInteger('created_by')->nullable()->comment('created_by');
            $table->unsignedBigInteger('updated_by')->nullable()->comment('updated_by');
            $table->timestamps();
            $table->index('created_at');
            $table->index('updated_at');
            $table->softDeletes();
            $table->index('deleted_at');

            $table->foreign('branch_id')->references('id')->on('branches')->onDelete('cascade');
            $table->foreign('module_id')->references('id')->on('modules')->onDelete('set null');
            $table->foreign('parent_product_id')->references('id')->on('products')->onDelete('cascade');
            $table->foreign('tax_id')->references('id')->on('taxes')->onDelete('set null');
            $table->foreign('price_list_id')->references('id')->on('price_groups')->onDelete('set null');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('updated_by')->references('id')->on('users')->onDelete('set null');

            $table->index(['branch_id', 'status'], 'prod_br_status_idx');
            $table->index('branch_id');
            $table->index('module_id');
            $table->index('parent_product_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
