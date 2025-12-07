<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('loyalty_settings')) {
            Schema::create('loyalty_settings', function (Blueprint $table) {
                $table->id();
                $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
                $table->decimal('points_per_amount', 10, 2)->default(1);
                $table->decimal('amount_per_point', 10, 2)->default(100);
                $table->decimal('redemption_rate', 10, 4)->default(0.01);
                $table->integer('min_points_redeem')->default(100);
                $table->integer('points_expiry_days')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('loyalty_transactions')) {
            Schema::create('loyalty_transactions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
                $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('sale_id')->nullable()->constrained()->nullOnDelete();
                $table->string('type');
                $table->integer('points');
                $table->integer('balance_after');
                $table->text('description')->nullable();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();

                $table->index(['customer_id', 'type'], 'loyalty_cust_type_idx');
                $table->index('created_at');
            });
        }

        if (! Schema::hasTable('low_stock_alerts')) {
            Schema::create('low_stock_alerts', function (Blueprint $table) {
                $table->id();
                $table->foreignId('product_id')->constrained()->cascadeOnDelete();
                $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('warehouse_id')->nullable()->constrained()->nullOnDelete();
                $table->integer('current_qty');
                $table->integer('min_qty');
                $table->string('status')->default('active');
                $table->foreignId('acknowledged_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('acknowledged_at')->nullable();
                $table->foreignId('resolved_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('resolved_at')->nullable();
                $table->timestamps();

                $table->index(['product_id', 'status'], 'lsa_prod_stat_idx');
                $table->index(['branch_id', 'status'], 'lsa_br_stat_idx');
            });
        }

        if (! Schema::hasTable('installment_plans')) {
            Schema::create('installment_plans', function (Blueprint $table) {
                $table->id();
                $table->foreignId('sale_id')->constrained()->cascadeOnDelete();
                $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
                $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
                $table->decimal('total_amount', 15, 2);
                $table->decimal('down_payment', 15, 2)->default(0);
                $table->decimal('remaining_amount', 15, 2);
                $table->integer('num_installments');
                $table->decimal('installment_amount', 15, 2);
                $table->decimal('interest_rate', 5, 2)->default(0);
                $table->string('status')->default('active');
                $table->date('start_date');
                $table->date('end_date');
                $table->text('notes')->nullable();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();

                $table->index(['customer_id', 'status'], 'inst_plan_cust_idx');
                $table->index('status');
            });
        }

        if (! Schema::hasTable('installment_payments')) {
            Schema::create('installment_payments', function (Blueprint $table) {
                $table->id();
                $table->foreignId('installment_plan_id')->constrained()->cascadeOnDelete();
                $table->integer('installment_number');
                $table->decimal('amount_due', 15, 2);
                $table->decimal('amount_paid', 15, 2)->nullable();
                $table->date('due_date');
                $table->timestamp('paid_at')->nullable();
                $table->string('status')->default('pending');
                $table->string('payment_method')->nullable();
                $table->string('payment_reference')->nullable();
                $table->foreignId('paid_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();

                $table->index(['installment_plan_id', 'installment_number'], 'inst_pay_plan_num_idx');
                $table->index('status');
                $table->index('due_date');
            });
        }

        if (! Schema::hasTable('login_activities')) {
            Schema::create('login_activities', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
                $table->string('email');
                $table->string('event');
                $table->string('ip_address')->nullable();
                $table->text('user_agent')->nullable();
                $table->string('browser')->nullable();
                $table->string('platform')->nullable();
                $table->string('device_type')->nullable();
                $table->string('failure_reason')->nullable();
                $table->timestamps();

                $table->index(['user_id', 'event'], 'login_user_event_idx');
                $table->index('created_at');
                $table->index('ip_address');
            });
        }

        if (! Schema::hasTable('user_preferences')) {
            Schema::create('user_preferences', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->string('theme')->default('light');
                $table->integer('session_timeout')->default(30);
                $table->boolean('auto_logout')->default(true);
                $table->string('default_printer')->nullable();
                $table->json('dashboard_widgets')->nullable();
                $table->json('pos_shortcuts')->nullable();
                $table->json('notification_settings')->nullable();
                $table->timestamps();

                $table->unique('user_id');
            });
        }

        // Add loyalty columns to customers table if not exists (safe, no after())
        if (Schema::hasTable('customers')) {
            Schema::table('customers', function (Blueprint $table) {
                if (! Schema::hasColumn('customers', 'loyalty_points')) {
                    $table->integer('loyalty_points')->default(0);
                }
                if (! Schema::hasColumn('customers', 'customer_tier')) {
                    $table->string('customer_tier')->default('new');
                }
                if (! Schema::hasColumn('customers', 'tier_updated_at')) {
                    $table->timestamp('tier_updated_at')->nullable();
                }
            });
        }

        // Add stock alert column to products table if not exists (safe, no after())
        if (Schema::hasTable('products')) {
            Schema::table('products', function (Blueprint $table) {
                if (! Schema::hasColumn('products', 'track_stock_alerts')) {
                    $table->boolean('track_stock_alerts')->default(true);
                }
            });
        }
    }

    public function down(): void
    {
        // Note: Customer and product columns are now in base migrations
        // so we don't drop them here to maintain rollback safety

        Schema::dropIfExists('user_preferences');
        Schema::dropIfExists('login_activities');
        Schema::dropIfExists('installment_payments');
        Schema::dropIfExists('installment_plans');
        Schema::dropIfExists('low_stock_alerts');
        Schema::dropIfExists('loyalty_transactions');
        Schema::dropIfExists('loyalty_settings');
    }
};
