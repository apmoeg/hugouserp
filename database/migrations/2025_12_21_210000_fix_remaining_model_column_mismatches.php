<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Fix remaining column mismatches between models and database schema.
 * 
 * This migration addresses missing columns in ticket_replies and other tables
 * that are referenced in model $fillable/$casts but not present in the database.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Fix ticket_replies - add read_at, created_by, updated_by columns
        if (Schema::hasTable('ticket_replies')) {
            Schema::table('ticket_replies', function (Blueprint $table): void {
                if (!Schema::hasColumn('ticket_replies', 'read_at')) {
                    $table->timestamp('read_at')->nullable();
                }
                if (!Schema::hasColumn('ticket_replies', 'created_by')) {
                    $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                }
                if (!Schema::hasColumn('ticket_replies', 'updated_by')) {
                    $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('ticket_replies')) {
            Schema::table('ticket_replies', function (Blueprint $table): void {
                if (Schema::hasColumn('ticket_replies', 'updated_by')) {
                    $table->dropForeign(['updated_by']);
                    $table->dropColumn('updated_by');
                }
                if (Schema::hasColumn('ticket_replies', 'created_by')) {
                    $table->dropForeign(['created_by']);
                    $table->dropColumn('created_by');
                }
                if (Schema::hasColumn('ticket_replies', 'read_at')) {
                    $table->dropColumn('read_at');
                }
            });
        }
    }
};
