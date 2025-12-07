<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('receipts', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';
            $table->bigIncrements('id')->comment('id');
            $table->unsignedBigInteger('branch_id')->comment('branch_id');
            $table->unsignedBigInteger('purchase_id')->nullable()->comment('purchase_id');
            $table->unsignedBigInteger('sale_id')->nullable()->comment('sale_id');
            $table->string('method')->nullable()->comment('method');
            $table->decimal('amount', 18, 2)->comment('amount');
            $table->string('reference')->nullable()->comment('reference');
            $table->timestamp('paid_at')->nullable()->comment('paid_at');
            $table->unsignedBigInteger('created_by')->nullable()->comment('created_by');
            $table->timestamps();
            $table->index('created_at');
            $table->index('updated_at');

            $table->foreign('branch_id')->references('id')->on('branches')->onDelete('cascade');
            $table->foreign('purchase_id')->references('id')->on('purchases')->onDelete('set null');
            $table->foreign('sale_id')->references('id')->on('sales')->onDelete('set null');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
            $table->index('branch_id');
        });

        Schema::create('deliveries', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';
            $table->bigIncrements('id')->comment('id');
            $table->unsignedBigInteger('sale_id')->comment('sale_id');
            $table->timestamp('delivered_at')->nullable()->comment('delivered_at');
            $table->unsignedBigInteger('delivered_by')->nullable()->comment('delivered_by');
            $table->string('status')->default('pending')->comment('status');
            $table->text('notes')->nullable()->comment('notes');
            $table->timestamps();
            $table->index('created_at');
            $table->index('updated_at');
            $table->softDeletes();
            $table->index('deleted_at');

            $table->foreign('sale_id')->references('id')->on('sales')->onDelete('cascade');
            $table->foreign('delivered_by')->references('id')->on('users')->onDelete('set null');
        });

        Schema::create('return_notes', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';
            $table->bigIncrements('id')->comment('id');
            $table->unsignedBigInteger('branch_id')->comment('branch_id');
            $table->unsignedBigInteger('sale_id')->nullable()->comment('sale_id');
            $table->unsignedBigInteger('purchase_id')->nullable()->comment('purchase_id');
            $table->string('reason')->nullable()->comment('reason');
            $table->decimal('total', 18, 2)->default(0)->comment('total');
            $table->unsignedBigInteger('created_by')->nullable()->comment('created_by');
            $table->timestamps();
            $table->index('created_at');
            $table->index('updated_at');
            $table->softDeletes();
            $table->index('deleted_at');

            $table->foreign('branch_id')->references('id')->on('branches')->onDelete('cascade');
            $table->foreign('sale_id')->references('id')->on('sales')->onDelete('set null');
            $table->foreign('purchase_id')->references('id')->on('purchases')->onDelete('set null');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
            $table->index('branch_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('return_notes');
        Schema::dropIfExists('deliveries');
        Schema::dropIfExists('receipts');
    }
};
