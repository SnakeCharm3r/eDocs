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
        if (!Schema::hasTable('sops')){
        Schema::create('sops', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('deptId');
            $table->string('title');
            $table->string('pdf_path')->nullable();
            $table->foreign('deptId')->references('id')->on('departments')->onDelete('cascade');
            $table->timestamps();
        });}
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sops');
    }
};