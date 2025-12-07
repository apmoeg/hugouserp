<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('system_settings', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';
            $table->bigIncrements('id')->comment('id');
            $table->string('key')->unique()->comment('key');
            $table->json('value')->nullable()->comment('value');
            $table->string('type')->nullable()->comment('type');
            $table->string('group')->nullable()->comment('group');
            $table->string('category')->nullable()->comment('category');
            $table->text('description')->nullable()->comment('description');
            $table->boolean('is_public')->default(false)->comment('is_public');
            $table->boolean('is_encrypted')->default(false)->comment('is_encrypted');
            $table->integer('sort_order')->default(0)->comment('sort_order');
            $table->timestamps();
            $table->index('created_at');
            $table->index('updated_at');

            $table->index('group');
            $table->index('category');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('system_settings');
    }
};
