<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('rental_contracts')) {
            return;
        }

        Schema::table('rental_contracts', function (Blueprint $table) {
            if (! Schema::hasColumn('rental_contracts', 'expiration_notified_at')) {
                $table->timestamp('expiration_notified_at')->nullable();
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('rental_contracts')) {
            return;
        }

        Schema::table('rental_contracts', function (Blueprint $table) {
            if (Schema::hasColumn('rental_contracts', 'expiration_notified_at')) {
                $table->dropColumn('expiration_notified_at');
            }
        });
    }
};
