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
        if (!Schema::hasTable('hr_documents')){
        Schema::create('hr_documents', function (Blueprint $table) {
            $table->id('DocId');
            // $table->unsignedBigInteger('deptId');//var pulling all departments aand referencing them to the document table
            $table->string('DocumentName');
            $table->string('DocumentPath');  // Path to where the file is stored
            $table->string('Type');
            // $table->string('Category');   // Type of document (e.g., payslip, contract, etc.)
            // $table->foreign('deptId')->references('id')->on('departments')->onDelete('cascade');
            $table->timestamps();
        });}
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hr_documents');
    }
};