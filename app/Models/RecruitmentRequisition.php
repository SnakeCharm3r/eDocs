<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class RecruitmentRequisition extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'recruitment_requisitions';

    protected $fillable = [
        'job_title',
        'background',
        'employee_name',
        'current_contract_end_date',
        'department_id',
        'responsibility_centre',
        'reports_to_position',
        'contract_type',
        'position_approved_in_budget',
        'max_monthly_budget',
        'funding_available',
        'donor_code',
        'activity_code',
        'payroll_accountant_user_id',
        'required_starting_date',
        'conditions',
        'justification_text',
        'hod_user_id',
        'hod_signed_at',
        'in_budget_flag',
        'needs_finance_review',
        'status',
        'hec_decision',
        'hec_justification_for_no_budget',
        'hec_proposed_funding',
        'hec_comments',
        'hec_reviewed_by',
        'hec_reviewed_at',
        'cfo_financing_confirmation',
        'cfo_financing_code',
        'cfo_comment',
        'cfo_reviewed_by',
        'cfo_reviewed_at',
        'ceo_decision',
        'ceo_comment',
        'ceo_reviewed_by',
        'ceo_reviewed_at',
        'start_recruitment_flag',
        'hr_notes',
        'advert_date',
        'shortlisting_date',
        'hr_processed_by',
        'hr_processed_at',
        'current_approver_id',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'conditions' => 'array',
        'position_approved_in_budget' => 'boolean',
        'funding_available' => 'boolean',
        'in_budget_flag' => 'boolean',
        'needs_finance_review' => 'boolean',
        'start_recruitment_flag' => 'boolean',
        'current_contract_end_date' => 'date',
        'required_starting_date' => 'date',
        'hod_signed_at' => 'datetime',
        'hec_reviewed_at' => 'datetime',
        'cfo_reviewed_at' => 'datetime',
        'ceo_reviewed_at' => 'datetime',
        'hr_processed_at' => 'datetime',
        'advert_date' => 'date',
        'shortlisting_date' => 'date',
        'max_monthly_budget' => 'decimal:2',
    ];

    /**
     * Get the department that this requisition belongs to
     */
    public function department(): BelongsTo
    {
        return $this->belongsTo(Departments::class, 'department_id');
    }

    /**
     * Get the HOD (Head of Department) who created this requisition
     */
    public function hod(): BelongsTo
    {
        return $this->belongsTo(User::class, 'hod_user_id');
    }

    /**
     * Get the payroll accountant assigned
     */
    public function payrollAccountant(): BelongsTo
    {
        return $this->belongsTo(User::class, 'payroll_accountant_user_id');
    }

    /**
     * Get the HEC member who reviewed
     */
    public function hecReviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'hec_reviewed_by');
    }

    /**
     * Get the CFO who reviewed
     */
    public function cfoReviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cfo_reviewed_by');
    }

    /**
     * Get the CEO who reviewed
     */
    public function ceoReviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'ceo_reviewed_by');
    }

    /**
     * Get the HR person who processed
     */
    public function hrProcessor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'hr_processed_by');
    }

    /**
     * Get the current approver
     */
    public function currentApprover(): BelongsTo
    {
        return $this->belongsTo(User::class, 'current_approver_id');
    }

    /**
     * Get the user who created this requisition
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who last updated this requisition
     */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Get all attachments for this requisition
     */
    public function attachments(): HasMany
    {
        return $this->hasMany(RecruitmentAttachment::class, 'requisition_id');
    }

    /**
     * Get all workflow history records
     */
    public function workflowHistories(): HasMany
    {
        return $this->hasMany(RecruitmentWorkflowHistory::class, 'requisition_id')->orderBy('created_at', 'asc');
    }

    /**
     * Get the latest workflow history
     */
    public function latestWorkflowHistory(): HasOne
    {
        return $this->hasOne(RecruitmentWorkflowHistory::class, 'requisition_id')->latestOfMany();
    }

    /**
     * Scope: Get requisitions pending HEC review
     */
    public function scopePendingHecReview($query)
    {
        return $query->where('status', 'hec_review_in_progress');
    }

    /**
     * Scope: Get requisitions pending CFO finance review
     */
    public function scopePendingCfoReview($query)
    {
        return $query->where('status', 'cfo_finance_review_in_progress')
                     ->where('needs_finance_review', true);
    }

    /**
     * Scope: Get requisitions pending CEO decision
     */
    public function scopePendingCeoDecision($query)
    {
        return $query->where('status', 'ceo_review_in_progress');
    }

    /**
     * Scope: Get requisitions ready for HR processing
     */
    public function scopeReadyForHr($query)
    {
        return $query->whereIn('status', ['hec_no_objection_in_budget', 'ceo_approved']);
    }

    /**
     * Check if requisition is in budget
     */
    public function isInBudget(): bool
    {
        return $this->position_approved_in_budget && $this->funding_available;
    }

    /**
     * Get the responsible HEC member for this requisition's department
     */
    public function getResponsibleHecMember(): ?User
    {
        if (!$this->department || !$this->department->hecMember) {
            return null;
        }
        return $this->department->hecMember;
    }

    /**
     * Get status badge class for UI
     */
    public function getStatusBadgeClass(): string
    {
        return match($this->status) {
            'draft' => 'secondary',
            'submitted_by_hod', 'hec_review_in_progress', 'cfo_finance_review_in_progress', 'ceo_review_in_progress' => 'warning',
            'hec_no_objection_in_budget', 'hec_no_objection_no_budget', 'cfo_finance_confirmed', 'ceo_approved', 'ready_for_hr_processing' => 'success',
            'hec_objection_in_budget', 'hec_objection_no_budget', 'cfo_finance_rejected', 'ceo_declined', 'closed_filed' => 'danger',
            'ceo_needs_more_info' => 'info',
            'in_recruitment_pipeline' => 'primary',
            default => 'secondary',
        };
    }

    /**
     * Get human-readable status
     */
    public function getStatusLabel(): string
    {
        return match($this->status) {
            'draft' => 'Draft',
            'submitted_by_hod' => 'Submitted by HOD',
            'hec_review_in_progress' => 'HEC Review in Progress',
            'hec_no_objection_in_budget' => 'HEC No Objection (In Budget)',
            'hec_objection_in_budget' => 'HEC Objection (In Budget)',
            'hec_objection_no_budget' => 'HEC Objection (No Budget)',
            'hec_no_objection_no_budget' => 'HEC No Objection (No Budget)',
            'cfo_finance_review_in_progress' => 'CFO Finance Review',
            'cfo_finance_confirmed' => 'CFO Finance Confirmed',
            'cfo_finance_rejected' => 'CFO Finance Rejected',
            'ceo_review_in_progress' => 'CEO Review',
            'ceo_approved' => 'CEO Approved',
            'ceo_declined' => 'CEO Declined',
            'ceo_needs_more_info' => 'CEO Needs More Info',
            'closed_filed' => 'Closed/Filed',
            'ready_for_hr_processing' => 'Ready for HR Processing',
            'in_recruitment_pipeline' => 'In Recruitment Pipeline',
            default => ucfirst(str_replace('_', ' ', $this->status)),
        };
    }
}







