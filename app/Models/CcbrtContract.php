<?php

namespace App\Models;

use App\Models\Division;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CcbrtContract extends Model
{
    use HasFactory;

    protected $table = 'ccbrt_contracts';

    // Mass assignable fields
    protected $fillable = [
        'contract_number',
        'title',
        'description',
        'contract_type',
        'file_path',
        'uploaded_contract_path',
        'signed_contract_path',
        'terms_conditions_path',
        'sla_document_path',
        'terms_of_reference_path',
        'cost',
        'currency',
        'division_id',
        'department_id',
        'vendor_id',
        'creation_date',
        'start_date',
        'duration_months',
        'end_date',
        'status',
        'lifecycle_stage',
        'likelihood_rating',
        'impact_if_not_requested',
        'overall_risk',
        'renewal_status',
        'contract_manager_id',
        'contact_persons',
        'approvers',
        'amendment_history',
        'alert_30_days',
        'alert_60_days',
        'alert_90_days',
        'kpi_metrics',
        'deliverables',
        'issue_log',
        'evaluation_score',
        'current_approver_id',
        'approval_stage',
        'created_by',
        'last_notification_sent_at',
        'notification_escalation_level',
        'expired_notification_sent_at',
        'line_manager_rating',
        'hec_rating',
        'contract_action',
        'parent_contract_id',
        'renewal_term_number',
    ];

    protected $casts = [
        'contact_persons' => 'array',
        'approvers' => 'array',
        'amendment_history' => 'array',
        'kpi_metrics' => 'array',
        'deliverables' => 'array',
        'issue_log' => 'array',
        'alert_30_days' => 'boolean',
        'alert_60_days' => 'boolean',
        'alert_90_days' => 'boolean',
        'creation_date' => 'date',
        'start_date' => 'date',
        'end_date' => 'date',
        'last_notification_sent_at' => 'datetime',
        'expired_notification_sent_at' => 'datetime',
    ];

    /**
     * Contract belongs to a Division
     */
    public function division()
    {
        return $this->belongsTo(Division::class);
    }

    /**
     * Contract belongs to a Department
     */
    public function department()
    {
        return $this->belongsTo(Departments::class);
    }

    /**
     * Contract belongs to a Vendor
     */
    public function vendor()
    {
        return $this->belongsTo(CcbrtVendor::class, 'vendor_id');
    }

    /**
     * Contract was created by a User
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function workflows()
    {
    return $this->hasMany(Workflow::class, 'ccbrt_contract_id');
    }

    /**
     * Get the latest workflow for this contract
     */
    public function workflow()
    {
        return $this->hasOne(Workflow::class, 'ccbrt_contract_id')->latestOfMany();
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Contract Manager (internal staff)
     */
    public function contractManager()
    {
        return $this->belongsTo(User::class, 'contract_manager_id');
    }

    /**
     * Current approver in workflow
     */
    public function currentApprover()
    {
        return $this->belongsTo(User::class, 'current_approver_id');
    }

    /**
     * Parent contract (original contract if this is a renewal)
     */
    public function parentContract()
    {
        return $this->belongsTo(CcbrtContract::class, 'parent_contract_id');
    }

    /**
     * Child contracts (renewals of this contract)
     */
    public function renewals()
    {
        return $this->hasMany(CcbrtContract::class, 'parent_contract_id')->orderBy('renewal_term_number', 'asc');
    }

    /**
     * Get all contracts in the renewal chain (original + all renewals)
     */
    public function contractChain()
    {
        $chain = collect([$this]);
        
        // Get the original contract
        $original = $this;
        while ($original->parentContract) {
            $original = $original->parentContract;
            $chain->prepend($original);
        }
        
        // Get all renewals
        $renewals = $this->renewals;
        $chain = $chain->merge($renewals);
        
        return $chain->sortBy('renewal_term_number');
    }
}
