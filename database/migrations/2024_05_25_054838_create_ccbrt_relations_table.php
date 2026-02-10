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
        if (!Schema::hasTable('ccbrt_relations')){
        Schema::create('ccbrt_relations', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('userId')->unsigned();
            $table->string('names')->nullable() ;
            $table->string('position')->nullable();
            $table->string('department')->nullable();
            $table->string('relation')->nullable();
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
        Schema::dropIfExists('ccbrt_relations');
    }
};