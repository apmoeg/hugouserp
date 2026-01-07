<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\QueryException;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('low_stock_alerts', function (Blueprint $table) {
            // Add branch_id column if it doesn't exist
            if (!Schema::hasColumn('low_stock_alerts', 'branch_id')) {
                $table->foreignId('branch_id')->nullable()
                    ->after('id')
                    ->constrained('branches')
                    ->nullOnDelete();
            }
            
            // Add resolved_by column if it doesn't exist
            if (!Schema::hasColumn('low_stock_alerts', 'resolved_by')) {
                $table->foreignId('resolved_by')->nullable()
                    ->after('acknowledged_at')
                    ->constrained('users')
                    ->nullOnDelete();
            }
            
            // Add resolved_at column if it doesn't exist
            if (!Schema::hasColumn('low_stock_alerts', 'resolved_at')) {
                $table->timestamp('resolved_at')->nullable()
                    ->after('resolved_by');
            }
        });
        
        // Add composite index
        try {
            Schema::table('low_stock_alerts', function (Blueprint $table) {
                $table->index(['branch_id', 'status', 'created_at'], 'idx_alerts_branch_status_created');
            });
        } catch (QueryException $e) {
            // Index might already exist, ignore duplicate key error
            if (!str_contains($e->getMessage(), 'Duplicate key name')) {
                throw $e;
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('low_stock_alerts', function (Blueprint $table) {
            // Drop index if exists
            try {
                $table->dropIndex('idx_alerts_branch_status_created');
            } catch (QueryException $e) {
                // Ignore if index doesn't exist
            }
            
            // Drop columns if they exist
            if (Schema::hasColumn('low_stock_alerts', 'resolved_at')) {
                $table->dropColumn('resolved_at');
            }
            if (Schema::hasColumn('low_stock_alerts', 'resolved_by')) {
                $table->dropForeign(['resolved_by']);
                $table->dropColumn('resolved_by');
            }
            if (Schema::hasColumn('low_stock_alerts', 'branch_id')) {
                $table->dropForeign(['branch_id']);
                $table->dropColumn('branch_id');
            }
        });
    }
};
