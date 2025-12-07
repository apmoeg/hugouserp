<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('currencies', function (Blueprint $table) {
            $table->id();
            $table->string('code', 3)->unique();
            $table->string('name');
            $table->string('name_ar')->nullable();
            $table->string('symbol', 10);
            $table->boolean('is_base')->default(false);
            $table->boolean('is_active')->default(true);
            $table->integer('decimal_places')->default(2);
            $table->integer('sort_order')->default(0);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        DB::table('currencies')->insert([
            ['code' => 'EGP', 'name' => 'Egyptian Pound', 'name_ar' => 'جنيه مصري', 'symbol' => 'ج.م', 'is_base' => true, 'is_active' => true, 'decimal_places' => 2, 'sort_order' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['code' => 'USD', 'name' => 'US Dollar', 'name_ar' => 'دولار أمريكي', 'symbol' => '$', 'is_base' => false, 'is_active' => true, 'decimal_places' => 2, 'sort_order' => 2, 'created_at' => now(), 'updated_at' => now()],
            ['code' => 'EUR', 'name' => 'Euro', 'name_ar' => 'يورو', 'symbol' => '€', 'is_base' => false, 'is_active' => true, 'decimal_places' => 2, 'sort_order' => 3, 'created_at' => now(), 'updated_at' => now()],
            ['code' => 'SAR', 'name' => 'Saudi Riyal', 'name_ar' => 'ريال سعودي', 'symbol' => 'ر.س', 'is_base' => false, 'is_active' => true, 'decimal_places' => 2, 'sort_order' => 4, 'created_at' => now(), 'updated_at' => now()],
            ['code' => 'GBP', 'name' => 'British Pound', 'name_ar' => 'جنيه إسترليني', 'symbol' => '£', 'is_base' => false, 'is_active' => true, 'decimal_places' => 2, 'sort_order' => 5, 'created_at' => now(), 'updated_at' => now()],
            ['code' => 'AED', 'name' => 'UAE Dirham', 'name_ar' => 'درهم إماراتي', 'symbol' => 'د.إ', 'is_base' => false, 'is_active' => true, 'decimal_places' => 2, 'sort_order' => 6, 'created_at' => now(), 'updated_at' => now()],
            ['code' => 'KWD', 'name' => 'Kuwaiti Dinar', 'name_ar' => 'دينار كويتي', 'symbol' => 'د.ك', 'is_base' => false, 'is_active' => true, 'decimal_places' => 3, 'sort_order' => 7, 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('currencies');
    }
};
