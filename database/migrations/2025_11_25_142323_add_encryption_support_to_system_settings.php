<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('system_settings', function (Blueprint $table) {
            if (! Schema::hasColumn('system_settings', 'is_encrypted')) {
                $table->boolean('is_encrypted')->default(false);
            }
            if (! Schema::hasColumn('system_settings', 'category')) {
                $table->string('category')->nullable();
            }
            if (! Schema::hasColumn('system_settings', 'description')) {
                $table->text('description')->nullable();
            }
            if (! Schema::hasColumn('system_settings', 'sort_order')) {
                $table->integer('sort_order')->default(0);
            }
        });
    }

    public function down(): void
    {
        Schema::table('system_settings', function (Blueprint $table) {
            $columns = ['is_encrypted', 'category', 'description', 'sort_order'];
            foreach ($columns as $col) {
                if (Schema::hasColumn('system_settings', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
