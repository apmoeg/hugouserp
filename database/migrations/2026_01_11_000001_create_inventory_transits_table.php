<?php

declare(strict_types=1);

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
        Schema::create('inventory_transits', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_0900_ai_ci';
            
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('from_warehouse_id')->constrained('warehouses');
            $table->foreignId('to_warehouse_id')->constrained('warehouses');
            $table->foreignId('stock_transfer_id')->nullable()->constrained('stock_transfers')->nullOnDelete();
            $table->string('reference_type', 255)->nullable(); // polymorphic reference
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->decimal('quantity', 18, 4);
            $table->decimal('unit_cost', 18, 4)->nullable();
            $table->string('batch_number', 100)->nullable();
            $table->date('expiry_date')->nullable();
            $table->string('status', 50)->default('in_transit'); // in_transit, received, cancelled
            $table->timestamp('shipped_at')->nullable();
            $table->timestamp('expected_arrival')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            // Indexes
            $table->index(['product_id', 'status']);
            $table->index(['from_warehouse_id', 'to_warehouse_id']);
            $table->index(['stock_transfer_id', 'status']);
            $table->index(['reference_type', 'reference_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventory_transits');
    }
};
