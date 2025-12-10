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
        if (!Schema::hasTable('users')){
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('fname');
            $table->string('mname')->nullable();
            $table->string('lname');
            $table->date('DOB')->nullable();
            $table->string('username');
            $table->enum('gender', ['Male', 'Female'])->nullable();
            $table->enum('marital_status',['Married','Single','Divorced', 'Widower'])->nullable();
            $table->string('region')->nullable();
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('religion')->nullable();
            $table->string('mobile')->nullable();
            $table->bigInteger('job_title')->nullable();
            $table->string('home_address')->nullable();
            $table->string('district')->nullable();
            $table->string('professional_reg_number')->nullable();
            $table->string('place_of_birth')->nullable();
            $table->string('nationality')->nullable();
            $table->string('house_no')->nullable();
            $table->string('street')->nullable();
            $table->string('popular_landmark')->nullable();
            $table->string('plot_no')->nullable();
            $table->string('box_no')->nullable();
            $table->string('emp_id')->nullable();
            $table->bigInteger('deptId')->unsigned();
            $table->bigInteger('employment_typeId')->unsigned();
            $table->string('employee_cv')->nullable();
            $table->string('NIN')->nullable();
            $table->string('nssf_no')->nullable();
            $table->string('tin_no')->nullable();
            $table->string('passport_no')->nullable();
            $table->string('marriage_certificate')->nullable();
            $table->string('divorced_certificate')->nullable();
            $table->string('domicile')->nullable();
            $table->string('profile_picture')->nullable();
            $table->text('signature')->nullable();
            $table->date('starting_date')->nullable();
            $table->date('ending_date')->nullable();
            $table->string('password');
            $table->foreign('deptId')->references('id')->on('departments');
            $table->foreign('job_title')->references('id')->on('job_titles');
            $table->foreign('employment_typeId')->references('id')->on('employment_types');
            $table->string('delete_status')->nullable();
            $table->enum('status', ['active', 'inactive','pending','deactivated'])->default('inactive');
            $table->enum('conflict_officer_role', ['Yes', 'No'])->default('No');
            $table->text('officer_details')->nullable();
            $table->enum('financial_interest', ['Yes', 'No'])->default('No');
            $table->text('financial_details')->nullable();
            $table->enum('other_interests', ['Yes', 'No'])->default('No');
            $table->text('interest_details')->nullable();
            $table->enum('primary_employer_ccbrt', ['Yes', 'No'])->default('Yes');
            $table->text('primary_employer_details')->nullable();
            $table->enum('court_proceedings', ['Yes', 'No'])->default('No');
            $table->text('court_details')->nullable();
            $table->text('hr_detail_declare')->nullable();
            $table->string('ccbrt_code')->nullable()->after('job_title');
            $levels = ['primary', 'o_level', 'a_level', 'certificate', 'diploma', 'degree', 'masters', 'phd'];
            foreach ($levels as $level) {
                $table->string("{$level}_institution")->nullable();
                $table->year("{$level}_start_year")->nullable();
                $table->year("{$level}_completion_year")->nullable();
                $table->string("{$level}_certificate")->nullable();
                if (in_array($level, ['certificate', 'diploma', 'degree', 'masters', 'phd'])) {
                    $table->string("{$level}_transcript")->nullable();
                }
            }
            $table->rememberToken();
            $table->timestamps();
        });}
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
