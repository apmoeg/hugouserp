<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('scheduled_reports')) {
            return;
        }

        Schema::create('scheduled_reports', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id')->index();
            $table->unsignedBigInteger('report_template_id')->nullable()->index();
            $table->string('route_name', 191);
            $table->string('cron_expression', 191)->default('0 8 * * *');
            $table->json('filters')->nullable();
            $table->string('recipient_email', 191)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('scheduled_reports');
    }
};
