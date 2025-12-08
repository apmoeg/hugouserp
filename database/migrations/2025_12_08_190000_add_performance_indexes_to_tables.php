<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Add performance indexes to frequently queried columns.
     * Note: Indexes may already exist, so we wrap in try-catch for safety.
     */
    public function up(): void
    {
        // Sales table indexes
        if (Schema::hasTable('sales')) {
            try {
                Schema::table('sales', function (Blueprint $table) {
                    $table->index(['branch_id', 'status'], 'sales_branch_id_status_index');
                });
            } catch (\Exception $e) {
                // Index may already exist
            }
            
            try {
                Schema::table('sales', function (Blueprint $table) {
                    $table->index(['customer_id', 'due_date'], 'sales_customer_id_due_date_index');
                });
            } catch (\Exception $e) {
                // Index may already exist
            }
            
            try {
                Schema::table('sales', function (Blueprint $table) {
                    $table->index('created_at');
                });
            } catch (\Exception $e) {
                // Index may already exist
            }
        }

        // Products table indexes
        if (Schema::hasTable('products')) {
            try {
                Schema::table('products', function (Blueprint $table) {
                    $table->index(['branch_id', 'is_active'], 'products_branch_id_status_index');
                });
            } catch (\Exception $e) {
                // Index may already exist
            }
            
            try {
                Schema::table('products', function (Blueprint $table) {
                    $table->index('sku');
                });
            } catch (\Exception $e) {
                // Index may already exist
            }
            
            try {
                Schema::table('products', function (Blueprint $table) {
                    $table->index('category_id');
                });
            } catch (\Exception $e) {
                // Index may already exist
            }
        }

        // Stock movements table indexes
        if (Schema::hasTable('stock_movements')) {
            try {
                Schema::table('stock_movements', function (Blueprint $table) {
                    $table->index(['product_id', 'warehouse_id'], 'stock_movements_product_warehouse_index');
                });
            } catch (\Exception $e) {
                // Index may already exist
            }
            
            try {
                Schema::table('stock_movements', function (Blueprint $table) {
                    $table->index('created_at');
                });
            } catch (\Exception $e) {
                // Index may already exist
            }
        }

        // Purchases table indexes
        if (Schema::hasTable('purchases')) {
            try {
                Schema::table('purchases', function (Blueprint $table) {
                    $table->index(['branch_id', 'status'], 'purchases_branch_id_status_index');
                });
            } catch (\Exception $e) {
                // Index may already exist
            }
            
            try {
                Schema::table('purchases', function (Blueprint $table) {
                    $table->index('supplier_id');
                });
            } catch (\Exception $e) {
                // Index may already exist
            }
        }

        // Rental contracts table indexes
        if (Schema::hasTable('rental_contracts')) {
            try {
                Schema::table('rental_contracts', function (Blueprint $table) {
                    $table->index('status');
                });
            } catch (\Exception $e) {
                // Index may already exist
            }
            
            try {
                Schema::table('rental_contracts', function (Blueprint $table) {
                    $table->index(['start_date', 'end_date'], 'rental_contracts_start_end_date_index');
                });
            } catch (\Exception $e) {
                // Index may already exist
            }
        }

        // Rental invoices table indexes
        if (Schema::hasTable('rental_invoices')) {
            try {
                Schema::table('rental_invoices', function (Blueprint $table) {
                    $table->index(['status', 'due_date'], 'rental_invoices_status_due_date_index');
                });
            } catch (\Exception $e) {
                // Index may already exist
            }
            
            try {
                Schema::table('rental_invoices', function (Blueprint $table) {
                    $table->index('tenant_id');
                });
            } catch (\Exception $e) {
                // Index may already exist
            }
        }

        // Journal entries table indexes
        if (Schema::hasTable('journal_entries')) {
            try {
                Schema::table('journal_entries', function (Blueprint $table) {
                    $table->index('date');
                });
            } catch (\Exception $e) {
                // Index may already exist
            }
            
            try {
                Schema::table('journal_entries', function (Blueprint $table) {
                    $table->index('status');
                });
            } catch (\Exception $e) {
                // Index may already exist
            }
        }

        // HR Employees table indexes
        if (Schema::hasTable('hr_employees')) {
            try {
                Schema::table('hr_employees', function (Blueprint $table) {
                    $table->index(['branch_id', 'is_active'], 'hr_employees_branch_id_status_index');
                });
            } catch (\Exception $e) {
                // Index may already exist
            }
        }

        // Attendances table indexes
        if (Schema::hasTable('attendances')) {
            try {
                Schema::table('attendances', function (Blueprint $table) {
                    $table->index(['employee_id', 'date'], 'attendances_employee_date_index');
                });
            } catch (\Exception $e) {
                // Index may already exist
            }
        }

        // Tickets table indexes
        if (Schema::hasTable('tickets')) {
            try {
                Schema::table('tickets', function (Blueprint $table) {
                    $table->index(['status', 'priority'], 'tickets_status_priority_index');
                });
            } catch (\Exception $e) {
                // Index may already exist
            }
            
            try {
                Schema::table('tickets', function (Blueprint $table) {
                    $table->index('assigned_to');
                });
            } catch (\Exception $e) {
                // Index may already exist
            }
        }

        // Bank accounts table indexes
        if (Schema::hasTable('bank_accounts')) {
            try {
                Schema::table('bank_accounts', function (Blueprint $table) {
                    $table->index(['branch_id', 'is_active'], 'bank_accounts_branch_id_active_index');
                });
            } catch (\Exception $e) {
                // Index may already exist
            }
        }

        // Audit logs table indexes
        if (Schema::hasTable('audit_logs')) {
            try {
                Schema::table('audit_logs', function (Blueprint $table) {
                    $table->index(['user_id', 'created_at'], 'audit_logs_user_id_created_index');
                });
            } catch (\Exception $e) {
                // Index may already exist
            }
            
            try {
                Schema::table('audit_logs', function (Blueprint $table) {
                    $table->index(['auditable_type', 'auditable_id'], 'audit_logs_auditable_index');
                });
            } catch (\Exception $e) {
                // Index may already exist
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Sales
        if (Schema::hasTable('sales')) {
            Schema::table('sales', function (Blueprint $table) {
                $table->dropIndex('sales_branch_id_status_index');
                $table->dropIndex('sales_customer_id_due_date_index');
                $table->dropIndex(['created_at']);
            });
        }

        // Products
        if (Schema::hasTable('products')) {
            Schema::table('products', function (Blueprint $table) {
                $table->dropIndex('products_branch_id_status_index');
                $table->dropIndex(['sku']);
                $table->dropIndex(['category_id']);
            });
        }

        // Stock movements
        if (Schema::hasTable('stock_movements')) {
            Schema::table('stock_movements', function (Blueprint $table) {
                $table->dropIndex('stock_movements_product_warehouse_index');
                $table->dropIndex(['created_at']);
            });
        }

        // Purchases
        if (Schema::hasTable('purchases')) {
            Schema::table('purchases', function (Blueprint $table) {
                $table->dropIndex('purchases_branch_id_status_index');
                $table->dropIndex(['supplier_id']);
            });
        }

        // Rental contracts
        if (Schema::hasTable('rental_contracts')) {
            Schema::table('rental_contracts', function (Blueprint $table) {
                $table->dropIndex(['status']);
                $table->dropIndex('rental_contracts_start_end_date_index');
            });
        }

        // Rental invoices
        if (Schema::hasTable('rental_invoices')) {
            Schema::table('rental_invoices', function (Blueprint $table) {
                $table->dropIndex('rental_invoices_status_due_date_index');
                $table->dropIndex(['tenant_id']);
            });
        }

        // Journal entries
        if (Schema::hasTable('journal_entries')) {
            Schema::table('journal_entries', function (Blueprint $table) {
                $table->dropIndex(['date']);
                $table->dropIndex(['status']);
            });
        }

        // HR Employees
        if (Schema::hasTable('hr_employees')) {
            Schema::table('hr_employees', function (Blueprint $table) {
                $table->dropIndex('hr_employees_branch_id_status_index');
            });
        }

        // Attendances
        if (Schema::hasTable('attendances')) {
            Schema::table('attendances', function (Blueprint $table) {
                $table->dropIndex('attendances_employee_date_index');
            });
        }

        // Tickets
        if (Schema::hasTable('tickets')) {
            Schema::table('tickets', function (Blueprint $table) {
                $table->dropIndex('tickets_status_priority_index');
                $table->dropIndex(['assigned_to']);
            });
        }

        // Bank accounts
        if (Schema::hasTable('bank_accounts')) {
            Schema::table('bank_accounts', function (Blueprint $table) {
                $table->dropIndex('bank_accounts_branch_id_active_index');
            });
        }

        // Audit logs
        if (Schema::hasTable('audit_logs')) {
            Schema::table('audit_logs', function (Blueprint $table) {
                $table->dropIndex('audit_logs_user_id_created_index');
                $table->dropIndex('audit_logs_auditable_index');
            });
        }
    }
};
