<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Requisition extends Model
{
    use HasFactory;

    protected $fillable = [
        'access_id',
        'user_id',
        'initiator_type',
        'initiator_name',
        'initiator_role',
        'hec_initial_financial_decision',
        'position_type',
        'employee_id',
        'employee_name',
        'employee_ids',
        'employee_names',
        'current_contract_end_date',
        'line_manager_id',
        'department_id',
        'dept_name',
        'responsibility_centre',
        'reporting_line',
        'job_title_id',
        'new_job_title',
        'contract_type',
        'required_start_date',
        'job_description_path',
        'condition_medical_operational',
        'condition_safety_reputational',
        'condition_legal_requirement',
        'condition_financial_loss',
        'condition_increase_income',
        'elaborate_reason',
        'budget_approved',
        'max_monthly_budget',
        'funding_available',
        'donor_code',
        'activity_code',
        'payroll_accountant_id',
        'payroll_reviewed_at',
        'payroll_comment',
        'hec_financial_decision',
        'proposed_funding_source',
        'hec_comment',
        'hec_reviewer_id',
        'hec_reviewed_at',
        'cfo_financing_confirmation',
        'cfo_financing_code',
        'cfo_comment',
        'cfo_decision',
        'cfo_id',
        'cfo_reviewed_at',
        'ceo_decision',
        'ceo_comment',
        'ceo_id',
        'ceo_reviewed_at',
        'hr_decision',
        'hr_comment',
        'hr_attachment_path',
        'hr_id',
        'hr_reviewed_at',
        'hr_filed_only',
        'status',
        'current_step',
        'rejection_reason',
        'rejected_at',
        'rejected_by',
        'rejection_stage',
        'can_edit_until',
    ];

    protected $casts = [
        'budget_approved' => 'boolean',
        'funding_available' => 'boolean',
        'condition_medical_operational' => 'boolean',
        'condition_safety_reputational' => 'boolean',
        'condition_legal_requirement' => 'boolean',
        'condition_financial_loss' => 'boolean',
        'condition_increase_income' => 'boolean',
        'hr_filed_only' => 'boolean',
        'current_contract_end_date' => 'date',
        'required_start_date' => 'date',
        'payroll_reviewed_at' => 'datetime',
        'hec_reviewed_at' => 'datetime',
        'cfo_reviewed_at' => 'datetime',
        'ceo_reviewed_at' => 'datetime',
        'hr_reviewed_at' => 'datetime',
        'rejected_at' => 'datetime',
        'can_edit_until' => 'datetime',
        'max_monthly_budget' => 'decimal:2',
        'employee_ids' => 'array',
        'employee_names' => 'array',
    ];

    /**
     * Boot method to generate access_id
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->access_id)) {
                // Get all requisitions with access_id starting with RRF-
                $allRequisitions = static::where('access_id', 'like', 'RRF-%')
                    ->pluck('access_id')
                    ->toArray();

                $maxNumber = 0;

                // Extract numbers from existing access_ids
                foreach ($allRequisitions as $accessId) {
                    // Check if it's in the new format (RRF-####)
                    if (preg_match('/^RRF-(\d+)$/', $accessId, $matches)) {
                        $number = (int) $matches[1];
                        if ($number > $maxNumber) {
                            $maxNumber = $number;
                        }
                    }
                }

                // Increment to get the next number
                $nextNumber = $maxNumber + 1;

                // Format as RRF-0001, RRF-0002, etc. (4 digits minimum)
                $model->access_id = 'RRF-' . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
            }
        });
    }

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function initiator()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function employee()
    {
        return $this->belongsTo(User::class, 'employee_id');
    }

    public function lineManager()
    {
        return $this->belongsTo(User::class, 'line_manager_id');
    }

    public function department()
    {
        return $this->belongsTo(Departments::class, 'department_id');
    }

    public function jobTitle()
    {
        return $this->belongsTo(JobTitle::class, 'job_title_id');
    }

    public function payrollAccountant()
    {
        return $this->belongsTo(User::class, 'payroll_accountant_id');
    }

    public function hecReviewer()
    {
        return $this->belongsTo(User::class, 'hec_reviewer_id');
    }

    public function cfo()
    {
        return $this->belongsTo(User::class, 'cfo_id');
    }

    public function ceo()
    {
        return $this->belongsTo(User::class, 'ceo_id');
    }

    public function hr()
    {
        return $this->belongsTo(User::class, 'hr_id');
    }

    public function rejectedBy()
    {
        return $this->belongsTo(User::class, 'rejected_by');
    }

    public function workflow()
    {
        return $this->hasOne(Workflow::class, 'requisition_id');
    }

    // Helper methods
    public function isInitiatedByHEC(): bool
    {
        return $this->initiator_type === 'hec_member';
    }

    public function isInitiatedByLineManager(): bool
    {
        return $this->initiator_type === 'line_manager';
    }

    public function hasBudget(): bool
    {
        return $this->budget_approved === true;
    }

    public function needsCFOApproval(): bool
    {
        // CFO needed when: HEC initiator + no budget + no financial decision specified
        return $this->isInitiatedByHEC()
            && !$this->hasBudget()
            && !in_array($this->hec_financial_decision, ['no_financial_implication', 'proposed_funding']);
    }

    public function needsCEOApproval(): bool
    {
        return $this->needsCFOApproval() && $this->cfo_decision === 'approved';
    }

    public function getStatusBadgeClass(): string
    {
        if ($this->status === 'pending_hec' && $this->ceo_decision === 'need_more_info') {
            return 'bg-warning text-dark';
        }

        return match ($this->status) {
            'draft' => 'bg-secondary',
            'pending_payroll' => 'bg-warning text-dark',
            'pending_hec' => 'bg-warning text-dark',
            'pending_cfo' => 'bg-warning text-dark',
            'pending_ceo' => 'bg-warning text-dark',
            'pending_hr' => 'bg-warning text-dark',
            'approved' => 'bg-success',
            'rejected' => 'bg-danger',
            'rejected_for_editing' => $this->isExpired() ? 'bg-secondary' : 'bg-warning text-dark',
            'filed' => 'bg-dark',
            default => 'bg-secondary',
        };
    }

    public function getStatusLabel(): string
    {
        return match ($this->status) {
            'draft' => 'Draft',
            'pending_payroll' => 'Pending Payroll Review',
            'pending_hec' => 'Pending HEC Review',
            'pending_cfo' => 'Pending CFO Approval',
            'pending_ceo' => 'Pending CEO Approval',
            'pending_hr' => 'Pending HR Approval',
            'approved' => 'Approved',
            'rejected' => 'Rejected',
            'rejected_for_editing' => $this->isExpired() ? 'Expired' : 'Rejected - Can Edit',
            'filed' => 'Filed (No Action)',
            default => 'Unknown',
        };
    }

    public function getPositionTypeLabel(): string
    {
        return match ($this->position_type) {
            'new_position' => 'New Position',
            'replacement' => 'Replacement',
            'contract_renewal' => 'Contract Renewal/Extension',
            default => 'Unknown',
        };
    }

    public function getContractTypeLabel(): string
    {
        return match ($this->contract_type) {
            'minimal_1_year' => 'Minimal 1 year (employment)',
            'termed_less_1_year' => 'Termed < 1 year (consultant/specific task)',
            'health_volunteer' => 'Health Volunteer (50% basic, minimal 1 year)',
            'work_exposure' => 'Work Exposure Placement (no pay, max 2×3 months)',
            default => 'Not specified',
        };
    }

    /**
     * Check if requisition can be edited (within one month of rejection)
     */
    public function canBeEdited(): bool
    {
        if ($this->status !== 'rejected_for_editing') {
            return false;
        }

        if (!$this->can_edit_until) {
            return false;
        }

        return now()->lte($this->can_edit_until);
    }

    /**
     * Check if requisition is expired (more than one month after rejection)
     */
    public function isExpired(): bool
    {
        if ($this->status !== 'rejected_for_editing') {
            return false;
        }

        if (!$this->can_edit_until) {
            return false;
        }

        return now()->gt($this->can_edit_until);
    }

    /**
     * Get days remaining until expiration
     */
    public function getDaysUntilExpiration(): ?int
    {
        if (!$this->can_edit_until || !$this->canBeEdited()) {
            return null;
        }

        return now()->diffInDays($this->can_edit_until, false);
    }
}
