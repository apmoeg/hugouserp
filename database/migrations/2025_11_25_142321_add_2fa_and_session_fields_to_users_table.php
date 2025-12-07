<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Add discount-related columns first if they don't exist
            if (! Schema::hasColumn('users', 'max_discount_percent')) {
                $table->decimal('max_discount_percent', 5, 2)->nullable();
            }
            if (! Schema::hasColumn('users', 'daily_discount_limit')) {
                $table->decimal('daily_discount_limit', 12, 2)->nullable();
            }
            if (! Schema::hasColumn('users', 'can_modify_price')) {
                $table->boolean('can_modify_price')->default(true);
            }

            // Add session management columns
            if (! Schema::hasColumn('users', 'max_sessions')) {
                $table->unsignedInteger('max_sessions')->default(3);
            }

            // Add 2FA columns
            if (! Schema::hasColumn('users', 'two_factor_enabled')) {
                $table->boolean('two_factor_enabled')->default(false);
            }
            if (! Schema::hasColumn('users', 'two_factor_secret')) {
                $table->text('two_factor_secret')->nullable();
            }
            if (! Schema::hasColumn('users', 'two_factor_recovery_codes')) {
                $table->text('two_factor_recovery_codes')->nullable();
            }
            if (! Schema::hasColumn('users', 'two_factor_confirmed_at')) {
                $table->timestamp('two_factor_confirmed_at')->nullable();
            }
            if (! Schema::hasColumn('users', 'password_changed_at')) {
                $table->timestamp('password_changed_at')->nullable();
            }
        });
    }

    public function down(): void
    {
        // Note: Only drop columns that are NOT defined in the base users migration.
        // The columns max_discount_percent, daily_discount_limit, can_modify_price,
        // and max_sessions are defined in 2025_11_15_000002_create_users_table.php
        // and should NOT be dropped here to maintain rollback safety.
        Schema::table('users', function (Blueprint $table) {
            $columns = [
                'two_factor_enabled',
                'two_factor_secret',
                'two_factor_recovery_codes',
                'two_factor_confirmed_at',
                'password_changed_at',
            ];
            foreach ($columns as $col) {
                if (Schema::hasColumn('users', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
