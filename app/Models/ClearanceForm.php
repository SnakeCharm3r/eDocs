<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClearanceForm extends Model
{
    use HasFactory;

    protected $fillable = [
        'userId',
        'status',
        'rejection_reason',
        'date',
        'date_of_hire',
        'last_working_day',
        'end_of_contract',
        'reason_for_leaving',
        'reason_for_leaving_other',
        'exit_reason',
        'exit_explanation',
        'suggestions',
        'would_recommend',
        'ccbrt_id_card',
        'ccbrt_name_tag',
        'nhif_cards',
        'work_permit_cancelled',
        'residence_permit_cancelled',
        'resignation_letter_received',
        'exit_interview_completed',
        'exit_interview_document',
        'leave_balance_confirmed',
        'contract_file_reviewed',
        'repaid_salary_advance',
        'all_salary_advances_cleared',
        'allowances_reconciled',
        'pending_claims_settled',
        'outstanding_loans_recovered',
        'loan_balances_informed',
        'repaid_outstanding_imprest',
        'employee_eligible_for_final_payment',
        'finance_comments',
        'changing_room_keys',
        'office_keys',
        'office_keys_returned',
        'mobile_phone',
        'camera',
        'ccbrt_uniforms',
        'uniform_ppe_returned',
        'tools_equipment_returned',
        'office_car_keys',
        'vehicle_clearance',
        'handover_report_received',
        'work_responsibilities_transferred',
        'projects_tasks_closed',
        'line_manager_comments',
        'other_items',
        'laptop_returned',
        'keyboard_mouse_returned',
        'phone_returned',
        'id_card_returned',
        'access_card_returned',
        'domain_account_disabled',
        'email_account_disabled',
        'telephone_pin_disabled',
        'health_ai_disabled',
        'openclinic_account_disabled',
        'sap_account_disabled',
        'aruti_account_disabled',
        'it_ticket_no',
        'all_clearances_completed',
        'hr_manager_comments',
        'employee_confirmed',
        'employee_confirmed_at',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'userId');
    }

    public function workflow()
    {
        return $this->hasOne(Clearance_work_flow::class, 'requested_resource_id', 'id');
    }
}

