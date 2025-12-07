<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hr_employees', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';
            $table->bigIncrements('id')->comment('id');
            $table->unsignedBigInteger('branch_id')->comment('branch_id');
            $table->unsignedBigInteger('user_id')->nullable()->comment('user_id');
            $table->string('code')->unique()->comment('code');
            $table->string('name')->comment('name');
            $table->string('position')->nullable()->comment('position');
            $table->decimal('salary', 18, 2)->default(0)->comment('salary');
            $table->boolean('is_active')->default(true)->comment('is_active');
            $table->timestamps();
            $table->index('created_at');
            $table->index('updated_at');
            $table->softDeletes();
            $table->index('deleted_at');

            $table->foreign('branch_id')->references('id')->on('branches')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
            $table->index('branch_id');
        });

        Schema::create('attendances', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';
            $table->bigIncrements('id')->comment('id');
            $table->unsignedBigInteger('branch_id')->comment('branch_id');
            $table->unsignedBigInteger('employee_id')->comment('employee_id');
            $table->date('date')->comment('date');
            $table->timestamp('check_in')->nullable()->comment('check_in');
            $table->timestamp('check_out')->nullable()->comment('check_out');
            $table->string('status')->nullable()->comment('status');
            $table->unsignedBigInteger('approved_by')->nullable()->comment('approved_by');
            $table->timestamp('approved_at')->nullable()->comment('approved_at');
            $table->timestamps();
            $table->index('created_at');
            $table->index('updated_at');
            $table->softDeletes();
            $table->index('deleted_at');

            $table->foreign('branch_id')->references('id')->on('branches')->onDelete('cascade');
            $table->foreign('employee_id')->references('id')->on('hr_employees')->onDelete('cascade');
            $table->foreign('approved_by')->references('id')->on('users')->onDelete('set null');
            $table->index('branch_id');
        });

        Schema::create('leave_requests', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';
            $table->bigIncrements('id')->comment('id');
            $table->unsignedBigInteger('employee_id')->comment('employee_id');
            $table->date('from_date')->comment('from_date');
            $table->date('to_date')->comment('to_date');
            $table->string('type')->nullable()->comment('type');
            $table->string('status')->default('pending')->comment('status');
            $table->text('reason')->nullable()->comment('reason');
            $table->unsignedBigInteger('approved_by')->nullable()->comment('approved_by');
            $table->timestamp('approved_at')->nullable()->comment('approved_at');
            $table->timestamps();
            $table->index('created_at');
            $table->index('updated_at');
            $table->softDeletes();
            $table->index('deleted_at');

            $table->foreign('employee_id')->references('id')->on('hr_employees')->onDelete('cascade');
            $table->foreign('approved_by')->references('id')->on('users')->onDelete('set null');
        });

        Schema::create('payrolls', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';
            $table->bigIncrements('id')->comment('id');
            $table->unsignedBigInteger('employee_id')->comment('employee_id');
            $table->string('period')->comment('period');
            $table->decimal('basic', 18, 2)->default(0)->comment('basic');
            $table->decimal('allowances', 18, 2)->default(0)->comment('allowances');
            $table->decimal('deductions', 18, 2)->default(0)->comment('deductions');
            $table->decimal('net', 18, 2)->default(0)->comment('net');
            $table->string('status')->default('draft')->comment('status');
            $table->timestamp('paid_at')->nullable()->comment('paid_at');
            $table->timestamps();
            $table->index('created_at');
            $table->index('updated_at');
            $table->softDeletes();
            $table->index('deleted_at');

            $table->foreign('employee_id')->references('id')->on('hr_employees')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payrolls');
        Schema::dropIfExists('leave_requests');
        Schema::dropIfExists('attendances');
        Schema::dropIfExists('hr_employees');
    }
};
