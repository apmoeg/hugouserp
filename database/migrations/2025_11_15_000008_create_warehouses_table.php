<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('warehouses', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';

            $table->bigIncrements('id')->comment('id');
            $table->uuid('uuid')->unique()->comment('uuid');
            $table->string('code')->unique()->comment('code');
            $table->string('name')->comment('name');
            $table->string('type')->nullable()->comment('type');
            $table->string('status')->default('active')->comment('status');
            $table->string('address')->nullable()->comment('address');
            $table->text('notes')->nullable()->comment('notes');

            $table->json('extra_attributes')->nullable()->comment('extra_attributes');
            // إزالة خوارزمية gin لأنها خاصة بـ PostgreSQL
            // والاكتفاء بإنشاء index عادي على MySQL

            $table->unsignedBigInteger('branch_id')->comment('branch_id');
            $table->unsignedBigInteger('created_by')->nullable()->comment('created_by');
            $table->unsignedBigInteger('updated_by')->nullable()->comment('updated_by');

            $table->timestamps();
            $table->index('created_at');
            $table->index('updated_at');

            $table->softDeletes();
            $table->index('deleted_at');

            $table->foreign('branch_id')
                ->references('id')
                ->on('branches')
                ->onDelete('cascade');

            $table->foreign('created_by')
                ->references('id')
                ->on('users')
                ->onDelete('set null');

            $table->foreign('updated_by')
                ->references('id')
                ->on('users')
                ->onDelete('set null');

            $table->index('branch_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('warehouses');
    }
};
