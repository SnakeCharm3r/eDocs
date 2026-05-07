<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Stores file attachments for recruitment requisitions (job descriptions, etc.)
     */
    public function up(): void
    {
        Schema::create('recruitment_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('requisition_id')->constrained('recruitment_requisitions')->onDelete('cascade');
            $table->string('file_path');
            $table->string('file_type', 50)->default('job_description')->comment('job_description, other');
            $table->string('original_name', 255);
            $table->string('mime_type', 100)->nullable();
            $table->unsignedBigInteger('file_size')->nullable()->comment('Size in bytes');
            $table->foreignId('uploaded_by')->constrained('users')->onDelete('restrict');
            $table->timestamps();
            
            $table->index('requisition_id');
            $table->index('file_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('recruitment_attachments');
    }
};







