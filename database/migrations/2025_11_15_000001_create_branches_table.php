<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('branches', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';
            $table->bigIncrements('id')->comment('id');
            $table->string('name')->comment('name');
            $table->string('code')->unique()->comment('code');
            $table->boolean('is_active')->default(true)->comment('is_active');
            $table->string('address')->nullable()->comment('address');
            $table->string('phone')->nullable()->comment('phone');
            $table->string('timezone')->nullable()->comment('timezone');
            $table->string('currency', 3)->nullable()->comment('currency');
            $table->boolean('is_main')->default(false)->comment('is_main');
            $table->unsignedBigInteger('parent_id')->nullable()->comment('parent_id');
            $table->json('settings')->nullable()->comment('settings');
            $table->timestamps();
            $table->index('created_at');
            $table->index('updated_at');
            $table->softDeletes();
            $table->index('deleted_at');

            $table->foreign('parent_id')->references('id')->on('branches')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('branches');
    }
};
