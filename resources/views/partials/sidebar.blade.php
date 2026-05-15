<style>
    /* Custom CSS for Blinking Badge */
    .badge-blink {
        animation: blink 1s infinite;
    }

    @keyframes blink {

        0%,
        100% {
            opacity: 1;
            background-color: #ff5733;
        }

        50% {
            opacity: 0;
            background-color: #ff0000;
        }
    }
</style>
<div class="sidebar" id="sidebar">
    <div class="sidebar-inner slimscroll">
        <div id="sidebar-menu" class="sidebar-menu">
            <ul>

                {{-- Dashboard --}}
                <li class="{{ request()->routeIs('dashboard') ? 'active' : '' }}">
                    <a href="{{ route('dashboard') }}"><i class="fas fa-tachometer-alt"></i> <span> Dashboard</span></a>
                </li>

                <li class="{{ request()->routeIs('it-requests.index') ? 'active' : '' }}">
                    <a href="{{ route('it-requests.index') }}"><i class="fas fa-server"></i> <span>IT Requests</span></a>
                </li>
                <!-- Sidebar HR Requests Section -->
                <li class="{{ request()->routeIs('hr-requests.index') ? 'active' : '' }}">
                    <a href="{{ route('hr-requests.index') }}"><i class="fas fa-file-alt"></i> <span>HR
                            Forms</span></a>
                </li>

                <!-- Contract Management Main Item -->
                {{-- <li
                    class="nav-item {{ request()->routeIs(['contract-templates.*', 'contracts.fixed-flex.*']) ? 'menu-open' : '' }}">
                    <a href="#"
                        class="nav-link {{ request()->routeIs(['contract-templates.*', 'contracts.fixed-flex.*']) ? 'active' : '' }}">
                        <i class="nav-icon fas fa-file-contract"></i>
                        <p>
                            Contract Management
                            <i class="right fas fa-angle-left"></i>
                        </p>
                    </a>

                    <ul class="nav nav-treeview">
                        <!-- Fix-Flex Contracts -->
                        <li class="nav-item">
                            <a href="{{ route('contracts.fixed-flex.index') }}"
                                class="nav-link {{ request()->routeIs('contracts.fixed-flex.*') ? 'active' : '' }}">
                                <i class="fas fa-random nav-icon"></i> <!-- Flex icon -->
                                <p>Fix-Flex Contracts</p>
                            </a>
                        </li>

                        <!-- Templates -->
                        <li class="nav-item">
                            <a href="{{ route('contract-templates.index') }}"
                                class="nav-link {{ request()->routeIs('contract-templates.*') ? 'active' : '' }}">
                                <i class="fas fa-file-alt nav-icon"></i> <!-- Template icon -->
                                <p>Templates</p>
                            </a>
                        </li>

                        <!-- Other Contract Types (optional) -->
                        <li class="nav-item">
                            <a href="{{ route('contracts.index') }}"
                                class="nav-link {{ request()->routeIs('contracts.index') ? 'active' : '' }}">
                                <i class="fas fa-file-signature nav-icon"></i>
                                <p>All Contracts</p>
                            </a>
                        </li>
                    </ul>
                </li> --}}

                {{-- Requests --}}
                @can('view my requests')
                    <li class="{{ request()->routeIs('request.index') ? 'active' : '' }}">
                        <a href="{{ route('request.index') }}">
                            <i class="fas fa-tasks"></i> <span>My Requests</span>
                        </a>
                    </li>
                @endcan

                {{-- <li class="{{ request()->routeIs('education.details') ? 'active' : '' }}">
                    <a href="{{ route('education.details') }}">
                        <i class="fas fa-graduation-cap"></i> <span>Education Details</span>
                    </a>
                </li> --}}


                {{-- Policies: Staff signed visible to all; Department & Organization only with permission --}}
                @php
                    $isPoliciesActive = request()->routeIs('policies.staff-signed') || request()->routeIs('policies.index') || request()->routeIs('policies.create') || request()->routeIs('policies.edit')
                        || request()->routeIs('department-policies.*');
                @endphp
                <li class="treeview {{ $isPoliciesActive ? 'active' : '' }}">
                    <a href="#">
                        <i class="fas fa-shield-alt"></i>
                        <span>Policies</span>
                        <span class="menu-arrow"></span>
                    </a>
                    <ul style="{{ $isPoliciesActive ? 'display: block;' : '' }}">
                        <li>
                            <a href="{{ route('policies.staff-signed') }}"
                                class="{{ request()->routeIs('policies.staff-signed') ? 'active' : '' }}">
                                <span>Staff signed policies</span>
                            </a>
                        </li>
                        @can('view policies')
                        <li>
                            <a href="{{ route('department-policies.index') }}"
                                class="{{ request()->routeIs('department-policies.*') ? 'active' : '' }}">
                                <span>Department policy</span>
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('policies.index', ['type' => 'other_organization']) }}"
                                class="{{ request()->routeIs('policies.index') && request()->get('type') === 'other_organization' ? 'active' : '' }}">
                                <span>Organization Policies</span>
                            </a>
                        </li>
                        @endcan
                    </ul>
                </li>
                @can('view sops')
                    <li class="{{ request()->routeIs('sops.index') || request()->routeIs('sops.create') || request()->routeIs('sops.edit') ? 'active' : '' }}">
                        <a href="{{ route('sops.index') }}">
                            <i class="fas fa-clipboard-list"></i>
                            <span>SOPs</span>
                        </a>
                    </li>
                @endcan

                @php
                    use Illuminate\Support\Facades\Auth;
                    use Illuminate\Support\Facades\DB;

                    $isActiveMenu = request()->routeIs(
                        'requestapprove.index',
                        'clearance.*',
                        'locum-requests.viewAgreementRequest',
                        'locum-requests.view',
                        'oncall_requests.index',
                        'oncall_requests.view',
                        'oncall_requests.report',
                        'night-shift.approve.index',
                        'requisitions.pending',
                        'requisitions.show',
                    );
                    // Count general requests broken down by form type
                    // Finance Officers should not see notification badge
                    $generalFormTypes = [];
                    $generalRequestCount = 0;
                    if (!Auth::user()->hasRole('finance officer')) {
                        $baseGeneralQuery = function () {
                            return DB::table('work_flow_histories')
                                ->join('workflows', 'work_flow_histories.work_flow_id', '=', 'workflows.id')
                                ->where('work_flow_histories.attended_by', Auth::id())
                                ->where('work_flow_histories.status', 0)
                                ->where('workflows.work_flow_completed', '!=', 1)
                                ->whereNull('locum_agreement_status')
                                ->whereNull('locum_request_status')
                                ->whereNull('on_call_request_status')
                                ->whereNull('workflows.night_shift_claim_id');
                        };

                        $formTypeDefs = [
                            ['label' => 'HR Forms',          'query' => fn() => $baseGeneralQuery()->whereNotNull('workflows.hr_form')->count()],
                            ['label' => 'ICT Access Forms',  'query' => fn() => $baseGeneralQuery()->whereNotNull('workflows.ict_request_resource_id')->count()],
                            ['label' => 'Change Requests',   'query' => fn() => $baseGeneralQuery()->whereNotNull('workflows.change_request_id')->count()],
                            ['label' => 'ID Card Forms',     'query' => fn() => $baseGeneralQuery()->where('workflows.id_form', '>', 0)->count()],
                            ['label' => 'Bank Forms',        'query' => fn() => $baseGeneralQuery()->where('workflows.bank_form', '>', 0)->count()],
                            ['label' => 'NHIF Forms',        'query' => fn() => $baseGeneralQuery()->where('workflows.nhif_form', '>', 0)->count()],
                            ['label' => 'HESLB Forms',       'query' => fn() => $baseGeneralQuery()->where('workflows.heslb_form', '>', 0)->count()],
                            ['label' => 'Job Descriptions',  'query' => fn() => $baseGeneralQuery()->whereNotNull('workflows.job_description_id')->count()],
                        ];

                        foreach ($formTypeDefs as $ft) {
                            $cnt = ($ft['query'])();
                            if ($cnt > 0) {
                                $generalFormTypes[] = ['label' => $ft['label'], 'count' => $cnt];
                                $generalRequestCount += $cnt;
                            }
                        }
                    }

                    // Count clearance forms pending for current user
                    // Only count pending approvals (status = 0) for all users including HR
                    $isHR = Auth::user()->hasRole('hr');
                    $clearancePendingCount = 0;
                    if (Auth::user()->can('access clearance form')) {
                        // For all users (including HR): Only count pending approvals
                        $clearancePendingCount = DB::table('clearance_work_flow_histories')
                            ->join(
                                'clearance_work_flows',
                                'clearance_work_flow_histories.work_flow_id',
                                '=',
                                'clearance_work_flows.id',
                            )
                            ->join(
                                'clearance_forms',
                                'clearance_forms.id',
                                '=',
                                'clearance_work_flows.requested_resource_id',
                            )
                            ->where('clearance_work_flow_histories.attended_by', Auth::id())
                            ->where('clearance_work_flow_histories.status', 0)
                            ->where('clearance_forms.status', '!=', 'rejected')
                            ->where(function ($query) {
                                $query
                                    ->where('clearance_work_flows.work_flow_completed', '!=', 1)
                                    ->orWhereNull('clearance_work_flows.work_flow_completed');
                            })
                            ->whereIn('clearance_work_flow_histories.id', function ($subquery) {
                                $subquery
                                    ->selectRaw('MAX(clearance_work_flow_histories.id)')
                                    ->from('clearance_work_flow_histories')
                                    ->where('clearance_work_flow_histories.status', 0)
                                    ->where('clearance_work_flow_histories.attended_by', Auth::id())
                                    ->groupBy('clearance_work_flow_histories.work_flow_id');
                            })
                            ->count();
                    }

                    $pendingCount = $generalRequestCount;
                    $pendingLocumCount = DB::table('work_flow_histories')
                        ->join('workflows', 'work_flow_histories.work_flow_id', '=', 'workflows.id')
                        ->where('work_flow_histories.attended_by', Auth::id())
                        ->where('work_flow_histories.status', 0)
                        ->whereNotNull('workflows.locum_request_id')
                        ->count();

                    $pendingLocumAgreementCount = DB::table('work_flow_histories')
                        ->join('workflows', 'work_flow_histories.work_flow_id', '=', 'workflows.id')
                        ->where('work_flow_histories.attended_by', Auth::id())
                        ->where('work_flow_histories.status', 0)
                        ->whereNotNull('workflows.locum_agreement_id')
                        ->count();

                    $pendingOnCallCount = DB::table('work_flow_histories')
                        ->join('workflows', 'work_flow_histories.work_flow_id', '=', 'workflows.id')
                        ->where('work_flow_histories.attended_by', Auth::id())
                        ->where('work_flow_histories.status', 0) // pending step, regardless of code
                        ->whereNotNull('workflows.on_call_request_id')
                        ->count();

                    $pendingNightShiftCount = DB::table('work_flow_histories')
                        ->join('workflows', 'work_flow_histories.work_flow_id', '=', 'workflows.id')
                        ->where('work_flow_histories.attended_by', Auth::id())
                        ->where('work_flow_histories.status', 0)
                        ->whereNotNull('workflows.night_shift_claim_id')
                        ->count();

                    $user = Auth::user();
                    $isApprover = $user && $user->hasAnyRole(['hr', 'line-manager', 'in-charge']);

                    // Procurement contracts counts
                    $pendingContractLineManager = 0;
                    $pendingContractHec = 0;
                    $pendingContractProcurement = 0;
                    $expiringContractCount = 0;
                    $isContractRelevant = $user && ($user->hasAnyRole([
                        'procurement-officer', 'line-manager', 'coo', 'cfo', 'cms', 'ccdro', 'hr', 'super-admin'
                    ]) || $user->can('manage_contracts'));
                    if ($isContractRelevant && \Schema::hasTable('ccbrt_contracts')) {
                        $pendingContractLineManager = \App\Models\CcbrtContract::where('approval_stage', 'line_manager')
                            ->where('current_approver_id', Auth::id())
                            ->whereIn('status', ['in_progress', 'pending', 'draft'])->count();
                        $pendingContractHec = \App\Models\CcbrtContract::where('approval_stage', 'hec')
                            ->where('current_approver_id', Auth::id())
                            ->whereIn('status', ['in_progress', 'pending'])->count();
                        $pendingContractProcurement = \App\Models\CcbrtContract::where('approval_stage', 'procurement')
                            ->where('current_approver_id', Auth::id())
                            ->whereIn('status', ['in_progress', 'pending'])->count();
                        if ($user->hasAnyRole(['line-manager', 'coo', 'cfo', 'cms', 'ccdro'])) {
                            $expiringContractCount = \App\Models\CcbrtContract::where('end_date', '>=', now())
                                ->where('end_date', '<=', now()->addDays(30))
                                ->whereNotIn('renewal_status', ['pending'])
                                ->whereNotIn('approval_stage', ['line_manager', 'hec', 'procurement'])->count();
                        }
                    }
                    $pendingContractTotal = $pendingContractLineManager + $pendingContractHec + $pendingContractProcurement;

                    // Smart link: route to the view most relevant for this user's pending actions
                    if ($pendingContractTotal > 0) {
                        $contractsActionUrl = route('procurements.contracts.index', ['view' => 'my_pending']);
                    } elseif ($expiringContractCount > 0) {
                        $contractsActionUrl = route('procurements.contracts.index', ['view' => 'expiring']);
                    } else {
                        $contractsActionUrl = route('procurements.contracts.index');
                    }
                @endphp

                @can('approve requests')
                    <li class="treeview {{ $isActiveMenu ? 'active' : '' }}">
                        <a href="#">
                            <i class="fas fa-folder-open"></i>
                            <span>Requests to Approve</span>
                            <span class="menu-arrow"></span>
                        </a>
                        <ul style="{{ $isActiveMenu ? 'display: block;' : '' }}">
                            @can('approve general requests')
                                @if(count($generalFormTypes) > 0)
                                    @foreach($generalFormTypes as $gft)
                                    <li>
                                        <a href="{{ route('requestapprove.index') }}"
                                            class="{{ request()->routeIs('requestapprove.index') ? 'active' : '' }}">
                                            <span>{{ $gft['label'] }}</span>
                                            <span class="badge badge-pill badge-primary badge-blink ms-2">
                                                {{ $gft['count'] }}
                                            </span>
                                        </a>
                                    </li>
                                    @endforeach
                                @else
                                <li>
                                    <a href="{{ route('requestapprove.index') }}"
                                        class="{{ request()->routeIs('requestapprove.index') ? 'active' : '' }}">
                                        <span>General Requests</span>
                                    </a>
                                </li>
                                @endif
                            @endcan
                            @can('access clearance form')
                                <li>
                                    <a href="{{ route('clearance.index') }}"
                                        class="{{ request()->routeIs('clearance.*') ? 'active' : '' }}">
                                        <span>Clearance Forms</span>
                                        @if ($clearancePendingCount > 0)
                                            <span class="badge badge-pill badge-primary badge-blink ms-2">
                                                {{ $clearancePendingCount }}
                                            </span>
                                        @endif
                                    </a>
                                </li>
                            @endcan
                            @can('approve locum requests')
                                <li>
                                    <a href="{{ route('locum-requests.view') }}"
                                        class="{{ request()->routeIs('locum-requests.view', 'locum-requests.viewAgreementRequest') ? 'active' : '' }}">
                                        <span>Locum Requests</span>
                                        @if ($pendingLocumCount + $pendingLocumAgreementCount > 0)
                                            <span class="badge badge-pill badge-primary badge-blink ms-2">
                                                {{ $pendingLocumCount + $pendingLocumAgreementCount }}
                                            </span>
                                        @endif
                                    </a>
                                </li>
                            @endcan
                            @can('approve oncall requests')
                                <li>
                                    <a href="{{ route('oncall_requests.view') }}"
                                        class="{{ request()->routeIs('oncall_requests.index', 'oncall_requests.view', 'oncall_requests.report') ? 'active' : '' }}">
                                        <span>On Call Claims</span>
                                        @if ($pendingOnCallCount > 0)
                                            <span class="badge badge-pill badge-primary badge-blink ms-2">
                                                {{ $pendingOnCallCount }}
                                            </span>
                                        @endif
                                    </a>
                                </li>
                            @endcan
                            @if ($pendingNightShiftCount > 0 || request()->routeIs('night-shift.approve.index'))
                                <li>
                                    <a href="{{ route('night-shift.approve.index') }}"
                                        class="{{ request()->routeIs('night-shift.approve.index') ? 'active' : '' }}">
                                        <span>Night Allowances</span>
                                        @if ($pendingNightShiftCount > 0)
                                            <span class="badge badge-pill badge-primary badge-blink ms-2">
                                                {{ $pendingNightShiftCount }}
                                            </span>
                                        @endif
                                    </a>
                                </li>
                            @endif
                            @if (auth()->user()->hasAnyRole(['line-manager', 'payroll_accountant', 'coo', 'cms', 'cfo', 'ccdro', '', 'ceo', 'hr']))
                                @php
                                    $pendingRequisitionCount = 0;
                                    if (\Schema::hasTable('requisitions')) {
                                        $pendingRequisitionCount = 0;
                                        $user = auth()->user();
                                        if ($user->hasRole('payroll_accountant')) {
                                            $pendingRequisitionCount = \App\Models\Requisition::where('status', 'pending_payroll')->count();
                                        } elseif ($user->hasAnyRole(['coo', 'cfo', 'cms', 'ccdro'])) {
                                            $userHecRoles = collect($user->getRoleNames())
                                                ->map(fn ($r) => strtolower((string) $r))
                                                ->filter(fn ($r) => in_array($r, ['coo', 'cfo', 'cms', 'ccdro'], true))
                                                ->values()->all();
                                            $pendingRequisitionCount = \App\Models\Requisition::where(function ($query) use ($user, $userHecRoles) {
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
                                                if ($user->hasRole('cfo')) {
                                                    $query->orWhere('status', 'pending_cfo');
                                                }
                                            })->count();
                                        } elseif ($user->hasRole('ceo')) {
                                            $pendingRequisitionCount = \App\Models\Requisition::where('status', 'pending_ceo')->count();
                                        } elseif ($user->hasRole('hr')) {
                                            $pendingRequisitionCount = \App\Models\Requisition::where('status', 'pending_hr')->count();
                                        } elseif ($user->hasRole('line-manager')) {
                                            // Only count items that need line manager's action (returned for editing, not expired)
                                            $pendingRequisitionCount = \App\Models\Requisition::where('user_id', $user->id)
                                                ->where('status', 'rejected_for_editing')
                                                ->where(function ($q) {
                                                    $q->whereNull('can_edit_until')->orWhere('can_edit_until', '>', now());
                                                })->count();
                                        }
                                    }
                                @endphp
                                <li>
                                    <a href="{{ route('requisitions.pending') }}"
                                        class="{{ request()->routeIs('requisitions.pending') ? 'active' : '' }}">
                                        <span>Recruitment Requests</span>
                                        @if ($pendingRequisitionCount > 0)
                                            <span class="badge badge-pill badge-primary badge-blink ms-2">
                                                {{ $pendingRequisitionCount }}
                                            </span>
                                        @endif
                                    </a>
                                </li>
                            @endif
                        </ul>
                    </li>
                @endcan

                {{-- Parent: "Manage Category" permission was missing from Super-Admin in RoleSeeder; show if user can manage categories OR view departments (same as route middleware). --}}
                @canany(['Manage Category', 'view departments'])
                    <li
                        class="treeview {{ request()->routeIs('department.index', 'nhif.index', 'hmis.index', 'remark.index', 'privilege.index', 'employment.index', 'job_titles.index', 'hec.index', 'division.index', 'contractual-hours.*', 'locum-rates.*', 'oncall-rates.*') ? 'active' : '' }}">
                        <a href="#">
                            <i class="fas fa-tasks"></i>
                            <span>Manage Category</span>
                            <span class="menu-arrow"></span>
                        </a>
                        <ul
                            style="{{ request()->routeIs('department.index', 'nhif.index', 'hmis.index', 'remark.index', 'privilege.index', 'employment.index', 'job_titles.index', 'hec.index', 'division.index', 'contractual-hours.*', 'locum-rates.*', 'oncall-rates.*') ? 'display: block;' : '' }}">

                            @can('view departments')
                                <li class="{{ request()->routeIs('department.index') ? 'active' : '' }}">
                                    <a href="{{ route('department.index') }}">
                                        <span>Departments</span>
                                    </a>
                                </li>
                            @endcan

                            @can('job title')
                                <li
                                    class="{{ request()->routeIs('job_titles.index') || request()->routeIs('job_titles.create') || request()->routeIs('job_titles.edit') ? 'active' : '' }}">
                                    <a href="{{ route('job_titles.index') }}">
                                        <span>Job Titles</span>
                                    </a>
                                </li>
                            @endcan

                            @can('view nhif')
                                <li class="{{ request()->routeIs('nhif.index') ? 'active' : '' }}">
                                    <a href="{{ route('nhif.index') }}">
                                        <span>NHIF Qualifications</span>
                                    </a>
                                </li>
                            @endcan

                            @can('view hmis')
                                <li class="{{ request()->routeIs('hmis.index') ? 'active' : '' }}">
                                    <a href="{{ route('hmis.index') }}">
                                        <span>HMIS Access</span>
                                    </a>
                                </li>
                            @endcan

                            @can('view user category')
                                <li class="{{ request()->routeIs('privilege.index') ? 'active' : '' }}">
                                    <a href="{{ route('privilege.index') }}">
                                        <span>User Category</span>
                                    </a>
                                </li>
                            @endcan
                            @can('view user category')
                                <li class="{{ request()->routeIs('aruti.index') ? 'active' : '' }}">
                                    <a href="{{ route('aruti.index') }}">
                                        <span>ARUT Levels</span>
                                    </a>
                                </li>
                            @endcan
                            @can('view user category')
                                <li class="{{ request()->routeIs('edocs.index') ? 'active' : '' }}">
                                    <a href="{{ route('edocs.index') }}">
                                        <span>eDocs Levels</span>
                                    </a>
                                </li>
                            @endcan
                            @can('manage asset categories')
                                <li class="{{ request()->routeIs('asset-management.asset-categories.*') ? 'active' : '' }}">
                                    <a href="{{ route('asset-management.asset-categories.index') }}">
                                        <span>Asset Categories</span>
                                    </a>
                                </li>
                            @endcan
                            @can('view user category')
                                <li class="{{ request()->routeIs('network-folder.index') ? 'active' : '' }}">
                                    <a href="{{ route('network-folder.index') }}">
                                        <span>Network Folders</span>
                                    </a>
                                </li>
                            @endcan
                            @can('view user category')
                                <li class="{{ request()->routeIs('access-key-card.index') ? 'active' : '' }}">
                                    <a href="{{ route('access-key-card.index') }}">
                                        <span>Access Key Cards</span>
                                    </a>
                                </li>
                            @endcan
                            @can('view sap access')
                                <li class="{{ request()->routeIs('sap.index') ? 'active' : '' }}">
                                    <a href="{{ route('sap.index') }}">
                                        <span>SAP Access</span>
                                    </a>
                                </li>
                            @endcan
                            @can('view departments')
                                <li class="{{ request()->routeIs('shift-settings.index') ? 'active' : '' }}">
                                    <a href="{{ route('shift-settings.index') }}">
                                        <span>Shift Settings</span>
                                    </a>
                                </li>
                            @endcan

                            @can('view entities')
                                <li class="{{ request()->routeIs('division.index') ? 'active' : '' }}">
                                    <a href="{{ route('division.index') }}">
                                        <span>CCBRT Entities</span>
                                    </a>
                                </li>
                            @endcan

                            @can('Manage Category')
                                <li class="{{ request()->routeIs('contractual-hours.*') ? 'active' : '' }}">
                                    <a href="{{ route('contractual-hours.index') }}">
                                        <span>Contractual Hours</span>
                                    </a>
                                </li>
                            @endcan
                            @can('Manage Category')
                                <li class="{{ request()->routeIs('locum-rates.*') ? 'active' : '' }}">
                                    <a href="{{ route('locum-rates.index') }}">
                                        <span>Manage Locum Rates</span>
                                    </a>
                                </li>
                            @endcan
                            @can('Manage Category')
                                <li class="{{ request()->routeIs('oncall-rates.*') ? 'active' : '' }}">
                                    <a href="{{ route('oncall-rates.index') }}">
                                        <span>Manage On-Call Rates</span>
                                    </a>
                                </li>
                            @endcan

                        </ul>
                    </li>
                @endcanany
                @can('view staff details')
                    <li class="{{ request()->routeIs('employee.index') ? 'active' : '' }}">
                        <a href="{{ route('employee.index') }}">
                            <i class="fas fa-user"></i>
                            <span>Staff Details</span>
                        </a>
                    </li>
                @endcan

                {{-- Asset Management --}}
                @can('manage assets')
                    <li class="treeview {{ request()->routeIs('asset-management.*') ? 'active' : '' }}">
                        <a href="#">
                            <i class="fas fa-laptop"></i>
                            <span>Asset Management</span>
                            <span class="menu-arrow"></span>
                        </a>
                        <ul style="{{ request()->routeIs('asset-management.*') ? 'display: block;' : '' }}">
                            <li class="{{ request()->routeIs('asset-management.assets.tag-management') ? 'active' : '' }}">
                                <a href="{{ route('asset-management.assets.tag-management') }}">
                                    <span>Asset Tags</span>
                                </a>
                            </li>
                            <li
                                class="{{ request()->routeIs('asset-management.assets.index') || request()->routeIs('asset-management.assets.create') || request()->routeIs('asset-management.assets.edit') || request()->routeIs('asset-management.assets.show') ? 'active' : '' }}">
                                <a href="{{ route('asset-management.assets.index') }}">
                                    <span>Add Assets</span>
                                </a>
                            </li>
                            <li class="{{ request()->routeIs('asset-management.assignments.*') ? 'active' : '' }}">
                                <a href="{{ route('asset-management.assignments.index') }}">
                                    <span>Assign Assets</span>
                                </a>
                            </li>
                            <li class="{{ request()->routeIs('asset-management.movements.*') ? 'active' : '' }}">
                                <a href="{{ route('asset-management.movements.index') }}">
                                    <span>Movements</span>
                                </a>
                            </li>
                            <li class="{{ request()->routeIs('asset-management.maintenance.*') ? 'active' : '' }}">
                                <a href="{{ route('asset-management.maintenance.index') }}">
                                    <span>Maintenance</span>
                                </a>
                            </li>
                            <li class="{{ request()->routeIs('asset-management.retirement.*') ? 'active' : '' }}">
                                <a href="{{ route('asset-management.retirement.index') }}">
                                    <span>Retired Assets</span>
                                </a>
                            </li>
                            <li class="{{ request()->routeIs('asset-management.dashboard') ? 'active' : '' }}">
                                <a href="{{ route('asset-management.dashboard') }}">
                                    <span>Dashboard</span>
                                </a>
                            </li>
                        </ul>
                    </li>
                @endcan

                @role('incharge|line-manager|hr|coo|cfo|cms|super-admin|platform-manager|it')
                    <li class="{{ request()->routeIs('biotime.index') ? 'active' : '' }}">
                        <a href="{{ route('biotime.index') }}">
                            <i class="fas fa-fingerprint"></i>
                            <span>Biometric Attendance</span>
                        </a>
                    </li>
                @endrole

                {{-- Performance Management --}}
{{--                @if(Auth::user()->can('view performance dashboard') || Auth::user()->hasAnyRole(['super-admin','Super-Admin','admin','hr','ceo','coo','cms','ccdro','line-manager']))--}}
{{--                    @php--}}
{{--                        $isPerformanceActive = request()->is('performance/*') || request()->routeIs('performance.*');--}}
{{--                        $pmsPendingCount = 0;--}}
{{--                        if (\Illuminate\Support\Facades\Schema::hasTable('pms_notifications')) {--}}
{{--                            $pmsPendingCount = \App\Models\Performance\PmsNotification::where('user_id', Auth::id())--}}
{{--                                ->whereNull('read_at')->count();--}}
{{--                        }--}}
{{--                        $pmsAuthUser = Auth::user();--}}
{{--                        $pmsIsSuperAdmin = $pmsAuthUser->hasAnyRole(['super-admin','Super-Admin','admin']);--}}
{{--                    @endphp--}}
{{--                    <li class="treeview {{ $isPerformanceActive ? 'active' : '' }}">--}}
{{--                        <a href="#">--}}
{{--                            <i class="fas fa-chart-line"></i>--}}
{{--                            <span>Performance Management</span>--}}
{{--                            @if($pmsPendingCount > 0)--}}
{{--                                <span class="badge badge-pill badge-primary badge-blink ms-2">{{ min($pmsPendingCount, 99) }}</span>--}}
{{--                            @endif--}}
{{--                            <span class="menu-arrow"></span>--}}
{{--                        </a>--}}
{{--                        <ul style="{{ $isPerformanceActive ? 'display:block;' : '' }}">--}}

{{--                            --}}{{-- Overview --}}
{{--                            <li>--}}
{{--                                <a href="{{ route('performance.dashboard') }}" class="{{ request()->routeIs('performance.dashboard') ? 'active' : '' }}">--}}
{{--                                    <span>Dashboard</span>--}}
{{--                                </a>--}}
{{--                            </li>--}}
{{--                            --}}{{-- KPI Management --}}
{{--                            <li>--}}
{{--                                <a href="{{ route('performance.goals-setting.index') }}" class="{{ request()->routeIs('performance.goals-setting.*') ? 'active' : '' }}">--}}
{{--                                    <span>Goals Setting</span>--}}
{{--                                </a>--}}
{{--                            </li>--}}

{{--                            --}}{{-- Cycle Activities --}}
{{--                            <li>--}}
{{--                                <a href="{{ route('performance.monitoring.index') }}" class="{{ request()->routeIs('performance.monitoring.index','performance.monitoring.create-update','performance.monitoring.history') ? 'active' : '' }}">--}}
{{--                                    <span>Monitoring</span>--}}
{{--                                </a>--}}
{{--                            </li>--}}
{{--                            <li>--}}
{{--                                <a href="{{ route('performance.mid-year.index') }}" class="{{ request()->routeIs('performance.mid-year.*') ? 'active' : '' }}">--}}
{{--                                    <span>Mid-Year Review</span>--}}
{{--                                </a>--}}
{{--                            </li>--}}
{{--                            <li>--}}
{{--                                <a href="{{ route('performance.appraisals.index') }}" class="{{ request()->routeIs('performance.appraisals.*') ? 'active' : '' }}">--}}
{{--                                    <span>Final Appraisal</span>--}}
{{--                                </a>--}}
{{--                            </li>--}}

{{--                            --}}{{-- Reports --}}
{{--                            @if($pmsIsSuperAdmin || $pmsAuthUser->can('view performance reports'))--}}
{{--                            <li>--}}
{{--                                <a href="{{ route('performance.reports.index') }}" class="{{ request()->routeIs('performance.reports.*') ? 'active' : '' }}">--}}
{{--                                    <span>Reports</span>--}}
{{--                                </a>--}}
{{--                            </li>--}}
{{--                            @endif--}}

{{--                            --}}{{-- Notifications: only when unread --}}
{{--                            @if($pmsPendingCount > 0)--}}
{{--                            <li>--}}
{{--                                <a href="{{ route('performance.notifications.index') }}" class="{{ request()->routeIs('performance.notifications.*') ? 'active' : '' }}">--}}
{{--                                    <span>Notifications</span>--}}
{{--                                    <span class="badge badge-pill badge-danger badge-blink ms-2">{{ min($pmsPendingCount, 99) }}</span>--}}
{{--                                </a>--}}
{{--                            </li>--}}
{{--                            @endif--}}

{{--                            --}}{{-- Administration (HR / CEO / Admin only) --}}
{{--                            @if($pmsIsSuperAdmin || $pmsAuthUser->hasAnyRole(['hr','ceo']))--}}
{{--                            <li>--}}
{{--                                <a href="{{ route('performance.cycles.index') }}" class="{{ request()->routeIs('performance.cycles.*') ? 'active' : '' }}">--}}
{{--                                    <span>Cycles</span>--}}
{{--                                </a>--}}
{{--                            </li>--}}
{{--                            <li>--}}
{{--                                <a href="{{ route('performance.workflow.hrbp-queue') }}" class="{{ request()->routeIs('performance.workflow.hrbp-queue') ? 'active' : '' }}">--}}
{{--                                    <span>HRBP Queue</span>--}}
{{--                                </a>--}}
{{--                            </li>--}}
{{--                            <li>--}}
{{--                                <a href="{{ route('performance.audit-log.index') }}" class="{{ request()->routeIs('performance.audit-log.*') ? 'active' : '' }}">--}}
{{--                                    <span>Audit Log</span>--}}
{{--                                </a>--}}
{{--                            </li>--}}
{{--                            @endif--}}

{{--                        </ul>--}}
{{--                    </li>--}}
{{--                @endif--}}

                {{-- Procurements Module --}}
                @can('view procureents')
                    <li
                        class="submenu {{ request()->is('procurements/*') || request()->is('VendorContracts*') || request()->is('vendors*') || request()->is('division*') ? 'active' : '' }}">
                        <a href="#">
                            <i class="fas fa-shopping-cart"></i> <span>Procurement</span>
                            <span class="menu-arrow"></span>
                        </a>
                        <ul
                            style="{{ request()->is('procurements/*') || request()->is('VendorContracts*') || request()->is('vendors*') || request()->is('division*') ? 'display: block;' : '' }}">

                            @can('view contracts')
                                <li>
                                    <a href="{{ route('procurements.contracts.index') }}"
                                        class="{{ request()->routeIs('procurements.contracts.*') ? 'active' : '' }}">
                                        Contracts
                                    </a>
                                </li>
                            @elsecan('manage_contracts')
                                <li>
                                    <a href="{{ route('procurements.contracts.index') }}"
                                        class="{{ request()->routeIs('procurements.contracts.*') ? 'active' : '' }}">
                                        All Contracts
                                    </a>
                                </li>
                            @endcan

                            {{-- Vendors: rendered exactly once for the highest-priority matching role/permission.
                                 Without this guard, users with multiple roles (e.g. line-manager + coo) would
                                 see multiple Vendors links because each @hasanyrole block renders independently. --}}
                            @php $vendorRendered = false; @endphp

                            @hasanyrole('super-admin|procurement-officer')
                                @php $vendorRendered = true; @endphp
                                <li>
                                    <a href="{{ route('procurements.vendors.index') }}"
                                        class="{{ request()->routeIs('procurements.vendors.index') || (request()->routeIs('procurements.vendors.*') && !request()->routeIs('procurements.vendors.department-vendors') && !request()->routeIs('procurements.vendors.hec-department-vendors')) ? 'active' : '' }}">
                                        Vendors
                                    </a>
                                </li>
                            @endhasanyrole

                            @if(!$vendorRendered)
                                @hasanyrole('line-manager')
                                    @php $vendorRendered = true; @endphp
                                    <li>
                                        @can('manage_vendor')
                                            <a href="{{ route('procurements.vendors.index') }}"
                                                class="{{ request()->routeIs('procurements.vendors.*') ? 'active' : '' }}">
                                                Vendors
                                            </a>
                                        @else
                                            <a href="{{ route('procurements.vendors.department-vendors') }}"
                                                class="{{ request()->routeIs('procurements.vendors.department-vendors') ? 'active' : '' }}">
                                                Vendors
                                            </a>
                                        @endcan
                                    </li>
                                @endhasanyrole
                            @endif

                            @if(!$vendorRendered)
                                @hasanyrole('coo|cfo|cms|ccdro')
                                    @php $vendorRendered = true; @endphp
                                    <li>
                                        @can('manage_vendor')
                                            <a href="{{ route('procurements.vendors.index') }}"
                                                class="{{ request()->routeIs('procurements.vendors.*') ? 'active' : '' }}">
                                                Vendors
                                            </a>
                                        @else
                                            <a href="{{ route('procurements.vendors.hec-department-vendors') }}"
                                                class="{{ request()->routeIs('procurements.vendors.hec-department-vendors') ? 'active' : '' }}">
                                                Vendors
                                            </a>
                                        @endcan
                                    </li>
                                @endhasanyrole
                            @endif

                            @if(!$vendorRendered)
                                @can('manage_vendor')
                                    <li>
                                        <a href="{{ route('procurements.vendors.index') }}"
                                            class="{{ request()->routeIs('procurements.vendors.*') ? 'active' : '' }}">
                                            Vendors
                                        </a>
                                    </li>
                                @endcan
                            @endif

                        </ul>
                    </li>
                @endcan

                {{-- HEC Contracts Module --}}
                @hasanyrole('Admin-Secretary|coo|cfo|cms|ccdro|super-admin')
                    <li class="{{ request()->routeIs('hec-contracts.*') ? 'active' : '' }}">
                        <a href="{{ route('hec-contracts.index') }}">
                            <i class="fas fa-file-contract"></i> <span>HEC Contracts</span>
                        </a>
                    </li>
                @endhasanyrole




                {{-- User Management --}}
                @php
                    $isUserManagementActive =
                        request()->routeIs('users.index') ||
                        request()->routeIs('role.index') ||
                        request()->routeIs('permission.index') ||
                        request()->routeIs('permission.create');
                @endphp
                @if (Auth::user()->can('view logs') || Auth::user()->can('manage roles'))
                    <li class="treeview {{ $isUserManagementActive ? 'active' : '' }}">
                        <a href="#">
                            <i class="fas fa-users"></i>
                            <span>User Management</span>
                            <span class="menu-arrow"></span>
                        </a>
                        <ul style="{{ $isUserManagementActive ? 'display: block;' : '' }}">
                            @can('view logs')
                                <li>
                                    <a href="{{ route('users.index') }}"
                                        class="{{ request()->routeIs('users.index') ? 'active' : '' }}">
                                        <span>Users</span>
                                    </a>
                                </li>
                            @endcan
                            @can('manage roles')
                                <li>
                                    <a href="{{ route('role.index') }}"
                                        class="{{ request()->routeIs('role.index') || request()->routeIs('permission.index') || request()->routeIs('permission.create') ? 'active' : '' }}">
                                        <span>Roles & Permissions</span>
                                    </a>
                                </li>
                            @endcan
                        </ul>
                    </li>
                @endif

                {{-- Workflow Management --}}
                @can('manage workflows')
                    @php
                        $isWorkflowActive = request()->routeIs('workflow-management.*');
                    @endphp
                    <li class="treeview {{ $isWorkflowActive ? 'active' : '' }}">
                        <a href="#">
                            <i class="fas fa-project-diagram"></i>
                            <span>Workflow</span>
                            <span class="menu-arrow"></span>
                        </a>
                        <ul style="{{ $isWorkflowActive ? 'display: block;' : '' }}">
                            <li class="{{ request()->routeIs('workflow-management.index') ? 'active' : '' }}">
                                <a href="{{ route('workflow-management.index') }}">
                                    <span>All Workflows</span>
                                </a>
                            </li>
                            <li class="{{ request()->routeIs('workflow-management.errors') ? 'active' : '' }}">
                                <a href="{{ route('workflow-management.errors') }}">
                                    <span>Workflows with Errors</span>
                                    @php
                                        // Use cache to avoid performance issues - cache for 5 minutes
                                        $errorCount = \Cache::remember('workflow_errors_count', 300, function () {
                                            try {
                                                return \App\Models\Workflow::whereHas('workflowHistory', function ($q) {
                                                    $q->where('status', 0)->where(function ($query) {
                                                        $query
                                                            ->whereDoesntHave('attendedBy')
                                                            ->orWhereHas('attendedBy', function ($userQuery) {
                                                                $userQuery->where('status', '!=', 'active');
                                                            });
                                                    });
                                                })->count() +
                                                    \App\Models\Clearance_work_flow::whereHas('histories', function (
                                                        $q,
                                                    ) {
                                                        $q->where('status', 0)->where(function ($query) {
                                                            $query
                                                                ->whereDoesntHave('attendedBy')
                                                                ->orWhereHas('attendedBy', function ($userQuery) {
                                                                    $userQuery->where('status', '!=', 'active');
                                                                });
                                                        });
                                                    })->count();
                                            } catch (\Exception $e) {
                                                return 0;
                                            }
                                        });
                                    @endphp
                                    @if ($errorCount > 0)
                                        <span
                                            class="badge badge-pill badge-danger badge-blink ms-2">{{ $errorCount }}</span>
                                    @endif
                                </a>
                            </li>
                        </ul>
                    </li>
                @endcan

                {{-- System Settings --}}
                @php
                    $isSystemSettingsActive =
                        request()->routeIs('settings.email') ||
                        request()->routeIs('settings.maintenance') ||
                        request()->routeIs('settings.deadlines') ||
                        request()->routeIs('settings.jobs') ||
                        request()->routeIs('tariff-categories.*') ||
                        request()->routeIs('service-categories.*') ||
                        request()->routeIs('external-system-links.*') ||
                        request()->routeIs('procurements.contracts.notification-management');
                @endphp
                @can('view settings')
                    <li class="treeview {{ $isSystemSettingsActive ? 'active' : '' }}">
                        <a href="#">
                            <i class="fas fa-cog"></i>
                            <span>System Settings</span>
                            <span class="menu-arrow"></span>
                        </a>
                        <ul style="{{ $isSystemSettingsActive ? 'display: block;' : '' }}">
                            @can('view logs')
                                <li>
                                    <a href="{{ route('settings.email') }}"
                                        class="{{ request()->routeIs('settings.email') ? 'active' : '' }}">
                                        <span>Email</span>
                                    </a>
                                </li>
                            @endcan
                            @can('manage maintenance mode')
                                <li>
                                    <a href="{{ route('settings.maintenance') }}"
                                        class="{{ request()->routeIs('settings.maintenance') ? 'active' : '' }}">
                                        <span>Maintenance Mode</span>
                                    </a>
                                </li>
                            @endcan
                            @can('view settings')
                                <li>
                                    <a href="{{ route('settings.deadlines') }}"
                                        class="{{ request()->routeIs('settings.deadlines') ? 'active' : '' }}">
                                        <span>Submission Deadlines</span>
                                    </a>
                                </li>
                                <li>
                                    <a href="{{ route('settings.jobs') }}"
                                        class="{{ request()->routeIs('settings.jobs') ? 'active' : '' }}">
                                        <span>Cron & Queue Jobs</span>
                                    </a>
                                </li>
                            @endcan
                            @can('view contracts')
                                <li>
                                    <a href="{{ route('procurements.contracts.notification-management') }}"
                                        class="{{ request()->routeIs('procurements.contracts.notification-management') ? 'active' : '' }}">
                                        <span>Contract Notifications</span>
                                    </a>
                                </li>
                            @endcan
                            @can('manage users')
                                <li>
                                    <a href="{{ route('external-system-links.index') }}"
                                        class="{{ request()->routeIs('external-system-links.*') ? 'active' : '' }}">
                                        <span>External System Links</span>
                                    </a>
                                </li>
                            @endcan
                            @can('manage change request categories')
                                <li>
                                    <a href="{{ route('tariff-categories.index') }}"
                                        class="{{ request()->routeIs('tariff-categories.*') ? 'active' : '' }}">
                                        <span>Tariff Categories</span>
                                    </a>
                                </li>
                                <li>
                                    <a href="{{ route('service-categories.index') }}"
                                        class="{{ request()->routeIs('service-categories.*') ? 'active' : '' }}">
                                        <span>Service Categories</span>
                                    </a>
                                </li>
                            @endcan
                        </ul>
                    </li>
                @endcan


                {{-- Spacer menu item to ensure all menus remain visible when scrolling --}}
                <li class="sidebar-spacer" style="height: 30px; pointer-events: none; opacity: 0;">
                    <a href="javascript:void(0);" style="cursor: default;">
                        <i class="fas fa-circle" style="opacity: 0;"></i>
                        <span style="opacity: 0;">Spacer</span>
                    </a>
                </li>
            </ul>
        </div>
    </div>
</div>
