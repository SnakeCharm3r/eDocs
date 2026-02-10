<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Workflow;
use App\Models\LocumRequest;
use Illuminate\Http\Request;
use App\Models\LocumAgreement;
use App\Models\WorkFlowHistory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use App\Mail\ApprovalRequestNotification;
use Carbon\Carbon;




class LocumAgreementController extends Controller
{
    //
    public function index()
    {
        $user = Auth::user();
        $agreement = LocumAgreement::where('user_id', $user->id)->first();
        return view('locum_agreement.index', compact('agreement', 'user'));
    }

    public function store(Request $request)
    {
        $user = Auth::user();

        // --- Guards ---
        if (empty($user->ccbrt_code)) {
            return back()->withErrors(['ccbrt_code' => 'Your account is missing a CCBRT code. Please contact Admin.'])->withInput();
        }
        if (in_array($user->status, ['inactive', 'deactivated', 'pending'], true)) {
            return back()->withErrors(['status' => 'Your account is not active. Please contact HR.'])->withInput();
        }

        // --- Validate ---
        $validated = $request->validate([
            'education_level' => 'required|string|in:Certificate,Enrolled_Certificate,Diploma,Degree,Masters',
            'locum_rate'      => 'required|numeric|min:0',
            'start_date'      => 'required|date',
            'end_date'        => 'required|date|after:start_date',
        ]);

        // --- Enforce rate ---
        $expectedRate = match ($validated['education_level']) {
            'Certificate'          => 50000,
            'Enrolled_Certificate' => 60000,
            'Diploma'              => 80000,
            'Degree'               => 100000,
            'Masters'              => 120000,
            default                => 0,
        };
        if ((float)$validated['locum_rate'] !== (float)$expectedRate) {
            return back()->withErrors(['locum_rate' => 'Invalid locum rate for the selected education level.'])->withInput();
        }

        // --- Helpers (NO department filter for HR approver) ---
        $getHrApprover = function () {
            return \App\Models\User::whereHas('roles', fn($q) => $q->where('name', 'hr'))
                ->where('approvelocum', 1)
                ->orderBy('id') // or any tie-breaker you prefer
                ->first();
        };

        $isLineManagerRequester = $user->hasRole('line-manager') && !$user->hasRole('hr');

        // Resolve recipients
        $lineManager = null;
        if (!$isLineManagerRequester) {
            $lineManager = \App\Models\User::whereHas('roles', fn($q) => $q->where('name', 'line-manager'))
                ->where('deptId', $user->deptId)
                ->first();

            if (!$lineManager) {
                return back()->withErrors(['line_manager' => 'No line manager found for your department. Please contact HR.'])->withInput();
            }
        }

        $hrApprover = null;
        if ($isLineManagerRequester) {
            // DIRECT → HR (any approver with approvelocum=1)
            $hrApprover = $getHrApprover();
            if (!$hrApprover) {
                return back()->withErrors(['hr_approver' => 'No HR approver (approvelocum=1) found. Please contact admin.'])->withInput();
            }
        }

        // --- Persist ---
        DB::beginTransaction();
        try {
            $locumagreement = \App\Models\LocumAgreement::create([
                'user_id'         => $user->id,
                'has_contract'    => true,
                'education_level' => $validated['education_level'],
                'locum_rate'      => $validated['locum_rate'],
                'start_date'      => $validated['start_date'],
                'end_date'        => $validated['end_date'],
                'status'          => $isLineManagerRequester ? 1 : 0, // LM requester → Pending HR
            ]);

            $workflow = \App\Models\Workflow::create([
                'user_id'             => $user->id,
                'work_flow_status'    => 0,
                'work_flow_completed' => 0,
                'locum_agreement_id'  => $locumagreement->id,
            ]);

            // Verify workflow was created
            if (!$workflow || !$workflow->id) {
                throw new \Exception('Failed to create workflow for locum agreement');
            }

            Log::info('Locum Agreement: Workflow created', [
                'agreement_id' => $locumagreement->id,
                'workflow_id' => $workflow->id,
                'user_id' => $user->id,
            ]);

            if ($isLineManagerRequester) {
                // First step goes straight to HR
                $history = \App\Models\WorkFlowHistory::create([
                    'work_flow_id'           => $workflow->id,
                    'forwarded_by'           => $user->id,
                    'attended_by'            => $hrApprover->id,
                    'status'                 => 0,
                    'remark'                 => 'Submitted by Line Manager — pending HR approval.',
                    'locum_agreement_status' => 1, // HR stage
                ]);

                // Verify history was created
                if (!$history || !$history->id) {
                    throw new \Exception('Failed to create workflow history for HR approver');
                }

                Log::info('Locum Agreement: Created workflow history for HR', [
                    'agreement_id' => $locumagreement->id,
                    'workflow_id' => $workflow->id,
                    'history_id' => $history->id,
                    'hr_approver_id' => $hrApprover->id,
                    'hr_approver_name' => $hrApprover->username ?? $hrApprover->fname,
                    'history_status' => $history->status,
                    'locum_agreement_status' => $history->locum_agreement_status,
                ]);

                if (!empty($hrApprover->email)) {
                    Mail::to($hrApprover->email)->queue(new \App\Mail\ApprovalRequestNotification($hrApprover, [
                        'forwarded_by' => $user->name ?? $user->fname ?? 'Requester',
                        'request'      => 'Locum Agreement',
                        'requestDate'  => now()->format('d F Y'),
                        'action'       => 'HR Approval Required',
                    ]));
                }
            } else {
                // Normal flow → Line Manager first
                $history = \App\Models\WorkFlowHistory::create([
                    'work_flow_id'           => $workflow->id,
                    'forwarded_by'           => $user->id,
                    'attended_by'            => $lineManager->id,
                    'status'                 => 0,
                    'remark'                 => 'Locum Agreement submitted for approval.',
                    'locum_agreement_status' => 0, // LM stage
                ]);

                // Verify history was created
                if (!$history || !$history->id) {
                    throw new \Exception('Failed to create workflow history for Line Manager');
                }

                Log::info('Locum Agreement: Created workflow history for Line Manager', [
                    'agreement_id' => $locumagreement->id,
                    'workflow_id' => $workflow->id,
                    'history_id' => $history->id,
                    'line_manager_id' => $lineManager->id,
                    'line_manager_name' => $lineManager->username ?? $lineManager->fname,
                    'history_status' => $history->status,
                    'locum_agreement_status' => $history->locum_agreement_status,
                ]);

                if (!empty($lineManager->email)) {
                    Mail::to($lineManager->email)->queue(new \App\Mail\ApprovalRequestNotification($lineManager, [
                        'forwarded_by' => $user->name ?? $user->fname ?? 'Requester',
                        'request'      => 'Locum Agreement',
                        'requestDate'  => now()->format('d F Y'),
                    ]));
                }
            }

            DB::commit();

            // Verify data was actually saved after commit
            $locumagreement->refresh();
            $workflow->refresh();
            $history->refresh();

            // Double-check workflow history exists
            $verifyHistory = \App\Models\WorkFlowHistory::find($history->id);
            if (!$verifyHistory) {
                Log::error('Locum Agreement: Workflow history not found after commit!', [
                    'history_id' => $history->id,
                    'workflow_id' => $workflow->id,
                    'agreement_id' => $locumagreement->id,
                ]);
            }

            Log::info('Locum Agreement: Successfully created and workflow initialized', [
                'agreement_id' => $locumagreement->id,
                'workflow_id' => $workflow->id,
                'history_id' => $history->id,
                'user_id' => $user->id,
                'status' => $locumagreement->status,
                'history_status' => $history->status,
                'history_attended_by' => $history->attended_by,
                'history_locum_agreement_status' => $history->locum_agreement_status,
                'workflow_exists' => $verifyHistory ? 'yes' : 'no',
            ]);
            return redirect()->route('locum-agreements.view')->with('success', 'Locum Agreement submitted successfully.');
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Failed to create Locum Agreement: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return back()->withErrors(['error' => 'An error occurred while submitting your request. Please try again.'])->withInput();
        }
    }


    // public function store(Request $request)
    // {
    //     $user = Auth::user();

    //     // Check if user has a ccbrt_code
    //     if (empty($user->ccbrt_code)) {
    //         return redirect()->back()->withErrors(['ccbrt_code' => 'Your account is missing a CCBRT code. Please contact Admin.'])->withInput();
    //     }

    //     // Check user's status and contract validity
    //     if (in_array($user->status, ['inactive', 'deactivated', 'pending'])) {
    //         return redirect()->back()->withErrors(['status' => 'Your account is not active. Please contact HR.'])->withInput();
    //     }

    //     $today = now()->startOfDay();

    //     // Validate input
    //     $validated = $request->validate([
    //         'education_level' => 'required|string|in:Certificate,Enrolled_Certificate,Diploma,Degree,Masters',
    //         'locum_rate' => 'required|numeric|min:0',
    //         'start_date' => 'required|date',
    //         'end_date' => 'required|date|after:start_date',
    //     ]);

    //     // Validate locum rate based on education level
    //     $expectedRate = match ($validated['education_level']) {
    //         'Certificate' => 50000,
    //         'Enrolled_Certificate' => 60000,
    //         'Diploma' => 80000,
    //         'Degree' => 100000,
    //         'Masters' => 200000,
    //         default => 0,
    //     };

    //     if ($validated['locum_rate'] != $expectedRate) {
    //         return back()->withErrors(['locum_rate' => 'Invalid locum rate for the selected education level.'])->withInput();
    //     }

    //     // Find line manager
    //     $lineManager = User::whereHas('roles', function ($query) {
    //         $query->where('name', 'line-manager');
    //     })->where('deptId', Auth::user()->deptId)->first();

    //     // Check if line manager exists
    //     if (!$lineManager) {
    //         return redirect()->back()->withErrors(['line_manager' => 'No line manager found for your department. Please contact HR.'])->withInput();
    //     }

    //     // Create locum agreement
    //     $locumagreement = LocumAgreement::create([
    //         'user_id' => $user->id,
    //         'has_contract' => true,
    //         'education_level' => $validated['education_level'],
    //         'locum_rate' => $validated['locum_rate'],
    //         'start_date' => $validated['start_date'],
    //         'end_date' => $validated['end_date'],
    //     ]);

    //     // Create workflow
    //     $workflow = Workflow::create([
    //         'user_id' => Auth::user()->id,
    //         'work_flow_status' => 0,
    //         'work_flow_completed' => 0,
    //         'locum_agreement_id' => $locumagreement->id
    //     ]);

    //     // Create workflow history
    //     $input = [
    //         'work_flow_id' => $workflow->id,
    //         'forwarded_by' => Auth::user()->id,
    //         'attended_by' => $lineManager->id,
    //         'remark' => 'Locum Agreement submitted for approval.',
    //         'status' => 0,
    //         'locum_agreement_status' => 0,
    //     ];
    //     WorkFlowHistory::create($input);

    //     // Send email notification to line manager
    //     Mail::to($lineManager->email)->queue(new ApprovalRequestNotification($lineManager, [
    //         'forwarded_by' => Auth::user()->fname,
    //         'request' => 'Locum Agreement',
    //         'requestDate' => now()->format('d F Y'),
    //     ]));

    //     return redirect()->route('locum-agreements.view')->with('success', 'Locum Agreement submitted successfully.');
    // }

    public function view()
    {
        $user = Auth::user();

        $agreements = LocumAgreement::where('user_id', $user->id)
            ->with(['user.department'])
            ->orderBy('created_at', 'desc')
            ->get();

        $latestAgreement = $agreements->first(); // pick the most recent
        return view('locum_agreement.view', compact('agreements', 'latestAgreement'));
    }



    public function show($id)
    {
        $agreement = LocumAgreement::with('workflow.histories')->findOrFail($id);

        $linemanager = null;
        $Hr = null;

        // Find line manager from workflow history
        if ($agreement->workflow && $agreement->workflow->histories) {
            // First, try to find history record with locum_agreement_status = 0 (LM stage)
            // This could be pending (status = 0) or approved (status = 1, but locum_agreement_status might be updated to 1)
            $linemanagerHistory = $agreement->workflow->histories
                ->where('locum_agreement_status', 0)
                ->first();

            // If not found, look for any history where attended_by is a line manager
            if (!$linemanagerHistory) {
                foreach ($agreement->workflow->histories as $history) {
                    $user = User::find($history->attended_by);
                    if ($user && $user->hasRole('line-manager') && !$user->hasRole('hr')) {
                        $linemanagerHistory = $history;
                        break;
                    }
                }
            }

            if ($linemanagerHistory) {
                $linemanager = User::find($linemanagerHistory->attended_by);
            }

            // Get HR approver - look for history with locum_agreement_status = 1 (HR stage)
            // IMPORTANT: When LM approves, a NEW workflow history is created for HR
            // We need to find the HR history that was CREATED for HR, not the LM history that was UPDATED
            $HrHistory = null;

            // Find the LM history IDs first to know which ones to exclude
            $lmHistoryIds = [];
            foreach ($agreement->workflow->histories as $history) {
                $user = User::find($history->attended_by);
                if ($user && $user->hasRole('line-manager') && !$user->hasRole('hr')) {
                    $lmHistoryIds[] = $history->id;
                }
            }

            // First, look for histories with locum_agreement_status = 1 where:
            // 1. The user is HR (preferably not also a line manager)
            // 2. This history is NOT one of the LM histories (to avoid the updated LM history)
            foreach ($agreement->workflow->histories as $history) {
                if ($history->locum_agreement_status == 1 && !in_array($history->id, $lmHistoryIds)) {
                    $user = User::find($history->attended_by);
                    if ($user && $user->hasRole('hr')) {
                        // Prefer users who are HR but NOT line managers
                        if (!$user->hasRole('line-manager')) {
                            $HrHistory = $history;
                            break;
                        } else {
                            // If user has both roles, still use it but only if we haven't found a better one
                            if (!$HrHistory) {
                                $HrHistory = $history;
                            }
                        }
                    }
                }
            }

            // If still not found, look for any HR user from workflow history (excluding LM histories)
            if (!$HrHistory) {
                foreach ($agreement->workflow->histories as $history) {
                    if (!in_array($history->id, $lmHistoryIds)) {
                        $user = User::find($history->attended_by);
                        if ($user && $user->hasRole('hr') && !$user->hasRole('line-manager')) {
                            $HrHistory = $history;
                            break;
                        }
                    }
                }
            }

            if ($HrHistory) {
                $Hr = User::find($HrHistory->attended_by);
            }
        }

        // Fallback: try to get line manager from user's department if not found in workflow
        if (!$linemanager && $agreement->user && $agreement->user->deptId) {
            $linemanager = User::whereHas('roles', fn($q) => $q->where('name', 'line-manager'))
                ->where('deptId', $agreement->user->deptId)
                ->first();
        }

        // Check if Line Manager has approved
        // When LM approves, the workflow history status becomes 1, but locum_agreement_status is updated to 1
        // So we need to find the LM history by checking if the user is a line manager AND status = 1
        $lmApproved = false;
        $lmActionAt = null;
        if ($agreement->workflow && $agreement->workflow->histories) {
            // First try to find approved LM history (status = 1) where user is a line manager
            foreach ($agreement->workflow->histories as $history) {
                $user = User::find($history->attended_by);
                if ($user && $user->hasRole('line-manager') && !$user->hasRole('hr') && $history->status == 1) {
                    $lmHistory = $history;
                    $lmApproved = true;
                    $dt = $lmHistory->attend_date ?? $lmHistory->decision_date ?? $lmHistory->updated_at ?? $lmHistory->created_at;
                    $lmActionAt = $dt ? \Carbon\Carbon::parse($dt)->format('d M Y H:i') : null;
                    break;
                }
            }

            // Fallback: check for history with locum_agreement_status = 0 AND status = 1 (before it was updated)
            if (!$lmApproved) {
                $lmHistory = $agreement->workflow->histories
                    ->where('locum_agreement_status', 0)
                    ->where('status', 1)
                    ->first();
                if ($lmHistory) {
                    $lmApproved = true;
                    $dt = $lmHistory->attend_date ?? $lmHistory->decision_date ?? $lmHistory->updated_at ?? $lmHistory->created_at;
                    $lmActionAt = $dt ? \Carbon\Carbon::parse($dt)->format('d M Y H:i') : null;
                }
            }
        }

        // Check if HR has approved
        // HR approval is indicated by: locum_agreement_status = 2 (final stage) AND status = 1 (approved)
        // OR agreement status = 2 (approved)
        $hrApproved = false;
        $hrActionAt = null;
        if ($agreement->status == 2) {
            // Agreement is approved, so HR must have approved
            $hrApproved = true;
            $lastHrHistory = $agreement->workflow && $agreement->workflow->histories
                ? $agreement->workflow->histories
                ->where('locum_agreement_status', 2)
                ->where('status', 1)
                ->first()
                : null;
            if ($lastHrHistory) {
                $dt = $lastHrHistory->attend_date ?? $lastHrHistory->decision_date ?? $lastHrHistory->updated_at ?? $lastHrHistory->created_at;
                $hrActionAt = $dt ? \Carbon\Carbon::parse($dt)->format('d M Y H:i') : null;
            }
        } elseif ($agreement->workflow && $agreement->workflow->histories) {
            $hrHistory = $agreement->workflow->histories
                ->where('locum_agreement_status', 2)
                ->where('status', 1)
                ->first();
            $hrApproved = $hrHistory ? true : false;
            if ($hrHistory) {
                $dt = $hrHistory->attend_date ?? $hrHistory->decision_date ?? $hrHistory->updated_at ?? $hrHistory->created_at;
                $hrActionAt = $dt ? \Carbon\Carbon::parse($dt)->format('d M Y H:i') : null;
            }
        }

        if (!$agreement) {
            return redirect()->back()->withErrors(['agreement' => 'Locum Agreement not found.']);
        }
        if ($agreement->user_id == auth()->id()) {
            return view('locum_agreement.show', compact('agreement', 'linemanager', 'Hr', 'lmApproved', 'hrApproved', 'lmActionAt', 'hrActionAt'));
        } else {
            return view('locum_agreement.approve_locum_agreement', compact('agreement', 'linemanager', 'Hr', 'lmApproved', 'hrApproved', 'lmActionAt', 'hrActionAt'));
        }
    }

    public function viewAgreementRequest()
    {
        $user = Auth::user();

        // Badge count for "Locum Requests"
        $requests = LocumRequest::whereHas('workflow', function ($query) use ($user) {
            $query->where('work_flow_completed', 0)
                ->whereHas('histories', function ($q) use ($user) {
                    $q->where('attended_by', $user->id)
                        ->where('status', 0)
                        ->whereNotNull('locum_request_status');
                });
        })
            ->with(['user', 'locumAgreement', 'workflow.histories' => function ($q) {
                $q->whereNotNull('locum_request_status')->latest();
            }])
            ->orderBy('created_at', 'desc')
            ->count();

        // === figure out how to read "department" safely ===
        $hasDepartmentsTable = \Schema::hasTable('departments');

        // Try common department name columns (now includes dept_name)
        $deptNameColumn = null;
        if ($hasDepartmentsTable) {
            foreach (['dept_name', 'name', 'department_name', 'title'] as $col) {
                if (\Schema::hasColumn('departments', $col)) {
                    $deptNameColumn = $col;
                    break;
                }
            }
        }

        // Detect the FK on users (department_id OR deptId)
        $userDeptFk = null;
        if (\Schema::hasColumn('users', 'department_id')) {
            $userDeptFk = 'department_id';
        } elseif (\Schema::hasColumn('users', 'deptId')) {
            $userDeptFk = 'deptId';
        }

        // === Agreements list (assigned to me, not completed) ===
        // Get unique agreements by using a subquery to find the latest pending history for each workflow
        $latestPendingHistoryIds = \DB::table('work_flow_histories')
            ->select(\DB::raw('MAX(id) as id'))
            ->where('attended_by', $user->id)
            ->where('status', 0)
            ->whereNotNull('locum_agreement_status')
            ->groupBy('work_flow_id')
            ->pluck('id');

        // Log for debugging on live server
        Log::info('Locum Agreement Approver Queue', [
            'user_id' => $user->id,
            'user_roles' => $user->getRoleNames()->toArray(),
            'pending_history_ids_count' => $latestPendingHistoryIds->count(),
            'pending_history_ids' => $latestPendingHistoryIds->toArray(),
        ]);

        // If no pending histories found, return empty collection
        if ($latestPendingHistoryIds->isEmpty()) {
            Log::info('No pending locum agreement histories found for user', ['user_id' => $user->id]);
            $agreements = collect();
        } else {
            $agreementsQuery = \DB::table('locum_agreements')
                ->join('workflows', 'locum_agreements.id', '=', 'workflows.locum_agreement_id')
                ->join('work_flow_histories', function ($join) use ($latestPendingHistoryIds) {
                    $join->on('workflows.id', '=', 'work_flow_histories.work_flow_id')
                        ->whereIn('work_flow_histories.id', $latestPendingHistoryIds->toArray());
                })
                ->join('users', 'locum_agreements.user_id', '=', 'users.id');

            // Base selects
            $selects = [
                'locum_agreements.*',
                'work_flow_histories.attended_by',
                'work_flow_histories.remark',
                'users.fname',
                'users.lname',
                'users.ccbrt_code',
            ];

            // Join departments if we can; otherwise emit a fallback alias
            if ($hasDepartmentsTable && $deptNameColumn && $userDeptFk) {
                $agreementsQuery->leftJoin('departments', "users.$userDeptFk", '=', 'departments.id');
                $selects[] = \DB::raw("COALESCE(departments.$deptNameColumn, '—') AS department_name");
            } else {
                $selects[] = \DB::raw("'—' AS department_name");
            }

            $agreements = $agreementsQuery
                ->select($selects)
                ->orderByDesc('locum_agreements.created_at')
                ->get();

            Log::info('Locum Agreement Approver Queue Results', [
                'user_id' => $user->id,
                'agreements_count' => $agreements->count(),
                'agreement_ids' => $agreements->pluck('id')->toArray(),
            ]);
        }

        // ---- SPLIT FOR TABS/COUNTS (this fixes Undefined variable $pending) ----
        $pending  = $agreements->filter(function ($a) {
            return in_array((int)($a->status ?? 0), [0, 1]);
        })->values();

        $approved = $agreements->filter(function ($a) {
            return (int)($a->status ?? 0) === 2;
        })->values();

        $rejected = $agreements->filter(function ($a) {
            return in_array((int)($a->status ?? -1), [3, 4]);
        })->values();

        // === HR Summary (all agreements) ===
        $hrStatusCounts = \DB::table('locum_agreements')
            ->selectRaw("
            SUM(CASE WHEN status = 0 THEN 1 ELSE 0 END) AS pending_line_manager,
            SUM(CASE WHEN status = 1 THEN 1 ELSE 0 END) AS pending_hr,
            SUM(CASE WHEN status = 2 THEN 1 ELSE 0 END) AS approved,
            SUM(CASE WHEN status IN (3,4) THEN 1 ELSE 0 END) AS rejected,
            COUNT(*) AS total
        ")
            ->first();

        // === Department Summary (if we can resolve department name) ===
        if ($hasDepartmentsTable && $deptNameColumn && $userDeptFk) {
            $departmentSummary = \DB::table('locum_agreements AS la')
                ->join('users AS u', 'la.user_id', '=', 'u.id')
                ->leftJoin('departments AS d', "u.$userDeptFk", '=', 'd.id')
                ->selectRaw("
                COALESCE(d.$deptNameColumn, 'Unassigned') AS department,
                COUNT(*) AS total,
                SUM(CASE WHEN la.status = 1 THEN 1 ELSE 0 END) AS pending_hr,
                SUM(CASE WHEN la.status = 2 THEN 1 ELSE 0 END) AS approved,
                SUM(CASE WHEN la.status IN (3,4) THEN 1 ELSE 0 END) AS rejected
            ")
                ->groupBy('department')
                ->orderBy('department')
                ->get();
        } else {
            $departmentSummary = collect([(object) [
                'department'  => 'All Departments',
                'total'       => (int) ($hrStatusCounts->total ?? 0),
                'pending_hr'  => (int) ($hrStatusCounts->pending_hr ?? 0),
                'approved'    => (int) ($hrStatusCounts->approved ?? 0),
                'rejected'    => (int) ($hrStatusCounts->rejected ?? 0),
            ]]);
        }

        // status map for badges in view
        $statusMap = [
            0 => ['label' => 'Pending to Line Manager',  'class' => 'bg-warning'],
            1 => ['label' => 'Pending to HR',           'class' => 'bg-info'],
            2 => ['label' => 'Approved',                'class' => 'bg-success'],
            3 => ['label' => 'Rejected by Line Manager', 'class' => 'bg-danger'],
            4 => ['label' => 'Rejected by HR',          'class' => 'bg-danger'],
        ];

        return view('locum_agreement.all_locum_agreement', compact(
            'agreements',
            'pending',
            'approved',
            'rejected',   // <-- add these
            'requests',
            'hrStatusCounts',
            'departmentSummary',
            'statusMap'
        ));
    }

    // public function viewAgreementRequest()
    // {
    //     $user = Auth::user();

    //     // Badge count for "Locum Requests"
    //     $requests = LocumRequest::whereHas('workflow', function ($query) use ($user) {
    //         $query->where('work_flow_completed', 0)
    //             ->whereHas('histories', function ($q) use ($user) {
    //                 $q->where('attended_by', $user->id)
    //                     ->where('status', 0)
    //                     ->whereNotNull('locum_request_status');
    //             });
    //     })
    //         ->with(['user', 'locumAgreement', 'workflow.histories' => function ($q) {
    //             $q->whereNotNull('locum_request_status')->latest();
    //         }])
    //         ->orderBy('created_at', 'desc')
    //         ->count();

    //     // === figure out how to read "department" safely ===
    //     $hasDepartmentsTable = Schema::hasTable('departments');

    //     // Try common department name columns (now includes dept_name)
    //     $deptNameColumn = null;
    //     if ($hasDepartmentsTable) {
    //         foreach (['dept_name', 'name', 'department_name', 'title'] as $col) {
    //             if (Schema::hasColumn('departments', $col)) {
    //                 $deptNameColumn = $col;
    //                 break;
    //             }
    //         }
    //     }

    //     // Detect the FK on users (department_id OR deptId)
    //     $userDeptFk = null;
    //     if (Schema::hasColumn('users', 'department_id')) {
    //         $userDeptFk = 'department_id';
    //     } elseif (Schema::hasColumn('users', 'deptId')) {
    //         $userDeptFk = 'deptId';
    //     }

    //     // === Agreements list (assigned to me, not completed) ===
    //     $agreementsQuery = DB::table('locum_agreements')
    //         ->join('workflows', 'locum_agreements.id', '=', 'workflows.locum_agreement_id')
    //         ->join('work_flow_histories', 'workflows.id', '=', 'work_flow_histories.work_flow_id')
    //         ->join('users', 'locum_agreements.user_id', '=', 'users.id');

    //     // Base selects
    //     $selects = [
    //         'locum_agreements.*',
    //         'work_flow_histories.attended_by',
    //         'work_flow_histories.remark',
    //         'users.fname',
    //         'users.lname',
    //         'users.ccbrt_code',
    //     ];

    //     // Join departments if we can; otherwise emit a fallback alias
    //     if ($hasDepartmentsTable && $deptNameColumn && $userDeptFk) {
    //         $agreementsQuery->leftJoin('departments', "users.$userDeptFk", '=', 'departments.id');
    //         $selects[] = DB::raw("COALESCE(departments.$deptNameColumn, '—') AS department_name");
    //     } else {
    //         $selects[] = DB::raw("'—' AS department_name");
    //     }

    //     $agreements = $agreementsQuery
    //         ->select($selects) // <-- single select avoids overriding addSelect
    //         ->where('work_flow_histories.attended_by', $user->id)
    //         ->where('work_flow_histories.status', '!=', 2)
    //         ->orderByDesc('locum_agreements.created_at')
    //         ->get();

    //     // === HR Summary (all agreements) ===
    //     $hrStatusCounts = DB::table('locum_agreements')
    //         ->selectRaw("
    //         SUM(CASE WHEN status = 0 THEN 1 ELSE 0 END) AS pending_line_manager,
    //         SUM(CASE WHEN status = 1 THEN 1 ELSE 0 END) AS pending_hr,
    //         SUM(CASE WHEN status = 2 THEN 1 ELSE 0 END) AS approved,
    //         SUM(CASE WHEN status IN (3,4) THEN 1 ELSE 0 END) AS rejected,
    //         COUNT(*) AS total
    //     ")
    //         ->first();

    //     // === Department Summary (if we can resolve department name) ===
    //     if ($hasDepartmentsTable && $deptNameColumn && $userDeptFk) {
    //         $departmentSummary = DB::table('locum_agreements AS la')
    //             ->join('users AS u', 'la.user_id', '=', 'u.id')
    //             ->leftJoin('departments AS d', "u.$userDeptFk", '=', 'd.id')
    //             ->selectRaw("
    //             COALESCE(d.$deptNameColumn, 'Unassigned') AS department,
    //             COUNT(*) AS total,
    //             SUM(CASE WHEN la.status = 1 THEN 1 ELSE 0 END) AS pending_hr,
    //             SUM(CASE WHEN la.status = 2 THEN 1 ELSE 0 END) AS approved,
    //             SUM(CASE WHEN la.status IN (3,4) THEN 1 ELSE 0 END) AS rejected
    //         ")
    //             ->groupBy('department')
    //             ->orderBy('department')
    //             ->get();
    //     } else {
    //         $departmentSummary = collect([(object) [
    //             'department'  => 'All Departments',
    //             'total'       => (int) ($hrStatusCounts->total ?? 0),
    //             'pending_hr'  => (int) ($hrStatusCounts->pending_hr ?? 0),
    //             'approved'    => (int) ($hrStatusCounts->approved ?? 0),
    //             'rejected'    => (int) ($hrStatusCounts->rejected ?? 0),
    //         ]]);
    //     }

    //     // status map for badges in view
    //     $statusMap = [
    //         0 => ['label' => 'Pending to Line Manager', 'class' => 'bg-warning'],
    //         1 => ['label' => 'Pending to HR',          'class' => 'bg-info'],
    //         2 => ['label' => 'Approved',               'class' => 'bg-success'],
    //         3 => ['label' => 'Rejected by Line Manager', 'class' => 'bg-danger'],
    //         4 => ['label' => 'Rejected by HR',         'class' => 'bg-danger'],
    //     ];

    //     return view('locum_agreement.all_locum_agreement', compact(
    //         'agreements',
    //         'requests',
    //         'hrStatusCounts',
    //         'departmentSummary',
    //         'statusMap'
    //     ));
    // }


    public function approve($id)
    {
        // Load agreement + workflow up front
        $locumAgreement = LocumAgreement::with(['workflow.histories'])->find($id);
        if (!$locumAgreement) {
            Log::error('Locum Agreement not found: ' . $id);
            return redirect()
                ->route('locum-requests.viewAgreementRequest')
                ->withErrors(['error' => 'Locum Agreement not found.']);
        }

        $workflow = $locumAgreement->workflow;
        if (!$workflow) {
            Log::error('Workflow missing on Locum Agreement: ' . $id);
            return redirect()
                ->route('locum-requests.viewAgreementRequest')
                ->withErrors(['error' => 'Workflow not configured for this agreement. Contact admin.']);
        }

        // My pending workflow step for LOCUM AGREEMENT
        $workflowHistory = $workflow->histories()
            ->where('attended_by', Auth::id())
            ->where('status', 0)
            ->whereNotNull('locum_agreement_status')
            ->first();

        if (!$workflowHistory) {
            Log::error('No pending workflow history for user ' . Auth::id() . ' and agreement ' . $id);
            return redirect()
                ->route('locum-requests.viewAgreementRequest')
                ->withErrors(['error' => 'No pending approval found for this agreement.']);
        }

        $user = Auth::user();

        DB::beginTransaction();
        try {
            // LINE MANAGER -> forward to the single assigned HR approver (approvelocum = 1)
            if ($user->hasRole('line-manager') && !$user->hasRole('hr')) {
                // Set agreement stage to "Pending HR"
                $locumAgreement->status = 1; // your map: 1 => Pending to HR
                $workflowHistory->locum_agreement_status = 1; // stage marker
                $workflowHistory->status = 1;                  // close my step

                // Resolve the HR approver ONCE per request
                static $hrApproverCache = null;
                if (!$hrApproverCache) {
                    $hrApproverCache = User::whereHas('roles', fn($q) => $q->where('name', 'hr'))
                        ->where('approvelocum', 1)
                        // If approver is department-specific, uncomment:
                        // ->when($locumAgreement->department_id ?? null, fn($q, $deptId) => $q->where('department_id', $deptId))
                        ->first();
                }

                if (!$hrApproverCache) {
                    DB::rollBack();
                    return redirect()
                        ->route('locum-requests.viewAgreementRequest')
                        ->withErrors(['error' => 'No HR approver found. Please contact admin.']);
                }

                // Create NEXT pending step assigned to the specific HR approver
                WorkFlowHistory::create([
                    'work_flow_id'             => $workflow->id,
                    'forwarded_by'             => $user->id,
                    'attended_by'              => $hrApproverCache->id,
                    'status'                   => 0, // pending
                    'remark'                   => 'Line manager approved and sent to HR approver',
                    'locum_agreement_status'   => 1, // still HR stage
                ]);

                // Notify the HR approver (if email present)
                if (!empty($hrApproverCache->email)) {
                    try {
                        Mail::to($hrApproverCache->email)->queue(new ApprovalRequestNotification($hrApproverCache, [
                            'forwarded_by' => $user->name,
                            'request'      => 'Locum Agreement',
                            'requestDate'  => now()->format('d F Y'),
                            'action'       => 'HR Approval Required',
                        ]));
                        Log::info('Email queued to HR approver: ' . $hrApproverCache->email);
                    } catch (\Throwable $e) {
                        Log::error('Failed to queue email to HR approver ' . $hrApproverCache->email . ': ' . $e->getMessage());
                    }
                } else {
                    Log::warning('HR approver ID ' . $hrApproverCache->id . ' has no email configured');
                }

                $locumAgreement->save();
                $workflowHistory->save();
            }
            // HR -> final approval
            elseif ($user->hasRole('hr')) {
                $locumAgreement->status = 2; // Approved
                $workflowHistory->locum_agreement_status = 2; // final stage code
                $workflowHistory->status = 1;                 // close my HR step

                // Just in case: close any other stray pending HR entries for this workflow
                WorkFlowHistory::where('work_flow_id', $workflow->id)
                    ->where('status', 0)
                    ->whereNotNull('locum_agreement_status')
                    ->where('id', '!=', $workflowHistory->id)
                    ->update(['status' => 1]);

                // Optional audit entry (closed)
                WorkFlowHistory::create([
                    'work_flow_id'           => $workflow->id,
                    'forwarded_by'           => $user->id,
                    'attended_by'            => null,
                    'status'                 => 1, // closed (not pending)
                    'remark'                 => 'Approved by HR',
                    'locum_agreement_status' => 2,
                ]);

                $locumAgreement->save();
                $workflowHistory->save();
            } else {
                DB::rollBack();
                Log::error('Unauthorized role for approval: ' . $user->id);
                return redirect()
                    ->route('locum-requests.viewAgreementRequest')
                    ->withErrors(['error' => 'You are not authorized to approve this agreement.']);
            }

            DB::commit();
            return redirect()
                ->route('locum-requests.viewAgreementRequest')
                ->with('success', 'Locum Agreement approved successfully.');
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Approval process failed: ' . $e->getMessage());
            return redirect()
                ->route('locum-requests.viewAgreementRequest')
                ->withErrors(['error' => 'An error occurred during approval. Please try again.']);
        }
    }




    public function reject(Request $request, $id)
    {
        // dd($request->all());
        $request->validate([
            'reason' => 'required|string|max:255',
        ]);
        // Add rejection reason to locum_agreements table
        // $locumAgreement->rejection_reason = $rejectionReason;
        $locumAgreement = LocumAgreement::find($id);
        $workflowHistory = $locumAgreement->workflow->histories()
            ->where('attended_by', Auth::id())
            ->where('status', 0)
            ->whereNotNull('locum_agreement_status')
            ->first();

        if (!$workflowHistory) {
            return redirect()->back()->withErrors(['error' => 'Workflow history not found.']);
        }

        $role = Auth::user()->getRoleNames()[0];
        $input = [
            'work_flow_id' => $locumAgreement->workflow->id,
            'forwarded_by' => Auth::id(),
            'status' => 2,
        ];

        if ($role === 'line-manager') {
            $locumAgreement->status = 3;
            $locumAgreement->rejection_status = $request->reason;
            $workflowHistory->locum_agreement_status = 3;
            $input = array_merge($input, [
                'attended_by' => Auth::id(),
                'remark' => 'Line manager rejected the agreement.',
                'locum_agreement_status' => 3,
            ]);
        } elseif ($role === 'hr') {
            $locumAgreement->status = 4;
            $locumAgreement->rejection_status = $request->reason;
            $workflowHistory->locum_agreement_status = 4;
            $input = array_merge($input, [
                'remark' => 'Rejected by HR',
                'locum_agreement_status' => 4,
            ]);
        }

        $locumAgreement->save();
        $workflowHistory->status = 1;
        $workflowHistory->save();
        WorkFlowHistory::create($input);

        return redirect()->back()->with('warning', 'Locum Agreement has been rejected.');
    }

    public function edit($id)
    {
        $user = Auth::user();
        $agreement = LocumAgreement::with('workflow.histories')->where('user_id', $user->id)->findOrFail($id);

        // Only allow editing of rejected agreements
        $isRejected = in_array((int)($agreement->status ?? 0), [3, 4]);

        if (!$isRejected) {
            return redirect()->route('locum-agreements.view')
                ->with('error', 'Only rejected agreements can be edited and resubmitted.');
        }

        // Check if 2 months have passed since rejection
        $rejectedAt = null;
        if ($agreement->workflow && $agreement->workflow->histories) {
            $rejectedHistory = $agreement->workflow->histories
                ->where('status', 2)
                ->whereIn('locum_agreement_status', [3, 4])
                ->first();

            if ($rejectedHistory) {
                $rejectedAt = $rejectedHistory->attend_date ?? $rejectedHistory->updated_at ?? $rejectedHistory->created_at;
            }
        }

        if ($rejectedAt) {
            $rejectedDate = $rejectedAt instanceof Carbon ? $rejectedAt : Carbon::parse($rejectedAt);
            $twoMonthsAgo = now()->subMonths(2);

            if ($rejectedDate->lt($twoMonthsAgo)) {
                return redirect()->route('locum-agreements.view')
                    ->with('error', 'This agreement was rejected more than 2 months ago and can no longer be edited or resubmitted.');
            }
        }

        return view('locum_agreement.edit', compact('agreement'));
    }

    public function update(Request $request, $id)
    {
        $user = Auth::user();
        $agreement = LocumAgreement::with('workflow.histories')->where('user_id', $user->id)->findOrFail($id);

        // Only allow updating of rejected agreements
        $isRejected = in_array((int)($agreement->status ?? 0), [3, 4]);

        if (!$isRejected) {
            return redirect()->route('locum-agreements.view')
                ->with('error', 'Only rejected agreements can be updated and resubmitted.');
        }

        // Check if 2 months have passed since rejection
        $rejectedAt = null;
        if ($agreement->workflow && $agreement->workflow->histories) {
            $rejectedHistory = $agreement->workflow->histories
                ->where('status', 2)
                ->whereIn('locum_agreement_status', [3, 4])
                ->first();

            if ($rejectedHistory) {
                $rejectedAt = $rejectedHistory->attend_date ?? $rejectedHistory->updated_at ?? $rejectedHistory->created_at;
            }
        }

        if ($rejectedAt) {
            $rejectedDate = $rejectedAt instanceof Carbon ? $rejectedAt : Carbon::parse($rejectedAt);
            $twoMonthsAgo = now()->subMonths(2);

            if ($rejectedDate->lt($twoMonthsAgo)) {
                return redirect()->route('locum-agreements.view')
                    ->with('error', 'This agreement was rejected more than 2 months ago and can no longer be edited or resubmitted.');
            }
        }

        // Validate input
        $validated = $request->validate([
            'education_level' => 'required|string|in:Certificate,Enrolled_Certificate,Diploma,Degree,Masters',
            'locum_rate'      => 'required|numeric|min:0',
            'start_date'      => 'required|date',
            'end_date'        => 'required|date|after:start_date',
        ]);

        // Enforce rate
        $expectedRate = match ($validated['education_level']) {
            'Certificate'          => 50000,
            'Enrolled_Certificate' => 60000,
            'Diploma'              => 80000,
            'Degree'               => 100000,
            'Masters'              => 120000,
            default                => 0,
        };
        if ((float)$validated['locum_rate'] !== (float)$expectedRate) {
            return back()->withErrors(['locum_rate' => 'Invalid locum rate for the selected education level.'])->withInput();
        }

        // Helpers
        $getHrApprover = function () {
            return User::whereHas('roles', fn($q) => $q->where('name', 'hr'))
                ->where('approvelocum', 1)
                ->orderBy('id')
                ->first();
        };

        $isLineManagerRequester = $user->hasRole('line-manager') && !$user->hasRole('hr');

        // Resolve recipients
        $lineManager = null;
        if (!$isLineManagerRequester) {
            $lineManager = User::whereHas('roles', fn($q) => $q->where('name', 'line-manager'))
                ->where('deptId', $user->deptId)
                ->first();

            if (!$lineManager) {
                return back()->withErrors(['line_manager' => 'No line manager found for your department. Please contact HR.'])->withInput();
            }
        }

        $hrApprover = null;
        if ($isLineManagerRequester) {
            $hrApprover = $getHrApprover();
            if (!$hrApprover) {
                return back()->withErrors(['hr_approver' => 'No HR approver (approvelocum=1) found. Please contact admin.'])->withInput();
            }
        }

        DB::beginTransaction();
        try {
            // Update agreement
            $agreement->update([
                'education_level' => $validated['education_level'],
                'locum_rate'      => $validated['locum_rate'],
                'start_date'      => $validated['start_date'],
                'end_date'        => $validated['end_date'],
                'status'          => $isLineManagerRequester ? 1 : 0, // Reset to initial status
                'rejection_status' => null, // Clear rejection status
            ]);

            // Reset workflow
            if ($agreement->workflow) {
                $workflow = $agreement->workflow;
                $workflow->update([
                    'work_flow_status'    => 0,
                    'work_flow_completed' => 0,
                ]);

                // Delete all old histories to prevent duplicates in approver queue
                // This ensures only the new pending history shows up
                WorkFlowHistory::where('work_flow_id', $workflow->id)->delete();
            } else {
                // Create new workflow if it doesn't exist
                $workflow = Workflow::create([
                    'user_id'             => $user->id,
                    'work_flow_status'    => 0,
                    'work_flow_completed' => 0,
                    'locum_agreement_id'  => $agreement->id,
                ]);
            }

            // Refresh workflow relationship
            $agreement->load('workflow');
            $workflow = $agreement->workflow;

            // Create new workflow history
            if ($isLineManagerRequester) {
                WorkFlowHistory::create([
                    'work_flow_id'           => $workflow->id,
                    'forwarded_by'           => $user->id,
                    'attended_by'            => $hrApprover->id,
                    'status'                 => 0,
                    'remark'                 => 'Resubmitted by Line Manager — pending HR approval.',
                    'locum_agreement_status' => 1, // HR stage
                ]);

                if (!empty($hrApprover->email)) {
                    Mail::to($hrApprover->email)->queue(new ApprovalRequestNotification($hrApprover, [
                        'forwarded_by' => $user->name ?? $user->fname ?? 'Requester',
                        'request'      => 'Locum Agreement (Resubmitted)',
                        'requestDate'  => now()->format('d F Y'),
                        'action'       => 'HR Approval Required',
                    ]));
                }
            } else {
                WorkFlowHistory::create([
                    'work_flow_id'           => $workflow->id,
                    'forwarded_by'           => $user->id,
                    'attended_by'            => $lineManager->id,
                    'status'                 => 0,
                    'remark'                 => 'Locum Agreement resubmitted for approval.',
                    'locum_agreement_status' => 0, // LM stage
                ]);

                if (!empty($lineManager->email)) {
                    Mail::to($lineManager->email)->queue(new ApprovalRequestNotification($lineManager, [
                        'forwarded_by' => $user->name ?? $user->fname ?? 'Requester',
                        'request'      => 'Locum Agreement (Resubmitted)',
                        'requestDate'  => now()->format('d F Y'),
                    ]));
                }
            }

            DB::commit();
            return redirect()->route('locum-agreements.view')
                ->with('success', 'Locum Agreement updated and resubmitted successfully.');
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Failed to update Locum Agreement: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return back()->withErrors(['error' => 'An error occurred while resubmitting your request. Please try again.'])->withInput();
        }
    }
}
