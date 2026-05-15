<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('night_shift_claims', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('department_id')->nullable();
            $table->unsignedBigInteger('submitted_by'); // LM user id
            $table->string('month');          // e.g. "February"
            $table->unsignedSmallInteger('year');
            $table->string('type_of_allowance')->default('Night Duty');
            $table->unsignedInteger('number_of_employees')->default(0);
            $table->unsignedInteger('total_days_worked')->default(0);
            $table->string('status')->default('pending'); // pending / approved / rejected
            $table->text('rejection_reason')->nullable();
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();

            $table->foreign('submitted_by')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('approved_by')->references('id')->on('users')->onDelete('set null');
        });

        Schema::create('night_shift_claim_employees', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('night_shift_claim_id');
            $table->unsignedBigInteger('user_id')->nullable(); // if staff exists in system
            $table->string('emp_code');
            $table->string('employee_name');
            $table->unsignedInteger('days_on_duty')->default(0);
            $table->timestamps();

            $table->foreign('night_shift_claim_id')
                  ->references('id')->on('night_shift_claims')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('night_shift_claim_employees');
        Schema::dropIfExists('night_shift_claims');
    }
};
