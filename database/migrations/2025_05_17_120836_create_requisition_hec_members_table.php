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
    Schema::create('requisition_hec_members', function (Blueprint $table) {
    
    $table->id();
    $table->foreignId('requisition_id')->constrained()->onDelete('cascade');
    $table->enum('status', [
        'in_budget_funds_confirmed',
        'not_in_budget_funds_not_confirmed_objection',
        'not_in_budget_funds_not_confirmed_no_objection'
    ]);

    $table->boolean('objection_3a')->nullable();
    $table->text('comments_3a')->nullable();

    $table->boolean('objection_3b')->nullable();
    $table->text('comments_3b')->nullable();

    $table->boolean('objection_3c')->nullable();
    $table->text('justification_3c')->nullable();
    $table->text('proposed_funding_3c')->nullable();

    $table->string('hec_signature')->nullable();
    $table->date('signed_date')->nullable();

    $table->timestamps();
});

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('requisition_hec_members');
    }
};
