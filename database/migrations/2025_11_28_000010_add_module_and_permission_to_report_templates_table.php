<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('report_templates', function (Blueprint $table): void {
            if (! Schema::hasColumn('report_templates', 'module')) {
                $table->string('module', 50)->default('general')->index();
            }

            if (! Schema::hasColumn('report_templates', 'category')) {
                $table->string('category', 50)->nullable();
            }

            if (! Schema::hasColumn('report_templates', 'required_permission')) {
                $table->string('required_permission', 191)->nullable()->index();
            }
        });
    }

    public function down(): void
    {
        Schema::table('report_templates', function (Blueprint $table): void {
            if (Schema::hasColumn('report_templates', 'required_permission')) {
                $table->dropColumn('required_permission');
            }
            if (Schema::hasColumn('report_templates', 'category')) {
                $table->dropColumn('category');
            }
            if (Schema::hasColumn('report_templates', 'module')) {
                $table->dropColumn('module');
            }
        });
    }
};
