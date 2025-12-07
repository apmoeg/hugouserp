<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_movements', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';
            $table->bigIncrements('id')->comment('id');
            $table->uuid('uuid')->unique()->comment('uuid');
            $table->string('code')->unique()->comment('code');
            $table->unsignedBigInteger('branch_id')->comment('branch_id');
            $table->unsignedBigInteger('warehouse_id')->comment('warehouse_id');
            $table->unsignedBigInteger('product_id')->comment('product_id');
            $table->string('direction'); // in|out
            $table->decimal('qty', 18, 4)->comment('qty');
            $table->string('uom')->nullable()->comment('uom');
            $table->decimal('unit_cost', 18, 4)->default(0)->comment('unit_cost');
            $table->string('cost_currency', 3)->nullable()->comment('cost_currency');
            $table->decimal('valuated_amount', 18, 4)->default(0)->comment('valuated_amount');
            $table->string('reference_type')->nullable()->comment('reference_type');
            $table->unsignedBigInteger('reference_id')->nullable()->comment('reference_id');
            $table->string('batch_no')->nullable()->comment('batch_no');
            $table->string('serial_no')->nullable()->comment('serial_no');
            $table->timestamp('expires_at')->nullable()->comment('expires_at');
            $table->string('status')->default('posted')->comment('status');
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
            $table->foreign('product_id')->references('id')->on('products')->onDelete('restrict');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('updated_by')->references('id')->on('users')->onDelete('set null');

            $table->index(['branch_id', 'warehouse_id', 'product_id'], 'sm_br_wh_prod_idx');
            $table->index('branch_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
    }
};
