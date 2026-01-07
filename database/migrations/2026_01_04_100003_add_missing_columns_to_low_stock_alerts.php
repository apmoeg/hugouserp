<?php

declare(strict_types=1);

/**
 * Add missing columns to low_stock_alerts table
 * 
 * Adds:
 * - branch_id for branch-level filtering
 * - resolved_by and resolved_at for tracking resolution
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('low_stock_alerts', function (Blueprint $table) {
            // Add branch_id for branch-level filtering (optional for multi-branch support)
            if (!Schema::hasColumn('low_stock_alerts', 'branch_id')) {
                $table->foreignId('branch_id')->nullable()
                    ->after('product_id')
                    ->constrained()
                    ->nullOnDelete();
            }
            
            // Add resolved tracking fields
            if (!Schema::hasColumn('low_stock_alerts', 'resolved_by')) {
                $table->foreignId('resolved_by')->nullable()
                    ->after('acknowledged_at')
                    ->constrained('users')
                    ->nullOnDelete();
            }
            
            if (!Schema::hasColumn('low_stock_alerts', 'resolved_at')) {
                $table->timestamp('resolved_at')->nullable()
                    ->after('resolved_by');
            }
            
            // Update index to include branch_id
            $table->index(['branch_id', 'status', 'created_at'], 'idx_alerts_branch_status_created');
        });
    }

    public function down(): void
    {
        Schema::table('low_stock_alerts', function (Blueprint $table) {
            $table->dropIndex('idx_alerts_branch_status_created');
            $table->dropForeign(['resolved_by']);
            $table->dropColumn(['resolved_by', 'resolved_at']);
            $table->dropForeign(['branch_id']);
            $table->dropColumn('branch_id');
        });
    }
};
