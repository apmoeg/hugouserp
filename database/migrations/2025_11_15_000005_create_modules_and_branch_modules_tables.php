<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('modules', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';
            $table->bigIncrements('id')->comment('id');
            $table->string('key')->unique()->comment('key');
            $table->string('slug', 100)->nullable()->comment('slug');
            $table->string('name')->comment('name');
            $table->string('name_ar', 255)->nullable()->comment('name_ar');
            $table->string('version')->nullable()->comment('version');
            $table->boolean('is_core')->default(false)->comment('is_core');
            $table->boolean('is_active')->default(true)->comment('is_active');
            $table->text('description')->nullable()->comment('description');
            $table->text('description_ar')->nullable()->comment('description_ar');
            $table->string('icon', 50)->nullable()->comment('icon');
            $table->string('color', 20)->nullable()->comment('color');
            $table->integer('sort_order')->default(0)->comment('sort_order');
            $table->json('default_settings')->nullable()->comment('default_settings');
            $table->enum('pricing_type', ['buy_sell', 'sell_only', 'cost_only', 'no_pricing'])->default('buy_sell')->comment('pricing_type');
            $table->boolean('has_variations')->default(false)->comment('has_variations');
            $table->boolean('has_inventory')->default(true)->comment('has_inventory');
            $table->boolean('has_serial_numbers')->default(false)->comment('has_serial_numbers');
            $table->boolean('has_expiry_dates')->default(false)->comment('has_expiry_dates');
            $table->boolean('has_batch_numbers')->default(false)->comment('has_batch_numbers');
            $table->boolean('is_rental')->default(false)->comment('is_rental');
            $table->boolean('is_service')->default(false)->comment('is_service');
            $table->string('category', 50)->nullable()->comment('category');
            $table->timestamps();
            $table->index('created_at');
            $table->index('updated_at');
        });

        Schema::create('branch_modules', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';
            $table->bigIncrements('id')->comment('id');
            $table->unsignedBigInteger('branch_id')->comment('branch_id');
            $table->unsignedBigInteger('module_id')->nullable()->comment('module_id');
            $table->string('module_key')->comment('module_key');
            $table->boolean('enabled')->default(true)->comment('enabled');
            $table->json('settings')->nullable()->comment('settings');
            $table->timestamps();
            $table->index('created_at');
            $table->index('updated_at');

            $table->unique(['branch_id', 'module_key']);

            $table->foreign('branch_id')->references('id')->on('branches')->onDelete('cascade');
            $table->foreign('module_id')->references('id')->on('modules')->onDelete('set null');
            $table->index('branch_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('branch_modules');
        Schema::dropIfExists('modules');
    }
};
