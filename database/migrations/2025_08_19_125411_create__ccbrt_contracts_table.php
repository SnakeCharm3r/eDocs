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
        Schema::create('ccbrt_contracts', function (Blueprint $table) {
        $table->id();
        $table->string('title');
        $table->string('contract_type');
        $table->string('file_path')->nullable(); // PDF path
        $table->decimal('cost', 15, 2)->nullable();
        $table->foreignId('division_id')->nullable()->constrained('divisions')->onDelete('set null');
        $table->foreignId('department_id')->nullable()->constrained('departments')->onDelete('set null');
        $table->foreignId('vendor_id')->nullable()->constrained('ccbrt_vendors')->onDelete('set null');
        $table->date('creation_date')->nullable();
        $table->integer('duration_months')->nullable();
        $table->date('end_date')->nullable();
        $table->enum('status', ['draft', 'active', 'expired', 'terminated','soonToExpire']);
        $table->enum('renewal_status', ['not_renewed', 'renewed', 'pending'])->default('not_renewed');
        $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
        $table->timestamps();
    });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('_ccbrt_contracts');
    }
};
