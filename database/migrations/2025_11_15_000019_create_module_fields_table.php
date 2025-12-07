<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('module_fields', function (Blueprint $table): void {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';

            $table->bigIncrements('id');

            $table->unsignedBigInteger('branch_id')->nullable()->comment('Null = global, otherwise branch-specific override');
            $table->string('module_key', 100)->comment('e.g. inventory, rental, hrm');
            $table->string('entity', 100)->comment('e.g. products, employees');
            $table->string('name', 150)->comment('field key, e.g. engine_capacity');
            $table->string('label', 255)->nullable();
            $table->string('type', 50)->default('text')->comment('text, textarea, number, select, date, boolean, etc.');
            $table->json('options')->nullable()->comment('For select fields: {value: label, ...}');
            $table->json('rules')->nullable()->comment('Validation rules as array of strings');
            $table->boolean('is_required')->default(false);
            $table->boolean('is_visible')->default(true);
            $table->boolean('show_in_list')->default(false);
            $table->boolean('show_in_export')->default(true);
            $table->unsignedInteger('order')->default(0);
            $table->json('default')->nullable();
            $table->json('meta')->nullable();

            $table->timestamps();

            $table->index(['module_key', 'entity'], 'mf_mod_ent_idx');
            $table->index(['branch_id', 'module_key', 'entity'], 'mf_br_mod_ent_idx');
            $table->unique(
                ['branch_id', 'module_key', 'entity', 'name'],
                'module_fields_unique_key_per_branch'
            );

            $table->foreign('branch_id')
                ->references('id')
                ->on('branches')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('module_fields');
    }
};
