<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Create the missing stock_transfers table (advanced transfer system)
        Schema::create('stock_transfers', function (Blueprint $table) {
            $table->id();
            $table->string('transfer_number', 100)->unique();
            $table->foreignId('from_warehouse_id')->constrained('warehouses')->restrictOnDelete();
            $table->foreignId('to_warehouse_id')->constrained('warehouses')->restrictOnDelete();
            $table->foreignId('from_branch_id')->constrained('branches')->restrictOnDelete();
            $table->foreignId('to_branch_id')->constrained('branches')->restrictOnDelete();
            $table->string('transfer_type', 50)->default('inter_warehouse'); // inter_warehouse, inter_branch, internal
            $table->string('status', 50)->default('draft'); // draft, pending, approved, in_transit, received, completed, cancelled, rejected
            $table->date('transfer_date')->nullable();
            $table->date('expected_delivery_date')->nullable();
            $table->date('actual_delivery_date')->nullable();
            $table->string('priority', 20)->default('medium'); // low, medium, high, urgent
            $table->text('reason')->nullable();
            $table->text('notes')->nullable();
            $table->text('internal_notes')->nullable();
            $table->string('tracking_number', 100)->nullable();
            $table->string('courier_name', 100)->nullable();
            $table->string('vehicle_number', 50)->nullable();
            $table->string('driver_name', 100)->nullable();
            $table->string('driver_phone', 50)->nullable();
            $table->decimal('shipping_cost', 18, 2)->default(0);
            $table->decimal('insurance_cost', 18, 2)->default(0);
            $table->decimal('total_cost', 18, 2)->default(0);
            $table->string('currency', 10)->default('USD');
            $table->decimal('total_qty_requested', 18, 3)->default(0);
            $table->decimal('total_qty_shipped', 18, 3)->default(0);
            $table->decimal('total_qty_received', 18, 3)->default(0);
            $table->decimal('total_qty_damaged', 18, 3)->default(0);
            $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('shipped_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('shipped_at')->nullable();
            $table->foreignId('received_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('received_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['from_branch_id', 'status']);
            $table->index(['to_branch_id', 'status']);
            $table->index(['status', 'priority']);
            $table->index('transfer_date');
        });

        // Create the missing stock_transfer_items table
        Schema::create('stock_transfer_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stock_transfer_id')->constrained('stock_transfers')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->restrictOnDelete();
            $table->decimal('qty_requested', 18, 3)->default(0);
            $table->decimal('qty_approved', 18, 3)->default(0);
            $table->decimal('qty_shipped', 18, 3)->default(0);
            $table->decimal('qty_received', 18, 3)->default(0);
            $table->decimal('qty_damaged', 18, 3)->default(0);
            $table->string('batch_number', 100)->nullable();
            $table->date('expiry_date')->nullable();
            $table->decimal('unit_cost', 18, 2)->default(0);
            $table->text('condition_on_shipping')->nullable();
            $table->text('condition_on_receiving')->nullable();
            $table->text('notes')->nullable();
            $table->text('damage_report')->nullable();
            $table->timestamps();

            $table->index('stock_transfer_id');
            $table->index('product_id');
        });

        // Fix stock_transfer_approvals - rename transfer_id to stock_transfer_id
        if (Schema::hasTable('stock_transfer_approvals') && Schema::hasColumn('stock_transfer_approvals', 'transfer_id')) {
            Schema::table('stock_transfer_approvals', function (Blueprint $table) {
                $table->dropForeign(['transfer_id']);
                $table->dropIndex(['transfer_id', 'approval_level']);
            });
            
            Schema::table('stock_transfer_approvals', function (Blueprint $table) {
                $table->renameColumn('transfer_id', 'stock_transfer_id');
            });
            
            Schema::table('stock_transfer_approvals', function (Blueprint $table) {
                $table->foreign('stock_transfer_id')->references('id')->on('stock_transfers')->cascadeOnDelete();
                $table->index(['stock_transfer_id', 'approval_level']);
            });
        }

        // Fix stock_transfer_documents - rename transfer_id to stock_transfer_id
        if (Schema::hasTable('stock_transfer_documents') && Schema::hasColumn('stock_transfer_documents', 'transfer_id')) {
            Schema::table('stock_transfer_documents', function (Blueprint $table) {
                $table->dropForeign(['transfer_id']);
                $table->dropIndex(['transfer_id', 'document_type']);
            });
            
            Schema::table('stock_transfer_documents', function (Blueprint $table) {
                $table->renameColumn('transfer_id', 'stock_transfer_id');
            });
            
            Schema::table('stock_transfer_documents', function (Blueprint $table) {
                $table->foreign('stock_transfer_id')->references('id')->on('stock_transfers')->cascadeOnDelete();
                $table->index(['stock_transfer_id', 'document_type']);
            });
        }

        // Fix stock_transfer_history - rename transfer_id to stock_transfer_id
        if (Schema::hasTable('stock_transfer_history') && Schema::hasColumn('stock_transfer_history', 'transfer_id')) {
            Schema::table('stock_transfer_history', function (Blueprint $table) {
                $table->dropForeign(['transfer_id']);
                $table->dropIndex(['transfer_id', 'changed_at']);
            });
            
            Schema::table('stock_transfer_history', function (Blueprint $table) {
                $table->renameColumn('transfer_id', 'stock_transfer_id');
            });
            
            Schema::table('stock_transfer_history', function (Blueprint $table) {
                $table->foreign('stock_transfer_id')->references('id')->on('stock_transfers')->cascadeOnDelete();
                $table->index(['stock_transfer_id', 'changed_at']);
            });
        }
    }

    /**
     * Reverse the migrations.
     * 
     * NOTE: This rollback restores the ORIGINAL BUGGY STATE where the foreign keys
     * pointed to the 'transfers' table instead of 'stock_transfers'. This is intentional
     * to allow the migration to be re-run if needed.
     */
    public function down(): void
    {
        // Revert stock_transfer_history
        if (Schema::hasTable('stock_transfer_history') && Schema::hasColumn('stock_transfer_history', 'stock_transfer_id')) {
            Schema::table('stock_transfer_history', function (Blueprint $table) {
                $table->dropForeign(['stock_transfer_id']);
                $table->dropIndex(['stock_transfer_id', 'changed_at']);
            });
            
            Schema::table('stock_transfer_history', function (Blueprint $table) {
                $table->renameColumn('stock_transfer_id', 'transfer_id');
            });
            
            Schema::table('stock_transfer_history', function (Blueprint $table) {
                $table->foreign('transfer_id')->references('id')->on('transfers')->cascadeOnDelete();
                $table->index(['transfer_id', 'changed_at']);
            });
        }

        // Revert stock_transfer_documents
        if (Schema::hasTable('stock_transfer_documents') && Schema::hasColumn('stock_transfer_documents', 'stock_transfer_id')) {
            Schema::table('stock_transfer_documents', function (Blueprint $table) {
                $table->dropForeign(['stock_transfer_id']);
                $table->dropIndex(['stock_transfer_id', 'document_type']);
            });
            
            Schema::table('stock_transfer_documents', function (Blueprint $table) {
                $table->renameColumn('stock_transfer_id', 'transfer_id');
            });
            
            Schema::table('stock_transfer_documents', function (Blueprint $table) {
                $table->foreign('transfer_id')->references('id')->on('transfers')->cascadeOnDelete();
                $table->index(['transfer_id', 'document_type']);
            });
        }

        // Revert stock_transfer_approvals
        if (Schema::hasTable('stock_transfer_approvals') && Schema::hasColumn('stock_transfer_approvals', 'stock_transfer_id')) {
            Schema::table('stock_transfer_approvals', function (Blueprint $table) {
                $table->dropForeign(['stock_transfer_id']);
                $table->dropIndex(['stock_transfer_id', 'approval_level']);
            });
            
            Schema::table('stock_transfer_approvals', function (Blueprint $table) {
                $table->renameColumn('stock_transfer_id', 'transfer_id');
            });
            
            Schema::table('stock_transfer_approvals', function (Blueprint $table) {
                $table->foreign('transfer_id')->references('id')->on('transfers')->cascadeOnDelete();
                $table->index(['transfer_id', 'approval_level']);
            });
        }

        // Drop the tables
        Schema::dropIfExists('stock_transfer_items');
        Schema::dropIfExists('stock_transfers');
    }
};
