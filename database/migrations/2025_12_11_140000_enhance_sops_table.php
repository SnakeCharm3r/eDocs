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
        Schema::table('sops', function (Blueprint $table) {
            // Add division/entity reference
            if (!Schema::hasColumn('sops', 'division_id')) {
                $table->unsignedBigInteger('division_id')->nullable()->after('id');
                $table->foreign('division_id')->references('id')->on('divisions')->onDelete('set null');
            }

            // Add date fields for SOP validity
            if (!Schema::hasColumn('sops', 'effective_date')) {
                $table->date('effective_date')->nullable()->after('pdf_path');
            }

            if (!Schema::hasColumn('sops', 'expiry_date')) {
                $table->date('expiry_date')->nullable()->after('effective_date');
            }

            // Add version control
            if (!Schema::hasColumn('sops', 'version')) {
                $table->string('version', 20)->default('1.0')->after('expiry_date');
            }

            // Add SOP status (active, archived, expired)
            if (!Schema::hasColumn('sops', 'status')) {
                $table->enum('status', ['active', 'archived', 'expired'])->default('active')->after('version');
            }

            // Add description field
            if (!Schema::hasColumn('sops', 'description')) {
                $table->text('description')->nullable()->after('title');
            }

            // Add document number/code
            if (!Schema::hasColumn('sops', 'document_code')) {
                $table->string('document_code', 50)->nullable()->after('title');
            }

            // Archive tracking
            if (!Schema::hasColumn('sops', 'archived_at')) {
                $table->timestamp('archived_at')->nullable();
            }

            if (!Schema::hasColumn('sops', 'archived_by')) {
                $table->unsignedBigInteger('archived_by')->nullable();
                $table->foreign('archived_by')->references('id')->on('users')->onDelete('set null');
            }

            // Updated by tracking
            if (!Schema::hasColumn('sops', 'updated_by')) {
                $table->unsignedBigInteger('updated_by')->nullable();
                $table->foreign('updated_by')->references('id')->on('users')->onDelete('set null');
            }

            // File type tracking (pdf, doc, docx)
            if (!Schema::hasColumn('sops', 'file_type')) {
                $table->string('file_type', 10)->nullable()->after('pdf_path');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sops', function (Blueprint $table) {
            // Drop foreign keys first
            if (Schema::hasColumn('sops', 'division_id')) {
                $table->dropForeign(['division_id']);
                $table->dropColumn('division_id');
            }
            if (Schema::hasColumn('sops', 'archived_by')) {
                $table->dropForeign(['archived_by']);
                $table->dropColumn('archived_by');
            }
            if (Schema::hasColumn('sops', 'updated_by')) {
                $table->dropForeign(['updated_by']);
                $table->dropColumn('updated_by');
            }

            // Drop other columns
            $columns = ['effective_date', 'expiry_date', 'version', 'status', 'description', 'document_code', 'archived_at', 'file_type'];
            foreach ($columns as $column) {
                if (Schema::hasColumn('sops', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
