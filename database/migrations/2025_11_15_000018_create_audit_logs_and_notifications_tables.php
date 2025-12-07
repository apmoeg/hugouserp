<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';
            $table->bigIncrements('id')->comment('id');
            $table->unsignedBigInteger('user_id')->nullable()->comment('user_id');
            $table->unsignedBigInteger('target_user_id')->nullable()->comment('target_user_id');
            $table->unsignedBigInteger('branch_id')->nullable()->comment('branch_id');
            $table->string('module_key')->nullable()->comment('module_key');
            $table->string('action')->comment('action');
            $table->string('subject_type')->nullable()->comment('subject_type');
            $table->unsignedBigInteger('subject_id')->nullable()->comment('subject_id');
            $table->string('ip')->nullable()->comment('ip');
            $table->text('user_agent')->nullable()->comment('user_agent');
            $table->json('old_values')->nullable()->comment('old_values');
            $table->json('new_values')->nullable()->comment('new_values');
            $table->json('meta')->nullable()->comment('meta');
            $table->timestamps();
            $table->index('created_at');
            $table->index('updated_at');
            $table->softDeletes();
            $table->index('deleted_at');

            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
            $table->foreign('target_user_id')->references('id')->on('users')->onDelete('set null');
            $table->foreign('branch_id')->references('id')->on('branches')->onDelete('set null');

            $table->index(['branch_id', 'module_key'], 'audit_br_mod_idx');
            $table->index('branch_id');
        });

        Schema::create('notifications', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';
            $table->bigIncrements('id')->comment('id');
            $table->unsignedBigInteger('user_id')->comment('user_id');
            $table->string('title')->comment('title');
            $table->text('body')->nullable()->comment('body');
            $table->json('data')->nullable()->comment('data');
            $table->timestamp('read_at')->nullable()->comment('read_at');
            $table->timestamps();
            $table->index('created_at');
            $table->index('updated_at');
            $table->softDeletes();
            $table->index('deleted_at');

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
        Schema::dropIfExists('audit_logs');
    }
};
