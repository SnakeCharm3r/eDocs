<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Requisition extends Model
{
    use HasFactory;
    protected $fillable = [
        'user_id',
        'initiator_id',
        'initiator_role',
        'initiator_name',
        'employee_id',
        'job_title_id',
        'deptId',
        'background',
        'contract_end_date',
        'new_job_title',
        'responsibility_centre',
        'reporting_line',
        'contract_type',
        'budget_approved',
        'max_monthly_budget',
        'funding_available',
        'donor_code',
        'activity_code',
        'required_start_date',
        'job_description_file',
        'conditions',
        'replacement_user',
        'reasoning',
        'hec_objection',
        'hec_member_comment',
        'hec_justification',
        'hec_justification_type',
        'hec_proposed_funding',
        'cfo_comment',
        'cfo_financing_code',
        'cfo_financing_confirmation',
        'ceo_decision',
        'ceo_comment',
        'hr_comments',
        'hr_signature_date',
        'hr_processed',
        'hr_processed_at'
    ];

    protected $casts = [
        'conditions' => 'array',
        'budget_approved' => 'boolean',
        'funding_available' => 'boolean',
        'max_monthly_budget' => 'decimal:2',
        'required_start_date' => 'date',
        'contract_end_date' => 'date',
        'hr_processed' => 'boolean',
        'hr_signature_date' => 'date',
        'hr_processed_at' => 'datetime',
    ];


    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function jobTitle()
    {
        return $this->belongsTo(JobTitle::class, 'job_title_id');
    }

    public function department()
    {
        return $this->belongsTo(Departments::class, 'deptId');
    }
    public function replacementUser()
    {
        return $this->belongsTo(User::class, 'replacement_user');
    }
    public function employee()
    {
        return $this->belongsTo(User::class, 'employee_id');
    }

    public function workflow()
    {
        return $this->hasOne(Workflow::class, 'requisition_id');
    }
}
