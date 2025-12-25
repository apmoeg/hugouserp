<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('price_groups')) {
            Schema::table('price_groups', function (Blueprint $table): void {
                if (! Schema::hasColumn('price_groups', 'deleted_at')) {
                    $table->softDeletes();
                }
            });
        }

        if (Schema::hasTable('taxes')) {
            Schema::table('taxes', function (Blueprint $table): void {
                if (! Schema::hasColumn('taxes', 'deleted_at')) {
                    $table->softDeletes();
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('price_groups')) {
            Schema::table('price_groups', function (Blueprint $table): void {
                if (Schema::hasColumn('price_groups', 'deleted_at')) {
                    $table->dropSoftDeletes();
                }
            });
        }

        if (Schema::hasTable('taxes')) {
            Schema::table('taxes', function (Blueprint $table): void {
                if (Schema::hasColumn('taxes', 'deleted_at')) {
                    $table->dropSoftDeletes();
                }
            });
        }
    }
};
