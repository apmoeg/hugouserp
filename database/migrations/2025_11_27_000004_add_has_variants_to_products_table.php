<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('products')) {
            return;
        }

        Schema::table('products', function (Blueprint $table): void {
            if (! Schema::hasColumn('products', 'has_variants')) {
                $table->boolean('has_variants')->default(false)->index();
            }
        });
    }

    public function down(): void
    {
        // Note: has_variants is now in base products migration
        // so we don't drop it here to maintain rollback safety
    }
};
