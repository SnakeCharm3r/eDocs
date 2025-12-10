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
        if (!Schema::hasTable('health_details')){
        Schema::create('health_details', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('userId')->unsigned();
            $table->string('physical_disability');
            $table->string('blood_group')->nullable();
            $table->string('illness_history')->nullable();
            $table->enum('health_insurance',['Yes', 'No']);
            $table->string('insur_name')->nullable();
            $table->string('insur_no')->nullable();
            $table->string('allergies')->nullable();
            $table->string('delete_status')->nullable();
            $table->foreign('userId')->references('id')->on('users');
            $table->timestamps();
        });}
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('health_details');
    }
};