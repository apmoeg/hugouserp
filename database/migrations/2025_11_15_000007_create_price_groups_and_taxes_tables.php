<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('price_groups', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';
            $table->bigIncrements('id')->comment('id');
            $table->string('name')->comment('name');
            $table->text('description')->nullable()->comment('description');
            $table->timestamps();
            $table->index('created_at');
            $table->index('updated_at');
        });

        Schema::create('taxes', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';
            $table->bigIncrements('id')->comment('id');
            $table->string('name')->comment('name');
            $table->decimal('rate', 10, 4)->default(0)->comment('rate');
            $table->string('type')->default('percentage'); // percentage|fixed
            $table->boolean('is_inclusive')->default(false)->comment('is_inclusive');
            $table->timestamps();
            $table->index('created_at');
            $table->index('updated_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('taxes');
        Schema::dropIfExists('price_groups');
    }
};
