<div class="header">

    <!-- Logo Area -->
    <div class="header-left">
        <a href="#" class="logo">
            <img src="{{ asset('assets/img/logo.png') }}" alt="Logo">
        </a>
        <a href="#" class="logo logo-small">
            <img src="{{ asset('assets/img/logo-small.png') }}" alt="Logo" width="30" height="30">
        </a>
    </div>

    <!-- Sidebar Toggle -->
    <div class="menu-toggle">
        <a href="javascript:void(0);" id="toggle_btn">
            <i class="fas fa-bars" style="background-color: #61ce70; padding: 12px; border-radius: 5px;"></i>
        </a>
    </div>

    <!-- Mobile Menu Toggle -->
    <a class="mobile_btn" id="mobile_btn">
        <i class="fas fa-bars" style="background-color: #61ce70; padding: 12px; border-radius: 5px;"></i>
    </a>

    <!-- Right Side Menu -->
    <ul class="nav user-menu">

        <!-- Zoom Screen Icon -->
        <li class="nav-item zoom-screen me-2">
            <a href="#" class="nav-link header-nav-list">
                <img src="{{ asset('assets/img/icons/header-icon-04.svg') }}" alt="">
            </a>
        </li>



        <!-- BioTime Details Button -->
        @if (Auth::check())
            <li class="nav-item me-2 d-flex align-items-center">
                <a href="{{ route('biotime.details') }}" class="nav-link header-icon-link" title="BioTime Details">
                    <i class="fas fa-clock header-clock-icon"></i>
                </a>
            </li>
        @endif

        <!-- 🔔 Notification Bell with Pending Items -->
        @if (Auth::check())
            @php
                $user = auth()->user();
                $pendingItems = [];
                $totalPending = 0;

                // Get pending form approvals - broken down by form type
                if ($user->can('approve requests')) {
                    if (!$user->hasRole('finance officer')) {
                        // Base query builder for general (non-locum/oncall/nightshift) pending items
                        // Only status = 0 (pending action) on incomplete workflows
                        $baseGeneralQuery = function () {
                            return \Illuminate\Support\Facades\DB::table('work_flow_histories')
                                ->join('workflows', 'work_flow_histories.work_flow_id', '=', 'workflows.id')
                                ->where('work_flow_histories.attended_by', \Illuminate\Support\Facades\Auth::id())
                                ->where('work_flow_histories.status', 0)
                                ->where('workflows.work_flow_completed', '!=', 1)
                                ->whereNull('locum_agreement_status')
                                ->whereNull('locum_request_status')
                                ->whereNull('on_call_request_status')
                                ->whereNull('workflows.night_shift_claim_id');
                        };

                        // HR Forms (hr_form is not null)
                        $pendingHrForms = $baseGeneralQuery()->whereNotNull('workflows.hr_form')->count();
                        if ($pendingHrForms > 0) {
                            $pendingItems[] = [
                                'title' => 'HR Forms',
                                'count' => $pendingHrForms,
                                'route' => route('requestapprove.index'),
                                'icon' => 'file-alt',
                                'color' => 'warning'
                            ];
                            $totalPending += $pendingHrForms;
                        }

                        // ICT Access Forms
                        $pendingIctForms = $baseGeneralQuery()->whereNotNull('workflows.ict_request_resource_id')->count();
                        if ($pendingIctForms > 0) {
                            $pendingItems[] = [
                                'title' => 'ICT Access Forms',
                                'count' => $pendingIctForms,
                                'route' => route('requestapprove.index'),
                                'icon' => 'laptop',
                                'color' => 'info'
                            ];
                            $totalPending += $pendingIctForms;
                        }

                        // Change Requests
                        $pendingChangeReqs = $baseGeneralQuery()->whereNotNull('workflows.change_request_id')->count();
                        if ($pendingChangeReqs > 0) {
                            $pendingItems[] = [
                                'title' => 'Change Requests',
                                'count' => $pendingChangeReqs,
                                'route' => route('requestapprove.index'),
                                'icon' => 'exchange-alt',
                                'color' => 'primary'
                            ];
                            $totalPending += $pendingChangeReqs;
                        }

                        // ID Card Forms
                        $pendingIdForms = $baseGeneralQuery()->where('workflows.id_form', '>', 0)->count();
                        if ($pendingIdForms > 0) {
                            $pendingItems[] = [
                                'title' => 'ID Card Forms',
                                'count' => $pendingIdForms,
                                'route' => route('requestapprove.index'),
                                'icon' => 'id-card',
                                'color' => 'success'
                            ];
                            $totalPending += $pendingIdForms;
                        }

                        // Bank Detail Forms
                        $pendingBankForms = $baseGeneralQuery()->where('workflows.bank_form', '>', 0)->count();
                        if ($pendingBankForms > 0) {
                            $pendingItems[] = [
                                'title' => 'Bank Forms',
                                'count' => $pendingBankForms,
                                'route' => route('requestapprove.index'),
                                'icon' => 'university',
                                'color' => 'primary'
                            ];
                            $totalPending += $pendingBankForms;
                        }

                        // NHIF Forms
                        $pendingNhifForms = $baseGeneralQuery()->where('workflows.nhif_form', '>', 0)->count();
                        if ($pendingNhifForms > 0) {
                            $pendingItems[] = [
                                'title' => 'NHIF Forms',
                                'count' => $pendingNhifForms,
                                'route' => route('requestapprove.index'),
                                'icon' => 'heartbeat',
                                'color' => 'danger'
                            ];
                            $totalPending += $pendingNhifForms;
                        }

                        // HESLB / Loan Declaration Forms
                        $pendingHeslbForms = $baseGeneralQuery()->where('workflows.heslb_form', '>', 0)->count();
                        if ($pendingHeslbForms > 0) {
                            $pendingItems[] = [
                                'title' => 'HESLB Forms',
                                'count' => $pendingHeslbForms,
                                'route' => route('requestapprove.index'),
                                'icon' => 'graduation-cap',
                                'color' => 'warning'
                            ];
                            $totalPending += $pendingHeslbForms;
                        }

                        // Job Description Forms
                        $pendingJobDesc = $baseGeneralQuery()->whereNotNull('workflows.job_description_id')->count();
                        if ($pendingJobDesc > 0) {
                            $pendingItems[] = [
                                'title' => 'Job Descriptions',
                                'count' => $pendingJobDesc,
                                'route' => route('requestapprove.index'),
                                'icon' => 'briefcase',
                                'color' => 'secondary'
                            ];
                            $totalPending += $pendingJobDesc;
                        }
                    }

                    // Clearance forms - SAME QUERY AS SIDEBAR
                    if ($user->can('access clearance form')) {
                        $pendingClearance = \Illuminate\Support\Facades\DB::table('clearance_work_flow_histories')
                            ->join('clearance_work_flows', 'clearance_work_flow_histories.work_flow_id', '=', 'clearance_work_flows.id')
                            ->join('clearance_forms', 'clearance_forms.id', '=', 'clearance_work_flows.requested_resource_id')
                            ->where('clearance_work_flow_histories.attended_by', \Illuminate\Support\Facades\Auth::id())
                            ->where('clearance_work_flow_histories.status', 0)
                            ->where('clearance_forms.status', '!=', 'rejected')
                            ->where(function ($query) {
                                $query->where('clearance_work_flows.work_flow_completed', '!=', 1)
                                    ->orWhereNull('clearance_work_flows.work_flow_completed');
                            })
                            ->whereIn('clearance_work_flow_histories.id', function ($subquery) {
                                $subquery->selectRaw('MAX(clearance_work_flow_histories.id)')
                                    ->from('clearance_work_flow_histories')
                                    ->where('clearance_work_flow_histories.status', 0)
                                    ->where('clearance_work_flow_histories.attended_by', \Illuminate\Support\Facades\Auth::id())
                                    ->groupBy('clearance_work_flow_histories.work_flow_id');
                            })
                            ->count();
                        if ($pendingClearance > 0) {
                            $pendingItems[] = [
                                'title' => 'Clearance Forms',
                                'count' => $pendingClearance,
                                'route' => route('clearance.index'),
                                'icon' => 'file-check',
                                'color' => 'info'
                            ];
                            $totalPending += $pendingClearance;
                        }
                    }
                }

                // Locum requests - SAME QUERY AS SIDEBAR
                if ($user->can('approve locum requests')) {
                    $pendingLocum = \Illuminate\Support\Facades\DB::table('work_flow_histories')
                        ->join('workflows', 'work_flow_histories.work_flow_id', '=', 'workflows.id')
                        ->where('work_flow_histories.attended_by', \Illuminate\Support\Facades\Auth::id())
                        ->where('work_flow_histories.status', 0)
                        ->whereNotNull('workflows.locum_request_id')
                        ->count();
                    if ($pendingLocum > 0) {
                        $pendingItems[] = [
                            'title' => 'Locum Requests',
                            'count' => $pendingLocum,
                            'route' => route('locum-requests.view'),
                            'icon' => 'money-bill-wave',
                            'color' => 'success'
                        ];
                        $totalPending += $pendingLocum;
                    }
                }

                // Locum agreements - pending approval
                if ($user->can('approve locum requests')) {
                    $pendingLocumAgreements = \Illuminate\Support\Facades\DB::table('work_flow_histories')
                        ->join('workflows', 'work_flow_histories.work_flow_id', '=', 'workflows.id')
                        ->where('work_flow_histories.attended_by', \Illuminate\Support\Facades\Auth::id())
                        ->where('work_flow_histories.status', 0)
                        ->whereNotNull('workflows.locum_agreement_id')
                        ->count();
                    if ($pendingLocumAgreements > 0) {
                        $pendingItems[] = [
                            'title' => 'Locum Agreements',
                            'count' => $pendingLocumAgreements,
                            'route' => url('/locum-agreement-show'),
                            'icon' => 'file-signature',
                            'color' => 'primary'
                        ];
                        $totalPending += $pendingLocumAgreements;
                    }
                }

                // On-call requests - SAME QUERY AS SIDEBAR
                if ($user->can('approve oncall requests')) {
                    $pendingOncall = \Illuminate\Support\Facades\DB::table('work_flow_histories')
                        ->join('workflows', 'work_flow_histories.work_flow_id', '=', 'workflows.id')
                        ->where('work_flow_histories.attended_by', \Illuminate\Support\Facades\Auth::id())
                        ->where('work_flow_histories.status', 0)
                        ->whereNotNull('workflows.on_call_request_id')
                        ->count();
                    if ($pendingOncall > 0) {
                        $pendingItems[] = [
                            'title' => 'On Call Claims',
                            'count' => $pendingOncall,
                            'route' => route('oncall_requests.view'),
                            'icon' => 'phone',
                            'color' => 'warning'
                        ];
                        $totalPending += $pendingOncall;
                    }
                }

                // Night shift claims - pending approval
                $pendingNightShift = \Illuminate\Support\Facades\DB::table('work_flow_histories')
                    ->join('workflows', 'work_flow_histories.work_flow_id', '=', 'workflows.id')
                    ->where('work_flow_histories.attended_by', \Illuminate\Support\Facades\Auth::id())
                    ->where('work_flow_histories.status', 0)
                    ->whereNotNull('workflows.night_shift_claim_id')
                    ->count();
                if ($pendingNightShift > 0) {
                    $pendingItems[] = [
                        'title' => 'Night Allowances',
                        'count' => $pendingNightShift,
                        'route' => route('night-shift.approve.index'),
                        'icon' => 'moon',
                        'color' => 'info'
                    ];
                    $totalPending += $pendingNightShift;
                }

                // Procurement contracts pending action
                if ($user->hasAnyRole(['procurement-officer', 'line-manager', 'coo', 'cfo', 'cms', 'ccdro', 'hr', 'super-admin'])
                    && \Schema::hasTable('ccbrt_contracts')) {

                    // Line Manager: contracts awaiting their review
                    $pendingContractsLM = \App\Models\CcbrtContract::where('approval_stage', 'line_manager')
                        ->where('current_approver_id', $user->id)
                        ->whereIn('status', ['in_progress', 'pending', 'draft'])
                        ->count();
                    if ($pendingContractsLM > 0) {
                        $pendingItems[] = [
                            'title' => 'Contracts – My Review',
                            'count' => $pendingContractsLM,
                            'route' => route('procurements.contracts.index'),
                            'icon' => 'file-contract',
                            'color' => 'warning'
                        ];
                        $totalPending += $pendingContractsLM;
                    }

                    // HEC Contracts — show contracts needing action for the current user
                    if (\Schema::hasTable('hec_contracts')) {
                        $hecToday = \Carbon\Carbon::today();
                        $isHecMgr = $user->hasAnyRole(['Admin-Secretary','super-admin'])
                                    || $user->hasPermissionTo('manage_hec_contract');

                        // Expired + not yet renewed, scoped to owner unless manager
                        $hecActionQ = \App\Models\HecContract::where(function($q) use ($hecToday) {
                            $q->whereDate('end_date', '<', $hecToday)  // expired
                              ->orWhere(function($q2) use ($hecToday) {
                                  $q2->whereDate('end_date', '>=', $hecToday)
                                     ->whereDate('end_date', '<=', $hecToday->copy()->addDays(30)); // expiring soon
                              });
                        })
                        ->whereNotIn('status', ['renewed','archived','terminated']);

                        if (!$isHecMgr) {
                            $hecActionQ->where('contract_owner_id', $user->id);
                        }

                        $hecActionCount = $hecActionQ->count();
                        if ($hecActionCount > 0) {
                            // Determine dominant type for color
                            $hecExpiredOnly = (clone $hecActionQ)->whereDate('end_date', '<', $hecToday)->count();
                            $pendingItems[] = [
                                'title' => 'HEC Contracts',
                                'count' => $hecActionCount,
                                'route' => route('hec-contracts.index'),
                                'icon'  => 'file-contract',
                                'color' => $hecExpiredOnly > 0 ? 'danger' : 'warning'
                            ];
                            $totalPending += $hecActionCount;
                        }
                    }

                    // Procurement officer: contracts pending processing
                    $pendingContractsProc = \App\Models\CcbrtContract::where('approval_stage', 'procurement')
                        ->where('current_approver_id', $user->id)
                        ->whereIn('status', ['in_progress', 'pending'])
                        ->count();
                    if ($pendingContractsProc > 0) {
                        $pendingItems[] = [
                            'title' => 'Contracts – Pending Processing',
                            'count' => $pendingContractsProc,
                            'route' => route('procurements.contracts.index', ['view' => 'procurement']),
                            'icon' => 'file-contract',
                            'color' => 'info'
                        ];
                        $totalPending += $pendingContractsProc;
                    }

                    // Expiring soon — role-scoped (matches ContractsController::allowedDepartmentIds + index())
                    if ($user->hasAnyRole(['procurement-officer','super-admin','line-manager', 'coo', 'cfo', 'cms', 'ccdro']) || $user->can('manage_contracts')) {
                        // Resolve dept filter — matches ContractsController logic
                        $hdrDeptIds = null;
                        if ($user->can('manage_contracts') || $user->hasAnyRole(['procurement-officer', 'super-admin', 'coo'])) {
                            $hdrDeptIds = null; // see all departments
                        } elseif ($user->hasRole('line-manager')) {
                            $hdrDeptIds = $user->deptId ? [(int)$user->deptId] : [-1];
                        } elseif ($user->hasAnyRole(['cfo', 'cms', 'ccdro'])) {
                            $hdrRoleToHec = [];
                            if ($user->hasRole('cfo')) $hdrRoleToHec[] = 'CFO';
                            if ($user->hasRole('cms')) $hdrRoleToHec[] = 'CMS';
                            if ($user->hasRole('ccdro')) $hdrRoleToHec[] = 'CCDRO';
                            $hdrDeptIds = \Illuminate\Support\Facades\DB::table('departments')
                                ->join('hecs', 'departments.hec_id', '=', 'hecs.id')
                                ->whereIn(\Illuminate\Support\Facades\DB::raw('UPPER(TRIM(hecs.hec_level_name))'), $hdrRoleToHec)
                                ->pluck('departments.id')->toArray();
                            if (empty($hdrDeptIds)) $hdrDeptIds = [-1];
                        }

                        $expQ = \App\Models\CcbrtContract::where('end_date', '>', now())
                            ->where('end_date', '<=', now()->addDays(30))
                            ->whereNotIn('status', ['expired','archived','terminated','renewed'])
                            ->where(function($q){ $q->whereNull('is_archived')->orWhere('is_archived', false); })
                            ->where(function($q){ $q->whereNull('renewal_status')->orWhere('renewal_status','!=','pending'); })
                            ->where(function($q){ $q->whereNull('lifecycle_stage')->orWhere('lifecycle_stage','!=','renewal'); })
                            ->where(function($q){
                                $q->whereNull('approval_stage')
                                  ->orWhereNotIn('approval_stage', ['line_manager','hec','procurement'])
                                  ->orWhereNotIn('status', ['in_progress','pending','draft']);
                            })
                            ->whereNotExists(function($q){
                                $q->select(\Illuminate\Support\Facades\DB::raw(1))
                                  ->from('ccbrt_contracts as renewals')
                                  ->whereColumn('renewals.parent_contract_id','ccbrt_contracts.id');
                            });
                        if ($hdrDeptIds !== null) $expQ->whereIn('department_id', $hdrDeptIds);
                        $expiringContracts = $expQ->count();
                        if ($expiringContracts > 0) {
                            $pendingItems[] = [
                                'title' => 'Contracts Expiring Soon',
                                'count' => $expiringContracts,
                                'route' => route('procurements.contracts.index', ['view' => 'expiring']),
                                'icon' => 'clock',
                                'color' => 'warning'
                            ];
                            $totalPending += $expiringContracts;
                        }

                        // Expired contracts (all, no time limit — matches index page)
                        $expiredQ = \App\Models\CcbrtContract::where(function($q) {
                                $q->where('end_date', '<', now())
                                  ->orWhere('status', 'expired');
                            })
                            ->whereNotIn('status', ['archived','terminated','renewed'])
                            ->where(function($q){ $q->whereNull('is_archived')->orWhere('is_archived', false); })
                            ->where(function($q){ $q->whereNull('renewal_status')->orWhere('renewal_status','!=','renewed'); })
                            ->whereNotExists(function($q){
                                $q->select(\Illuminate\Support\Facades\DB::raw(1))
                                  ->from('ccbrt_contracts as renewals')
                                  ->whereColumn('renewals.parent_contract_id','ccbrt_contracts.id');
                            });
                        if ($hdrDeptIds !== null) $expiredQ->whereIn('department_id', $hdrDeptIds);
                        $expiredContracts = $expiredQ->count();
                        if ($expiredContracts > 0) {
                            $pendingItems[] = [
                                'title' => 'Contracts Expired',
                                'count' => $expiredContracts,
                                'route' => route('procurements.contracts.index', ['view' => 'expired']),
                                'icon' => 'exclamation-triangle',
                                'color' => 'danger'
                            ];
                            $totalPending += $expiredContracts;
                        }
                    }
                }

                // Recruitment requisitions - SAME QUERY AS SIDEBAR
                if ($user->hasAnyRole(['line-manager', 'payroll_accountant', 'coo', 'cms', 'cfo', 'ccdro', 'ceo', 'hr'])) {
                    if (\Schema::hasTable('requisitions')) {
                        $pendingRequisition = 0;
                        if ($user->hasRole('payroll_accountant')) {
                            $pendingRequisition = \App\Models\Requisition::where('status', 'pending_payroll')->count();
                        } elseif ($user->hasAnyRole(['coo', 'cfo', 'cms', 'ccdro'])) {
                            // Department-scoped: only requisitions from departments this HEC member oversees
                            $userHecRoles = collect($user->getRoleNames())
                                ->map(fn ($r) => strtolower((string) $r))
                                ->filter(fn ($r) => in_array($r, ['coo', 'cfo', 'cms', 'ccdro'], true))
                                ->values()->all();
                            $pendingRequisition = \App\Models\Requisition::where(function ($query) use ($user, $userHecRoles) {
                                $query->where(function ($q) use ($user, $userHecRoles) {
                                    $q->where('status', 'pending_hec')
                                        ->whereHas('department', function ($dq) use ($user, $userHecRoles) {
                                            $dq->where('hec_member_id', $user->id)
                                                ->orWhereHas('hec', function ($hecQuery) use ($userHecRoles) {
                                                    if (empty($userHecRoles)) {
                                                        $hecQuery->whereRaw('1 = 0');
                                                    } else {
                                                        $hecQuery->whereIn(\Illuminate\Support\Facades\DB::raw('LOWER(hec_level_name)'), $userHecRoles);
                                                    }
                                                });
                                        });
                                });
                                // CFO also sees all pending_cfo
                                if ($user->hasRole('cfo')) {
                                    $query->orWhere('status', 'pending_cfo');
                                }
                            })->count();
                        } elseif ($user->hasRole('ceo')) {
                            $pendingRequisition = \App\Models\Requisition::where('status', 'pending_ceo')->count();
                        } elseif ($user->hasRole('hr')) {
                            $pendingRequisition = \App\Models\Requisition::where('status', 'pending_hr')->count();
                        } elseif ($user->hasRole('line-manager')) {
                            // Only count items that need line manager's action (returned for editing)
                            $pendingRequisition = \App\Models\Requisition::where('user_id', $user->id)
                                ->where('status', 'rejected_for_editing')->count();
                        }
                        if ($pendingRequisition > 0) {
                            $pendingItems[] = [
                                'title' => 'Recruitment Requests',
                                'count' => $pendingRequisition,
                                'route' => route('requisitions.pending'),
                                'icon' => 'user-tie',
                                'color' => 'danger'
                            ];
                            $totalPending += $pendingRequisition;
                        }
                    }
                }
            @endphp
            <li class="nav-item dropdown me-2 d-flex align-items-center">
                <a href="#" class="nav-link header-icon-link notification-bell-wrapper" id="notificationDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false" title="Notifications">
                    <span class="notification-icon-container">
                        <i class="fas fa-bell notification-bell-icon"></i>
                        @if($totalPending > 0)
                            <span class="notification-badge">{{ $totalPending > 99 ? '99+' : $totalPending }}</span>
                        @endif
                    </span>
                </a>
                <style>
                    /* Unified header icon styling */
                    .header-icon-link {
                        display: flex;
                        align-items: center;
                        justify-content: center;
                        padding: 0.5rem 0.6rem;
                        height: 100%;
                    }
                    .header-clock-icon {
                        color: #61ce70;
                        font-size: 1.25rem;
                        line-height: 1;
                    }
                    .notification-bell-wrapper {
                        position: relative;
                    }
                    .notification-icon-container {
                        position: relative;
                        display: inline-flex;
                        align-items: center;
                        justify-content: center;
                    }
                    .notification-bell-icon {
                        color: #61ce70;
                        font-size: 1.25rem;
                        line-height: 1;
                    }
                    .notification-badge {
                        position: absolute;
                        top: -8px;
                        right: -10px;
                        background: linear-gradient(135deg, #ff4757, #ff6b81);
                        color: white;
                        font-size: 0.6rem;
                        font-weight: 700;
                        min-width: 18px;
                        height: 18px;
                        line-height: 18px;
                        text-align: center;
                        border-radius: 50%;
                        border: 2px solid white;
                        box-shadow: 0 2px 6px rgba(255, 71, 87, 0.5);
                        z-index: 10;
                        opacity: 0;
                        transform: scale(0);
                        animation: badge-entrance 0.5s ease-out 2s forwards, attention-pulse 2s ease-in-out 2.5s infinite;
                    }
                    /* Initial pop-up entrance after 2 seconds */
                    @keyframes badge-entrance {
                        0% {
                            opacity: 0;
                            transform: scale(0);
                        }
                        50% {
                            transform: scale(1.4);
                        }
                        70% {
                            transform: scale(0.9);
                        }
                        100% {
                            opacity: 1;
                            transform: scale(1);
                        }
                    }
                    /* Attention-grabbing pulse animation */
                    @keyframes attention-pulse {
                        0%, 100% {
                            transform: scale(1);
                            box-shadow: 0 2px 6px rgba(255, 71, 87, 0.5);
                        }
                        10% {
                            transform: scale(1.3);
                            box-shadow: 0 0 15px rgba(255, 71, 87, 0.8);
                        }
                        20% {
                            transform: scale(1);
                            box-shadow: 0 2px 6px rgba(255, 71, 87, 0.5);
                        }
                        30% {
                            transform: scale(1.3);
                            box-shadow: 0 0 15px rgba(255, 71, 87, 0.8);
                        }
                        40% {
                            transform: scale(1);
                            box-shadow: 0 2px 6px rgba(255, 71, 87, 0.5);
                        }
                    }
                    /* Bell shake when badge appears */
                    .notification-bell-icon {
                        animation: bell-shake 0.6s ease-in-out 2s;
                    }
                    @keyframes bell-shake {
                        0%, 100% { transform: rotate(0deg); }
                        10%, 30%, 50%, 70%, 90% { transform: rotate(-10deg); }
                        20%, 40%, 60%, 80% { transform: rotate(10deg); }
                    }
                    .notification-bell-wrapper:hover .notification-badge {
                        animation: none;
                        opacity: 1;
                        transform: scale(1.15);
                    }
                    .notification-bell-wrapper:hover .notification-bell-icon,
                    .header-icon-link:hover .header-clock-icon {
                        color: #4CAF50;
                        animation: none;
                    }
                </style>
                <ul class="dropdown-menu dropdown-menu-end notification-dropdown" aria-labelledby="notificationDropdown" style="min-width: 320px; max-width: 400px; max-height: 500px; overflow-y: auto; box-shadow: 0 4px 12px rgba(0,0,0,0.15); border: none; border-radius: 8px; padding: 0;">
                    <li>
                        <div class="dropdown-header d-flex justify-content-between align-items-center px-3 py-2 border-bottom bg-light">
                            <h6 class="mb-0 fw-bold">Pending Actions</h6>
                            @if($totalPending > 0)
                                <span class="badge bg-danger rounded-pill">{{ $totalPending }}</span>
                            @endif
                        </div>
                    </li>
                    @if(count($pendingItems) > 0)
                        <li>
                            <div class="px-2 py-2">
                            @foreach($pendingItems as $item)
                                @php
                                    $borderColors = [
                                        'warning' => '#ffc107',
                                        'info' => '#0dcaf0',
                                        'success' => '#198754',
                                        'danger' => '#dc3545',
                                        'primary' => '#0d6efd'
                                    ];
                                    $bgColors = [
                                        'warning' => 'rgba(255, 193, 7, 0.1)',
                                        'info' => 'rgba(13, 202, 240, 0.1)',
                                        'success' => 'rgba(25, 135, 84, 0.1)',
                                        'danger' => 'rgba(220, 53, 69, 0.1)',
                                        'primary' => 'rgba(13, 110, 253, 0.1)'
                                    ];
                                    $borderColor = $borderColors[$item['color']] ?? '#61ce70';
                                    $bgColor = $bgColors[$item['color']] ?? 'rgba(97, 206, 112, 0.1)';
                                @endphp
                                <a href="{{ $item['route'] }}" 
                                   class="dropdown-item d-flex align-items-center p-3 mb-2 rounded notification-item"
                                   style="transition: all 0.2s; border-left: 3px solid {{ $borderColor }};"
                                   onmouseover="this.style.backgroundColor='#f8f9fa'; this.style.transform='translateX(4px)';"
                                   onmouseout="this.style.backgroundColor='transparent'; this.style.transform='translateX(0)';">
                                    <div class="flex-shrink-0 me-3">
                                        <div class="rounded-circle d-flex align-items-center justify-content-center" 
                                             style="width: 40px; height: 40px; background-color: {{ $bgColor }};">
                                            <i class="fas fa-{{ $item['icon'] }} text-{{ $item['color'] }}"></i>
                                        </div>
                                    </div>
                                    <div class="flex-grow-1">
                                        <h6 class="mb-1 fw-semibold" style="font-size: 0.9rem;">{{ $item['title'] }}</h6>
                                        <small class="text-muted">Requires your attention</small>
                                    </div>
                                    <div class="flex-shrink-0 ms-2">
                                        <span class="badge bg-{{ $item['color'] }} rounded-pill">{{ $item['count'] }}</span>
                                    </div>
                                </a>
                            @endforeach
                            </div>
                        </li>
                    @else
                        <li>
                            <div class="text-center py-4 px-3">
                                <i class="fas fa-check-circle fa-2x text-success mb-2"></i>
                                <p class="text-muted mb-0 fw-medium">All caught up!</p>
                                <small class="text-muted">No pending actions</small>
                            </div>
                        </li>
                    @endif
                </ul>
            </li>
        @endif

        <!-- ⏰ Digital Clock Display -->
        <li class="nav-item d-flex align-items-center me-3">
            <span id="header-date" class="text-muted fs-6"></span>
        </li>


        <script>
            function updateDate() {
                const dateElement = document.getElementById('header-date');

                const now = new Date();

                // Format the date
                const months = ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"];
                const day = now.getDate();
                const month = months[now.getMonth()];
                const year = now.getFullYear();
                dateElement.textContent = `${month} ${day}, ${year}`;
            }

            updateDate(); // Set the date immediately
        </script>



        <!-- 👤 User Profile Dropdown -->
        <li class="nav-item dropdown has-arrow new-user-menus">
            <a href="#" class="dropdown-toggle nav-link" data-bs-toggle="dropdown">
                <span class="user-img">
                    @if (Auth::check() && auth()->user() && auth()->user()->profile_picture)
                        <img class="rounded-circle" src="{{ asset('storage/' . auth()->user()->profile_picture) }}"
                            width="31" alt="User">
                    @else
                        <img class="rounded-circle" src="{{ asset('assets/img/icon.png') }}" alt="Default User Icon"
                            style="max-width: 160px; height: 38px; padding: 1px; object-fit: cover;">
                    @endif
                    <div class="user-text">
                        @if (Auth::check() && auth()->user())
                            @php
                                $currentUser = auth()->user();
                                $userRoles = $currentUser->getRoleNames();
                                $rolePriority = [
                                    'super-admin'        => 100,
                                    'ceo'                => 90,
                                    'coo'                => 85,
                                    'cfo'                => 80,
                                    'cms'                => 75,
                                    'ccdro'              => 75,
                                    'crhdo'              => 70,
                                    'hr'                 => 65,
                                    'Admin-Secretary'    => 60,
                                    'procurement-officer'=> 55,
                                    'payroll_accountant' => 50,
                                    'finance officer'    => 50,
                                    'it'                 => 45,
                                    'line-manager'       => 40,
                                ];
                                $displayRole = $userRoles->sortByDesc(fn($r) => $rolePriority[strtolower($r)] ?? $rolePriority[$r] ?? 0)->first();
                                $roleAbbreviations = ['coo', 'cfo', 'cms', 'ccdro', 'ceo', 'crhdo', 'it', 'hr'];
                                $displayRoleFormatted = $displayRole
                                    ? (in_array(strtolower($displayRole), $roleAbbreviations)
                                        ? strtoupper($displayRole)
                                        : ucwords(str_replace(['-', '_'], ' ', $displayRole)))
                                    : null;
                            @endphp
                            <h6>{{ Auth::user()->fname }} {{ Auth::user()->lname }}</h6>
                            <p class="text-muted mb-0">
                                @if ($displayRoleFormatted !== null)
                                    {{ $displayRoleFormatted }}
                                @else
                                    {{ Auth::user()->jobTitle?->job_title ?? 'No job title' }}
                                @endif
                            </p>
                        @else
                            <h6>Guest</h6>
                            <p class="text-muted mb-0">Not logged in</p>
                        @endif
                    </div>
                </span>
            </a>
            <div class="dropdown-menu dropdown-menu-end user-profile-dropdown shadow-sm" style="min-width: 240px; border-radius: 10px; border: 1px solid rgba(0,0,0,0.06); padding: 0.5rem 0;">
                @if (Auth::check())
                    <div class="px-1">
                        <a class="user-dropdown-link" href="{{ route('profile.index') }}">
                            <i class="fas fa-user-circle me-2"></i>My Profile
                        </a>
                        <a class="user-dropdown-link user-dropdown-logout" href="{{ route('logout') }}">
                            <i class="fas fa-sign-out-alt me-2"></i>Logout
                        </a>
                    </div>
                @else
                    <a class="dropdown-item" href="{{ route('login') }}">Login</a>
                @endif
            </div>
        </li>
        <style>
            .user-profile-dropdown .user-dropdown-link {
                display: flex;
                align-items: center;
                padding: 0.5rem 0.75rem;
                border-radius: 6px;
                font-size: 0.9rem;
                color: #374151;
                text-decoration: none;
                transition: background 0.15s ease, color 0.15s ease;
            }
            .user-profile-dropdown .user-dropdown-link:hover { background: rgba(0,0,0,0.04); color: #111827; }
            .user-profile-dropdown .user-dropdown-logout:hover { color: #dc2626; }
        </style>

    </ul>
</div>
{{-- <script>
    function updateHeaderClock() {
        const now = new Date();
        const hours = String(now.getHours()).padStart(2, '0');
        const minutes = String(now.getMinutes()).padStart(2, '0');
        const seconds = String(now.getSeconds()).padStart(2, '0');
        const timeString = `${hours}:${minutes}:${seconds}`;
        document.getElementById('header-clock').textContent = timeString;
    }

    setInterval(updateHeaderClock, 1000);
    updateHeaderClock(); // Run once immediately
</script> --}}
