<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Creates the main recruitment_requisitions table for HR.01 form
     */
    public function up(): void
    {
        Schema::create('recruitment_requisitions', function (Blueprint $table) {
            $table->id();
            
            // SECTION 1 - Position Details
            $table->string('job_title');
            $table->enum('background', [
                'new_position',
                'replacement',
                'contract_renewal_extension'
            ]);
            
            // For renewals/extensions
            $table->string('employee_name')->nullable();
            $table->date('current_contract_end_date')->nullable();
            
            // Department and reporting
            $table->foreignId('department_id')->constrained('departments')->onDelete('restrict');
            $table->string('responsibility_centre', 255);
            $table->string('reports_to_position', 255);
            
            // Contract type
            $table->enum('contract_type', [
                'minimal_1_year_employment',
                'termed_less_than_1_year_consultant_task',
                'health_volunteer_50_basic_min_1_year',
                'work_exposure_no_pay_max_2x3_months'
            ]);
            
            // Budget allocation
            $table->boolean('position_approved_in_budget')->default(false);
            $table->decimal('max_monthly_budget', 15, 2)->nullable();
            $table->boolean('funding_available')->default(false);
            $table->string('donor_code', 50)->nullable();
            $table->string('activity_code', 50)->nullable();
            $table->foreignId('payroll_accountant_user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->date('required_starting_date');
            
            // SECTION 2 - Conditions & Justification
            $table->json('conditions')->nullable()->comment('Array of condition IDs [1-5]');
            $table->text('justification_text');
            $table->foreignId('hod_user_id')->constrained('users')->onDelete('restrict')->comment('Head of Department who created this');
            $table->timestamp('hod_signed_at')->nullable();
            
            // Workflow tracking
            $table->boolean('in_budget_flag')->default(false)->comment('Computed: position_approved_in_budget AND funding_available');
            $table->boolean('needs_finance_review')->default(false)->comment('True when HEC has no objection but no budget');
            
            // Status tracking
            $table->enum('status', [
                'draft',
                'submitted_by_hod',
                'hec_review_in_progress',
                'hec_no_objection_in_budget',
                'hec_objection_in_budget',
                'hec_objection_no_budget',
                'hec_no_objection_no_budget',
                'cfo_finance_review_in_progress',
                'cfo_finance_confirmed',
                'cfo_finance_rejected',
                'ceo_review_in_progress',
                'ceo_approved',
                'ceo_declined',
                'ceo_needs_more_info',
                'closed_filed',
                'ready_for_hr_processing',
                'in_recruitment_pipeline'
            ])->default('draft');
            
            // HEC review fields
            $table->enum('hec_decision', ['no_objection', 'objection'])->nullable();
            $table->text('hec_justification_for_no_budget')->nullable();
            $table->string('hec_proposed_funding', 255)->nullable();
            $table->text('hec_comments')->nullable();
            $table->foreignId('hec_reviewed_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('hec_reviewed_at')->nullable();
            
            // CFO Finance Review fields
            $table->text('cfo_financing_confirmation')->nullable();
            $table->string('cfo_financing_code', 100)->nullable();
            $table->text('cfo_comment')->nullable();
            $table->foreignId('cfo_reviewed_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('cfo_reviewed_at')->nullable();
            
            // CEO Decision fields
            $table->enum('ceo_decision', ['approved', 'declined', 'needs_further_information'])->nullable();
            $table->text('ceo_comment')->nullable();
            $table->foreignId('ceo_reviewed_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('ceo_reviewed_at')->nullable();
            
            // HR Processing fields
            $table->boolean('start_recruitment_flag')->default(false);
            $table->text('hr_notes')->nullable();
            $table->date('advert_date')->nullable();
            $table->date('shortlisting_date')->nullable();
            $table->foreignId('hr_processed_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('hr_processed_at')->nullable();
            
            // Current approver tracking
            $table->foreignId('current_approver_id')->nullable()->constrained('users')->onDelete('set null');
            
            // Audit fields
            $table->foreignId('created_by')->constrained('users')->onDelete('restrict');
            $table->foreignId('updated_by')->nullable()->constrained('users')->onDelete('set null');
            
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes for performance
            $table->index('status');
            $table->index('department_id');
            $table->index('hod_user_id');
            $table->index('current_approver_id');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('recruitment_requisitions');
    }
};







