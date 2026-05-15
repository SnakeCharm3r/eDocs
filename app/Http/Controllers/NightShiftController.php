<?php

namespace App\Http\Controllers;

use App\Models\Departments;
use App\Models\Hec;
use App\Models\NightShiftClaim;
use App\Models\NightShiftClaimEmployee;
use App\Models\Platform;
use App\Models\Unit;
use App\Models\User;
use App\Models\Workflow;
use App\Models\WorkFlowHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use App\Exports\NightShiftClaimsExport;
use App\Http\Controllers\SettingsController;
use App\Mail\NightShiftApprovalRequest;
use App\Mail\NightShiftStatusUpdate;
use Illuminate\Support\Facades\Mail;
use Maatwebsite\Excel\Facades\Excel;

class NightShiftController extends Controller
{
    /* ───────────────────────────────────────────────
     |  MY CLAIMS (submitter view)
     ─────────────────────────────────────────────── */
    public function index()
    {
        $user   = Auth::user();
        $claims = NightShiftClaim::where('submitted_by', $user->id)
                      ->with(['employees', 'department', 'workflow.histories.attendedBy'])
                      ->latest()
                      ->get();

        $ratePerDay = (float) SettingsController::getSetting('night_allowance_amount_per_day', 0);

        $pillCounts = [
            'Pending'  => $claims->where('status', 'pending')->count(),
            'Approved' => $claims->where('status', 'approved')->count(),
            'Rejected' => $claims->where('status', 'rejected')->count(),
        ];

        return view('night_shift.index', compact('claims', 'ratePerDay', 'pillCounts'));
    }

    /* ───────────────────────────────────────────────
     |  CREATE FORM
     ─────────────────────────────────────────────── */
    public function create()
    {
        $user      = Auth::user();
        $platforms = Platform::orderBy('name')->get(['id', 'name', 'manager_user_id']);
        $units     = Unit::orderBy('name')->get(['id', 'platform_id', 'name', 'incharge_user_id']);

        $months = [
            'January','February','March','April','May','June',
            'July','August','September','October','November','December',
        ];

        $previousMonth = now()->subMonth()->format('F');
        $previousYear  = (int) now()->subMonth()->format('Y');

        // Deadline check — same logic as locum/on-call
        $today       = now('Africa/Dar_es_Salaam');
        $deadlineDay = (int) SettingsController::getSetting('night_shift_submission_deadline', 5);
        $deadline    = $today->copy()->startOfMonth()->addDays($deadlineDay - 1)->endOfDay();
        if ($today->gt($deadline)) {
            return redirect()->route('night-shift.index')
                ->with('error', "Submission for {$previousMonth} {$previousYear} is closed. The deadline was {$deadline->format('j F Y')}.");
        }

        $nightAllowanceAmountPerDay = (float) SettingsController::getSetting('night_allowance_amount_per_day', 0);

        $dept    = Departments::with('hec')->find($user->deptId);
        $flowKey = $this->resolveDepartmentApprovalFlow($dept);

        return view('night_shift.create', compact(
            'user', 'months', 'platforms', 'units',
            'previousMonth', 'previousYear', 'nightAllowanceAmountPerDay', 'flowKey'
        ));
    }

    /* ───────────────────────────────────────────────
     |  STORE
     ─────────────────────────────────────────────── */
    public function store(Request $request)
    {
        $request->validate([
            'department_id'     => 'nullable|exists:departments,id',
            'month'             => 'required|string',
            'year'              => 'required|integer|min:2020|max:2100',
            'type_of_allowance' => 'required|string|max:100',
            'worked_days'       => 'nullable|array',
        ]);

        $workedDays = collect($request->worked_days ?? [])
            ->filter(fn($d) => !empty($d['worked']));

        if ($workedDays->isEmpty()) {
            return back()->withErrors(['worked_days' => 'Please select at least one worked day.'])->withInput();
        }

        $user   = Auth::user();
        $deptId = $request->department_id ?? $user->deptId;
        $dept   = Departments::with('hec')->find($deptId);

        if (!$dept) {
            return back()->withErrors(['department_id' => 'Department not found. Please contact the administrator.'])->withInput();
        }

        // ── Block future-month submissions ────────────────────────────
        try {
            $requestedMonth = \Carbon\Carbon::createFromFormat('F Y', $request->month . ' ' . $request->year)->startOfMonth();
        } catch (\Throwable) {
            return back()->withErrors(['month' => 'Invalid month or year selected.'])->withInput();
        }
        if ($requestedMonth->startOfMonth()->gt(\Carbon\Carbon::now()->startOfMonth())) {
            return back()->withErrors(['month' => 'You cannot submit a night allowance claim for a future month.'])->withInput();
        }

        // ── Block duplicate claims for the same month ─────────────────
        $duplicate = NightShiftClaim::where('submitted_by', $user->id)
            ->where('month', $request->month)
            ->where('year', $request->year)
            ->first();
        if ($duplicate) {
            return back()->withErrors(['month' => "You have already submitted a night allowance claim for {$request->month} {$request->year}."])->withInput();
        }

        // ── Deadline check ────────────────────────────────────────────
        $todayTz     = now('Africa/Dar_es_Salaam');
        $deadlineDay = (int) SettingsController::getSetting('night_shift_submission_deadline', 5);
        $deadline    = $todayTz->copy()->startOfMonth()->addDays($deadlineDay - 1)->endOfDay();
        if ($todayTz->gt($deadline)) {
            return back()->withErrors([
                'month' => "Submission is closed. The deadline was {$deadline->format('j F Y')}.",
            ])->withInput();
        }

        // ── Block future-day selections ───────────────────────────────
        $today = \Carbon\Carbon::today();
        foreach ($workedDays as $iso => $day) {
            try {
                $dayDate = \Carbon\Carbon::parse($iso)->startOfDay();
            } catch (\Throwable) {
                continue;
            }
            if ($dayDate->gt($today)) {
                return back()->withErrors([
                    'worked_days' => "You cannot claim a future date ({$dayDate->format('d M Y')}). Only past or today's dates are allowed.",
                ])->withInput();
            }
        }

        // ── Detect department approval flow ──────────────────────────
        $flowKey       = $this->resolveDepartmentApprovalFlow($dept);
        $isLineManager = $user->hasAnyRole(['line-manager', 'line_manager', 'acting-line-manager']);
        $needsHec      = in_array($flowKey, ['three_level', 'incharge_lm_hec']);

        // Check if requester is an in-charge or platform manager for any of the selected units/platforms
        $isIncharge = false;
        $isPlatformManager = false;
        if (in_array($flowKey, ['incharge_platform', 'incharge_lm_hec'])) {
            $selectedUnitInchargeIds = $workedDays->pluck('unit_id')->filter()->unique()
                ->map(fn($id) => Unit::find($id)?->incharge_user_id)->filter()->unique();
            $isIncharge = $selectedUnitInchargeIds->contains($user->id);
        }
        if ($flowKey === 'incharge_platform') {
            $selectedPlatformManagerIds = $workedDays->pluck('platform_id')->filter()->unique()
                ->map(fn($pid) => Platform::find($pid)?->manager_user_id)->filter()->unique();
            $isPlatformManager = $selectedPlatformManagerIds->contains($user->id);
        }

        // ── Resolve approvers BEFORE the transaction so we can return errors ──
        $hrUser = User::whereHas('roles', fn($q) => $q->where('name', 'hr'))
            ->when(Schema::hasColumn('users', 'approvelocum'), fn($q) => $q->where('approvelocum', 1))
            ->first();

        if (!$hrUser) {
            return back()->withErrors([
                'worked_days' => 'No HR approver is configured. Please contact the administrator.',
            ])->withInput();
        }

        $lmUser = null;
        if (!$isLineManager) {
            $lmUser = User::whereHas('roles', fn($q) => $q->whereIn('name', ['line-manager', 'line_manager', 'acting-line-manager']))
                ->where('deptId', $deptId)
                ->where('id', '!=', $user->id)
                ->first();
        }

        $hecUser = null;
        if ($needsHec) {
            $hecUser = $this->resolveHecApprover($dept);
            if (!$hecUser) {
                return back()->withErrors([
                    'worked_days' => "Your department ({$dept->dept_name}) requires HEC approval but no HEC approver is configured. Please contact the administrator.",
                ])->withInput();
            }
        }

        // ── For incharge flows: every selected unit must have an In-Charge ──────
        if (in_array($flowKey, ['incharge_platform', 'incharge_lm_hec'])) {
            foreach ($workedDays->pluck('unit_id')->filter()->unique() as $unitId) {
                $unit = Unit::find($unitId);
                if (!$unit || !$unit->incharge_user_id) {
                    $unitName = $unit?->name ?? "ID #{$unitId}";
                    return back()->withErrors([
                        'worked_days' => "Unit \"{$unitName}\" has no In-Charge assigned. Please contact the administrator to configure one before submitting.",
                    ])->withInput();
                }
            }
        }

        // ── For incharge_platform flow: every selected platform must have a manager ──
        if ($flowKey === 'incharge_platform') {
            foreach ($workedDays->pluck('platform_id')->filter()->unique() as $platformId) {
                $platform = Platform::find($platformId);
                if (!$platform || !$platform->manager_user_id) {
                    $platformName = $platform?->name ?? "ID #{$platformId}";
                    return back()->withErrors([
                        'worked_days' => "Platform \"{$platformName}\" has no Platform Manager assigned. Please contact the administrator to configure one before submitting.",
                    ])->withInput();
                }
            }
        }

        // ── Determine first step approvers (array — supports parallel incharge notify) ──
        // LM submitting → skip LM, go to HEC or HR
        $firstApproverIds = [];
        $firstStep        = '';
        $firstRemark      = '';

        if ($isLineManager) {
            if ($needsHec && $hecUser) {
                $firstApproverIds = [$hecUser->id];
                $firstStep        = 'HEC Approval';
                $firstRemark      = 'LM submitted — awaiting HEC approval.';
            } else {
                $firstApproverIds = [$hrUser->id];
                $firstStep        = 'HR Approval';
                $firstRemark      = 'LM submitted — awaiting HR approval.';
            }
        } else {
            switch ($flowKey) {
                case 'three_level':
                    if ($lmUser) {
                        $firstApproverIds = [$lmUser->id];
                        $firstStep        = 'Line Manager Approval';
                        $firstRemark      = 'Awaiting Line Manager approval.';
                    } elseif ($hecUser) {
                        $firstApproverIds = [$hecUser->id];
                        $firstStep        = 'HEC Approval';
                        $firstRemark      = 'Awaiting HEC approval (no Line Manager found in department).';
                    } else {
                        $firstApproverIds = [$hrUser->id];
                        $firstStep        = 'HR Approval';
                        $firstRemark      = 'Awaiting HR approval (no Line Manager or HEC found).';
                    }
                    break;

                case 'incharge_lm_hec':
                case 'incharge_platform':
                    // All unique incharges notified simultaneously (parallel)
                    // But exclude the requester if they are an in-charge
                    $inchargeIds = $workedDays->pluck('unit_id')->filter()->unique()
                        ->map(fn($id) => Unit::find($id)?->incharge_user_id)->filter()->unique()
                        ->reject(fn($id) => $id == $user->id)->values();

                    if ($isIncharge && $inchargeIds->isEmpty()) {
                        // Requester is the only in-charge — skip to next level
                        if ($flowKey === 'incharge_platform') {
                            // Skip to Platform Manager(s), but exclude requester if they are also a PM
                            $pmIds = $workedDays->pluck('platform_id')->filter()->unique()
                                ->map(fn($pid) => Platform::find($pid)?->manager_user_id)->filter()->unique()
                                ->reject(fn($id) => $id == $user->id)->values();
                            if ($pmIds->isNotEmpty()) {
                                $firstApproverIds = $pmIds->all();
                                $firstStep        = 'Platform Manager Approval';
                                $firstRemark      = 'In-Charge submitted — awaiting Platform Manager approval.';
                                break;
                            }
                        }
                        // Skip to Line Manager
                        if ($lmUser) {
                            $firstApproverIds = [$lmUser->id];
                            $firstStep        = 'Line Manager Approval';
                            $firstRemark      = 'In-Charge submitted — awaiting Line Manager approval.';
                        } elseif ($needsHec && $hecUser) {
                            $firstApproverIds = [$hecUser->id];
                            $firstStep        = 'HEC Approval';
                            $firstRemark      = 'In-Charge submitted — awaiting HEC approval.';
                        } else {
                            $firstApproverIds = [$hrUser->id];
                            $firstStep        = 'HR Approval';
                            $firstRemark      = 'In-Charge submitted — awaiting HR approval.';
                        }
                    } elseif ($isPlatformManager && $inchargeIds->isEmpty()) {
                        // Requester is Platform Manager (not in-charge) — skip to LM
                        if ($lmUser) {
                            $firstApproverIds = [$lmUser->id];
                            $firstStep        = 'Line Manager Approval';
                            $firstRemark      = 'Platform Manager submitted — awaiting Line Manager approval.';
                        } elseif ($needsHec && $hecUser) {
                            $firstApproverIds = [$hecUser->id];
                            $firstStep        = 'HEC Approval';
                            $firstRemark      = 'Platform Manager submitted — awaiting HEC approval.';
                        } else {
                            $firstApproverIds = [$hrUser->id];
                            $firstStep        = 'HR Approval';
                            $firstRemark      = 'Platform Manager submitted — awaiting HR approval.';
                        }
                    } elseif ($inchargeIds->isNotEmpty()) {
                        $firstApproverIds = $inchargeIds->all();
                        $firstStep        = 'Incharge Approval';
                        $firstRemark      = 'Awaiting In-Charge approval.';
                    } elseif ($lmUser) {
                        $firstApproverIds = [$lmUser->id];
                        $firstStep        = 'Line Manager Approval';
                        $firstRemark      = 'Awaiting Line Manager approval (no In-Charge found).';
                    } else {
                        $firstApproverIds = [$hrUser->id];
                        $firstStep        = 'HR Approval';
                        $firstRemark      = 'Awaiting HR approval.';
                    }
                    break;

                case 'standard':
                default:
                    if ($lmUser) {
                        $firstApproverIds = [$lmUser->id];
                        $firstStep        = 'Line Manager Approval';
                        $firstRemark      = 'Awaiting Line Manager approval.';
                    } else {
                        $firstApproverIds = [$hrUser->id];
                        $firstStep        = 'HR Approval';
                        $firstRemark      = 'Awaiting HR approval (no Line Manager found in department).';
                    }
                    break;
            }
        }

        // ── All approvers confirmed — now write to DB ─────────────────
        DB::beginTransaction();
        try {
            $claim = NightShiftClaim::create([
                'department_id'       => $deptId,
                'submitted_by'        => $user->id,
                'month'               => $request->month,
                'year'                => $request->year,
                'type_of_allowance'   => $request->type_of_allowance,
                'status'              => 'pending',
                'number_of_employees' => 1,
                'total_days_worked'   => $workedDays->count(),
            ]);

            foreach ($workedDays as $iso => $day) {
                NightShiftClaimEmployee::create([
                    'night_shift_claim_id' => $claim->id,
                    'user_id'              => $user->id,
                    'emp_code'             => $user->ccbrt_code ?? '—',
                    'employee_name'        => trim($user->fname.' '.($user->mname ?? '').' '.$user->lname),
                    'days_on_duty'         => 1,
                    'date'                 => $iso,
                    'platform_id'          => $day['platform_id'] ?: null,
                    'unit_id'              => !empty($day['unit_id']) ? $day['unit_id'] : null,
                    'hours'                => !empty($day['hours']) ? (float) $day['hours'] : null,
                ]);
            }

            $workflow = Workflow::create([
                'user_id'              => $user->id,
                'night_shift_claim_id' => $claim->id,
                'work_flow_status'     => 0,
                'work_flow_completed'  => 0,
            ]);

            // Create one WorkFlowHistory row per first-step approver (parallel notify)
            foreach (array_unique($firstApproverIds) as $approverId) {
                WorkFlowHistory::create([
                    'work_flow_id'  => $workflow->id,
                    'forwarded_by'  => $user->id,
                    'attended_by'   => $approverId,
                    'step_name'     => $firstStep,
                    'action_taken'  => 'Forwarded',
                    'status'        => 0,
                    'remark'        => $firstRemark,
                ]);
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Failed to submit: '.$e->getMessage()])->withInput();
        }

        // ── Send email notifications AFTER commit ─────────────────────
        try {
            foreach (array_unique($firstApproverIds) as $approverId) {
                $approverUser = User::find($approverId);
                if ($approverUser && $approverUser->email) {
                    Mail::to($approverUser->email)->queue(new NightShiftApprovalRequest(
                        $claim, $workflow, $user, $approverUser
                    ));
                }
            }
        } catch (\Throwable $e) {
            \Log::warning('NightShift: Failed to send approval request email — '.$e->getMessage());
        }

        $approverCount = count(array_unique($firstApproverIds));
        $stepLabel     = $approverCount > 1 ? "{$firstStep} ({$approverCount} approvers notified)" : $firstStep;
        if ($approverCount === 1) {
            $approver     = User::find($firstApproverIds[0]);
            $approverName = $approver ? trim($approver->fname.' '.$approver->lname) : 'approver';
            $stepLabel    = "sent to {$approverName} for {$firstStep}";
        } else {
            $stepLabel = "{$approverCount} In-Charge approvers notified for {$firstStep}";
        }
        return redirect()->route('night-shift.index')
            ->with('success', "Night Allowances claim submitted and {$stepLabel}.");
    }

    /* ───────────────────────────────────────────────
     |  DETAILS (JSON for modal)
     ─────────────────────────────────────────────── */
    public function details(NightShiftClaim $nightShift)
    {
        $nightShift->load([
            'submitter', 'approver', 'department',
            'employees.platform', 'employees.unit',
            'workflow.histories.attendedBy',
            'workflow.histories.forwardedBy',
        ]);

        $wf          = $nightShift->workflow;
        $histories   = $wf ? $wf->histories->sortBy('id')->values() : collect();
        $pendingStep = $histories->firstWhere('status', 0);

        $steps = $histories->map(fn($h) => [
            'step_name'    => $h->step_name,
            'attended_by'  => $h->attendedBy  ? trim($h->attendedBy->fname.' '.$h->attendedBy->lname)  : '—',
            'forwarded_by' => $h->forwardedBy ? trim($h->forwardedBy->fname.' '.$h->forwardedBy->lname) : '—',
            'status'       => (int) $h->status,  // 0=pending,1=approved,2=rejected
            'acted_at'     => $h->attend_date ? \Carbon\Carbon::parse($h->attend_date)->format('d M Y H:i') : '—',
            'rejection_reason' => $h->rejection_reason,
        ])->values();

        $days = $nightShift->employees->sortBy('date')->values()->map(fn($e) => [
            'iso'      => $e->date ?? null,
            'date'     => $e->date ? \Carbon\Carbon::parse($e->date)->format('d M Y') : '—',
            'weekday'  => $e->date ? \Carbon\Carbon::parse($e->date)->format('l') : '',
            'platform' => $e->platform?->name ?? '—',
            'unit'     => $e->unit?->name ?? '—',
            'hours'    => $e->hours,
        ]);

        $ratePerDay = (float) SettingsController::getSetting('night_allowance_amount_per_day', 0);

        return response()->json([
            'id'           => $nightShift->id,
            'month'        => $nightShift->month.' / '.$nightShift->year,
            'status'       => $nightShift->status,
            'submitted_at' => $nightShift->created_at->format('d M Y H:i'),
            'approved_at'  => $nightShift->approved_at?->format('d M Y H:i') ?? null,
            'pending_with' => $pendingStep && $pendingStep->attendedBy
                ? trim($pendingStep->attendedBy->fname.' '.$pendingStep->attendedBy->lname) : null,
            'pending_step' => $pendingStep?->step_name,
            'steps'        => $steps,
            'days'         => $days,
            'total_days'   => $nightShift->total_days_worked,
            'total_hours'  => $nightShift->employees->sum('hours'),
            'amount'       => $nightShift->total_days_worked * $ratePerDay,
            'rate_per_day' => $ratePerDay,
        ]);
    }

    /* ───────────────────────────────────────────────
     |  SHOW (detail view)
     ─────────────────────────────────────────────── */
    public function show(NightShiftClaim $nightShift)
    {
        $nightShift->load([
            'submitter', 'approver', 'department',
            'employees.platform', 'employees.unit',
            'workflow.histories.attendedBy',
            'workflow.histories.forwardedBy',
        ]);
        return view('night_shift.show', compact('nightShift'));
    }

    /* ───────────────────────────────────────────────
     |  APPROVER INDEX
     ─────────────────────────────────────────────── */
    public function approveIndex()
    {
        $user = Auth::user();

        // Only claims where this user has a pending step awaiting their action
        $pendingClaims = NightShiftClaim::whereHas('workflow.histories', function ($q) use ($user) {
                $q->where('attended_by', $user->id)->where('status', 0);
            })
            ->with(['submitter', 'department', 'employees', 'workflow.histories.attendedBy'])
            ->latest()
            ->get();

        return view('night_shift.approve_index', compact('pendingClaims'));
    }

    /* ───────────────────────────────────────────────
     |  APPROVE ACTION (workflow step)
     ─────────────────────────────────────────────── */
    public function approve(NightShiftClaim $nightShift)
    {
        $user     = Auth::user();
        $workflow = $nightShift->workflow;

        if (!$workflow) {
            return back()->with('error', 'No workflow found for this claim.');
        }

        $pendingStep = $workflow->histories()
            ->where('attended_by', $user->id)
            ->where('status', 0)
            ->first();

        if (!$pendingStep) {
            return back()->with('error', 'No pending step assigned to you for this claim.');
        }

        // Load dept for flow detection
        $dept    = Departments::with('hec')->find($nightShift->department_id);
        $flowKey = $this->resolveDepartmentApprovalFlow($dept);
        $needsHec = in_array($flowKey, ['three_level', 'incharge_lm_hec']);

        $hrUser = User::whereHas('roles', fn($q) => $q->where('name', 'hr'))
            ->when(Schema::hasColumn('users', 'approvelocum'), fn($q) => $q->where('approvelocum', 1))
            ->first();

        $emailJobs = [];
        DB::transaction(function () use ($pendingStep, $workflow, $nightShift, $user, $dept, $flowKey, $needsHec, $hrUser, &$emailJobs) {
            $pendingStep->update(['status' => 1, 'attend_date' => now(), 'action_taken' => 'Approved']);
            $emailJobs = $this->advanceWorkflow($workflow, $nightShift->fresh('employees'), $pendingStep, $user, $dept, $flowKey, $needsHec, $hrUser);
        });

        // Send emails AFTER commit
        $this->sendWorkflowEmails($emailJobs, $nightShift, $workflow, $user);

        return redirect()->route('night-shift.approve.index')
                         ->with('success', "Claim #{$nightShift->id} approved successfully.");
    }

    /* ───────────────────────────────────────────────
     |  REPORT (HR/admin view of approved claims)
     ─────────────────────────────────────────────── */
    public function report(Request $request)
    {
        $ratePerDay   = (float) SettingsController::getSetting('night_allowance_amount_per_day', 0);
        $allDepartments = \App\Models\Departments::orderBy('dept_name')->get();

        // ── Quick filter presets ──────────────────────────────────────
        $quickFilter = $request->query('quick_filter');
        $now = \Carbon\Carbon::now();

        if ($quickFilter === 'this_month') {
            $startDate = $now->copy()->startOfMonth();
            $endDate   = $now->copy()->endOfMonth();
        } elseif ($quickFilter === 'last_month') {
            $startDate = $now->copy()->subMonth()->startOfMonth();
            $endDate   = $now->copy()->subMonth()->endOfMonth();
        } elseif ($quickFilter === 'last_3_months') {
            $startDate = $now->copy()->subMonths(2)->startOfMonth();
            $endDate   = $now->copy()->endOfMonth();
        } elseif ($quickFilter === 'this_year') {
            $startDate = $now->copy()->startOfYear();
            $endDate   = $now->copy()->endOfMonth();
        } else {
            $fromYear  = (int) ($request->query('from_year',  $now->year));
            $fromMonth = (int) ($request->query('from_month', 1));
            $toYear    = (int) ($request->query('to_year',    $now->year));
            $toMonth   = (int) ($request->query('to_month',   $now->month));
            $startDate = \Carbon\Carbon::create($fromYear, $fromMonth, 1)->startOfMonth();
            $endDate   = \Carbon\Carbon::create($toYear,  $toMonth,   1)->endOfMonth();
        }

        $selectedDept = $request->query('analytics_dept', '_all');

        // ── Load all approved claims ──────────────────────────────────
        $allApproved = NightShiftClaim::where('status', 'approved')
            ->with(['submitter.department', 'department', 'workflow.histories'])
            ->orderBy('approved_at')
            ->get();

        // ── Monthly trends ────────────────────────────────────────────
        $monthlyTrends = [];
        $cursor = $startDate->copy()->startOfMonth();
        while ($cursor->lte($endDate)) {
            $monthName = $cursor->format('M Y');
            $monthKey  = $cursor->format('Y-m');
            $yr = $cursor->year;
            $mo = $cursor->format('F'); // e.g. "March"

            $monthClaims = $allApproved->filter(function ($c) use ($yr, $mo) {
                return (int) $c->year === $yr && $c->month === $mo;
            });

            if ($selectedDept !== '_all') {
                $monthClaims = $monthClaims->filter(fn($c) => ($c->department?->dept_name ?? 'N/A') === $selectedDept);
            }

            $monthlyTrends[$monthKey] = [
                'label'  => $monthName,
                'count'  => $monthClaims->count(),
                'amount' => $monthClaims->sum(fn($c) => $c->total_days_worked * $ratePerDay),
            ];
            $cursor->addMonth();
        }

        // ── Department trends (within date range) ─────────────────────
        $rangeFiltered = $allApproved->filter(function ($c) use ($startDate, $endDate) {
            $date = $c->approved_at ? \Carbon\Carbon::parse($c->approved_at) : null;
            return $date && $date->gte($startDate) && $date->lte($endDate);
        });

        $deptTrends = $rangeFiltered
            ->groupBy(fn($c) => $c->department?->dept_name ?? 'N/A')
            ->map(function ($rows) use ($ratePerDay) {
                $total = $rows->sum(fn($c) => $c->total_days_worked * $ratePerDay);
                return [
                    'total_requests'  => $rows->count(),
                    'total_employees' => $rows->pluck('submitted_by')->unique()->count(),
                    'total_days'      => $rows->sum('total_days_worked'),
                    'total_amount'    => $total,
                    'avg_per_request' => $rows->count() > 0 ? round($total / $rows->count(), 2) : 0,
                ];
            })
            ->sortByDesc('total_amount')
            ->take(10);

        $analytics = [
            'monthly_trends'    => $monthlyTrends,
            'department_trends' => $deptTrends,
        ];

        // ── Table: ALL claims with pending step info ──────────────────
        $actionedRequests = NightShiftClaim::with([
                'submitter', 'department',
                'workflow.histories.attendedBy',
            ])
            ->orderBy('created_at', 'desc')
            ->get();

        return view('night_shift.report', compact(
            'analytics', 'actionedRequests', 'allDepartments', 'ratePerDay',
            'startDate', 'endDate', 'selectedDept', 'quickFilter'
        ));
    }

    public function reportExport(Request $request)
    {
        $now = \Carbon\Carbon::now();
        $quickFilter = $request->query('quick_filter');

        if ($quickFilter === 'this_month') {
            $startDate = $now->copy()->startOfMonth(); $endDate = $now->copy()->endOfMonth();
        } elseif ($quickFilter === 'last_month') {
            $startDate = $now->copy()->subMonth()->startOfMonth(); $endDate = $now->copy()->subMonth()->endOfMonth();
        } elseif ($quickFilter === 'last_3_months') {
            $startDate = $now->copy()->subMonths(2)->startOfMonth(); $endDate = $now->copy()->endOfMonth();
        } elseif ($quickFilter === 'this_year') {
            $startDate = $now->copy()->startOfYear(); $endDate = $now->copy()->endOfMonth();
        } else {
            $fromYear  = (int) ($request->query('from_year',  $now->year));
            $fromMonth = (int) ($request->query('from_month', 1));
            $toYear    = (int) ($request->query('to_year',    $now->year));
            $toMonth   = (int) ($request->query('to_month',   $now->month));
            $startDate = \Carbon\Carbon::create($fromYear, $fromMonth, 1)->startOfMonth();
            $endDate   = \Carbon\Carbon::create($toYear,  $toMonth,   1)->endOfMonth();
        }

        $claims = NightShiftClaim::where('status', 'approved')
            ->with(['submitter', 'department'])
            ->whereBetween('approved_at', [$startDate, $endDate])
            ->when($request->filled('department') && $request->department !== '_all', function ($q) use ($request) {
                $dept = \App\Models\Departments::where('dept_name', $request->department)->first();
                if ($dept) $q->where('department_id', $dept->id);
            })
            ->orderBy('approved_at', 'desc')
            ->get();

        $ratePerDay = (float) SettingsController::getSetting('night_allowance_amount_per_day', 0);
        $fileName   = "Night_Allowances_{$startDate->format('MY')}_{$endDate->format('MY')}_" . now()->format('Ymd_His') . '.xlsx';

        return Excel::download(new NightShiftClaimsExport($claims, $ratePerDay), $fileName);
    }

    /* ───────────────────────────────────────────────
     |  REJECT ACTION (workflow step)
     ─────────────────────────────────────────────── */
    public function reject(Request $request, NightShiftClaim $nightShift)
    {
        $request->validate([
            'rejection_reason' => 'required|string|min:5',
        ]);

        $user     = Auth::user();
        $workflow = $nightShift->workflow;

        if (!$workflow) {
            return back()->with('error', 'No workflow found for this claim.');
        }

        $pendingStep = $workflow->histories()
            ->where('attended_by', $user->id)
            ->where('status', 0)
            ->first();

        if (!$pendingStep) {
            return back()->with('error', 'No pending step assigned to you for this claim.');
        }

        DB::transaction(function () use ($pendingStep, $workflow, $nightShift, $user, $request) {

            $pendingStep->update([
                'status'           => 2,
                'attend_date'      => now(),
                'action_taken'     => 'Rejected',
                'rejection_reason' => $request->rejection_reason,
            ]);

            $workflow->update([
                'work_flow_completed' => 1,
                'work_flow_status'    => 2,
            ]);

            $nightShift->update([
                'status'           => 'rejected',
                'rejection_reason' => $request->rejection_reason,
                'approved_by'      => $user->id,
                'approved_at'      => now(),
            ]);
        });

        // ── Send rejection email to submitter ─────────────────────────
        try {
            $submitter = User::find($workflow->user_id);
            if ($submitter && $submitter->email) {
                Mail::to($submitter->email)->queue(new NightShiftStatusUpdate(
                    $nightShift->fresh(), 'rejected', $user, $request->rejection_reason, $pendingStep->step_name
                ));
            }
        } catch (\Throwable $e) {
            \Log::warning('NightShift: Failed to send rejection email — '.$e->getMessage());
        }

        return redirect()->route('night-shift.approve.index')
                         ->with('success', "Claim #{$nightShift->id} rejected.");
    }

    /* ───────────────────────────────────────────────
     |  EDIT FORM (rejected claim, within 1 month)
     ─────────────────────────────────────────────── */
    public function edit(NightShiftClaim $nightShift)
    {
        $user = Auth::user();

        if ($nightShift->submitted_by !== $user->id) {
            abort(403);
        }

        if ($nightShift->status !== 'rejected') {
            return redirect()->route('night-shift.index')
                ->with('error', 'Only rejected claims can be edited and resubmitted.');
        }

        if ($nightShift->created_at->addMonth()->isPast()) {
            return redirect()->route('night-shift.index')
                ->with('error', 'This claim can no longer be edited. The 1-month edit window has expired.');
        }

        $nightShift->load(['employees.platform', 'employees.unit', 'workflow.histories']);

        $platforms = Platform::orderBy('name')->get(['id', 'name', 'manager_user_id']);
        $units     = Unit::orderBy('name')->get(['id', 'platform_id', 'name', 'incharge_user_id']);

        $months = [
            'January','February','March','April','May','June',
            'July','August','September','October','November','December',
        ];

        $nightAllowanceAmountPerDay = (float) SettingsController::getSetting('night_allowance_amount_per_day', 0);

        $editDept = Departments::with('hec')->find($nightShift->department_id ?? $user->deptId);
        $flowKey  = $this->resolveDepartmentApprovalFlow($editDept);

        // Rejection reason from last rejected workflow step
        $rejectionReason = $nightShift->workflow?->histories->where('status', 2)->last()?->rejection_reason
            ?? $nightShift->rejection_reason;

        // Existing days keyed by ISO date
        $existingDays = $nightShift->employees->keyBy(fn($e) => $e->date)->map(fn($e) => [
            'platform_id' => $e->platform_id,
            'unit_id'     => $e->unit_id,
            'hours'       => $e->hours,
        ])->toArray();

        $expiresAt = $nightShift->created_at->addMonth();

        return view('night_shift.edit', compact(
            'nightShift', 'user', 'months', 'platforms', 'units',
            'nightAllowanceAmountPerDay', 'rejectionReason', 'existingDays', 'expiresAt', 'flowKey'
        ));
    }

    /* ───────────────────────────────────────────────
     |  RESUBMIT (update rejected claim & re-route)
     ─────────────────────────────────────────────── */
    public function resubmit(Request $request, NightShiftClaim $nightShift)
    {
        $user = Auth::user();

        if ($nightShift->submitted_by !== $user->id) {
            abort(403);
        }

        if ($nightShift->status !== 'rejected') {
            return back()->with('error', 'Only rejected claims can be resubmitted.');
        }

        if ($nightShift->created_at->addMonth()->isPast()) {
            return back()->with('error', 'The 1-month edit window has expired for this claim.');
        }

        $request->validate([
            'month'       => 'required|string',
            'year'        => 'required|integer|min:2020|max:2100',
            'worked_days' => 'nullable|array',
        ]);

        $workedDays = collect($request->worked_days ?? [])
            ->filter(fn($d) => !empty($d['worked']));

        if ($workedDays->isEmpty()) {
            return back()->withErrors(['worked_days' => 'Please select at least one worked day.'])->withInput();
        }

        $deptId = $nightShift->department_id ?? $user->deptId;
        $dept   = Departments::with('hec')->find($deptId);

        if (!$dept) {
            return back()->withErrors(['worked_days' => 'Department not found. Please contact the administrator.'])->withInput();
        }

        // ── Resolve flow & approvers BEFORE the transaction ───────────
        $flowKey       = $this->resolveDepartmentApprovalFlow($dept);
        $isLineManager = $user->hasAnyRole(['line-manager', 'line_manager', 'acting-line-manager']);
        $needsHec      = in_array($flowKey, ['three_level', 'incharge_lm_hec']);

        // Check if requester is an in-charge or platform manager for any of the selected units/platforms
        $isIncharge = false;
        $isPlatformManager = false;
        if (in_array($flowKey, ['incharge_platform', 'incharge_lm_hec'])) {
            $selectedUnitInchargeIds = $workedDays->pluck('unit_id')->filter()->unique()
                ->map(fn($id) => Unit::find($id)?->incharge_user_id)->filter()->unique();
            $isIncharge = $selectedUnitInchargeIds->contains($user->id);
        }
        if ($flowKey === 'incharge_platform') {
            $selectedPlatformManagerIds = $workedDays->pluck('platform_id')->filter()->unique()
                ->map(fn($pid) => Platform::find($pid)?->manager_user_id)->filter()->unique();
            $isPlatformManager = $selectedPlatformManagerIds->contains($user->id);
        }

        $hrUser = User::whereHas('roles', fn($q) => $q->where('name', 'hr'))
            ->when(Schema::hasColumn('users', 'approvelocum'), fn($q) => $q->where('approvelocum', 1))
            ->first();

        if (!$hrUser) {
            return back()->withErrors([
                'worked_days' => 'No HR approver is configured. Please contact the administrator.',
            ])->withInput();
        }

        $lmUser = null;
        if (!$isLineManager) {
            $lmUser = User::whereHas('roles', fn($q) => $q->whereIn('name', ['line-manager', 'line_manager', 'acting-line-manager']))
                ->where('deptId', $deptId)
                ->where('id', '!=', $user->id)
                ->first();
        }

        $hecUser = null;
        if ($needsHec) {
            $hecUser = $this->resolveHecApprover($dept);
            if (!$hecUser) {
                return back()->withErrors([
                    'worked_days' => "Your department ({$dept->dept_name}) requires HEC approval but no HEC approver is configured.",
                ])->withInput();
            }
        }

        // ── For incharge flows: every selected unit must have an In-Charge ──────
        if (in_array($flowKey, ['incharge_platform', 'incharge_lm_hec'])) {
            foreach ($workedDays->pluck('unit_id')->filter()->unique() as $unitId) {
                $unit = Unit::find($unitId);
                if (!$unit || !$unit->incharge_user_id) {
                    $unitName = $unit?->name ?? "ID #{$unitId}";
                    return back()->withErrors([
                        'worked_days' => "Unit \"{$unitName}\" has no In-Charge assigned. Please contact the administrator to configure one before submitting.",
                    ])->withInput();
                }
            }
        }

        // ── For incharge_platform flow: every selected platform must have a manager ──
        if ($flowKey === 'incharge_platform') {
            foreach ($workedDays->pluck('platform_id')->filter()->unique() as $platformId) {
                $platform = Platform::find($platformId);
                if (!$platform || !$platform->manager_user_id) {
                    $platformName = $platform?->name ?? "ID #{$platformId}";
                    return back()->withErrors([
                        'worked_days' => "Platform \"{$platformName}\" has no Platform Manager assigned. Please contact the administrator to configure one before submitting.",
                    ])->withInput();
                }
            }
        }

        // Determine first step (parallel incharge notify — same as store)
        $firstApproverIds = [];
        $firstStep        = '';
        $firstRemark      = '';

        if ($isLineManager) {
            $firstApproverIds = [($needsHec && $hecUser ? $hecUser : $hrUser)->id];
            $firstStep        = ($needsHec && $hecUser) ? 'HEC Approval' : 'HR Approval';
            $firstRemark      = 'Resubmitted by LM — awaiting '.($needsHec && $hecUser ? 'HEC' : 'HR').' approval.';
        } else {
            switch ($flowKey) {
                case 'three_level':
                    if ($lmUser) {
                        $firstApproverIds = [$lmUser->id]; $firstStep = 'Line Manager Approval';
                        $firstRemark = 'Resubmitted — awaiting Line Manager approval.';
                    } elseif ($hecUser) {
                        $firstApproverIds = [$hecUser->id]; $firstStep = 'HEC Approval';
                        $firstRemark = 'Resubmitted — awaiting HEC approval (no LM found).';
                    } else {
                        $firstApproverIds = [$hrUser->id]; $firstStep = 'HR Approval';
                        $firstRemark = 'Resubmitted — awaiting HR approval.';
                    }
                    break;

                case 'incharge_lm_hec':
                case 'incharge_platform':
                    $inchargeIds = $workedDays->pluck('unit_id')->filter()->unique()
                        ->map(fn($id) => Unit::find($id)?->incharge_user_id)->filter()->unique()
                        ->reject(fn($id) => $id == $user->id)->values();

                    if ($isIncharge && $inchargeIds->isEmpty()) {
                        if ($flowKey === 'incharge_platform') {
                            $pmIds = $workedDays->pluck('platform_id')->filter()->unique()
                                ->map(fn($pid) => Platform::find($pid)?->manager_user_id)->filter()->unique()
                                ->reject(fn($id) => $id == $user->id)->values();
                            if ($pmIds->isNotEmpty()) {
                                $firstApproverIds = $pmIds->all();
                                $firstStep        = 'Platform Manager Approval';
                                $firstRemark      = 'In-Charge resubmitted — awaiting Platform Manager approval.';
                                break;
                            }
                        }
                        if ($lmUser) {
                            $firstApproverIds = [$lmUser->id]; $firstStep = 'Line Manager Approval';
                            $firstRemark = 'In-Charge resubmitted — awaiting Line Manager approval.';
                        } elseif ($needsHec && $hecUser) {
                            $firstApproverIds = [$hecUser->id]; $firstStep = 'HEC Approval';
                            $firstRemark = 'In-Charge resubmitted — awaiting HEC approval.';
                        } else {
                            $firstApproverIds = [$hrUser->id]; $firstStep = 'HR Approval';
                            $firstRemark = 'In-Charge resubmitted — awaiting HR approval.';
                        }
                    } elseif ($isPlatformManager && $inchargeIds->isEmpty()) {
                        if ($lmUser) {
                            $firstApproverIds = [$lmUser->id]; $firstStep = 'Line Manager Approval';
                            $firstRemark = 'Platform Manager resubmitted — awaiting Line Manager approval.';
                        } elseif ($needsHec && $hecUser) {
                            $firstApproverIds = [$hecUser->id]; $firstStep = 'HEC Approval';
                            $firstRemark = 'Platform Manager resubmitted — awaiting HEC approval.';
                        } else {
                            $firstApproverIds = [$hrUser->id]; $firstStep = 'HR Approval';
                            $firstRemark = 'Platform Manager resubmitted — awaiting HR approval.';
                        }
                    } elseif ($inchargeIds->isNotEmpty()) {
                        $firstApproverIds = $inchargeIds->all(); $firstStep = 'Incharge Approval';
                        $firstRemark = 'Resubmitted — awaiting In-Charge approval.';
                    } elseif ($lmUser) {
                        $firstApproverIds = [$lmUser->id]; $firstStep = 'Line Manager Approval';
                        $firstRemark = 'Resubmitted — awaiting Line Manager approval.';
                    } else {
                        $firstApproverIds = [$hrUser->id]; $firstStep = 'HR Approval';
                        $firstRemark = 'Resubmitted — awaiting HR approval.';
                    }
                    break;

                default:
                    $firstApproverIds = [($lmUser ?? $hrUser)->id];
                    $firstStep        = $lmUser ? 'Line Manager Approval' : 'HR Approval';
                    $firstRemark      = 'Resubmitted — awaiting '.($lmUser ? 'Line Manager' : 'HR').' approval.';
                    break;
            }
        }

        DB::beginTransaction();
        try {
            $nightShift->update([
                'month'             => $request->month,
                'year'              => $request->year,
                'status'            => 'pending',
                'total_days_worked' => $workedDays->count(),
                'rejection_reason'  => null,
                'approved_by'       => null,
                'approved_at'       => null,
            ]);

            $nightShift->employees()->delete();

            foreach ($workedDays as $iso => $day) {
                NightShiftClaimEmployee::create([
                    'night_shift_claim_id' => $nightShift->id,
                    'user_id'              => $user->id,
                    'emp_code'             => $user->ccbrt_code ?? '—',
                    'employee_name'        => trim($user->fname.' '.($user->mname ?? '').' '.$user->lname),
                    'days_on_duty'         => 1,
                    'date'                 => $iso,
                    'platform_id'          => $day['platform_id'] ?: null,
                    'unit_id'              => !empty($day['unit_id']) ? $day['unit_id'] : null,
                    'hours'                => !empty($day['hours']) ? (float) $day['hours'] : null,
                ]);
            }

            $workflow = $nightShift->workflow;
            if ($workflow) {
                $workflow->histories()->delete();
                $workflow->update(['work_flow_completed' => 0, 'work_flow_status' => 0]);
            } else {
                $workflow = Workflow::create([
                    'user_id'              => $user->id,
                    'night_shift_claim_id' => $nightShift->id,
                    'work_flow_status'     => 0,
                    'work_flow_completed'  => 0,
                ]);
            }

            foreach (array_unique($firstApproverIds) as $approverId) {
                WorkFlowHistory::create([
                    'work_flow_id'  => $workflow->id,
                    'forwarded_by'  => $user->id,
                    'attended_by'   => $approverId,
                    'step_name'     => $firstStep,
                    'action_taken'  => 'Forwarded',
                    'status'        => 0,
                    'remark'        => $firstRemark,
                ]);
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Failed to resubmit: '.$e->getMessage()])->withInput();
        }

        // ── Send email notifications AFTER commit ─────────────────────
        try {
            foreach (array_unique($firstApproverIds) as $approverId) {
                $approverUser = User::find($approverId);
                if ($approverUser && $approverUser->email) {
                    Mail::to($approverUser->email)->queue(new NightShiftApprovalRequest(
                        $nightShift->fresh(), $workflow, $user, $approverUser
                    ));
                }
            }
        } catch (\Throwable $e) {
            \Log::warning('NightShift: Failed to send resubmit approval request email — '.$e->getMessage());
        }

        $approverCount = count(array_unique($firstApproverIds));
        if ($approverCount === 1) {
            $approver     = User::find($firstApproverIds[0]);
            $approverName = $approver ? trim($approver->fname.' '.$approver->lname) : 'approver';
            $msg = "Claim #{$nightShift->id} resubmitted and sent to {$approverName} for {$firstStep}.";
        } else {
            $msg = "Claim #{$nightShift->id} resubmitted and {$approverCount} In-Charge approvers notified for {$firstStep}.";
        }
        return redirect()->route('night-shift.index')->with('success', $msg);
    }

    /* ───────────────────────────────────────────────
     |  BULK APPROVE
     ─────────────────────────────────────────────── */
    public function bulkApprove(Request $request)
    {
        $request->validate([
            'claim_ids'   => 'required|array|min:1',
            'claim_ids.*' => 'exists:night_shift_claims,id',
        ]);

        $user     = Auth::user();
        $approved = 0;
        $skipped  = 0;

        foreach ($request->claim_ids as $claimId) {
            $nightShift = NightShiftClaim::with(['workflow.histories', 'employees', 'department'])->find($claimId);
            if (!$nightShift) { $skipped++; continue; }

            $workflow = $nightShift->workflow;
            if (!$workflow) { $skipped++; continue; }

            $pendingStep = $workflow->histories()
                ->where('attended_by', $user->id)->where('status', 0)->first();
            if (!$pendingStep) { $skipped++; continue; }

            $dept     = Departments::with('hec')->find($nightShift->department_id);
            $flowKey  = $this->resolveDepartmentApprovalFlow($dept);
            $needsHec = in_array($flowKey, ['three_level', 'incharge_lm_hec']);
            $hrUser   = User::whereHas('roles', fn($q) => $q->where('name', 'hr'))
                ->when(Schema::hasColumn('users', 'approvelocum'), fn($q) => $q->where('approvelocum', 1))
                ->first();

            $emailJobs = [];
            DB::transaction(function () use ($pendingStep, $workflow, $nightShift, $user, $dept, $flowKey, $needsHec, $hrUser, &$emailJobs) {
                $pendingStep->update(['status' => 1, 'attend_date' => now(), 'action_taken' => 'Approved']);
                $emailJobs = $this->advanceWorkflow($workflow, $nightShift, $pendingStep, $user, $dept, $flowKey, $needsHec, $hrUser);
            });

            // Send emails AFTER commit
            $this->sendWorkflowEmails($emailJobs, $nightShift, $workflow, $user);

            $approved++;
        }

        $msg = $approved > 0
            ? "{$approved} claim(s) approved successfully." . ($skipped > 0 ? " {$skipped} skipped (no pending step)." : '')
            : "No claims were approved. You may not have a pending step on the selected claims.";

        return redirect()->route('night-shift.approve.index')->with('success', $msg);
    }

    /* ───────────────────────────────────────────────
     |  BULK REJECT
     ─────────────────────────────────────────────── */
    public function bulkReject(Request $request)
    {
        $request->validate([
            'claim_ids'        => 'required|array|min:1',
            'claim_ids.*'      => 'exists:night_shift_claims,id',
            'rejection_reason' => 'required|string|min:5',
        ]);

        $user     = Auth::user();
        $rejected = 0;

        foreach ($request->claim_ids as $claimId) {
            $nightShift = NightShiftClaim::with('workflow.histories')->find($claimId);
            if (!$nightShift) continue;
            $workflow    = $nightShift->workflow;
            if (!$workflow) continue;
            $pendingStep = $workflow->histories()->where('attended_by', $user->id)->where('status', 0)->first();
            if (!$pendingStep) continue;

            DB::transaction(function () use ($pendingStep, $workflow, $nightShift, $user, $request) {
                $pendingStep->update(['status' => 2, 'attend_date' => now(), 'action_taken' => 'Rejected', 'rejection_reason' => $request->rejection_reason]);
                $workflow->update(['work_flow_completed' => 1, 'work_flow_status' => 2]);
                $nightShift->update(['status' => 'rejected', 'rejection_reason' => $request->rejection_reason, 'approved_by' => $user->id, 'approved_at' => now()]);
            });

            // Send rejection email to submitter
            try {
                $submitter = User::find($workflow->user_id);
                if ($submitter && $submitter->email) {
                    Mail::to($submitter->email)->queue(new NightShiftStatusUpdate(
                        $nightShift->fresh(), 'rejected', $user, $request->rejection_reason, $pendingStep->step_name
                    ));
                }
            } catch (\Throwable $e) {
                \Log::warning('NightShift: Failed to send bulk rejection email — '.$e->getMessage());
            }

            $rejected++;
        }

        return redirect()->route('night-shift.approve.index')
            ->with('success', "{$rejected} claim(s) rejected.");
    }

    /* ───────────────────────────────────────────────
     |  BIOTIME DATA (for approver detail modal)
     ─────────────────────────────────────────────── */
    public function biotimeData(NightShiftClaim $nightShift)
    {
        $submitter  = $nightShift->submitter;
        $ccbrtCode  = trim((string) ($submitter?->ccbrt_code ?? ''));
        $monthYear  = $nightShift->month . ' ' . $nightShift->year;

        try {
            $anchor      = \Carbon\Carbon::createFromFormat('F Y', $monthYear)->startOfMonth();
            $month       = (int) $anchor->month;
            $year        = (int) $anchor->year;
            $daysInMonth = (int) $anchor->daysInMonth;
        } catch (\Throwable) {
            return response()->json(['worked_days' => []]);
        }

        $workedDays = [];
        for ($d = 1; $d <= $daysInMonth; $d++) {
            $iso = $anchor->copy()->day($d)->toDateString();
            $workedDays[$iso] = ['worked' => 0, 'hours' => '0.00', 'time_in' => null, 'time_out' => null];
        }

        if ($ccbrtCode === '' || strtoupper($ccbrtCode) === 'N/A') {
            return response()->json(['worked_days' => $workedDays]);
        }

        try { \DB::connection('bio')->getPdo(); } catch (\Throwable) {
            return response()->json(['worked_days' => $workedDays]);
        }

        $trxTable      = 'iclock_transaction';
        $hasPunchState = Schema::connection('bio')->hasColumn($trxTable, 'punch_state');
        $start         = $anchor->copy()->startOfDay();
        $end           = $anchor->copy()->endOfMonth()->addDay()->endOfDay();

        try {
            $selectCols = $hasPunchState ? "emp_code, punch_time, punch_state" : "emp_code, punch_time";
            $rows = \DB::connection('bio')->select(
                "SELECT {$selectCols} FROM {$trxTable} WHERE emp_code = ? AND punch_time >= ? AND punch_time < ? ORDER BY punch_time ASC",
                [$ccbrtCode, $start, $end]
            );
        } catch (\Throwable) {
            return response()->json(['worked_days' => $workedDays]);
        }

        $punches = [];
        foreach ($rows as $r) {
            $dt  = \Carbon\Carbon::parse($r->punch_time);
            $dir = 'UNK';
            if ($hasPunchState) {
                $dir = ($r->punch_state === '0') ? 'IN' : (($r->punch_state === '1') ? 'OUT' : 'UNK');
            }
            $punches[] = ['dt' => $dt, 'date' => $dt->toDateString(), 'time' => $dt->format('H:i:s'), 'dir' => $dir];
        }

        $MAX_GAP = 36;
        $sessions = [];
        $open     = null;
        $push = function ($a, $b) use (&$sessions) {
            $mins = (int) round(($b['dt']->getTimestamp() - $a['dt']->getTimestamp()) / 60);
            if ($mins > 0) {
                $sessions[] = ['startDT' => $a['dt'], 'startTime' => $a['time'], 'endDT' => $b['dt'], 'endTime' => $b['time'], 'minutes' => $mins];
            }
        };

        foreach ($punches as $p) {
            $dir = ($p['dir'] === 'UNK') ? ($open ? 'OUT' : 'IN') : $p['dir'];
            if ($dir === 'IN') {
                if ($open) { $gap = ($p['dt']->getTimestamp() - $open['dt']->getTimestamp()) / 3600; if ($gap > 0 && $gap <= $MAX_GAP) $push($open, $p); }
                $open = $p;
            } else {
                if ($open) { $gap = ($p['dt']->getTimestamp() - $open['dt']->getTimestamp()) / 3600; if ($gap > 0 && $gap <= $MAX_GAP) { $push($open, $p); $open = null; } else $open = null; }
            }
        }

        $inMonth = fn(\Carbon\Carbon $c) => (int)$c->month === $month && (int)$c->year === $year;
        $days = [];
        foreach ($sessions as $s) {
            if (!$inMonth($s['startDT'])) continue;
            $key = $s['startDT']->toDateString();
            if (!isset($days[$key])) {
                $days[$key] = ['firstIn' => $s['startTime'], 'firstInDT' => $s['startDT'], 'lastOut' => $s['endTime'], 'lastOutDT' => $s['endDT'], 'totalMin' => 0];
            }
            $days[$key]['totalMin'] += $s['minutes'];
            if ($s['startDT']->lt($days[$key]['firstInDT'])) { $days[$key]['firstIn'] = $s['startTime']; $days[$key]['firstInDT'] = $s['startDT']; }
            if ($s['endDT']->gt($days[$key]['lastOutDT']))   { $days[$key]['lastOut']  = $s['endTime'];   $days[$key]['lastOutDT']  = $s['endDT']; }
        }

        if ($open && $inMonth($open['dt'])) {
            $key = $open['dt']->toDateString();
            if (!isset($days[$key])) {
                $days[$key] = ['firstIn' => $open['time'], 'firstInDT' => $open['dt'], 'lastOut' => null, 'lastOutDT' => null, 'totalMin' => 0];
            }
            if ($key === now()->toDateString()) {
                $days[$key]['totalMin'] += min((int) round((now()->getTimestamp() - $open['dt']->getTimestamp()) / 60), $MAX_GAP * 60);
            }
        }

        $toHHMM = function (int $mins) {
            $mins = max(0, $mins);
            return str_pad((string) intdiv($mins, 60), 2, '0', STR_PAD_LEFT) . ':' . str_pad((string) ($mins % 60), 2, '0', STR_PAD_LEFT);
        };

        foreach ($days as $iso => $agg) {
            $workedDays[$iso] = [
                'worked'    => 1,
                'hours'     => $toHHMM((int) $agg['totalMin']),
                'total_min' => (int) $agg['totalMin'],
                'time_in'   => $agg['firstIn'] ?? null,
                'time_out'  => $agg['lastOut'] ?? null,
            ];
        }

        return response()->json(['worked_days' => $workedDays]);
    }

    /* ───────────────────────────────────────────────
     |  ADVANCE WORKFLOW (shared: approve + bulkApprove)
     ─────────────────────────────────────────────── */
    private function advanceWorkflow(
        Workflow $workflow, NightShiftClaim $nightShift,
        WorkFlowHistory $pendingStep, $user,
        ?Departments $dept, string $flowKey, bool $needsHec, ?User $hrUser
    ): array {
        $submitterId = $workflow->user_id;
        $emailJobs = [];

        if ($pendingStep->step_name === 'Incharge Approval') {
            if ($workflow->histories()->where('step_name', 'Incharge Approval')->where('status', 0)->where('id', '!=', $pendingStep->id)->exists()) return $emailJobs;

            if ($flowKey === 'incharge_platform') {
                $nightShift->load('employees');
                $pmIds = $nightShift->employees->pluck('platform_id')->filter()->unique()
                    ->map(fn($pid) => Platform::find($pid)?->manager_user_id)->filter()->unique()
                    ->reject(fn($id) => $id == $submitterId);
                foreach ($pmIds as $pmId) {
                    $pm = User::find($pmId);
                    if ($pm) {
                        WorkFlowHistory::create(['work_flow_id' => $workflow->id, 'forwarded_by' => $user->id, 'attended_by' => $pm->id, 'step_name' => 'Platform Manager Approval', 'action_taken' => 'Forwarded', 'status' => 0, 'remark' => 'Approved by In-Charge. Awaiting Platform Manager approval.']);
                        $emailJobs[] = ['type' => 'approval_request', 'to' => $pm];
                    }
                }
                if ($pmIds->isNotEmpty()) return $emailJobs;
            }

            $lmUser = User::whereHas('roles', fn($q) => $q->whereIn('name', ['line-manager', 'line_manager', 'acting-line-manager']))
                ->where('deptId', $nightShift->department_id)
                ->where('id', '!=', $submitterId)
                ->first();
            if ($lmUser) {
                WorkFlowHistory::create(['work_flow_id' => $workflow->id, 'forwarded_by' => $user->id, 'attended_by' => $lmUser->id, 'step_name' => 'Line Manager Approval', 'action_taken' => 'Forwarded', 'status' => 0, 'remark' => 'Approved by In-Charge. Awaiting Line Manager approval.']);
                $emailJobs[] = ['type' => 'approval_request', 'to' => $lmUser];
                return $emailJobs;
            }
            if ($needsHec) {
                $hecUser = $this->resolveHecApprover($dept);
                if ($hecUser && $hecUser->id != $submitterId) {
                    WorkFlowHistory::create(['work_flow_id' => $workflow->id, 'forwarded_by' => $user->id, 'attended_by' => $hecUser->id, 'step_name' => 'HEC Approval', 'action_taken' => 'Forwarded', 'status' => 0, 'remark' => 'Approved by In-Charge. Awaiting HEC approval.']);
                    $emailJobs[] = ['type' => 'approval_request', 'to' => $hecUser];
                    return $emailJobs;
                }
            }
            if ($hrUser) {
                WorkFlowHistory::create(['work_flow_id' => $workflow->id, 'forwarded_by' => $user->id, 'attended_by' => $hrUser->id, 'step_name' => 'HR Approval', 'action_taken' => 'Forwarded', 'status' => 0, 'remark' => 'Approved by In-Charge. Awaiting HR approval.']);
                $emailJobs[] = ['type' => 'approval_request', 'to' => $hrUser];
            }
            return $emailJobs;
        }

        if ($pendingStep->step_name === 'Platform Manager Approval') {
            if ($workflow->histories()->where('step_name', 'Platform Manager Approval')->where('status', 0)->where('id', '!=', $pendingStep->id)->exists()) return $emailJobs;
            $lmUser = User::whereHas('roles', fn($q) => $q->whereIn('name', ['line-manager', 'line_manager', 'acting-line-manager']))
                ->where('deptId', $nightShift->department_id)
                ->where('id', '!=', $submitterId)
                ->first();
            if ($lmUser) {
                WorkFlowHistory::create(['work_flow_id' => $workflow->id, 'forwarded_by' => $user->id, 'attended_by' => $lmUser->id, 'step_name' => 'Line Manager Approval', 'action_taken' => 'Forwarded', 'status' => 0, 'remark' => 'Approved by Platform Manager. Awaiting Line Manager approval.']);
                $emailJobs[] = ['type' => 'approval_request', 'to' => $lmUser];
                return $emailJobs;
            }
            if ($needsHec) {
                $hecUser = $this->resolveHecApprover($dept);
                if ($hecUser && $hecUser->id != $submitterId) {
                    WorkFlowHistory::create(['work_flow_id' => $workflow->id, 'forwarded_by' => $user->id, 'attended_by' => $hecUser->id, 'step_name' => 'HEC Approval', 'action_taken' => 'Forwarded', 'status' => 0, 'remark' => 'Approved by Platform Manager. Awaiting HEC approval.']);
                    $emailJobs[] = ['type' => 'approval_request', 'to' => $hecUser];
                    return $emailJobs;
                }
            }
            if ($hrUser) {
                WorkFlowHistory::create(['work_flow_id' => $workflow->id, 'forwarded_by' => $user->id, 'attended_by' => $hrUser->id, 'step_name' => 'HR Approval', 'action_taken' => 'Forwarded', 'status' => 0, 'remark' => 'Approved by Platform Manager. Awaiting HR approval.']);
                $emailJobs[] = ['type' => 'approval_request', 'to' => $hrUser];
            }
            return $emailJobs;
        }

        if ($pendingStep->step_name === 'Line Manager Approval') {
            if ($needsHec) {
                $hecUser = $this->resolveHecApprover($dept);
                if ($hecUser) {
                    WorkFlowHistory::create(['work_flow_id' => $workflow->id, 'forwarded_by' => $user->id, 'attended_by' => $hecUser->id, 'step_name' => 'HEC Approval', 'action_taken' => 'Forwarded', 'status' => 0, 'remark' => 'Approved by Line Manager. Awaiting HEC approval.']);
                    $emailJobs[] = ['type' => 'approval_request', 'to' => $hecUser];
                    return $emailJobs;
                }
            }
            if ($hrUser) {
                WorkFlowHistory::create(['work_flow_id' => $workflow->id, 'forwarded_by' => $user->id, 'attended_by' => $hrUser->id, 'step_name' => 'HR Approval', 'action_taken' => 'Forwarded', 'status' => 0, 'remark' => 'Approved by Line Manager. Awaiting HR approval.']);
                $emailJobs[] = ['type' => 'approval_request', 'to' => $hrUser];
            }
            return $emailJobs;
        }

        if ($pendingStep->step_name === 'HEC Approval') {
            if ($hrUser) {
                WorkFlowHistory::create(['work_flow_id' => $workflow->id, 'forwarded_by' => $user->id, 'attended_by' => $hrUser->id, 'step_name' => 'HR Approval', 'action_taken' => 'Forwarded', 'status' => 0, 'remark' => 'Approved by HEC. Awaiting HR approval.']);
                $emailJobs[] = ['type' => 'approval_request', 'to' => $hrUser];
            }
            return $emailJobs;
        }

        // Final (HR) → complete
        $workflow->update(['work_flow_completed' => 1, 'work_flow_status' => 1]);
        $nightShift->update(['status' => 'approved', 'approved_by' => $user->id, 'approved_at' => now()]);
        $emailJobs[] = ['type' => 'status_update', 'status' => 'approved', 'step' => $pendingStep->step_name];
        return $emailJobs;
    }

    /**
     * Send queued email notifications collected from advanceWorkflow.
     */
    private function sendWorkflowEmails(array $emailJobs, NightShiftClaim $nightShift, Workflow $workflow, $approver): void
    {
        $submitter = User::find($workflow->user_id);

        foreach ($emailJobs as $job) {
            try {
                if ($job['type'] === 'approval_request' && isset($job['to']) && $job['to']->email) {
                    Mail::to($job['to']->email)->queue(new NightShiftApprovalRequest(
                        $nightShift->fresh(), $workflow, $submitter, $job['to']
                    ));
                } elseif ($job['type'] === 'status_update' && $submitter && $submitter->email) {
                    Mail::to($submitter->email)->queue(new NightShiftStatusUpdate(
                        $nightShift->fresh(), $job['status'], $approver, null, $job['step'] ?? null
                    ));
                }
            } catch (\Throwable $e) {
                \Log::warning('NightShift: Failed to send workflow email — '.$e->getMessage());
            }
        }
    }

    /* ───────────────────────────────────────────────
     |  RESOLVE DEPARTMENT APPROVAL FLOW
     ─────────────────────────────────────────────── */
    private function resolveDepartmentApprovalFlow(?Departments $dept): string
    {
        if (!$dept) {
            return 'standard';
        }

        // Priority order matches locum controller
        if ((int) ($dept->has_incharge_platform_flow ?? 0) === 1) {
            return 'incharge_platform'; // In-Charge → Platform Manager(s) → LM → HR
        }

        if ((int) ($dept->has_incharge_lm_hec_flow ?? 0) === 1) {
            return 'incharge_lm_hec';   // In-Charge → LM → HEC → HR
        }

        if ((int) ($dept->has_three_level_approval ?? 0) === 1) {
            return 'three_level';       // LM → HEC → HR
        }

        return 'standard';              // LM → HR
    }

    /* ───────────────────────────────────────────────
     |  RESOLVE HEC APPROVER for a department
     ─────────────────────────────────────────────── */
    private function resolveHecApprover(?Departments $dept): ?User
    {
        if (!$dept || !$dept->hec_id) return null;

        $hec = $dept->hec ?? \App\Models\Hec::find($dept->hec_id);
        if (!$hec || !$hec->hec_level_name) return null;

        $roleMap = [
            'COO'   => 'coo',  'CFO'   => 'cfo',
            'CMS'   => 'cms',  'CCDRO' => 'ccdro',
        ];

        $roleSlug = $roleMap[strtoupper(trim($hec->hec_level_name))] ?? null;
        if (!$roleSlug) return null;

        return User::role($roleSlug)->orderBy('id')->first();
    }
}
