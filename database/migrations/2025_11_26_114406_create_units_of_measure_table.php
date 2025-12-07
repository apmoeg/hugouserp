<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('units_of_measure', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('name_ar')->nullable();
            $table->string('symbol', 20);
            $table->string('type')->default('unit');
            $table->unsignedBigInteger('base_unit_id')->nullable();
            $table->decimal('conversion_factor', 15, 6)->default(1);
            $table->integer('decimal_places')->default(2);
            $table->boolean('is_base_unit')->default(false);
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            $table->foreign('base_unit_id')->references('id')->on('units_of_measure')->nullOnDelete();
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('updated_by')->references('id')->on('users')->nullOnDelete();
        });

        Schema::table('products', function (Blueprint $table) {
            $table->unsignedBigInteger('unit_id')->nullable()->after('uom_factor');
            $table->foreign('unit_id')->references('id')->on('units_of_measure')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropForeign(['unit_id']);
            $table->dropColumn('unit_id');
        });
        Schema::dropIfExists('units_of_measure');
    }
};
