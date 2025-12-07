<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('module_branch')) {
            Schema::create('module_branch', function (Blueprint $table) {
                $table->id();
                $table->foreignId('module_id')->constrained('modules')->cascadeOnDelete();
                $table->foreignId('branch_id')->constrained('branches')->cascadeOnDelete();
                $table->boolean('is_enabled')->default(true);
                $table->json('settings')->nullable();
                $table->timestamps();
                $table->unique(['module_id', 'branch_id']);
            });
        }

        if (! Schema::hasTable('module_custom_fields')) {
            Schema::create('module_custom_fields', function (Blueprint $table) {
                $table->id();
                $table->foreignId('module_id')->constrained('modules')->cascadeOnDelete();
                $table->string('field_key', 100);
                $table->string('field_label');
                $table->string('field_label_ar')->nullable();
                $table->enum('field_type', ['text', 'textarea', 'number', 'email', 'phone', 'date', 'datetime', 'select', 'multiselect', 'checkbox', 'radio', 'file', 'image', 'color', 'url']);
                $table->json('field_options')->nullable();
                $table->boolean('is_required')->default(false);
                $table->boolean('is_active')->default(true);
                $table->integer('sort_order')->default(0);
                $table->text('validation_rules')->nullable();
                $table->string('placeholder')->nullable();
                $table->string('default_value')->nullable();
                $table->timestamps();
                $table->unique(['module_id', 'field_key']);
            });
        }

        if (! Schema::hasTable('customers')) {
            Schema::create('customers', function (Blueprint $table) {
                $table->id();
                $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
                $table->string('name');
                $table->string('email')->nullable();
                $table->string('phone')->nullable();
                $table->string('phone2')->nullable();
                $table->text('address')->nullable();
                $table->string('city')->nullable();
                $table->string('country')->nullable();
                $table->string('tax_number')->nullable();
                $table->string('company_name')->nullable();
                $table->enum('customer_type', ['individual', 'company'])->default('individual');
                $table->decimal('credit_limit', 15, 2)->default(0);
                $table->decimal('balance', 15, 2)->default(0);
                $table->boolean('is_active')->default(true);
                $table->text('notes')->nullable();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (! Schema::hasTable('suppliers')) {
            Schema::create('suppliers', function (Blueprint $table) {
                $table->id();
                $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
                $table->string('name');
                $table->string('email')->nullable();
                $table->string('phone')->nullable();
                $table->text('address')->nullable();
                $table->string('city')->nullable();
                $table->string('country')->nullable();
                $table->string('tax_number')->nullable();
                $table->string('company_name')->nullable();
                $table->string('contact_person')->nullable();
                $table->decimal('balance', 15, 2)->default(0);
                $table->boolean('is_active')->default(true);
                $table->text('notes')->nullable();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (! Schema::hasTable('sales')) {
            Schema::create('sales', function (Blueprint $table) {
                $table->id();
                $table->foreignId('branch_id')->constrained('branches');
                $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();
                $table->string('invoice_number')->unique();
                $table->date('sale_date');
                $table->decimal('subtotal', 15, 2)->default(0);
                $table->decimal('discount', 15, 2)->default(0);
                $table->decimal('tax', 15, 2)->default(0);
                $table->decimal('total', 15, 2)->default(0);
                $table->decimal('paid', 15, 2)->default(0);
                $table->decimal('due', 15, 2)->default(0);
                $table->enum('payment_status', ['paid', 'partial', 'unpaid'])->default('unpaid');
                $table->enum('status', ['pending', 'completed', 'cancelled', 'refunded'])->default('pending');
                $table->string('payment_method')->nullable();
                $table->text('notes')->nullable();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (! Schema::hasTable('sale_items')) {
            Schema::create('sale_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('sale_id')->constrained('sales')->cascadeOnDelete();
                $table->foreignId('product_id')->constrained('products');
                $table->string('product_name');
                $table->decimal('quantity', 15, 3);
                $table->decimal('unit_price', 15, 2);
                $table->decimal('discount', 15, 2)->default(0);
                $table->decimal('tax', 15, 2)->default(0);
                $table->decimal('total', 15, 2);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('purchases')) {
            Schema::create('purchases', function (Blueprint $table) {
                $table->id();
                $table->foreignId('branch_id')->constrained('branches');
                $table->foreignId('supplier_id')->nullable()->constrained('suppliers')->nullOnDelete();
                $table->string('reference_number')->unique();
                $table->date('purchase_date');
                $table->decimal('subtotal', 15, 2)->default(0);
                $table->decimal('discount', 15, 2)->default(0);
                $table->decimal('tax', 15, 2)->default(0);
                $table->decimal('shipping', 15, 2)->default(0);
                $table->decimal('total', 15, 2)->default(0);
                $table->decimal('paid', 15, 2)->default(0);
                $table->decimal('due', 15, 2)->default(0);
                $table->enum('payment_status', ['paid', 'partial', 'unpaid'])->default('unpaid');
                $table->enum('status', ['pending', 'received', 'cancelled'])->default('pending');
                $table->text('notes')->nullable();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (! Schema::hasTable('purchase_items')) {
            Schema::create('purchase_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('purchase_id')->constrained('purchases')->cascadeOnDelete();
                $table->foreignId('product_id')->constrained('products');
                $table->string('product_name');
                $table->decimal('quantity', 15, 3);
                $table->decimal('unit_cost', 15, 2);
                $table->decimal('total', 15, 2);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('expense_categories')) {
            Schema::create('expense_categories', function (Blueprint $table) {
                $table->id();
                $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
                $table->string('name');
                $table->string('name_ar')->nullable();
                $table->text('description')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('expenses')) {
            Schema::create('expenses', function (Blueprint $table) {
                $table->id();
                $table->foreignId('branch_id')->constrained('branches');
                $table->foreignId('category_id')->nullable()->constrained('expense_categories')->nullOnDelete();
                $table->string('reference_number')->nullable();
                $table->date('expense_date');
                $table->decimal('amount', 15, 2);
                $table->string('payment_method')->nullable();
                $table->text('description')->nullable();
                $table->string('attachment')->nullable();
                $table->boolean('is_recurring')->default(false);
                $table->string('recurrence_interval')->nullable();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (! Schema::hasTable('income_categories')) {
            Schema::create('income_categories', function (Blueprint $table) {
                $table->id();
                $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
                $table->string('name');
                $table->string('name_ar')->nullable();
                $table->text('description')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('incomes')) {
            Schema::create('incomes', function (Blueprint $table) {
                $table->id();
                $table->foreignId('branch_id')->constrained('branches');
                $table->foreignId('category_id')->nullable()->constrained('income_categories')->nullOnDelete();
                $table->string('reference_number')->nullable();
                $table->date('income_date');
                $table->decimal('amount', 15, 2);
                $table->string('payment_method')->nullable();
                $table->text('description')->nullable();
                $table->string('attachment')->nullable();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (! Schema::hasTable('accounts')) {
            Schema::create('accounts', function (Blueprint $table) {
                $table->id();
                $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
                $table->string('account_number')->unique();
                $table->string('name');
                $table->string('name_ar')->nullable();
                $table->enum('type', ['asset', 'liability', 'equity', 'revenue', 'expense']);
                $table->foreignId('parent_id')->nullable()->constrained('accounts')->nullOnDelete();
                $table->decimal('balance', 15, 2)->default(0);
                $table->boolean('is_active')->default(true);
                $table->text('description')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('journal_entries')) {
            Schema::create('journal_entries', function (Blueprint $table) {
                $table->id();
                $table->foreignId('branch_id')->constrained('branches');
                $table->string('reference_number')->unique();
                $table->date('entry_date');
                $table->text('description')->nullable();
                $table->enum('status', ['draft', 'posted', 'cancelled'])->default('draft');
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('journal_entry_lines')) {
            Schema::create('journal_entry_lines', function (Blueprint $table) {
                $table->id();
                $table->foreignId('journal_entry_id')->constrained('journal_entries')->cascadeOnDelete();
                $table->foreignId('account_id')->constrained('accounts');
                $table->decimal('debit', 15, 2)->default(0);
                $table->decimal('credit', 15, 2)->default(0);
                $table->text('description')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('journal_entry_lines');
        Schema::dropIfExists('journal_entries');
        Schema::dropIfExists('accounts');
        Schema::dropIfExists('incomes');
        Schema::dropIfExists('income_categories');
        Schema::dropIfExists('expenses');
        Schema::dropIfExists('expense_categories');
        Schema::dropIfExists('purchase_items');
        Schema::dropIfExists('purchases');
        Schema::dropIfExists('sale_items');
        Schema::dropIfExists('sales');
        Schema::dropIfExists('suppliers');
        Schema::dropIfExists('customers');
        Schema::dropIfExists('module_custom_fields');
        Schema::dropIfExists('module_branch');
        Schema::dropIfExists('modules');
    }
};
