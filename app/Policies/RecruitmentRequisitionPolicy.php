<?php

namespace App\Policies;

use App\Models\RecruitmentRequisition;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class RecruitmentRequisitionPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['hod', 'hec-cfo', 'hec-coo', 'hec-cms', 'hec-ccd', 'cfo', 'ceo', 'hr']);
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, RecruitmentRequisition $requisition): bool
    {
        // HOD can view their own requisitions
        if ($user->hasRole('hod') && $requisition->hod_user_id === $user->id) {
            return true;
        }

        // HEC members can view requisitions for their departments
        if ($user->hasAnyRole(['hec-cfo', 'hec-coo', 'hec-cms', 'hec-ccd'])) {
            $departmentIds = \App\Models\Departments::where('hec_member_id', $user->id)->pluck('id');
            return $departmentIds->contains($requisition->department_id);
        }

        // CFO can view requisitions needing finance review
        if ($user->hasRole('cfo') && $requisition->needs_finance_review) {
            return true;
        }

        // CEO can view requisitions pending their decision
        if ($user->hasRole('ceo') && in_array($requisition->status, ['cfo_finance_confirmed', 'hec_no_objection_in_budget', 'ceo_review_in_progress'])) {
            return true;
        }

        // HR can view all requisitions
        if ($user->hasRole('hr')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->hasRole('hod');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, RecruitmentRequisition $requisition): bool
    {
        // HOD can only update their own draft requisitions
        return $user->hasRole('hod') 
            && $requisition->hod_user_id === $user->id 
            && $requisition->status === 'draft';
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, RecruitmentRequisition $requisition): bool
    {
        // Only HOD can delete their own draft requisitions
        return $user->hasRole('hod') 
            && $requisition->hod_user_id === $user->id 
            && $requisition->status === 'draft';
    }

    /**
     * Determine whether the user can submit the requisition.
     */
    public function submit(User $user, RecruitmentRequisition $requisition): bool
    {
        return $user->hasRole('hod') 
            && $requisition->hod_user_id === $user->id 
            && $requisition->status === 'draft';
    }

    /**
     * Determine whether the user can perform HEC review.
     */
    public function hecReview(User $user, RecruitmentRequisition $requisition): bool
    {
        if (!$user->hasAnyRole(['hec-cfo', 'hec-coo', 'hec-cms', 'hec-ccd'])) {
            return false;
        }

        $departmentIds = \App\Models\Departments::where('hec_member_id', $user->id)->pluck('id');
        return $departmentIds->contains($requisition->department_id)
            && in_array($requisition->status, ['submitted_by_hod', 'hec_review_in_progress']);
    }

    /**
     * Determine whether the user can perform CFO review.
     */
    public function cfoReview(User $user, RecruitmentRequisition $requisition): bool
    {
        return $user->hasRole('cfo') 
            && $requisition->status === 'hec_no_objection_no_budget' 
            && $requisition->needs_finance_review;
    }

    /**
     * Determine whether the user can make CEO decision.
     */
    public function ceoDecision(User $user, RecruitmentRequisition $requisition): bool
    {
        return $user->hasRole('ceo') 
            && in_array($requisition->status, ['cfo_finance_confirmed', 'hec_no_objection_in_budget', 'ceo_review_in_progress']);
    }

    /**
     * Determine whether the user can process as HR.
     */
    public function hrProcess(User $user, RecruitmentRequisition $requisition): bool
    {
        return $user->hasRole('hr') 
            && in_array($requisition->status, ['hec_no_objection_in_budget', 'ceo_approved', 'ready_for_hr_processing']);
    }
}
