<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('report_templates')) {
            return;
        }

        Schema::create('report_templates', function (Blueprint $table): void {
            $table->id();
            $table->string('key', 100)->unique();
            $table->string('name', 191);
            $table->string('description', 255)->nullable();
            $table->string('route_name', 191);
            $table->json('default_filters')->nullable();
            $table->string('output_type', 20)->default('web');
            $table->json('export_columns')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('report_templates');
    }
};
