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
        // Bill of Materials (BOM) - Recipe for manufactured products
        Schema::create('bills_of_materials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete(); // Finished good
            $table->string('bom_number')->unique();
            $table->string('name');
            $table->string('name_ar')->nullable();
            $table->text('description')->nullable();
            $table->decimal('quantity', 10, 2)->default(1.00); // How many units this BOM produces
            $table->enum('status', ['draft', 'active', 'archived'])->default('draft');
            $table->decimal('scrap_percentage', 5, 2)->default(0.00); // Expected waste
            $table->boolean('is_multi_level')->default(false); // Has sub-BOMs
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['branch_id', 'status']);
            $table->index('product_id');
        });

        // BOM Items - Components/raw materials needed
        Schema::create('bom_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bom_id')->constrained('bills_of_materials')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete(); // Component/raw material
            $table->decimal('quantity', 10, 4); // Quantity needed per unit
            $table->foreignId('unit_id')->nullable()->constrained('units_of_measure')->nullOnDelete();
            $table->decimal('scrap_percentage', 5, 2)->default(0.00); // Item-specific scrap
            $table->integer('sort_order')->default(0);
            $table->text('notes')->nullable();
            $table->boolean('is_alternative')->default(false); // Can be substituted
            $table->foreignId('alternative_group_id')->nullable(); // Group alternatives together
            $table->json('metadata')->nullable();
            $table->timestamps();
            
            $table->index('bom_id');
            $table->index('product_id');
        });

        // Work Centers - Production stations/machines
        Schema::create('work_centers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->string('code')->unique();
            $table->string('name');
            $table->string('name_ar')->nullable();
            $table->text('description')->nullable();
            $table->enum('type', ['manual', 'machine', 'assembly', 'quality_control', 'packaging'])->default('manual');
            $table->decimal('capacity_per_hour', 10, 2)->nullable(); // Units per hour
            $table->decimal('cost_per_hour', 10, 2)->default(0.00); // Operating cost
            $table->enum('status', ['active', 'maintenance', 'inactive'])->default('active');
            $table->json('operating_hours')->nullable(); // Schedule
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['branch_id', 'status']);
        });

        // BOM Operations - Steps in production process
        Schema::create('bom_operations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bom_id')->constrained('bills_of_materials')->cascadeOnDelete();
            $table->foreignId('work_center_id')->constrained('work_centers')->cascadeOnDelete();
            $table->string('operation_name');
            $table->string('operation_name_ar')->nullable();
            $table->text('description')->nullable();
            $table->integer('sequence')->default(0); // Order of operations
            $table->decimal('duration_minutes', 10, 2); // Expected time
            $table->decimal('setup_time_minutes', 10, 2)->default(0.00);
            $table->decimal('labor_cost', 10, 2)->default(0.00);
            $table->json('quality_criteria')->nullable(); // QC checkpoints
            $table->json('metadata')->nullable();
            $table->timestamps();
            
            $table->index('bom_id');
            $table->index(['work_center_id', 'sequence']);
        });

        // Production Orders - Manufacturing jobs
        Schema::create('production_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->string('order_number')->unique();
            $table->foreignId('bom_id')->constrained('bills_of_materials');
            $table->foreignId('product_id')->constrained(); // Product to manufacture
            $table->foreignId('warehouse_id')->constrained(); // Where to store finished goods
            $table->decimal('quantity_planned', 10, 2);
            $table->decimal('quantity_produced', 10, 2)->default(0.00);
            $table->decimal('quantity_scrapped', 10, 2)->default(0.00);
            $table->enum('status', ['draft', 'released', 'in_progress', 'completed', 'cancelled'])->default('draft');
            $table->enum('priority', ['low', 'normal', 'high', 'urgent'])->default('normal');
            $table->date('planned_start_date')->nullable();
            $table->date('planned_end_date')->nullable();
            $table->dateTime('actual_start_date')->nullable();
            $table->dateTime('actual_end_date')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->decimal('estimated_cost', 15, 2)->default(0.00);
            $table->decimal('actual_cost', 15, 2)->default(0.00);
            $table->foreignId('sale_id')->nullable()->constrained()->nullOnDelete(); // Make-to-order link
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['branch_id', 'status']);
            $table->index('order_number');
            $table->index('product_id');
        });

        // Production Order Items - Materials consumed
        Schema::create('production_order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('production_order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained(); // Raw material
            $table->decimal('quantity_required', 10, 4);
            $table->decimal('quantity_consumed', 10, 4)->default(0.00);
            $table->foreignId('unit_id')->nullable()->constrained('units_of_measure')->nullOnDelete();
            $table->decimal('unit_cost', 10, 2)->default(0.00);
            $table->decimal('total_cost', 10, 2)->default(0.00);
            $table->foreignId('warehouse_id')->nullable()->constrained()->nullOnDelete();
            $table->boolean('is_issued')->default(false); // Material picked from warehouse
            $table->dateTime('issued_at')->nullable();
            $table->timestamps();
            
            $table->index('production_order_id');
            $table->index('product_id');
        });

        // Production Order Operations - Actual work done
        Schema::create('production_order_operations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('production_order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('bom_operation_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('work_center_id')->constrained('work_centers');
            $table->string('operation_name');
            $table->integer('sequence')->default(0);
            $table->enum('status', ['pending', 'in_progress', 'completed', 'on_hold'])->default('pending');
            $table->decimal('planned_duration_minutes', 10, 2);
            $table->decimal('actual_duration_minutes', 10, 2)->default(0.00);
            $table->dateTime('started_at')->nullable();
            $table->dateTime('completed_at')->nullable();
            $table->foreignId('operator_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->json('quality_results')->nullable(); // QC results
            $table->timestamps();
            
            $table->index('production_order_id');
            $table->index(['work_center_id', 'status']);
        });

        // Manufacturing Transactions - Accounting link
        Schema::create('manufacturing_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('production_order_id')->constrained()->cascadeOnDelete();
            $table->enum('transaction_type', ['material_issue', 'labor_cost', 'overhead_cost', 'finished_good']);
            $table->decimal('amount', 15, 2);
            $table->foreignId('journal_entry_id')->nullable()->constrained()->nullOnDelete();
            $table->text('description')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            
            $table->index('production_order_id');
            $table->index('transaction_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('manufacturing_transactions');
        Schema::dropIfExists('production_order_operations');
        Schema::dropIfExists('production_order_items');
        Schema::dropIfExists('production_orders');
        Schema::dropIfExists('bom_operations');
        Schema::dropIfExists('work_centers');
        Schema::dropIfExists('bom_items');
        Schema::dropIfExists('bills_of_materials');
    }
};
