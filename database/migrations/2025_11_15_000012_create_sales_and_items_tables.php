<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';
            $table->bigIncrements('id')->comment('id');
            $table->uuid('uuid')->unique()->comment('uuid');
            $table->string('code')->unique()->comment('code');
            $table->unsignedBigInteger('branch_id')->comment('branch_id');
            $table->unsignedBigInteger('warehouse_id')->comment('warehouse_id');
            $table->unsignedBigInteger('customer_id')->nullable()->comment('customer_id');
            $table->string('status')->default('draft')->comment('status');
            $table->string('channel')->nullable()->comment('channel');
            $table->string('currency', 3)->nullable()->comment('currency');
            $table->decimal('sub_total', 18, 4)->default(0)->comment('sub_total');
            $table->decimal('discount_total', 18, 4)->default(0)->comment('discount_total');
            $table->decimal('tax_total', 18, 4)->default(0)->comment('tax_total');
            $table->decimal('shipping_total', 18, 4)->default(0)->comment('shipping_total');
            $table->decimal('grand_total', 18, 4)->default(0)->comment('grand_total');
            $table->decimal('paid_total', 18, 4)->default(0)->comment('paid_total');
            $table->decimal('due_total', 18, 4)->default(0)->comment('due_total');
            $table->string('reference_no')->nullable()->comment('reference_no');
            $table->timestamp('posted_at')->nullable()->comment('posted_at');
            $table->text('notes')->nullable()->comment('notes');
            $table->json('extra_attributes')->nullable()->comment('extra_attributes');
            $table->unsignedBigInteger('created_by')->nullable()->comment('created_by');
            $table->unsignedBigInteger('updated_by')->nullable()->comment('updated_by');
            $table->timestamps();
            $table->index('created_at');
            $table->index('updated_at');
            $table->softDeletes();
            $table->index('deleted_at');

            $table->foreign('branch_id')->references('id')->on('branches')->onDelete('cascade');
            $table->foreign('warehouse_id')->references('id')->on('warehouses')->onDelete('restrict');
            $table->foreign('customer_id')->references('id')->on('customers')->onDelete('set null');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('updated_by')->references('id')->on('users')->onDelete('set null');

            $table->index(['branch_id', 'customer_id', 'status'], 'sales_br_cust_stat_idx');
            $table->index('branch_id');
            $table->unique(['branch_id', 'reference_no'], 'sales_branch_reference_unique');
        });

        Schema::create('sale_items', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';
            $table->bigIncrements('id')->comment('id');
            $table->unsignedBigInteger('sale_id')->comment('sale_id');
            $table->unsignedBigInteger('product_id')->comment('product_id');
            $table->unsignedBigInteger('branch_id')->comment('branch_id');
            $table->unsignedBigInteger('tax_id')->nullable()->comment('tax_id');
            $table->decimal('qty', 18, 4)->comment('qty');
            $table->string('uom')->nullable()->comment('uom');
            $table->decimal('unit_price', 18, 4)->comment('unit_price');
            $table->decimal('discount', 18, 4)->default(0)->comment('discount');
            $table->decimal('tax_rate', 18, 4)->default(0)->comment('tax_rate');
            $table->decimal('line_total', 18, 4)->default(0)->comment('line_total');
            $table->json('extra_attributes')->nullable()->comment('extra_attributes');
            $table->unsignedBigInteger('created_by')->nullable()->comment('created_by');
            $table->unsignedBigInteger('updated_by')->nullable()->comment('updated_by');
            $table->timestamps();
            $table->index('created_at');
            $table->index('updated_at');
            $table->softDeletes();
            $table->index('deleted_at');

            $table->foreign('sale_id')->references('id')->on('sales')->onDelete('cascade');
            $table->foreign('product_id')->references('id')->on('products')->onDelete('restrict');
            $table->foreign('branch_id')->references('id')->on('branches')->onDelete('cascade');
            $table->foreign('tax_id')->references('id')->on('taxes')->onDelete('set null');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('updated_by')->references('id')->on('users')->onDelete('set null');
            $table->index('branch_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sale_items');
        Schema::dropIfExists('sales');
    }
};
