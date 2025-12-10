<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Helper table to track which HEC roles a user has
     */
    public function up(): void
    {
        Schema::create('hec_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users')->onDelete('cascade');
            $table->boolean('is_cfo')->default(false);
            $table->boolean('is_coo')->default(false);
            $table->boolean('is_cms')->default(false);
            $table->boolean('is_ccdro')->default(false);
            $table->timestamps();
            
            $table->index('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hec_profiles');
    }
};







