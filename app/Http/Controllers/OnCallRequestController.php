<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\User;
use App\Models\Workflow;
use App\Models\Departments;
use Illuminate\Http\Request;
use App\Models\OnCallRequest;
use App\Models\WorkFlowHistory;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use App\Mail\OnCallApprovalRequest;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\OnCallRequestsExport;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use App\Mail\OnCallRejectionNotification;
use App\Mail\OnCallSubmissionNotification;
use Illuminate\Validation\ValidationException;

class OnCallRequestController extends Controller
{
    private $onCallRates = [
        'Specialists Off site' => 40000,
        'Certificate' => 50000,
        'Enrolled Certificate' => 60000,
        'Diploma'     => 80000,
        'Degree'      => 100000,
        'Hospital Supervisor' => 140000,
        'Masters'     => 120000,
        // 'PhD'         => 200000,
    ];

    /** ---------- Helpers: role scoping & report base query ---------- */

    private function allowedDepartmentIds(User $user): array|string
    {
        // HR: see all departments
        if ($user->hasRole('hr')) {
            return \App\Models\Departments::pluck('id')->all();
        }

        // Line Manager: see their department(s)
        if ($user->hasRole('line-manager')) {
            // If you have a relation like $user->managedDepartments, include it; otherwise fall back to own deptId
            $managed = method_exists($user, 'managedDepartments')
                ? $user->managedDepartments()->pluck('departments.id')->all()
                : [];
            $own = $user->deptId ? [(int) $user->deptId] : [];
            return array_values(array_unique(array_filter(array_merge($managed, $own))));
        }

        // HEC roles (COO/CFO/CMS): see departments mapped to their HEC level
        if ($user->hasAnyRole(['coo', 'cfo', 'cms'])) {
            $roleToHec = [];
            if ($user->hasRole('coo')) $roleToHec[] = 'COO';
            if ($user->hasRole('cfo')) $roleToHec[] = 'CFO';
            if ($user->hasRole('cms')) $roleToHec[] = 'CMS';

            return \App\Models\Departments::query()
                ->join('hecs', 'departments.hec_id', '=', 'hecs.id')
                ->whereIn(\DB::raw('LOWER(hecs.hec_level_name)'), collect($roleToHec)->map(fn($r) => strtolower($r))->all())
                ->pluck('departments.id')->all();
        }

        // Requester (or in-charge) only: see own requests
        if ($user->hasAnyRole(['requester', 'in-charge'])) {
            return '__SELF__';
        }

        // Default: nothing
        return [];
    }
    private function baseReportQuery(User $user)
    {
        $scope = $this->allowedDepartmentIds($user);

        $q = DB::table('on_call_requests as ocr')
            ->join('users as u', 'ocr.user_id', '=', 'u.id')
            ->leftJoin('departments as d', 'u.deptId', '=', 'd.id')
            ->selectRaw(
                'u.username, u.ccbrt_code, u.fname, u.mname, u.lname, ' .
                    'd.dept_name as department, u.job_title, ' .
                    'ocr.rate, ocr.total_amount_payable, ' .
                    'ocr.locum_month as month, ocr.locum_year as year, ocr.status, ocr.created_at'
            );

        if ($scope === '__SELF__') {
            $q->where('ocr.user_id', $user->id);
        } elseif (is_array($scope) && !empty($scope)) {
            $q->whereIn('u.deptId', $scope);
        } else {
            $q->whereRaw('1=0');
        }

        return $q;
    }

    private function applyReportFilters(Request $request, \Illuminate\Database\Query\Builder $q, User $user)
    {
        if ($year = $request->query('year'))   $q->where('ocr.locum_year',  $year);
        if ($month = $request->query('month')) $q->where('ocr.locum_month', $month);

        // Department filter must still respect visibility
        if ($department = $request->query('department')) {
            $scope = $this->allowedDepartmentIds($user);
            if ($scope === '__SELF__') {
                // Ignore department filter in requester-only scope
            } elseif (is_array($scope) && in_array((int)$department, $scope, true)) {
                $q->where('d.id', (int)$department);
            }
        }

        return $q;
    }
    public function create()
    {
        $user  = Auth::user();
        $today = Carbon::now();

        // default claim month = previous month (e.g. "September 2025")
        $previous = $today->copy()->subMonthNoOverflow();
        $defaultClaimMonth = $previous->format('F Y');

        // Get deadline day from system settings (default to 5th if not set)
        $deadlineDay = (int) SettingsController::getSetting('oncall_submission_deadline', 5);
        $deadline = $today->copy()->startOfMonth()->addDays($deadlineDay - 1)->endOfDay();

        if ($today->gt($deadline)) {
            return redirect()->route('oncall_requests.index')
                ->with('error', "Submission for {$defaultClaimMonth} is closed. The deadline was {$deadline->format('j F Y')}.");
        }

        // Only allow previous month in the dropdown
        $months = [$defaultClaimMonth];

        $educationLevels = array_keys($this->onCallRates);
        $onCallRates      = $this->onCallRates;

        return view('oncall_requests.create', compact(
            'user',
            'months',
            'educationLevels',
            'onCallRates',
            'defaultClaimMonth'
        ));
    }



    public function store(Request $request)
    {
        if (!Auth::check()) {
            return redirect()->route('login')->with('error', 'You must be logged in to create an on-call request.');
        }

        // Check deadline: submissions close on the configured day of the current month
        $today = Carbon::now();
        $previous = $today->copy()->subMonthNoOverflow();
        $defaultClaimMonth = $previous->format('F Y');
        $deadlineDay = (int) SettingsController::getSetting('oncall_submission_deadline', 5);
        $deadline = $today->copy()->startOfMonth()->addDays($deadlineDay - 1)->endOfDay();

        if ($today->gt($deadline)) {
            return redirect()->route('oncall_requests.index')
                ->with('error', "Submission for {$defaultClaimMonth} is closed. The deadline was {$deadline->format('j F Y')}.");
        }
        if ($request->input('has_special_task') === '1' && !$request->hasFile('special_task_document')) {
            return back()
                ->withErrors(['special_task_document' => 'No file reached the server. Check form enctype and upload size limits.'])
                ->withInput();
        }

        try {
            $validated = $request->validate([
                'education_level'       => ['required', Rule::in(array_keys($this->onCallRates))],
                'locum_month'           => ['required', 'string', 'regex:/^(January|February|March|April|May|June|July|August|September|October|November|December) [0-9]{4}$/'],
                'worked_days'           => ['required', 'array', 'min:1'],
                'worked_days.*.worked'  => ['required', 'in:0,1'],
                'worked_days.*.hours'   => ['required_if:worked_days.*.worked,1', 'numeric', 'min:0', 'max:24'],
                // 'reason'                => ['nullable', 'string', 'max:1000'],
                'description'           => ['nullable', 'string', 'max:15000'],
                'has_special_task'      => ['required', 'in:0,1'],
                'special_task_document' => ['bail', 'required_if:has_special_task,1', 'file', 'mimes:pdf,doc,docx,jpg,jpeg,png', 'max:10240'], // 10MB
            ], [
                'special_task_document.required_if' => 'Please upload the supportive document for a special task.',
            ]);
            $totalDays = 0;
            $totalHours = 0;

            foreach ($validated['worked_days'] as $dateStr => $data) {
                $date = \DateTime::createFromFormat('Y-m-d', $dateStr);
                if (!$date) continue;
                if ((int)$data['worked'] === 1) {
                    if (empty($data['hours']) || floatval($data['hours']) <= 0) {
                        return back()->withErrors(['worked_days' => "Hours must be greater than 0 for {$dateStr}"])->withInput();
                    }
                    $totalDays++;
                    $totalHours += floatval($data['hours']);
                }
            }
            if ($totalDays === 0) {
                return back()->withErrors(['worked_days' => 'At least one day must be selected'])->withInput();
            }

            $rate        = $this->onCallRates[$validated['education_level']];
            $totalAmount = $totalDays * $rate;

            DB::beginTransaction();

            [$month, $year] = explode(' ', $validated['locum_month']);

            // If a request for this user+month+year already exists...
            $existing = OnCallRequest::where('user_id', Auth::id())
                ->where('locum_month', $month)
                ->where('locum_year', $year)
                ->latest('id')
                ->first();

            if ($existing) {
                if ($existing->status === 'rejected') {
                    // Tell user to update the rejected one instead of creating new
                    return redirect()
                        ->route('oncall_requests.edit', $existing->id)
                        ->with('error', "Your {$month} {$year} on-call request was rejected. Please update and resubmit that request instead of creating a new one.");
                }

                // For any other status, block creation
                return back()
                    ->withErrors([
                        'locum_month' => "You already have an on-call request for {$month} {$year} (status: {$existing->status}). You can’t create another."
                    ])
                    ->withInput();
            }
            // --- Handle special-task file (if any) ---
            $hasSpecial   = (int)($validated['has_special_task'] ?? 0) === 1;
            $specialPath  = null;
            $originalName = null;
            $mime         = null;
            $size         = null;

            if ($hasSpecial) {
                $file = $request->file('special_task_document'); // guaranteed by validation if has_special_task=1
                if (!$file || !$file->isValid()) {
                    DB::rollBack();
                    return back()
                        ->withErrors(['special_task_document' => 'Upload failed. Please try again.'])
                        ->withInput();
                }

                // storage/app/public/oncall/special_tasks/...
                $specialPath  = $file->store('oncall/special_tasks', 'public');
                $originalName = $file->getClientOriginalName();
                $mime         = $file->getClientMimeType();
                $size         = $file->getSize();
            }

            $requestRecord = OnCallRequest::create([
                'user_id'               => Auth::id(),
                'education_level'       => $validated['education_level'],
                'locum_month'           => $month,
                'locum_year'            => $year,
                'number_of_days'        => $totalDays,
                'total_hours'           => $totalHours,
                'total_amount'          => $totalAmount,
                'total_amount_payable'  => $totalAmount,
                'worked_days'           => $validated['worked_days'],
                // 'reason'                => $validated['reason'],
                'description'          => $validated['description'] ?? null,
                'status'                => 'pending',
                'has_special_task'           => $hasSpecial,
                'special_task_path'          => $specialPath,
                'special_task_original_name' => $originalName,
                'special_task_mime'          => $mime,
                'special_task_size'          => $size,
            ]);

            $user = Auth::user();

            //current & future months
            $tz = config('app.timezone', 'Africa/Dar_es_Salaam');
            $claimedMonth = \Carbon\Carbon::createFromFormat('F Y', $validated['locum_month'], $tz)->startOfMonth();
            $currentMonth = \Carbon\Carbon::now($tz)->startOfMonth();

            if ($claimedMonth->greaterThanOrEqualTo($currentMonth)) {
                return back()->withErrors([
                    'locum_month' => 'You cannot submit or claim for the current or a future month. Please select a past month.'
                ])->withInput();
            }

            $user = Auth::user();
            $isLineManagerRequester = $user->hasRole('line-manager') && !$user->hasRole('hr');

            $workflow = Workflow::create([
                'on_call_request_id' => $requestRecord->id,
                'user_id'            => Auth::id(),
                'work_flow_status'   => 0,
                'work_flow_completed' => 0,
            ]);

            // If line manager is creating the request, always route to HEC (if mapped) then HR
            if ($isLineManagerRequester) {
                $department = $user->department;

                // Always try to route to HEC first (if department has HEC mapped)
                $hecApprover = $this->resolveHecApproverLax($department, null, $workflow);

                if ($hecApprover) {
                    // Route to HEC member, then HR
                    WorkFlowHistory::create([
                        'work_flow_id' => $workflow->id,
                        'forwarded_by' => Auth::id(),
                        'attended_by'  => $hecApprover->id,
                        'step_name'    => 'HEC Approval',
                        'action_taken' => 'Forwarded',
                        'status'       => 0,
                        'on_call_request_status' => 3, // pending HEC
                        'remark'       => 'On-call request submitted by Line Manager — pending HEC approval.',
                    ]);
                } else {
                    // No HEC mapped, route directly to HR
                    $hrApprover = User::whereHas('roles', fn($q) => $q->where('name', 'hr'))
                        ->where('approvelocum', 1)
                        ->first();

                    if (!$hrApprover) {
                        DB::rollBack();
                        return back()->withErrors(['error' => 'No HR approver found. Please contact an administrator.'])->withInput();
                    }

                    WorkFlowHistory::create([
                        'work_flow_id' => $workflow->id,
                        'forwarded_by' => Auth::id(),
                        'attended_by'  => $hrApprover->id,
                        'step_name'    => 'HR Approval',
                        'action_taken' => 'Forwarded',
                        'status'       => 0,
                        'on_call_request_status' => 4, // pending HR
                        'remark'       => 'On-call request submitted by Line Manager — pending HR approval (no HEC mapped for department).',
                    ]);
                }
            } else {
                // Normal flow: route to Line Manager
                $lineManager = User::whereHas('roles', fn($q) => $q->where('name', 'line-manager'))
                    ->where('deptId', Auth::user()->deptId)
                    ->first();

                if (!$lineManager) {
                    DB::rollBack();
                    return back()->withErrors(['error' => 'No Line Manager found for your department.'])->withInput();
                }

                WorkFlowHistory::create([
                    'work_flow_id' => $workflow->id,
                    'forwarded_by' => Auth::id(),
                    'attended_by'  => $lineManager->id,
                    'step_name'    => 'Line Manager Approval',
                    'action_taken' => 'Forwarded',
                    'status'       => 0,
                    'on_call_request_status' => 0,
                    'remark'       => 'On-call request submitted for Line Manager approval.',
                ]);
            }


            DB::commit();

            // Get the first approver for email notification
            $firstApprover = null;
            $firstHistory = $workflow->histories()->orderBy('id')->first();
            if ($firstHistory) {
                $firstApprover = User::find($firstHistory->attended_by);
            }

            try {
                Mail::to($requestRecord->user->email)->queue(new OnCallSubmissionNotification(
                    onCallRequest: $requestRecord,
                    workflow: $workflow,
                    submitter: $requestRecord->user
                ));
            } catch (\Throwable $mailEx) {
                Log::error('Mail to submitter failed', ['err' => $mailEx->getMessage()]);
            }

            if ($firstApprover) {
                try {
                    Mail::to($firstApprover->email)->queue(new OnCallApprovalRequest(
                        onCallRequest: $requestRecord,
                        workflow: $workflow,
                        submitter: $requestRecord->user,
                        approver: $firstApprover
                    ));
                } catch (\Throwable $mailEx) {
                    Log::error('Mail to approver failed', ['err' => $mailEx->getMessage()]);
                }
            }

            return redirect()->route('oncall_requests.index')->with('success', 'On-call request submitted for approval.');
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors())->withInput();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to submit on-call request', ['err' => $e->getMessage()]);
            return back()->with('error', 'Failed to submit request. Please try again.')->withInput();
        }
    }

    public function index()
    {
        // My requests (all requests - pending, rejected, and approved)
        $requests = OnCallRequest::where('user_id', Auth::id())
            ->with([
                'user',
                'workflow.histories' => fn($q) => $q->orderBy('created_at', 'desc'),
                'workflow.histories.attendedBy',
                'workflow.histories.approver',
                'workflow.histories.forwardedBy',
            ])
            ->orderBy('created_at', 'desc')
            ->get();

        // Approver inbox (assigned to me & pending) - show all requests pending approval
        $pendingFlat = OnCallRequest::with([
            'user',
            'user.department',
            'workflow.histories' => fn($q) => $q->orderBy('created_at', 'desc'),
            'workflow.histories.attendedBy',
            'workflow.histories.approver',
            'workflow.histories.forwardedBy',
        ])
            ->where('status', 'pending')
            ->whereHas('workflow', function ($q) {
                $q->where('work_flow_completed', 0)
                    ->whereHas(
                        'histories',
                        fn($h) =>
                        $h->where('attended_by', Auth::id())->where('status', 0)
                    );
            })
            ->orderBy('created_at', 'desc')
            ->get();

        // Summary for cards (assigned-to-me pending only)
        $pendingSummary = [
            'count'  => $pendingFlat->count(),
            'days'   => (int) $pendingFlat->sum('number_of_days'),
            'hours'  => (float) $pendingFlat->sum('total_hours'),
            'amount' => (float) $pendingFlat->sum('total_amount_payable'),
            'by_month' => $pendingFlat
                ->groupBy(fn($r) => trim(($r->locum_month ?? '') . ' ' . ($r->locum_year ?? '')))
                ->map(fn($grp) => [
                    'count'  => $grp->count(),
                    'days'   => (int) $grp->sum('number_of_days'),
                    'hours'  => (float) $grp->sum('total_hours'),
                    'amount' => (float) $grp->sum('total_amount_payable'),
                ]),
        ];

        // Group for your existing table render
        $pendingRequests = $pendingFlat->groupBy(['locum_month', 'locum_year']);

        // Calculate status counts for filter pills - using same logic as showOnCallRequest (the modal)
        $statusCounts = [
            'All' => $requests->count(),
            'Approved' => $requests->filter(function ($req) {
                $wf = $req->workflow ?? null;
                if (!$wf) return false;
                // If workflow is completed, check work_flow_status: 1 = approved, 2 = rejected
                if ((int) $wf->work_flow_completed === 1) {
                    return (int) $wf->work_flow_status === 1; // Only approved if status is 1
                }
                return false;
            })->count(),
            'Rejected' => $requests->filter(function ($req) {
                $wf = $req->workflow ?? null;
                if (!$wf) return false;
                $histories = $wf->histories ?? collect();

                // If workflow is completed, check work_flow_status
                if ((int) $wf->work_flow_completed === 1) {
                    return (int) $wf->work_flow_status === 2; // Rejected if status is 2
                }

                // Get the last history entry (sorted by updated_at or created_at) - same as modal
                $last = $histories->sortByDesc(fn($h) => $h->updated_at ?? $h->created_at)->first();

                // If last history status is 2 (rejected), it's rejected
                return $last && (string) $last->status === '2';
            })->count(),
            'Pending' => $requests->filter(function ($req) {
                $wf = $req->workflow ?? null;
                if (!$wf) return false;
                $histories = $wf->histories ?? collect();

                // If workflow is completed, it's not pending
                if ((int) $wf->work_flow_completed === 1) {
                    return false;
                }

                // Get the last history entry (sorted by updated_at or created_at) - same as modal
                $last = $histories->sortByDesc(fn($h) => $h->updated_at ?? $h->created_at)->first();

                // If last history status is not 2 (rejected), it's pending
                return !$last || (string) $last->status !== '2';
            })->count(),
        ];

        return view('oncall_requests.index', compact('requests', 'pendingRequests', 'pendingSummary', 'statusCounts', 'pendingFlat'));
    }


    public function view(Request $request)
    {
        $user = Auth::user();

        // Check if user is an approver (line-manager, hr, coo, cfo, cms)
        $isApprover = $user->hasAnyRole(['line-manager', 'hr', 'coo', 'cfo', 'cms']);

        if ($isApprover) {
            // Approvers: Get pending requests assigned to them for approval
            $requests = OnCallRequest::with([
                'user.department',
                'workflow.histories' => fn($q) => $q->with(['attendedBy', 'forwardedBy', 'approver'])->orderBy('id', 'desc'),
            ])
                ->where('status', 'pending')
                ->whereHas('workflow', function ($q) {
                    $q->where('work_flow_completed', 0)
                        ->whereHas('histories', fn($h) => $h->where('attended_by', Auth::id())->where('status', 0));
                })
                ->orderBy('created_at', 'desc')
                ->get();

            return view('oncall_requests.view', compact('requests'));
        } else {
            // Regular users: Show their own requests for review
            $requests = OnCallRequest::where('user_id', $user->id)
                ->with([
                    'user.department',
                    'workflow.histories' => fn($q) => $q->with(['attendedBy', 'forwardedBy', 'approver'])->orderBy('id', 'desc'),
                ])
                ->orderBy('created_at', 'desc')
                ->get();

            // Calculate status counts for filter pills
            $statusCounts = [
                'All' => $requests->count(),
                'Pending' => $requests->filter(function ($req) {
                    $wf = $req->workflow ?? null;
                    if (!$wf) return false;
                    $histories = $wf->histories ?? collect();

                    // Check if there are any pending histories (resubmitted after rejection)
                    $hasPending = $histories->where('status', 0)->isNotEmpty();
                    if ($hasPending) return true;

                    // If no pending and workflow is completed, it's not pending
                    if ((int) $wf->work_flow_completed === 1) return false;

                    // Check if there's a rejection but also check if it was resubmitted
                    $rejectedHistory = $histories->where('status', 2)->sortByDesc('id')->first();
                    if ($rejectedHistory) {
                        // Check if there are any histories created after the rejection
                        $historiesAfterRejection = $histories->where('id', '>', $rejectedHistory->id);
                        // If there are histories after rejection, it was resubmitted
                        if ($historiesAfterRejection->isNotEmpty()) {
                            // Check if any of those are pending
                            return $historiesAfterRejection->where('status', 0)->isNotEmpty();
                        }
                        return false; // Rejected and not resubmitted
                    }

                    return true;
                })->count(),
                'Approved' => $requests->filter(function ($req) {
                    $wf = $req->workflow ?? null;
                    if (!$wf) return false;
                    return (int) $wf->work_flow_completed === 1;
                })->count(),
                'Rejected' => $requests->filter(function ($req) {
                    $wf = $req->workflow ?? null;
                    if (!$wf) return false;
                    $histories = $wf->histories ?? collect();

                    // Check if there are pending histories (resubmitted)
                    $hasPending = $histories->where('status', 0)->isNotEmpty();
                    if ($hasPending) return false; // Not rejected if resubmitted

                    // Check for rejection
                    $rejectedHistory = $histories->where('status', 2)->sortByDesc('id')->first();
                    if ($rejectedHistory) {
                        // Check if there are histories after rejection (resubmitted)
                        $historiesAfterRejection = $histories->where('id', '>', $rejectedHistory->id);
                        // If resubmitted, it's not rejected anymore
                        return $historiesAfterRejection->isEmpty();
                    }

                    return $req->status === 'rejected';
                })->count(),
            ];

            return view('oncall_requests.my_requests', compact('requests', 'statusCounts'));
        }
    }

    public function show($id)
    {
        try {
            $request = OnCallRequest::with([
                'user',
                'workflow.histories.attendedBy',
                'workflow.histories.approver',
            ])->findOrFail($id);

            if ($request->user_id !== Auth::id() && !Auth::user()->hasAnyRole(['in-charge', 'line-manager', 'hr', 'cms', 'cfo', 'coo'])) {
                return redirect()->route('oncall_requests.index')
                    ->with('error', 'You are not authorized to view this request.');
            }

            return view('oncall_requests.show', compact('request'));
        } catch (\Exception $e) {
            return redirect()->route('oncall_requests.index')
                ->with('error', 'Failed to load the request. Please try again.');
        }
    }

    public function edit(OnCallRequest $onCallRequest)
    {
        // Only owner can edit, and only when rejected
        abort_unless(
            Auth::id() === $onCallRequest->user_id && $onCallRequest->status === 'rejected',
            403,
            'You can only edit your own rejected request.'
        );

        $user = Auth::user();

        // Build months list Jan..current (inclusive)
        $now = Carbon::now();
        $months = [];
        $cursor = Carbon::create($now->year, 1, 1);
        while ($cursor->lte($now)) {
            $months[] = $cursor->format('F Y');
            $cursor->addMonth();
        }

        // Ensure "previous month" shows even when it's last year's December
        $defaultClaimMonth = $now->copy()->subMonthNoOverflow()->format('F Y');
        if (!in_array($defaultClaimMonth, $months, true)) {
            array_unshift($months, $defaultClaimMonth);
        }
        // Optional: most recent first
        $months = array_values(array_unique(array_reverse($months)));

        // From your existing setup
        $educationLevels = array_keys($this->onCallRates);
        $onCallRates     = $this->onCallRates;

        // Pre-fill
        $selectedMonth = trim(($onCallRequest->locum_month ?? '') . ' ' . ($onCallRequest->locum_year ?? ''));
        $existingDays  = $onCallRequest->worked_days ?? [];

        return view('oncall_requests.edit', compact(
            'user',
            'months',
            'educationLevels',
            'onCallRates',
            'onCallRequest',
            'selectedMonth',
            'existingDays',
            'defaultClaimMonth'
        ));
    }

    public function update(Request $request, OnCallRequest $onCallRequest)
    {
        // Only owner + rejected
        abort_unless(
            Auth::id() === $onCallRequest->user_id && $onCallRequest->status === 'rejected',
            403,
            'You can only update your own rejected request.'
        );

        try {
            $validated = $request->validate([
                'education_level'       => ['required', Rule::in(array_keys($this->onCallRates))],
                'locum_month'           => ['required', 'string', 'regex:/^(January|February|March|April|May|June|July|August|September|October|November|December) [0-9]{4}$/'],
                'worked_days'           => ['required', 'array', 'min:1'],
                'worked_days.*.worked'  => ['required', 'in:0,1'],
                'worked_days.*.hours'   => ['required_if:worked_days.*.worked,1', 'numeric', 'min:0', 'max:24'],
                'worked_days.*.reason'  => ['nullable', 'string', 'max:1000'],
                'description'           => ['nullable', 'string', 'max:1000'],
                'has_special_task'      => ['required', 'in:0,1'],
                'special_task_document' => ['nullable', 'file', 'mimes:pdf,doc,docx,jpg,jpeg,png', 'max:10240'],
            ]);
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors())->withInput();
        }
        // If user says "Yes" but neither uploaded a new file nor had an existing one => error
        $wantsSpecial = (int)$validated['has_special_task'] === 1;
        if ($wantsSpecial && !$request->hasFile('special_task_document') && empty($onCallRequest->special_task_path)) {
            return back()
                ->withErrors(['special_task_document' => 'Please upload the supportive document for the special task.'])
                ->withInput();
        }


        //current & future months
        $tz = config('app.timezone', 'Africa/Dar_es_Salaam');
        $claimedMonth = \Carbon\Carbon::createFromFormat('F Y', $validated['locum_month'], $tz)->startOfMonth();
        $currentMonth = \Carbon\Carbon::now($tz)->startOfMonth();

        if ($claimedMonth->greaterThanOrEqualTo($currentMonth)) {
            return back()->withErrors([
                'locum_month' => 'You cannot submit or claim for the current or a future month. Please select a past month.'
            ])->withInput();
        }
        // Split "F Y" into month & year
        [$monthName, $year] = explode(' ', $validated['locum_month']);

        // Recompute totals (same approach as store())
        $totalDays  = 0;
        $totalHours = 0.0;

        foreach ($validated['worked_days'] as $dateStr => $data) {
            $date = \DateTime::createFromFormat('Y-m-d', $dateStr);
            if (!$date) {
                continue;
            }

            if ((int)$data['worked'] === 1) {
                if (empty($data['hours']) || floatval($data['hours']) <= 0) {
                    return back()
                        ->withErrors(['worked_days' => "Hours must be greater than 0 for {$dateStr}"])
                        ->withInput();
                }
                $totalDays++;
                $totalHours += floatval($data['hours']);
            }
        }
        if ($totalDays === 0) {
            return back()->withErrors(['worked_days' => 'At least one day must be selected'])->withInput();
        }

        $rate        = $this->onCallRates[$validated['education_level']];
        $totalAmount = $totalDays * $rate;

        // Check if requester is a line manager (same logic as store method)
        $requester = $onCallRequest->user;
        $isLineManagerRequester = $requester && $requester->hasRole('line-manager') && !$requester->hasRole('hr');

        DB::beginTransaction();
        try {
            // Handle special-task file
            $specialPath  = $onCallRequest->special_task_path;
            $originalName = $onCallRequest->special_task_original_name;
            $mime         = $onCallRequest->special_task_mime;
            $size         = $onCallRequest->special_task_size;

            if ($wantsSpecial && $request->hasFile('special_task_document')) {
                $file = $request->file('special_task_document');

                // (Optional) delete the previous file to save space
                if ($specialPath && Storage::disk('public')->exists($specialPath)) {
                    Storage::disk('public')->delete($specialPath);
                }

                // Save new file
                $specialPath  = $file->store('oncall/special_tasks', 'public');
                $originalName = $file->getClientOriginalName();
                $mime         = $file->getClientMimeType();
                $size         = $file->getSize();
            }
            // Update the request
            $onCallRequest->update([
                'education_level'       => $validated['education_level'],
                'locum_month'           => $monthName,
                'locum_year'            => $year,
                'number_of_days'        => $totalDays,
                'total_hours'           => $totalHours,
                'total_amount'          => $totalAmount,
                'total_amount_payable'  => $totalAmount,
                'worked_days'           => $validated['worked_days'],
                'description'           => $validated['description'],
                'status'                => 'pending',
                // special-task fields
                'has_special_task'           => $wantsSpecial,
                'special_task_path'          => $specialPath,
                'special_task_original_name' => $originalName,
                'special_task_mime'          => $mime,
                'special_task_size'          => $size,
            ]);

            // (Re)create / reuse workflow
            $workflow = $onCallRequest->workflow ?? Workflow::create([
                'on_call_request_id'  => $onCallRequest->id,
                'user_id'             => $onCallRequest->user_id,
                'work_flow_status'    => 0,
                'work_flow_completed' => 0,
            ]);

            // Clean any leftover pending histories
            $workflow->histories()->where('status', 0)->delete();

            $firstApprover = null;
            $successMessage = '';

            // If line manager is resubmitting, always route to HEC (if mapped) then HR
            if ($isLineManagerRequester) {
                $department = $requester->department;

                // Always try to route to HEC first (if department has HEC mapped)
                $hecApprover = $this->resolveHecApproverLax($department, null, $workflow);

                if ($hecApprover) {
                    // Route to HEC member, then HR
                    WorkFlowHistory::create([
                        'work_flow_id' => $workflow->id,
                        'forwarded_by' => Auth::id(),
                        'attended_by'  => $hecApprover->id,
                        'step_name'    => 'HEC Approval',
                        'action_taken' => 'Forwarded',
                        'status'       => 0,
                        'on_call_request_status' => 3, // pending HEC
                        'remark'       => 'On-call request resubmitted by Line Manager — pending HEC approval.',
                    ]);

                    $firstApprover = $hecApprover;
                    $successMessage = 'On-call request updated and resubmitted to HEC.';
                } else {
                    // No HEC mapped, route directly to HR
                    $hrApprover = User::whereHas('roles', fn($q) => $q->where('name', 'hr'))
                        ->where('approvelocum', 1)
                        ->first();

                    if (!$hrApprover) {
                        DB::rollBack();
                        return back()->withErrors(['error' => 'No HR approver found. Please contact an administrator.'])->withInput();
                    }

                    WorkFlowHistory::create([
                        'work_flow_id' => $workflow->id,
                        'forwarded_by' => Auth::id(),
                        'attended_by'  => $hrApprover->id,
                        'step_name'    => 'HR Approval',
                        'action_taken' => 'Forwarded',
                        'status'       => 0,
                        'on_call_request_status' => 4, // pending HR
                        'remark'       => 'On-call request resubmitted by Line Manager — pending HR approval (no HEC mapped for department).',
                    ]);

                    $firstApprover = $hrApprover;
                    $successMessage = 'On-call request updated and resubmitted to HR.';
                }
            } else {
                // Normal flow: route to Line Manager
                $lineManager = User::whereHas('roles', fn($q) => $q->where('name', 'line-manager'))
                    ->where('deptId', $requester->deptId)
                    ->first();

                if (!$lineManager) {
                    DB::rollBack();
                    return back()->withErrors(['error' => 'No Line Manager found for your department.'])->withInput();
                }

                WorkFlowHistory::create([
                    'work_flow_id'            => $workflow->id,
                    'forwarded_by'            => Auth::id(),
                    'attended_by'             => $lineManager->id,
                    'step_name'               => 'Line Manager Approval',
                    'action_taken'            => 'Forwarded',
                    'status'                  => 0, // pending
                    'on_call_request_status'  => 0, // keep consistent with your store()
                    'remark'                  => 'On-call request resubmitted to Line Manager after rejection.',
                ]);

                $firstApprover = $lineManager;
                $successMessage = 'On-call request updated and resubmitted to Line Manager.';
            }

            // Reset summary flags
            $workflow->update([
                'work_flow_status'    => 0,
                'work_flow_completed' => 0,
            ]);

            DB::commit();

            // Send emails via queue, after commit
            try {
                Mail::to($onCallRequest->user->email)->queue(new OnCallSubmissionNotification(
                    onCallRequest: $onCallRequest,
                    workflow: $workflow,
                    submitter: $onCallRequest->user
                ));
            } catch (\Throwable $mailEx) {
                Log::error('Mail to submitter failed', ['err' => $mailEx->getMessage()]);
            }

            if ($firstApprover) {
                try {
                    Mail::to($firstApprover->email)->queue(new OnCallApprovalRequest(
                        onCallRequest: $onCallRequest,
                        workflow: $workflow,
                        submitter: $onCallRequest->user,
                        approver: $firstApprover
                    ));
                } catch (\Throwable $mailEx) {
                    Log::error('Mail to approver failed', ['err' => $mailEx->getMessage()]);
                }
            }

            return redirect()
                ->route('oncall_requests.index')
                ->with('success', $successMessage);
        } catch (\Throwable $e) {
            DB::rollBack();
            report($e);
            return back()->with('error', 'Failed to update request. Please try again.')->withInput();
        }
    }

    public function bulkApprove(Request $request)
    {
        Log::debug('OnCall bulkApprove received:', $request->all());

        try {
            $request->validate([
                'request_ids'      => 'required|array|min:1',
                'request_ids.*'    => 'exists:workflows,id',
                'action'           => 'required|in:approve,reject',
                'rejection_reason' => 'required_if:action,reject|string|max:255',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('OnCall bulkApprove validation failed:', [
                'errors' => $e->errors(),
                'input'  => $request->all(),
            ]);

            $payload = [
                'icon'  => 'error',
                'title' => 'Validation Error',
                'text'  => implode("\n", array_merge(...array_values($e->errors()))),
            ];

            return $request->wantsJson()
                ? response()->json($payload, 422)
                : back()->with('error', nl2br(e($payload['text'])));
        }

        $actor = Auth::user();

        // Load only workflows where this user currently has a pending step
        $workflows = Workflow::whereIn('id', $request->request_ids)
            ->whereHas('histories', fn($q) => $q->where('attended_by', $actor->id)->where('status', 0))
            ->with([
                // NOTE: we'll use requester via workflow->user (aligns with Locum's approach)
                'histories' => fn($q) => $q->where('attended_by', $actor->id)->where('status', 0),
                'user.department.hec',
                'onCallRequest.user', // Load the onCallRequest and its user
            ])
            ->get();

        Log::info('OnCall bulkApprove: Loaded workflows', [
            'count' => $workflows->count(),
            'workflow_ids' => $workflows->pluck('id')->toArray(),
            'actor_id' => $actor->id,
            'action' => $request->action,
        ]);

        if ($workflows->isEmpty()) {
            $payload = [
                'icon'  => 'error',
                'title' => 'Error',
                'text'  => 'No authorized workflows found for this action.',
            ];
            return $request->wantsJson()
                ? response()->json($payload, 403)
                : back()->with('error', $payload['text']);
        }

        $hecApproverCache = []; // [workflow_id => User]
        $hrApproverCache  = null;

        // === Pre-checks (fail fast like Locum) ===
        foreach ($workflows as $wf) {
            $current = $wf->histories->first();
            if (!$current) {
                $payload = [
                    'icon'  => 'error',
                    'title' => 'Error',
                    'text'  => 'One or more workflows have no pending history.',
                ];
                return $request->wantsJson()
                    ? response()->json($payload, 403)
                    : back()->with('error', $payload['text']);
            }

            if ($request->action !== 'approve') {
                continue;
            }

            $department = $wf->user?->department;
            $isThree    = $department && (int)($department->has_oncall_three_level_approval) === 1;

            if ($current->step_name === 'Line Manager Approval') {
                if ($isThree) {
                    // Non-strict HEC lookup (match Locum behavior)
                    $hecUser = $this->resolveHecApproverLax($department, $current, $wf);
                    if (!$hecUser) {
                        Log::error('OnCall: no HEC Member available for required level', [
                            'workflow_id'    => $wf->id,
                            'dept_id'        => $department->dept_id ?? $department->id ?? null,
                            'dept_name'      => $department->dept_name ?? null,
                            'hec_id'         => $department->hec_id ?? null,
                            'hec_level_name' => optional($department->hec)->hec_level_name,
                        ]);
                        $payload = [
                            'icon'  => 'error',
                            'title' => 'Error',
                            'text'  => "No user found with role “" . strtolower((string) optional($department->hec)->hec_level_name) . "”. Please assign at least one.",
                        ];
                        return $request->wantsJson()
                            ? response()->json($payload, 500)
                            : back()->with('error', $payload['text']);
                    }
                    $hecApproverCache[$wf->id] = $hecUser;
                } else {
                    // Next: HR
                    if (!$hrApproverCache) {
                        $hrApproverCache = User::whereHas('roles', fn($q) => $q->where('name', 'hr'))
                            ->where('approvelocum', 1)
                            ->first();
                    }
                    if (!$hrApproverCache) {
                        $payload = [
                            'icon'  => 'error',
                            'title' => 'Error',
                            'text'  => 'No HR approver found. Please contact an administrator.',
                        ];
                        return $request->wantsJson()
                            ? response()->json($payload, 500)
                            : back()->with('error', $payload['text']);
                    }
                }
            }

            if ($current->step_name === 'HEC Approval') {
                if (!$hrApproverCache) {
                    $hrApproverCache = User::whereHas('roles', fn($q) => $q->where('name', 'hr'))
                        ->where('approvelocum', 1)
                        ->first();
                }
                if (!$hrApproverCache) {
                    $payload = [
                        'icon'  => 'error',
                        'title' => 'Error',
                        'text'  => 'No HR approver found. Please contact an administrator.',
                    ];
                    return $request->wantsJson()
                        ? response()->json($payload, 500)
                        : back()->with('error', $payload['text']);
                }
            }
        }

        // === Process like Locum ===
        DB::beginTransaction();

        try {
            $forwardedCount = 0;
            $approvedCount = 0;
            $rejectedCount = 0;
            $forwardedSteps = [];

            foreach ($workflows as $wf) {
                $current = $wf->histories()
                    ->where('attended_by', $actor->id)
                    ->where('status', 0)
                    ->first();

                if (!$current) {
                    // If someone took it in the meantime, just skip gracefully
                    Log::warning('OnCall: pending history missing during process', ['wf' => $wf->id]);
                    continue;
                }

                if ($request->action === 'reject') {
                    // Mirror Locum rejection behavior
                    $current->update([
                        'status'                 => 2,
                        'action_taken'           => 'Rejected',
                        'rejection_reason'       => $request->rejection_reason,
                        'who_approve'            => $actor->id,
                        'attend_date'            => now(),
                        // Keep On-Call status field consistent; using 5 as "Rejected" like your earlier On-Call
                        'on_call_request_status' => 5,
                    ]);

                    // Close any other pending histories (consistent with bulkReject)
                    WorkFlowHistory::where('work_flow_id', $wf->id)
                        ->where('status', 0)
                        ->where('id', '!=', $current->id)
                        ->update([
                            'status'       => 2,
                            'action_taken' => 'Auto-Closed',
                        ]);

                    $wf->update([
                        'work_flow_status'    => 2,
                        'work_flow_completed' => 1,
                    ]);

                    // Update request status
                    $wf->onCallRequest?->update(['status' => 'rejected']);

                    // Send rejection email to submitter
                    if ($wf->onCallRequest && $wf->onCallRequest->user && $wf->onCallRequest->user->email) {
                        try {
                            Mail::to($wf->onCallRequest->user->email)->queue(new \App\Mail\OnCallStatusUpdate(
                                onCallRequest: $wf->onCallRequest,
                                status: 'rejected',
                                approver: $actor,
                                reason: $request->rejection_reason,
                                approvalStep: $current->step_name ?? null
                            ));
                        } catch (\Throwable $mailEx) {
                            Log::error('OnCall bulkApprove: rejection email failed', [
                                'workflow_id' => $wf->id,
                                'err'         => $mailEx->getMessage(),
                            ]);
                        }
                    }

                    $rejectedCount++;
                    Log::info('OnCall: rejected', ['workflow_id' => $wf->id]);
                    continue;
                }

                // Store step name before update (in case it changes)
                $currentStepName = $current->step_name;

                // Approve current step
                $current->update([
                    'status'       => 1,
                    'action_taken' => 'Approved',
                    'who_approve'  => $actor->id,
                    'attend_date'  => now(),
                ]);

                // Refresh workflow to get updated relationships
                $wf->refresh();
                $wf->load(['user.department.hec', 'onCallRequest']);

                // Ensure department is loaded with oncall settings
                if ($wf->user && !$wf->user->relationLoaded('department')) {
                    $wf->user->load('department');
                }

                $department = $wf->user?->department;
                $isThree    = $department && (int)($department->has_oncall_three_level_approval) === 1;

                Log::info('OnCall approval processing', [
                    'workflow_id' => $wf->id,
                    'current_step' => $currentStepName,
                    'department' => $department?->dept_name,
                    'department_id' => $department?->id,
                    'is_three_level' => $isThree,
                    'has_oncall_three_level_approval' => $department?->has_oncall_three_level_approval,
                ]);

                $nextStep = null;
                $nextApprover = null;
                $nextCode = null; // Track On-Call codes similar to your previous scheme

                if ($currentStepName === 'Line Manager Approval') {
                    if ($isThree) {
                        $nextStep     = 'HEC Approval';
                        $nextApprover = $hecApproverCache[$wf->id]
                            ?? $this->resolveHecApproverLax($department, $current, $wf);
                        $nextCode     = 3; // pending HEC

                        Log::info('OnCall: Line Manager -> HEC', [
                            'workflow_id' => $wf->id,
                            'hec_approver' => $nextApprover?->id,
                            'hec_approver_name' => $nextApprover ? trim(($nextApprover->fname ?? '') . ' ' . ($nextApprover->lname ?? '')) : null,
                        ]);
                    } else {
                        $nextStep     = 'HR Approval';
                        $nextApprover = $hrApproverCache ?: User::whereHas('roles', fn($q) => $q->where('name', 'hr'))
                            ->where('approvelocum', 1)
                            ->first();
                        $nextCode     = 4; // pending HR

                        Log::info('OnCall: Line Manager -> HR', [
                            'workflow_id' => $wf->id,
                            'hr_approver' => $nextApprover?->id,
                            'hr_approver_name' => $nextApprover ? trim(($nextApprover->fname ?? '') . ' ' . ($nextApprover->lname ?? '')) : null,
                        ]);
                    }
                } elseif ($currentStepName === 'HEC Approval') {
                    $nextStep     = 'HR Approval';
                    $nextApprover = $hrApproverCache ?: User::whereHas('roles', fn($q) => $q->where('name', 'hr'))
                        ->where('approvelocum', 1)
                        ->first();
                    $nextCode     = 4; // pending HR

                    Log::info('OnCall: HEC -> HR', [
                        'workflow_id' => $wf->id,
                        'hr_approver' => $nextApprover?->id,
                        'hr_approver_name' => $nextApprover ? trim(($nextApprover->fname ?? '') . ' ' . ($nextApprover->lname ?? '')) : null,
                    ]);
                }

                Log::info('OnCall: Next step determination', [
                    'workflow_id' => $wf->id,
                    'next_step' => $nextStep,
                    'next_approver' => $nextApprover?->id,
                    'next_code' => $nextCode,
                ]);

                if ($nextStep && $nextApprover) {
                    // reflect overall state on the row we just approved
                    $current->update(['on_call_request_status' => $nextCode]);

                    try {
                        $newHistory = WorkFlowHistory::create([
                            'work_flow_id'           => $wf->id,
                            'forwarded_by'           => $actor->id,
                            'attended_by'            => $nextApprover->id,
                            'step_name'              => $nextStep,
                            'action_taken'           => 'Forwarded',
                            'status'                 => 0,
                            'remark'                 => "Forwarded to {$nextStep}",
                            'on_call_request_status' => $nextCode,
                        ]);

                        Log::info('OnCall: Created next workflow history', [
                            'workflow_id' => $wf->id,
                            'history_id' => $newHistory->id,
                            'next_step' => $nextStep,
                            'next_approver' => $nextApprover->id,
                        ]);
                    } catch (\Exception $e) {
                        Log::error('OnCall: Failed to create workflow history', [
                            'workflow_id' => $wf->id,
                            'error' => $e->getMessage(),
                            'trace' => $e->getTraceAsString(),
                        ]);
                        throw $e;
                    }

                    // keep parent request "pending" while routing
                    $wf->onCallRequest?->update(['status' => 'pending']);

                    $forwardedCount++;
                    if (!in_array($nextStep, $forwardedSteps)) {
                        $forwardedSteps[] = $nextStep;
                    }

                    // Send approval request email to the next approver
                    if ($wf->onCallRequest && $wf->onCallRequest->user) {
                        try {
                            Mail::to($nextApprover->email)->queue(new OnCallApprovalRequest(
                                onCallRequest: $wf->onCallRequest,
                                workflow: $wf,
                                submitter: $wf->onCallRequest->user,
                                approver: $nextApprover
                            ));
                        } catch (\Throwable $mailEx) {
                            Log::error('OnCall: email to next approver failed', [
                                'workflow_id' => $wf->id,
                                'err'         => $mailEx->getMessage(),
                            ]);
                        }
                    }

                    Log::info('OnCall forwarded', [
                        'workflow_id' => $wf->id,
                        'next_step'   => $nextStep,
                        'approver'    => $nextApprover->id,
                        'code'        => $nextCode,
                    ]);
                } else {
                    // Terminal approval (no further step)
                    Log::warning('OnCall: Terminal approval (no next step) - This might be unexpected!', [
                        'workflow_id' => $wf->id,
                        'current_step' => $currentStepName,
                        'next_step' => $nextStep,
                        'next_approver' => $nextApprover?->id,
                        'next_approver_name' => $nextApprover ? trim(($nextApprover->fname ?? '') . ' ' . ($nextApprover->lname ?? '')) : null,
                        'department' => $department?->dept_name,
                        'is_three_level' => $isThree,
                    ]);

                    $current->update(['on_call_request_status' => 6]); // Approved Final
                    $wf->update([
                        'work_flow_status'    => 1,
                        'work_flow_completed' => 1,
                    ]);
                    $wf->onCallRequest?->update(['status' => 'approved']);

                    $approvedCount++;

                    // Notify submitter of final approval
                    if ($wf->onCallRequest && $wf->onCallRequest->user) {
                        try {
                            Mail::to($wf->onCallRequest->user->email)->queue(new \App\Mail\OnCallStatusUpdate(
                                onCallRequest: $wf->onCallRequest,
                                status: 'approved',
                                approver: $actor,
                                reason: null,
                                approvalStep: $current->step_name
                            ));
                        } catch (\Throwable $mailEx) {
                            Log::error('OnCall: email to submitter (final approval) failed', [
                                'workflow_id' => $wf->id,
                                'err'         => $mailEx->getMessage(),
                            ]);
                        }
                    }

                    Log::info('OnCall approved - terminal', ['workflow_id' => $wf->id]);
                }
            }

            DB::commit();

            // Build informative response message
            $messages = [];
            if ($forwardedCount > 0) {
                $stepText = implode(' and ', $forwardedSteps);
                $messages[] = "{$forwardedCount} request(s) approved and forwarded to {$stepText}.";
            }
            if ($approvedCount > 0) {
                $messages[] = "{$approvedCount} request(s) fully approved.";
            }
            if ($rejectedCount > 0) {
                $messages[] = "{$rejectedCount} request(s) rejected.";
            }

            $responseText = !empty($messages) ? implode(' ', $messages) : 'On-call requests processed successfully.';

            // Same response behavior as Locum: redirect with flash on normal requests; JSON when asked
            if ($request->wantsJson()) {
                return response()->json([
                    'icon'  => 'success',
                    'title' => 'Success',
                    'text'  => $responseText,
                ]);
            }

            return redirect()
                ->route('oncall_requests.view')
                ->with('success', $responseText);
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('OnCall bulkApprove failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            $payload = [
                'icon'  => 'error',
                'title' => 'Error',
                'text'  => 'Failed to process approval: ' . $e->getMessage(),
            ];

            return $request->wantsJson()
                ? response()->json($payload, 500)
                : back()->with('error', $payload['text']);
        }
    }

    private function resolveHecApproverLax($department, ?WorkFlowHistory $currentHistory, ?Workflow $workflow): ?User
    {
        if (!$department || !$department->hec_id) {
            Log::warning('HEC resolve (lax): missing department/hec_id', [
                'workflow_id' => $workflow?->id,
                'dept'        => $department ? $department->toArray() : null,
            ]);
            return null;
        }

        $hec = $department->hec ?? \App\Models\Hec::find($department->hec_id);
        if (!$hec) {
            Log::error('HEC resolve (lax): department has hec_id but no Hec row', [
                'workflow_id' => $workflow?->id,
                'hec_id'      => $department->hec_id,
            ]);
            return null;
        }

        $levelKey = strtolower(trim($hec->hec_level_name));
        $roleMap  = [
            'coo'  => 'coo',
            'cfo'  => 'cfo',
            'cms'  => 'cms',
            // aliases
            'chief operating officer'  => 'coo',
            'chief financial officer'  => 'cfo',
            'chief medical specialist' => 'cms',
        ];
        $roleSlug = $roleMap[$levelKey] ?? null;

        if (!$roleSlug) {
            Log::error('HEC resolve (lax): unrecognized hec_level_name', [
                'workflow_id'      => $workflow?->id,
                'hec_level_name'   => $hec->hec_level_name,
                'normalized_level' => $levelKey,
            ]);
            return null;
        }

        // Any user with that role (no strict mapping), excluding current approver & requester
        $excludeIds = [];
        if ($currentHistory && $currentHistory->attended_by) {
            $excludeIds[] = $currentHistory->attended_by;
        }
        if ($workflow && $workflow->user_id) {
            $excludeIds[] = $workflow->user_id;
        }

        $query = User::whereHas('roles', fn($q) => $q->where('name', $roleSlug));
        if (!empty($excludeIds)) {
            $query->whereNotIn('id', $excludeIds);
        }

        return $query->orderBy('id')
            ->get(['id', 'email', 'fname', 'mname', 'lname'])
            ->first();
    }

    public function bulkReject(Request $request)
    {
        Log::debug('OnCall bulk reject payload', $request->all());

        try {
            $validated = $request->validate([
                'request_ids'      => ['required', 'array', 'min:1'],
                'request_ids.*'    => ['exists:workflows,id'],
                'rejection_reason' => ['required', 'string', 'max:255'],
            ]);
        } catch (ValidationException $e) {
            Log::error('OnCall bulkReject validation error', ['errors' => $e->errors()]);
            return back()->withErrors($e->errors())->withInput();
        }

        $actor = Auth::user();
        Log::debug('Authenticated User', ['id' => $actor->id, 'roles' => $actor->getRoleNames()->toArray()]);

        $workflows = Workflow::whereIn('id', $validated['request_ids'])
            ->whereHas('histories', function ($q) use ($actor) {
                $q->where('attended_by', $actor->id)
                    ->where('status', 0);
            })
            ->with(['onCallRequest', 'onCallRequest.user', 'histories'])
            ->get();
        Log::debug('Filtered Workflows', ['count' => $workflows->count(), 'ids' => $workflows->pluck('id')->toArray()]);

        if ($workflows->isEmpty()) {
            return back()->with('error', 'No authorized workflows found for this action.');
        }

        DB::beginTransaction();
        try {
            $rejected = 0;
            $skipped = [];
            $emailsToSend = []; // Initialize email array
            foreach ($workflows as $wf) {
                $current = $wf->histories()
                    ->where('attended_by', $actor->id)
                    ->where('status', 0)
                    ->lockForUpdate()
                    ->first();
                Log::debug('Processing Workflow', ['workflow_id' => $wf->id, 'current_step' => $current ? $current->toArray() : null]);

                if (!$current) {
                    $skipped[] = $wf->id;
                    continue;
                }

                $current->update([
                    'status'                 => 2,
                    'action_taken'           => 'Rejected',
                    'rejection_reason'       => $validated['rejection_reason'],
                    'who_approve'            => $actor->id,
                    'attend_date'            => now(),
                    'on_call_request_status' => 5,
                ]);

                // Close any other pending histories (exclude the current one being rejected)
                WorkFlowHistory::where('work_flow_id', $wf->id)
                    ->where('status', 0)
                    ->where('id', '!=', $current->id)
                    ->update([
                        'status'       => 2,
                        'action_taken' => 'Auto-Closed',
                    ]);

                $wf->update([
                    'work_flow_status'    => 2,
                    'work_flow_completed' => 1,
                ]);

                $onCallRequest = $wf->onCallRequest;
                if ($onCallRequest) {
                    $onCallRequest->update(['status' => 'rejected']);

                    // Prepare rejection email to the submitter (send later)
                    if ($onCallRequest->user && $onCallRequest->user->email) {
                        $emailsToSend[] = [
                            'to'       => $onCallRequest->user->email,
                            'mailable' => new \App\Mail\OnCallStatusUpdate(
                                onCallRequest: $onCallRequest,
                                status: 'rejected',
                                approver: $actor,
                                reason: $validated['rejection_reason'],
                                approvalStep: $current->step_name ?? null
                            ),
                            'label'    => 'submitter_rejection'
                        ];
                    }
                }

                $rejected++;
            }
            DB::commit();
            // ==== SEND EMAILS (QUEUED) AFTER COMMIT ====
            if (!empty($emailsToSend)) {
                foreach ($emailsToSend as $job) {
                    try {
                        Mail::to($job['to'])->queue($job['mailable']);
                    } catch (\Throwable $mailEx) {
                        Log::error('OnCall bulkReject: mail queue failed', [
                            'label' => $job['label'] ?? null,
                            'to'    => $job['to'],
                            'err'   => $mailEx->getMessage(),
                        ]);
                    }
                }
            }
            $msg = "{$rejected} request(s) rejected successfully.";
            if (count($skipped)) {
                $msg .= ' Skipped (already acted): ' . implode(', ', $skipped) . '.';
            }
            return redirect()->route('oncall_requests.index')->with('success', $msg);
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('OnCall bulkReject failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return back()->with('error', 'Failed to reject requests: ' . $e->getMessage());
        }
    }

    public function approvedRequests(Request $request)
    {
        $user  = Auth::user();
        $scope = $this->allowedDepartmentIds($user); // your existing visibility

        // Payroll Year/Month (default: now in TZ)
        $now      = Carbon::now('Africa/Dar_es_Salaam');
        $payYear  = (int)($request->query('pay_year',  $now->year));
        $payMonth = $this->resolveMonth($request->query('pay_month', $now->format('F'))) ?? (int)$now->month;

        // Optional department filter (within visibility)
        if ($scope === '__SELF__') {
            $deptIds = $user->deptId ? [(int)$user->deptId] : [];
        } elseif (is_array($scope)) {
            $deptIds = $scope;
        } else {
            $deptIds = [];
        }
        $departments = empty($deptIds) ? collect() : Departments::whereIn('id', $deptIds)->orderBy('dept_name')->get();
        $filterDept  = $request->query('department');

        // Pull rows in scope that were APPROVED BY ME during the payroll month
        $q = OnCallRequest::with(['user.department', 'workflow.histories']);

        // Visibility
        if ($scope === '__SELF__') {
            $q->where('user_id', $user->id);
        } elseif (is_array($scope) && !empty($scope)) {
            $q->whereHas('user', fn($u) => $u->whereIn('deptId', $scope));
        } else {
            $q->whereRaw('1=0');
        }

        // Restrict to requests where *this user* has an *Approved* action in the payroll year/month
        $q->whereHas('workflow.histories', function ($h) use ($user, $payYear, $payMonth) {
            $h->where('attended_by', $user->id)
                ->where('action_taken', 'Approved')
                ->whereYear('created_at', $payYear)
                ->whereMonth('created_at', $payMonth);
        });

        // Optional department filter
        if ($filterDept && ($scope !== '__SELF__') && in_array((int)$filterDept, $deptIds, true)) {
            $q->whereHas('user', fn($u) => $u->where('deptId', (int)$filterDept));
        }

        // In payroll we typically pay APPROVED only; but you can still show status column
        $q->whereIn('status', ['approved', 'pending', 'rejected']);

        $approvedRequests = $q->orderBy('created_at', 'desc')->get();

        // Build month name for the UI select
        $monthName = $this->numToFullMonth($payMonth);

        return view('oncall_requests.approved_requests', [
            'approvedRequests' => $approvedRequests,
            'departments'      => $departments,
            'payYear'          => $payYear,
            'payMonth'         => $monthName, // "October"
        ]);
    }


    public function consumptionReport(Request $request)
    {
        $user  = Auth::user();
        $scope = $this->allowedDepartmentIds($user);

        $q = OnCallRequest::select(
            'departments.dept_name as department_name',
            'on_call_requests.locum_month as month',
            'on_call_requests.locum_year  as year',
            DB::raw('SUM(on_call_requests.total_amount_payable) as total_amount')
        )
            ->join('users', 'on_call_requests.user_id', '=', 'users.id')
            ->join('departments', 'users.deptId', '=', 'departments.id')
            ->where('on_call_requests.status', 'approved')
            ->groupBy('departments.dept_name', 'on_call_requests.locum_month', 'on_call_requests.locum_year');

        if ($scope === '__SELF__') {
            $q->where('on_call_requests.user_id', $user->id);
        } elseif (is_array($scope) && !empty($scope)) {
            $q->whereIn('users.deptId', $scope);
        } else {
            $q->whereRaw('1=0');
        }

        if ($year = $request->query('year'))   $q->where('on_call_requests.locum_year', $year);
        if ($month = $request->query('month')) $q->where('on_call_requests.locum_month', $month);

        return response()->json(['data' => $q->get()]);
    }


    public function reports(Request $request)
    {
        $user = Auth::user();
        $years = OnCallRequest::selectRaw('DISTINCT locum_year')->pluck('locum_year')->sort()->values();
        $months = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
        $departments = Departments::whereIn('id', $this->allowedDepartmentIds($user))->get();

        return view('oncall_requests.reports', compact('years', 'months', 'departments'));
    }

    public function reportsData(Request $request)
    {
        $user = Auth::user();

        $rows = $this->applyReportFilters($request, $this->baseReportQuery($user), $user)
            ->orderBy('ocr.created_at', 'desc')
            ->get();

        $summary = $this->applyReportFilters(
            $request,
            DB::table('on_call_requests as ocr')
                ->join('users as u', 'ocr.user_id', '=', 'u.id')
                ->leftJoin('departments as d', 'u.deptId', '=', 'd.id')
                ->selectRaw('d.dept_name as department, SUM(ocr.total_amount_payable) as total_amount'),
            $user
        )
            ->groupBy('d.dept_name')
            ->get();

        $grandTotal = $rows->sum('total_amount_payable');

        return response()->json([
            'rows'       => $rows,
            'summary'    => $summary,
            'grandTotal' => $grandTotal,
        ]);
    }

    public function report(Request $request)
    {
        $user = Auth::user();

        // Restrict access to HR role only
        if (!$user->hasRole('hr')) {
            abort(403, 'Unauthorized. Only HR users can access this area.');
        }

        // Summary filters
        $summaryYear  = $request->query('summary_year');
        $summaryMonth = $request->query('summary_month');

        // Get all requests EXCEPT those fully approved by HR (to show pending requests that need attention)
        $actionedRequests = OnCallRequest::whereHas('workflow')
            ->whereDoesntHave('workflow.histories', function ($query) {
                // Exclude requests where HR has approved (status = 1 and step_name = 'HR Approval')
                $query->where('step_name', 'HR Approval')
                    ->where('status', 1);
            })
            ->with([
                'user.department',
                'workflow.histories' => function ($q) {
                    $q->with(['attendedBy', 'forwardedBy', 'approver'])
                        ->orderBy('id', 'asc');
                },
            ])
            ->orderBy('created_at', 'desc')
            ->get();

        // Build options from existing data
        $summaryYearOptions = $actionedRequests
            ->filter(fn($r) => !is_null($r->created_at))
            ->map(fn($r) => \Carbon\Carbon::parse($r->created_at)->year)
            ->unique()->sortDesc()->values();

        $summaryMonthOptions = collect([1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12]);

        // Helper: resolve numeric month for each request
        $resolveMonth = function ($r) {
            try {
                if (!empty($r->locum_month)) {
                    return \Carbon\Carbon::parse('1 ' . $r->locum_month . ' ' . ($r->locum_year ?? now()->year))->month;
                }
            } catch (\Throwable $e) {
                // fallback below
            }
            return \Carbon\Carbon::parse($r->created_at)->month;
        };

        // Apply summary-only filters
        $summarySource = $actionedRequests
            ->when($summaryYear, function ($col) use ($summaryYear) {
                return $col->filter(fn($r) => (int)\Carbon\Carbon::parse($r->created_at)->year === (int)$summaryYear);
            })
            ->when($summaryMonth, function ($col) use ($summaryMonth, $resolveMonth) {
                return $col->filter(fn($r) => (int)$resolveMonth($r) === (int)$summaryMonth);
            })
            ->values();

        // Department summary
        $deptSummary = $summarySource
            ->groupBy(fn($r) => $r->user?->department?->dept_name ?? 'N/A')
            ->map(function ($rows) {
                $approved = $rows->filter(function ($r) {
                    return ($r->status ?? null) === 'approved';
                });

                $rejected = $rows->filter(function ($r) {
                    return ($r->status ?? null) === 'rejected';
                });

                return [
                    'requests'  => $rows->count(),
                    'employees' => $rows->pluck('user_id')->unique()->count(),
                    'approved'  => $approved->count(),
                    'rejected'  => $rejected->count(),
                    'hours'     => round((float)$rows->sum('total_hours'), 2),
                    'amount'    => (float)$rows->sum('total_amount_payable'),
                ];
            })
            ->sortKeys();

        // Grand totals - ONLY Approved
        $approvedOnly = $summarySource->filter(function ($r) {
            return ($r->status ?? null) === 'approved';
        });

        $grandTotals = [
            'requests'  => $approvedOnly->count(),
            'employees' => $approvedOnly->pluck('user_id')->unique()->count(),
            'approved'  => $approvedOnly->count(),
            'hours'     => round((float)$approvedOnly->sum('total_hours'), 2),
            'amount'    => (float)$approvedOnly->sum('total_amount_payable'),
        ];

        // === Payment Report: Claims approved by HR in selected month/year ===
        // Get payment report filters (default to current month/year if not specified)
        $paymentYearParam = $request->query('payment_year');
        $paymentYear = $paymentYearParam ? (int)$paymentYearParam : null;
        $paymentMonthParam = $request->query('payment_month');
        $paymentMonth = $paymentMonthParam ? $this->resolveMonth($paymentMonthParam) : null;

        // If no filters, default to current month/year
        $currentMonth = $paymentMonth ?? now()->month;
        $currentYear = $paymentYear ?? now()->year;

        $paymentReport = OnCallRequest::whereHas('workflow.histories', function ($q) use ($currentYear, $currentMonth, $paymentYear, $paymentMonth) {
            $q->where('step_name', 'HR Approval')
                ->where('status', 1); // Approved
            if ($paymentYear) {
                $q->whereYear('updated_at', $currentYear);
            }
            if ($paymentMonth) {
                $q->whereMonth('updated_at', $currentMonth);
            }
        })
            ->whereHas('workflow', fn($q) => $q->where('work_flow_completed', 1))
            ->with([
                'user.department',
                'workflow.histories' => function ($q) {
                    $q->where('step_name', 'HR Approval')
                        ->where('status', 1)
                        ->latest();
                },
            ])
            ->orderBy('created_at', 'desc')
            ->get();

        $paymentReportSummary = [
            'total_requests' => $paymentReport->count(),
            'total_employees' => $paymentReport->pluck('user_id')->unique()->count(),
            'total_hours' => round((float)$paymentReport->sum('total_hours'), 2),
            'total_amount' => (float)$paymentReport->sum('total_amount_payable'),
            'by_department' => $paymentReport->groupBy(fn($r) => $r->user?->department?->dept_name ?? 'N/A')
                ->map(function ($rows) {
                    return [
                        'count' => $rows->count(),
                        'employees' => $rows->pluck('user_id')->unique()->count(),
                        'hours' => round((float)$rows->sum('total_hours'), 2),
                        'amount' => (float)$rows->sum('total_amount_payable'),
                    ];
                })
                ->sortKeys(),
        ];

        // === Analytics/Trends: Department-wise statistics ===
        // Get filter parameters
        $analyticsDept = $request->query('analytics_dept');
        $fromYear = $request->query('from_year');
        $fromMonth = $request->query('from_month');
        $toYear = $request->query('to_year');
        $toMonth = $request->query('to_month');

        // Get all approved requests for trend analysis
        $allApproved = OnCallRequest::whereHas('workflow.histories', function ($q) {
            $q->where('step_name', 'HR Approval')
                ->where('status', 1);
        })
            ->whereHas('workflow', fn($q) => $q->where('work_flow_completed', 1))
            ->with(['user.department', 'workflow.histories' => function ($q) {
                $q->where('step_name', 'HR Approval')
                    ->where('status', 1)
                    ->latest();
            }])
            ->get();

        // Apply department filter if specified
        if ($analyticsDept && $analyticsDept !== '_all') {
            $allApproved = $allApproved->filter(function ($req) use ($analyticsDept) {
                return ($req->user?->department?->dept_name ?? 'N/A') === $analyticsDept;
            });
        }

        // Get all departments for filter dropdown
        $allDepartments = Departments::orderBy('dept_name', 'asc')->get();

        // Determine date range for monthly trends
        $startDate = null;
        $endDate = null;

        if ($fromYear && $fromMonth) {
            $startDate = Carbon::create($fromYear, $fromMonth, 1)->startOfMonth();
        }
        if ($toYear && $toMonth) {
            $endDate = Carbon::create($toYear, $toMonth, 1)->endOfMonth();
        }

        // If no date range specified, default to last 12 months
        if (!$startDate || !$endDate) {
            $endDate = now()->endOfMonth();
            $startDate = now()->subMonths(11)->startOfMonth();
        }

        // Get top departments by total amount for the chart (max 8 to keep it readable)
        $topDepts = $allApproved->groupBy(fn($r) => $r->user?->department?->dept_name ?? 'N/A')
            ->map(function ($rows) {
                return (float)$rows->sum('total_amount_payable');
            })
            ->sortByDesc(function ($amount) {
                return $amount;
            })
            ->take(8)
            ->keys()
            ->values()
            ->all();

        $monthlyTrends = [];
        $currentDate = $startDate->copy();

        // Generate monthly data for the date range
        while ($currentDate->lte($endDate)) {
            $monthYear = $currentDate->format('Y-m');
            $monthName = $currentDate->format('M Y'); // Month and year for chart

            $monthRequests = $allApproved->filter(function ($req) use ($currentDate) {
                $hrApproval = $req->workflow?->histories?->first();
                if (!$hrApproval) return false;
                $approvedAt = $hrApproval->updated_at ?? $hrApproval->created_at;
                if (!$approvedAt) return false;
                $approved = Carbon::parse($approvedAt);
                return $approved->year == $currentDate->year && $approved->month == $currentDate->month;
            });

            // Group by department for this month
            $byDept = $monthRequests->groupBy(fn($r) => $r->user?->department?->dept_name ?? 'N/A')
                ->map(function ($rows) {
                    return (float)$rows->sum('total_amount_payable') / 1000; // Convert to thousands
                });

            // Ensure all top departments are included (even with 0)
            $byDeptComplete = collect($topDepts)->mapWithKeys(function ($dept) use ($byDept) {
                return [$dept => $byDept->get($dept, 0)];
            });

            $monthlyTrends[$monthYear] = [
                'label' => $monthName,
                'count' => $monthRequests->count(),
                'amount' => (float)$monthRequests->sum('total_amount_payable'),
                'hours' => round((float)$monthRequests->sum('total_hours'), 2),
                'by_department' => $byDeptComplete->toArray(),
            ];

            $currentDate->addMonth();
        }

        // Calculate growth/decline metrics
        $trendsArray = array_values($monthlyTrends);
        $growth_amount = 0;
        $growth_count = 0;
        $growth_percent = 0;
        $count_growth_percent = 0;

        if (count($trendsArray) >= 2) {
            $latest = $trendsArray[count($trendsArray) - 1];
            $previous = $trendsArray[count($trendsArray) - 2];

            $growth_amount = $latest['amount'] - $previous['amount'];
            $growth_count = $latest['count'] - $previous['count'];

            if ($previous['amount'] > 0) {
                $growth_percent = (($latest['amount'] - $previous['amount']) / $previous['amount']) * 100;
            }

            if ($previous['count'] > 0) {
                $count_growth_percent = (($latest['count'] - $previous['count']) / $previous['count']) * 100;
            }
        }

        // Department trends - apply date range filter
        $filteredApproved = $allApproved->filter(function ($req) use ($startDate, $endDate) {
            $hrApproval = $req->workflow?->histories?->first();
            if (!$hrApproval) return false;
            $approvedAt = $hrApproval->updated_at ?? $hrApproval->created_at;
            if (!$approvedAt) return false;
            $approved = Carbon::parse($approvedAt);
            return $approved->gte($startDate) && $approved->lte($endDate);
        });

        $deptTrends = $filteredApproved->groupBy(fn($r) => $r->user?->department?->dept_name ?? 'N/A')
            ->map(function ($rows) {
                return [
                    'total_requests' => $rows->count(),
                    'total_employees' => $rows->pluck('user_id')->unique()->count(),
                    'total_hours' => round((float)$rows->sum('total_hours'), 2),
                    'total_amount' => (float)$rows->sum('total_amount_payable'),
                    'avg_per_request' => $rows->count() > 0 ? round((float)$rows->sum('total_amount_payable') / $rows->count(), 2) : 0,
                ];
            })
            ->sortByDesc('total_amount')
            ->take(10); // Top 10 departments

        // Top departments for chart (filtered by date range)
        $topDeptsFiltered = $filteredApproved->groupBy(fn($r) => $r->user?->department?->dept_name ?? 'N/A')
            ->map(function ($rows) {
                return (float)$rows->sum('total_amount_payable');
            })
            ->sortByDesc(function ($amount) {
                return $amount;
            })
            ->take(8)
            ->keys()
            ->values()
            ->all();

        $analytics = [
            'monthly_trends' => $monthlyTrends,
            'department_trends' => $deptTrends,
            'top_departments' => $topDeptsFiltered,
            'growth_amount' => $growth_amount,
            'growth_count' => $growth_count,
            'growth_percent' => $growth_percent,
            'count_growth_percent' => $count_growth_percent,
        ];

        // Filter years for the table
        $filterYears = $actionedRequests
            ->filter(fn($r) => !is_null($r->created_at))
            ->map(fn($r) => \Carbon\Carbon::parse($r->created_at)->year)
            ->unique()->sortDesc()->values();

        // Get payment report year/month options for filters
        $paymentYearOptions = OnCallRequest::whereHas('workflow.histories', function ($q) {
            $q->where('step_name', 'HR Approval')
                ->where('status', 1);
        })
            ->whereHas('workflow', fn($q) => $q->where('work_flow_completed', 1))
            ->get()
            ->map(function ($req) {
                $hrHist = $req->workflow?->histories?->where('step_name', 'HR Approval')?->where('status', 1)?->first();
                return $hrHist ? Carbon::parse($hrHist->updated_at ?? $hrHist->created_at)->year : null;
            })
            ->filter()
            ->unique()
            ->sortDesc()
            ->values();

        return view('oncall_requests.report', compact(
            'actionedRequests',
            'deptSummary',
            'grandTotals',
            'summaryYearOptions',
            'summaryMonthOptions',
            'summaryYear',
            'summaryMonth',
            'paymentReport',
            'paymentReportSummary',
            'analytics',
            'currentMonth',
            'currentYear',
            'filterYears',
            'paymentYearOptions',
            'paymentYear',
            'paymentMonth',
            'allDepartments',
            'analyticsDept'
        ));
    }

    // Keep old method name for backward compatibility
    public function actioned(Request $request)
    {
        return $this->report($request);
    }

    public function showOnCallRequest(int $id)
    {
        $req = OnCallRequest::with([
            'user.department',
            'workflow.histories' => fn($q) => $q->with(['attendedBy', 'forwardedBy'])->orderBy('id'),
        ])->findOrFail($id);

        $wf        = $req->workflow;
        $histories = $wf ? $wf->histories : collect();
        $last      = $histories->sortByDesc(fn($h) => $h->updated_at ?? $h->created_at)->first();

        $pendingLabel = fn(?string $step) => match ($step) {
            'Line Manager Approval' => 'Pending Line Manager Approval',
            'HEC Approval'          => 'Pending HEC Approval',
            'HR Approval'           => 'Pending HR Approval',
            default                 => 'Pending',
        };

        $statusLabel = 'Pending';
        $statusClass = 'warning';
        $tooltip     = null;

        if ($wf && (int) $wf->work_flow_completed === 1) {
            // Check work_flow_status to distinguish approved (1) vs rejected (2)
            if ((int) $wf->work_flow_status === 2) {
                // Rejected
                $statusLabel = 'Rejected';
                $statusClass = 'danger';
                if ($last && (string) $last->status === '2') {
                    $tooltip = $last->rejection_reason ?: null;
                }
            } else {
                // Approved
                $statusLabel = ($last && $last->step_name === 'HR Approval' && (string) $last->status === '1')
                    ? 'HR Approved' : 'Approved';
                $statusClass = 'success';
            }
        } elseif ($last) {
            if ((string) $last->status === '2') {
                $statusLabel = 'Rejected';
                $statusClass = 'danger';
                $tooltip     = $last->rejection_reason ?: null;
            } else {
                $nextPending = $histories->firstWhere('status', 0);
                $statusLabel = $nextPending ? $pendingLabel($nextPending->step_name) : $pendingLabel($last->step_name);
                $statusClass = 'warning';
            }
        }

        $pendingWith = null;
        $pendingStep = null;
        if ($statusClass === 'warning') {
            $next        = $histories->firstWhere('status', 0);
            $pendingStep = $next?->step_name;
            if ($next?->attendedBy) {
                $nm = trim(collect([$next->attendedBy->fname ?? null, $next->attendedBy->lname ?? null])->filter()->implode(' '));
                $pendingWith = $nm !== '' ? $nm : ($next->attendedBy->email ?? null);
            }
        }

        $steps = $histories->map(function ($h) {
            $attendedName = $h->attendedBy
                ? (trim(collect([$h->attendedBy->fname ?? null, $h->attendedBy->lname ?? null])->filter()->implode(' ')) ?: ($h->attendedBy->email ?? '—'))
                : '—';
            $forwardedName = $h->forwardedBy
                ? (trim(collect([$h->forwardedBy->fname ?? null, $h->forwardedBy->lname ?? null])->filter()->implode(' ')) ?: ($h->forwardedBy->email ?? '—'))
                : '—';

            $statusText = match ((string) $h->status) {
                '0' => 'Pending',
                '1' => 'Approved',
                '2' => 'Rejected',
                default => '—',
            };

            // Only show an "acted at" timestamp once someone acted (Approved/Rejected).
            $actedAt = null;
            if ((string) $h->status !== '0') {
                // Priority: attend_date > updated_at > created_at
                $dt = $h->attend_date ?: $h->updated_at ?: $h->created_at;
                if ($dt) {
                    if (!$dt instanceof \Carbon\Carbon) {
                        $dt = \Carbon\Carbon::parse($dt);
                    }
                    $actedAt = $dt->timezone('Africa/Dar_es_Salaam')->format('d M Y H:i');
                }
            }

            return [
                'step'         => $h->step_name,
                'attended_by'  => $attendedName,
                'forwarded_by' => $forwardedName,
                'status'       => $statusText,
                'remark'       => $h->remark,
                'rejection'    => $h->rejection_reason,
                'acted_at'     => $actedAt ?? '—',
            ];
        })->values();

        return response()->json([
            'user' => [
                'username'   => $req->user->username ?? '—',
                'email'      => $req->user->email ?? '—',
                'department' => optional($req->user->department)->dept_name ?? '—',
                'education_level' => $req->education_level ?? '—',
            ],
            'workflow' => [
                'current_label' => $statusLabel,
                'current_class' => $statusClass, // success|warning|danger
                'tooltip'       => $tooltip,
                'pending_step'  => $pendingStep,
                'pending_with'  => $pendingWith,
                'completed'     => $wf ? (int) $wf->work_flow_completed === 1 : false,
                'steps'         => $steps,
            ],
        ]);
    }

    public function exportApprovedExcel(Request $request)
    {
        $user  = Auth::user();

        // Restrict access to HR role only
        if (!$user->hasRole('hr')) {
            abort(403, 'Unauthorized. Only HR users can export reports.');
        }

        $scope = $this->allowedDepartmentIds($user);

        // Get filter parameters
        $year = $request->query('pay_year') ? (int)$request->query('pay_year') : null;
        $monthParam = $request->query('pay_month');
        $month = $monthParam ? $this->resolveMonth($monthParam) : null;
        $department = $request->query('department');
        $basis = $request->query('basis', 'approved'); // 'approved', 'locum', 'created'

        // Get all HR-approved requests
        $allApproved = OnCallRequest::whereHas('workflow.histories', function ($q) {
            $q->where('step_name', 'HR Approval')
                ->where('status', 1);
        })
            ->whereHas('workflow', fn($q) => $q->where('work_flow_completed', 1))
            ->with([
                'user.department',
                'workflow.histories' => function ($q) {
                    $q->where('step_name', 'HR Approval')
                        ->where('status', 1)
                        ->latest();
                },
            ])
            ->get();

        // Apply visibility scope
        if ($scope === '__SELF__') {
            $allApproved = $allApproved->where('user_id', $user->id);
        } elseif (is_array($scope) && !empty($scope)) {
            $allApproved = $allApproved->filter(function ($req) use ($scope) {
                return in_array($req->user->deptId ?? null, $scope);
            });
        } else {
            $allApproved = collect();
        }

        // Helper to get HR approval date
        $hrApprovedAt = function ($req) {
            $hrHist = optional($req->workflow)->histories
                ?->where('step_name', 'HR Approval')
                ?->where('status', 1)
                ?->sortByDesc('updated_at')
                ?->first();
            return $hrHist
                ? ($hrHist->updated_at ?? $hrHist->created_at ?? $hrHist->attend_date)
                : null;
        };

        // Helper to parse claim month/year
        $parseClaim = function ($monthText, $year, $createdAt) {
            return \App\Exports\OnCallRequestsExport::parseClaimToMonthYear($monthText, $year, $createdAt);
        };

        // Filter based on basis
        $filtered = $allApproved->filter(function ($req) use ($basis, $year, $month, $parseClaim, $hrApprovedAt) {
            $createdMonth = optional($req->created_at)->month;
            $createdYear  = optional($req->created_at)->year;

            [$claimMonth, $claimYear] = $parseClaim($req->locum_month, $req->locum_year, $req->created_at);

            if ($basis === 'approved') {
                $approvedTime = $hrApprovedAt($req);
                if (!$approvedTime) return false;
                $appr = Carbon::parse($approvedTime);
                if ($year  && (int)$appr->year  !== $year)  return false;
                if ($month && (int)$appr->month !== $month) return false;
                return true;
            }

            if ($basis === 'locum') {
                if ($year  && $claimYear  !== $year)  return false;
                if ($month && $claimMonth !== $month) return false;
                return true;
            }

            // 'created'
            if ($year  && (int)$createdYear  !== $year)  return false;
            if ($month && (int)$createdMonth !== $month) return false;
            return true;
        })->values();

        // Apply department filter
        if ($department && $department !== '_all') {
            $filtered = $filtered->filter(function ($req) use ($department) {
                return ($req->user?->department?->dept_name ?? '') === $department;
            });
        }

        // Generate filename
        $filename = 'oncall_requests_hr_approved';
        $parts = [$basis];
        if ($department && $department !== '_all') $parts[] = \Illuminate\Support\Str::slug($department, '_');
        if ($year)  $parts[] = $year;
        if ($month) $parts[] = str_pad($month, 2, '0', STR_PAD_LEFT);
        if ($parts) $filename .= '_' . implode('_', $parts);
        $filename .= '.xlsx';

        return Excel::download(
            new \App\Exports\OnCallRequestsExport(
                $filtered,
                $department === '_all' ? null : $department,
                $year,
                $month
            ),
            $filename
        );
    }

    /** Accepts "10", "Oct", "October" -> 1..12 */
    private function resolveMonth($value): ?int
    {
        if (!$value) return null;
        $value = trim((string)$value);
        if (empty($value)) return null;

        if (ctype_digit($value)) {
            $n = (int) $value;
            return ($n >= 1 && $n <= 12) ? $n : null;
        }
        try {
            return Carbon::createFromFormat('F', $value)->month; // October
        } catch (\Exception $e) {
            try {
                return Carbon::createFromFormat('M', $value)->month; // Oct
            } catch (\Exception $e2) {
                return null;
            }
        }
    }

    /** 1..12 -> "January".."December" */
    private function numToFullMonth(int $n): string
    {
        return Carbon::createFromDate(2000, $n, 1)->format('F');
    }

    private function isIncharge(User $user): bool
    {
        // accept both spellings
        return $user->hasAnyRole(['incharge', 'in-charge']);
    }

    public function createForStaff()
    {
        $actor = Auth::user();
        abort_if(!$actor, 302, 'Login required.');

        if (!$this->isIncharge($actor)) {
            return redirect()
                ->route('oncall_requests.create') // your normal self-create page
                ->with('error', 'Only In-Charge users can claim on-call for staff.');
        }

        // Eligible staff = same department
        $users = User::query()
            ->where('deptId', $actor->deptId)
            ->orderBy('fname')
            ->get(['id', 'fname', 'mname', 'lname', 'username', 'ccbrt_code', 'deptId', 'job_title']);

        if ($users->isEmpty()) {
            return redirect()->route('oncall_requests.index')->with('error', 'No staff found in your department.');
        }

        // Months: Jan..current (like your create())
        $today  = Carbon::now();
        $months = [];
        $cursor = Carbon::create($today->year, 1, 1);
        while ($cursor->lte($today)) {
            $months[] = $cursor->format('F Y');
            $cursor->addMonth();
        }
        // default to previous month (same as your self-create)
        $defaultClaimMonth = $today->copy()->subMonthNoOverflow()->format('F Y');
        if (!in_array($defaultClaimMonth, $months, true)) {
            array_unshift($months, $defaultClaimMonth);
        }
        $months = array_values(array_unique(array_reverse($months)));

        $educationLevels = array_keys($this->onCallRates);
        $onCallRates     = $this->onCallRates;

        // Build a view similar to your self-create, but includes a "Staff Member" select.
        return view('oncall_requests.create_for_staff', compact(
            'actor',
            'users',
            'months',
            'defaultClaimMonth',
            'educationLevels',
            'onCallRates'
        ));
    }

    public function claimForStaff(Request $request)
    {
        if (!Auth::check()) {
            return redirect()->route('login')->with('error', 'You must be logged in.');
        }

        $actor = Auth::user();
        if (!$this->isIncharge($actor)) {
            return redirect()
                ->route('oncall_requests.create')
                ->with('error', 'Only In-Charge users can claim on-call for staff.');
        }

        // Validate input
        $validated = $request->validate([
            'user_id'               => ['required', Rule::exists('users', 'id')->where(fn($q) => $q->where('deptId', $actor->deptId))],
            'education_level'       => ['required', Rule::in(array_keys($this->onCallRates))],
            'locum_month'           => ['required', 'string', 'regex:/^(January|February|March|April|May|June|July|August|September|October|November|December) \d{4}$/'],
            'worked_days'           => ['required', 'array', 'min:1'],
            // <-- Not required; only present if the checkbox is ticked
            'worked_days.*.worked'  => ['nullable', 'in:0,1'],
            // Hours must be > 0 only when a day is ticked
            'worked_days.*.hours'   => ['nullable', 'numeric', 'min:0', 'max:24'],
            'description'           => ['nullable', 'string', 'max:15000'],
            'has_special_task'      => ['required', 'in:0,1'],
            'special_task_document' => ['bail', 'required_if:has_special_task,1', 'file', 'mimes:pdf,doc,docx,jpg,jpeg,png', 'max:10240'],
        ], [
            'special_task_document.required_if' => 'Please upload the supportive document for a special task.',
        ]);

        [$monthName, $year] = explode(' ', $validated['locum_month']);

        // Compute totals (only count rows where worked == 1)
        $totalDays  = 0;
        $totalHours = 0.0;

        foreach ($validated['worked_days'] as $dateStr => $data) {
            // Expect keys as Y-m-d from the view
            $date = \DateTime::createFromFormat('Y-m-d', $dateStr);
            if (!$date) {
                continue;
            }

            $worked = (int)($data['worked'] ?? 0);
            $hours  = (float)($data['hours'] ?? 0);

            if ($worked === 1) {
                if ($hours <= 0) {
                    return back()
                        ->withErrors(['worked_days' => "Hours must be greater than 0 for {$dateStr}"])
                        ->withInput();
                }
                $totalDays++;
                $totalHours += $hours;
            }
        }

        if ($totalDays === 0) {
            return back()->withErrors(['worked_days' => 'At least one day must be selected'])->withInput();
        }

        // Prevent duplicate on-call request for that staff + month/year
        $existing = OnCallRequest::where('user_id', $validated['user_id'])
            ->where('locum_month', $monthName)
            ->where('locum_year',  $year)
            ->latest('id')
            ->first();

        if ($existing) {
            if ($existing->status === 'rejected') {
                return redirect()
                    ->route('oncall_requests.edit', $existing->id)
                    ->with('error', "A {$monthName} {$year} on-call request for that staff was rejected. Please update and resubmit that request instead of creating a new one.");
            }

            return back()
                ->withErrors(['locum_month' => "That staff already has an on-call request for {$monthName} {$year} (status: {$existing->status})."])
                ->withInput();
        }

        // Amount by education level
        $rate        = $this->onCallRates[$validated['education_level']];
        $totalAmount = $totalDays * $rate;

        DB::beginTransaction();
        try {
            // Create on-call request for the SELECTED STAFF
            $requestRecord = OnCallRequest::create([
                'user_id'               => $validated['user_id'],
                'education_level'       => $validated['education_level'],
                'locum_month'           => $monthName,
                'locum_year'            => $year,
                'number_of_days'        => $totalDays,
                'total_hours'           => $totalHours,
                'total_amount'          => $totalAmount,
                'total_amount_payable'  => $totalAmount,
                'worked_days'           => $validated['worked_days'], // array -> JSON column
                'description'           => $validated['description'] ?? null,
                'status'                => 'pending',
                'has_special_task'      => (int)$validated['has_special_task'] === 1,
                // IMPORTANT: exact DB column name
                'submited_by_incharge'  => $actor->id,
            ]);

            $staff = User::find($validated['user_id']);

            // Handle special-task file (if any)
            if ((int)$validated['has_special_task'] === 1) {
                $file = $request->file('special_task_document'); // validated above
                $path = $file->store('oncall/special_tasks', 'public');
                $requestRecord->update([
                    'special_task_path'          => $path,
                    'special_task_original_name' => $file->getClientOriginalName(),
                    'special_task_mime'          => $file->getClientMimeType(),
                    'special_task_size'          => $file->getSize(),
                ]);
            }

            // Route to Line Manager for the staff’s department
            $staff       = User::find($validated['user_id']);
            $lineManager = User::whereHas('roles', fn($q) => $q->where('name', 'line-manager'))
                ->where('deptId', $staff?->deptId)
                ->first();

            if (!$lineManager) {
                DB::rollBack();
                return back()->withErrors(['error' => 'No Line Manager found for that staff’s department.'])->withInput();
            }

            $workflow = Workflow::create([
                'on_call_request_id'  => $requestRecord->id,
                'user_id'             => $validated['user_id'], // owner is the staff member
                'work_flow_status'    => 0,
                'work_flow_completed' => 0,
            ]);

            WorkFlowHistory::create([
                'work_flow_id'           => $workflow->id,
                'forwarded_by'           => $actor->id,        // in-charge user
                'attended_by'            => $lineManager->id,  // line manager
                'step_name'              => 'Line Manager Approval',
                'action_taken'           => 'Forwarded',
                'status'                 => 0,
                'remark'                 => 'On-call request submitted by In-Charge for staff.',
                'on_call_request_status' => 0,
            ]);

            DB::commit();

            // Optional emails (mirror your existing behavior)
            try {
                Mail::to($staff?->email)->queue(new OnCallSubmissionNotification(
                    onCallRequest: $requestRecord,
                    workflow: $workflow,
                    submitter: $actor
                ));
            } catch (\Throwable $mailEx) {
                Log::error('OnCall (in-charge): mail to staff failed', ['err' => $mailEx->getMessage()]);
            }

            try {
                Mail::to($lineManager->email)->queue(new OnCallApprovalRequest(
                    onCallRequest: $requestRecord,
                    workflow: $workflow,
                    submitter: $actor,
                    approver: $lineManager
                ));
            } catch (\Throwable $mailEx) {
                Log::error('OnCall (in-charge): mail to LM failed', ['err' => $mailEx->getMessage()]);
            }

            return redirect()->route('oncall_requests.index')->with('success', 'On-call request submitted for approval.');
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('OnCall (in-charge) submit failed', ['err' => $e->getMessage()]);
            return back()->with('error', 'Failed to submit request. Please try again.')->withInput();
        }
    }


    public function inchargeFetchBioTimeDataForOnCall(Request $request)
    {
        $request->validate([
            'ccbrt_code' => 'nullable|string',
            'month_year' => 'required|string', // "September 2025"
        ]);

        $ccbrtCode = $request->input('ccbrt_code');
        $monthYear = $request->input('month_year');

        Log::info('OnCall inchargeFetchBioTimeData', compact('ccbrtCode', 'monthYear'));

        try {
            $date = Carbon::createFromFormat('F Y', trim($monthYear));
            $month = $date->month;
            $year  = $date->year;
            $daysInMonth = $date->daysInMonth;
        } catch (\Throwable $e) {
            return response()->json(['error' => 'Invalid month and year format'], 400);
        }

        // IMPORTANT: On-call store()/update() expect Y-m-d keys
        $workedDays = [];
        for ($d = 1; $d <= $daysInMonth; $d++) {
            $iso = Carbon::createFromDate($year, $month, $d)->format('Y-m-d');
            $workedDays[$iso] = [
                'worked'   => 0,
                'hours'    => '0.00',
                'time_in'  => null,
                'time_out' => null,
            ];
        }

        if (!$ccbrtCode || $ccbrtCode === 'N/A') {
            return response()->json(['worked_days' => $workedDays]);
        }

        try {
            $sql = "
            SELECT emp_code AS userid,
                   DATE(punch_time) AS punch_date,
                   punch_time,
                   CASE WHEN ROW_NUMBER() OVER (PARTITION BY emp_code, DATE(punch_time) ORDER BY punch_time) % 2 = 1
                        THEN 'Punch In' ELSE 'Punch Out' END AS punch_type
            FROM (
                SELECT emp_code, punch_time,
                       ROW_NUMBER() OVER (PARTITION BY emp_code, DATE(punch_time) ORDER BY punch_time) AS rn
                FROM iclock_transaction
                WHERE emp_code = ?
                  AND EXTRACT(MONTH FROM punch_time) = ?
                  AND EXTRACT(YEAR  FROM punch_time) = ?
            ) t
            ORDER BY punch_date, punch_time
        ";

            $punches = DB::connection('bio')->select($sql, [$ccbrtCode, $month, $year]);

            $daily = [];
            foreach ($punches as $p) {
                $iso = Carbon::parse($p->punch_date)->format('Y-m-d');
                $daily[$iso] ??= [];
                $daily[$iso][] = $p;
            }

            foreach ($daily as $iso => $punchList) {
                $total = 0.0;
                $in = null;
                $tin = null;
                $tout = null;
                $has = false;
                foreach ($punchList as $p) {
                    $has = true;
                    $ts = Carbon::parse($p->punch_time)->format('H:i:s');
                    if ($p->punch_type === 'Punch In') {
                        $in = Carbon::parse($p->punch_time);
                        $tin = $ts;
                    } elseif ($p->punch_type === 'Punch Out' && $in) {
                        $tout = $ts;
                        $out = Carbon::parse($p->punch_time);
                        $total += $out->diffInSeconds($in) / 3600;
                        $in = null;
                    }
                }
                if ($has) {
                    $workedDays[$iso] = [
                        'worked'   => 1,
                        'hours'    => number_format($total, 2, '.', ''),
                        'time_in'  => $tin,
                        'time_out' => $tout,
                    ];
                }
            }

            return response()->json(['worked_days' => $workedDays]);
        } catch (\Throwable $e) {
            Log::error('OnCall BioTime fetch failed', ['err' => $e->getMessage()]);
            // Return base grid anyway
            return response()->json(['worked_days' => $workedDays]);
        }
    }
}
