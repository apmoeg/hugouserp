<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('modules', function (Blueprint $table) {
            if (! Schema::hasColumn('modules', 'slug')) {
                $table->string('slug', 100)->nullable();
            }
            if (! Schema::hasColumn('modules', 'name_ar')) {
                $table->string('name_ar', 255)->nullable();
            }
            if (! Schema::hasColumn('modules', 'description_ar')) {
                $table->text('description_ar')->nullable();
            }
            if (! Schema::hasColumn('modules', 'icon')) {
                $table->string('icon', 50)->nullable();
            }
            if (! Schema::hasColumn('modules', 'color')) {
                $table->string('color', 20)->nullable();
            }
            if (! Schema::hasColumn('modules', 'sort_order')) {
                $table->integer('sort_order')->default(0);
            }
            if (! Schema::hasColumn('modules', 'default_settings')) {
                $table->json('default_settings')->nullable();
            }
        });
    }

    public function down(): void
    {
        // Note: These columns are now defined in the base modules migration
        // so we don't drop them here to maintain rollback safety
    }
};
