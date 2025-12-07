<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicle_models', function (Blueprint $table) {
            $table->id();
            $table->string('brand', 100)->index();
            $table->string('model', 100)->index();
            $table->integer('year_from')->nullable();
            $table->integer('year_to')->nullable();
            $table->string('category', 50)->nullable();
            $table->string('engine_type', 50)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['brand', 'model', 'year_from', 'year_to'], 'vehicle_models_unique');
        });

        Schema::create('product_compatibilities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade');
            $table->foreignId('vehicle_model_id')->constrained('vehicle_models')->onDelete('cascade');
            $table->string('oem_number', 100)->nullable();
            $table->string('position', 50)->nullable();
            $table->text('notes')->nullable();
            $table->boolean('is_verified')->default(false);
            $table->timestamps();

            $table->unique(['product_id', 'vehicle_model_id'], 'product_vehicle_unique');
            $table->index('oem_number');
        });

        Schema::create('currency_rates', function (Blueprint $table) {
            $table->id();
            $table->string('from_currency', 3)->index();
            $table->string('to_currency', 3)->index();
            $table->decimal('rate', 18, 6);
            $table->date('effective_date')->index();
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();

            $table->unique(['from_currency', 'to_currency', 'effective_date'], 'currency_rate_unique');
        });

        // Add service product columns if not exists (safe, no after())
        if (Schema::hasTable('products') && ! Schema::hasColumn('products', 'service_duration')) {
            Schema::table('products', function (Blueprint $table) {
                if (! Schema::hasColumn('products', 'hourly_rate')) {
                    $table->decimal('hourly_rate', 12, 2)->nullable();
                }
                if (! Schema::hasColumn('products', 'service_duration')) {
                    $table->integer('service_duration')->nullable();
                }
                if (! Schema::hasColumn('products', 'duration_unit')) {
                    $table->string('duration_unit', 20)->nullable();
                }
            });
        }
    }

    public function down(): void
    {
        // Note: Product columns are now in base migration
        // so we don't drop them here to maintain rollback safety

        Schema::dropIfExists('product_compatibilities');
        Schema::dropIfExists('vehicle_models');
        Schema::dropIfExists('currency_rates');
    }
};
