<?php

declare(strict_types=1);

/**
 * Fix Performance Indexes Bugs
 * 
 * This migration fixes incorrect column references in the performance indexes migration.
 * The audit_logs table uses 'causer_id' and 'event' columns, not 'user_id' and 'action'.
 * Also removes reference to non-existent 'module_key' column.
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Fix audit_logs indexes - use correct column names
        if (Schema::hasTable('audit_logs')) {
            Schema::table('audit_logs', function (Blueprint $table) {
                // Drop the incorrectly named index if it exists
                try {
                    $indexes = Schema::getIndexes('audit_logs');
                    $existingIndexNames = array_column($indexes, 'name');
                    
                    if (in_array('idx_audit_user_action_date', $existingIndexNames)) {
                        $table->dropIndex('idx_audit_user_action_date');
                    }
                    
                    if (in_array('idx_audit_module_date', $existingIndexNames)) {
                        $table->dropIndex('idx_audit_module_date');
                    }
                } catch (\Throwable) {
                    // Indexes may not exist, ignore
                }
                
                // Add correct indexes with proper column names
                $this->addIndexIfNotExists($table, 'audit_logs', ['causer_id', 'event', 'created_at'], 'idx_audit_causer_event_date');
                
                // Add index for log_name which is commonly queried
                $this->addIndexIfNotExists($table, 'audit_logs', ['log_name', 'created_at'], 'idx_audit_log_created');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('audit_logs')) {
            $this->dropIndexIfExists('audit_logs', 'idx_audit_causer_event_date');
            $this->dropIndexIfExists('audit_logs', 'idx_audit_log_created');
        }
    }

    /**
     * Add index if it doesn't already exist
     */
    private function addIndexIfNotExists(Blueprint $table, string $tableName, array $columns, string $indexName): void
    {
        try {
            $indexes = Schema::getIndexes($tableName);
            $existingIndexNames = array_column($indexes, 'name');
            
            if (!in_array($indexName, $existingIndexNames)) {
                $table->index($columns, $indexName);
            }
        } catch (\Throwable $e) {
            // If we can't check, try to add (may fail silently if exists)
            try {
                $table->index($columns, $indexName);
            } catch (\Throwable $e) {
                // Index already exists, ignore
            }
        }
    }

    /**
     * Drop index if it exists
     */
    private function dropIndexIfExists(string $tableName, string $indexName): void
    {
        try {
            $indexes = Schema::getIndexes($tableName);
            $existingIndexNames = array_column($indexes, 'name');
            
            if (in_array($indexName, $existingIndexNames)) {
                Schema::table($tableName, function (Blueprint $table) use ($indexName) {
                    $table->dropIndex($indexName);
                });
            }
        } catch (\Throwable $e) {
            // Index may not exist or cannot be dropped, ignore
        }
    }
};
