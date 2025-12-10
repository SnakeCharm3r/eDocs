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
        Schema::create('hec_contract_owners', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hec_contract_id')->constrained('hec_contracts')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade')->comment('HEC Member who will receive notifications');
            $table->timestamps();
            
            // Ensure a HEC member can only be assigned once per contract
            $table->unique(['hec_contract_id', 'user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hec_contract_owners');
    }
};
