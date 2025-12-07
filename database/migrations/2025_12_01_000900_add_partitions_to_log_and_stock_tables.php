<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (config('database.default') !== 'mysql') {
            return;
        }

        // stock_movements: partition by month on moved_at
        if (Schema::hasTable('stock_movements') && Schema::hasColumn('stock_movements', 'moved_at')) {
            try {
                DB::statement(<<<'SQL'
ALTER TABLE `stock_movements`
PARTITION BY RANGE (YEAR(`moved_at`) * 100 + MONTH(`moved_at`)) (
    PARTITION p2025m01 VALUES LESS THAN (202502),
    PARTITION pmax   VALUES LESS THAN MAXVALUE
);
SQL);
            } catch (\Throwable $e) {
                // Partitioning is best-effort; table will still work without it.
            }
        }

        // login_activities: partition by year of created_at
        if (Schema::hasTable('login_activities') && Schema::hasColumn('login_activities', 'created_at')) {
            try {
                DB::statement(<<<'SQL'
ALTER TABLE `login_activities`
PARTITION BY RANGE (YEAR(`created_at`)) (
    PARTITION p2025 VALUES LESS THAN (2026),
    PARTITION pmax  VALUES LESS THAN MAXVALUE
);
SQL);
            } catch (\Throwable $e) {
                // ignore if partitioning not supported or already applied
            }
        }

        // loyalty_transactions: partition by year of created_at
        if (Schema::hasTable('loyalty_transactions') && Schema::hasColumn('loyalty_transactions', 'created_at')) {
            try {
                DB::statement(<<<'SQL'
ALTER TABLE `loyalty_transactions`
PARTITION BY RANGE (YEAR(`created_at`)) (
    PARTITION p2025 VALUES LESS THAN (2026),
    PARTITION pmax  VALUES LESS THAN MAXVALUE
);
SQL);
            } catch (\Throwable $e) {
                // ignore if partitioning not supported or already applied
            }
        }
    }

    public function down(): void
    {
        if (config('database.default') !== 'mysql') {
            return;
        }

        if (Schema::hasTable('stock_movements')) {
            try {
                DB::statement('ALTER TABLE `stock_movements` REMOVE PARTITIONING;');
            } catch (\Throwable $e) {
                // ignore
            }
        }

        if (Schema::hasTable('login_activities')) {
            try {
                DB::statement('ALTER TABLE `login_activities` REMOVE PARTITIONING;');
            } catch (\Throwable $e) {
                // ignore
            }
        }

        if (Schema::hasTable('loyalty_transactions')) {
            try {
                DB::statement('ALTER TABLE `loyalty_transactions` REMOVE PARTITIONING;');
            } catch (\Throwable $e) {
                // ignore
            }
        }
    }
};
