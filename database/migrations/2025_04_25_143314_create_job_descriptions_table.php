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
        Schema::create('job_descriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
            $table->string('job_title')->nullable();
            $table->string('employee_type')->nullable(); // or place it where you pref
            $table->text('jobs_responsible_for')->nullable();
            $table->string('working_hours')->nullable();
            $table->date('job_review_date')->nullable();
            $table->string('job_grade')->nullable();
            $table->string('region_location')->nullable();
            $table->text('reports_to')->nullable();
            $table->string('technical_job_level')->nullable();
            $table->string('grade_job_holder')->nullable();
            $table->text('grade_difference_reason')->nullable();
            $table->text('purpose')->nullable();
            $table->text('accountabilities')->nullable();
            $table->text('qualifications_experience')->nullable();
            $table->text('competencies')->nullable();
            $table->text('financial_details')->nullable();
            $table->string('employees_managed')->nullable();
            $table->text('stakeholders_managed')->nullable();
            $table->text('org_structure')->nullable();
            $table->timestamps();
        });
        
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('job_descriptions');
    }
};
