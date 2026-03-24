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
        Schema::table('policies', function (Blueprint $table) {
            // Add division/entity reference
            if (!Schema::hasColumn('policies', 'division_id')) {
                $table->unsignedBigInteger('division_id')->nullable()->after('id');
                $table->foreign('division_id')->references('id')->on('divisions')->onDelete('set null');
            }

            // Add content type (text or pdf)
            if (!Schema::hasColumn('policies', 'content_type')) {
                $table->enum('content_type', ['text', 'pdf'])->default('text')->after('content');
            }

            // Add PDF file path
            if (!Schema::hasColumn('policies', 'pdf_path')) {
                $table->string('pdf_path')->nullable()->after('content_type');
            }

            // Add document code
            if (!Schema::hasColumn('policies', 'document_code')) {
                $table->string('document_code', 50)->nullable()->after('title');
            }

            // Add description
            if (!Schema::hasColumn('policies', 'description')) {
                $table->text('description')->nullable()->after('title');
            }

            // Add global flag (for all users)
            if (!Schema::hasColumn('policies', 'is_global')) {
                $table->boolean('is_global')->default(false)->after('content_type');
            }

            // Add created_by and updated_by
            if (!Schema::hasColumn('policies', 'created_by')) {
                $table->unsignedBigInteger('created_by')->nullable();
                $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
            }

            if (!Schema::hasColumn('policies', 'updated_by')) {
                $table->unsignedBigInteger('updated_by')->nullable();
                $table->foreign('updated_by')->references('id')->on('users')->onDelete('set null');
            }

            // Add status
            if (!Schema::hasColumn('policies', 'status')) {
                $table->enum('status', ['active', 'archived'])->default('active')->after('is_global');
            }
        });

        // Create pivot table for policies and departments (many-to-many)
        if (!Schema::hasTable('policy_department')) {
            Schema::create('policy_department', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('policy_id');
                $table->unsignedBigInteger('department_id');
                $table->unsignedBigInteger('created_by')->nullable();
                $table->timestamps();

                $table->foreign('policy_id')->references('id')->on('policies')->onDelete('cascade');
                $table->foreign('department_id')->references('id')->on('departments')->onDelete('cascade');
                $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');

                $table->unique(['policy_id', 'department_id']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('policies', function (Blueprint $table) {
            // Drop foreign keys first
            if (Schema::hasColumn('policies', 'division_id')) {
                $table->dropForeign(['division_id']);
                $table->dropColumn('division_id');
            }
            if (Schema::hasColumn('policies', 'created_by')) {
                $table->dropForeign(['created_by']);
                $table->dropColumn('created_by');
            }
            if (Schema::hasColumn('policies', 'updated_by')) {
                $table->dropForeign(['updated_by']);
                $table->dropColumn('updated_by');
            }

            // Drop other columns
            $columns = ['content_type', 'pdf_path', 'document_code', 'description', 'is_global', 'status'];
            foreach ($columns as $column) {
                if (Schema::hasColumn('policies', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        Schema::dropIfExists('policy_department');
    }
};
