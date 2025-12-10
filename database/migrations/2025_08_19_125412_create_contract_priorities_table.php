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
        Schema::create('contract_priorities', function (Blueprint $table) {
        $table->id();
        $table->foreignId('contract_id')->constrained('contracts')->onDelete('cascade');
        $table->tinyInteger('priority_level')->comment('0=low,1=high');
        $table->foreignId('changed_by')->constrained('users')->onDelete('cascade');
        $table->text('change_reason')->nullable();
        $table->timestamp('created_at')->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contract_priorities');
    }
};
