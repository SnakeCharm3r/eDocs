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
        if (!Schema::hasTable('h_m_i_s_access_levels')){
        Schema::create('h_m_i_s_access_levels', function (Blueprint $table) {
            $table->id();
            $table->string('names')->nullable();
            $table->string('status')->nullable();
            $table->string('delete_status')->nullable();
            $table->timestamps();
        });}
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('h_m_i_s_access_levels');
    }
};