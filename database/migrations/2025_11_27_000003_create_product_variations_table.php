<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('product_variations')) {
            return;
        }

        Schema::create('product_variations', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('product_id')->index();
            $table->string('sku', 191)->nullable()->index();
            $table->string('name', 191);
            $table->json('attributes')->nullable();
            $table->decimal('price', 15, 2)->nullable();
            $table->decimal('cost_price', 15, 2)->nullable();
            $table->decimal('current_stock', 15, 2)->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['product_id', 'is_active'], 'pv_prod_active_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_variations');
    }
};
