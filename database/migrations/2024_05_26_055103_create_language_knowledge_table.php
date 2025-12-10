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
        if (!Schema::hasTable('language_knowledge')){
        Schema::create('language_knowledge', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('userId')->unsigned();
            $table->string('language');
            $table->string('speaking');
            $table->string('reading');
            $table->string('writing');
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
        Schema::dropIfExists('language_knowledge');
    }
};