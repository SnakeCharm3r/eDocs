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
        Schema::create('hec_contracts', function (Blueprint $table) {
            $table->id();
            $table->string('contract_number')->unique()->nullable();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('contract_type');
            $table->string('file_path')->nullable();
            $table->string('signed_contract_path')->nullable();
            $table->string('terms_conditions_path')->nullable();
            $table->string('sla_document_path')->nullable();
            $table->decimal('cost', 15, 2)->nullable();
            $table->string('currency')->default('TZS');
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->integer('duration_months')->nullable();
            $table->enum('status', ['draft', 'active', 'expired', 'terminated', 'soonToExpire', 'in_progress', 'renewed'])->default('draft');
            $table->enum('renewal_status', ['not_renewed', 'renewed', 'pending'])->default('not_renewed');
            $table->string('impact_if_not_requested')->nullable(); // Low, Medium, High
            $table->string('likelihood_rating')->nullable(); // Low, Medium, High
            $table->foreignId('contract_owner_id')->nullable()->constrained('users')->onDelete('set null')->comment('Line Manager');
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hec_contracts');
    }
};
