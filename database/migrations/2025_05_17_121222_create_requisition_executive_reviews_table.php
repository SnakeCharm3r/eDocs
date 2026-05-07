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
        Schema::create('requisition_executive_reviews', function (Blueprint $table) {
            $table->id();

            // Foreign key to requisitions
            $table->foreignId('requisition_id')->constrained()->onDelete('cascade');

            // Foreign key to requisition_hec_members
            $table->foreignId('requisition_hec_member_id')->constrained()->onDelete('cascade');

            // CFO Section
            $table->string('financing_confirmation')->nullable();
            $table->string('financing_code')->nullable();
            $table->text('cfo_comment')->nullable();
            $table->string('cfo_signature')->nullable();
            $table->date('cfo_signed_date')->nullable();

            // CEO Section
            $table->enum('decision_on_hiring', ['approved', 'declined', 'needs_more_info'])->nullable();
            $table->timestamps();
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('requisition_executive_reviews');
    }
};
