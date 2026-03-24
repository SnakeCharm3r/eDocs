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
            $table->string('access_id')->unique(); // Unique reference number
            $table->unsignedBigInteger('user_id'); // Initiator (HEC or Line Manager)
            $table->string('initiator_type'); // 'hec_member' or 'line_manager'
            $table->string('initiator_name')->nullable();
            $table->string('initiator_role')->nullable();

            // Section A: Position Details
            $table->enum('position_type', ['new_position', 'replacement', 'contract_renewal']);
            $table->unsignedBigInteger('employee_id')->nullable(); // For replacement/renewal
            $table->string('employee_name')->nullable();
            $table->date('current_contract_end_date')->nullable();
            $table->unsignedBigInteger('line_manager_id')->nullable(); // Selected line manager
            $table->unsignedBigInteger('department_id')->nullable();
            $table->string('dept_name')->nullable();
            $table->string('responsibility_centre')->nullable();
            $table->string('reporting_line')->nullable(); // Reports to position
            $table->unsignedBigInteger('job_title_id')->nullable();
            $table->string('new_job_title')->nullable(); // For new positions

            // Contract Type
            $table->enum('contract_type', [
                'minimal_1_year',
                'termed_less_1_year',
                'health_volunteer',
                'work_exposure'
            ])->nullable();

            $table->date('required_start_date')->nullable();
            $table->string('job_description_path')->nullable(); // PDF upload

            // Section B: Reasoning (PDF Conditions)
            $table->boolean('condition_medical_operational')->default(false);
            $table->boolean('condition_safety_reputational')->default(false);
            $table->boolean('condition_legal_requirement')->default(false);
            $table->boolean('condition_financial_loss')->default(false);
            $table->boolean('condition_increase_income')->default(false);
            $table->text('elaborate_reason')->nullable();

            // Section C: Payroll Accountant Check
            $table->boolean('budget_approved')->nullable(); // Yes/No
            $table->decimal('max_monthly_budget', 15, 2)->nullable();
            $table->boolean('funding_available')->nullable();
            $table->string('donor_code')->nullable();
            $table->string('activity_code')->nullable();
            $table->unsignedBigInteger('payroll_accountant_id')->nullable();
            $table->timestamp('payroll_reviewed_at')->nullable();
            $table->text('payroll_comment')->nullable();

            // Section D: HEC Financial Decision (when no budget)
            $table->enum('hec_financial_decision', [
                'no_financial_implication',
                'proposed_funding',
                'reject',
                'no_objection',
                'objection'
            ])->nullable();
            $table->string('proposed_funding_source')->nullable();
            $table->text('hec_comment')->nullable();
            $table->unsignedBigInteger('hec_reviewer_id')->nullable();
            $table->timestamp('hec_reviewed_at')->nullable();

            // Section E: CFO Approval
            $table->text('cfo_financing_confirmation')->nullable();
            $table->string('cfo_financing_code')->nullable();
            $table->text('cfo_comment')->nullable();
            $table->enum('cfo_decision', ['approved', 'rejected', 'pending'])->nullable();
            $table->unsignedBigInteger('cfo_id')->nullable();
            $table->timestamp('cfo_reviewed_at')->nullable();

            // Section F: CEO Approval
            $table->enum('ceo_decision', ['approved', 'declined', 'need_more_info'])->nullable();
            $table->text('ceo_comment')->nullable();
            $table->unsignedBigInteger('ceo_id')->nullable();
            $table->timestamp('ceo_reviewed_at')->nullable();

            // Section G: HR Final Approval
            $table->enum('hr_decision', ['approved', 'rejected'])->nullable();
            $table->text('hr_comment')->nullable();
            $table->string('hr_attachment_path')->nullable();
            $table->unsignedBigInteger('hr_id')->nullable();
            $table->timestamp('hr_reviewed_at')->nullable();
            $table->boolean('hr_filed_only')->default(false); // For rejected/objection cases

            // Status tracking
            $table->enum('status', [
                'draft',
                'pending_payroll',
                'pending_hec',
                'pending_cfo',
                'pending_ceo',
                'pending_hr',
                'approved',
                'rejected',
                'filed'
            ])->default('draft');

            $table->enum('current_step', [
                'initiation',
                'payroll_review',
                'hec_review',
                'cfo_review',
                'ceo_review',
                'hr_review',
                'completed'
            ])->default('initiation');

            $table->timestamps();

            // Foreign keys
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('employee_id')->references('id')->on('users')->onDelete('set null');
            $table->foreign('line_manager_id')->references('id')->on('users')->onDelete('set null');
            $table->foreign('department_id')->references('id')->on('departments')->onDelete('set null');
            $table->foreign('job_title_id')->references('id')->on('job_titles')->onDelete('set null');
            $table->foreign('payroll_accountant_id')->references('id')->on('users')->onDelete('set null');
            $table->foreign('hec_reviewer_id')->references('id')->on('users')->onDelete('set null');
            $table->foreign('cfo_id')->references('id')->on('users')->onDelete('set null');
            $table->foreign('ceo_id')->references('id')->on('users')->onDelete('set null');
            $table->foreign('hr_id')->references('id')->on('users')->onDelete('set null');
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
