<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Complete audit trail of all workflow steps and decisions
     */
    public function up(): void
    {
        Schema::create('recruitment_workflow_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('requisition_id')->constrained('recruitment_requisitions')->onDelete('cascade');
            $table->string('step_name', 50)->comment('HOD, HEC, CFO_FINANCE, CEO, HR');
            $table->string('from_status', 50)->nullable();
            $table->string('to_status', 50);
            $table->enum('action', [
                'approved',
                'rejected',
                'no_objection',
                'objection',
                'returned',
                'submitted',
                'confirmed',
                'declined',
                'needs_more_info'
            ]);
            $table->foreignId('attended_by')->constrained('users')->onDelete('restrict');
            $table->text('comments')->nullable();
            $table->json('metadata')->nullable()->comment('Additional data like budget amounts, etc.');
            $table->timestamps();
            
            $table->index('requisition_id');
            $table->index('attended_by');
            $table->index('step_name');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('recruitment_workflow_histories');
    }
};







