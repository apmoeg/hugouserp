<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('store_orders')) {
            return;
        }

        Schema::create('store_orders', function (Blueprint $table): void {
            $table->id();
            $table->string('external_order_id', 191)->unique();
            $table->string('status', 50)->default('pending')->index();
            $table->unsignedBigInteger('branch_id')->nullable()->index();
            $table->string('currency', 10)->nullable();
            $table->decimal('total', 15, 2)->default(0);
            $table->decimal('discount_total', 15, 2)->default(0);
            $table->decimal('shipping_total', 15, 2)->default(0);
            $table->decimal('tax_total', 15, 2)->default(0);
            $table->json('payload');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('store_orders');
    }
};
