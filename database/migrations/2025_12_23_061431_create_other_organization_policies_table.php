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
        if (!Schema::hasTable('other_organization_policies')) {
            Schema::create('other_organization_policies', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('document_code', 50)->nullable();
            $table->text('description')->nullable();
            $table->longtext('content')->nullable();
            $table->enum('content_type', ['text', 'pdf'])->default('text');
            $table->string('pdf_path')->nullable();
            $table->unsignedBigInteger('division_id')->nullable();
            $table->foreign('division_id')->references('id')->on('divisions')->onDelete('set null');
            $table->boolean('is_global')->default(false);
            $table->enum('status', ['active', 'archived'])->default('active');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->foreign('updated_by')->references('id')->on('users')->onDelete('set null');
            $table->timestamps();
            });
        }

        // Create pivot table for other_organization_policies and departments
        if (!Schema::hasTable('other_organization_policy_department')) {
            Schema::create('other_organization_policy_department', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('other_organization_policy_id');
            $table->unsignedBigInteger('department_id');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            $table->foreign('other_organization_policy_id', 'other_org_policy_dept_fk')->references('id')->on('other_organization_policies')->onDelete('cascade');
            $table->foreign('department_id', 'other_org_policy_dept_dept_fk')->references('id')->on('departments')->onDelete('cascade');
            $table->foreign('created_by', 'other_org_policy_dept_user_fk')->references('id')->on('users')->onDelete('set null');

            $table->unique(['other_organization_policy_id', 'department_id'], 'other_org_policy_dept_unique');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('other_organization_policy_department');
        Schema::dropIfExists('other_organization_policies');
    }
};
