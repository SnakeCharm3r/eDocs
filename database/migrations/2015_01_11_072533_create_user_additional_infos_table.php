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
        if (!Schema::hasTable('user_additional_infos')){
        Schema::create('user_additional_infos', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('userId')->unsigned();
            $table->string('full_name');
            $table->string('relationship');
            $table->string('address');
            $table->string('mobile')->nullable();
            $table->string('email')->nullable();
            $table->string('occupation')->nullable();
            $table->foreign('userId')->references('id')->on('users');
            $table->timestamps();
        });}
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_additional_infos');
    }
};