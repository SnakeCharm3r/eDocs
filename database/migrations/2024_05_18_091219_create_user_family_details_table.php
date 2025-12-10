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
        if (!Schema::hasTable('user_family_details')){
        Schema::create('user_family_details', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('userId')->unsigned();
            $table->string('full_name');
            $table->string('relationship');
            $table->string('occupation')->nullable();
            $table->string('phone_number')->nullable();
            $table->boolean('next_of_kin')->default(false);
            $table->boolean('emergency_contact')->default(false);
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
        Schema::dropIfExists('user_family_details');
    }
};