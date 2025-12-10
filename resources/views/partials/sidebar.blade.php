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

                {{-- announcements --}}
                @can('view announcements')
                    <li class="{{ request()->is('announcements*') ? 'active' : '' }}">
                        <a href="{{ route('announcements.index') }}">
                            <i class="fas fa-bullhorn"></i>
                            <span>Notice Board</span>
                        </a>
                    </li>
                @endcan
                {{-- <li class="{{ request()->routeIs('education.details') ? 'active' : '' }}">
                    <a href="{{ route('education.details') }}">
                        <i class="fas fa-graduation-cap"></i> <span>Education Details</span>
                    </a>
                </li> --}}


                @can('view policies')
                    <li
                        class="treeview
                    {{ request()->routeIs('policies.index') || request()->routeIs('sops.index') || request()->routeIs('policies.create') || request()->routeIs('sops.create') || request()->routeIs('policies.edit') || request()->routeIs('sops.edit') ? 'active' : '' }}">
                        <a href="#">
                            <i class="fas fa-file-invoice"></i>
                            <span>Policies and SoPs</span>
                            <span class="menu-arrow"></span>
                        </a>
                        <ul
                            style="{{ request()->routeIs('policies.index') || request()->routeIs('sops.index') || request()->routeIs('policies.create') || request()->routeIs('sops.create') || request()->routeIs('policies.edit') || request()->routeIs('sops.edit') ? 'display: block;' : '' }}">
                            @can('view policies')
                                <li>
                                    <a href="{{ route('policies.index') }}"
                                        class="{{ request()->routeIs('policies.index') ? 'active' : '' }}">
                                        <span>Policies</span>
                                    </a>
                                </li>
                            @endcan
                            @can('view sops')
                                <li>
                                    <a href="{{ route('sops.index') }}"
                                        class="{{ request()->routeIs('sops.index') ? 'active' : '' }}">
                                        <span>SoPs</span>
                                    </a>
                                </li>
                            @endcan
                        </ul>
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
                        'requisitions.view',
                    );
                    // Count general requests (excluding locum, oncall, clearance, requisitions)
                    // Finance Officers should not see notification badge
                    $generalRequestCount = 0;
                    if (!Auth::user()->hasRole('finance officer')) {
                        $generalRequestCount = DB::table('work_flow_histories')
                            ->join('workflows', 'work_flow_histories.work_flow_id', '=', 'workflows.id')
                            ->where('work_flow_histories.attended_by', Auth::id())
                            ->where('work_flow_histories.status', 0)
                            ->whereNull('locum_agreement_status')
                            ->whereNull('locum_request_status')
                            ->whereNull('on_call_request_status')
                            ->whereNull('workflows.requisition_id')
                            ->count();
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

                    $pendingOnCallCount = DB::table('work_flow_histories')
                        ->join('workflows', 'work_flow_histories.work_flow_id', '=', 'workflows.id')
                        ->where('work_flow_histories.attended_by', Auth::id())
                        ->where('work_flow_histories.status', 0) // pending step, regardless of code
                        ->whereNotNull('workflows.on_call_request_id')
                        ->count();

                    $pendingRequisitionCount = DB::table('work_flow_histories')
                        ->join('workflows', 'work_flow_histories.work_flow_id', '=', 'workflows.id')
                        ->where('work_flow_histories.attended_by', Auth::id())
                        ->whereIn('work_flow_histories.requisition_status', [0, 1]) // Pending or Under Review
                        ->whereNotNull('workflows.requisition_id')
                        ->where('workflows.work_flow_completed', 0)
                        ->count();

                    $user = Auth::user();
                    $isApprover = $user && $user->hasAnyRole(['hr', 'line-manager', 'in-charge']);
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
                                <li>
                                    <a href="{{ route('requestapprove.index') }}"
                                        class="{{ request()->routeIs('requestapprove.index') ? 'active' : '' }}">
                                        <span>General Requests</span>
                                        @if ($pendingCount > 0)
                                            <span class="badge badge-pill badge-primary badge-blink ms-2">
                                                {{ $pendingCount }}
                                            </span>
                                        @endif
                                    </a>
                                </li>
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
                                        @if ($pendingLocumCount > 0)
                                            <span class="badge badge-pill badge-primary badge-blink ms-2">
                                                {{ $pendingLocumCount }}
                                            </span>
                                        @endif
                                    </a>
                                </li>
                            @endcan
                            @can('approve oncall requests')
                                <li>
                                    <a href="{{ route('oncall_requests.view') }}"
                                        class="{{ request()->routeIs('oncall_requests.index', 'oncall_requests.view') ? 'active' : '' }}">
                                        <span>On Call Claims</span>
                                        @if ($pendingOnCallCount > 0)
                                            <span class="badge badge-pill badge-primary badge-blink ms-2">
                                                {{ $pendingOnCallCount }}
                                            </span>
                                        @endif
                                    </a>
                                </li>
                            @endcan
                            @can('approve requisitions')
                                <li>
                                    <a href="{{ route('requisitions.view') }}"
                                        class="{{ request()->routeIs('requisitions.view') ? 'active' : '' }}">
                                        <span>Recruitment Requisitions</span>
                                        @if ($pendingRequisitionCount > 0)
                                            <span class="badge badge-pill badge-primary badge-blink ms-2">
                                                {{ $pendingRequisitionCount }}
                                            </span>
                                        @endif
                                    </a>
                                </li>
                            @endcan
                        </ul>
                    </li>
                @endcan

                @can('Manage Category')
                    <li class="treeview">
                        <a href="#">
                            <i class="fas fa-tasks"></i>
                            <span>Manage Category</span>
                            <span class="menu-arrow"></span>
                        </a>
                        <ul
                            style="{{ request()->routeIs('department.index', 'nhif.index', 'hmis.index', 'remark.index', 'privilege.index', 'employment.index', 'job_titles.index', 'hec.index', 'division.index') ? 'display: block;' : '' }}">

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

                        </ul>
                    </li>
                @endcan
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

                @role('incharge|line-manager|hr|coo|cfo|cms|super-admin|platform-manager
                    ')
                    <li class="{{ request()->routeIs('biotime.index') ? 'active' : '' }}">
                        <a href="{{ route('biotime.index') }}">
                            <i class="fas fa-fingerprint"></i>
                            <span>Biometric Attendance</span>
                        </a>
                    </li>
                @endrole

                {{-- Procurements Module --}}
                @can('view procureents')
                    <li
                        class="submenu {{ request()->is('procurements/*') || request()->is('VendorContracts*') || request()->is('vendors*') || request()->is('division*') ? 'active' : '' }}">
                        <a href="#">
                            <i class="fas fa-shopping-cart"></i> <span>Procurements</span>
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
                            @endcan

                            @hasanyrole('procurement-officer|super-admin')
                                @can('view vendors')
                                    <li>
                                        <a href="{{ route('procurements.vendors.index') }}"
                                            class="{{ request()->routeIs('procurements.vendors.index') || (request()->routeIs('procurements.vendors.*') && !request()->routeIs('procurements.vendors.department-vendors') && !request()->routeIs('procurements.vendors.hec-department-vendors')) ? 'active' : '' }}">
                                            <i class="fas fa-list me-1"></i> All Vendors
                                        </a>
                                    </li>
                                @endcan
                            @endrole

                            @hasanyrole('line-manager|super-admin')
                                <li>
                                    <a href="{{ route('procurements.vendors.department-vendors') }}"
                                        class="{{ request()->routeIs('procurements.vendors.department-vendors') ? 'active' : '' }}">
                                        Vendors
                                    </a>
                                </li>
                            @endrole

                            @hasanyrole('coo|cfo|cms|crhdo|super-admin')
                                <li>
                                    <a href="{{ route('procurements.vendors.hec-department-vendors') }}"
                                        class="{{ request()->routeIs('procurements.vendors.hec-department-vendors') ? 'active' : '' }}">
                                        Vendors
                                    </a>
                                </li>
                            @endhasanyrole

                        </ul>
                    </li>
                @endcan

                {{-- HEC Contracts Module --}}
                @hasanyrole('Admin-Secretary|coo|cfo|cms|crhdo|super-admin')
                    <li class="{{ request()->routeIs('hec-contracts.*') ? 'active' : '' }}">
                        <a href="{{ route('hec-contracts.index') }}">
                            <i class="fas fa-file-contract"></i> <span>HEC Contracts</span>
                        </a>
                    </li>
                @endhasanyrole

                {{-- Legacy Business Contracts (deprecated - for backward compatibility) --}}
                {{-- @can('view procureents')
                    <li
                        class="submenu {{ request()->is('VendorContracts*') && !request()->is('procurements/*') ? 'active' : '' }}">
                        <a href="#">
                            <i class="fas fa-briefcase"></i> <span>Business Contracts (Legacy)</span>
                            <span class="menu-arrow"></span>
                        </a>
                        <ul
                            style="{{ request()->is('VendorContracts*') && !request()->is('procurements/*') ? 'display: block;' : '' }}">

                            @can('view contracts')
                                <li>
                                    <a href="{{ route('vendorContract.index') }}"
                                        class="{{ request()->routeIs('vendorContract.index') ? 'active' : '' }}">
                                        Contracts
                                    </a>
                                </li>
                            @endcan

                            @can('view vendors')
                                <li>
                                    <a href="{{ route('vendors.index') }}"
                                        class="{{ request()->routeIs('vendors.index') ? 'active' : '' }}">
                                        Vendors
                                    </a>
                                </li>
                            @endcan

                        </ul>
                    </li>
                @endcan --}}



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
                        request()->routeIs('settings.requisition-flow') ||
                        request()->routeIs('tariff-categories.*') ||
                        request()->routeIs('service-categories.*');
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
                            @endcan
                            @can('approve requests')
                                <li>
                                    <a href="{{ route('settings.requisition-flow') }}"
                                        class="{{ request()->routeIs('settings.requisition-flow') ? 'active' : '' }}">
                                        <span>Requisition Flow</span>
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
