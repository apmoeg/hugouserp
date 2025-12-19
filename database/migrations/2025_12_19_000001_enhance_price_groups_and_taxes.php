<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('price_groups')) {
            Schema::table('price_groups', function (Blueprint $table) {
                if (! Schema::hasColumn('price_groups', 'branch_id')) {
                    $table->unsignedBigInteger('branch_id')->nullable()->after('id');
                    $table->foreign('branch_id')->references('id')->on('branches')->onDelete('set null');
                }

                if (! Schema::hasColumn('price_groups', 'code')) {
                    $table->string('code', 50)->nullable()->after('name');
                    $table->unique('code');
                }

                if (! Schema::hasColumn('price_groups', 'is_active')) {
                    $table->boolean('is_active')->default(true)->after('description');
                }

                if (! Schema::hasColumn('price_groups', 'extra_attributes')) {
                    $table->json('extra_attributes')->nullable()->after('is_active');
                }
            });
        }

        if (Schema::hasTable('taxes')) {
            Schema::table('taxes', function (Blueprint $table) {
                if (! Schema::hasColumn('taxes', 'branch_id')) {
                    $table->unsignedBigInteger('branch_id')->nullable()->after('id');
                    $table->foreign('branch_id')->references('id')->on('branches')->onDelete('set null');
                }

                if (! Schema::hasColumn('taxes', 'code')) {
                    $table->string('code', 50)->nullable()->after('name');
                    $table->unique('code');
                }

                if (! Schema::hasColumn('taxes', 'description')) {
                    $table->text('description')->nullable()->after('code');
                }

                if (! Schema::hasColumn('taxes', 'is_active')) {
                    $table->boolean('is_active')->default(true)->after('is_inclusive');
                }

                if (! Schema::hasColumn('taxes', 'extra_attributes')) {
                    $table->json('extra_attributes')->nullable()->after('is_active');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('price_groups')) {
            Schema::table('price_groups', function (Blueprint $table) {
                if (Schema::hasColumn('price_groups', 'code')) {
                    $table->dropUnique('price_groups_code_unique');
                    $table->dropColumn('code');
                }

                if (Schema::hasColumn('price_groups', 'branch_id')) {
                    $table->dropForeign(['branch_id']);
                    $table->dropColumn('branch_id');
                }

                if (Schema::hasColumn('price_groups', 'is_active')) {
                    $table->dropColumn('is_active');
                }

                if (Schema::hasColumn('price_groups', 'extra_attributes')) {
                    $table->dropColumn('extra_attributes');
                }
            });
        }

        if (Schema::hasTable('taxes')) {
            Schema::table('taxes', function (Blueprint $table) {
                if (Schema::hasColumn('taxes', 'code')) {
                    $table->dropUnique('taxes_code_unique');
                    $table->dropColumn('code');
                }

                if (Schema::hasColumn('taxes', 'description')) {
                    $table->dropColumn('description');
                }

                if (Schema::hasColumn('taxes', 'branch_id')) {
                    $table->dropForeign(['branch_id']);
                    $table->dropColumn('branch_id');
                }

                if (Schema::hasColumn('taxes', 'is_active')) {
                    $table->dropColumn('is_active');
                }

                if (Schema::hasColumn('taxes', 'extra_attributes')) {
                    $table->dropColumn('extra_attributes');
                }
            });
        }
    }
};
