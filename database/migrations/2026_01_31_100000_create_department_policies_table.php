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
        if (!Schema::hasTable('department_policies')) {
            Schema::create('department_policies', function (Blueprint $table) {
                $table->id();
                $table->string('title');
                $table->string('document_code', 50)->nullable();
                $table->text('description')->nullable();
                $table->string('pdf_path'); // PDF only
                $table->unsignedBigInteger('department_id');
                $table->foreign('department_id')->references('id')->on('departments')->onDelete('cascade');
                $table->boolean('visible_to_all_staff')->default(false);
                $table->enum('status', ['active', 'archived'])->default('active');
                $table->unsignedBigInteger('created_by')->nullable();
                $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
                $table->unsignedBigInteger('updated_by')->nullable();
                $table->foreign('updated_by')->references('id')->on('users')->onDelete('set null');
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('department_policies');
    }
};
