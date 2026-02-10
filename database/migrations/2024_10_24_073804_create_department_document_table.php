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
        if (!Schema::hasTable('department_document')){
        Schema::create('department_document', function (Blueprint $table) {
            $table->id();
            $table->foreignId('DocId')->references('DocId')->on('documents')->onDelete('cascade');
            $table->foreignId('deptId')->references('id')->on('departments')->onDelete('cascade');
            $table->timestamps();
        });}
    }
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('department_document');
    }
};