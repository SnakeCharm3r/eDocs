<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WorkFlowHistory extends Model
{
    use HasFactory;
    protected $fillable = [
        'work_flow_id',
        'forwarded_by',
        'attended_by',
        'who_approve',
        'step_name',
        'action_taken',
        'rejection_reason',
        'comments',
        'attend_date',
        'status',
        'remark',
        'parent_id',
        'requisition_status',
        'jd_status',
        'locum_agreement_status',
        'locum_request_status',
        'on_call_request_status',
        'professional_reg_verified',
        'professional_reg_verification_notes',
        'license_valid_until',
        'license_provider',
        'decision_date',
    ];

    protected $casts = [
        'professional_reg_verified' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'attended_by');
    }
    public function forwardedBy()
    {
        return $this->belongsTo(User::class, 'forwarded_by');
    }
    public function attendedBy()
    {
        return $this->belongsTo(User::class, 'attended_by');
    }
    public function approver()
    {
        return $this->belongsTo(User::class, 'who_approve');
    }
    public function workflow()
    {
        return $this->belongsTo(Workflow::class, 'work_flow_id');
    }
}
