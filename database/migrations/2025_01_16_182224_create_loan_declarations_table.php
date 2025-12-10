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
        if (!Schema::hasTable('loan_declarations')){
        Schema::create('loan_declarations', function (Blueprint $table) {
            $table->id();

            $table->foreignId('userId')->references('id')->on('users');
            $table->string('form_iv_index')->nullable();
            $table->enum('has_loan', ['yes', 'no'])->default('no');
            $table->timestamps();
        });}
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('loan_declarations');
    }
};