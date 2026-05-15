@extends('layouts.template')

@section('breadcrumb')
    @include('sweetalert::alert')
@endsection

@section('content')
    <div class="page-wrapper">
        <div class="content container-fluid">

            <!-- Dashboard Title -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h3 class="mb-0 fw-normal text-dark">Dashboard</h3>
                <small class="text-muted">Welcome back, {{ ucfirst(auth()->user()->username ?? 'User') }}</small>
            </div>

            <!-- ====== FLASH MESSAGES ====== -->
            @if (session('success') || session('error'))
                <div class="mb-4">
                    @if (session('success'))
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="fas fa-check-circle me-2"></i> {{ session('success') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif
                    @if (session('error'))
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="fas fa-exclamation-circle me-2"></i> {{ session('error') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif
                </div>
            @endif

            <!-- ====== LICENSE STATUS ALERT FOR CLINICAL USERS ====== -->
            @if (isset($isClinicalDepartment) && $isClinicalDepartment && isset($licenseStatus))
                @if (!$licenseStatus['has_license'] || $licenseStatus['is_expired'])
                    <div class="alert alert-danger mb-4" role="alert">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        @if (!$licenseStatus['has_license'])
                            <strong>No Professional License Found</strong> - Please contact HR BP.
                        @else
                            <strong>Professional License Expired</strong> on {{ \Carbon\Carbon::parse($licenseStatus['expiry_date'])->format('d F Y') }}. Please contact HR BP.
                        @endif
                    </div>
                @elseif ($licenseStatus['expiring_soon'])
                    <div class="alert alert-warning mb-4" role="alert">
                        <i class="fas fa-clock me-2"></i>
                        <strong>License Expiring Soon</strong> - {{ \Carbon\Carbon::parse($licenseStatus['expiry_date'])->format('d F Y') }}
                    </div>
                @endif
            @endif

            <!-- ====== CALCULATE COUNTS ====== -->
            @php
                $user = auth()->user();
            $isLineManager = $user->hasRole('line-manager');
            $isHecMember = $user->hasAnyRole(['coo', 'cfo', 'cms', 'ccdro']);
            $isSeniorViewer = $user->hasAnyRole(['ceo', 'super-admin', 'hr']);

            // Announcements count (last month only, not more than 30 days)
            $announcementsCount = \App\Models\Announcement::where('created_at', '>=', now()->subDays(30))->count();

            // User-specific unseen announcements in the last month - for blink alert
            $newAnnouncementsQuery = \App\Models\Announcement::where('created_at', '>=', now()->subDays(30));
            if (\Schema::hasTable('announcement_user_views')) {
                $newAnnouncementsQuery->whereNotExists(function ($query) use ($user) {
                    $query->select(\DB::raw(1))
                        ->from('announcement_user_views')
                        ->whereColumn('announcement_user_views.announcement_id', 'announcements.id')
                        ->where('announcement_user_views.user_id', $user->id);
                });
            } else {
                $newAnnouncementsQuery->where('created_at', '>=', now()->subHours(24));
            }
            $newAnnouncementsCount = $newAnnouncementsQuery->count();

            // Contracts pending current user's action
            $dashContractPending = 0;
            $dashContractUrl = route('procurements.contracts.index');
            $dashContractExpiring = 0;
            $dashContractExpired = 0;
            $isContractResponsible = $user->hasAnyRole(['procurement-officer','line-manager','coo','cfo','cms','ccdro','hr','super-admin']);
            if ($isContractResponsible && \Schema::hasTable('ccbrt_contracts')) {
                $dashContractPending = \App\Models\CcbrtContract::where('current_approver_id', $user->id)
                    ->whereIn('approval_stage', ['line_manager','hec','procurement'])
                    ->whereIn('status', ['in_progress','pending','draft'])
                    ->count();
                if ($dashContractPending > 0) {
                    $dashContractUrl = route('procurements.contracts.index', ['view' => 'my_pending']);
                }

                // Resolve department IDs visible to this user
                $dashDeptIds = null; // null = all departments (PO / super-admin / COO)
                if ($user->hasRole('line-manager')) {
                    $dashDeptIds = $user->deptId ? [$user->deptId] : [-1];
                } elseif ($user->hasAnyRole(['cfo','cms','ccdro'])) {
                    $byMember = DB::table('departments')->where('hec_member_id', $user->id)->pluck('id')->toArray();
                    $roleName  = strtolower($user->getRoleNames()->first() ?? '');
                    $byLevel   = $roleName ? DB::table('departments')
                        ->join('hecs','departments.hec_id','=','hecs.id')
                        ->whereRaw('LOWER(hecs.hec_level_name) = ?', [$roleName])
                        ->pluck('departments.id')->toArray() : [];
                    $dashDeptIds = array_unique(array_merge($byMember, $byLevel)) ?: [-1];
                }
                // COO / PO / super-admin => $dashDeptIds stays null (no dept filter)

                // Expiring within 30 days (matches contracts index logic)
                $expiringQ = \App\Models\CcbrtContract::where('end_date', '>', now())
                    ->where('end_date', '<=', now()->addDays(30))
                    ->whereNotIn('status', ['expired','archived','terminated','renewed'])
                    ->where(function($q) {
                        $q->whereNull('is_archived')->orWhere('is_archived', false);
                    })
                    ->where(function($q) {
                        $q->whereNull('renewal_status')
                          ->orWhere('renewal_status', '!=', 'pending');
                    })
                    ->where(function($q) {
                        $q->whereNull('lifecycle_stage')->orWhere('lifecycle_stage', '!=', 'renewal');
                    })
                    ->where(function($q) {
                        // Exclude contracts actively in workflow (still pending approval)
                        $q->whereNull('approval_stage')
                          ->orWhereNotIn('approval_stage', ['line_manager','hec','procurement'])
                          ->orWhereNotIn('status', ['in_progress','pending','draft']);
                    })
                    ->whereNotExists(function($q) {
                        $q->select(DB::raw(1))
                          ->from('ccbrt_contracts as renewals')
                          ->whereColumn('renewals.parent_contract_id', 'ccbrt_contracts.id');
                    });
                if ($dashDeptIds !== null) {
                    $expiringQ->whereIn('department_id', $dashDeptIds);
                }
                $dashContractExpiring = $expiringQ->count();

                // Expired contracts (all, no time limit — matches index page)
                $expiredQ = \App\Models\CcbrtContract::where('end_date', '<', now())
                    ->whereNotIn('status', ['archived','terminated','renewed'])
                    ->where(function($q) {
                        $q->whereNull('is_archived')->orWhere('is_archived', false);
                    })
                    ->where(function($q) {
                        $q->whereNull('renewal_status')
                          ->orWhere('renewal_status', '!=', 'renewed');
                    })
                    ->whereNotExists(function($q) {
                        $q->select(DB::raw(1))
                          ->from('ccbrt_contracts as renewals')
                          ->whereColumn('renewals.parent_contract_id', 'ccbrt_contracts.id');
                    });
                if ($dashDeptIds !== null) {
                    $expiredQ->whereIn('department_id', $dashDeptIds);
                }
                $dashContractExpired = $expiredQ->count();
            }

            // ===== Personal Locum & On-Call Stats (for ALL users) =====
            $myLocumTotal = DB::table('locum_requests')->where('user_id', $user->id)->count();
            $myLocumPending = DB::table('locum_requests')
                ->join('workflows', 'locum_requests.id', '=', 'workflows.locum_request_id')
                ->where('locum_requests.user_id', $user->id)
                ->where('workflows.work_flow_completed', 0)
                ->count();
            $myLocumApproved = DB::table('locum_requests')
                ->join('workflows', 'locum_requests.id', '=', 'workflows.locum_request_id')
                ->where('locum_requests.user_id', $user->id)
                ->where('workflows.work_flow_completed', 1)
                ->where('workflows.work_flow_status', '1')
                ->count();
            $myLocumRejected = DB::table('locum_requests')
                ->join('workflows', 'locum_requests.id', '=', 'workflows.locum_request_id')
                ->where('locum_requests.user_id', $user->id)
                ->where('workflows.work_flow_completed', 1)
                ->where('workflows.work_flow_status', '2')
                ->count();
            $myLocumAmount = (float) DB::table('locum_requests')
                ->join('workflows', 'locum_requests.id', '=', 'workflows.locum_request_id')
                ->where('locum_requests.user_id', $user->id)
                ->where('workflows.work_flow_completed', 1)
                ->where('workflows.work_flow_status', '1')
                ->sum('locum_requests.total_amount_payable');
            $myLocumThisMonth = DB::table('locum_requests')
                ->where('user_id', $user->id)
                ->whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)
                ->count();

            $myOncallTotal = DB::table('on_call_requests')->where('user_id', $user->id)->count();
            $myOncallPending = DB::table('on_call_requests')
                ->join('workflows', 'on_call_requests.id', '=', 'workflows.on_call_request_id')
                ->where('on_call_requests.user_id', $user->id)
                ->where('workflows.work_flow_completed', 0)
                ->count();
            $myOncallApproved = DB::table('on_call_requests')
                ->join('workflows', 'on_call_requests.id', '=', 'workflows.on_call_request_id')
                ->where('on_call_requests.user_id', $user->id)
                ->where('workflows.work_flow_completed', 1)
                ->where('workflows.work_flow_status', '1')
                ->count();
            $myOncallRejected = DB::table('on_call_requests')
                ->join('workflows', 'on_call_requests.id', '=', 'workflows.on_call_request_id')
                ->where('on_call_requests.user_id', $user->id)
                ->where('workflows.work_flow_completed', 1)
                ->where('workflows.work_flow_status', '2')
                ->count();
            $myOncallAmount = (float) DB::table('on_call_requests')
                ->join('workflows', 'on_call_requests.id', '=', 'workflows.on_call_request_id')
                ->where('on_call_requests.user_id', $user->id)
                ->where('workflows.work_flow_completed', 1)
                ->where('workflows.work_flow_status', '1')
                ->sum('on_call_requests.total_amount_payable');
            $myOncallThisMonth = DB::table('on_call_requests')
                ->where('user_id', $user->id)
                ->whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)
                ->count();

            // For Line Managers, HEC Members, CEO, HR, Super Admin - get department stats
            if ($isLineManager || $isHecMember || $isSeniorViewer) {
                $deptUserIds = [];
                $deptName = '';
                $managedDeptIds = [];

                if ($isLineManager) {
                    // Line Manager: Get their own department
                    $userDeptId = $user->deptId;
                    $deptName = 'Monthly Trends';
                    $managedDeptIds = [$userDeptId];
                    $deptUserIds = DB::table('users')->where('deptId', $userDeptId)->pluck('id')->toArray();
                    // For line managers, managedDepartments is just their own department
                    $managedDepartments = DB::table('departments')
                        ->where('id', $userDeptId)
                        ->select('id', 'dept_name')
                        ->get();
                    $selectedDeptId = null; // Line managers don't need department filter
                } elseif ($isSeniorViewer) {
                    // CEO, Super Admin, HR: See ALL departments
                    $managedDepartments = DB::table('departments')
                        ->select('id', 'dept_name')
                        ->orderBy('dept_name')
                        ->get();
                    $managedDeptIds = $managedDepartments->pluck('id')->toArray();
                    $deptName = 'Monthly Trends';

                    $selectedDeptId = request('trend_department', null);
                    if (!empty($selectedDeptId)) {
                        $selectedDeptId = (int)$selectedDeptId;
                        if (in_array($selectedDeptId, $managedDeptIds)) {
                            $selectedDeptName = DB::table('departments')->where('id', $selectedDeptId)->value('dept_name');
                            $deptName = $selectedDeptName ?? 'Monthly Trends';
                            $managedDeptIds = [$selectedDeptId];
                        } else {
                            $selectedDeptId = null;
                        }
                    } else {
                        $selectedDeptId = null;
                    }

                    $deptUserIds = DB::table('users')
                        ->whereIn('deptId', $managedDeptIds)
                        ->pluck('id')
                        ->toArray();
                } elseif ($isHecMember) {
                    // HEC Member: Get all departments they manage
                    // Departments can be mapped through:
                    // 1. hec_member_id (direct assignment)
                    // 2. hec_id -> hecs table (HEC level like COO, CFO, CMS)

                    // Get departments using the same logic as RequisitionController
                    // 1. Departments directly assigned via hec_member_id
                    // 2. Departments linked via hec_id matching user's role name
                    $deptIdsByMember = DB::table('departments')
                        ->where('hec_member_id', $user->id)
                        ->pluck('id')
                        ->toArray();

                    // Get departments via hec_id matching role name
                    $userRoleName = strtolower($user->getRoleNames()->first() ?? '');
                    $deptIdsByHecLevel = [];
                    if (!empty($userRoleName)) {
                        $deptIdsByHecLevel = DB::table('departments')
                            ->join('hecs', 'departments.hec_id', '=', 'hecs.id')
                            ->whereRaw("LOWER(hecs.hec_level_name) = ?", [$userRoleName])
                            ->pluck('departments.id')
                            ->toArray();
                    }

                    // Combine both sources
                    $managedDeptIds = array_unique(array_merge($deptIdsByMember, $deptIdsByHecLevel));

                    if (!empty($managedDeptIds)) {
                        // Get department details for dropdown
                        $managedDepartments = DB::table('departments')
                            ->whereIn('id', $managedDeptIds)
                            ->select('id', 'dept_name')
                            ->orderBy('dept_name')
                            ->get();

                        $deptName = 'Monthly Trends';

                        // Get selected department from request (for HEC members)
                        $selectedDeptId = request('trend_department', null);
                        if ($isHecMember && !empty($selectedDeptId)) {
                            $selectedDeptId = (int)$selectedDeptId;
                            // Validate that the selected department is in managed departments
                            if (in_array($selectedDeptId, $managedDeptIds)) {
                                $selectedDeptName = DB::table('departments')->where('id', $selectedDeptId)->value('dept_name');
                                $deptName = $selectedDeptName ?? 'Monthly Trends';
                                // Filter to selected department only
                                $managedDeptIds = [$selectedDeptId];
                            } else {
                                $selectedDeptId = null; // Invalid selection, reset to null
                            }
                        } else {
                            $selectedDeptId = null; // No selection or not HEC member
                        }

                        // Get all user IDs from selected/managed departments
                        $deptUserIds = DB::table('users')
                            ->whereIn('deptId', $managedDeptIds)
                            ->pluck('id')
                            ->toArray();
                    } else {
                        // Fallback: If no departments assigned, use their own department
                        $userDeptId = $user->deptId;
                        $deptName = DB::table('departments')->where('id', $userDeptId)->value('dept_name') ?? 'Your Department';
                        $managedDeptIds = $userDeptId ? [$userDeptId] : [];
                        $deptUserIds = $userDeptId ? DB::table('users')->where('deptId', $userDeptId)->pluck('id')->toArray() : [];
                    }
                }

                // Department Locum Stats (HR Approved: work_flow_completed=1 and work_flow_status=1)
                $deptLocumTotal = DB::table('locum_requests')->whereIn('user_id', $deptUserIds)->count();
                $deptLocumPending = DB::table('locum_requests')
                    ->join('workflows', 'locum_requests.id', '=', 'workflows.locum_request_id')
                    ->whereIn('locum_requests.user_id', $deptUserIds)
                    ->where('workflows.work_flow_completed', 0)
                    ->count();
                $deptLocumApproved = DB::table('locum_requests')
                    ->join('workflows', 'locum_requests.id', '=', 'workflows.locum_request_id')
                    ->whereIn('locum_requests.user_id', $deptUserIds)
                    ->where('workflows.work_flow_completed', 1)
                    ->where('workflows.work_flow_status', '1')
                    ->count();
                $deptLocumThisMonth = DB::table('locum_requests')
                    ->whereIn('user_id', $deptUserIds)
                    ->whereMonth('created_at', now()->month)
                    ->whereYear('created_at', now()->year)
                    ->count();
                $deptLocumAmount = DB::table('locum_requests')
                    ->join('workflows', 'locum_requests.id', '=', 'workflows.locum_request_id')
                    ->whereIn('locum_requests.user_id', $deptUserIds)
                    ->where('workflows.work_flow_completed', 1)
                    ->where('workflows.work_flow_status', '1')
                    ->sum('locum_requests.total_amount_payable');

                // Department On-Call Stats (HR Approved: work_flow_completed=1 and work_flow_status=1)
                $deptOncallTotal = DB::table('on_call_requests')->whereIn('user_id', $deptUserIds)->count();
                $deptOncallPending = DB::table('on_call_requests')
                    ->join('workflows', 'on_call_requests.id', '=', 'workflows.on_call_request_id')
                    ->whereIn('on_call_requests.user_id', $deptUserIds)
                    ->where('workflows.work_flow_completed', 0)
                    ->count();
                $deptOncallApproved = DB::table('on_call_requests')
                    ->join('workflows', 'on_call_requests.id', '=', 'workflows.on_call_request_id')
                    ->whereIn('on_call_requests.user_id', $deptUserIds)
                    ->where('workflows.work_flow_completed', 1)
                    ->where('workflows.work_flow_status', '1')
                    ->count();
                $deptOncallThisMonth = DB::table('on_call_requests')
                    ->whereIn('user_id', $deptUserIds)
                    ->whereMonth('created_at', now()->month)
                    ->whereYear('created_at', now()->year)
                        ->count();
                $deptOncallAmount = DB::table('on_call_requests')
                    ->join('workflows', 'on_call_requests.id', '=', 'workflows.on_call_request_id')
                    ->whereIn('on_call_requests.user_id', $deptUserIds)
                    ->where('workflows.work_flow_completed', 1)
                    ->where('workflows.work_flow_status', '1')
                    ->sum('on_call_requests.total_amount_payable');

                // Staff count
                $deptStaffCount = count($deptUserIds);

                // Per-department claims breakdown (for Claims by Department chart)
                $claimsByDepartment = [];
                if (isset($managedDepartments) && count($managedDepartments) > 0) {
                    foreach ($managedDepartments as $dept) {
                        $deptUsers = DB::table('users')->where('deptId', $dept->id)->pluck('id')->toArray();
                        if (empty($deptUsers)) continue;
                        $locumAmt = (float) DB::table('locum_requests')
                            ->join('workflows', 'locum_requests.id', '=', 'workflows.locum_request_id')
                            ->whereIn('locum_requests.user_id', $deptUsers)
                            ->where('workflows.work_flow_completed', 1)
                            ->where('workflows.work_flow_status', '1')
                            ->sum('locum_requests.total_amount_payable');
                        $oncallAmt = (float) DB::table('on_call_requests')
                            ->join('workflows', 'on_call_requests.id', '=', 'workflows.on_call_request_id')
                            ->whereIn('on_call_requests.user_id', $deptUsers)
                            ->where('workflows.work_flow_completed', 1)
                            ->where('workflows.work_flow_status', '1')
                            ->sum('on_call_requests.total_amount_payable');
                        $totalAmt = $locumAmt + $oncallAmt;
                        if ($totalAmt > 0) {
                            $claimsByDepartment[] = [
                                'name' => $dept->dept_name,
                                'locum' => $locumAmt,
                                'oncall' => $oncallAmt,
                                'total' => $totalAmt,
                            ];
                        }
                    }
                    // Sort by total descending
                    usort($claimsByDepartment, fn($a, $b) => $b['total'] <=> $a['total']);
                }

                // If no users found, set empty array to avoid query errors
                if (empty($deptUserIds)) {
                    $deptUserIds = [-1]; // Use -1 to ensure no results (invalid ID)
                }

                // Get filter parameters
                $selectedYear = (int)request('trend_year', now()->year);
                $fromMonth = (int)request('trend_from_month', 1);
                $toMonth = (int)request('trend_to_month', 12);
                $requestType = request('trend_type', 'all'); // 'all', 'locum', 'oncall'

                // Ensure valid month range
                $fromMonth = max(1, min(12, $fromMonth));
                $toMonth = max(1, min(12, $toMonth));
                if ($fromMonth > $toMonth) {
                    $fromMonth = 1;
                    $toMonth = 12;
                }

                // Calculate monthly trends for selected month range - HR Approved only
                $monthlyTrends = [];
                for ($month = $fromMonth; $month <= $toMonth; $month++) {
                    $monthDate = \Carbon\Carbon::create($selectedYear, $month, 1);
                    $monthStart = $monthDate->copy()->startOfMonth();
                    $monthEnd = $monthDate->copy()->endOfMonth();
                    $monthLabel = $monthDate->format('M'); // Just month name since year is in selector

                    // Locum requests for this month (HR Approved)
                    $locumCount = DB::table('locum_requests')
                        ->join('workflows', 'locum_requests.id', '=', 'workflows.locum_request_id')
                        ->whereIn('locum_requests.user_id', $deptUserIds)
                        ->where('workflows.work_flow_completed', 1)
                        ->where('workflows.work_flow_status', '1')
                        ->whereBetween('locum_requests.created_at', [$monthStart, $monthEnd])
                        ->count();

                    $locumAmount = DB::table('locum_requests')
                        ->join('workflows', 'locum_requests.id', '=', 'workflows.locum_request_id')
                        ->whereIn('locum_requests.user_id', $deptUserIds)
                        ->where('workflows.work_flow_completed', 1)
                        ->where('workflows.work_flow_status', '1')
                        ->whereBetween('locum_requests.created_at', [$monthStart, $monthEnd])
                        ->sum('locum_requests.total_amount_payable') ?? 0;

                    // On-Call requests for this month (HR Approved)
                    $oncallCount = DB::table('on_call_requests')
                        ->join('workflows', 'on_call_requests.id', '=', 'workflows.on_call_request_id')
                        ->whereIn('on_call_requests.user_id', $deptUserIds)
                        ->where('workflows.work_flow_completed', 1)
                        ->where('workflows.work_flow_status', '1')
                        ->whereBetween('on_call_requests.created_at', [$monthStart, $monthEnd])
                        ->count();

                    $oncallAmount = DB::table('on_call_requests')
                        ->join('workflows', 'on_call_requests.id', '=', 'workflows.on_call_request_id')
                        ->whereIn('on_call_requests.user_id', $deptUserIds)
                        ->where('workflows.work_flow_completed', 1)
                        ->where('workflows.work_flow_status', '1')
                        ->whereBetween('on_call_requests.created_at', [$monthStart, $monthEnd])
                        ->sum('on_call_requests.total_amount_payable') ?? 0;

                    // Per-department breakdown for this month
                    $monthDeptBreakdown = [];
                    if (isset($managedDepartments)) {
                        foreach ($managedDepartments as $dept) {
                            $du = DB::table('users')->where('deptId', $dept->id)->pluck('id')->toArray();
                            if (empty($du)) continue;
                            $dLocum = (float) DB::table('locum_requests')
                                ->join('workflows', 'locum_requests.id', '=', 'workflows.locum_request_id')
                                ->whereIn('locum_requests.user_id', $du)
                                ->where('workflows.work_flow_completed', 1)->where('workflows.work_flow_status', '1')
                                ->whereBetween('locum_requests.created_at', [$monthStart, $monthEnd])
                                ->sum('locum_requests.total_amount_payable');
                            $dOncall = (float) DB::table('on_call_requests')
                                ->join('workflows', 'on_call_requests.id', '=', 'workflows.on_call_request_id')
                                ->whereIn('on_call_requests.user_id', $du)
                                ->where('workflows.work_flow_completed', 1)->where('workflows.work_flow_status', '1')
                                ->whereBetween('on_call_requests.created_at', [$monthStart, $monthEnd])
                                ->sum('on_call_requests.total_amount_payable');
                            if ($dLocum > 0 || $dOncall > 0) {
                                $monthDeptBreakdown[] = ['name' => $dept->dept_name, 'locum' => $dLocum, 'oncall' => $dOncall, 'total' => $dLocum + $dOncall];
                            }
                        }
                        usort($monthDeptBreakdown, fn($a, $b) => $b['total'] <=> $a['total']);
                    }

                    $monthlyTrends[] = [
                        'month' => $monthLabel,
                        'locum_count' => $locumCount,
                        'locum_amount' => (float)$locumAmount,
                        'oncall_count' => $oncallCount,
                        'oncall_amount' => (float)$oncallAmount,
                        'departments' => $monthDeptBreakdown,
                    ];
                }
            }
            @endphp


                <!-- Announcements Row (shown for all users) -->
            <div class="row mb-4">
                <div class="col-12">
                    <div class="card border-0 shadow-sm {{ $newAnnouncementsCount > 0 ? 'announcement-blink' : '' }}" style="border-left: 4px solid #e74a3b !important;">
                        <div class="card-body py-3">
                            <div class="d-flex justify-content-between align-items-center">
                                <div class="d-flex align-items-center">
                                    <div class="rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 45px; height: 45px; background: rgba(231, 74, 59, 0.1);">
                                        <i class="fas fa-bullhorn fa-lg {{ $newAnnouncementsCount > 0 ? 'bell-ring' : '' }}" style="color: #e74a3b;"></i>
                                    </div>
                                    <div>
                                        <div class="text-uppercase text-muted small fw-bold">
                                            New Announcements
                                            @if ($newAnnouncementsCount > 0)
                                                <span class="badge bg-danger ms-1" style="animation:badge-entrance 0.5s ease-out forwards,attention-pulse 2s ease-in-out 0.5s infinite">{{ $newAnnouncementsCount }} new</span>
                                            @endif
                                        </div>
                                        <div class="fw-bold text-dark">{{ $announcementsCount }} <small class="text-muted fw-normal">in the last month</small></div>
                                    </div>
                                </div>
                                <a href="{{ route('announcements.index') }}" class="btn btn-sm {{ $newAnnouncementsCount > 0 ? 'btn-danger' : 'btn-outline-danger' }}">
                                    View All <i class="fas fa-arrow-right ms-1"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <style>
                @keyframes contract-alert-pulse {
                    0%,100% { box-shadow: 0 0 0 0 rgba(253,126,20,0); }
                    50%      { box-shadow: 0 0 0 6px rgba(253,126,20,0.25); }
                }
                .contract-alert-blink { animation: contract-alert-pulse 2s ease-in-out infinite; }
                @keyframes announcement-glow {
                    0%,100% { box-shadow: 0 0 0 0 rgba(231,74,59,0); }
                    50%      { box-shadow: 0 0 0 6px rgba(231,74,59,0.25); }
                }
                .announcement-blink { animation: announcement-glow 1.5s ease-in-out infinite; }
                @keyframes bell-shake-dash {
                    0%,100% { transform: rotate(0); }
                    10%,30%,50%,70%,90% { transform: rotate(-12deg); }
                    20%,40%,60%,80%     { transform: rotate(12deg); }
                }
                .bell-ring { animation: bell-shake-dash 1s ease-in-out 1s 3; }
            </style>

            <!-- ====== MAIN CONTENT ROW ====== -->
            <div class="row">
                <!-- Left Column: Quick Actions + Activity -->
                <div class="col-lg-8 mb-4">
                    <!-- Quick Actions -->
                    <div class="card border-0 shadow-sm mb-4">
                        <div class="card-header bg-white border-bottom py-3 d-flex align-items-center">
                            <i class="fas fa-bolt me-2" style="color: #1cc88a;"></i>
                            <h6 class="mb-0 fw-bold">Quick Actions</h6>
                        </div>
                        <div class="card-body">
                            <div class="row g-2">
                                @php
                                    $quickActions = [
                                        ['route' => 'form.index', 'icon' => 'server', 'title' => 'IT Access Form', 'color' => '#36b9cc'],
                                        ['route' => 'locum-requests.index', 'icon' => 'money-bill-wave', 'title' => 'Locum Request', 'color' => '#1cc88a'],
                                        ['route' => 'night-shift.index', 'icon' => 'moon', 'title' => 'Night Allowances', 'color' => '#3d4f8a'],
                                        ['route' => 'oncall_requests.index', 'icon' => 'phone-alt', 'title' => 'On-Call Request', 'color' => '#f6c23e'],
                                        ['route' => 'bank-details.index', 'icon' => 'university', 'title' => 'Bank Details', 'color' => '#858796'],
                                        ['route' => 'nhif_registration.index', 'icon' => 'briefcase-medical', 'title' => 'NHIF Registration', 'color' => '#5a5c69'],
                                        ['route' => 'change_request.create', 'icon' => 'exchange-alt', 'title' => 'Change Request', 'color' => '#4e73df'],
                                        ['route' => 'clearance.index', 'icon' => 'check-circle', 'title' => 'Clearance Form', 'color' => '#17a2b8'],
                                        ['route' => 'IDCard.create', 'icon' => 'id-card', 'title' => 'ID Card Request', 'color' => '#6c757d'],
                                        ['route' => 'loan-declarations.index', 'icon' => 'file-invoice', 'title' => 'HESLB Form', 'color' => '#fd7e14'],
                                    ];

                                    if ($user->hasAnyRole(['line-manager', 'coo', 'cms', 'ccdro'])) {
                                        $quickActions[] = ['route' => 'requisitions.index', 'icon' => 'user-plus', 'title' => 'Recruitment', 'color' => '#e74a3b'];
                                    }
                                    $quickActions[] = ['route' => 'pms.dashboard', 'icon' => 'chart-line', 'title' => 'Staff Performance Management', 'color' => '#6f42c1'];
                                @endphp
                                @foreach ($quickActions as $action)
                                    <div class="col-6 col-md-4 col-lg-3">
                                        <a href="{{ route($action['route']) }}" class="d-block text-decoration-none p-3 rounded text-center quick-action-item" style="background: {{ $action['color'] }}10; transition: all 0.2s;">
                                            <i class="fas fa-{{ $action['icon'] }} fa-lg mb-2" style="color: {{ $action['color'] }};"></i>
                                            <div class="small fw-semibold text-dark">{{ $action['title'] }}</div>
                                        </a>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>

                </div>

                <!-- Right Column: Resources -->
                <div class="col-lg-4 mb-4">
                    <!-- Quick Resources Panel -->
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-white border-bottom py-3 d-flex align-items-center">
                            <i class="fas fa-folder-open me-2" style="color: #4e73df;"></i>
                            <h6 class="mb-0 fw-bold">Resources</h6>
                        </div>
                        <div class="list-group list-group-flush">
                            <a href="{{ route('HrDocuments.index') }}" class="list-group-item list-group-item-action d-flex align-items-center py-3">
                                <i class="fas fa-file-alt me-3 text-muted"></i>
                                <div>
                                    <div class="fw-semibold">HR Documents</div>
                                    <small class="text-muted">Forms & Templates</small>
                                </div>
                            </a>
                            {{-- Staff signed policies: all users can view --}}
                            <a href="{{ route('policies.staff-signed') }}" class="list-group-item list-group-item-action d-flex align-items-center py-3">
                                <i class="fas fa-file-signature me-3 text-muted"></i>
                                <div>
                                    <div class="fw-semibold">Staff Signed Policies</div>
                                    <small class="text-muted">Policies you've signed</small>
                                </div>
                            </a>
                            @can('view policies')
                                <a href="{{ route('policies.index', ['type' => 'other_organization']) }}" class="list-group-item list-group-item-action d-flex align-items-center py-3">
                                    <i class="fas fa-landmark me-3 text-muted"></i>
                                    <div>
                                        <div class="fw-semibold">Organization Policies</div>
                                        <small class="text-muted">External & organization rules</small>
                                    </div>
                                </a>
                            @endcan
                            <a href="{{ route('sops.index') }}" class="list-group-item list-group-item-action d-flex align-items-center py-3">
                                <i class="fas fa-clipboard-list me-3 text-muted"></i>
                                <div>
                                    <div class="fw-semibold">SOPs</div>
                                    <small class="text-muted">Standard Procedures</small>
                                </div>
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Monthly Trends & Claims by Department for Line Managers and HEC Members -->
            @if ($isLineManager || $isHecMember || $isSeniorViewer)
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="card border-0 shadow-sm">
                            <div class="card-header bg-white border-bottom py-3">
                                <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                                    <div class="d-flex align-items-center">
                                        <i class="fas fa-chart-bar me-2" style="color: #61ce70;"></i>
                                        <h6 class="mb-0 fw-bold">Locum & On-Call Payments Trend</h6>
                                    </div>
                                    <div class="d-flex align-items-center gap-2 flex-wrap">
                                        @if (($isHecMember || $isSeniorViewer) && isset($managedDepartments) && count($managedDepartments) > 1)
                                            <select id="trendDepartmentSelect" class="form-select form-select-sm" style="width:auto;min-width:140px;">
                                                <option value="">All Depts</option>
                                                @foreach($managedDepartments as $dept)
                                                    <option value="{{ $dept->id }}" {{ (isset($selectedDeptId) && $selectedDeptId == $dept->id) ? 'selected' : '' }}>{{ $dept->dept_name }}</option>
                                                @endforeach
                                            </select>
                                        @endif
                                        <select id="trendYearSelect" class="form-select form-select-sm" style="width:auto;">
                                            @for ($year = now()->year; $year >= now()->year - 5; $year--)
                                                <option value="{{ $year }}" {{ $selectedYear == $year ? 'selected' : '' }}>{{ $year }}</option>
                                            @endfor
                                        </select>
                                        <select id="trendFromMonth" class="form-select form-select-sm" style="width:auto;">
                                            @foreach(['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'] as $index => $month)
                                                <option value="{{ $index + 1 }}" {{ $fromMonth == ($index + 1) ? 'selected' : '' }}>{{ $month }}</option>
                                            @endforeach
                                        </select>
                                        <span class="text-muted small">to</span>
                                        <select id="trendToMonth" class="form-select form-select-sm" style="width:auto;">
                                            @foreach(['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'] as $index => $month)
                                                <option value="{{ $index + 1 }}" {{ $toMonth == ($index + 1) ? 'selected' : '' }}>{{ $month }}</option>
                                            @endforeach
                                        </select>
                                        <select id="trendTypeSelect" class="form-select form-select-sm" style="width:auto;">
                                            <option value="all" {{ $requestType == 'all' ? 'selected' : '' }}>All</option>
                                            <option value="locum" {{ $requestType == 'locum' ? 'selected' : '' }}>Locum</option>
                                            <option value="oncall" {{ $requestType == 'oncall' ? 'selected' : '' }}>On-Call</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div class="card-body pt-3">
                                <div id="departmentTrendChart" style="min-height: 380px;"></div>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

        </div>
    </div>

    <style>
        .quick-action-item:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        .list-group-item:hover {
            background-color: #f8f9fc !important;
        }
        .card {
            border-radius: 8px;
        }
        .card-footer {
            border-radius: 0 0 8px 8px;
        }
    </style>

    @if (($isLineManager || $isHecMember || $isSeniorViewer) && isset($monthlyTrends))
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const monthlyTrends = @json($monthlyTrends);

                // Helper: format TZS
                function fmtTZS(val) {
                    if (val >= 1000000) return 'TZS ' + (val / 1000000).toFixed(1) + 'M';
                    if (val >= 1000) return 'TZS ' + (val / 1000).toFixed(0) + 'k';
                    return 'TZS ' + val.toLocaleString();
                }

                const monthLabels = monthlyTrends.map(t => t.month);
                const locumAmounts = monthlyTrends.map(t => t.locum_amount);
                const oncallAmounts = monthlyTrends.map(t => t.oncall_amount);
                const totalAmounts = monthlyTrends.map(t => t.locum_amount + t.oncall_amount);
                const grandTotal = totalAmounts.reduce((a, b) => a + b, 0);

                const requestType = '{{ $requestType }}';

                // Build series
                let series = [];
                let chartColors = [];
                if (requestType === 'locum') {
                    series = [{ name: 'Locum', data: locumAmounts }];
                    chartColors = ['#61ce70'];
                } else if (requestType === 'oncall') {
                    series = [{ name: 'On-Call', data: oncallAmounts }];
                    chartColors = ['#3aa248'];
                } else {
                    series = [
                        { name: 'Locum', data: locumAmounts },
                        { name: 'On-Call', data: oncallAmounts }
                    ];
                    chartColors = ['#61ce70', '#3aa248'];
                }

                // Show total above chart
                document.querySelector('#departmentTrendChart').insertAdjacentHTML('beforebegin',
                    '<div class="text-center mb-2"><span class="text-muted small">Total Cost:</span> <strong style="color:#1f2937;font-size:1.05rem;">TZS ' + grandTotal.toLocaleString('en-US') + '</strong></div>'
                );

                const trendOpts = {
                    series: series,
                    chart: {
                        height: 380,
                        type: 'bar',
                        stacked: true,
                        toolbar: { show: true, tools: { download: true, selection: false, zoom: false, zoomin: false, zoomout: false, pan: false, reset: false } },
                        fontFamily: 'inherit'
                    },
                    colors: chartColors,
                    plotOptions: {
                        bar: { columnWidth: '55%', borderRadius: 5 }
                    },
                    dataLabels: {
                        enabled: true,
                        formatter: function(val, opts) {
                            // Only show label on the top segment
                            const sIdx = opts.seriesIndex;
                            const dIdx = opts.dataPointIndex;
                            const isTopSegment = (requestType !== 'all') || (sIdx === series.length - 1);
                            if (!isTopSegment) return '';
                            const total = requestType === 'all' ? (locumAmounts[dIdx] + oncallAmounts[dIdx]) : val;
                            if (total >= 1000000) return 'TZS ' + (total / 1000000).toFixed(1) + 'M';
                            if (total >= 1000) return 'TZS ' + (total / 1000).toFixed(0) + 'k';
                            return total > 0 ? 'TZS ' + total : '';
                        },
                        offsetY: -18,
                        style: { fontSize: '11px', fontWeight: 700, colors: ['#1f2937'] }
                    },
                    xaxis: {
                        categories: monthLabels,
                        labels: { style: { fontSize: '12px', colors: '#4b5563', fontWeight: '600' } },
                        axisBorder: { show: false },
                        axisTicks: { show: false }
                    },
                    yaxis: {
                        labels: {
                            style: { colors: '#9ca3af', fontSize: '11px' },
                            formatter: function(val) { return fmtTZS(val); }
                        }
                    },
                    legend: { show: (requestType === 'all'), position: 'top', horizontalAlign: 'left', fontWeight: 600, markers: { radius: 12 } },
                    grid: { borderColor: '#f3f4f6', strokeDashArray: 4, yaxis: { lines: { show: true } }, xaxis: { lines: { show: false } }, padding: { top: 10 } },
                    tooltip: {
                        custom: function({ series, seriesIndex, dataPointIndex, w }) {
                            const t = monthlyTrends[dataPointIndex];
                            const total = t.locum_amount + t.oncall_amount;
                            const depts = t.departments || [];

                            let html = '<div style="padding:12px 16px;min-width:260px;font-family:inherit;">';
                            html += '<div style="font-weight:700;font-size:14px;margin-bottom:4px;color:#1f2937;">' + t.month + ' {{ $selectedYear }}</div>';
                            html += '<div style="display:flex;gap:16px;margin-bottom:8px;padding-bottom:8px;border-bottom:1px solid #e5e7eb;font-size:12px;color:#6b7280;">';
                            html += '<span>Total: <strong style="color:#1f2937;">TZS ' + total.toLocaleString() + '</strong></span>';
                            html += '<span>' + (t.locum_count + t.oncall_count) + ' requests</span>';
                            html += '</div>';
                            if (requestType === 'all' || requestType === 'locum') {
                                html += '<div style="font-size:12px;margin-bottom:2px;"><span style="display:inline-block;width:8px;height:8px;border-radius:50%;background:#61ce70;margin-right:6px;"></span>Locum: <strong>TZS ' + t.locum_amount.toLocaleString() + '</strong> <span style="color:#9ca3af;">(' + t.locum_count + ')</span></div>';
                            }
                            if (requestType === 'all' || requestType === 'oncall') {
                                html += '<div style="font-size:12px;margin-bottom:6px;"><span style="display:inline-block;width:8px;height:8px;border-radius:50%;background:#3aa248;margin-right:6px;"></span>On-Call: <strong>TZS ' + t.oncall_amount.toLocaleString() + '</strong> <span style="color:#9ca3af;">(' + t.oncall_count + ')</span></div>';
                            }
                            if (depts.length > 0) {
                                html += '<div style="font-size:10px;color:#9ca3af;text-transform:uppercase;font-weight:600;margin:6px 0 4px;padding-top:6px;border-top:1px solid #e5e7eb;">Department Breakdown</div>';
                                const deptShades = ['#61ce70','#4db85c','#3aa248','#278c34','#1cc88a','#45d87a','#5ce48a','#73ea9a','#8af0aa','#a1f6ba','#2ed86a','#17a65a'];
                                depts.forEach(function(d, i) {
                                    const pct = total > 0 ? ((d.total / total) * 100).toFixed(0) : 0;
                                    const dc = deptShades[i % deptShades.length];
                                    html += '<div style="display:flex;justify-content:space-between;align-items:center;padding:2px 0;font-size:12px;">';
                                    html += '<div style="display:flex;align-items:center;"><span style="width:8px;height:8px;border-radius:50%;background:' + dc + ';display:inline-block;margin-right:6px;"></span><span style="color:#374151;">' + d.name + '</span></div>';
                                    html += '<span><strong style="color:#1f2937;">TZS ' + d.total.toLocaleString() + '</strong> <span style="color:#9ca3af;font-size:10px;">(' + pct + '%)</span></span>';
                                    html += '</div>';
                                });
                            }
                            html += '</div>';
                            return html;
                        }
                    }
                };

                new ApexCharts(document.querySelector("#departmentTrendChart"), trendOpts).render();

                // Filter change handler
                function updateFilters() {
                    const url = new URL(window.location.href);
                    url.searchParams.set('trend_year', document.getElementById('trendYearSelect').value);
                    url.searchParams.set('trend_from_month', document.getElementById('trendFromMonth').value);
                    url.searchParams.set('trend_to_month', document.getElementById('trendToMonth').value);
                    url.searchParams.set('trend_type', document.getElementById('trendTypeSelect').value);
                    const deptSelect = document.getElementById('trendDepartmentSelect');
                    if (deptSelect) {
                        if (deptSelect.value) { url.searchParams.set('trend_department', deptSelect.value); }
                        else { url.searchParams.delete('trend_department'); }
                    }
                    window.location.href = url.toString();
                }

                document.getElementById('trendYearSelect')?.addEventListener('change', updateFilters);
                document.getElementById('trendFromMonth')?.addEventListener('change', updateFilters);
                document.getElementById('trendToMonth')?.addEventListener('change', updateFilters);
                document.getElementById('trendTypeSelect')?.addEventListener('change', updateFilters);
                document.getElementById('trendDepartmentSelect')?.addEventListener('change', updateFilters);
            });
        </script>
    @endif
@endsection
