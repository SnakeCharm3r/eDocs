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
        if (!Schema::hasTable('job_titles')){
        Schema::create('job_titles', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('deptId')->unsigned();
            $table->string('job_title');
            $table->foreign('deptId')->references('id')->on('departments');
            $table->timestamps();
        });
    }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('job_titles');
    }
};