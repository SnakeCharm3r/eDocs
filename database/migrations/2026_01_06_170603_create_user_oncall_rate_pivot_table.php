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
        Schema::create('user_oncall_rate', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('on_call_rate_id')->constrained('on_call_rates')->onDelete('cascade');
            $table->timestamps();
            
            // Ensure a user can't have the same rate assigned twice
            $table->unique(['user_id', 'on_call_rate_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_oncall_rate');
    }
};
