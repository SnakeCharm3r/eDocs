<?php

namespace App\Http\Controllers;

use App\Models\User;

use App\Models\Policy;
use Illuminate\Http\Request;
use App\Models\HealthDetails;
use App\Models\LanguageKnowledge;
use App\Models\Announcement;
use App\Models\JobTitle;
use Carbon\Carbon;
use DB;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    //
    public function index()
    {
        $user = Auth::user()->load('jobTitle');
        
        // Logic to fetch and pass data based on roles and permissions
        $policies = Policy::all();
        $announcements = Announcement::with('user')->latest()->take(5)->get();
        $totalUsers = \App\Models\User::count();
        $healthDetails = HealthDetails::join('users', 'health_details.userId', '=', 'users.id')
            ->where('health_details.userId', Auth::user()->id)
            ->select('health_details.*', 'users.*')
            ->get();

        $languageData = LanguageKnowledge::join('users', 'language_knowledge.userId', '=', 'users.id')
            ->where('language_knowledge.userId', Auth::user()->id)
            ->select('language_knowledge.*', 'users.*')
            ->get();

        // Calculate dashboard statistics
        $stats = $this->calculateDashboardStats($user);

        // Check license status for clinical users
        $licenseStatus = null;
        $isClinicalDepartment = $user->jobTitle && $user->jobTitle->clinical_or_non_clinical === 'Clinical';

        if ($isClinicalDepartment) {
            // Get HR workflow for this user
            $hrWorkflow = \App\Models\Workflow::where('hr_form', $user->id)->orderBy('id', 'desc')->first();

            if ($hrWorkflow) {
                // Get the most recent HR workflow history with license info
                $hrWorkflowHistory = \App\Models\WorkFlowHistory::where('work_flow_id', $hrWorkflow->id)
                    ->whereNotNull('license_valid_until')
                    ->orderBy('id', 'desc')
                    ->first();

                if ($hrWorkflowHistory && $hrWorkflowHistory->license_valid_until) {
                    $expiryDate = Carbon::parse($hrWorkflowHistory->license_valid_until)->startOfDay();
                    $today = Carbon::now()->startOfDay();
                    $daysUntilExpiry = $expiryDate->diffInDays($today, false);

                    // License is expired only if expiry date is before today (not including today)
                    $isExpired = $expiryDate->lt($today);

                    // Expiring soon: not expired and expires within 20 days (daysUntilExpiry is negative for future dates)
                    $expiringSoon = !$isExpired && $daysUntilExpiry <= 0 && $daysUntilExpiry >= -20;

                    $licenseStatus = [
                        'has_license' => true,
                        'is_expired' => $isExpired,
                        'days_until_expiry' => $daysUntilExpiry,
                        'expiry_date' => $hrWorkflowHistory->license_valid_until,
                        'expiring_soon' => $expiringSoon,
                    ];
                } else {
                    // No license found in workflow history
                    $licenseStatus = [
                        'has_license' => false,
                        'is_expired' => false,
                        'days_until_expiry' => null,
                        'expiry_date' => null,
                        'expiring_soon' => false,
                    ];
                }
            } else {
                // No HR workflow found
                $licenseStatus = [
                    'has_license' => false,
                    'is_expired' => false,
                    'days_until_expiry' => null,
                    'expiry_date' => null,
                    'expiring_soon' => false,
                ];
            }
        }

        $data = [];

        if ($user->hasRole('requester')) {
            $data['requester_content'] = 'Content for requesters';
        }

        if ($user->hasRole('head of hr')) {
            $data['hr_content'] = 'Content for HR heads';
        }

        if ($user->hasRole('head of it')) {
            $data['it_content'] = 'Content for IT heads';
        }

        if ($user->hasRole('head of department')) {
            $data['hod_content'] = 'Content for heads of department';
        }

        if ($user->hasRole('acting hod')) {
            $data['acting_hod_content'] = 'Content for acting heads of department';
        }

        if ($user->hasRole('super admin')) {
            $data['admin_content'] = 'Content for super admin';
        }

        // Get pending recruitment requisitions for HR
        $pendingRecruitmentRequisitions = collect();

        if ($user->hasRole('hr')) {
            // Check if requisitions table exists before querying
            if (\Schema::hasTable('requisitions')) {
                $pendingRecruitmentRequisitions = \App\Models\Requisition::with(['department', 'user', 'hecReviewer', 'cfo', 'ceo', 'hr'])
                    ->where('status', 'pending_hr')
                    ->orderBy('created_at', 'desc')
                    ->get();
            }
        }

        // Role-specific data
        $roleData = $this->getRoleSpecificData($user);

        // Locum and On-Call Summary for all users
        $locumOncallSummary = $this->getLocumOncallSummary($user);

        return view('dashboard', compact('data', 'policies', 'user', 'healthDetails', 'languageData', 'totalUsers', 'announcements', 'licenseStatus', 'isClinicalDepartment', 'pendingRecruitmentRequisitions', 'stats', 'roleData', 'locumOncallSummary'));
    }

    /**
     * Calculate dashboard statistics
     */
    private function calculateDashboardStats($user)
    {
        $stats = [
            'pending_approvals' => 0,
            'my_requests' => 0,
            'locum_claims' => 0,
            'oncall_claims' => 0,
            'recruitment_requisitions' => 0,
        ];

        // Pending Approvals (for approvers)
        if ($user->can('approve requests')) {
            // General requests
            if (!$user->hasFinanceOfficerRole()) {
                $stats['pending_approvals'] += DB::table('work_flow_histories')
                    ->join('workflows', 'work_flow_histories.work_flow_id', '=', 'workflows.id')
                    ->where('work_flow_histories.attended_by', $user->id)
                    ->where('work_flow_histories.status', 0)
                    ->whereNull('locum_agreement_status')
                    ->whereNull('locum_request_status')
                    ->whereNull('on_call_request_status')
                    ->count();
            }

            // Clearance forms
            if ($user->can('access clearance form')) {
                $stats['pending_approvals'] += DB::table('clearance_work_flow_histories')
                    ->join('clearance_work_flows', 'clearance_work_flow_histories.work_flow_id', '=', 'clearance_work_flows.id')
                    ->join('clearance_forms', 'clearance_forms.id', '=', 'clearance_work_flows.requested_resource_id')
                    ->where('clearance_work_flow_histories.attended_by', $user->id)
                    ->where('clearance_work_flow_histories.status', 0)
                    ->where('clearance_forms.status', '!=', 'rejected')
                    ->count();
            }

            // Locum requests
            if ($user->can('approve locum requests')) {
                $stats['pending_approvals'] += DB::table('work_flow_histories')
                    ->join('workflows', 'work_flow_histories.work_flow_id', '=', 'workflows.id')
                    ->where('work_flow_histories.attended_by', $user->id)
                    ->where('work_flow_histories.status', 0)
                    ->whereNotNull('workflows.locum_request_id')
                    ->count();
            }

            // On-call requests
            if ($user->can('approve oncall requests')) {
                $stats['pending_approvals'] += DB::table('work_flow_histories')
                    ->join('workflows', 'work_flow_histories.work_flow_id', '=', 'workflows.id')
                    ->where('work_flow_histories.attended_by', $user->id)
                    ->where('work_flow_histories.status', 0)
                    ->whereNotNull('workflows.on_call_request_id')
                    ->count();
            }

            // Recruitment requisitions
            if ($user->hasAnyRole(['line-manager', 'payroll_accountant', 'coo', 'cms', 'cfo', 'ccdro', 'ceo', 'hr'])) {
                if (\Schema::hasTable('requisitions')) {
                    $stats['pending_approvals'] += \App\Models\Requisition::where(function ($q) use ($user) {
                        if ($user->hasRole('payroll_accountant')) {
                            $q->where('status', 'pending_payroll');
                        } elseif ($user->hasAnyRole(['coo', 'cms', 'ccdro'])) {
                            $q->where('status', 'pending_hec');
                        } elseif ($user->hasRole('cfo')) {
                            $q->whereIn('status', ['pending_hec', 'pending_cfo']);
                        } elseif ($user->hasRole('ceo')) {
                            $q->where('status', 'pending_ceo');
                        } elseif ($user->hasRole('hr')) {
                            $q->where('status', 'pending_hr');
                        }
                    })->count();
                }
            }
        }

        // My Requests
        if ($user->can('view my requests')) {
            $stats['my_requests'] = DB::table('workflows')
                ->where('user_id', $user->id)
                ->where('work_flow_completed', 0)
                ->count();
        }

        // My Locum Claims
        if (\Schema::hasTable('locum_requests')) {
            $stats['locum_claims'] = DB::table('locum_requests')
                ->where('user_id', $user->id)
                ->count();
        }

        // My On-Call Claims
        if (\Schema::hasTable('on_call_requests')) {
            $stats['oncall_claims'] = DB::table('on_call_requests')
                ->where('user_id', $user->id)
                ->count();
        }

        // My Recruitment Requisitions (for line managers and HEC)
        if ($user->hasAnyRole(['line-manager', 'coo', 'cms', 'ccdro']) && \Schema::hasTable('requisitions')) {
            $stats['recruitment_requisitions'] = \App\Models\Requisition::where('user_id', $user->id)->count();
        }

        return $stats;
    }

    public function dashboard()
    {
        $showAlert = false;

        if (auth()->check()) {
            $user = auth()->user();

            // Check if the session variable for first login is set
            if (!session()->has('first_login_shown')) {
                // Set session variable
                session(['first_login_shown' => true]);
                $showAlert = true;
            }
        }

        // Get notifications for forms and workflows
        $notifications = $this->getFormNotifications();

        // // Check if the signature is empty
        // $signature = !empty($user->signature);

        // Debugging statement
        \Log::info('Show Alert: ' . ($showAlert ? 'true' : 'false'));

        return view('dashboard', compact('showAlert', 'hasSignature', 'notifications'));
    }

    /**
     * Get notifications for pending forms and workflows
     */
    private function getFormNotifications()
    {
        if (!Auth::check()) {
            return collect([]);
        }

        $user = Auth::user();
        $notifications = collect([]);

        // Get pending workflow requests
        $pendingWorkflows = \App\Models\Workflow::join('work_flow_histories', 'work_flow_histories.work_flow_id', '=', 'workflows.id')
            ->join('users as requesters', 'requesters.id', '=', 'workflows.user_id')
            ->where('work_flow_histories.attended_by', $user->id)
            ->where(function ($query) {
                $query->where(function ($q) {
                    $q->whereNotNull('workflows.ict_request_resource_id')
                        ->orWhereNotNull('workflows.hr_form')
                        ->orWhereNotNull('workflows.bank_form')
                        ->orWhereNotNull('workflows.heslb_form')
                        ->orWhereNotNull('workflows.nhif_form')
                        ->orWhereNotNull('workflows.id_form')
                        ->orWhereNotNull('workflows.change_request_id');
                })
                    ->whereIn('work_flow_histories.status', [0, 1]);
            })
            ->orWhere(function ($query) {
                $query->whereNotNull('workflows.requisition_id')
                    ->whereIn('work_flow_histories.requisition_status', [0, 1]);
            })
            ->select('workflows.*', 'work_flow_histories.*', 'requesters.username as requester_name', 'requesters.fname', 'requesters.lname')
            ->orderBy('workflows.created_at', 'desc')
            ->get();

        foreach ($pendingWorkflows as $workflow) {
            $formType = $this->getFormType($workflow);
            $notifications->push([
                'type' => 'workflow',
                'form_type' => $formType,
                'title' => "Pending {$formType} Approval",
                'message' => "{$workflow->fname} {$workflow->lname} ({$workflow->requester_name}) submitted a {$formType}",
                'url' => $this->getFormUrl($workflow),
                'created_at' => $workflow->created_at,
                'id' => $workflow->id,
            ]);
        }

        // Get pending clearance forms
        $pendingClearance = \App\Models\Clearance_work_flow::join('clearance_work_flow_histories', 'clearance_work_flow_histories.work_flow_id', '=', 'clearance_work_flows.id')
            ->join('users as requesters', 'requesters.id', '=', 'clearance_work_flows.user_id')
            ->join('clearance_forms', 'clearance_forms.id', '=', 'clearance_work_flows.requested_resource_id')
            ->where('clearance_work_flow_histories.attended_by', $user->id)
            ->where('clearance_work_flow_histories.status', 0)
            ->where('clearance_forms.status', '!=', 'rejected')
            ->select('clearance_work_flows.*', 'clearance_work_flow_histories.*', 'requesters.username as requester_name', 'requesters.fname', 'requesters.lname', 'clearance_work_flows.requested_resource_id')
            ->orderBy('clearance_work_flows.created_at', 'desc')
            ->get();

        foreach ($pendingClearance as $clearance) {
            $stepName = $clearance->step_name ?? 'Clearance Form';
            $notifications->push([
                'type' => 'clearance',
                'form_type' => 'Clearance Form',
                'title' => "Pending Clearance Form - {$stepName}",
                'message' => "{$clearance->fname} {$clearance->lname} submitted a clearance form requiring your {$stepName} approval",
                'url' => route('exit_forms.show', ['id' => $clearance->requested_resource_id]),
                'created_at' => $clearance->created_at,
                'id' => $clearance->requested_resource_id,
            ]);
        }

        return $notifications->sortByDesc('created_at')->take(10);
    }

    private function getFormType($workflow)
    {
        if ($workflow->ict_request_resource_id) return 'ICT Access Form';
        if ($workflow->hr_form) return 'HR Form';
        if ($workflow->bank_form) return 'Bank Details Form';
        if ($workflow->heslb_form) return 'Loan Board Form';
        if ($workflow->nhif_form) return 'NHIF Form';
        if ($workflow->id_form) return 'ID Form';
        if ($workflow->change_request_id) return 'Change Request';
        if ($workflow->requisition_id) return 'RRF Request';
        return 'Form';
    }

    private function getFormUrl($workflow)
    {
        if ($workflow->ict_request_resource_id) return route('show_form', ['id' => $workflow->ict_request_resource_id]);
        if ($workflow->hr_form) return route('hr_form', ['id' => $workflow->hr_form]);
        if ($workflow->bank_form) return route('bank_form', ['id' => $workflow->bank_form]);
        if ($workflow->heslb_form) return route('heslb_form', ['id' => $workflow->heslb_form]);
        if ($workflow->nhif_form) return route('nhif_form', ['id' => $workflow->nhif_form]);
        if ($workflow->id_form) return route('id_form', ['id' => $workflow->id_form]);
        if ($workflow->change_request_id) return route('change_request.show', ['id' => $workflow->change_request_id]);
        if ($workflow->requisition_id) return route('requisitions.show', ['id' => $workflow->requisition_id]);
        return '#';
    }



    public function reviewDashboard()
    {
        // Get the authenticated user
        $user = Auth::user();

        $data = DB::table('work_flow_histories')
            ->join('workflows', 'workflows.id', 'work_flow_histories.work_flow_id')
            ->where('hr_form', $user->id)
            ->orderBy('workflows.created_at', 'desc')
            ->first();

        // Check if data exists
        if (!$data) {
            return redirect()->route('profile.confirm')->with('error', 'No HR form submission found. Please submit your form first.');
        }

        // Determine status based on workflow history
        if ($data->status == 0) {
            $decision_date = null;
            $status = 'Pending';
        } elseif ($data->status == 1) {
            $decision_date = Carbon::parse($data->updated_at)->format('d F Y');
            $status = 'Accepted';
        } else {
            $status = 'Rejected';
            $decision_date = Carbon::parse($data->updated_at)->format('d F Y');
        }

        $formFeedback = [
            'requester_name' => $user->fname . ' ' . $user->lname,
            'request_date' => $data->attend_date ?? Carbon::now()->format('d F Y'),
            'status' => $status,
            'decision_date' => $decision_date,
            'feedback' => $data->rejection_reason ?? null,
            'url' => route('profile.confirm'),
        ];

        // Redirect to the view with form details
        return view('review_dashboard', compact('formFeedback'));
    }

    /**
     * Get role-specific data for dashboard
     */
    private function getRoleSpecificData($user)
    {
        $roleData = [];

        // Line Manager & HEC Members: Department Locum/On-Call Summary
        if ($user->hasRole('line-manager') || $user->hasAnyRole(['coo', 'cms', 'cfo', 'ccdro'])) {
            $roleData['departmentSummary'] = $this->getDepartmentLocumOncallSummary($user);
        }

        // Procurement Officer, Line Manager, HEC: Procurement statistics (filtered by department)
        if ($user->hasRole('procurement-officer') || $user->can('view procureents') || 
            $user->hasRole('line-manager') || $user->hasAnyRole(['coo', 'cms', 'cfo', 'ccdro'])) {
            $roleData['procurementStats'] = $this->getProcurementStats($user);
        }

        // HEC Members (COO, CMS, CFO, CCDRO): Requisition statistics
        if ($user->hasAnyRole(['coo', 'cms', 'cfo', 'ccdro'])) {
            $roleData['requisitionStats'] = $this->getRequisitionStats($user);
        }

        // HR: Employee statistics
        if ($user->hasRole('hr')) {
            $roleData['employeeStats'] = $this->getEmployeeStats();
        }

        // Platform Manager: Platform-specific data
        if ($user->hasRole('platform-manager')) {
            $roleData['platformStats'] = $this->getPlatformStats($user);
        }

        // Price Committee: Procurement review data
        if ($user->hasRole('price-committee') || $user->hasAnyRole(['coo', 'cfo', 'cms'])) {
            $roleData['priceCommitteeData'] = $this->getPriceCommitteeData();
        }

        return $roleData;
    }

    /**
     * Get department-level Locum & On-Call summary for Line Managers and HEC Members
     * Shows PREVIOUS month data (since current month claims are submitted next month)
     * Only includes HR-APPROVED claims (workflow completed)
     */
    private function getDepartmentLocumOncallSummary($user)
    {
        $summary = [
            'departments' => [],
            'totals' => [
                'locum' => ['this_month' => 0, 'this_month_amount' => 0, 'prev_month' => 0, 'prev_month_amount' => 0, 'staff_count' => 0],
                'oncall' => ['this_month' => 0, 'this_month_amount' => 0, 'prev_month' => 0, 'prev_month_amount' => 0, 'staff_count' => 0],
            ],
            'display_month' => '',
            'compare_month' => '',
        ];

        try {
            // Get departments for this user
            $departmentIds = $this->getUserManagedDepartments($user);
            
            if (empty($departmentIds)) {
                return $summary;
            }

            // Show PREVIOUS month data (current month claims are submitted next month)
            // E.g., In December, show November data; compare with October
            $displayMonth = Carbon::now()->subMonth();
            $compareMonth = Carbon::now()->subMonths(2);
            
            $displayMonthName = $displayMonth->format('F');
            $displayYear = $displayMonth->year;
            $compareMonthName = $compareMonth->format('F');
            $compareYear = $compareMonth->year;
            
            $summary['display_month'] = $displayMonth->format('F Y');
            $summary['compare_month'] = $compareMonth->format('F Y');

            // Get department names
            $departments = DB::table('departments')
                ->whereIn('id', $departmentIds)
                ->pluck('dept_name', 'id')
                ->toArray();

            // Get IDs of HR-approved locum requests (workflow completed with status = 1)
            $approvedLocumIds = [];
            $approvedOncallIds = [];
            
            if (\Schema::hasTable('workflows') && \Schema::hasTable('work_flow_histories')) {
                // Get approved locum request IDs
                $approvedLocumIds = DB::table('workflows')
                    ->whereNotNull('locum_request_id')
                    ->where('work_flow_completed', 1)
                    ->where('work_flow_status', 1) // Approved
                    ->pluck('locum_request_id')
                    ->toArray();
                
                // Get approved on-call request IDs
                $approvedOncallIds = DB::table('workflows')
                    ->whereNotNull('on_call_request_id')
                    ->where('work_flow_completed', 1)
                    ->where('work_flow_status', 1) // Approved
                    ->pluck('on_call_request_id')
                    ->toArray();
            }

            foreach ($departmentIds as $deptId) {
                $deptName = $departments[$deptId] ?? 'Unknown Department';
                
                // Get staff in this department
                $staffIds = DB::table('users')
                    ->where('deptid', $deptId)
                    ->pluck('id')
                    ->toArray();
                
                $deptSummary = [
                    'id' => $deptId,
                    'name' => $deptName,
                    'staff_count' => count($staffIds),
                    'locum' => [
                        'this_month' => 0,
                        'this_month_amount' => 0,
                        'prev_month' => 0,
                        'prev_month_amount' => 0,
                        'change' => 0,
                        'change_percentage' => 0,
                        'trend' => 'neutral',
                    ],
                    'oncall' => [
                        'this_month' => 0,
                        'this_month_amount' => 0,
                        'prev_month' => 0,
                        'prev_month_amount' => 0,
                        'change' => 0,
                        'change_percentage' => 0,
                        'trend' => 'neutral',
                    ],
                ];

                if (!empty($staffIds)) {
                    // Locum Requests - Display Month (Previous Month) - Only HR Approved
                    if (\Schema::hasTable('locum_requests')) {
                        $locumDisplayMonthQuery = DB::table('locum_requests')
                            ->whereIn('user_id', $staffIds)
                            ->where('locum_month', $displayMonthName)
                            ->where('locum_year', $displayYear);
                        
                        // Filter by approved only
                        if (!empty($approvedLocumIds)) {
                            $locumDisplayMonthQuery->whereIn('id', $approvedLocumIds);
                        } else {
                            $locumDisplayMonthQuery->whereRaw('1 = 0'); // No approved claims
                        }
                        
                        $locumDisplayMonth = $locumDisplayMonthQuery
                            ->selectRaw('COUNT(*) as count, COALESCE(SUM(total_amount_payable), 0) as total')
                            ->first();

                        $locumCompareMonthQuery = DB::table('locum_requests')
                            ->whereIn('user_id', $staffIds)
                            ->where('locum_month', $compareMonthName)
                            ->where('locum_year', $compareYear);
                        
                        if (!empty($approvedLocumIds)) {
                            $locumCompareMonthQuery->whereIn('id', $approvedLocumIds);
                        } else {
                            $locumCompareMonthQuery->whereRaw('1 = 0');
                        }
                        
                        $locumCompareMonth = $locumCompareMonthQuery
                            ->selectRaw('COUNT(*) as count, COALESCE(SUM(total_amount_payable), 0) as total')
                            ->first();

                        $deptSummary['locum']['this_month'] = $locumDisplayMonth->count ?? 0;
                        $deptSummary['locum']['this_month_amount'] = (float)($locumDisplayMonth->total ?? 0);
                        $deptSummary['locum']['prev_month'] = $locumCompareMonth->count ?? 0;
                        $deptSummary['locum']['prev_month_amount'] = (float)($locumCompareMonth->total ?? 0);

                        // Calculate change
                        $deptSummary['locum']['change'] = $deptSummary['locum']['this_month_amount'] - $deptSummary['locum']['prev_month_amount'];
                        if ($deptSummary['locum']['prev_month_amount'] > 0) {
                            $deptSummary['locum']['change_percentage'] = round(($deptSummary['locum']['change'] / $deptSummary['locum']['prev_month_amount']) * 100, 1);
                        }
                        $deptSummary['locum']['trend'] = $deptSummary['locum']['change'] > 0 ? 'up' : ($deptSummary['locum']['change'] < 0 ? 'down' : 'neutral');
                    }

                    // On-Call Requests - Display Month (Previous Month) - Only HR Approved
                    if (\Schema::hasTable('on_call_requests')) {
                        $oncallDisplayMonthQuery = DB::table('on_call_requests')
                            ->whereIn('user_id', $staffIds)
                            ->where('locum_month', $displayMonthName)
                            ->where('locum_year', $displayYear);
                        
                        // Filter by approved only
                        if (!empty($approvedOncallIds)) {
                            $oncallDisplayMonthQuery->whereIn('id', $approvedOncallIds);
                        } else {
                            $oncallDisplayMonthQuery->whereRaw('1 = 0');
                        }
                        
                        $oncallDisplayMonth = $oncallDisplayMonthQuery
                            ->selectRaw('COUNT(*) as count, COALESCE(SUM(total_amount_payable), 0) as total')
                            ->first();

                        $oncallCompareMonthQuery = DB::table('on_call_requests')
                            ->whereIn('user_id', $staffIds)
                            ->where('locum_month', $compareMonthName)
                            ->where('locum_year', $compareYear);
                        
                        if (!empty($approvedOncallIds)) {
                            $oncallCompareMonthQuery->whereIn('id', $approvedOncallIds);
                        } else {
                            $oncallCompareMonthQuery->whereRaw('1 = 0');
                        }
                        
                        $oncallCompareMonth = $oncallCompareMonthQuery
                            ->selectRaw('COUNT(*) as count, COALESCE(SUM(total_amount_payable), 0) as total')
                            ->first();

                        $deptSummary['oncall']['this_month'] = $oncallDisplayMonth->count ?? 0;
                        $deptSummary['oncall']['this_month_amount'] = (float)($oncallDisplayMonth->total ?? 0);
                        $deptSummary['oncall']['prev_month'] = $oncallCompareMonth->count ?? 0;
                        $deptSummary['oncall']['prev_month_amount'] = (float)($oncallCompareMonth->total ?? 0);

                        // Calculate change
                        $deptSummary['oncall']['change'] = $deptSummary['oncall']['this_month_amount'] - $deptSummary['oncall']['prev_month_amount'];
                        if ($deptSummary['oncall']['prev_month_amount'] > 0) {
                            $deptSummary['oncall']['change_percentage'] = round(($deptSummary['oncall']['change'] / $deptSummary['oncall']['prev_month_amount']) * 100, 1);
                        }
                        $deptSummary['oncall']['trend'] = $deptSummary['oncall']['change'] > 0 ? 'up' : ($deptSummary['oncall']['change'] < 0 ? 'down' : 'neutral');
                    }
                }

                $summary['departments'][] = $deptSummary;

                // Add to totals
                $summary['totals']['locum']['this_month'] += $deptSummary['locum']['this_month'];
                $summary['totals']['locum']['this_month_amount'] += $deptSummary['locum']['this_month_amount'];
                $summary['totals']['locum']['prev_month'] += $deptSummary['locum']['prev_month'];
                $summary['totals']['locum']['prev_month_amount'] += $deptSummary['locum']['prev_month_amount'];
                $summary['totals']['locum']['staff_count'] += $deptSummary['staff_count'];

                $summary['totals']['oncall']['this_month'] += $deptSummary['oncall']['this_month'];
                $summary['totals']['oncall']['this_month_amount'] += $deptSummary['oncall']['this_month_amount'];
                $summary['totals']['oncall']['prev_month'] += $deptSummary['oncall']['prev_month'];
                $summary['totals']['oncall']['prev_month_amount'] += $deptSummary['oncall']['prev_month_amount'];
                $summary['totals']['oncall']['staff_count'] += $deptSummary['staff_count'];
            }

            // Calculate total changes
            $summary['totals']['locum']['change'] = $summary['totals']['locum']['this_month_amount'] - $summary['totals']['locum']['prev_month_amount'];
            $summary['totals']['oncall']['change'] = $summary['totals']['oncall']['this_month_amount'] - $summary['totals']['oncall']['prev_month_amount'];

        } catch (\Exception $e) {
            \Log::error('Error fetching department locum/oncall summary: ' . $e->getMessage());
        }

        return $summary;
    }

    /**
     * Get departments managed by a user (Line Manager or HEC Member)
     */
    private function getUserManagedDepartments($user)
    {
        $departmentIds = [];

        // Line Manager: Their own department
        if ($user->hasRole('line-manager') && $user->deptid) {
            $departmentIds[] = $user->deptid;
        }

        // HEC Members: May have multiple departments or all departments
        if ($user->hasAnyRole(['coo', 'cms', 'cfo', 'ccdro'])) {
            // Check if user has specific departments assigned (if there's a pivot table)
            // For now, HEC members see their own department + any departments they manage
            if ($user->deptid) {
                $departmentIds[] = $user->deptid;
            }

            // If HEC member is COO or CMS, they may see all departments
            if ($user->hasAnyRole(['coo', 'cms'])) {
                $allDeptIds = DB::table('departments')->pluck('id')->toArray();
                $departmentIds = array_merge($departmentIds, $allDeptIds);
            }
        }

        return array_unique($departmentIds);
    }

    /**
     * Get locum rates by month for line managers
     */
    private function getLocumRatesByMonth($user)
    {
        if (!\Schema::hasTable('locum_agreements') || !\Schema::hasTable('locum_requests')) {
            return [];
        }

        $currentYear = Carbon::now()->year;
        $months = [];
        
        // Get all months for the current year
        for ($i = 1; $i <= 12; $i++) {
            $monthName = Carbon::create($currentYear, $i, 1)->format('F');
            $months[$monthName] = [
                'month' => $i,
                'month_name' => $monthName,
                'year' => $currentYear,
                'avg_rate' => 0,
                'total_claims' => 0,
                'total_amount' => 0,
            ];
        }

        // Get locum requests for the user's department
        $userDepartmentId = $user->deptid ?? null;
        
        if ($userDepartmentId) {
            $locumRequests = DB::table('locum_requests')
                ->join('users', 'locum_requests.user_id', '=', 'users.id')
                ->join('locum_agreements', 'locum_requests.locum_agreement_id', '=', 'locum_agreements.id')
                ->where('users.deptid', $userDepartmentId)
                ->where('locum_requests.locum_year', $currentYear)
                ->select(
                    'locum_requests.locum_month',
                    'locum_agreements.locum_rate',
                    'locum_requests.total_amount_payable',
                    'locum_requests.grand_total_locums'
                )
                ->get();

            foreach ($locumRequests as $request) {
                $monthName = $request->locum_month;
                if (isset($months[$monthName])) {
                    $months[$monthName]['total_claims']++;
                    $months[$monthName]['total_amount'] += (float)($request->total_amount_payable ?? 0);
                }
            }

            // Calculate average rates per month
            foreach ($months as $monthName => &$monthData) {
                $monthRequests = DB::table('locum_requests')
                    ->join('users', 'locum_requests.user_id', '=', 'users.id')
                    ->join('locum_agreements', 'locum_requests.locum_agreement_id', '=', 'locum_agreements.id')
                    ->where('users.deptid', $userDepartmentId)
                    ->where('locum_requests.locum_year', $currentYear)
                    ->where('locum_requests.locum_month', $monthName)
                    ->select('locum_agreements.locum_rate')
                    ->get();

                if ($monthRequests->count() > 0) {
                    $avgRate = $monthRequests->avg('locum_rate');
                    $monthData['avg_rate'] = round($avgRate ?? 0, 2);
                }
            }
        }

        return array_values($months);
    }

    /**
     * Get procurement statistics (filtered by department for line managers and HEC members)
     */
    private function getProcurementStats($user)
    {
        if (!\Schema::hasTable('ccbrt_contracts')) {
            return [];
        }

        $stats = [
            'total_contracts' => 0,
            'active_contracts' => 0,
            'expiring_soon' => 0,
            'pending_review' => 0,
            'total_value' => 0,
            'recent_contracts' => [],
            'recent_vendors' => [],
        ];

        try {
            // Get department IDs for filtering
            $departmentIds = [];
            $filterByDepartment = false;

            // Procurement officers and super-admins see all
            if (!$user->hasRole('procurement-officer') && !$user->hasRole('super-admin')) {
                // Line Managers and HEC Members see only their department's contracts
                if ($user->hasRole('line-manager') || $user->hasAnyRole(['coo', 'cms', 'cfo', 'ccdro'])) {
                    $departmentIds = $this->getUserManagedDepartments($user);
                    // For COO/CMS, don't filter (they see all)
                    if (!$user->hasAnyRole(['coo', 'cms'])) {
                        $filterByDepartment = true;
                    }
                }
            }

            // Build the query
            $contractsQuery = DB::table('ccbrt_contracts');
            if ($filterByDepartment && !empty($departmentIds)) {
                $contractsQuery->whereIn('department_id', $departmentIds);
            }
            $contracts = $contractsQuery->get();

            $stats['total_contracts'] = $contracts->count();
            $stats['total_value'] = $contracts->sum('cost');

            $today = Carbon::now();
            $expiringDate = $today->copy()->addDays(30);

            foreach ($contracts as $contract) {
                if ($contract->status === 'active' || $contract->status === 'in_progress') {
                    $stats['active_contracts']++;
                }

                if ($contract->approval_stage === 'procurement' && 
                    in_array($contract->status, ['in_progress', 'pending']) &&
                    ($user->hasRole('procurement-officer') ? $contract->current_approver_id == $user->id : true)) {
                    $stats['pending_review']++;
                }

                if ($contract->end_date) {
                    $endDate = Carbon::parse($contract->end_date);
                    if ($endDate->isFuture() && $endDate->lte($expiringDate)) {
                        $stats['expiring_soon']++;
                    }
                }
            }

            // Recent contracts with vendor name (filtered by department)
            $recentContractsQuery = DB::table('ccbrt_contracts')
                ->leftJoin('ccbrt_vendors', 'ccbrt_contracts.vendor_id', '=', 'ccbrt_vendors.id')
                ->leftJoin('departments', 'ccbrt_contracts.department_id', '=', 'departments.id')
                ->select('ccbrt_contracts.*', 'ccbrt_vendors.vendor_name', 'departments.dept_name');
            
            if ($filterByDepartment && !empty($departmentIds)) {
                $recentContractsQuery->whereIn('ccbrt_contracts.department_id', $departmentIds);
            }
            
            $recentContracts = $recentContractsQuery
                ->orderBy('ccbrt_contracts.created_at', 'desc')
                ->limit(5)
                ->get();

            $stats['recent_contracts'] = $recentContracts->map(function ($contract) {
                return [
                    'id' => $contract->id,
                    'title' => $contract->title ?? 'N/A',
                    'vendor' => $contract->vendor_name ?? 'N/A',
                    'department' => $contract->dept_name ?? 'N/A',
                    'cost' => $contract->cost ?? 0,
                    'status' => $contract->status ?? 'N/A',
                    'created_at' => $contract->created_at,
                ];
            });

            // Recent vendors involved with contracts (filtered by department)
            $vendorsQuery = DB::table('ccbrt_vendors')
                ->join('ccbrt_contracts', 'ccbrt_vendors.id', '=', 'ccbrt_contracts.vendor_id');
            
            if ($filterByDepartment && !empty($departmentIds)) {
                $vendorsQuery->whereIn('ccbrt_contracts.department_id', $departmentIds);
            }
            
            $recentVendors = $vendorsQuery
                ->select('ccbrt_vendors.id', 'ccbrt_vendors.vendor_name', 'ccbrt_vendors.contact_person', 'ccbrt_vendors.phone')
                ->distinct()
                ->limit(5)
                ->get();

            $stats['recent_vendors'] = $recentVendors->map(function ($vendor) {
                return [
                    'id' => $vendor->id,
                    'name' => $vendor->vendor_name ?? 'N/A',
                    'contact' => $vendor->contact_person ?? 'N/A',
                    'phone' => $vendor->phone ?? 'N/A',
                ];
            });
        } catch (\Exception $e) {
            \Log::error('Error fetching procurement stats: ' . $e->getMessage());
        }

        return $stats;
    }

    /**
     * Get requisition statistics for HEC members
     */
    private function getRequisitionStats($user)
    {
        if (!\Schema::hasTable('requisitions')) {
            return [];
        }

        $stats = [
            'pending_hec' => 0,
            'pending_cfo' => 0,
            'pending_ceo' => 0,
            'approved' => 0,
            'rejected' => 0,
            'total' => 0,
        ];

        try {
            $query = \App\Models\Requisition::query();

            if ($user->hasAnyRole(['coo', 'cms', 'ccdro'])) {
                $stats['pending_hec'] = (clone $query)->where('status', 'pending_hec')->count();
            }

            if ($user->hasRole('cfo')) {
                $stats['pending_cfo'] = (clone $query)->where('status', 'pending_cfo')->count();
                $stats['pending_hec'] = (clone $query)->where('status', 'pending_hec')->count();
            }

            if ($user->hasRole('ceo')) {
                $stats['pending_ceo'] = (clone $query)->where('status', 'pending_ceo')->count();
            }

            $stats['approved'] = (clone $query)->where('status', 'approved')->count();
            $stats['rejected'] = (clone $query)->whereIn('status', ['rejected', 'rejected_for_editing'])->count();
            $stats['total'] = (clone $query)->count();
        } catch (\Exception $e) {
            \Log::error('Error fetching requisition stats: ' . $e->getMessage());
        }

        return $stats;
    }

    /**
     * Get employee statistics for HR
     */
    private function getEmployeeStats()
    {
        $stats = [
            'total_employees' => 0,
            'active_employees' => 0,
            'pending_forms' => 0,
            'recent_hires' => 0,
        ];

        try {
            $stats['total_employees'] = \App\Models\User::count();
            $stats['active_employees'] = \App\Models\User::where('status', 'active')->count();

            // Pending HR forms
            $stats['pending_forms'] = DB::table('workflows')
                ->whereNotNull('hr_form')
                ->where('work_flow_completed', 0)
                ->count();

            // Recent hires (last 30 days)
            $stats['recent_hires'] = \App\Models\User::where('created_at', '>=', Carbon::now()->subDays(30))->count();
        } catch (\Exception $e) {
            \Log::error('Error fetching employee stats: ' . $e->getMessage());
        }

        return $stats;
    }

    /**
     * Get platform statistics for platform managers
     */
    private function getPlatformStats($user)
    {
        // This would need to be implemented based on your platform structure
        return [
            'total_platforms' => 0,
            'active_units' => 0,
        ];
    }

    /**
     * Get price committee data
     */
    private function getPriceCommitteeData()
    {
        if (!\Schema::hasTable('ccbrt_contracts')) {
            return [];
        }

        return [
            'pending_reviews' => DB::table('ccbrt_contracts')
                ->where('approval_stage', 'price_committee')
                ->whereIn('status', ['in_progress', 'pending'])
                ->count(),
            'total_contracts' => DB::table('ccbrt_contracts')->count(),
        ];
    }

    /**
     * Get locum and on-call summary for users
     */
    private function getLocumOncallSummary($user)
    {
        $summary = [
            'locum' => [
                'total_claims' => 0,
                'this_month' => 0,
                'pending_approval' => 0,
                'approved' => 0,
                'recent_claims' => [],
            ],
            'oncall' => [
                'total_claims' => 0,
                'this_month' => 0,
                'pending_approval' => 0,
                'approved' => 0,
                'recent_claims' => [],
            ],
        ];

        try {
            // Locum Requests Summary
            if (\Schema::hasTable('locum_requests')) {
                $locumRequests = DB::table('locum_requests')
                    ->where('user_id', $user->id)
                    ->get();

                $summary['locum']['total_claims'] = $locumRequests->count();
                
                // This month's claims
                $summary['locum']['this_month'] = DB::table('locum_requests')
                    ->where('user_id', $user->id)
                    ->whereMonth('created_at', Carbon::now()->month)
                    ->whereYear('created_at', Carbon::now()->year)
                    ->count();

                // Check workflow status for pending/approved
                $locumWorkflows = DB::table('workflows')
                    ->where('user_id', $user->id)
                    ->whereNotNull('locum_request_id')
                    ->get();

                foreach ($locumWorkflows as $workflow) {
                    $history = DB::table('work_flow_histories')
                        ->where('work_flow_id', $workflow->id)
                        ->where('status', 0)
                        ->first();
                    
                    if ($history) {
                        $summary['locum']['pending_approval']++;
                    } else {
                        $completed = DB::table('work_flow_histories')
                            ->where('work_flow_id', $workflow->id)
                            ->where('status', 1)
                            ->whereNotNull('locum_request_status')
                            ->exists();
                        if ($completed) {
                            $summary['locum']['approved']++;
                        }
                    }
                }

                // Recent locum claims (last 5)
                $summary['locum']['recent_claims'] = DB::table('locum_requests')
                    ->where('user_id', $user->id)
                    ->orderBy('created_at', 'desc')
                    ->limit(5)
                    ->get()
                    ->map(function ($request) {
                        return [
                            'id' => $request->id,
                            'month' => $request->locum_month ?? 'N/A',
                            'year' => $request->locum_year ?? date('Y'),
                            'amount' => $request->total_amount_payable ?? 0,
                            'created_at' => $request->created_at,
                        ];
                    });
            }

            // On-Call Requests Summary
            if (\Schema::hasTable('on_call_requests')) {
                $oncallRequests = DB::table('on_call_requests')
                    ->where('user_id', $user->id)
                    ->get();

                $summary['oncall']['total_claims'] = $oncallRequests->count();
                
                // This month's claims
                $summary['oncall']['this_month'] = DB::table('on_call_requests')
                    ->where('user_id', $user->id)
                    ->whereMonth('created_at', Carbon::now()->month)
                    ->whereYear('created_at', Carbon::now()->year)
                    ->count();

                // Check workflow status for pending/approved
                $oncallWorkflows = DB::table('workflows')
                    ->where('user_id', $user->id)
                    ->whereNotNull('on_call_request_id')
                    ->get();

                foreach ($oncallWorkflows as $workflow) {
                    $history = DB::table('work_flow_histories')
                        ->where('work_flow_id', $workflow->id)
                        ->where('status', 0)
                        ->first();
                    
                    if ($history) {
                        $summary['oncall']['pending_approval']++;
                    } else {
                        $completed = DB::table('work_flow_histories')
                            ->where('work_flow_id', $workflow->id)
                            ->where('status', 1)
                            ->whereNotNull('on_call_request_status')
                            ->exists();
                        if ($completed) {
                            $summary['oncall']['approved']++;
                        }
                    }
                }

                // Recent on-call claims (last 5)
                $summary['oncall']['recent_claims'] = DB::table('on_call_requests')
                    ->where('user_id', $user->id)
                    ->orderBy('created_at', 'desc')
                    ->limit(5)
                    ->get()
                    ->map(function ($request) {
                        return [
                            'id' => $request->id,
                            'month' => $request->locum_month ?? 'N/A',
                            'amount' => $request->total_amount ?? 0,
                            'created_at' => $request->created_at,
                        ];
                    });
            }
        } catch (\Exception $e) {
            \Log::error('Error fetching locum/oncall summary: ' . $e->getMessage());
        }

        return $summary;
    }
}
