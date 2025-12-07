<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('scheduled_reports', function (Blueprint $table): void {
            if (! Schema::hasColumn('scheduled_reports', 'last_status')) {
                $table->string('last_status', 20)->nullable();
            }
            if (! Schema::hasColumn('scheduled_reports', 'last_run_at')) {
                $table->timestamp('last_run_at')->nullable();
            }
            if (! Schema::hasColumn('scheduled_reports', 'last_error')) {
                $table->text('last_error')->nullable();
            }
            if (! Schema::hasColumn('scheduled_reports', 'runs_count')) {
                $table->unsignedInteger('runs_count')->default(0);
            }
            if (! Schema::hasColumn('scheduled_reports', 'failures_count')) {
                $table->unsignedInteger('failures_count')->default(0);
            }
        });
    }

    public function down(): void
    {
        Schema::table('scheduled_reports', function (Blueprint $table): void {
            if (Schema::hasColumn('scheduled_reports', 'failures_count')) {
                $table->dropColumn('failures_count');
            }
            if (Schema::hasColumn('scheduled_reports', 'runs_count')) {
                $table->dropColumn('runs_count');
            }
            if (Schema::hasColumn('scheduled_reports', 'last_error')) {
                $table->dropColumn('last_error');
            }
            if (Schema::hasColumn('scheduled_reports', 'last_run_at')) {
                $table->dropColumn('last_run_at');
            }
            if (Schema::hasColumn('scheduled_reports', 'last_status')) {
                $table->dropColumn('last_status');
            }
        });
    }
};
