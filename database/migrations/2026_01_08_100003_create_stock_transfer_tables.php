<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations - Stock Transfer System.
     * Critical feature for managing inventory movement between warehouses/branches.
     */
    public function up(): void
    {
        // Stock Transfers - Inter-warehouse inventory movements
        Schema::create('stock_transfers', function (Blueprint $table) {
            $table->id();
            $table->string('transfer_number', 50)->unique();
            $table->foreignId('from_warehouse_id')->constrained('warehouses')->onDelete('restrict');
            $table->foreignId('to_warehouse_id')->constrained('warehouses')->onDelete('restrict');
            $table->foreignId('from_branch_id')->nullable()->constrained('branches')->onDelete('restrict');
            $table->foreignId('to_branch_id')->nullable()->constrained('branches')->onDelete('restrict');
            $table->enum('transfer_type', ['inter_warehouse', 'inter_branch', 'internal'])->default('inter_warehouse')->index();
            $table->enum('status', ['draft', 'pending', 'approved', 'in_transit', 'received', 'completed', 'cancelled', 'rejected'])->default('pending')->index();
            $table->date('transfer_date')->nullable()->index();
            $table->date('expected_delivery_date')->nullable();
            $table->date('actual_delivery_date')->nullable();
            $table->enum('priority', ['low', 'medium', 'high', 'urgent'])->default('medium')->index();
            $table->string('reason', 255)->nullable(); // restock, customer_order, branch_need, etc.
            $table->text('notes')->nullable();
            $table->text('internal_notes')->nullable();
            
            // Shipping details
            $table->string('tracking_number', 100)->nullable()->index();
            $table->string('courier_name', 100)->nullable();
            $table->string('vehicle_number', 50)->nullable();
            $table->string('driver_name', 100)->nullable();
            $table->string('driver_phone', 20)->nullable();
            
            // Cost tracking
            $table->decimal('shipping_cost', 15, 2)->default(0);
            $table->decimal('insurance_cost', 15, 2)->default(0);
            $table->decimal('total_cost', 15, 2)->default(0);
            $table->string('currency', 3)->default('EGP');
            
            // Quantities
            $table->decimal('total_qty_requested', 15, 3)->default(0);
            $table->decimal('total_qty_shipped', 15, 3)->default(0);
            $table->decimal('total_qty_received', 15, 3)->default(0);
            $table->decimal('total_qty_damaged', 15, 3)->default(0);
            
            // Workflow tracking
            $table->foreignId('requested_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('shipped_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('shipped_at')->nullable();
            $table->foreignId('received_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('received_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('updated_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
            $table->softDeletes();

            // Performance indexes
            $table->index(['from_warehouse_id', 'status', 'transfer_date']);
            $table->index(['to_warehouse_id', 'status', 'transfer_date']);
            $table->index(['status', 'priority', 'transfer_date']);
            
            // Prevent self-transfer
            $table->check('from_warehouse_id != to_warehouse_id');
        });

        // Stock Transfer Items - Individual products being transferred
        Schema::create('stock_transfer_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stock_transfer_id')->constrained('stock_transfers')->onDelete('cascade');
            $table->foreignId('product_id')->constrained('products')->onDelete('restrict');
            $table->decimal('qty_requested', 15, 3)->default(0); // Requested quantity
            $table->decimal('qty_approved', 15, 3)->default(0); // Approved quantity (may be less)
            $table->decimal('qty_shipped', 15, 3)->default(0); // Actually shipped
            $table->decimal('qty_received', 15, 3)->default(0); // Received at destination
            $table->decimal('qty_damaged', 15, 3)->default(0); // Damaged during transit
            $table->string('batch_number', 100)->nullable()->index();
            $table->date('expiry_date')->nullable();
            $table->decimal('unit_cost', 15, 2)->default(0); // For costing purposes
            $table->enum('condition_on_shipping', ['new', 'good', 'used'])->default('good');
            $table->enum('condition_on_receiving', ['good', 'damaged', 'defective', 'missing'])->nullable();
            $table->text('notes')->nullable();
            $table->text('damage_report')->nullable();
            $table->timestamps();

            // Performance indexes
            $table->index(['stock_transfer_id', 'product_id']);
            $table->index(['product_id', 'batch_number']);
        });

        // Stock Transfer Approvals - Multi-level approval workflow
        Schema::create('stock_transfer_approvals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stock_transfer_id')->constrained('stock_transfers')->onDelete('cascade');
            $table->integer('approval_level')->default(1); // Level 1, 2, 3, etc.
            $table->foreignId('approver_id')->constrained('users')->onDelete('restrict');
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending')->index();
            $table->text('comments')->nullable();
            $table->timestamp('responded_at')->nullable();
            $table->timestamps();

            // Performance indexes
            $table->index(['stock_transfer_id', 'approval_level']);
            $table->index(['approver_id', 'status']);
        });

        // Stock Transfer Documents - Attachments (photos, PDFs, etc.)
        Schema::create('stock_transfer_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stock_transfer_id')->constrained('stock_transfers')->onDelete('cascade');
            $table->string('document_type', 50)->index(); // packing_list, delivery_note, damage_report, photo, etc.
            $table->string('file_name', 255);
            $table->string('file_path', 500);
            $table->string('file_type', 50)->nullable(); // pdf, jpg, png, etc.
            $table->integer('file_size')->nullable(); // bytes
            $table->text('description')->nullable();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();

            // Performance indexes
            $table->index(['stock_transfer_id', 'document_type']);
        });

        // Stock Transfer History - Audit trail of status changes
        Schema::create('stock_transfer_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stock_transfer_id')->constrained('stock_transfers')->onDelete('cascade');
            $table->string('from_status', 50);
            $table->string('to_status', 50);
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable(); // Additional data about the change
            $table->foreignId('changed_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('changed_at')->nullable();
            $table->timestamps();

            // Performance indexes
            $table->index(['stock_transfer_id', 'changed_at']);
            $table->index(['to_status', 'changed_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_transfer_history');
        Schema::dropIfExists('stock_transfer_documents');
        Schema::dropIfExists('stock_transfer_approvals');
        Schema::dropIfExists('stock_transfer_items');
        Schema::dropIfExists('stock_transfers');
    }
};
