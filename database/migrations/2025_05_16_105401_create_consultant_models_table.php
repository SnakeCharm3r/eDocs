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
        Schema::create('consultant_models', function (Blueprint $table) {
            $table->id();
            $table->string('application_date');
            $table->string('consultant_full_name');
            $table->string('consultant_address');
            $table->string('consultant_mobile');
            $table->string('qualification');
            $table->string('year_of_experience');
            $table->bigInteger('hosted_dept')->unsigned();
            $table->string('startDate');
            $table->string('endDate');
            $table->bigInteger('createdBy')->unsigned()->nullable();
            $table->foreign('createdBy')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('hosted_dept')->references('id')->on('departments')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('consultant_models');
    }
};
