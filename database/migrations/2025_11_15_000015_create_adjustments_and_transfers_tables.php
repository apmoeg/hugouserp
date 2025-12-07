<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('adjustments', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';
            $table->bigIncrements('id')->comment('id');
            $table->unsignedBigInteger('branch_id')->comment('branch_id');
            $table->unsignedBigInteger('warehouse_id')->comment('warehouse_id');
            $table->string('reason')->nullable()->comment('reason');
            $table->text('note')->nullable()->comment('note');
            $table->unsignedBigInteger('created_by')->nullable()->comment('created_by');
            $table->timestamps();
            $table->index('created_at');
            $table->index('updated_at');
            $table->softDeletes();
            $table->index('deleted_at');

            $table->foreign('branch_id')->references('id')->on('branches')->onDelete('cascade');
            $table->foreign('warehouse_id')->references('id')->on('warehouses')->onDelete('restrict');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
            $table->index('branch_id');
        });

        Schema::create('adjustment_items', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';
            $table->bigIncrements('id')->comment('id');
            $table->unsignedBigInteger('adjustment_id')->comment('adjustment_id');
            $table->unsignedBigInteger('product_id')->comment('product_id');
            $table->decimal('qty', 18, 4)->comment('qty');
            $table->timestamps();
            $table->index('created_at');
            $table->index('updated_at');
            $table->softDeletes();
            $table->index('deleted_at');

            $table->foreign('adjustment_id')->references('id')->on('adjustments')->onDelete('cascade');
            $table->foreign('product_id')->references('id')->on('products')->onDelete('restrict');
        });

        Schema::create('transfers', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';
            $table->bigIncrements('id')->comment('id');
            $table->unsignedBigInteger('branch_id')->comment('branch_id');
            $table->unsignedBigInteger('from_warehouse_id')->comment('from_warehouse_id');
            $table->unsignedBigInteger('to_warehouse_id')->comment('to_warehouse_id');
            $table->string('status')->default('draft')->comment('status');
            $table->text('note')->nullable()->comment('note');
            $table->unsignedBigInteger('created_by')->nullable()->comment('created_by');
            $table->timestamps();
            $table->index('created_at');
            $table->index('updated_at');
            $table->softDeletes();
            $table->index('deleted_at');

            $table->foreign('branch_id')->references('id')->on('branches')->onDelete('cascade');
            $table->foreign('from_warehouse_id')->references('id')->on('warehouses')->onDelete('restrict');
            $table->foreign('to_warehouse_id')->references('id')->on('warehouses')->onDelete('restrict');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
            $table->index('branch_id');
        });

        Schema::create('transfer_items', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';
            $table->bigIncrements('id')->comment('id');
            $table->unsignedBigInteger('transfer_id')->comment('transfer_id');
            $table->unsignedBigInteger('product_id')->comment('product_id');
            $table->decimal('qty', 18, 4)->comment('qty');
            $table->timestamps();
            $table->index('created_at');
            $table->index('updated_at');
            $table->softDeletes();
            $table->index('deleted_at');

            $table->foreign('transfer_id')->references('id')->on('transfers')->onDelete('cascade');
            $table->foreign('product_id')->references('id')->on('products')->onDelete('restrict');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transfer_items');
        Schema::dropIfExists('transfers');
        Schema::dropIfExists('adjustment_items');
        Schema::dropIfExists('adjustments');
    }
};
