<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations - Leave Management System.
     * Essential HR feature for managing employee time off and leave balances.
     */
    public function up(): void
    {
        // Leave Types - Define available leave categories
        Schema::create('leave_types', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100)->unique();
            $table->string('code', 20)->unique(); // annual, sick, casual, maternity, etc.
            $table->text('description')->nullable();
            $table->enum('unit', ['days', 'hours'])->default('days');
            $table->decimal('default_annual_quota', 10, 2)->default(0); // Default days/hours per year
            $table->boolean('is_paid')->default(true);
            $table->boolean('requires_approval')->default(true);
            $table->boolean('requires_document')->default(false); // Medical certificate, etc.
            $table->integer('max_consecutive_days')->nullable(); // Max days in one request
            $table->integer('min_notice_days')->default(0); // Notice period required
            $table->integer('max_carry_forward')->nullable(); // Max unused days to next year
            $table->boolean('carry_forward_expires')->default(false);
            $table->integer('carry_forward_expiry_months')->nullable(); // Expire after X months
            $table->boolean('is_active')->default(true)->index();
            $table->integer('sort_order')->default(0);
            $table->string('color', 7)->default('#3B82F6'); // For calendar display
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
            $table->softDeletes();

            // Performance indexes
            $table->index(['is_active', 'sort_order']);
        });

        // Leave Balances - Track employee leave quotas
        Schema::create('leave_balances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->onDelete('cascade');
            $table->foreignId('leave_type_id')->constrained('leave_types')->onDelete('restrict');
            $table->year('year')->index();
            $table->decimal('opening_balance', 10, 2)->default(0);
            $table->decimal('annual_quota', 10, 2)->default(0);
            $table->decimal('accrued', 10, 2)->default(0); // Earned through the year
            $table->decimal('used', 10, 2)->default(0);
            $table->decimal('pending', 10, 2)->default(0); // In pending requests
            $table->decimal('available', 10, 2)->default(0); // Calculated field
            $table->decimal('carry_forward_from_previous', 10, 2)->default(0);
            $table->date('carry_forward_expiry_date')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            // Unique constraint - one balance record per employee per leave type per year
            $table->unique(['employee_id', 'leave_type_id', 'year'], 'unique_employee_leave_year');
            
            // Performance indexes
            $table->index(['employee_id', 'year']);
            $table->index(['leave_type_id', 'year']);
        });

        // Leave Requests - Employee leave applications
        Schema::create('leave_requests', function (Blueprint $table) {
            $table->id();
            $table->string('request_number', 50)->unique();
            $table->foreignId('employee_id')->constrained('employees')->onDelete('restrict');
            $table->foreignId('leave_type_id')->constrained('leave_types')->onDelete('restrict');
            $table->foreignId('branch_id')->nullable()->constrained('branches')->onDelete('restrict');
            $table->foreignId('department_id')->nullable()->constrained('departments')->onDelete('restrict');
            $table->date('start_date')->index();
            $table->date('end_date')->index();
            $table->decimal('total_days', 10, 2)->default(0);
            $table->enum('start_half', ['full', 'first_half', 'second_half'])->default('full');
            $table->enum('end_half', ['full', 'first_half', 'second_half'])->default('full');
            $table->enum('status', ['draft', 'pending', 'approved', 'rejected', 'cancelled', 'expired'])->default('pending')->index();
            $table->text('reason')->nullable();
            $table->text('employee_notes')->nullable();
            $table->text('manager_notes')->nullable();
            $table->text('hr_notes')->nullable();
            
            // Supporting documents
            $table->string('document_path', 500)->nullable();
            $table->string('document_name', 255)->nullable();
            
            // Contact during leave
            $table->string('contact_number', 20)->nullable();
            $table->text('emergency_contact')->nullable();
            
            // Replacement/backup person
            $table->foreignId('replacement_employee_id')->nullable()->constrained('employees')->onDelete('set null');
            
            // Workflow tracking
            $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('rejected_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('rejected_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->foreignId('cancelled_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('cancelled_at')->nullable();
            $table->text('cancellation_reason')->nullable();
            
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('updated_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
            $table->softDeletes();

            // Performance indexes
            $table->index(['employee_id', 'status', 'start_date']);
            $table->index(['branch_id', 'status', 'start_date']);
            $table->index(['leave_type_id', 'status']);
            $table->index(['status', 'start_date']);
        });

        // Leave Request Approvals - Multi-level approval workflow
        Schema::create('leave_request_approvals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('leave_request_id')->constrained('leave_requests')->onDelete('cascade');
            $table->integer('approval_level')->default(1); // 1=Manager, 2=HR, 3=Director, etc.
            $table->foreignId('approver_id')->constrained('users')->onDelete('restrict');
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending')->index();
            $table->text('comments')->nullable();
            $table->timestamp('responded_at')->nullable();
            $table->timestamps();

            // Performance indexes
            $table->index(['leave_request_id', 'approval_level']);
            $table->index(['approver_id', 'status']);
        });

        // Leave Adjustments - Manual balance corrections
        Schema::create('leave_adjustments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->onDelete('restrict');
            $table->foreignId('leave_type_id')->constrained('leave_types')->onDelete('restrict');
            $table->year('year');
            $table->enum('adjustment_type', ['addition', 'deduction', 'correction', 'carry_forward', 'encashment'])->index();
            $table->decimal('amount', 10, 2); // Can be positive or negative
            $table->text('reason');
            $table->text('notes')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();

            // Performance indexes
            $table->index(['employee_id', 'year']);
            $table->index(['leave_type_id', 'year']);
        });

        // Leave Holidays - Company/public holidays
        Schema::create('leave_holidays', function (Blueprint $table) {
            $table->id();
            $table->string('name', 200);
            $table->date('date')->index();
            $table->year('year')->index();
            $table->enum('type', ['public', 'company', 'regional', 'religious'])->default('public')->index();
            $table->boolean('is_mandatory')->default(true); // Mandatory day off
            $table->text('description')->nullable();
            $table->foreignId('branch_id')->nullable()->constrained('branches')->onDelete('cascade');
            $table->boolean('is_active')->default(true)->index();
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
            $table->softDeletes();

            // Performance indexes
            $table->index(['date', 'is_active']);
            $table->index(['year', 'type', 'is_active']);
        });

        // Leave Accrual Rules - Define how leave accrues over time
        Schema::create('leave_accrual_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('leave_type_id')->constrained('leave_types')->onDelete('cascade');
            $table->enum('accrual_frequency', ['monthly', 'quarterly', 'semi_annually', 'annually', 'per_pay_period'])->default('monthly');
            $table->decimal('accrual_amount', 10, 2); // Amount accrued per period
            $table->boolean('prorate_on_joining')->default(true); // Prorate for mid-year joiners
            $table->boolean('prorate_on_leaving')->default(true);
            $table->integer('waiting_period_months')->default(0); // Months before accrual starts
            $table->date('effective_from')->nullable();
            $table->date('effective_to')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();

            // Performance indexes
            $table->index(['leave_type_id', 'is_active']);
        });

        // Leave Encashments - Convert unused leave to cash
        Schema::create('leave_encashments', function (Blueprint $table) {
            $table->id();
            $table->string('encashment_number', 50)->unique();
            $table->foreignId('employee_id')->constrained('employees')->onDelete('restrict');
            $table->foreignId('leave_type_id')->constrained('leave_types')->onDelete('restrict');
            $table->year('year');
            $table->decimal('days_encashed', 10, 2);
            $table->decimal('rate_per_day', 15, 2); // Daily salary rate
            $table->decimal('total_amount', 15, 2);
            $table->string('currency', 3)->default('EGP');
            $table->enum('status', ['pending', 'approved', 'processed', 'paid', 'rejected'])->default('pending')->index();
            $table->text('notes')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('processed_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('processed_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();

            // Performance indexes
            $table->index(['employee_id', 'year', 'status']);
            $table->index(['status', 'year']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('leave_encashments');
        Schema::dropIfExists('leave_accrual_rules');
        Schema::dropIfExists('leave_holidays');
        Schema::dropIfExists('leave_adjustments');
        Schema::dropIfExists('leave_request_approvals');
        Schema::dropIfExists('leave_requests');
        Schema::dropIfExists('leave_balances');
        Schema::dropIfExists('leave_types');
    }
};
