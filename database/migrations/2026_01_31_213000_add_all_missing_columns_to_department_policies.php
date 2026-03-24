<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Add any columns that department_policies is missing (table may have been created with minimal schema).
     */
    public function up(): void
    {
        if (!Schema::hasTable('department_policies')) {
            return;
        }

        Schema::table('department_policies', function (Blueprint $table) {
            if (!Schema::hasColumn('department_policies', 'document_code')) {
                $table->string('document_code', 50)->nullable()->after('title');
            }
            if (!Schema::hasColumn('department_policies', 'description')) {
                $table->text('description')->nullable()->after('document_code');
            }
            if (!Schema::hasColumn('department_policies', 'pdf_path')) {
                $table->string('pdf_path')->after('description');
            }
            if (!Schema::hasColumn('department_policies', 'visible_to_all_staff')) {
                $table->boolean('visible_to_all_staff')->default(false)->after('department_id');
            }
            if (!Schema::hasColumn('department_policies', 'status')) {
                $table->enum('status', ['active', 'archived'])->default('active')->after('visible_to_all_staff');
            }
            if (!Schema::hasColumn('department_policies', 'created_by')) {
                $table->unsignedBigInteger('created_by')->nullable()->after('status');
                $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
            }
            if (!Schema::hasColumn('department_policies', 'updated_by')) {
                $table->unsignedBigInteger('updated_by')->nullable()->after('created_by');
                $table->foreign('updated_by')->references('id')->on('users')->onDelete('set null');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!Schema::hasTable('department_policies')) {
            return;
        }

        Schema::table('department_policies', function (Blueprint $table) {
            if (Schema::hasColumn('department_policies', 'updated_by')) {
                $table->dropForeign(['updated_by']);
            }
            if (Schema::hasColumn('department_policies', 'created_by')) {
                $table->dropForeign(['created_by']);
            }
        });
        Schema::table('department_policies', function (Blueprint $table) {
            foreach (['document_code', 'description', 'pdf_path', 'visible_to_all_staff', 'status', 'created_by', 'updated_by'] as $col) {
                if (Schema::hasColumn('department_policies', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
