<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('sales')) {
            return;
        }

        Schema::table('sales', function (Blueprint $table): void {
            if (! Schema::hasColumn('sales', 'store_order_id')) {
                $table->unsignedBigInteger('store_order_id')->nullable()->index();
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('sales')) {
            return;
        }

        Schema::table('sales', function (Blueprint $table): void {
            if (Schema::hasColumn('sales', 'store_order_id')) {
                $table->dropColumn('store_order_id');
            }
        });
    }
};
