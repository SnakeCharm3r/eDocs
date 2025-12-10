<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Workflow extends Model
{
    use HasFactory;
    protected $fillable = [
        'user_id',
        'ict_request_resource_id',
        'work_flow_status',
        'work_flow_completed',
        'hr_form',
        'bank_form',
        'heslb_form',
        'nhif_form',
        'requisition_id',
        'job_description_id',
        'locum_agreement_id',
        'locum_request_id',
        'on_call_request_id',
        'ccbrt_contract_id',
        'contract_renewal_id',
        'change_request_id',
    ];
    public function user()
    {
        return $this->belongsTo(User::class); // Assumes the foreign key is 'user_id'
    }
    // app/Models/Workflow.php

    public function workflowHistory()
    {
        return $this->hasMany(WorkFlowHistory::class, 'work_flow_id');
    }

    public function changeRequest()
    {
        return $this->belongsTo(ChangeRequest::class, 'change_request_id');
    }

    public function ictAccessResource()
    {
        return $this->belongsTo(IctAccessResource::class, 'ict_request_resource_id');
    }
    public function histories()
    {
        return $this->hasMany(WorkFlowHistory::class, 'work_flow_id');
    }

    public function jobDescription()
    {
        return $this->belongsTo(ChangeRequest::class, 'job_description_id');
    }
    public function locumRequest()
    {
        return $this->belongsTo(LocumRequest::class, 'locum_request_id');
    }
    public function onCallRequest()
    {
        return $this->belongsTo(OnCallRequest::class, 'on_call_request_id');
    }

    public function locumAgreement()
    {
        return $this->belongsTo(LocumAgreement::class, 'locum_agreement_id');
    }
}
