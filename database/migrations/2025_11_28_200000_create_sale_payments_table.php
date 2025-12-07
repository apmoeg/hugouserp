<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('sale_payments')) {
            Schema::create('sale_payments', function (Blueprint $table) {
                $table->id();
                $table->foreignId('sale_id')->constrained('sales')->onDelete('cascade');
                $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
                $table->string('payment_method', 50);
                $table->decimal('amount', 15, 4);
                $table->string('currency', 10)->default('EGP');
                $table->decimal('exchange_rate', 12, 6)->default(1.000000);
                $table->string('reference_no')->nullable();
                $table->string('card_type')->nullable();
                $table->string('card_last_four', 4)->nullable();
                $table->string('bank_name')->nullable();
                $table->string('cheque_number')->nullable();
                $table->date('cheque_date')->nullable();
                $table->text('notes')->nullable();
                $table->string('status', 20)->default('completed');
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();

                $table->index(['sale_id', 'payment_method'], 'sp_sale_method_idx');
                $table->index(['branch_id', 'created_at'], 'sp_br_created_idx');
            });
        }

        if (! Schema::hasTable('pos_sessions')) {
            Schema::create('pos_sessions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('branch_id')->constrained('branches')->onDelete('cascade');
                $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
                $table->decimal('opening_cash', 15, 4)->default(0);
                $table->decimal('closing_cash', 15, 4)->nullable();
                $table->decimal('expected_cash', 15, 4)->nullable();
                $table->decimal('cash_difference', 15, 4)->nullable();
                $table->json('payment_summary')->nullable();
                $table->integer('total_transactions')->default(0);
                $table->decimal('total_sales', 15, 4)->default(0);
                $table->decimal('total_refunds', 15, 4)->default(0);
                $table->string('status', 20)->default('open');
                $table->timestamp('opened_at');
                $table->timestamp('closed_at')->nullable();
                $table->text('closing_notes')->nullable();
                $table->foreignId('closed_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();

                $table->index(['branch_id', 'status'], 'pos_br_status_idx');
                $table->index(['user_id', 'status'], 'pos_user_status_idx');
            });
        }

        // Add user discount/price fields if not exists (safe for fresh or existing databases)
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'max_discount_percent')) {
                $table->decimal('max_discount_percent', 5, 2)->nullable();
            }
            if (! Schema::hasColumn('users', 'can_modify_price')) {
                $table->boolean('can_modify_price')->default(true);
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pos_sessions');
        Schema::dropIfExists('sale_payments');

        // Note: max_discount_percent and can_modify_price are now defined in the base
        // users migration (2025_11_15_000002_create_users_table.php), so we do NOT
        // drop them here to maintain rollback safety and prevent data loss.
    }
};
