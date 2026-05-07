<?php

namespace App\Http\Controllers;

use App\Models\RecruitmentRequisition;
use App\Models\RecruitmentAttachment;
use App\Models\RecruitmentWorkflowHistory;
use App\Models\Departments;
use App\Models\User;
use App\Models\HecProfile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use App\Notifications\RecruitmentRequisitionSubmitted;
use App\Notifications\RecruitmentRequisitionApproved;
use App\Notifications\RecruitmentRequisitionRejected;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;

class RecruitmentRequisitionController extends Controller
{
    /**
     * Display a listing of requisitions based on user role
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $requisitions = collect();

        if ($user->hasRole('hod')) {
            // HOD sees only their own requisitions
            $requisitions = RecruitmentRequisition::with(['department', 'currentApprover'])
                ->where('hod_user_id', $user->id)
                ->orderBy('created_at', 'desc')
                ->get();
        } elseif ($user->hasAnyRole(['hec-cfo', 'hec-coo', 'hec-cms', 'hec-ccd'])) {
            // HEC members see requisitions for their departments
            $departmentIds = Departments::where('hec_member_id', $user->id)->pluck('id');
            $requisitions = RecruitmentRequisition::with(['department', 'hod', 'currentApprover'])
                ->whereIn('department_id', $departmentIds)
                ->whereIn('status', ['submitted_by_hod', 'hec_review_in_progress'])
                ->orderBy('created_at', 'desc')
                ->get();
        } elseif ($user->hasRole('cfo')) {
            // CFO sees requisitions needing finance review
            $requisitions = RecruitmentRequisition::with(['department', 'hod', 'hecReviewer'])
                ->pendingCfoReview()
                ->orderBy('created_at', 'desc')
                ->get();
        } elseif ($user->hasRole('ceo')) {
            // CEO sees requisitions pending their decision
            $requisitions = RecruitmentRequisition::with(['department', 'hod', 'hecReviewer', 'cfoReviewer'])
                ->pendingCeoDecision()
                ->orderBy('created_at', 'desc')
                ->get();
        } elseif ($user->hasRole('hr')) {
            // HR sees all requisitions
            $requisitions = RecruitmentRequisition::with(['department', 'hod', 'currentApprover'])
                ->orderBy('created_at', 'desc')
                ->get();
        }

        return view('recruitment_requisitions.index', compact('requisitions'));
    }

    /**
     * Show the form for creating a new requisition (HOD only)
     */
    public function create()
    {
        $user = Auth::user();
        
        if (!$user->hasRole('hod')) {
            abort(403, 'Only Heads of Department can create requisitions.');
        }

        $departments = Departments::where('id', $user->deptId)->get();
        $payrollAccountants = User::role('payroll_accountant')->get();

        return view('recruitment_requisitions.create', compact('departments', 'payrollAccountants'));
    }

    /**
     * Store a newly created requisition
     */
    public function store(Request $request)
    {
        $user = Auth::user();
        
        if (!$user->hasRole('hod')) {
            abort(403, 'Only Heads of Department can create requisitions.');
        }

        $validated = $request->validate([
            // Section 1
            'job_title' => 'required|string|max:255',
            'background' => 'required|in:new_position,replacement,contract_renewal_extension',
            'employee_name' => 'nullable|required_if:background,contract_renewal_extension|string|max:255',
            'current_contract_end_date' => 'nullable|required_if:background,contract_renewal_extension|date',
            'department_id' => 'required|exists:departments,id',
            'responsibility_centre' => 'required|string|max:255',
            'reports_to_position' => 'required|string|max:255',
            'contract_type' => 'required|in:minimal_1_year_employment,termed_less_than_1_year_consultant_task,health_volunteer_50_basic_min_1_year,work_exposure_no_pay_max_2x3_months',
            'position_approved_in_budget' => 'boolean',
            'max_monthly_budget' => 'nullable|numeric|min:0',
            'funding_available' => 'boolean',
            'donor_code' => 'nullable|string|max:50',
            'activity_code' => 'nullable|string|max:50',
            'payroll_accountant_user_id' => 'nullable|exists:users,id',
            'required_starting_date' => 'required|date',
            'attachments.*' => 'nullable|file|mimes:pdf,doc,docx|max:5120', // 5MB max
            
            // Section 2
            'conditions' => 'required|array|min:1',
            'conditions.*' => 'in:1,2,3,4,5',
            'justification_text' => 'required|string|min:50',
        ]);

        DB::beginTransaction();
        try {
            // Calculate in_budget_flag
            $inBudgetFlag = ($validated['position_approved_in_budget'] ?? false) && ($validated['funding_available'] ?? false);

            // Create requisition
            $requisition = RecruitmentRequisition::create([
                'job_title' => $validated['job_title'],
                'background' => $validated['background'],
                'employee_name' => $validated['employee_name'] ?? null,
                'current_contract_end_date' => $validated['current_contract_end_date'] ?? null,
                'department_id' => $validated['department_id'],
                'responsibility_centre' => $validated['responsibility_centre'],
                'reports_to_position' => $validated['reports_to_position'],
                'contract_type' => $validated['contract_type'],
                'position_approved_in_budget' => $validated['position_approved_in_budget'] ?? false,
                'max_monthly_budget' => $validated['max_monthly_budget'] ?? null,
                'funding_available' => $validated['funding_available'] ?? false,
                'donor_code' => $validated['donor_code'] ?? null,
                'activity_code' => $validated['activity_code'] ?? null,
                'payroll_accountant_user_id' => $validated['payroll_accountant_user_id'] ?? null,
                'required_starting_date' => $validated['required_starting_date'],
                'conditions' => $validated['conditions'],
                'justification_text' => $validated['justification_text'],
                'hod_user_id' => $user->id,
                'hod_signed_at' => now(),
                'in_budget_flag' => $inBudgetFlag,
                'needs_finance_review' => false,
                'status' => 'draft',
                'created_by' => $user->id,
                'updated_by' => $user->id,
            ]);

            // Handle file uploads
            if ($request->hasFile('attachments')) {
                foreach ($request->file('attachments') as $file) {
                    $path = $file->store('recruitment_requisitions', 'public');
                    RecruitmentAttachment::create([
                        'requisition_id' => $requisition->id,
                        'file_path' => $path,
                        'file_type' => 'job_description',
                        'original_name' => $file->getClientOriginalName(),
                        'mime_type' => $file->getMimeType(),
                        'file_size' => $file->getSize(),
                        'uploaded_by' => $user->id,
                    ]);
                }
            }

            DB::commit();

            return redirect()->route('recruitment-requisitions.show', $requisition->id)
                ->with('success', 'Requisition created successfully. You can now submit it for review.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error creating recruitment requisition: ' . $e->getMessage());
            return redirect()->back()
                ->withInput()
                ->with('error', 'Error creating requisition: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified requisition
     */
    public function show($id)
    {
        $requisition = RecruitmentRequisition::with([
            'department.hecMember',
            'hod',
            'payrollAccountant',
            'hecReviewer',
            'cfoReviewer',
            'ceoReviewer',
            'hrProcessor',
            'attachments',
            'workflowHistories.attendedBy'
        ])->findOrFail($id);

        $user = Auth::user();

        // Check access permissions
        $canView = false;
        if ($user->hasRole('hod') && $requisition->hod_user_id == $user->id) {
            $canView = true;
        } elseif ($user->hasAnyRole(['hec-cfo', 'hec-coo', 'hec-cms', 'hec-ccd'])) {
            $departmentIds = Departments::where('hec_member_id', $user->id)->pluck('id');
            $canView = $departmentIds->contains($requisition->department_id);
        } elseif ($user->hasRole('cfo') && $requisition->needs_finance_review) {
            $canView = true;
        } elseif ($user->hasRole('ceo') && in_array($requisition->status, ['cfo_finance_confirmed', 'hec_no_objection_in_budget', 'ceo_review_in_progress'])) {
            $canView = true;
        } elseif ($user->hasRole('hr')) {
            $canView = true;
        }

        if (!$canView) {
            abort(403, 'You do not have permission to view this requisition.');
        }

        return view('recruitment_requisitions.show', compact('requisition'));
    }

    /**
     * Show the form for editing the specified requisition (HOD only, draft status)
     */
    public function edit($id)
    {
        $requisition = RecruitmentRequisition::with(['attachments'])->findOrFail($id);
        $user = Auth::user();

        if (!$user->hasRole('hod') || $requisition->hod_user_id != $user->id) {
            abort(403, 'You can only edit your own requisitions.');
        }

        if ($requisition->status !== 'draft') {
            return redirect()->route('recruitment-requisitions.show', $requisition->id)
                ->with('error', 'You can only edit requisitions in draft status.');
        }

        $departments = Departments::where('id', $user->deptId)->get();
        $payrollAccountants = User::role('payroll_accountant')->get();

        return view('recruitment_requisitions.edit', compact('requisition', 'departments', 'payrollAccountants'));
    }

    /**
     * Update the specified requisition
     */
    public function update(Request $request, $id)
    {
        $requisition = RecruitmentRequisition::findOrFail($id);
        $user = Auth::user();

        if (!$user->hasRole('hod') || $requisition->hod_user_id != $user->id) {
            abort(403, 'You can only update your own requisitions.');
        }

        if ($requisition->status !== 'draft') {
            return redirect()->route('recruitment-requisitions.show', $requisition->id)
                ->with('error', 'You can only update requisitions in draft status.');
        }

        $validated = $request->validate([
            'job_title' => 'required|string|max:255',
            'background' => 'required|in:new_position,replacement,contract_renewal_extension',
            'employee_name' => 'nullable|required_if:background,contract_renewal_extension|string|max:255',
            'current_contract_end_date' => 'nullable|required_if:background,contract_renewal_extension|date',
            'department_id' => 'required|exists:departments,id',
            'responsibility_centre' => 'required|string|max:255',
            'reports_to_position' => 'required|string|max:255',
            'contract_type' => 'required|in:minimal_1_year_employment,termed_less_than_1_year_consultant_task,health_volunteer_50_basic_min_1_year,work_exposure_no_pay_max_2x3_months',
            'position_approved_in_budget' => 'boolean',
            'max_monthly_budget' => 'nullable|numeric|min:0',
            'funding_available' => 'boolean',
            'donor_code' => 'nullable|string|max:50',
            'activity_code' => 'nullable|string|max:50',
            'payroll_accountant_user_id' => 'nullable|exists:users,id',
            'required_starting_date' => 'required|date',
            'attachments.*' => 'nullable|file|mimes:pdf,doc,docx|max:5120',
            'conditions' => 'required|array|min:1',
            'conditions.*' => 'in:1,2,3,4,5',
            'justification_text' => 'required|string|min:50',
            'delete_attachments' => 'nullable|array',
            'delete_attachments.*' => 'exists:recruitment_attachments,id',
        ]);

        DB::beginTransaction();
        try {
            $inBudgetFlag = ($validated['position_approved_in_budget'] ?? false) && ($validated['funding_available'] ?? false);

            $requisition->update([
                'job_title' => $validated['job_title'],
                'background' => $validated['background'],
                'employee_name' => $validated['employee_name'] ?? null,
                'current_contract_end_date' => $validated['current_contract_end_date'] ?? null,
                'department_id' => $validated['department_id'],
                'responsibility_centre' => $validated['responsibility_centre'],
                'reports_to_position' => $validated['reports_to_position'],
                'contract_type' => $validated['contract_type'],
                'position_approved_in_budget' => $validated['position_approved_in_budget'] ?? false,
                'max_monthly_budget' => $validated['max_monthly_budget'] ?? null,
                'funding_available' => $validated['funding_available'] ?? false,
                'donor_code' => $validated['donor_code'] ?? null,
                'activity_code' => $validated['activity_code'] ?? null,
                'payroll_accountant_user_id' => $validated['payroll_accountant_user_id'] ?? null,
                'required_starting_date' => $validated['required_starting_date'],
                'conditions' => $validated['conditions'],
                'justification_text' => $validated['justification_text'],
                'in_budget_flag' => $inBudgetFlag,
                'updated_by' => $user->id,
            ]);

            // Handle file uploads
            if ($request->hasFile('attachments')) {
                foreach ($request->file('attachments') as $file) {
                    $path = $file->store('recruitment_requisitions', 'public');
                    RecruitmentAttachment::create([
                        'requisition_id' => $requisition->id,
                        'file_path' => $path,
                        'file_type' => 'job_description',
                        'original_name' => $file->getClientOriginalName(),
                        'mime_type' => $file->getMimeType(),
                        'file_size' => $file->getSize(),
                        'uploaded_by' => $user->id,
                    ]);
                }
            }

            // Delete requested attachments
            if ($request->has('delete_attachments')) {
                foreach ($request->delete_attachments as $attachmentId) {
                    $attachment = RecruitmentAttachment::find($attachmentId);
                    if ($attachment && $attachment->requisition_id == $requisition->id) {
                        Storage::disk('public')->delete($attachment->file_path);
                        $attachment->delete();
                    }
                }
            }

            DB::commit();

            return redirect()->route('recruitment-requisitions.show', $requisition->id)
                ->with('success', 'Requisition updated successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error updating recruitment requisition: ' . $e->getMessage());
            return redirect()->back()
                ->withInput()
                ->with('error', 'Error updating requisition: ' . $e->getMessage());
        }
    }

    /**
     * Submit requisition for review (HOD action)
     */
    public function submit($id)
    {
        $requisition = RecruitmentRequisition::with('department.hecMember')->findOrFail($id);
        $user = Auth::user();

        if (!$user->hasRole('hod') || $requisition->hod_user_id != $user->id) {
            abort(403, 'You can only submit your own requisitions.');
        }

        if ($requisition->status !== 'draft') {
            return redirect()->route('recruitment-requisitions.show', $requisition->id)
                ->with('error', 'This requisition has already been submitted.');
        }

        DB::beginTransaction();
        try {
            // Get the responsible HEC member
            $hecMember = $requisition->getResponsibleHecMember();
            
            if (!$hecMember) {
                return redirect()->back()
                    ->with('error', 'No HEC member assigned to this department. Please contact administration.');
            }

            // Update status and assign to HEC member
            $requisition->update([
                'status' => 'submitted_by_hod',
                'current_approver_id' => $hecMember->id,
                'updated_by' => $user->id,
            ]);

            // Create workflow history
            RecruitmentWorkflowHistory::create([
                'requisition_id' => $requisition->id,
                'step_name' => 'HOD',
                'from_status' => 'draft',
                'to_status' => 'submitted_by_hod',
                'action' => 'submitted',
                'attended_by' => $user->id,
                'comments' => 'Requisition submitted by HOD for HEC review',
            ]);

            // Notify HEC member
            $hecMember->notify(new RecruitmentRequisitionSubmitted($requisition, $user));

            DB::commit();

            return redirect()->route('recruitment-requisitions.show', $requisition->id)
                ->with('success', 'Requisition submitted successfully. HEC member has been notified.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error submitting recruitment requisition: ' . $e->getMessage());
            return redirect()->back()
                ->with('error', 'Error submitting requisition: ' . $e->getMessage());
        }
    }

    /**
     * HEC Review - Handle HEC member's decision (3a, 3b, or 3c)
     */
    public function hecReview(Request $request, $id)
    {
        $requisition = RecruitmentRequisition::with('department')->findOrFail($id);
        $user = Auth::user();

        // Verify user is the assigned HEC member
        if (!$user->hasAnyRole(['hec-cfo', 'hec-coo', 'hec-cms', 'hec-ccd'])) {
            abort(403, 'Only HEC members can review requisitions.');
        }

        $departmentIds = Departments::where('hec_member_id', $user->id)->pluck('id');
        if (!$departmentIds->contains($requisition->department_id)) {
            abort(403, 'You are not the assigned HEC member for this department.');
        }

        if (!in_array($requisition->status, ['submitted_by_hod', 'hec_review_in_progress'])) {
            return redirect()->back()
                ->with('error', 'This requisition is not pending HEC review.');
        }

        $validated = $request->validate([
            'hec_decision' => 'required|in:no_objection,objection',
            'hec_comments' => 'nullable|string|max:2000',
            'hec_justification_for_no_budget' => 'nullable|required_if:hec_decision,no_objection|string|max:2000',
            'hec_proposed_funding' => 'nullable|required_if:hec_decision,no_objection|string|max:255',
        ]);

        DB::beginTransaction();
        try {
            $oldStatus = $requisition->status;
            $nextApprover = null;
            $newStatus = null;
            $action = null;
            $comments = 'HEC Review: ';

            if ($validated['hec_decision'] === 'no_objection') {
                // Case 3a or 3c: No objection
                if ($requisition->isInBudget()) {
                    // Case 3a: In budget & funds confirmed
                    $newStatus = 'hec_no_objection_in_budget';
                    $action = 'no_objection';
                    $comments .= 'No objection to start recruitment/renewal (Position in budget and funds confirmed).';
                    
                    // Next: HR for processing
                    $hrUsers = User::role('hr')->get();
                    $nextApprover = $hrUsers->first();
                } else {
                    // Case 3c: No objection but no budget
                    $newStatus = 'hec_no_objection_no_budget';
                    $action = 'no_objection';
                    $comments .= 'No objection to start recruitment/renewal (No budget - requires CFO finance review).';
                    
                    // Next: CFO for finance review
                    $cfo = User::role('cfo')->first();
                    $nextApprover = $cfo;
                }
            } else {
                // Case 3b: Objection
                if ($requisition->isInBudget()) {
                    $newStatus = 'hec_objection_in_budget';
                } else {
                    $newStatus = 'hec_objection_no_budget';
                }
                $action = 'objection';
                $comments .= 'Objection to start recruitment/renewal.';
                
                // Next: HR for filing
                $hrUsers = User::role('hr')->get();
                $nextApprover = $hrUsers->first();
            }

            // Update requisition
            $updateData = [
                'status' => $newStatus,
                'hec_decision' => $validated['hec_decision'],
                'hec_comments' => $validated['hec_comments'] ?? null,
                'hec_reviewed_by' => $user->id,
                'hec_reviewed_at' => now(),
                'current_approver_id' => $nextApprover?->id,
                'updated_by' => $user->id,
            ];

            if ($newStatus === 'hec_no_objection_no_budget') {
                $updateData['needs_finance_review'] = true;
                $updateData['hec_justification_for_no_budget'] = $validated['hec_justification_for_no_budget'] ?? null;
                $updateData['hec_proposed_funding'] = $validated['hec_proposed_funding'] ?? null;
            }

            $requisition->update($updateData);

            // Create workflow history
            RecruitmentWorkflowHistory::create([
                'requisition_id' => $requisition->id,
                'step_name' => 'HEC',
                'from_status' => $oldStatus,
                'to_status' => $newStatus,
                'action' => $action,
                'attended_by' => $user->id,
                'comments' => $comments . ($validated['hec_comments'] ? ' Comments: ' . $validated['hec_comments'] : ''),
                'metadata' => [
                    'in_budget' => $requisition->isInBudget(),
                    'justification' => $validated['hec_justification_for_no_budget'] ?? null,
                    'proposed_funding' => $validated['hec_proposed_funding'] ?? null,
                ],
            ]);

            // Notify next approver
            if ($nextApprover) {
                $nextApprover->notify(new RecruitmentRequisitionSubmitted($requisition, $user));
            }

            // Notify HOD
            $requisition->hod->notify(new RecruitmentRequisitionApproved($requisition, $user, $newStatus));

            DB::commit();

            $message = $action === 'objection' 
                ? 'HEC objection recorded. Requisition forwarded to HR for filing.'
                : 'HEC review completed. Requisition forwarded to ' . ($nextApprover ? $nextApprover->name : 'next step') . '.';

            return redirect()->route('recruitment-requisitions.show', $requisition->id)
                ->with('success', $message);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error in HEC review: ' . $e->getMessage());
            return redirect()->back()
                ->withInput()
                ->with('error', 'Error processing HEC review: ' . $e->getMessage());
        }
    }

    /**
     * CFO Finance Review
     */
    public function cfoReview(Request $request, $id)
    {
        $requisition = RecruitmentRequisition::findOrFail($id);
        $user = Auth::user();

        if (!$user->hasRole('cfo')) {
            abort(403, 'Only CFO can perform finance review.');
        }

        if ($requisition->status !== 'hec_no_objection_no_budget' || !$requisition->needs_finance_review) {
            return redirect()->back()
                ->with('error', 'This requisition does not require CFO finance review.');
        }

        $validated = $request->validate([
            'cfo_financing_confirmation' => 'required|string|max:2000',
            'cfo_financing_code' => 'nullable|string|max:100',
            'cfo_comment' => 'nullable|string|max:2000',
            'action' => 'required|in:approve,reject',
        ]);

        DB::beginTransaction();
        try {
            $oldStatus = $requisition->status;
            $newStatus = null;
            $nextApprover = null;

            if ($validated['action'] === 'approve') {
                $newStatus = 'cfo_finance_confirmed';
                // Next: CEO
                $ceo = User::role('ceo')->first();
                $nextApprover = $ceo;
            } else {
                $newStatus = 'cfo_finance_rejected';
                // Next: HR for filing
                $hrUsers = User::role('hr')->get();
                $nextApprover = $hrUsers->first();
            }

            $requisition->update([
                'status' => $newStatus,
                'cfo_financing_confirmation' => $validated['cfo_financing_confirmation'],
                'cfo_financing_code' => $validated['cfo_financing_code'] ?? null,
                'cfo_comment' => $validated['cfo_comment'] ?? null,
                'cfo_reviewed_by' => $user->id,
                'cfo_reviewed_at' => now(),
                'current_approver_id' => $nextApprover?->id,
                'updated_by' => $user->id,
            ]);

            // Create workflow history
            RecruitmentWorkflowHistory::create([
                'requisition_id' => $requisition->id,
                'step_name' => 'CFO_FINANCE',
                'from_status' => $oldStatus,
                'to_status' => $newStatus,
                'action' => $validated['action'] === 'approve' ? 'confirmed' : 'rejected',
                'attended_by' => $user->id,
                'comments' => $validated['cfo_financing_confirmation'] . ($validated['cfo_comment'] ? ' Additional comments: ' . $validated['cfo_comment'] : ''),
                'metadata' => [
                    'financing_code' => $validated['cfo_financing_code'] ?? null,
                ],
            ]);

            // Notify next approver
            if ($nextApprover) {
                $nextApprover->notify(new RecruitmentRequisitionSubmitted($requisition, $user));
            }

            // Notify HOD
            $requisition->hod->notify(new RecruitmentRequisitionApproved($requisition, $user, $newStatus));

            DB::commit();

            $message = $validated['action'] === 'approve'
                ? 'CFO finance review completed. Requisition forwarded to CEO.'
                : 'CFO finance review rejected. Requisition forwarded to HR for filing.';

            return redirect()->route('recruitment-requisitions.show', $requisition->id)
                ->with('success', $message);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error in CFO review: ' . $e->getMessage());
            return redirect()->back()
                ->withInput()
                ->with('error', 'Error processing CFO review: ' . $e->getMessage());
        }
    }

    /**
     * CEO Decision
     */
    public function ceoDecision(Request $request, $id)
    {
        $requisition = RecruitmentRequisition::findOrFail($id);
        $user = Auth::user();

        if (!$user->hasRole('ceo')) {
            abort(403, 'Only CEO can make final decisions.');
        }

        $validStatuses = ['cfo_finance_confirmed', 'hec_no_objection_in_budget', 'ceo_review_in_progress'];
        if (!in_array($requisition->status, $validStatuses)) {
            return redirect()->back()
                ->with('error', 'This requisition is not pending CEO decision.');
        }

        $validated = $request->validate([
            'ceo_decision' => 'required|in:approved,declined,needs_further_information',
            'ceo_comment' => 'nullable|string|max:2000',
        ]);

        DB::beginTransaction();
        try {
            $oldStatus = $requisition->status;
            $newStatus = null;
            $nextApprover = null;
            $action = null;

            switch ($validated['ceo_decision']) {
                case 'approved':
                    $newStatus = 'ceo_approved';
                    $action = 'approved';
                    // Next: HR for processing
                    $hrUsers = User::role('hr')->get();
                    $nextApprover = $hrUsers->first();
                    break;
                case 'declined':
                    $newStatus = 'ceo_declined';
                    $action = 'declined';
                    // Next: HR for filing
                    $hrUsers = User::role('hr')->get();
                    $nextApprover = $hrUsers->first();
                    break;
                case 'needs_further_information':
                    $newStatus = 'ceo_needs_more_info';
                    $action = 'needs_more_info';
                    // Can route back to HOD or HEC - for now, route to HOD
                    $nextApprover = $requisition->hod;
                    break;
            }

            $requisition->update([
                'status' => $newStatus,
                'ceo_decision' => $validated['ceo_decision'],
                'ceo_comment' => $validated['ceo_comment'] ?? null,
                'ceo_reviewed_by' => $user->id,
                'ceo_reviewed_at' => now(),
                'current_approver_id' => $nextApprover?->id,
                'updated_by' => $user->id,
            ]);

            // Create workflow history
            RecruitmentWorkflowHistory::create([
                'requisition_id' => $requisition->id,
                'step_name' => 'CEO',
                'from_status' => $oldStatus,
                'to_status' => $newStatus,
                'action' => $action,
                'attended_by' => $user->id,
                'comments' => 'CEO Decision: ' . ucfirst($validated['ceo_decision']) . ($validated['ceo_comment'] ? '. Comments: ' . $validated['ceo_comment'] : ''),
            ]);

            // Notify next approver
            if ($nextApprover) {
                $nextApprover->notify(new RecruitmentRequisitionSubmitted($requisition, $user));
            }

            // Notify HOD
            $requisition->hod->notify(new RecruitmentRequisitionApproved($requisition, $user, $newStatus));

            DB::commit();

            $message = match($validated['ceo_decision']) {
                'approved' => 'CEO approved. Requisition forwarded to HR for recruitment processing.',
                'declined' => 'CEO declined. Requisition forwarded to HR for filing.',
                'needs_further_information' => 'CEO requested more information. Requisition returned to HOD.',
            };

            return redirect()->route('recruitment-requisitions.show', $requisition->id)
                ->with('success', $message);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error in CEO decision: ' . $e->getMessage());
            return redirect()->back()
                ->withInput()
                ->with('error', 'Error processing CEO decision: ' . $e->getMessage());
        }
    }

    /**
     * HR Processing - Mark as ready for recruitment
     */
    public function hrProcess(Request $request, $id)
    {
        $requisition = RecruitmentRequisition::findOrFail($id);
        $user = Auth::user();

        if (!$user->hasRole('hr')) {
            abort(403, 'Only HR can process requisitions.');
        }

        $validStatuses = ['hec_no_objection_in_budget', 'ceo_approved', 'ready_for_hr_processing'];
        if (!in_array($requisition->status, $validStatuses)) {
            return redirect()->back()
                ->with('error', 'This requisition is not ready for HR processing.');
        }

        $validated = $request->validate([
            'hr_notes' => 'nullable|string|max:2000',
            'advert_date' => 'nullable|date',
            'shortlisting_date' => 'nullable|date',
            'start_recruitment' => 'boolean',
        ]);

        DB::beginTransaction();
        try {
            $oldStatus = $requisition->status;
            $newStatus = $validated['start_recruitment'] ?? false ? 'in_recruitment_pipeline' : 'ready_for_hr_processing';

            $requisition->update([
                'status' => $newStatus,
                'start_recruitment_flag' => $validated['start_recruitment'] ?? false,
                'hr_notes' => $validated['hr_notes'] ?? null,
                'advert_date' => $validated['advert_date'] ?? null,
                'shortlisting_date' => $validated['shortlisting_date'] ?? null,
                'hr_processed_by' => $user->id,
                'hr_processed_at' => now(),
                'current_approver_id' => null,
                'updated_by' => $user->id,
            ]);

            // Create workflow history
            RecruitmentWorkflowHistory::create([
                'requisition_id' => $requisition->id,
                'step_name' => 'HR',
                'from_status' => $oldStatus,
                'to_status' => $newStatus,
                'action' => $validated['start_recruitment'] ?? false ? 'approved' : 'submitted',
                'attended_by' => $user->id,
                'comments' => $validated['hr_notes'] ?? 'HR processing initiated',
                'metadata' => [
                    'advert_date' => $validated['advert_date'] ?? null,
                    'shortlisting_date' => $validated['shortlisting_date'] ?? null,
                ],
            ]);

            // Notify HOD
            $requisition->hod->notify(new RecruitmentRequisitionApproved($requisition, $user, $newStatus));

            DB::commit();

            $message = $validated['start_recruitment'] ?? false
                ? 'Recruitment process started. Requisition is now in recruitment pipeline.'
                : 'Requisition marked as ready for HR processing.';

            return redirect()->route('recruitment-requisitions.show', $requisition->id)
                ->with('success', $message);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error in HR processing: ' . $e->getMessage());
            return redirect()->back()
                ->withInput()
                ->with('error', 'Error processing requisition: ' . $e->getMessage());
        }
    }

    /**
     * Export PDF version of the requisition
     */
    public function exportPDF($id)
    {
        $requisition = RecruitmentRequisition::with([
            'department',
            'hod',
            'payrollAccountant',
            'hecReviewer',
            'cfoReviewer',
            'ceoReviewer',
            'hrProcessor',
            'attachments',
            'workflowHistories.attendedBy'
        ])->findOrFail($id);

        $pdf = Pdf::loadView('recruitment_requisitions.pdf', compact('requisition'));
        
        $filename = 'Recruitment_Requisition_' . $requisition->id . '_' . now()->format('Y-m-d') . '.pdf';
        
        return $pdf->download($filename);
    }

    /**
     * Helper method to resolve next approver based on status and workflow
     */
    protected function resolveNextApprover(RecruitmentRequisition $requisition, string $newStatus): ?User
    {
        return match($newStatus) {
            'submitted_by_hod' => $requisition->getResponsibleHecMember(),
            'hec_no_objection_in_budget' => User::role('hr')->first(),
            'hec_no_objection_no_budget' => User::role('cfo')->first(),
            'hec_objection_in_budget', 'hec_objection_no_budget' => User::role('hr')->first(),
            'cfo_finance_confirmed' => User::role('ceo')->first(),
            'cfo_finance_rejected' => User::role('hr')->first(),
            'ceo_approved' => User::role('hr')->first(),
            'ceo_declined' => User::role('hr')->first(),
            'ceo_needs_more_info' => $requisition->hod,
            default => null,
        };
    }
}

