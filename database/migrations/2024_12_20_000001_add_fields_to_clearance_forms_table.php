<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('clearance_forms', function (Blueprint $table) {
            // Employee Information fields
            $table->date('date_of_hire')->nullable()->after('userId');
            $table->date('last_working_day')->nullable()->after('date_of_hire');
            $table->enum('reason_for_leaving', ['Resignation', 'Termination', 'Contract End', 'Retirement', 'Other'])->nullable()->after('last_working_day');
            $table->string('reason_for_leaving_other')->nullable()->after('reason_for_leaving');
            
            // HR Department fields
            $table->boolean('resignation_letter_received')->default(false)->after('reason_for_leaving_other');
            $table->boolean('exit_interview_completed')->default(false)->after('resignation_letter_received');
            $table->boolean('leave_balance_confirmed')->default(false)->after('exit_interview_completed');
            $table->boolean('contract_file_reviewed')->default(false)->after('leave_balance_confirmed');
            
            // Finance Department additional fields
            $table->boolean('all_salary_advances_cleared')->default(false)->after('repaid_salary_advance');
            $table->boolean('allowances_reconciled')->default(false)->after('all_salary_advances_cleared');
            $table->boolean('pending_claims_settled')->default(false)->after('allowances_reconciled');
            $table->boolean('outstanding_loans_recovered')->default(false)->after('pending_claims_settled');
            $table->boolean('employee_eligible_for_final_payment')->nullable()->after('outstanding_loans_recovered');
            $table->text('finance_comments')->nullable()->after('employee_eligible_for_final_payment');
            
            // IT Department additional fields
            $table->string('keyboard_mouse_returned')->nullable()->after('laptop_returned');
            $table->string('phone_returned')->nullable()->after('keyboard_mouse_returned');
            $table->string('id_card_returned')->nullable()->after('phone_returned');
            $table->string('it_ticket_no')->nullable()->after('id_card_returned');
            
            // Administration/Assets fields
            $table->boolean('office_keys_returned')->default(false)->after('office_keys');
            $table->boolean('uniform_ppe_returned')->default(false)->after('ccbrt_uniforms');
            $table->boolean('tools_equipment_returned')->default(false)->after('uniform_ppe_returned');
            $table->boolean('vehicle_clearance')->default(false)->after('office_car_keys');
            
            // Line Manager fields
            $table->boolean('handover_report_received')->default(false)->after('vehicle_clearance');
            $table->boolean('work_responsibilities_transferred')->default(false)->after('handover_report_received');
            $table->boolean('projects_tasks_closed')->default(false)->after('work_responsibilities_transferred');
            
            // Final HR Approval
            $table->boolean('all_clearances_completed')->default(false)->after('projects_tasks_closed');
            $table->text('hr_manager_comments')->nullable()->after('all_clearances_completed');
            
            // Employee Confirmation
            $table->boolean('employee_confirmed')->default(false)->after('hr_manager_comments');
            $table->timestamp('employee_confirmed_at')->nullable()->after('employee_confirmed');
        });
    }

    public function down()
    {
        Schema::table('clearance_forms', function (Blueprint $table) {
            $table->dropColumn([
                'date_of_hire',
                'last_working_day',
                'reason_for_leaving',
                'reason_for_leaving_other',
                'resignation_letter_received',
                'exit_interview_completed',
                'leave_balance_confirmed',
                'contract_file_reviewed',
                'all_salary_advances_cleared',
                'allowances_reconciled',
                'pending_claims_settled',
                'outstanding_loans_recovered',
                'employee_eligible_for_final_payment',
                'finance_comments',
                'keyboard_mouse_returned',
                'phone_returned',
                'id_card_returned',
                'it_ticket_no',
                'office_keys_returned',
                'uniform_ppe_returned',
                'tools_equipment_returned',
                'vehicle_clearance',
                'handover_report_received',
                'work_responsibilities_transferred',
                'projects_tasks_closed',
                'all_clearances_completed',
                'hr_manager_comments',
                'employee_confirmed',
                'employee_confirmed_at',
            ]);
        });
    }
};

