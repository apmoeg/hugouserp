<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';
            $table->bigIncrements('id')->comment('id');
            $table->uuid('uuid')->unique()->comment('uuid');
            $table->string('code')->unique()->comment('code');
            $table->string('name')->comment('name');
            $table->string('email')->nullable()->comment('email');
            $table->string('phone')->nullable()->comment('phone');
            $table->string('tax_number')->nullable()->comment('tax_number');
            $table->string('billing_address')->nullable()->comment('billing_address');
            $table->string('shipping_address')->nullable()->comment('shipping_address');
            $table->unsignedBigInteger('price_group_id')->nullable()->comment('price_group_id');
            $table->string('status')->default('active')->comment('status');
            $table->text('notes')->nullable()->comment('notes');
            $table->integer('loyalty_points')->default(0)->comment('loyalty_points');
            $table->string('customer_tier')->default('new')->comment('customer_tier');
            $table->timestamp('tier_updated_at')->nullable()->comment('tier_updated_at');
            $table->json('extra_attributes')->nullable()->comment('extra_attributes');
            $table->unsignedBigInteger('branch_id')->comment('branch_id');
            $table->unsignedBigInteger('created_by')->nullable()->comment('created_by');
            $table->unsignedBigInteger('updated_by')->nullable()->comment('updated_by');
            $table->timestamps();
            $table->index('created_at');
            $table->index('updated_at');
            $table->softDeletes();
            $table->index('deleted_at');

            $table->foreign('price_group_id')->references('id')->on('price_groups')->onDelete('set null');
            $table->foreign('branch_id')->references('id')->on('branches')->onDelete('cascade');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('updated_by')->references('id')->on('users')->onDelete('set null');
            $table->index('branch_id');
            $table->index('customer_tier');
        });

        Schema::create('suppliers', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';
            $table->bigIncrements('id')->comment('id');
            $table->unsignedBigInteger('branch_id')->comment('branch_id');
            $table->string('name')->comment('name');
            $table->string('email')->nullable()->comment('email');
            $table->string('phone')->nullable()->comment('phone');
            $table->string('address')->nullable()->comment('address');
            $table->string('tax_number')->nullable()->comment('tax_number');
            $table->boolean('is_active')->default(true)->comment('is_active');
            $table->json('extra_attributes')->nullable()->comment('extra_attributes');
            $table->timestamps();
            $table->index('created_at');
            $table->index('updated_at');
            $table->softDeletes();
            $table->index('deleted_at');

            $table->foreign('branch_id')->references('id')->on('branches')->onDelete('cascade');
            $table->index('branch_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('suppliers');
        Schema::dropIfExists('customers');
    }
};
