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
        Schema::create('contract_renewals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contract_id')->constrained('ccbrt_contracts')->onDelete('cascade');
            $table->foreignId('vendor_id')->constrained('ccbrt_vendors')->onDelete('cascade');
            $table->date('start_date');
            $table->date('end_date');
            $table->integer('notice_period_months')->default(0);
            $table->enum('status', ['draft', 'active', 'soon_to_expire', 'expired', 'terminated'])->default('draft');
            $table->enum('renewal_type', ['standard', 'partial', 'extension'])->default('Standard');
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contract_renewals');
    }
};
