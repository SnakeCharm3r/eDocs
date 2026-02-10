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
        Schema::create('contract_scores', function (Blueprint $table) {
        $table->id();
        $table->foreignId('contract_id')->constrained('contracts')->onDelete('cascade');
        $table->foreignId('vendor_id')->nullable()->constrained('vendors')->onDelete('set null');
        $table->foreignId('scored_by')->constrained('users')->onDelete('cascade');
        $table->decimal('score_value', 5, 2);
        $table->text('comments')->nullable();
        $table->timestamp('created_at')->useCurrent();
        
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contract_scores');
    }
};
