<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('notes')) {
            Schema::create('notes', function (Blueprint $table) {
                $table->id();
                $table->morphs('noteable');
                $table->text('content');
                $table->string('type')->default('general');
                $table->boolean('is_pinned')->default(false);
                $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
                $table->softDeletes();

                $table->index('created_at');
                $table->index('is_pinned');
            });
        }

        if (! Schema::hasTable('attachments')) {
            Schema::create('attachments', function (Blueprint $table) {
                $table->id();
                $table->morphs('attachable');
                $table->string('filename');
                $table->string('original_filename');
                $table->string('mime_type');
                $table->bigInteger('size');
                $table->string('disk')->default('public');
                $table->string('path');
                $table->string('type')->default('document');
                $table->text('description')->nullable();
                $table->json('metadata')->nullable();
                $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
                $table->softDeletes();

                $table->index('created_at');
                $table->index('type');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('attachments');
        Schema::dropIfExists('notes');
    }
};
