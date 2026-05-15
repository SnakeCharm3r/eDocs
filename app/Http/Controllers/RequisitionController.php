<?php

namespace App\Http\Controllers;

use App\Models\Requisition;
use App\Models\User;
use App\Models\JobTitle;
use App\Models\Departments;
use App\Models\Workflow;
use App\Models\WorkFlowHistory;
use App\Mail\RequisitionApprovalRequest;
use App\Mail\RequisitionStatusUpdate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class RequisitionController extends Controller
{
    /**
     * Display list of requisitions for the current user
     * Only line managers, HEC members, and approvers can access this
     */
    public function index()
    {
        $user = Auth::user();
        $userHecRoles = collect($user->getRoleNames())
            ->map(fn ($r) => strtolower((string) $r))
            ->filter(fn ($r) => in_array($r, ['coo', 'cfo', 'cms', 'ccdro'], true))
            ->values()
            ->all();

        // Restrict access to only line managers, HEC members, approvers, or users with view_all_rrf permission
        if (!$user->hasAnyRole(['line-manager', 'coo', 'cms', 'ccdro', 'hr', 'cfo', 'ceo', 'payroll_accountant'])
            && !$user->can('view_all_rrf')) {
            abort(403, 'Only line managers and HEC members can access recruitment requisitions.');
        }

        // Check if table exists
        if (!\Schema::hasTable('requisitions')) {
            return view('requisitions.index', ['requisitions' => collect()]);
        }

        // Get requisitions based on user role (DataTables handles pagination)
        $requisitionsQuery = Requisition::with(['user', 'department.divisions', 'jobTitle', 'rejectedBy'])
            ->orderBy('created_at', 'desc');

        if ($user->hasRole('hr') || $user->can('view_all_rrf')) {
            // HR or users with view_all_rrf permission see all requisitions
            $requisitions = $requisitionsQuery->get();
        } elseif ($user->hasAnyRole(['coo', 'cms', 'ccdro', 'cfo', 'ceo'])) {
            // HEC members, CFO, CEO: see all requisitions
            $requisitions = $requisitionsQuery->get();
        } elseif ($user->hasRole('line-manager')) {
            // Line managers: all their own requisitions (pending, approved, rejected, etc.)
            $requisitions = $requisitionsQuery
                ->where('user_id', $user->id)
                ->get();
        } elseif ($user->hasRole('payroll_accountant')) {
            // Payroll: own + pending payroll + ones they reviewed
            $requisitions = $requisitionsQuery
                ->where(function ($q) use ($user) {
                    $q->where('user_id', $user->id)
                        ->orWhere('status', 'pending_payroll')
                        ->orWhere('payroll_accountant_id', $user->id);
                })
                ->get();
        } else {
            // Default: own requisitions
            $requisitions = $requisitionsQuery
                ->where('user_id', $user->id)
                ->get();
        }

        // ── Chart data for HEC members / senior roles ──────────────────────
        $chartData = null;
        if ($user->hasAnyRole(['coo', 'cms', 'ccdro', 'cfo', 'ceo', 'hr']) || $user->can('view_all_rrf')) {
            // Status distribution
            $statusCounts = $requisitions->groupBy('status')->map->count();

            // Monthly trend (last 6 months)
            $monthlyTrend = [];
            for ($i = 5; $i >= 0; $i--) {
                $month = now()->subMonths($i);
                $label = $month->format('M Y');
                $count = $requisitions->filter(function ($r) use ($month) {
                    return $r->created_at->year === $month->year && $r->created_at->month === $month->month;
                })->count();
                $monthlyTrend[] = ['label' => $label, 'count' => $count];
            }

            // Department distribution (top 8)
            $deptCounts = $requisitions->groupBy(fn ($r) => $r->dept_name ?: 'Unassigned')
                ->map->count()
                ->sortDesc()
                ->take(8);

            $deptPositionBreakdown = $requisitions
                ->groupBy(fn ($r) => $r->dept_name ?: 'Unassigned')
                ->map(function ($departmentRequisitions) {
                    return [
                        'new_position' => $departmentRequisitions->where('position_type', 'new_position')->count(),
                        'contract_renewal' => $departmentRequisitions->where('position_type', 'contract_renewal')->count(),
                        'replacement' => $departmentRequisitions->where('position_type', 'replacement')->count(),
                    ];
                })
                ->only($deptCounts->keys());

            // Position type distribution
            $posTypeCounts = $requisitions->groupBy(fn ($r) => $r->getPositionTypeLabel())->map->count();

            $chartData = [
                'statusCounts'          => $statusCounts,
                'monthlyTrend'          => $monthlyTrend,
                'deptCounts'            => $deptCounts,
                'deptPositionBreakdown' => $deptPositionBreakdown,
                'posTypeCounts'         => $posTypeCounts,
            ];
        }

        return view('requisitions.index', compact('requisitions', 'chartData'));
    }

    /**
     * Show pending requisitions for approval / tracking
     */
    public function pending()
    {
        $user = Auth::user();
        $userHecRoles = collect($user->getRoleNames())
            ->map(fn ($r) => strtolower((string) $r))
            ->filter(fn ($r) => in_array($r, ['coo', 'cfo', 'cms', 'ccdro'], true))
            ->values()
            ->all();
        $requisitions = collect();

        // Check if table exists
        if (!\Schema::hasTable('requisitions')) {
            return view('requisitions.pending', [
                'requisitions' => collect(),
                'userRoleContext' => 'unknown',
                'stats' => ['pending' => 0, 'approved' => 0, 'rejected' => 0, 'in_progress' => 0],
            ]);
        }

        // Determine role context for UI display
        $userRoleContext = 'line-manager'; // default

        if ($user->hasRole('payroll_accountant')) {
            $userRoleContext = 'payroll_accountant';
            $requisitions = Requisition::with(['user', 'department'])
                ->where('status', 'pending_payroll')
                ->orderBy('created_at', 'desc')
                ->get();
        } elseif ($user->hasAnyRole(['coo', 'cfo', 'cms', 'ccdro'])) {
            $userRoleContext = $user->hasRole('cfo') ? 'cfo' : 'hec';
            $hecStatuses = ['pending_hec'];
            if ($user->hasRole('cfo')) {
                $hecStatuses[] = 'pending_cfo';
            }
            $requisitions = Requisition::with(['user', 'department'])
                ->where(function ($query) use ($user, $userHecRoles, $hecStatuses) {
                    $query->where(function ($q) use ($user, $userHecRoles) {
                        $q->where('status', 'pending_hec')
                            ->whereHas('department', function ($dq) use ($user, $userHecRoles) {
                                $dq->where('hec_member_id', $user->id)
                                    ->orWhereHas('hec', function ($hecQuery) use ($userHecRoles) {
                                        if (empty($userHecRoles)) {
                                            $hecQuery->whereRaw('1 = 0');
                                        } else {
                                            $hecQuery->whereIn(DB::raw('LOWER(hec_level_name)'), $userHecRoles);
                                        }
                                    });
                            });
                    });
                    if (in_array('pending_cfo', $hecStatuses)) {
                        $query->orWhere('status', 'pending_cfo');
                    }
                })
                ->orderBy('created_at', 'desc')
                ->get();
        } elseif ($user->hasRole('ceo')) {
            $userRoleContext = 'ceo';
            $requisitions = Requisition::with(['user', 'department'])
                ->where('status', 'pending_ceo')
                ->orderBy('created_at', 'desc')
                ->get();
        } elseif ($user->hasRole('hr')) {
            $userRoleContext = 'hr';
            $requisitions = Requisition::with(['user', 'department'])
                ->where('status', 'pending_hr')
                ->orderBy('created_at', 'desc')
                ->get();
        } elseif ($user->hasRole('line-manager')) {
            $userRoleContext = 'line-manager';
            $requisitions = Requisition::with(['user', 'department'])
                ->where('user_id', $user->id)
                ->whereNotIn('status', ['approved', 'rejected'])
                ->where(function ($q) {
                    // Exclude expired rejected_for_editing items
                    $q->where('status', '!=', 'rejected_for_editing')
                      ->orWhere(function ($q2) {
                          $q2->whereNull('can_edit_until')->orWhere('can_edit_until', '>', now());
                      });
                })
                ->orderBy('created_at', 'desc')
                ->get();
        }

        // Build stats for the current user
        $stats = ['pending' => 0, 'approved' => 0, 'rejected' => 0, 'in_progress' => 0];
        if ($userRoleContext === 'line-manager') {
            $allMyReqs = Requisition::where('user_id', $user->id)->get();
            $stats['pending'] = $allMyReqs->filter(fn ($r) => !in_array($r->status, ['approved', 'rejected', 'rejected_for_editing']))->count();
            $stats['approved'] = $allMyReqs->where('status', 'approved')->count();
            $stats['rejected'] = $allMyReqs->filter(fn ($r) => in_array($r->status, ['rejected']) || ($r->status === 'rejected_for_editing' && $r->isExpired()))->count();
            $stats['in_progress'] = $allMyReqs->whereIn('status', ['pending_hec', 'pending_cfo', 'pending_ceo', 'pending_hr'])->count();
        } else {
            $stats['pending'] = $requisitions->count();
        }

        return view('requisitions.pending', compact('requisitions', 'userRoleContext', 'stats'));
    }

    /**
     * Show create form
     */
    public function create()
    {
        $user = Auth::user();

        // Determine initiator type
        $isHecMember = $user->hasAnyRole(['coo', 'cms', 'cfo', 'ccdro']);
        $isLineManager = $user->hasRole('line-manager');

        if (!$isHecMember && !$isLineManager) {
            return redirect()->route('requisitions.index')
                ->with('error', 'You do not have permission to create requisitions.');
        }

        // Get departments
        if ($isHecMember) {
            // HEC can select from departments they oversee
            $departments = Departments::where('hec_member_id', $user->id)
                ->orWhereHas('hec', function ($q) use ($user) {
                    $q->whereRaw("LOWER(hec_level_name) = ?", [strtolower($user->getRoleNames()->first())]);
                })
                ->orderBy('dept_name')
                ->get();

            if ($departments->isEmpty()) {
                $departments = Departments::orderBy('dept_name')->get();
            }
        } else {
            // Line manager can only use their department
            $departments = collect([$user->department]);
        }

        // Get job titles
        $jobTitles = JobTitle::orderBy('job_title')->get();

        // Get line managers for selection (HEC needs this for replacement/renewal)
        $lineManagers = User::role('line-manager')
            ->where('status', 'active')
            ->orderBy('fname')
            ->get();

        // Get user's department ID for line managers
        $userDepartmentId = null;
        if ($isLineManager) {
            // Try multiple ways to get department ID
            $userDepartmentId = $user->deptId ?? $user->department?->id ?? $departments->first()?->id;

            // Log for debugging
            \Log::info('Line Manager Department ID', [
                'user_id' => $user->id,
                'deptId' => $user->deptId,
                'department_id' => $user->department?->id,
                'departments_first_id' => $departments->first()?->id,
                'final_userDepartmentId' => $userDepartmentId
            ]);
        }

        return view('requisitions.create', compact(
            'isHecMember',
            'isLineManager',
            'departments',
            'jobTitles',
            'lineManagers',
            'userDepartmentId'
        ));
    }

    /**
     * Store new requisition
     */
    public function store(Request $request)
    {
        $user = Auth::user();
        $isHecMember = $user->hasAnyRole(['coo', 'cms', 'cfo', 'ccdro']);

        // Validate
        $rules = [
            'position_type' => 'required|in:new_position,replacement,contract_renewal',
            'department_id' => 'required|exists:departments,id',
            'required_start_date' => 'required|date|after:today',
            'job_description' => 'required|file|mimes:pdf|max:10240',
            'elaborate_reason' => 'required|string|min:50',
        ];

        // Additional validation for replacement/renewal
        if (in_array($request->position_type, ['replacement', 'contract_renewal'])) {
            // Employee ID only required for line managers, not HEC members
            if (!$isHecMember) {
                $rules['employee_id'] = 'required|array|min:1';
                $rules['employee_id.*'] = 'required|exists:users,id';
                $rules['current_contract_end_date'] = 'required|date';
                $rules['job_title_id'] = 'required|exists:job_titles,id';
            } else {
                // For HEC members, these are optional since no employee is selected
                $rules['current_contract_end_date'] = 'nullable|date';
                $rules['job_title_id'] = 'nullable|exists:job_titles,id';
            }
            $rules['contract_type'] = 'required|in:minimal_1_year,termed_less_1_year,health_volunteer,work_exposure';
        }

        // Line manager always required for HEC members
        if ($isHecMember) {
            $rules['line_manager_id'] = 'required|exists:users,id';
        }

        if ($request->position_type === 'new_position') {
            $rules['new_job_title'] = 'required|string|max:255';
            $rules['contract_type'] = 'required|in:minimal_1_year,termed_less_1_year,health_volunteer,work_exposure';
        } else {
            // Job title ID required for line managers, but already set as nullable for HEC members above
            // Only set as required if not already set (for HEC members in replacement/renewal, it's nullable)
            if (!isset($rules['job_title_id'])) {
                $rules['job_title_id'] = 'required|exists:job_titles,id';
            }
        }

        // At least one condition must be selected
        $request->validate($rules);

        // Check at least one condition
        $conditions = [
            $request->condition_medical_operational,
            $request->condition_safety_reputational,
            $request->condition_legal_requirement,
            $request->condition_financial_loss,
            $request->condition_increase_income,
        ];

        if (!in_array(true, array_map('boolval', $conditions))) {
            return back()->withErrors(['conditions' => 'At least one condition must be selected.'])->withInput();
        }

        // Check if table exists before proceeding
        if (!\Schema::hasTable('requisitions')) {
            return back()->with('error', 'The requisitions table does not exist. Please run the migration: php artisan migrate')->withInput();
        }

        DB::beginTransaction();
        try {
            // Upload job description with error handling
            $jobDescPath = null;
            if ($request->hasFile('job_description')) {
                try {
                    $file = $request->file('job_description');
                    if ($file->isValid()) {
                        $jobDescPath = $file->store('requisitions/job-descriptions', 'public');
                        Log::info('Job description uploaded successfully', [
                            'requisition_access_id' => 'pending',
                            'file_path' => $jobDescPath,
                            'original_name' => $file->getClientOriginalName(),
                            'file_size' => $file->getSize()
                        ]);
                    } else {
                        Log::error('Invalid job description file', [
                            'error' => $file->getErrorMessage(),
                            'original_name' => $file->getClientOriginalName()
                        ]);
                        return back()->with('error', 'Invalid job description file: ' . $file->getErrorMessage())->withInput();
                    }
                } catch (\Exception $e) {
                    Log::error('Failed to upload job description', [
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString()
                    ]);
                    return back()->with('error', 'Failed to upload job description. Please try again.')->withInput();
                }
            }

            // Get department info with validation
            $department = Departments::find($request->department_id);
            if (!$department) {
                Log::error('Department not found during requisition creation', [
                    'department_id' => $request->department_id,
                    'user_id' => $user->id
                ]);
                return back()->with('error', 'Selected department not found. Please select a valid department.')->withInput();
            }

            // Validate that department has HEC member mapped
            if (!$department->hec_member_id) {
                Log::warning('Department has no HEC member assigned', [
                    'department_id' => $department->id,
                    'department_name' => $department->dept_name,
                    'user_id' => $user->id
                ]);
                return back()->with('error', 'No HEC member assigned for your department.')->withInput();
            }

            // Verify the assigned HEC member exists and has proper roles
            $assignedHecMember = User::find($department->hec_member_id);
            if (!$assignedHecMember) {
                Log::error('Assigned HEC member not found', [
                    'department_id' => $department->id,
                    'department_name' => $department->dept_name,
                    'hec_member_id' => $department->hec_member_id,
                    'user_id' => $user->id
                ]);
                return back()->with('error', 'No HEC member assigned for your department.')->withInput();
            }

            // Verify HEC member has proper roles
            if (!$assignedHecMember->hasAnyRole(['coo', 'cms', 'cfo', 'ccdro'])) {
                Log::error('Assigned HEC member lacks proper roles', [
                    'department_id' => $department->id,
                    'department_name' => $department->dept_name,
                    'hec_member_id' => $department->hec_member_id,
                    'hec_member_name' => $assignedHecMember->fname . ' ' . $assignedHecMember->lname,
                    'hec_member_roles' => $assignedHecMember->getRoleNames()->implode(', '),
                    'user_id' => $user->id
                ]);
                return back()->with('error', 'No HEC member assigned for your department.')->withInput();
            }

            // Validate line manager if HEC member
            if ($isHecMember && $request->line_manager_id) {
                $lineManager = User::find($request->line_manager_id);
                if (!$lineManager) {
                    Log::error('Line manager not found during requisition creation', [
                        'line_manager_id' => $request->line_manager_id,
                        'user_id' => $user->id
                    ]);
                    return back()->with('error', 'Selected line manager not found. Please select a valid line manager.')->withInput();
                }
            }

            // Validate employees if specified (supports multiple selection)
            $employeeIds = [];
            $employeeNames = [];
            $firstEmployeeId = null;
            $firstEmployeeName = null;

            if ($request->employee_id) {
                $ids = is_array($request->employee_id) ? $request->employee_id : [$request->employee_id];
                foreach ($ids as $empId) {
                    $emp = User::find($empId);
                    if (!$emp) {
                        Log::error('Employee not found during requisition creation', [
                            'employee_id' => $empId,
                            'user_id' => $user->id
                        ]);
                        return back()->with('error', 'Selected employee not found (ID: ' . $empId . '). Please select valid employees.')->withInput();
                    }
                    $employeeIds[] = $emp->id;
                    $employeeNames[] = trim($emp->fname . ' ' . ($emp->mname ?? '') . ' ' . $emp->lname);
                }
                $firstEmployeeId = $employeeIds[0] ?? null;
                $firstEmployeeName = $employeeNames[0] ?? null;
            }

            // Prepare requisition data
            $requisitionData = [
                'user_id' => $user->id,
                'initiator_type' => $isHecMember ? 'hec_member' : 'line_manager',
                'initiator_name' => $user->fname . ' ' . $user->lname,
                'initiator_role' => $user->getRoleNames()->first(),
                'position_type' => $request->position_type,
                'employee_id' => $firstEmployeeId,
                'employee_name' => $firstEmployeeName,
                'employee_ids' => !empty($employeeIds) ? $employeeIds : null,
                'employee_names' => !empty($employeeNames) ? $employeeNames : null,
                'current_contract_end_date' => $request->current_contract_end_date,
                'line_manager_id' => $isHecMember ? $request->line_manager_id : $user->id,
                'department_id' => $request->department_id,
                'dept_name' => $department->dept_name,
                'responsibility_centre' => $department->responsibility_centre ?? $request->responsibility_centre,
                'reporting_line' => $request->reporting_line,
                'job_title_id' => $request->job_title_id,
                'new_job_title' => $request->new_job_title,
                'contract_type' => $request->contract_type,
                'required_start_date' => $request->required_start_date,
                'job_description_path' => $jobDescPath,
                'condition_medical_operational' => $request->boolean('condition_medical_operational'),
                'condition_safety_reputational' => $request->boolean('condition_safety_reputational'),
                'condition_legal_requirement' => $request->boolean('condition_legal_requirement'),
                'condition_financial_loss' => $request->boolean('condition_financial_loss'),
                'condition_increase_income' => $request->boolean('condition_increase_income'),
                'elaborate_reason' => $request->elaborate_reason,
                'status' => 'pending_payroll',
                'current_step' => 'payroll_review',
            ];

            // No initial financial decision captured anymore

            // Create requisition
            $requisition = Requisition::create($requisitionData);

            // Create workflow
            $workflow = Workflow::create([
                'user_id' => $user->id,
                'requisition_id' => $requisition->id,
                'work_flow_status' => 0,
                'work_flow_completed' => 0,
            ]);

            // Create initial workflow history
            $payrollAccountant = User::role('payroll_accountant')->first();

            WorkFlowHistory::create([
                'work_flow_id' => $workflow->id,
                'forwarded_by' => $user->id,
                'attended_by' => $payrollAccountant?->id,
                'step_name' => 'Payroll Review',
                'remark' => 'Requisition submitted - awaiting payroll review',
                'requisition_status' => 1, // Pending
                'status' => 0,
            ]);

            DB::commit();

            // Send notification to payroll accountant (next approver)
            if ($payrollAccountant && $payrollAccountant->email) {
                try {
                    Mail::to($payrollAccountant->email)->queue(new RequisitionApprovalRequest(
                        $requisition,
                        $payrollAccountant,
                        'Payroll Review',
                        $user
                    ));
                } catch (\Exception $e) {
                    Log::error('Failed to send requisition approval email to payroll accountant', [
                        'requisition_id' => $requisition->id,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            // Notify initiator about submission/status
            $this->notifyInitiatorStatus($requisition, $user, 'pending_payroll', 'Requisition submitted and awaiting Payroll Review', 'Initiation');

            return redirect()->route('requisitions.show', $requisition->access_id)
                ->with('success', 'Requisition submitted successfully. Awaiting Payroll Accountant review.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Failed to create requisition: ' . $e->getMessage())->withInput();
        }
    }

    /**
     * Show requisition details
     */
    public function show($accessId)
    {
        $requisition = Requisition::with([
            'user',
            'employee',
            'lineManager',
            'department',
            'jobTitle',
            'payrollAccountant',
            'hecReviewer',
            'cfo',
            'ceo',
            'hr',
            'rejectedBy',
            'workflow.histories'
        ])->where('access_id', $accessId)->firstOrFail();

        $user = Auth::user();

        // Determine user permissions with error handling
        $canReviewPayroll = $user->hasRole('payroll_accountant') && $requisition->status === 'pending_payroll';
        $canReviewHEC = $user->hasAnyRole(['coo', 'cms', 'cfo', 'ccdro'])
            && $requisition->status === 'pending_hec'
            && $requisition->department 
            && $requisition->department->hec_member_id === $user->id;
        $canReviewCFO = $user->hasRole('cfo') && $requisition->status === 'pending_cfo';
        $canReviewCEO = $user->hasRole('ceo') && $requisition->status === 'pending_ceo';
        $canReviewHR = $user->hasRole('hr') && $requisition->status === 'pending_hr';
        $isInitiator = $requisition->user_id === $user->id;

        // Log potential issues
        if ($requisition->status === 'pending_hec' && !$requisition->department) {
            Log::warning('Requisition pending HEC review has no department', [
                'requisition_id' => $requisition->id,
                'access_id' => $requisition->access_id,
                'department_id' => $requisition->department_id
            ]);
        }

        return view('requisitions.show', compact(
            'requisition',
            'canReviewPayroll',
            'canReviewHEC',
            'canReviewCFO',
            'canReviewCEO',
            'canReviewHR',
            'isInitiator'
        ));
    }

    /**
     * Payroll Accountant Review
     */
    public function payrollReview(Request $request, $accessId)
    {
        $requisition = Requisition::where('access_id', $accessId)->firstOrFail();
        $user = Auth::user();

        if (!$user->hasRole('payroll_accountant')) {
            return back()->with('error', 'Unauthorized action.');
        }

        $request->validate([
            'budget_approved' => 'required|boolean',
            'max_monthly_budget' => 'nullable|numeric|min:0',
            'funding_available' => 'nullable|boolean',
            'donor_code' => 'nullable|string|max:50',
            'activity_code' => 'nullable|string|max:50',
            'payroll_comment' => 'nullable|string',
        ]);

        DB::beginTransaction();
        try {
            // Handle approval only (no reject-from-payroll)
            $requisition->update([
                'budget_approved' => $request->boolean('budget_approved'),
                'max_monthly_budget' => $request->max_monthly_budget,
                'funding_available' => $request->boolean('funding_available'),
                'donor_code' => $request->donor_code,
                'activity_code' => $request->activity_code,
                'payroll_accountant_id' => $user->id,
                'payroll_reviewed_at' => now(),
                'payroll_comment' => $request->payroll_comment,
            ]);

            // Determine next step based on workflow
            $nextStatus = $this->determineNextStepAfterPayroll($requisition);
            $requisition->update([
                'status' => $nextStatus,
                'current_step' => $this->getStepFromStatus($nextStatus),
            ]);

            // Update workflow history
            $this->updateWorkflowHistory($requisition, $user, 'Payroll Review', 'Payroll review completed');

            // Create next step history
            $nextUser = $this->createNextStepHistory($requisition);

            DB::commit();

            // Send email to next approver
            if ($nextUser) {
                try {
                    // Validate next user has email
                    if (!$nextUser->email) {
                        Log::warning('Next approver has no email address', [
                            'requisition_id' => $requisition->id,
                            'next_user_id' => $nextUser->id,
                            'step_name' => $this->getStepNameFromStatus($requisition->status)
                        ]);
                    } else {
                        // Validate email format
                        if (!filter_var($nextUser->email, FILTER_VALIDATE_EMAIL)) {
                            Log::error('Invalid email format for next approver', [
                                'requisition_id' => $requisition->id,
                                'next_user_id' => $nextUser->id,
                                'email' => $nextUser->email
                            ]);
                        } else {
                            $stepName = $this->getStepNameFromStatus($requisition->status);
                            Mail::to($nextUser->email)->queue(new RequisitionApprovalRequest(
                                $requisition,
                                $nextUser,
                                $stepName,
                                $user
                            ));

                            Log::info('Approval request email sent successfully', [
                                'requisition_id' => $requisition->id,
                                'recipient_email' => $nextUser->email,
                                'step_name' => $stepName
                            ]);
                        }
                    }
                } catch (\Exception $e) {
                    Log::error('Failed to send requisition approval email', [
                        'requisition_id' => $requisition->id,
                        'next_user_id' => $nextUser->id,
                        'recipient_email' => $nextUser->email ?? 'none',
                        'step_name' => $this->getStepNameFromStatus($requisition->status),
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString()
                    ]);
                }
            } else {
                Log::warning('No next approver found for requisition', [
                    'requisition_id' => $requisition->id,
                    'status' => $requisition->status
                ]);
            }

            // Notify initiator about status change
            $this->notifyInitiatorStatus(
                $requisition,
                $user,
                $requisition->status,
                'Payroll review completed. Next step: ' . $this->getStepNameFromStatus($requisition->status),
                'Payroll Review'
            );

            return redirect()->route('requisitions.pending')
                ->with('success', 'Payroll review completed successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Failed to submit review: ' . $e->getMessage());
        }
    }

    /**
     * HEC Member Review
     */
    public function hecReview(Request $request, $accessId)
    {
        $requisition = Requisition::where('access_id', $accessId)->firstOrFail();
        $user = Auth::user();

        if (!$user->hasAnyRole(['coo', 'cms', 'cfo', 'ccdro'])) {
            return back()->with('error', 'Unauthorized action.');
        }

        $request->validate([
            'action' => 'nullable|in:reject',
            'hec_financial_decision' => 'required_without:action|nullable|in:no_financial_implication,proposed_funding,reject,no_objection,objection,no_objection_cfo_ceo',
            'proposed_funding_source' => 'required_if:hec_financial_decision,proposed_funding|nullable|string',
            'hec_comment' => 'nullable|string',
            'rejection_reason' => 'required_if:action,reject|nullable|string|min:10',
        ]);

        DB::beginTransaction();
        try {
            if ($request->has('action') && $request->action === 'reject') {
                // Handle rejection
                $requisition->update([
                    'status' => 'rejected_for_editing',
                    'current_step' => 'rejected',
                    'rejection_reason' => $request->rejection_reason,
                    'rejected_at' => now(),
                    'rejected_by' => $user->id,
                    'rejection_stage' => 'hec',
                    'can_edit_until' => now()->addDays(10), // 10 days to edit
                    'hec_reviewer_id' => $user->id,
                    'hec_reviewed_at' => now(),
                    'hec_comment' => $request->rejection_reason,
                ]);

                // Update workflow history
                $this->updateWorkflowHistory($requisition, $user, 'HEC Review', 'Rejected: ' . $request->rejection_reason);

                DB::commit();

                // Send rejection email to submitter
                if ($requisition->user && $requisition->user->email) {
                    try {
                        Mail::to($requisition->user->email)->queue(new RequisitionStatusUpdate(
                            $requisition,
                            $requisition->user,
                            'rejected',
                            $user,
                            $request->rejection_reason,
                            'HEC Review'
                        ));
                    } catch (\Exception $e) {
                        Log::error('Failed to send requisition rejection email', [
                            'requisition_id' => $requisition->id,
                            'error' => $e->getMessage()
                        ]);
                    }
                }

                return redirect()->route('requisitions.pending')
                    ->with('success', 'Requisition rejected. Submitter has one month to edit and resubmit.');
            }

            // Handle approval
            $requisition->update([
                'hec_financial_decision' => $request->hec_financial_decision,
                'proposed_funding_source' => $request->proposed_funding_source,
                'hec_comment' => $request->hec_comment,
                'hec_reviewer_id' => $user->id,
                'hec_reviewed_at' => now(),
            ]);

            // Determine next step
            $nextStatus = $this->determineNextStepAfterHEC($requisition);
            $requisition->update([
                'status' => $nextStatus,
                'current_step' => $this->getStepFromStatus($nextStatus),
            ]);

            // Update workflow history
            $this->updateWorkflowHistory($requisition, $user, 'HEC Review', 'HEC review completed: ' . $request->hec_financial_decision);

            // Create next step history
            $nextUser = $this->createNextStepHistory($requisition);

            DB::commit();

            // Send email to next approver
            if ($nextUser && $nextUser->email) {
                try {
                    $stepName = $this->getStepNameFromStatus($requisition->status);
                    Mail::to($nextUser->email)->queue(new RequisitionApprovalRequest(
                        $requisition,
                        $nextUser,
                        $stepName,
                        $user
                    ));
                } catch (\Exception $e) {
                    Log::error('Failed to send requisition approval email', [
                        'requisition_id' => $requisition->id,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            // Notify initiator about status change
            $this->notifyInitiatorStatus(
                $requisition,
                $user,
                $requisition->status,
                'HEC review completed. Next step: ' . $this->getStepNameFromStatus($requisition->status),
                'HEC Review'
            );

            return redirect()->route('requisitions.pending')
                ->with('success', 'HEC review completed successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Failed to submit review: ' . $e->getMessage());
        }
    }

    /**
     * CFO Review
     */
    public function cfoReview(Request $request, $accessId)
    {
        $requisition = Requisition::where('access_id', $accessId)->firstOrFail();
        $user = Auth::user();

        if (!$user->hasRole('cfo')) {
            return back()->with('error', 'Unauthorized action.');
        }

        $request->validate([
            'cfo_decision' => 'required|in:approved,rejected',
            'cfo_financing_confirmation' => 'required_if:cfo_decision,approved|nullable|string',
            'cfo_financing_code' => 'nullable|string|max:50',
            'cfo_comment' => 'nullable|string',
            'rejection_reason' => 'required_if:cfo_decision,rejected|nullable|string|min:10',
        ]);

        DB::beginTransaction();
        try {
            $requisition->update([
                'cfo_decision' => $request->cfo_decision,
                'cfo_financing_confirmation' => $request->cfo_financing_confirmation,
                'cfo_financing_code' => $request->cfo_financing_code,
                'cfo_comment' => $request->cfo_comment,
                'cfo_id' => $user->id,
                'cfo_reviewed_at' => now(),
            ]);

            // If approved, send to CEO; if rejected, send to HR for filing
            if ($request->cfo_decision === 'approved') {
                $requisition->update([
                    'status' => 'pending_ceo',
                    'current_step' => 'ceo_review',
                ]);
            } else {
                $requisition->update([
                    'status' => 'filed',
                    'current_step' => 'completed',
                    'hr_filed_only' => true,
                ]);
            }

            $this->updateWorkflowHistory($requisition, $user, 'CFO Review', 'CFO decision: ' . $request->cfo_decision);
            
            // If rejected, notify submitter and allow editing; if approved, notify next approver
            if ($request->cfo_decision === 'rejected') {
                // Set status to rejected_for_editing with 10 days to edit
                $requisition->update([
                    'status' => 'rejected_for_editing',
                    'current_step' => 'rejected',
                    'rejection_reason' => $request->rejection_reason ?? $request->cfo_comment ?? 'Rejected by CFO',
                    'rejected_at' => now(),
                    'rejected_by' => $user->id,
                    'rejection_stage' => 'cfo',
                    'can_edit_until' => now()->addDays(10),
                ]);

                // Notify submitter of rejection
                if ($requisition->user && $requisition->user->email) {
                    try {
                        Mail::to($requisition->user->email)->queue(new RequisitionStatusUpdate(
                            $requisition,
                            $requisition->user,
                            'rejected',
                            $user,
                            $request->rejection_reason ?? $request->cfo_comment ?? 'Rejected by CFO',
                            'CFO Review'
                        ));
                    } catch (\Exception $e) {
                        Log::error('Failed to send requisition rejection email', [
                            'requisition_id' => $requisition->id,
                            'error' => $e->getMessage()
                        ]);
                    }
                }
            } else {
                // Create next step history and notify next approver
                $nextUser = $this->createNextStepHistory($requisition);
                if ($nextUser && $nextUser->email) {
                    try {
                        $stepName = $this->getStepNameFromStatus($requisition->status);
                        Mail::to($nextUser->email)->queue(new RequisitionApprovalRequest(
                            $requisition,
                            $nextUser,
                            $stepName,
                            $user
                        ));
                    } catch (\Exception $e) {
                        Log::error('Failed to send requisition approval email', [
                            'requisition_id' => $requisition->id,
                            'error' => $e->getMessage()
                        ]);
                    }
                }

                // Notify initiator about status change
                $this->notifyInitiatorStatus(
                    $requisition,
                    $user,
                    $requisition->status,
                    'CFO review completed. Next step: ' . $this->getStepNameFromStatus($requisition->status),
                    'CFO Review'
                );
            }

            DB::commit();

            return redirect()->route('requisitions.pending')
                ->with('success', 'CFO review completed successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Failed to submit review: ' . $e->getMessage());
        }
    }

    /**
     * CEO Review
     */
    public function ceoReview(Request $request, $accessId)
    {
        $requisition = Requisition::where('access_id', $accessId)->firstOrFail();
        $user = Auth::user();

        if (!$user->hasRole('ceo')) {
            return back()->with('error', 'Unauthorized action.');
        }

        $request->validate([
            'ceo_decision' => 'required|in:approved,declined',
            'ceo_comment' => 'nullable|string',
            'rejection_reason' => 'required_if:ceo_decision,declined|nullable|string|min:10',
        ]);

        DB::beginTransaction();
        try {
            $requisition->update([
                'ceo_decision' => $request->ceo_decision,
                'ceo_comment' => $request->ceo_comment,
                'ceo_id' => $user->id,
                'ceo_reviewed_at' => now(),
            ]);

            // Determine next step
            if ($request->ceo_decision === 'approved') {
                // Go to HR
                $requisition->update([
                    'status' => 'pending_hr',
                    'current_step' => 'hr_review',
                ]);
            } elseif ($request->ceo_decision === 'declined') {
                // Reject and return to initiator (then marked rejected_for_editing below)
                $requisition->update([
                    'status' => 'rejected',
                    'current_step' => 'completed',
                    'hr_filed_only' => false,
                ]);
            }

            $this->updateWorkflowHistory($requisition, $user, 'CEO Review', 'CEO decision: ' . $request->ceo_decision);
            
            // Handle different CEO decisions
            if ($request->ceo_decision === 'declined') {
                // Set status to rejected_for_editing with 10 days to edit
                $requisition->update([
                    'status' => 'rejected_for_editing',
                    'current_step' => 'rejected',
                    'rejection_reason' => $request->rejection_reason ?? $request->ceo_comment ?? 'Declined by CEO',
                    'rejected_at' => now(),
                    'rejected_by' => $user->id,
                    'rejection_stage' => 'ceo',
                    'can_edit_until' => now()->addDays(10),
                ]);

                // Notify submitter of rejection
                if ($requisition->user && $requisition->user->email) {
                    try {
                        Mail::to($requisition->user->email)->queue(new RequisitionStatusUpdate(
                            $requisition,
                            $requisition->user,
                            'rejected',
                            $user,
                            $request->rejection_reason ?? $request->ceo_comment ?? 'Declined by CEO',
                            'CEO Review'
                        ));
                    } catch (\Exception $e) {
                        Log::error('Failed to send requisition rejection email', [
                            'requisition_id' => $requisition->id,
                            'error' => $e->getMessage()
                        ]);
                    }
                }
            } else {
                // Create next step history and notify next approver
                $nextUser = $this->createNextStepHistory($requisition);
                if ($nextUser && $nextUser->email) {
                    try {
                        $stepName = $this->getStepNameFromStatus($requisition->status);
                        Mail::to($nextUser->email)->queue(new RequisitionApprovalRequest(
                            $requisition,
                            $nextUser,
                            $stepName,
                            $user
                        ));
                    } catch (\Exception $e) {
                        Log::error('Failed to send requisition approval email', [
                            'requisition_id' => $requisition->id,
                            'error' => $e->getMessage()
                        ]);
                    }
                }

                // Notify initiator about status change
                $this->notifyInitiatorStatus(
                    $requisition,
                    $user,
                    $requisition->status,
                    'CEO review completed. Next step: ' . $this->getStepNameFromStatus($requisition->status),
                    'CEO Review'
                );
            }

            DB::commit();

            return redirect()->route('requisitions.pending')
                ->with('success', 'CEO review completed successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Failed to submit review: ' . $e->getMessage());
        }
    }

    /**
     * HR Final Review
     */
    public function hrReview(Request $request, $accessId)
    {
        $requisition = Requisition::where('access_id', $accessId)->firstOrFail();
        $user = Auth::user();

        if (!$user->hasRole('hr')) {
            return back()->with('error', 'Unauthorized action.');
        }

        $request->validate([
            'hr_decision' => 'required|in:approved,rejected',
            'hr_comment' => 'nullable|string',
            'hr_attachment' => 'nullable|file|mimes:pdf|max:10240',
            'rejection_reason' => 'required_if:hr_decision,rejected|nullable|string|min:10',
        ]);

        DB::beginTransaction();
        try {
            $attachmentPath = null;
            if ($request->hasFile('hr_attachment')) {
                $attachmentPath = $request->file('hr_attachment')->store('requisitions/hr-attachments', 'public');
            }

            if ($request->hr_decision === 'rejected') {
                // Set status to rejected_for_editing with 10 days to edit
                $requisition->update([
                    'hr_decision' => $request->hr_decision,
                    'hr_comment' => $request->hr_comment,
                    'hr_attachment_path' => $attachmentPath,
                    'hr_id' => $user->id,
                    'hr_reviewed_at' => now(),
                    'status' => 'rejected_for_editing',
                    'current_step' => 'rejected',
                    'rejection_reason' => $request->rejection_reason ?? $request->hr_comment ?? 'Rejected by HR',
                    'rejected_at' => now(),
                    'rejected_by' => $user->id,
                    'rejection_stage' => 'hr',
                    'can_edit_until' => now()->addDays(10),
                ]);
            } else {
                $requisition->update([
                    'hr_decision' => $request->hr_decision,
                    'hr_comment' => $request->hr_comment,
                    'hr_attachment_path' => $attachmentPath,
                    'hr_id' => $user->id,
                    'hr_reviewed_at' => now(),
                    'status' => 'approved',
                    'current_step' => 'completed',
                ]);
            }

            // Mark workflow as completed
            if ($requisition->workflow) {
                $requisition->workflow->update([
                    'work_flow_status' => $request->hr_decision === 'approved' ? 3 : 2,
                    'work_flow_completed' => 1,
                ]);
            }

            $this->updateWorkflowHistory($requisition, $user, 'HR Review', 'HR decision: ' . $request->hr_decision);

            DB::commit();

            // Notify submitter of final decision
            if ($requisition->user && $requisition->user->email) {
                try {
                    Mail::to($requisition->user->email)->queue(new RequisitionStatusUpdate(
                        $requisition,
                        $requisition->user,
                        $request->hr_decision === 'approved' ? 'approved' : 'rejected',
                        $user,
                        $request->hr_comment ?? ($request->hr_decision === 'approved' ? 'Approved by HR' : 'Rejected by HR'),
                        'HR Review'
                    ));
                } catch (\Exception $e) {
                    Log::error('Failed to send requisition status update email', [
                        'requisition_id' => $requisition->id,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            return redirect()->route('requisitions.pending')
                ->with('success', 'HR review completed successfully. Requisition ' . ($request->hr_decision === 'approved' ? 'approved' : 'rejected') . '.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Failed to submit review: ' . $e->getMessage());
        }
    }

    /**
     * Get employees by department (AJAX)
     */
    public function getEmployeesByDepartment(Request $request)
    {
        try {
            $departmentId = $request->department_id;
            $includeInactive = $request->boolean('include_inactive');

            if (!$departmentId) {
                return response()->json(['employees' => [], 'error' => 'Department ID is required'], 400);
            }

            $query = User::where('deptId', $departmentId)
                ->select('id', 'fname', 'mname', 'lname', 'username', 'ending_date', 'job_title', 'status');

            // For renewal: include all users (active + inactive/deactivated)
            // For replacement: only active users
            if (!$includeInactive) {
                $query->where('status', 'active');
            }

            $employees = $query->orderBy('fname')
                ->get()
                ->map(function ($user) {
                    $statusLabel = $user->status !== 'active' ? ' [' . ucfirst($user->status) . ']' : '';
                    return [
                        'id' => $user->id,
                        'name' => trim($user->fname . ' ' . ($user->mname ?? '') . ' ' . $user->lname),
                        'username' => $user->username ?? 'N/A',
                        'contract_end_date' => $user->ending_date ? date('Y-m-d', strtotime($user->ending_date)) : null,
                        'job_title_id' => $user->job_title,
                        'status' => $user->status,
                        'status_label' => $statusLabel,
                    ];
                });

            return response()->json(['employees' => $employees]);
        } catch (\Exception $e) {
            \Log::error('Error loading employees by department: ' . $e->getMessage());
            return response()->json(['employees' => [], 'error' => 'Failed to load employees: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Get line managers by department (AJAX)
     */
    public function getLineManagersByDepartment(Request $request)
    {
        $departmentId = $request->department_id;

        $lineManagers = User::role('line-manager')
            ->where('deptId', $departmentId)
            ->where('status', 'active')
            ->select('id', 'fname', 'mname', 'lname', 'job_title')
            ->orderBy('fname')
            ->get()
            ->map(function ($user) {
                return [
                    'id' => $user->id,
                    'name' => trim($user->fname . ' ' . ($user->mname ?? '') . ' ' . $user->lname),
                    'job_title_id' => $user->job_title, // Column is 'job_title' not 'job_title_id'
                ];
            });

        return response()->json(['line_managers' => $lineManagers]);
    }

    /**
     * Get job titles by department (AJAX)
     */
    public function getJobTitlesByDepartment(Request $request)
    {
        $departmentId = $request->department_id;

        if (!$departmentId) {
            return response()->json(['job_titles' => []], 400);
        }

        $jobTitles = \App\Models\JobTitle::where('deptId', $departmentId)
            ->select('id', 'job_title')
            ->orderBy('job_title')
            ->get();

        return response()->json(['job_titles' => $jobTitles]);
    }

    /**
     * Download job description
     */
    public function downloadJobDescription($accessId)
    {
        $requisition = Requisition::where('access_id', $accessId)->firstOrFail();

        if (!$requisition->job_description_path) {
            return back()->with('error', 'No job description file found.');
        }

        return Storage::disk('public')->download($requisition->job_description_path);
    }

    // ==================== HELPER METHODS ====================

    /**
     * Determine next step after payroll review
     */
    private function determineNextStepAfterPayroll(Requisition $requisition): string
    {
        // If initiated by HEC Member:
        // - New Position     -> CFO → CEO → HR (even if budget exists)
        // - Budget approved  -> HR
        // - No budget        -> CFO → CEO → HR
        if ($requisition->isInitiatedByHEC()) {
            // Always route NEW positions through CFO/CEO
            if ($requisition->position_type === 'new_position') {
                return 'pending_cfo';
            }

            return $requisition->hasBudget() ? 'pending_hr' : 'pending_cfo';
        }

        // Line Manager initiated - always goes to HEC review after payroll
        return 'pending_hec';
    }

    /**
     * Determine next step after HEC review
     */
    private function determineNextStepAfterHEC(Requisition $requisition): string
    {
        $decision = $requisition->hec_financial_decision;

        // If HEC rejects or objects
        if (in_array($decision, ['reject', 'objection'])) {
            return 'filed'; // Goes to HR for filing only
        }

        // If initiated by Line Manager
        if ($requisition->isInitiatedByLineManager()) {
            // New option: send to CFO/CEO
            if ($decision === 'no_objection_cfo_ceo') {
                return 'pending_cfo';
            }
            // Default: send to HR
            return 'pending_hr';
        }

        // If initiated by HEC Member
        if ($requisition->isInitiatedByHEC()) {
            // For NEW positions initiated by HEC, always go via CFO/CEO even if budget exists
            if ($requisition->position_type === 'new_position') {
                return 'pending_cfo';
            }

            // If budget exists OR no financial implication OR proposed funding
            if ($requisition->hasBudget() || in_array($decision, ['no_financial_implication', 'proposed_funding', 'no_objection'])) {
                return 'pending_hr';
            }
            // No budget and no financial decision = CFO path
            return 'pending_cfo';
        }

        return 'pending_hr';
    }

    /**
     * Get step name from status
     */
    private function getStepFromStatus(string $status): string
    {
        return match ($status) {
            'pending_payroll' => 'payroll_review',
            'pending_hec' => 'hec_review',
            'pending_cfo' => 'cfo_review',
            'pending_ceo' => 'ceo_review',
            'pending_hr' => 'hr_review',
            'approved', 'rejected', 'filed' => 'completed',
            default => 'initiation',
        };
    }

    /**
     * Update workflow history
     */
    private function updateWorkflowHistory(Requisition $requisition, User $user, string $stepName, string $remark): void
    {
        if (!$requisition->workflow) return;

        WorkFlowHistory::where('work_flow_id', $requisition->workflow->id)
            ->where('step_name', $stepName)
            ->where('status', 0)
            ->update([
                'status' => 1,
                'decision_date' => now(),
                'remark' => $remark,
            ]);
    }

    /**
     * Create next step workflow history
     */
    private function createNextStepHistory(Requisition $requisition): ?User
    {
        try {
            if (!$requisition->workflow) {
                Log::error('Requisition has no workflow', [
                    'requisition_id' => $requisition->id,
                    'access_id' => $requisition->access_id
                ]);
                return null;
            }

            $nextUser = null;
            $stepName = '';
            $errorDetails = [];

            switch ($requisition->status) {
                case 'pending_hec':
                    // Find the specific HEC member assigned to the department
                    try {
                        $department = Departments::find($requisition->department_id);
                        
                        if (!$department) {
                            $errorDetails[] = 'Department not found: ' . $requisition->department_id;
                            Log::warning('Department not found for requisition', [
                                'requisition_id' => $requisition->id,
                                'department_id' => $requisition->department_id
                            ]);
                        } elseif ($department->hec_member_id) {
                            $nextUser = User::find($department->hec_member_id);
                            if (!$nextUser) {
                                $errorDetails[] = 'Assigned HEC member not found: ' . $department->hec_member_id;
                                Log::warning('Assigned HEC member not found', [
                                    'requisition_id' => $requisition->id,
                                    'department_id' => $requisition->department_id,
                                    'hec_member_id' => $department->hec_member_id
                                ]);
                            }
                        } else {
                            $errorDetails[] = 'No HEC member assigned to department';
                            Log::warning('No HEC member assigned to department', [
                                'requisition_id' => $requisition->id,
                                'department_id' => $requisition->department_id,
                                'department_name' => $department->dept_name ?? 'Unknown'
                            ]);
                        }

                        // Fallback: Find any HEC member if no specific assignment
                        if (!$nextUser) {
                            $nextUser = User::whereHas('roles', function ($q) {
                                $q->whereIn('name', ['coo', 'cms', 'cfo', 'ccdro']);
                            })->first();
                            
                            if ($nextUser) {
                                $errorDetails[] = 'Used fallback HEC member: ' . $nextUser->fname . ' ' . $nextUser->lname;
                                Log::info('Used fallback HEC member', [
                                    'requisition_id' => $requisition->id,
                                    'fallback_user_id' => $nextUser->id,
                                    'fallback_user_name' => $nextUser->fname . ' ' . $nextUser->lname
                                ]);
                            } else {
                                $errorDetails[] = 'No HEC members available in system';
                                Log::error('No HEC members available in system', [
                                    'requisition_id' => $requisition->id
                                ]);
                            }
                        }
                    } catch (\Exception $e) {
                        $errorDetails[] = 'Error finding HEC member: ' . $e->getMessage();
                        Log::error('Exception while finding HEC member', [
                            'requisition_id' => $requisition->id,
                            'error' => $e->getMessage(),
                            'trace' => $e->getTraceAsString()
                        ]);
                    }
                    $stepName = 'HEC Review';
                    break;

                case 'pending_cfo':
                    try {
                        $nextUser = User::role('cfo')->first();
                        if (!$nextUser) {
                            $errorDetails[] = 'No CFO found in system';
                            Log::error('No CFO found in system', [
                                'requisition_id' => $requisition->id
                            ]);
                        }
                    } catch (\Exception $e) {
                        $errorDetails[] = 'Error finding CFO: ' . $e->getMessage();
                        Log::error('Exception while finding CFO', [
                            'requisition_id' => $requisition->id,
                            'error' => $e->getMessage()
                        ]);
                    }
                    $stepName = 'CFO Review';
                    break;

                case 'pending_ceo':
                    try {
                        $nextUser = User::role('ceo')->first();
                        if (!$nextUser) {
                            $errorDetails[] = 'No CEO found in system';
                            Log::error('No CEO found in system', [
                                'requisition_id' => $requisition->id
                            ]);
                        }
                    } catch (\Exception $e) {
                        $errorDetails[] = 'Error finding CEO: ' . $e->getMessage();
                        Log::error('Exception while finding CEO', [
                            'requisition_id' => $requisition->id,
                            'error' => $e->getMessage()
                        ]);
                    }
                    $stepName = 'CEO Review';
                    break;

                case 'pending_hr':
                    try {
                        $nextUser = User::role('hr')->first();
                        if (!$nextUser) {
                            $errorDetails[] = 'No HR found in system';
                            Log::error('No HR found in system', [
                                'requisition_id' => $requisition->id
                            ]);
                        }
                    } catch (\Exception $e) {
                        $errorDetails[] = 'Error finding HR: ' . $e->getMessage();
                        Log::error('Exception while finding HR', [
                            'requisition_id' => $requisition->id,
                            'error' => $e->getMessage()
                        ]);
                    }
                    $stepName = 'HR Review';
                    break;

                default:
                    $errorDetails[] = 'Unknown requisition status: ' . $requisition->status;
                    Log::warning('Unknown requisition status in workflow', [
                        'requisition_id' => $requisition->id,
                        'status' => $requisition->status
                    ]);
                    break;
            }

            // Create workflow history if we have a valid user and step
            if ($nextUser && $stepName) {
                try {
                    WorkFlowHistory::create([
                        'work_flow_id' => $requisition->workflow->id,
                        'forwarded_by' => Auth::id(),
                        'attended_by' => $nextUser->id,
                        'step_name' => $stepName,
                        'remark' => 'Awaiting ' . $stepName . (count($errorDetails) > 0 ? ' (Note: ' . implode('; ', $errorDetails) . ')' : ''),
                        'requisition_status' => 1,
                        'status' => 0,
                    ]);
                    
                    Log::info('Workflow history created successfully', [
                        'requisition_id' => $requisition->id,
                        'next_user_id' => $nextUser->id,
                        'step_name' => $stepName
                    ]);
                } catch (\Exception $e) {
                    Log::error('Failed to create workflow history', [
                        'requisition_id' => $requisition->id,
                        'next_user_id' => $nextUser->id,
                        'step_name' => $stepName,
                        'error' => $e->getMessage()
                    ]);
                    
                    // Return null to indicate failure
                    return null;
                }
            } else {
                // Log the failure to find appropriate approver
                Log::error('Failed to find next approver for requisition', [
                    'requisition_id' => $requisition->id,
                    'status' => $requisition->status,
                    'step_name' => $stepName,
                    'next_user_found' => $nextUser ? true : false,
                    'error_details' => $errorDetails
                ]);
            }

            return $nextUser;

        } catch (\Exception $e) {
            Log::error('Critical error in createNextStepHistory', [
                'requisition_id' => $requisition->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return null;
        }
    }

    /**
     * Notify requisition initiator about a status change
     */
    private function notifyInitiatorStatus(Requisition $requisition, User $actor, string $status, ?string $reason = null, ?string $stage = null): void
    {
        try {
            // Validate inputs
            if (!$requisition) {
                Log::error('Invalid requisition provided to notifyInitiatorStatus');
                return;
            }

            if (!$requisition->user) {
                Log::warning('Requisition has no associated user', [
                    'requisition_id' => $requisition->id,
                    'access_id' => $requisition->access_id
                ]);
                return;
            }

            if (!$requisition->user->email) {
                Log::warning('Requisition initiator has no email address', [
                    'requisition_id' => $requisition->id,
                    'user_id' => $requisition->user->id,
                    'user_name' => ($requisition->user->fname ?? '') . ' ' . ($requisition->user->lname ?? '')
                ]);
                return;
            }

            if (!$actor) {
                Log::error('Invalid actor provided to notifyInitiatorStatus', [
                    'requisition_id' => $requisition->id
                ]);
                return;
            }

            // Validate email format
            if (!filter_var($requisition->user->email, FILTER_VALIDATE_EMAIL)) {
                Log::error('Invalid email format for requisition initiator', [
                    'requisition_id' => $requisition->id,
                    'user_id' => $requisition->user->id,
                    'email' => $requisition->user->email
                ]);
                return;
            }

            // Attempt to send email
            Mail::to($requisition->user->email)->queue(new RequisitionStatusUpdate(
                $requisition,
                $requisition->user,
                $status,
                $actor,
                $reason,
                $stage
            ));

            Log::info('Status update email sent successfully', [
                'requisition_id' => $requisition->id,
                'recipient_email' => $requisition->user->email,
                'status' => $status,
                'stage' => $stage,
                'actor_id' => $actor->id
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to send requisition status update email', [
                'requisition_id' => $requisition->id ?? 'unknown',
                'recipient_email' => $requisition->user->email ?? 'unknown',
                'status' => $status,
                'stage' => $stage,
                'actor_id' => $actor->id ?? 'unknown',
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * Get step name from status
     */
    private function getStepNameFromStatus(string $status): string
    {
        return match ($status) {
            'pending_payroll' => 'Payroll Review',
            'pending_hec' => 'HEC Review',
            'pending_cfo' => 'CFO Review',
            'pending_ceo' => 'CEO Review',
            'pending_hr' => 'HR Review',
            default => 'Review',
        };
    }

    /**
     * Edit rejected requisition (only within one month)
     */
    public function edit($accessId)
    {
        $requisition = Requisition::where('access_id', $accessId)->firstOrFail();
        $user = Auth::user();

        // Check if user is the initiator
        if ($requisition->user_id !== $user->id) {
            return redirect()->route('requisitions.show', $accessId)
                ->with('error', 'You can only edit your own requisitions.');
        }

        // Check if requisition can be edited
        if (!$requisition->canBeEdited()) {
            if ($requisition->isExpired()) {
                return redirect()->route('requisitions.show', $accessId)
                    ->with('error', 'This requisition has expired. The one-month editing period has passed.');
            }
            return redirect()->route('requisitions.show', $accessId)
                ->with('error', 'This requisition cannot be edited.');
        }

        // Get departments and job titles for the form
        $isHecMember = $user->hasAnyRole(['coo', 'cms', 'cfo', 'ccdro']);
        $isLineManager = $user->hasRole('line-manager');

        if ($isHecMember) {
            $departments = Departments::where('hec_member_id', $user->id)
                ->orWhereHas('hec', function ($q) use ($user) {
                    $q->whereRaw("LOWER(hec_level_name) = ?", [strtolower($user->getRoleNames()->first())]);
                })
                ->orderBy('dept_name')
                ->get();
            if ($departments->isEmpty()) {
                $departments = Departments::orderBy('dept_name')->get();
            }
        } else {
            $departments = collect([$user->department]);
        }

        $jobTitles = JobTitle::orderBy('job_title')->get();
        $lineManagers = User::role('line-manager')
            ->where('status', 'active')
            ->orderBy('fname')
            ->get();

        $userDepartmentId = null;
        if ($isLineManager) {
            $userDepartmentId = $user->deptId ?? $user->department?->id ?? $departments->first()?->id;
        }

        return view('requisitions.edit', compact(
            'requisition',
            'isHecMember',
            'isLineManager',
            'departments',
            'jobTitles',
            'lineManagers',
            'userDepartmentId'
        ));
    }

    /**
     * Update rejected requisition
     */
    public function update(Request $request, $accessId)
    {
        $requisition = Requisition::where('access_id', $accessId)->firstOrFail();
        $user = Auth::user();

        // Check if user is the initiator
        if ($requisition->user_id !== $user->id) {
            return back()->with('error', 'You can only edit your own requisitions.');
        }

        // Check if requisition can be edited
        if (!$requisition->canBeEdited()) {
            if ($requisition->isExpired()) {
                return back()->with('error', 'This requisition has expired. The one-month editing period has passed.');
            }
            return back()->with('error', 'This requisition cannot be edited.');
        }

        $isHecMember = $user->hasAnyRole(['coo', 'cms', 'cfo', 'ccdro']);

        // Validate (similar to store method)
        $rules = [
            'position_type' => 'required|in:new_position,replacement,contract_renewal',
            'department_id' => 'required|exists:departments,id',
            'required_start_date' => 'required|date|after:today',
            'job_description' => 'nullable|file|mimes:pdf|max:10240',
            'elaborate_reason' => 'required|string|min:50',
        ];

        if (in_array($request->position_type, ['replacement', 'contract_renewal'])) {
            if (!$isHecMember) {
                $rules['employee_id'] = 'required|array|min:1';
                $rules['employee_id.*'] = 'required|exists:users,id';
                $rules['current_contract_end_date'] = 'required|date';
                $rules['job_title_id'] = 'required|exists:job_titles,id';
            } else {
                $rules['current_contract_end_date'] = 'nullable|date';
                $rules['job_title_id'] = 'nullable|exists:job_titles,id';
            }
            $rules['contract_type'] = 'required|in:minimal_1_year,termed_less_1_year,health_volunteer,work_exposure';
        }

        if ($isHecMember) {
            $rules['line_manager_id'] = 'required|exists:users,id';
        }

        if ($request->position_type === 'new_position') {
            $rules['new_job_title'] = 'required|string|max:255';
            $rules['contract_type'] = 'required|in:minimal_1_year,termed_less_1_year,health_volunteer,work_exposure';
        }

        $request->validate($rules);

        // Check at least one condition
        $conditions = [
            $request->condition_medical_operational,
            $request->condition_safety_reputational,
            $request->condition_legal_requirement,
            $request->condition_financial_loss,
            $request->condition_increase_income,
        ];

        if (!in_array(true, array_map('boolval', $conditions))) {
            return back()->withErrors(['conditions' => 'At least one condition must be selected.'])->withInput();
        }

        DB::beginTransaction();
        try {
            // Upload job description if new one provided
            $jobDescPath = $requisition->job_description_path;
            if ($request->hasFile('job_description')) {
                // Delete old file if exists
                if ($jobDescPath) {
                    Storage::disk('public')->delete($jobDescPath);
                }
                $jobDescPath = $request->file('job_description')->store('requisitions/job-descriptions', 'public');
            }

            $department = Departments::find($request->department_id);

            // Build employee arrays from multi-select
            $employeeIds = [];
            $employeeNames = [];
            $firstEmployeeId = null;
            $firstEmployeeName = null;

            if ($request->employee_id) {
                $ids = is_array($request->employee_id) ? $request->employee_id : [$request->employee_id];
                foreach ($ids as $empId) {
                    $emp = User::find($empId);
                    if ($emp) {
                        $employeeIds[] = $emp->id;
                        $employeeNames[] = trim($emp->fname . ' ' . ($emp->mname ?? '') . ' ' . $emp->lname);
                    }
                }
                $firstEmployeeId = $employeeIds[0] ?? null;
                $firstEmployeeName = $employeeNames[0] ?? null;
            }

            // Update requisition data and automatically restart approval process
            $requisition->update([
                'position_type' => $request->position_type,
                'employee_id' => $firstEmployeeId,
                'employee_name' => $firstEmployeeName,
                'employee_ids' => !empty($employeeIds) ? $employeeIds : null,
                'employee_names' => !empty($employeeNames) ? $employeeNames : null,
                'current_contract_end_date' => $request->current_contract_end_date,
                'line_manager_id' => $isHecMember ? $request->line_manager_id : $user->id,
                'department_id' => $request->department_id,
                'dept_name' => $department->dept_name,
                'responsibility_centre' => $department->responsibility_centre ?? $request->responsibility_centre,
                'reporting_line' => $request->reporting_line,
                'job_title_id' => $request->job_title_id,
                'new_job_title' => $request->new_job_title,
                'contract_type' => $request->contract_type,
                'required_start_date' => $request->required_start_date,
                'job_description_path' => $jobDescPath,
                'condition_medical_operational' => $request->boolean('condition_medical_operational'),
                'condition_safety_reputational' => $request->boolean('condition_safety_reputational'),
                'condition_legal_requirement' => $request->boolean('condition_legal_requirement'),
                'condition_financial_loss' => $request->boolean('condition_financial_loss'),
                'condition_increase_income' => $request->boolean('condition_increase_income'),
                'elaborate_reason' => $request->elaborate_reason,
                // Clear rejection fields and restart approval process
                'rejection_reason' => null,
                'rejected_at' => null,
                'rejected_by' => null,
                'rejection_stage' => null,
                'can_edit_until' => null,
                // Reset status to start fresh approval process
                'status' => 'pending_payroll',
                'current_step' => 'payroll_review',
                // Clear all previous review data to start fresh
                'budget_approved' => null,
                'max_monthly_budget' => null,
                'funding_available' => null,
                'donor_code' => null,
                'activity_code' => null,
                'payroll_accountant_id' => null,
                'payroll_reviewed_at' => null,
                'payroll_comment' => null,
                'hec_financial_decision' => null,
                'proposed_funding_source' => null,
                'hec_comment' => null,
                'hec_reviewer_id' => null,
                'hec_reviewed_at' => null,
                'cfo_financing_confirmation' => null,
                'cfo_financing_code' => null,
                'cfo_comment' => null,
                'cfo_decision' => null,
                'cfo_id' => null,
                'cfo_reviewed_at' => null,
                'ceo_decision' => null,
                'ceo_comment' => null,
                'ceo_id' => null,
                'ceo_reviewed_at' => null,
                'hr_decision' => null,
                'hr_comment' => null,
                'hr_attachment_path' => null,
                'hr_id' => null,
                'hr_reviewed_at' => null,
                'hr_filed_only' => false,
            ]);

            // Reset workflow to start fresh
            $payrollAccountant = User::role('payroll_accountant')->first();
            if ($requisition->workflow) {
                // Mark all previous workflow histories as completed
                WorkFlowHistory::where('work_flow_id', $requisition->workflow->id)
                    ->where('status', 0)
                    ->update([
                        'status' => 1,
                        'decision_date' => now(),
                        'remark' => 'Requisition updated and resubmitted - previous workflow completed',
                    ]);

                // Reset workflow status
                $requisition->workflow->update([
                    'work_flow_status' => 0,
                    'work_flow_completed' => 0,
                ]);

                // Create new workflow history starting from Payroll Review
                WorkFlowHistory::create([
                    'work_flow_id' => $requisition->workflow->id,
                    'forwarded_by' => $user->id,
                    'attended_by' => $payrollAccountant?->id,
                    'step_name' => 'Payroll Review',
                    'remark' => 'Requisition updated and resubmitted - starting fresh approval process',
                    'requisition_status' => 1,
                    'status' => 0,
                ]);
            }

            DB::commit();

            // Send notification to payroll accountant
            if ($payrollAccountant && $payrollAccountant->email) {
                try {
                    Mail::to($payrollAccountant->email)->queue(new RequisitionApprovalRequest(
                        $requisition,
                        $payrollAccountant,
                        'Payroll Review',
                        $user
                    ));
                } catch (\Exception $e) {
                    Log::error('Failed to send requisition approval email to payroll accountant', [
                        'requisition_id' => $requisition->id,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            // Refresh the requisition to ensure we have the latest data
            $requisition->refresh();
            
            return redirect()->route('requisitions.show', $requisition->access_id)
                ->with('success', 'Requisition updated and resubmitted successfully. Status changed to "Pending Payroll Review". Awaiting Payroll Accountant review.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Failed to update requisition: ' . $e->getMessage())->withInput();
        }
    }

    /**
     * Resubmit edited requisition
     */
    public function resubmit($accessId)
    {
        $requisition = Requisition::where('access_id', $accessId)->firstOrFail();
        $user = Auth::user();

        // Check if user is the initiator
        if ($requisition->user_id !== $user->id) {
            return back()->with('error', 'You can only resubmit your own requisitions.');
        }

        // Check if requisition can be edited
        if (!$requisition->canBeEdited()) {
            if ($requisition->isExpired()) {
                return back()->with('error', 'This requisition has expired. The one-month editing period has passed.');
            }
            return back()->with('error', 'This requisition cannot be resubmitted.');
        }

        DB::beginTransaction();
        try {
            // Reset to initial status - clear all previous review data to start fresh
            $requisition->update([
                'status' => 'pending_payroll',
                'current_step' => 'payroll_review',
                // Clear rejection fields
                'rejection_reason' => null,
                'rejected_at' => null,
                'rejected_by' => null,
                'rejection_stage' => null,
                'can_edit_until' => null,
                // Clear all previous review data to start fresh approval process
                'budget_approved' => null,
                'max_monthly_budget' => null,
                'funding_available' => null,
                'donor_code' => null,
                'activity_code' => null,
                'payroll_accountant_id' => null,
                'payroll_reviewed_at' => null,
                'payroll_comment' => null,
                'hec_financial_decision' => null,
                'proposed_funding_source' => null,
                'hec_comment' => null,
                'hec_reviewer_id' => null,
                'hec_reviewed_at' => null,
                'cfo_financing_confirmation' => null,
                'cfo_financing_code' => null,
                'cfo_comment' => null,
                'cfo_decision' => null,
                'cfo_id' => null,
                'cfo_reviewed_at' => null,
                'ceo_decision' => null,
                'ceo_comment' => null,
                'ceo_id' => null,
                'ceo_reviewed_at' => null,
                'hr_decision' => null,
                'hr_comment' => null,
                'hr_attachment_path' => null,
                'hr_id' => null,
                'hr_reviewed_at' => null,
                'hr_filed_only' => false,
            ]);

            // Reset workflow to start fresh
            $payrollAccountant = User::role('payroll_accountant')->first();
            if ($requisition->workflow) {
                // Mark all previous workflow histories as completed
                WorkFlowHistory::where('work_flow_id', $requisition->workflow->id)
                    ->where('status', 0)
                    ->update([
                        'status' => 1,
                        'decision_date' => now(),
                        'remark' => 'Requisition resubmitted - previous workflow completed',
                    ]);

                // Reset workflow status
                $requisition->workflow->update([
                    'work_flow_status' => 0,
                    'work_flow_completed' => 0,
                ]);

                // Create new workflow history starting from Payroll Review
                WorkFlowHistory::create([
                    'work_flow_id' => $requisition->workflow->id,
                    'forwarded_by' => $user->id,
                    'attended_by' => $payrollAccountant?->id,
                    'step_name' => 'Payroll Review',
                    'remark' => 'Requisition resubmitted after editing - starting fresh approval process',
                    'requisition_status' => 1,
                    'status' => 0,
                ]);
            }

            DB::commit();

            // Send notification to payroll accountant
            if ($payrollAccountant && $payrollAccountant->email) {
                try {
                    Mail::to($payrollAccountant->email)->queue(new RequisitionApprovalRequest(
                        $requisition,
                        $payrollAccountant,
                        'Payroll Review',
                        $user
                    ));
                } catch (\Exception $e) {
                    Log::error('Failed to send requisition approval email to payroll accountant', [
                        'requisition_id' => $requisition->id,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            return redirect()->route('requisitions.show', $requisition->access_id)
                ->with('success', 'Requisition resubmitted successfully. Awaiting Payroll Accountant review.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Failed to resubmit requisition: ' . $e->getMessage());
        }
    }

    /**
     * Export requisitions to Excel
     */
    public function exportExcel(Request $request)
    {
        $user = Auth::user();

        if (!$user->hasAnyRole(['line-manager', 'coo', 'cms', 'ccdro', 'hr', 'cfo', 'ceo', 'payroll_accountant'])
            && !$user->can('view_all_rrf')) {
            abort(403);
        }

        if (!\Schema::hasTable('requisitions')) {
            abort(404, 'Requisitions table not found.');
        }

        // ── Filters ───────────────────────────────────────────────────────────
        $dateFrom  = $request->input('date_from');
        $dateTo    = $request->input('date_to');
        $statusF   = $request->input('export_status', 'all');
        $entityF   = $request->input('entity', '');
        $departmentF = $request->input('department', '');
        $posTypeF  = $request->input('position_type', '');

        // ── Role-based base query (matches index page visibility) ──────────
        $query = Requisition::with(['department.divisions', 'jobTitle'])
            ->orderBy('created_at', 'desc');

        if ($user->hasRole('hr') || $user->can('view_all_rrf')) {
            // HR or view_all_rrf: see all
        } elseif ($user->hasAnyRole(['coo', 'cms', 'ccdro', 'cfo', 'ceo'])) {
            // HEC members, CFO, CEO: see all
        } elseif ($user->hasRole('payroll_accountant')) {
            $query->where(function ($q) use ($user) {
                $q->where('user_id', $user->id)
                    ->orWhere('status', 'pending_payroll')
                    ->orWhere('payroll_accountant_id', $user->id);
            });
        } else {
            // Line managers / default: own requisitions only
            $query->where('user_id', $user->id);
        }

        // Apply column-level filters
        if ($dateFrom) $query->whereDate('created_at', '>=', $dateFrom);
        if ($dateTo)   $query->whereDate('created_at', '<=', $dateTo);
        if ($posTypeF) $query->where('position_type', $posTypeF);

        if ($statusF !== 'all') {
            $statusMap = [
                'draft'    => ['draft'],
                'pending'  => ['pending_payroll', 'pending_hec', 'pending_cfo', 'pending_ceo', 'pending_hr'],
                'approved' => ['approved'],
                'rejected' => ['rejected', 'rejected_for_editing'],
                'filed'    => ['filed'],
            ];
            if (isset($statusMap[$statusF])) {
                $query->whereIn('status', $statusMap[$statusF]);
            } else {
                $query->where('status', $statusF);
            }
        }

        $requisitions = $query->get();

        // Filter by entity (post-load — entity comes from dept→division relationship)
        if ($entityF) {
            $requisitions = $requisitions->filter(
                fn ($req) => ($req->department?->divisions?->first()?->name ?? '') === $entityF
            );
        }
        if ($departmentF) {
            $requisitions = $requisitions->filter(
                fn ($req) => ($req->dept_name ?: 'Unassigned') === $departmentF
            );
        }
        $requisitions = $requisitions->values();

        // ── Helper closures ───────────────────────────────────────────────────
        $getJobTitle = function ($req) {
            if ($req->position_type === 'new_position' && $req->new_job_title) {
                return $req->new_job_title . ' (New Position)';
            }
            if ($req->jobTitle) {
                return $req->jobTitle->job_title ?? $req->jobTitle->name ?? 'N/A';
            }
            return 'N/A';
        };

        $getEntity = fn ($req) => $req->department?->divisions?->first()?->name ?? 'Unassigned';

        // ── Aggregate stats ───────────────────────────────────────────────────
        $total     = $requisitions->count();
        $byStatus  = $requisitions->groupBy('status')->map->count();
        $byPosType = $requisitions->groupBy(fn ($r) => $r->getPositionTypeLabel())->map->count()->sortDesc();
        $byEntity  = $requisitions->groupBy(fn ($r) => $getEntity($r))->map->count()->sortDesc();
        $byDept    = $requisitions->groupBy(fn ($r) => $r->dept_name ?: 'Unassigned')->map->count()->sortDesc();
        $byDeptType = $requisitions
            ->groupBy(fn ($r) => $r->dept_name ?: 'Unassigned')
            ->map(function ($departmentRequisitions) {
                return [
                    'total' => $departmentRequisitions->count(),
                    'new_position' => $departmentRequisitions->where('position_type', 'new_position')->count(),
                    'contract_renewal' => $departmentRequisitions->where('position_type', 'contract_renewal')->count(),
                    'replacement' => $departmentRequisitions->where('position_type', 'replacement')->count(),
                ];
            })
            ->sortByDesc('total');

        // ── Build spreadsheet ─────────────────────────────────────────────────
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $spreadsheet->getProperties()
            ->setCreator('CCBRT System')
            ->setTitle('Recruitment Requisitions Report')
            ->setSubject('CCBRT Recruitment Requisitions Report');

        $GRN    = '007A33';
        $GRAY   = 'F5F5F5';
        $DGRAY  = '555555';
        $BORDER = \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN;
        $CENTER = \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER;
        $LEFT   = \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT;
        $MIDDLE = \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER;
        $SOLID  = \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID;

        $applyFont = function ($sheet, $range, $bold = false, $rgb = '000000', $size = 10) {
            $f = $sheet->getStyle($range)->getFont();
            $f->setBold($bold)->setSize($size);
            $f->getColor()->setRGB($rgb);
        };
        $align = function ($sheet, $range, $h, $v = null) use ($MIDDLE) {
            $a = $sheet->getStyle($range)->getAlignment()->setHorizontal($h);
            if ($v) $a->setVertical($v);
        };

        // Filter info string
        $filterParts = [];
        if ($dateFrom || $dateTo) $filterParts[] = 'Period: ' . ($dateFrom ?: '—') . ' → ' . ($dateTo ?: '—');
        if ($posTypeF) {
            $posLabels = [
                'new_position'     => 'New Position',
                'replacement'      => 'Replacement',
                'contract_renewal' => 'Contract Renewal/Extension',
            ];
            $filterParts[] = 'Type: ' . ($posLabels[$posTypeF] ?? $posTypeF);
        }
        if ($entityF) $filterParts[] = 'Entity: ' . $entityF;
        if ($departmentF) $filterParts[] = 'Department: ' . $departmentF;
        if ($statusF !== 'all') {
            $statusFilterLabels = [
                'draft' => 'Draft',
                'pending' => 'All Pending',
                'approved' => 'Approved',
                'rejected' => 'Rejected',
                'filed' => 'Filed',
            ];
            $filterParts[] = 'Status: ' . ($statusFilterLabels[$statusF] ?? ucfirst($statusF));
        }
        $filterParts[] = 'Generated: ' . now()->format('d M Y H:i');

        // ═══════════════════════════════════════════════
        //  SHEET 1 — Summary
        // ═══════════════════════════════════════════════
        $s1 = $spreadsheet->getActiveSheet()->setTitle('Summary');

        $s1->mergeCells('A1:C1');
        $s1->setCellValue('A1', 'CCBRT Recruitment Requisitions Report');
        $s1->getStyle('A1:C1')->applyFromArray([
            'font'      => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 15],
            'fill'      => ['fillType' => $SOLID, 'startColor' => ['rgb' => $GRN]],
            'alignment' => ['horizontal' => $CENTER, 'vertical' => $MIDDLE],
        ]);
        $s1->getRowDimension(1)->setRowHeight(38);

        $s1->mergeCells('A2:C2');
        $s1->setCellValue('A2', implode('   |   ', $filterParts));
        $s1->getStyle('A2:C2')->applyFromArray([
            'font'      => ['size' => 9, 'color' => ['rgb' => $DGRAY]],
            'fill'      => ['fillType' => $SOLID, 'startColor' => ['rgb' => $GRAY]],
            'alignment' => ['horizontal' => $CENTER],
        ]);
        $s1->getRowDimension(2)->setRowHeight(18);
        $s1->getRowDimension(3)->setRowHeight(8);

        // ─ Status breakdown ─
        $r = 4;
        $s1->mergeCells("A{$r}:C{$r}");
        $s1->setCellValue("A{$r}", 'OVERVIEW BY STATUS');
        $applyFont($s1, "A{$r}", true, $GRN, 10);
        $s1->getRowDimension($r)->setRowHeight(18);
        $r++;

        foreach (['Status', 'Count', '% of Total'] as $ci => $h) {
            $col = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($ci + 1);
            $s1->setCellValue($col . $r, $h);
        }
        $s1->getStyle("A{$r}:C{$r}")->applyFromArray([
            'font'      => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 10],
            'fill'      => ['fillType' => $SOLID, 'startColor' => ['rgb' => $GRN]],
            'alignment' => ['horizontal' => $CENTER],
            'borders'   => ['allBorders' => ['borderStyle' => $BORDER]],
        ]);
        $s1->getRowDimension($r)->setRowHeight(20);
        $r++;

        $statusLabels = [
            'draft'                => 'Draft',
            'pending_payroll'      => 'Pending Payroll Review',
            'pending_hec'          => 'Pending HEC Review',
            'pending_cfo'          => 'Pending CFO Approval',
            'pending_ceo'          => 'Pending CEO Approval',
            'pending_hr'           => 'Pending HR Approval',
            'approved'             => 'Approved',
            'rejected'             => 'Rejected',
            'rejected_for_editing' => 'Rejected - Can Edit',
            'filed'                => 'Filed',
        ];

        $statusDataStart = $r;
        foreach ($statusLabels as $key => $label) {
            $count = $byStatus[$key] ?? 0;
            if ($count === 0) continue;
            $pct = $total > 0 ? round($count / $total * 100, 1) . '%' : '0%';
            $s1->fromArray([$label, $count, $pct], null, 'A' . $r);
            $r++;
        }
        if ($r > $statusDataStart) {
            $s1->getStyle("A{$statusDataStart}:C" . ($r - 1))->applyFromArray([
                'fill'      => ['fillType' => $SOLID, 'startColor' => ['rgb' => 'FFFFFF']],
                'borders'   => ['allBorders' => ['borderStyle' => $BORDER, 'color' => ['rgb' => 'DDDDDD']]],
                'alignment' => ['horizontal' => $CENTER],
            ]);
            $s1->getStyle("A{$statusDataStart}:A" . ($r - 1))->getAlignment()->setHorizontal($LEFT);
        }
        $s1->fromArray(['TOTAL', $total, '100%'], null, 'A' . $r);
        $s1->getStyle("A{$r}:C{$r}")->applyFromArray([
            'font'      => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill'      => ['fillType' => $SOLID, 'startColor' => ['rgb' => $GRN]],
            'alignment' => ['horizontal' => $CENTER],
            'borders'   => ['allBorders' => ['borderStyle' => $BORDER]],
        ]);
        $s1->getStyle('A' . $r)->getAlignment()->setHorizontal($LEFT);
        $r += 2;

        // ─ Position Type breakdown ─
        $s1->mergeCells("A{$r}:C{$r}");
        $s1->setCellValue("A{$r}", 'BREAKDOWN BY POSITION TYPE');
        $applyFont($s1, "A{$r}", true, $GRN, 10);
        $s1->getRowDimension($r)->setRowHeight(18);
        $r++;

        foreach (['Position Type', 'Count', '% of Total'] as $ci => $h) {
            $col = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($ci + 1);
            $s1->setCellValue($col . $r, $h);
        }
        $s1->getStyle("A{$r}:C{$r}")->applyFromArray([
            'font'      => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 10],
            'fill'      => ['fillType' => $SOLID, 'startColor' => ['rgb' => $GRN]],
            'alignment' => ['horizontal' => $CENTER],
            'borders'   => ['allBorders' => ['borderStyle' => $BORDER]],
        ]);
        $s1->getRowDimension($r)->setRowHeight(20);
        $r++;

        $ptDataStart = $r;
        foreach ($byPosType as $label => $count) {
            $pct = $total > 0 ? round($count / $total * 100, 1) . '%' : '0%';
            $s1->fromArray([$label, $count, $pct], null, 'A' . $r);
            $r++;
        }
        if ($r > $ptDataStart) {
            $s1->getStyle("A{$ptDataStart}:C" . ($r - 1))->applyFromArray([
                'fill'      => ['fillType' => $SOLID, 'startColor' => ['rgb' => 'FFFFFF']],
                'borders'   => ['allBorders' => ['borderStyle' => $BORDER, 'color' => ['rgb' => 'DDDDDD']]],
                'alignment' => ['horizontal' => $CENTER],
            ]);
            $s1->getStyle("A{$ptDataStart}:A" . ($r - 1))->getAlignment()->setHorizontal($LEFT);
        }
        $r += 2;

        // ─ Entity breakdown ─
        $s1->mergeCells("A{$r}:C{$r}");
        $s1->setCellValue("A{$r}", 'BREAKDOWN BY ENTITY');
        $applyFont($s1, "A{$r}", true, $GRN, 10);
        $s1->getRowDimension($r)->setRowHeight(18);
        $r++;

        foreach (['Entity', 'Count', '% of Total'] as $ci => $h) {
            $col = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($ci + 1);
            $s1->setCellValue($col . $r, $h);
        }
        $s1->getStyle("A{$r}:C{$r}")->applyFromArray([
            'font'      => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 10],
            'fill'      => ['fillType' => $SOLID, 'startColor' => ['rgb' => $GRN]],
            'alignment' => ['horizontal' => $CENTER],
            'borders'   => ['allBorders' => ['borderStyle' => $BORDER]],
        ]);
        $s1->getRowDimension($r)->setRowHeight(20);
        $r++;

        $entDataStart = $r;
        foreach ($byEntity as $entityName => $count) {
            $pct = $total > 0 ? round($count / $total * 100, 1) . '%' : '0%';
            $s1->fromArray([$entityName, $count, $pct], null, 'A' . $r);
            $r++;
        }
        if ($r > $entDataStart) {
            $s1->getStyle("A{$entDataStart}:C" . ($r - 1))->applyFromArray([
                'fill'      => ['fillType' => $SOLID, 'startColor' => ['rgb' => 'FFFFFF']],
                'borders'   => ['allBorders' => ['borderStyle' => $BORDER, 'color' => ['rgb' => 'DDDDDD']]],
                'alignment' => ['horizontal' => $CENTER],
            ]);
            $s1->getStyle("A{$entDataStart}:A" . ($r - 1))->getAlignment()->setHorizontal($LEFT);
        }
        $r += 2;

        // ─ Department breakdown ─
        $s1->mergeCells("A{$r}:C{$r}");
        $s1->setCellValue("A{$r}", 'BREAKDOWN BY DEPARTMENT (Top Requesting)');
        $applyFont($s1, "A{$r}", true, $GRN, 10);
        $s1->getRowDimension($r)->setRowHeight(18);
        $r++;

        foreach (['Department', 'Count', '% of Total'] as $ci => $h) {
            $col = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($ci + 1);
            $s1->setCellValue($col . $r, $h);
        }
        $s1->getStyle("A{$r}:C{$r}")->applyFromArray([
            'font'      => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 10],
            'fill'      => ['fillType' => $SOLID, 'startColor' => ['rgb' => $GRN]],
            'alignment' => ['horizontal' => $CENTER],
            'borders'   => ['allBorders' => ['borderStyle' => $BORDER]],
        ]);
        $s1->getRowDimension($r)->setRowHeight(20);
        $r++;

        $deptDataStart = $r;
        foreach ($byDept as $deptName => $count) {
            $pct = $total > 0 ? round($count / $total * 100, 1) . '%' : '0%';
            $s1->fromArray([$deptName, $count, $pct], null, 'A' . $r);
            $r++;
        }
        if ($r > $deptDataStart) {
            $s1->getStyle("A{$deptDataStart}:C" . ($r - 1))->applyFromArray([
                'fill'      => ['fillType' => $SOLID, 'startColor' => ['rgb' => 'FFFFFF']],
                'borders'   => ['allBorders' => ['borderStyle' => $BORDER, 'color' => ['rgb' => 'DDDDDD']]],
                'alignment' => ['horizontal' => $CENTER],
            ]);
            $s1->getStyle("A{$deptDataStart}:A" . ($r - 1))->getAlignment()->setHorizontal($LEFT);
        }

        foreach (['A' => 38, 'B' => 10, 'C' => 12] as $col => $w) {
            $s1->getColumnDimension($col)->setWidth($w);
        }
        $s1->freezePane('A3');

        // ═══════════════════════════════════════════════
        //  SHEET 2 — Detailed Records
        // ═══════════════════════════════════════════════
        $s2 = $spreadsheet->createSheet()->setTitle('Detailed Records');

        $s2->mergeCells('A1:N1');
        $s2->setCellValue('A1', 'CCBRT Recruitment Requisitions — Detailed Records');
        $s2->getStyle('A1:N1')->applyFromArray([
            'font'      => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 13],
            'fill'      => ['fillType' => $SOLID, 'startColor' => ['rgb' => $GRN]],
            'alignment' => ['horizontal' => $CENTER, 'vertical' => $MIDDLE],
        ]);
        $s2->getRowDimension(1)->setRowHeight(30);

        $s2->mergeCells('A2:N2');
        $s2->setCellValue('A2', implode('   |   ', $filterParts));
        $s2->getStyle('A2:N2')->applyFromArray([
            'font'      => ['size' => 9, 'color' => ['rgb' => $DGRAY]],
            'fill'      => ['fillType' => $SOLID, 'startColor' => ['rgb' => $GRAY]],
            'alignment' => ['horizontal' => $CENTER],
        ]);
        $s2->getRowDimension(2)->setRowHeight(16);

        $cols2 = [
            '#',
            'Reference',
            'Position Type',
            'Job Title / Position',
            'Contract Type',
            'Entity',
            'Department',
            'Initiated By',
            'Status',
            'Current Step',
            'Budget Approved',
            'Funding Available',
            'Required Start Date',
            'Submitted Date',
        ];
        foreach ($cols2 as $ci => $h) {
            $col = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($ci + 1);
            $s2->setCellValue($col . '3', $h);
        }
        $s2->getStyle('A3:N3')->applyFromArray([
            'font'      => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 10],
            'fill'      => ['fillType' => $SOLID, 'startColor' => ['rgb' => $GRN]],
            'alignment' => ['horizontal' => $CENTER],
            'borders'   => ['allBorders' => ['borderStyle' => $BORDER]],
        ]);
        $s2->getRowDimension(3)->setRowHeight(20);

        $dr = 4;
        foreach ($requisitions as $i => $req) {
            $s2->fromArray([
                $i + 1,
                $req->access_id,
                $req->getPositionTypeLabel(),
                $getJobTitle($req),
                $req->getContractTypeLabel(),
                $getEntity($req),
                $req->dept_name ?? 'Unassigned',
                $req->initiator_name ?? 'N/A',
                $req->getStatusLabel(),
                $req->current_step ? ucwords(str_replace('_', ' ', $req->current_step)) : '—',
                $req->budget_approved ? 'Yes' : 'No',
                $req->funding_available ? 'Yes' : 'No',
                $req->required_start_date ? $req->required_start_date->format('d M Y') : '—',
                $req->created_at ? $req->created_at->format('d M Y H:i') : '—',
            ], null, 'A' . $dr);
            $dr++;
        }

        $lastDr = $dr - 1;
        if ($lastDr >= 4) {
            $s2->getStyle("A4:N{$lastDr}")->applyFromArray([
                'fill'      => ['fillType' => $SOLID, 'startColor' => ['rgb' => 'FFFFFF']],
                'borders'   => ['allBorders' => ['borderStyle' => $BORDER, 'color' => ['rgb' => 'DDDDDD']]],
                'alignment' => ['horizontal' => $CENTER, 'vertical' => $MIDDLE],
            ]);
            $s2->getStyle("B4:J{$lastDr}")->getAlignment()->setHorizontal($LEFT);
            $s2->getStyle("A4:N{$lastDr}")->getAlignment()->setWrapText(true);
        }

        foreach (['A' => 5, 'B' => 16, 'C' => 24, 'D' => 34, 'E' => 28, 'F' => 22, 'G' => 26, 'H' => 24, 'I' => 28, 'J' => 22, 'K' => 16, 'L' => 17, 'M' => 18, 'N' => 18] as $col => $w) {
            $s2->getColumnDimension($col)->setWidth($w);
        }
        $s2->freezePane('A4');
        $s2->setAutoFilter('A3:N3');

        // ═══════════════════════════════════════════════
        //  SHEET 3 — Department Breakdown
        // ═══════════════════════════════════════════════
        $s3 = $spreadsheet->createSheet()->setTitle('Department Breakdown');

        $s3->mergeCells('A1:F1');
        $s3->setCellValue('A1', 'Department Breakdown by Position Type');
        $s3->getStyle('A1:F1')->applyFromArray([
            'font'      => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 13],
            'fill'      => ['fillType' => $SOLID, 'startColor' => ['rgb' => $GRN]],
            'alignment' => ['horizontal' => $CENTER, 'vertical' => $MIDDLE],
        ]);
        $s3->getRowDimension(1)->setRowHeight(30);

        $s3->mergeCells('A2:F2');
        $s3->setCellValue('A2', implode('   |   ', $filterParts));
        $s3->getStyle('A2:F2')->applyFromArray([
            'font'      => ['size' => 9, 'color' => ['rgb' => $DGRAY]],
            'fill'      => ['fillType' => $SOLID, 'startColor' => ['rgb' => $GRAY]],
            'alignment' => ['horizontal' => $CENTER],
        ]);

        $cols3 = ['Department', 'Total', 'New Position', 'Contract Renewal/Extension', 'Replacement', '% of Total'];
        foreach ($cols3 as $ci => $h) {
            $col = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($ci + 1);
            $s3->setCellValue($col . '4', $h);
        }
        $s3->getStyle('A4:F4')->applyFromArray([
            'font'      => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 10],
            'fill'      => ['fillType' => $SOLID, 'startColor' => ['rgb' => $GRN]],
            'alignment' => ['horizontal' => $CENTER],
            'borders'   => ['allBorders' => ['borderStyle' => $BORDER]],
        ]);

        $deptRow = 5;
        foreach ($byDeptType as $deptName => $counts) {
            $pct = $total > 0 ? round(($counts['total'] ?? 0) / $total * 100, 1) . '%' : '0%';
            $s3->fromArray([
                $deptName,
                $counts['total'] ?? 0,
                $counts['new_position'] ?? 0,
                $counts['contract_renewal'] ?? 0,
                $counts['replacement'] ?? 0,
                $pct,
            ], null, 'A' . $deptRow);
            $deptRow++;
        }

        $lastDeptRow = $deptRow - 1;
        if ($lastDeptRow >= 5) {
            $s3->getStyle("A5:F{$lastDeptRow}")->applyFromArray([
                'fill'      => ['fillType' => $SOLID, 'startColor' => ['rgb' => 'FFFFFF']],
                'borders'   => ['allBorders' => ['borderStyle' => $BORDER, 'color' => ['rgb' => 'DDDDDD']]],
                'alignment' => ['horizontal' => $CENTER, 'vertical' => $MIDDLE],
            ]);
            $s3->getStyle("A5:A{$lastDeptRow}")->getAlignment()->setHorizontal($LEFT);
        }

        foreach (['A' => 34, 'B' => 10, 'C' => 16, 'D' => 28, 'E' => 14, 'F' => 12] as $col => $w) {
            $s3->getColumnDimension($col)->setWidth($w);
        }
        $s3->freezePane('A5');
        $s3->setAutoFilter('A4:F4');

        // ── Output ────────────────────────────────────────────────────────────
        $spreadsheet->setActiveSheetIndex(0);
        $filename = 'CCBRT_Requisitions_' . now()->format('Ymd_His') . '.xlsx';

        while (ob_get_level()) ob_end_clean();
        $token = $request->input('download_token', '');
        if ($token) {
            setcookie('download_token', $token, time() + 60, '/');
        }
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: max-age=0');
        (new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet))->save('php://output');
        exit;
    }
}
