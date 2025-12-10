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
        Schema::create('requisitions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade')->nullable();
            //$table->bigInteger('job_title_id');
            $table->bigInteger('deptId')->unsigned();
            $table->enum('background', ['new_position', 'replacement', 'contract_renewal']);
            $table->date('contract_end_date')->nullable();
            $table->string('responsibility_centre', 100);
            $table->string('reporting_line', 100);
            $table->enum('contract_type', [
                'minimal_1_year',
                'termed_less_1_year',
                'health_volunteer',
                'work_exposure'
            ]); // Contract type
            $table->boolean('budget_approved')->nullable();
            $table->decimal('max_monthly_budget', 10, 2)->nullable();
            $table->boolean('funding_available')->nullable();
            $table->string('donor_code', 50)->nullable();
            $table->string('activity_code', 50)->nullable();
            $table->date('required_start_date')->nullable();
            $table->string('job_description_file')->nullable();
            $table->json('conditions')->nullable();
            $table->text('reasoning')->nullable();

            //$table->foreign('job_title_id')->references('id')->on('job_titles')->onDelete('cascade');
            $table->foreign('deptId')->references('id')->on('departments');
            $table->foreignId('replacement_user')->nullable()->constrained('users')->onDelete('set null');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('requisitions');
    }
};
