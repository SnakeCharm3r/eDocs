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
        Schema::create('fix_flex_contracts', function (Blueprint $table) {
        $table->id();

        // If existing user, store user_id
        $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
        $table->unsignedBigInteger('department_id')->nullable();

        // For new users
        $table->string('name')->nullable();
        $table->date('date_of_birth')->nullable();
        $table->enum('gender', ['Male', 'Female'])->nullable();
        $table->string('nationality')->nullable();

        $table->foreignId('job_title_id')->nullable()->constrained('job_titles')->nullOnDelete();
        $table->string('duty_station')->nullable();
        $table->string('duration')->nullable();

        $table->decimal('salary_fixed', 15, 2)->nullable();
        $table->decimal('salary_flexible', 15, 2)->nullable();

        $table->string('type')->default('Fix-Flex contract');
        $table->string('working_hours')->nullable();
        $table->string('probation')->nullable();

        $table->date('contract_date')->nullable();
        $table->foreign('department_id')->references('id')->on('departments')->nullOnDelete();

        $table->timestamps();
});

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fix_flex_contracts');
    }
};
