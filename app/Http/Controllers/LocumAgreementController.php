<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Workflow;
use App\Models\LocumRequest;
use App\Models\LocumAgreement;
use App\Models\LocumRate;
use App\Models\WorkFlowHistory;
use App\Mail\ApprovalRequestNotification;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;




class LocumAgreementController extends Controller
{
    //
    public function index()
    {
        $user = Auth::user();
        $today = Carbon::today();
        // Most recent HR-approved agreement (valid or expired) for display
        $agreement = LocumAgreement::where('user_id', $user->id)
            ->whereIn('status', [2, 5])
            ->orderByDesc('updated_at')
            ->orderByDesc('created_at')
            ->first();
        if (!$agreement) {
            $agreement = LocumAgreement::where('user_id', $user->id)->orderByDesc('updated_at')->first();
        }
        $locumRates = LocumRate::activeRatesOrdered();

        // Check if user already has a pending agreement (status 0 or 1, not rejected)
        $hasPendingAgreement = LocumAgreement::where('user_id', $user->id)
            ->whereIn('status', [0, 1])
            ->whereNull('rejection_status')
            ->exists();

        // Check if user has an active (approved & not expired) agreement
        $hasActiveNonExpiredAgreement = LocumAgreement::where('user_id', $user->id)
            ->where('status', 2)
            ->where(function ($q) use ($today) {
                $q->whereNull('end_date')
                  ->orWhere('end_date', '>=', $today);
            })
            ->exists();

        // Admin "use expired agreement until" grace period
        $expiredButUseUntil = false;
        $expiredAgreementUseUntilDate = null;
        $agreementExpiredOnDate = null;
        if ($agreement && in_array((int)($agreement->status ?? 0), [2, 5]) && $agreement->end_date) {
            $agreementEnd = Carbon::parse($agreement->end_date)->endOfDay();
            if ($today->gt($agreementEnd)) {
                $useUntilRaw = \App\Http\Controllers\SettingsController::getSetting('locum_expired_agreement_use_until', '');
                if ($useUntilRaw !== '' && $useUntilRaw !== null) {
                    try {
                        $useUntil = Carbon::parse($useUntilRaw)->endOfDay();
                        if ($today->lte($useUntil)) {
                            $expiredButUseUntil = true;
                            $expiredAgreementUseUntilDate = Carbon::parse($useUntilRaw)->format('d M Y');
                            $agreementExpiredOnDate = Carbon::parse($agreement->end_date)->format('d M Y');
                        }
                    } catch (\Exception $e) {
                    }
                }
            }
        }

        // Compute contract period for new agreements: 29 Jan [year] – 29 Jan [year+1]
        $calendarYear = (int) $today->format('Y');
        $jan29ThisYear = Carbon::parse(sprintf('%d-01-29', $calendarYear));
        $contractStartYear = $today->lt($jan29ThisYear) ? $calendarYear - 1 : $calendarYear;
        $contractStartDate = sprintf('%d-01-29', $contractStartYear);
        $contractEndDate = sprintf('%d-01-29', $contractStartYear + 1);

        return view('locum_agreement.index', compact(
            'agreement', 'user', 'locumRates',
            'expiredButUseUntil', 'expiredAgreementUseUntilDate', 'agreementExpiredOnDate',
            'hasPendingAgreement', 'hasActiveNonExpiredAgreement',
            'contractStartDate', 'contractEndDate'
        ));
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

        // Prevent creating multiple agreements: block if user has a pending (status 0 or 1) agreement
        $hasPendingAgreement = LocumAgreement::where('user_id', $user->id)
            ->whereIn('status', [0, 1])
            ->whereNull('rejection_status')
            ->exists();
        if ($hasPendingAgreement) {
            return back()->with('error', 'You already have a locum agreement pending approval. Please wait for it to be processed before submitting a new one.');
        }

        // Prevent creating multiple agreements: block if user has an active (approved & not expired) agreement
        $today = Carbon::today();
        $hasActiveAgreement = LocumAgreement::where('user_id', $user->id)
            ->where('status', 2)
            ->where(function ($q) use ($today) {
                $q->whereNull('end_date')
                  ->orWhere('end_date', '>=', $today);
            })
            ->exists();
        if ($hasActiveAgreement) {
            return back()->with('error', 'You already have an active locum agreement. You cannot create a new one until your current agreement expires.');
        }

        // --- Validate ---
        $validated = $request->validate([
            'education_level' => 'required|string|max:255',
            'locum_rate'      => 'required|numeric|min:0',
            'start_date'      => 'nullable|date',
            'end_date'        => 'nullable|date|after:start_date',
        ]);

        // Contract period: 29 January [year] to 29 January [next year]
        // If today is before 29 Jan, the contract belongs to the previous cycle (last year's 29 Jan – this year's 29 Jan)
        // If today is 29 Jan or later, the contract is for this year's cycle (this year's 29 Jan – next year's 29 Jan)
        $today = \Carbon\Carbon::today();
        $calendarYear = (int) $today->format('Y');
        $jan29ThisYear = \Carbon\Carbon::parse(sprintf('%d-01-29', $calendarYear));
        $contractStartYear = $today->lt($jan29ThisYear) ? $calendarYear - 1 : $calendarYear;
        $validated['start_date'] = $validated['start_date'] ?? sprintf('%d-01-29', $contractStartYear);
        $validated['end_date'] = $validated['end_date'] ?? sprintf('%d-01-29', $contractStartYear + 1);

        // --- Enforce rate must match an active rate from locum rates settings ---
        $rateConfig = LocumRate::where('education_level', $validated['education_level'])
            ->where('is_active', true)
            ->where('rate', (float) $validated['locum_rate'])
            ->first();

        if (!$rateConfig) {
            return back()
                ->withErrors(['locum_rate' => 'Invalid locum rate for the selected education level. Please choose from the active rates in the dropdown.'])
                ->withInput();
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

    public function view(Request $request)
    {
        $user = Auth::user();

        $query = LocumAgreement::where('user_id', $user->id)
            ->with(['user.department', 'workflow.histories'])
            ->orderBy('created_at', 'desc');

        // Filter by applicable year (from start_date) to differentiate e.g. 2025 vs 2026 agreements
        $selectedYear = $request->get('year');
        if ($selectedYear !== null && $selectedYear !== '') {
            $query->whereRaw('YEAR(COALESCE(start_date, created_at)) = ?', [(int) $selectedYear]);
        }

        $agreements = $query->get();

        // Available years for filter (from agreements' start_date or created_at)
        $availableYears = LocumAgreement::where('user_id', $user->id)
            ->selectRaw('YEAR(COALESCE(start_date, created_at)) as year')
            ->distinct()
            ->orderByRaw('YEAR(COALESCE(start_date, created_at)) DESC')
            ->pluck('year');

        // "Valid to use until" date (same as locum-rates / locum-requests): from setting or max agreement end_date
        $today = Carbon::today();
        $validUntilRaw = trim((string) (\App\Http\Controllers\SettingsController::getSetting('locum_expired_agreement_use_until', '') ?? ''));
        $validUntilDate = null;
        $validUntilDateFormatted = null;
        if ($validUntilRaw !== '') {
            try {
                $d = Carbon::parse($validUntilRaw)->endOfDay();
                if ($today->lte($d)) {
                    $validUntilDate = $d;
                    $validUntilDateFormatted = $d->format('d M Y');
                }
            } catch (\Exception $e) {
            }
        }
        if ($validUntilDateFormatted === null) {
            $maxEnd = LocumAgreement::where('user_id', $user->id)->where('has_contract', true)->whereNotNull('end_date')->where('end_date', '>=', $today)->max('end_date');
            if ($maxEnd) {
                try {
                    $validUntilDate = Carbon::parse($maxEnd)->endOfDay();
                    $validUntilDateFormatted = $validUntilDate->format('d M Y');
                } catch (\Exception $e) {
                }
            }
        }

        // Enhance agreements with status information
        $agreements = $agreements->map(function ($agreement) use ($today, $validUntilDate) {
            $isApproved = in_array((int) $agreement->status, [2, 5]);
            $isExpired = (int) $agreement->status === 5;
            $isActive = false;
            $expiredButValidUntil = false;

            if ($agreement->end_date) {
                $endDate = Carbon::parse($agreement->end_date);
                if (!$isExpired) {
                    $isExpired = $endDate->lt($today);
                }
                if ($isApproved && $isExpired && $validUntilDate && $today->lte($validUntilDate)) {
                    $expiredButValidUntil = true;
                    $isActive = true; // treat as "usable" for claiming until validUntilDate
                } else {
                    $isActive = $isApproved && !$isExpired;
                }
            } elseif ($isApproved && !$isExpired) {
                $isActive = true;
            }

            $agreement->is_expired = $isExpired;
            $agreement->is_active = $isActive;
            $agreement->expired_but_valid_until = $expiredButValidUntil;

            return $agreement;
        });

        $latestAgreement = $agreements->first();
        return view('locum_agreement.view', compact('agreements', 'latestAgreement', 'availableYears', 'selectedYear', 'validUntilDateFormatted', 'validUntilDate'));
    }



    public function show($id)
    {
        $agreement = LocumAgreement::with(['user.department', 'workflow' => fn ($q) => $q->with(['histories' => fn ($q) => $q->orderBy('id', 'asc')])])->findOrFail($id);

        $linemanager = null;
        $Hr = null;

        // Find line manager from workflow history
        // When LM approves, their row is updated to locum_agreement_status = 1, so we must find by role (and status when approved)
        if ($agreement->workflow && $agreement->workflow->histories) {
            $linemanagerHistory = $agreement->workflow->histories
                ->where('locum_agreement_status', 0)
                ->first();
            if (!$linemanagerHistory) {
                foreach ($agreement->workflow->histories as $history) {
                    $u = User::find($history->attended_by);
                    if ($u && $u->hasRole('line-manager') && !$u->hasRole('hr')) {
                        $linemanagerHistory = $history;
                        break;
                    }
                }
            }
            if ($linemanagerHistory) {
                $linemanager = User::find($linemanagerHistory->attended_by);
            }

            // Get HR approver: locum_agreement_status 1 (pending HR) OR 2 (HR approved)
            $HrHistory = null;
            $lmHistoryIds = [];
            foreach ($agreement->workflow->histories as $history) {
                $u = User::find($history->attended_by);
                if ($u && $u->hasRole('line-manager') && !$u->hasRole('hr')) {
                    $lmHistoryIds[] = $history->id;
                }
            }
            foreach ($agreement->workflow->histories as $history) {
                $las = $history->locum_agreement_status;
                if (($las == 1 || $las == 2) && !in_array($history->id, $lmHistoryIds)) {
                    $u = User::find($history->attended_by);
                    if ($u && $u->hasRole('hr')) {
                        if (!$u->hasRole('line-manager')) {
                            $HrHistory = $history;
                            break;
                        }
                        if (!$HrHistory) {
                            $HrHistory = $history;
                        }
                    }
                }
            }
            if (!$HrHistory) {
                foreach ($agreement->workflow->histories as $history) {
                    if (!in_array($history->id, $lmHistoryIds)) {
                        $u = User::find($history->attended_by);
                        if ($u && $u->hasRole('hr') && !$u->hasRole('line-manager')) {
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

        // Agreement status 2 = fully approved, 5 = expired (was approved). Use as source of truth so we always show approvers.
        $agreementApproved = in_array((int) $agreement->status, [2, 5]);

        // Line Manager: find approval record (status=1) by role; when LM approves their row gets locum_agreement_status=1
        $lmApproved = false;
        $lmActionAt = null;
        if ($agreement->workflow && $agreement->workflow->histories) {
            $lmHistory = null;
            foreach ($agreement->workflow->histories as $history) {
                $u = User::find($history->attended_by);
                if (!$u || !$u->hasRole('line-manager') || $u->hasRole('hr')) {
                    continue;
                }
                if ((int) $history->status === 1) {
                    $lmHistory = $history;
                    break;
                }
            }
            if (!$lmHistory && $agreementApproved) {
                $lmHistory = $agreement->workflow->histories->first(function ($h) {
                    $u = User::find($h->attended_by);
                    return $u && $u->hasRole('line-manager') && !$u->hasRole('hr');
                });
            }
            if ($lmHistory) {
                $lmApproved = true;
                $dt = $lmHistory->attend_date ?? $lmHistory->decision_date ?? $lmHistory->updated_at ?? $lmHistory->created_at;
                $lmActionAt = $dt ? \Carbon\Carbon::parse($dt)->format('d M Y H:i') : null;
                if (!$linemanager) {
                    $linemanager = User::find($lmHistory->attended_by);
                }
            } elseif ($agreementApproved) {
                $lmApproved = true;
            }
        }

        // HR: find approval record (status=1) by role; when HR approves their row gets locum_agreement_status=2
        $hrApproved = false;
        $hrActionAt = null;
        if ($agreement->workflow && $agreement->workflow->histories) {
            $hrHistory = null;
            foreach ($agreement->workflow->histories as $history) {
                $u = User::find($history->attended_by);
                if (!$u || !$u->hasRole('hr')) {
                    continue;
                }
                if ((int) $history->status === 1) {
                    $hrHistory = $history;
                    break;
                }
            }
            if (!$hrHistory && $agreementApproved) {
                $hrHistory = $agreement->workflow->histories->first(function ($h) {
                    $u = User::find($h->attended_by);
                    return $u && $u->hasRole('hr');
                });
            }
            if ($hrHistory) {
                $hrApproved = true;
                $dt = $hrHistory->attend_date ?? $hrHistory->decision_date ?? $hrHistory->updated_at ?? $hrHistory->created_at;
                $hrActionAt = $dt ? \Carbon\Carbon::parse($dt)->format('d M Y H:i') : null;
                if (!$Hr) {
                    $Hr = User::find($hrHistory->attended_by);
                }
            } elseif ($agreementApproved) {
                $hrApproved = true;
            }
        }

        // Employee submission time (when agreement was submitted – real time, not today)
        $employeeActionAt = $agreement->created_at
            ? \Carbon\Carbon::parse($agreement->created_at)->format('d M Y H:i')
            : null;

        // Differentiate view: applicable year from start_date or created_at
        $applicableYear = $agreement->start_date
            ? (int) \Carbon\Carbon::parse($agreement->start_date)->format('Y')
            : (int) \Carbon\Carbon::parse($agreement->created_at)->format('Y');
        $currentYear = (int) now()->format('Y');
        $isCurrentYearAgreement = $applicableYear === $currentYear;
        $agreementYearLabel = $isCurrentYearAgreement ? 'Current year' : 'Previous year';

        // Agreements from 2026 onwards use "new year" format with year-specific explanations
        $newYearCutoff = 2026;
        $isNewYearFormat = $applicableYear >= $newYearCutoff;

        if (!$agreement) {
            return redirect()->back()->withErrors(['agreement' => 'Locum Agreement not found.']);
        }
        if ($agreement->user_id == auth()->id()) {
            return view('locum_agreement.show', compact('agreement', 'linemanager', 'Hr', 'lmApproved', 'hrApproved', 'lmActionAt', 'hrActionAt', 'employeeActionAt', 'applicableYear', 'isCurrentYearAgreement', 'agreementYearLabel', 'newYearCutoff', 'isNewYearFormat'));
        } else {
            return view('locum_agreement.approve_locum_agreement', compact('agreement', 'linemanager', 'Hr', 'lmApproved', 'hrApproved', 'lmActionAt', 'hrActionAt', 'employeeActionAt', 'applicableYear', 'isCurrentYearAgreement', 'agreementYearLabel', 'newYearCutoff', 'isNewYearFormat'));
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

        // === Agreements list ===
        // If user is HR or super-admin, show ALL agreements (even if approved by other HR)
        // Otherwise, show only agreements assigned to them
        $isHrOrSuperAdmin = $user->hasRole('hr') || $user->hasRole('super-admin');

        if ($isHrOrSuperAdmin) {
            // Show ALL agreements for HR/super-admin (all statuses, all users)
            $agreementsQuery = \DB::table('locum_agreements')
                ->join('users', 'locum_agreements.user_id', '=', 'users.id');

            // Build select columns
            $selectColumns = [
                'locum_agreements.*',
                \DB::raw('NULL as attended_by'),
                \DB::raw('NULL as remark'),
                'users.fname',
                'users.lname',
                'users.ccbrt_code'
            ];

            // Join departments if we can; otherwise emit a fallback alias
            if ($hasDepartmentsTable && $deptNameColumn && $userDeptFk) {
                $agreementsQuery->leftJoin('departments', "users.$userDeptFk", '=', 'departments.id');
                $deptCol = $deptNameColumn;
                $selectColumns[] = \DB::raw("COALESCE(departments.`{$deptCol}`, '-') AS department_name");
            } else {
                $selectColumns[] = \DB::raw("'-' AS department_name");
            }

            $agreements = $agreementsQuery
                ->select($selectColumns)
                ->orderByDesc('locum_agreements.created_at')
                ->get();

            Log::info('Locum Agreement Approver Queue Results (HR/Super-Admin - All Agreements)', [
                'user_id' => $user->id,
                'agreements_count' => $agreements->count(),
                'agreement_ids' => $agreements->pluck('id')->toArray(),
            ]);
        } else {
            // For non-HR users (line managers, HEC members), show:
            // 1. Pending agreements assigned to them (status = 0) - CURRENT pending step
            // 2. Approved agreements they approved (status = 1) - if no pending exists

            // First, get workflows with CURRENT pending steps (status = 0) for this user
            // A step is "current" if it's the latest pending step for that workflow
            $pendingHistoryIds = \DB::table('work_flow_histories as wfh1')
                ->select('wfh1.id')
                ->where('wfh1.attended_by', $user->id)
                ->where('wfh1.status', 0)
                ->whereNotNull('wfh1.locum_agreement_status')
                ->whereRaw('wfh1.id = (
                    SELECT MAX(wfh2.id)
                    FROM work_flow_histories as wfh2
                    WHERE wfh2.work_flow_id = wfh1.work_flow_id
                    AND wfh2.attended_by = ?
                    AND wfh2.status = 0
                    AND wfh2.locum_agreement_status IS NOT NULL
                )', [$user->id])
                ->pluck('id');

            // Get workflow IDs that have pending steps
            $pendingWorkflowIds = \DB::table('work_flow_histories')
                ->whereIn('id', $pendingHistoryIds->toArray())
                ->pluck('work_flow_id');

            // Get approved history IDs (where user approved, but no pending exists for that workflow)
            $approvedHistoryIds = \DB::table('work_flow_histories')
                ->select(\DB::raw('MAX(id) as id'))
                ->where('attended_by', $user->id)
                ->where('status', 1)
                ->whereNotNull('locum_agreement_status')
                ->whereNotIn('work_flow_id', $pendingWorkflowIds->toArray())
                ->groupBy('work_flow_id')
                ->pluck('id');

            // Combine both pending and approved history IDs
            $latestPendingHistoryIds = $pendingHistoryIds->merge($approvedHistoryIds);

            // Log for debugging on live server
            Log::info('Locum Agreement Approver Queue', [
                'user_id' => $user->id,
                'user_roles' => $user->getRoleNames()->toArray(),
                'pending_history_ids_count' => $pendingHistoryIds->count(),
                'approved_history_ids_count' => $approvedHistoryIds->count(),
                'total_history_ids_count' => $latestPendingHistoryIds->count(),
                'history_ids' => $latestPendingHistoryIds->toArray(),
            ]);

            // If no histories found, return empty collection
            if ($latestPendingHistoryIds->isEmpty()) {
                Log::info('No locum agreement histories found for user', ['user_id' => $user->id]);
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
                    'work_flow_histories.status as history_status',
                    'users.fname',
                    'users.lname',
                    'users.ccbrt_code',
                ];

                // Join departments if we can; otherwise emit a fallback alias
                if ($hasDepartmentsTable && $deptNameColumn && $userDeptFk) {
                    $agreementsQuery->leftJoin('departments', "users.$userDeptFk", '=', 'departments.id');
                    $deptCol = $deptNameColumn;
                    $selects[] = \DB::raw("COALESCE(departments.`{$deptCol}`, '-') AS department_name");
                } else {
                    $selects[] = \DB::raw("'-' AS department_name");
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
        }

        // ---- SPLIT FOR TABS/COUNTS (this fixes Undefined variable $pending) ----
        $pending  = $agreements->filter(function ($a) {
            return in_array((int)($a->status ?? 0), [0, 1]);
        })->values();

        $approved = $agreements->filter(function ($a) {
            return in_array((int)($a->status ?? 0), [2, 5]);
        })->values();

        $rejected = $agreements->filter(function ($a) {
            return in_array((int)($a->status ?? -1), [3, 4]);
        })->values();

        // === HR Summary (all agreements) ===
        $hrStatusCounts = \DB::table('locum_agreements')
            ->selectRaw("
            SUM(CASE WHEN status = 0 THEN 1 ELSE 0 END) AS pending_line_manager,
            SUM(CASE WHEN status = 1 THEN 1 ELSE 0 END) AS pending_hr,
            SUM(CASE WHEN status IN (2,5) THEN 1 ELSE 0 END) AS approved,
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
                SUM(CASE WHEN la.status IN (2,5) THEN 1 ELSE 0 END) AS approved,
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
            5 => ['label' => 'Expired',                 'class' => 'bg-secondary'],
        ];

        // Mark the most recent active contract per user
        $activeAgreementIds = collect();
        $grouped = $agreements->groupBy('user_id');
        foreach ($grouped as $userId => $userAgreements) {
            // Priority: approved (2) with end_date >= today, then most recent by status
            $active = $userAgreements->filter(fn($a) => (int)$a->status === 2 && $a->end_date && \Carbon\Carbon::parse($a->end_date)->gte(now()->startOfDay()))->sortByDesc('created_at')->first();
            if (!$active) {
                // Fallback: most recent pending (0 or 1)
                $active = $userAgreements->filter(fn($a) => in_array((int)$a->status, [0, 1]))->sortByDesc('created_at')->first();
            }
            if ($active) {
                $activeAgreementIds->push($active->id);
            }
        }

        $locumRates = LocumRate::activeRatesOrdered();

        return view('locum_agreement.all_locum_agreement', compact(
            'agreements',
            'pending',
            'approved',
            'rejected',
            'requests',
            'locumRates',
            'hrStatusCounts',
            'departmentSummary',
            'statusMap',
            'activeAgreementIds'
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

        if (!$workflowHistory && Auth::user()->hasRole('hr')) {
            $workflowHistory = $this->pendingHrAgreementHistoryAssignedToRequester($workflow, $locumAgreement, Auth::user());
        }

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
                $workflowHistory->attend_date = now();        // real time when LM approved

                $hrApproverQuery = User::whereHas('roles', fn($q) => $q->where('name', 'hr'))
                    ->where('approvelocum', 1)
                    ->orderBy('id');

                $requesterIsHr = User::whereKey($locumAgreement->user_id)
                    ->whereHas('roles', fn($q) => $q->where('name', 'hr'))
                    ->exists();

                $hrApproverCache = $requesterIsHr
                    ? (clone $hrApproverQuery)->where('id', '!=', $locumAgreement->user_id)->first()
                    : null;

                $hrApproverCache = $hrApproverCache ?: $hrApproverQuery->first();

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
                $workflowHistory->attended_by = $user->id;
                $workflowHistory->locum_agreement_status = 2; // final stage code
                $workflowHistory->status = 1;                 // close my HR step
                $workflowHistory->attend_date = now();        // real time when HR approved

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

    private function pendingHrAgreementHistoryAssignedToRequester(Workflow $workflow, LocumAgreement $locumAgreement, User $user): ?WorkFlowHistory
    {
        if ((int) $locumAgreement->status !== 1 || (int) $locumAgreement->user_id === (int) $user->id) {
            return null;
        }

        $requesterIsHr = User::whereKey($locumAgreement->user_id)
            ->whereHas('roles', fn($q) => $q->where('name', 'hr'))
            ->exists();

        if (!$requesterIsHr) {
            return null;
        }

        return $workflow->histories()
            ->where('attended_by', $locumAgreement->user_id)
            ->where('status', 0)
            ->where('locum_agreement_status', 1)
            ->first();
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

        // Check if agreement is expired (based on creation date + 1 year or stored end_date)
        $agreementCreatedDate = Carbon::parse($agreement->created_at);
        $validEndDate = $agreementCreatedDate->copy()->addYear();
        $today = Carbon::today();
        $isExpired = false;

        if ($agreement->end_date) {
            $endDate = Carbon::parse($agreement->end_date);
            $isExpired = $endDate->lt($today);
        } else {
            $isExpired = $validEndDate->lt($today);
        }

        // Prevent editing of expired agreements
        if ($isExpired) {
            return redirect()->route('locum-agreements.view')
                ->with('error', 'This agreement has expired and cannot be edited. Please create a new agreement.');
        }

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

        $locumRates = LocumRate::activeRatesOrdered();
        return view('locum_agreement.edit', compact('agreement', 'locumRates'));
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
            'education_level' => 'required|string|max:255',
            'locum_rate'      => 'required|numeric|min:0',
            'start_date'      => 'nullable|date',
            'end_date'        => 'nullable|date|after:start_date',
        ]);

        // Contract period: 29 January [year] to 29 January [next year]
        // From 1 Jan 2026 onward, use 2026 as agreement year (29 Jan 2026 – 29 Jan 2027)
        $today = \Carbon\Carbon::today();
        $calendarYear = (int) $today->format('Y');
        $year = $today->gte(\Carbon\Carbon::parse('2026-01-01')) ? 2026 : $calendarYear;
        $validated['start_date'] = $validated['start_date'] ?? sprintf('%d-01-29', $year);
        $validated['end_date'] = $validated['end_date'] ?? sprintf('%d-01-29', $year + 1);

        // Enforce rate must match an active rate from locum rates settings
        $rateConfig = LocumRate::where('education_level', $validated['education_level'])
            ->where('is_active', true)
            ->where('rate', (float) $validated['locum_rate'])
            ->first();

        if (!$rateConfig) {
            return back()
                ->withErrors(['locum_rate' => 'Invalid locum rate for the selected education level. Please choose from the active rates in the dropdown.'])
                ->withInput();
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

    /**
     * Update locum rate for an agreement (HR/Super-Admin only)
     */
    public function updateRate(Request $request, $id)
    {
        $user = Auth::user();

        // Only HR and super-admin can update rates
        if (!$user->hasRole('hr') && !$user->hasRole('super-admin')) {
            return back()->withErrors(['error' => 'You do not have permission to update locum rates.'])->withInput();
        }

        $agreement = LocumAgreement::with('user')->findOrFail($id);

        $validated = $request->validate([
            'education_level' => 'required|string|max:255',
            'locum_rate' => 'required|numeric|min:0',
        ]);

        try {
            DB::beginTransaction();

            // Store old values before updating
            $oldEducationLevel = $agreement->education_level;
            $oldLocumRate = (float) $agreement->locum_rate;
            $newEducationLevel = $validated['education_level'];
            $newLocumRate = (float) $validated['locum_rate'];

            // Check if anything actually changed
            $educationChanged = $oldEducationLevel !== $newEducationLevel;
            $rateChanged = abs($oldLocumRate - $newLocumRate) > 0.01;

            if (!$educationChanged && !$rateChanged) {
                DB::rollBack();
                return back()->with('info', 'No changes were made to the locum rate.');
            }

            // Update the agreement
            $agreement->update([
                'education_level' => $newEducationLevel,
                'locum_rate' => $newLocumRate,
            ]);

            // Get the staff member whose rate was changed
            $staffMember = $agreement->user;
            if (!$staffMember) {
                throw new \Exception('Staff member not found for this agreement.');
            }

            // Find line manager for the staff member's department
            $lineManager = null;
            if ($staffMember->deptId) {
                $lineManager = User::whereHas('roles', function ($query) {
                    $query->where('name', 'line-manager');
                })
                    ->where('deptId', $staffMember->deptId)
                    ->where('id', '!=', $staffMember->id) // Don't send to the staff member if they are their own line manager
                    ->first();
            }

            // Send email to the staff member (queued)
            if (!empty($staffMember->email)) {
                try {
                    Mail::to($staffMember->email)->queue(new \App\Mail\LocumRateChangeNotification(
                        $agreement,
                        $staffMember,
                        $oldEducationLevel,
                        $newEducationLevel,
                        $oldLocumRate,
                        $newLocumRate,
                        $user,
                        false // Not line manager
                    ));
                    Log::info('Locum Rate Change Email Sent to Staff Member', [
                        'staff_email' => $staffMember->email,
                        'agreement_id' => $agreement->id,
                    ]);
                } catch (\Exception $e) {
                    Log::error('Failed to send email to staff member', [
                        'staff_email' => $staffMember->email,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            // Send email to line manager if found (queued)
            if ($lineManager && !empty($lineManager->email)) {
                try {
                    Mail::to($lineManager->email)->queue(new \App\Mail\LocumRateChangeNotification(
                        $agreement,
                        $staffMember,
                        $oldEducationLevel,
                        $newEducationLevel,
                        $oldLocumRate,
                        $newLocumRate,
                        $user,
                        true // Is line manager
                    ));
                    Log::info('Locum Rate Change Email Sent to Line Manager', [
                        'line_manager_email' => $lineManager->email,
                        'agreement_id' => $agreement->id,
                    ]);
                } catch (\Exception $e) {
                    Log::error('Failed to send email to line manager', [
                        'line_manager_email' => $lineManager->email,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            // Log the change
            Log::info('Locum Agreement Rate Updated', [
                'agreement_id' => $agreement->id,
                'user_id' => $agreement->user_id,
                'updated_by' => $user->id,
                'old_education_level' => $oldEducationLevel,
                'new_education_level' => $newEducationLevel,
                'old_locum_rate' => $oldLocumRate,
                'new_locum_rate' => $newLocumRate,
                'line_manager_notified' => $lineManager ? true : false,
            ]);

            DB::commit();

            return back()->with('success', 'Locum rate updated successfully. Email notifications have been sent to the staff member' . ($lineManager ? ' and their line manager' : '') . '.');
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Failed to update Locum Rate: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return back()->withErrors(['error' => 'An error occurred while updating the locum rate. Please try again.'])->withInput();
        }
    }
}
