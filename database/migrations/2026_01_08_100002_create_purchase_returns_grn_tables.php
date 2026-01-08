<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations - Purchase Returns & GRN System.
     * Essential for handling returns to suppliers and tracking goods received.
     */
    public function up(): void
    {
        // Goods Received Notes (GRN) - Receipt of goods before invoice
        Schema::create('goods_received_notes', function (Blueprint $table) {
            $table->id();
            $table->string('grn_number', 50)->unique();
            $table->foreignId('purchase_order_id')->nullable()->constrained('purchase_orders')->onDelete('restrict');
            $table->foreignId('branch_id')->constrained('branches')->onDelete('restrict');
            $table->foreignId('warehouse_id')->constrained('warehouses')->onDelete('restrict');
            $table->foreignId('supplier_id')->constrained('suppliers')->onDelete('restrict');
            $table->enum('status', ['draft', 'pending', 'approved', 'completed', 'cancelled'])->default('pending')->index();
            $table->date('received_date')->nullable()->index();
            $table->string('delivery_note_number', 100)->nullable(); // Supplier's delivery note
            $table->string('vehicle_number', 50)->nullable();
            $table->string('driver_name', 100)->nullable();
            $table->text('notes')->nullable();
            $table->text('quality_check_notes')->nullable();
            $table->boolean('quality_approved')->default(true)->index();
            $table->decimal('total_received_qty', 15, 3)->default(0);
            $table->decimal('total_rejected_qty', 15, 3)->default(0);
            $table->foreignId('received_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('inspected_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('inspected_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('updated_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
            $table->softDeletes();

            // Performance indexes
            $table->index(['branch_id', 'status', 'received_date']);
            $table->index(['supplier_id', 'received_date']);
            $table->index('purchase_order_id');
        });

        // GRN Items - Individual items received
        Schema::create('grn_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('grn_id')->constrained('goods_received_notes')->onDelete('cascade');
            $table->foreignId('purchase_order_item_id')->nullable()->constrained('purchase_order_items')->onDelete('set null');
            $table->foreignId('product_id')->constrained('products')->onDelete('restrict');
            $table->foreignId('branch_id')->constrained('branches')->onDelete('restrict');
            $table->decimal('qty_ordered', 15, 3)->default(0); // Original PO quantity
            $table->decimal('qty_received', 15, 3)->default(0); // Actually received
            $table->decimal('qty_accepted', 15, 3)->default(0); // Passed quality check
            $table->decimal('qty_rejected', 15, 3)->default(0); // Failed quality check
            $table->string('batch_number', 100)->nullable()->index();
            $table->date('expiry_date')->nullable()->index();
            $table->date('manufacturing_date')->nullable();
            $table->decimal('unit_cost', 15, 2)->default(0);
            $table->enum('condition', ['good', 'damaged', 'defective', 'wrong_item'])->default('good');
            $table->text('inspection_notes')->nullable();
            $table->boolean('quality_passed')->default(true);
            $table->string('storage_location', 100)->nullable(); // Warehouse location
            $table->foreignId('inspected_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('inspected_at')->nullable();
            $table->timestamps();

            // Performance indexes
            $table->index(['grn_id', 'product_id']);
            $table->index(['batch_number', 'expiry_date']);
        });

        // Purchase Returns - Return defective/wrong items to supplier
        Schema::create('purchase_returns', function (Blueprint $table) {
            $table->id();
            $table->string('return_number', 50)->unique();
            $table->foreignId('purchase_order_id')->constrained('purchase_orders')->onDelete('restrict');
            $table->foreignId('grn_id')->nullable()->constrained('goods_received_notes')->onDelete('restrict');
            $table->foreignId('branch_id')->constrained('branches')->onDelete('restrict');
            $table->foreignId('warehouse_id')->nullable()->constrained('warehouses')->onDelete('restrict');
            $table->foreignId('supplier_id')->constrained('suppliers')->onDelete('restrict');
            $table->enum('return_type', ['full', 'partial'])->default('partial');
            $table->enum('status', ['pending', 'approved', 'shipped', 'completed', 'cancelled'])->default('pending')->index();
            $table->string('reason', 255)->nullable(); // defective, damaged, wrong_item, excess, etc.
            $table->decimal('subtotal', 15, 2)->default(0);
            $table->decimal('tax_amount', 15, 2)->default(0);
            $table->decimal('total_amount', 15, 2)->default(0)->index();
            $table->decimal('expected_credit', 15, 2)->default(0); // Expected refund from supplier
            $table->string('currency', 3)->default('EGP');
            $table->text('notes')->nullable();
            $table->text('internal_notes')->nullable();
            $table->date('return_date')->nullable()->index();
            $table->string('tracking_number', 100)->nullable(); // Shipment tracking
            $table->string('courier_name', 100)->nullable();
            $table->date('shipped_date')->nullable();
            $table->date('received_by_supplier_date')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('shipped_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('updated_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
            $table->softDeletes();

            // Performance indexes
            $table->index(['branch_id', 'status', 'return_date']);
            $table->index(['supplier_id', 'return_date']);
            $table->index('purchase_order_id');
        });

        // Purchase Return Items
        Schema::create('purchase_return_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_return_id')->constrained('purchase_returns')->onDelete('cascade');
            $table->foreignId('purchase_order_item_id')->constrained('purchase_order_items')->onDelete('restrict');
            $table->foreignId('grn_item_id')->nullable()->constrained('grn_items')->onDelete('set null');
            $table->foreignId('product_id')->constrained('products')->onDelete('restrict');
            $table->foreignId('branch_id')->constrained('branches')->onDelete('restrict');
            $table->decimal('qty_returned', 15, 3)->default(0);
            $table->decimal('qty_original', 15, 3)->default(0); // Original received quantity
            $table->decimal('unit_cost', 15, 2)->default(0);
            $table->decimal('tax_amount', 15, 2)->default(0);
            $table->decimal('line_total', 15, 2)->default(0);
            $table->enum('condition', ['defective', 'damaged', 'wrong_item', 'excess', 'expired'])->default('defective');
            $table->string('batch_number', 100)->nullable();
            $table->string('reason', 255)->nullable();
            $table->text('notes')->nullable();
            $table->boolean('deduct_from_stock')->default(true); // Remove from inventory?
            $table->foreignId('deducted_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('deducted_at')->nullable();
            $table->timestamps();

            // Performance indexes
            $table->index(['purchase_return_id', 'product_id']);
            $table->index('purchase_order_item_id');
        });

        // Debit Notes - Accounting documents for purchase returns
        Schema::create('debit_notes', function (Blueprint $table) {
            $table->id();
            $table->string('debit_note_number', 50)->unique();
            $table->foreignId('purchase_return_id')->nullable()->constrained('purchase_returns')->onDelete('restrict');
            $table->foreignId('purchase_order_id')->constrained('purchase_orders')->onDelete('restrict');
            $table->foreignId('branch_id')->constrained('branches')->onDelete('restrict');
            $table->foreignId('supplier_id')->constrained('suppliers')->onDelete('restrict');
            $table->enum('type', ['return', 'adjustment', 'discount', 'damage', 'other'])->default('return');
            $table->enum('status', ['draft', 'pending', 'approved', 'applied', 'cancelled'])->default('draft')->index();
            $table->decimal('amount', 15, 2)->default(0)->index();
            $table->string('currency', 3)->default('EGP');
            $table->text('reason')->nullable();
            $table->text('notes')->nullable();
            $table->date('issue_date')->nullable()->index();
            $table->date('applied_date')->nullable();
            $table->boolean('auto_apply')->default(true);
            $table->decimal('applied_amount', 15, 2)->default(0);
            $table->decimal('remaining_amount', 15, 2)->default(0);
            
            // Accounting integration
            $table->foreignId('journal_entry_id')->nullable()->constrained('journal_entries')->onDelete('set null');
            $table->boolean('posted_to_accounting')->default(false)->index();
            $table->timestamp('posted_at')->nullable();
            
            // Approval workflow
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('updated_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
            $table->softDeletes();

            // Performance indexes
            $table->index(['branch_id', 'status', 'issue_date']);
            $table->index(['supplier_id', 'status']);
            $table->index(['posted_to_accounting', 'status']);
        });

        // Supplier Performance Metrics - Track quality and reliability
        Schema::create('supplier_performance_metrics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supplier_id')->constrained('suppliers')->onDelete('cascade');
            $table->foreignId('branch_id')->constrained('branches')->onDelete('restrict');
            $table->string('period', 20)->index(); // e.g., '2026-01', '2026-Q1'
            $table->integer('total_orders')->default(0);
            $table->integer('on_time_deliveries')->default(0);
            $table->integer('late_deliveries')->default(0);
            $table->decimal('on_time_delivery_rate', 5, 2)->default(0); // Percentage
            $table->decimal('total_ordered_qty', 15, 3)->default(0);
            $table->decimal('total_received_qty', 15, 3)->default(0);
            $table->decimal('total_rejected_qty', 15, 3)->default(0);
            $table->decimal('quality_acceptance_rate', 5, 2)->default(100); // Percentage
            $table->integer('total_returns')->default(0);
            $table->decimal('return_rate', 5, 2)->default(0); // Percentage
            $table->decimal('total_purchase_value', 15, 2)->default(0);
            $table->decimal('average_order_value', 15, 2)->default(0);
            $table->decimal('average_lead_time_days', 10, 2)->default(0);
            $table->decimal('performance_score', 5, 2)->default(100); // Overall score (0-100)
            $table->text('notes')->nullable();
            $table->timestamp('calculated_at')->nullable();
            $table->timestamps();

            // Performance indexes
            $table->unique(['supplier_id', 'branch_id', 'period']);
            $table->index(['branch_id', 'period']);
            $table->index(['supplier_id', 'performance_score']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('supplier_performance_metrics');
        Schema::dropIfExists('debit_notes');
        Schema::dropIfExists('purchase_return_items');
        Schema::dropIfExists('purchase_returns');
        Schema::dropIfExists('grn_items');
        Schema::dropIfExists('goods_received_notes');
    }
};
