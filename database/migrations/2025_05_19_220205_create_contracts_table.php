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
        Schema::create('contracts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contract_template_id')->constrained();
            $table->foreignId('user_id')->constrained();
            $table->date('start_date');
            $table->date('end_date');
            $table->string('place_of_recruitment')->nullable();
            $table->string('duty_station')->nullable();
            $table->string('duration')->nullable();
            $table->string('working_hours')->nullable();
            $table->string('probation_period')->nullable();
            $table->string('basic_pay')->nullable();
            $table->string('total_gross_pay')->nullable();
            $table->string('medical_insurance_employee')->nullable();
            $table->string('medical_insurance_employer')->nullable();
            $table->string('funeral_insurance_eligibility')->nullable();
            $table->string('status')->default('draft');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contracts');
    }
};
