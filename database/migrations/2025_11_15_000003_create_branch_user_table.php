<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('branch_user', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';
            $table->unsignedBigInteger('branch_id')->comment('branch_id');
            $table->unsignedBigInteger('user_id')->comment('user_id');
            $table->timestamps();
            $table->index('created_at');
            $table->index('updated_at');

            $table->primary(['branch_id', 'user_id'])->comment('branch_id');

            $table->foreign('branch_id')->references('id')->on('branches')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->index('branch_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('branch_user');
    }
};
