@extends('layouts.template')

@section('breadcrumb')
    @include('sweetalert::alert')
    @include('includes.loader')

<div class="page-wrapper" style="visibility:hidden">
    <div class="content container-fluid">

        {{-- Page Header --}}
        <div class="page-header mb-3">
            <div class="row">
                <div class="col-sm-12">
                    <div class="page-sub-header d-flex justify-content-between align-items-center">
                        <h3 class="page-title mb-0">
                            <i class="fas fa-clipboard-check me-2" style="color:#007A33;"></i>Employee Clearance
                        </h3>
                        <div class="d-flex gap-2">
                            @if(auth()->user()->hasRole('super-admin') || auth()->user()->hasRole('coo'))
                            <a href="{{ route('certificate-of-service.index') }}" class="btn btn-outline-primary btn-sm">
                                <i class="fas fa-file-certificate me-1"></i> Certificate of Service
                            </a>
                            @endif
                            @if(($canManageAllClearances ?? false) || auth()->user()->can('ict_acces_report'))
                            <button type="button" class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#clearanceExportModal">
                                <i class="fas fa-file-excel me-1"></i> Export Report
                            </button>
                            @endif
                            @if($isLineManager ?? false)
                            <a href="{{ route('clearance.create') }}" class="btn btn-sm btn-outline-success">
                                <i class="fas fa-user-plus me-1"></i>New Clearance for Staff
                            </a>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- ── Employee's own clearance status ─────────────────────────────── --}}
        @if ($clearance && $clearance->userId === Auth::id())
        <div class="card shadow-sm mb-3" style="border-left:4px solid #007A33;">
            <div class="card-body py-3">
                <div class="d-flex align-items-start justify-content-between flex-wrap gap-3">
                    <div>
                        <h6 class="fw-semibold mb-2"><i class="fas fa-info-circle me-2" style="color:#007A33;"></i>Your Clearance Status</h6>
                        <div class="d-flex align-items-center gap-2 flex-wrap mb-2">
                            @if ($clearance->status === 'rejected')
                                <span class="badge bg-danger px-3 py-2"><i class="fas fa-times-circle me-1"></i>Rejected</span>
                            @elseif($workflow && $workflow->work_flow_completed)
                                <span class="badge bg-success px-3 py-2"><i class="fas fa-check-circle me-1"></i>Completed</span>
                            @elseif($workflow)
                                <span class="badge bg-warning text-dark px-3 py-2"><i class="fas fa-clock me-1"></i>In Progress</span>
                            @else
                                <span class="badge bg-secondary px-3 py-2"><i class="fas fa-hourglass-half me-1"></i>Submitted</span>
                            @endif
                            @if ($currentStep && $clearance->status !== 'rejected')
                                <span class="text-muted small">Pending with: <strong>{{ $currentStep }}</strong>
                                    @if(in_array($currentStep,['IT Officer','HR Officer']))
                                        <small class="text-muted">(any {{ $currentStep }})</small>
                                    @elseif($currentApprover)
                                        — {{ trim(($currentApprover->fname ?? '').' '.($currentApprover->lname ?? '')) }}
                                    @endif
                                </span>
                            @endif
                        </div>
                        @if($clearance->status !== 'rejected' && !($workflow && $workflow->work_flow_completed))
                        @php $steps = ['Line Manager','Finance Officer','IT Officer','HR Officer']; $stepIndex = array_search($currentStep, $steps); @endphp
                        <div class="d-flex gap-1 flex-wrap">
                            @foreach($steps as $i => $s)
                                @php $done = $stepIndex !== false ? $i < $stepIndex : false; $active = $currentStep === $s; @endphp
                                <span class="badge {{ $done ? 'bg-success' : ($active ? 'bg-warning text-dark' : 'bg-light text-muted border') }}">
                                    @if($done)<i class="fas fa-check me-1"></i>@elseif($active)<i class="fas fa-clock me-1"></i>@else<i class="fas fa-circle me-1" style="font-size:.45rem;vertical-align:middle"></i>@endif{{ $s }}
                                </span>
                            @endforeach
                        </div>
                        @endif
                        @if ($clearance->status === 'rejected' && $clearance->rejection_reason)
                        <div class="alert alert-danger mt-2 mb-0 py-2 px-3">
                            <small><strong>Reason:</strong> {{ $clearance->rejection_reason }}</small>
                        </div>
                        @endif
                    </div>
                    <div class="text-end">
                        <small class="text-muted d-block">Submitted {{ \Carbon\Carbon::parse($clearance->created_at)->format('d M Y') }}</small>
                        @if ($workflow && $workflow->work_flow_completed)
                        <small class="text-success d-block">Completed {{ \Carbon\Carbon::parse($workflow->updated_at)->format('d M Y') }}</small>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        {{-- Key details strip --}}
        <div class="card shadow-sm mb-3">
            <div class="card-body py-2 px-3">
                <div class="row g-2">
                    <div class="col-md-4">
                        <small class="text-muted d-block"><i class="fas fa-calendar-alt me-1"></i>Date of Hire</small>
                        <strong class="small">{{ $clearance->date_of_hire ? \Carbon\Carbon::parse($clearance->date_of_hire)->format('d F Y') : 'N/A' }}</strong>
                    </div>
                    <div class="col-md-4">
                        <small class="text-muted d-block"><i class="fas fa-calendar-times me-1"></i>Last Working Day</small>
                        <strong class="small">{{ $clearance->last_working_day ? \Carbon\Carbon::parse($clearance->last_working_day)->format('d F Y') : 'N/A' }}</strong>
                    </div>
                    <div class="col-md-4">
                        <small class="text-muted d-block"><i class="fas fa-sign-out-alt me-1"></i>Reason for Leaving</small>
                        <strong class="small">{{ $clearance->reason_for_leaving ?? 'N/A' }}
                            @if ($clearance->reason_for_leaving === 'Other' && $clearance->reason_for_leaving_other)
                                <span class="text-muted"> — {{ $clearance->reason_for_leaving_other }}</span>
                            @endif
                        </strong>
                    </div>
                </div>
            </div>
        </div>

        @if ($clearance->status === 'rejected' && Auth::user()->hasRole('requester'))
        <div class="alert alert-info d-flex align-items-center gap-3 mb-3">
            <i class="fas fa-info-circle fa-lg"></i>
            <div>Your clearance form was rejected. <a href="{{ route('clearance.create') }}" class="btn btn-sm btn-primary ms-2"><i class="fas fa-plus me-1"></i>Submit New Form</a></div>
        </div>
        @endif

        @else
            @php
                $isApprover = Auth::user()->hasAnyRole(['hr','line-manager','finance officer','it','coo','cfo','cms']) || Auth::user()->can('ict_acces_report');
            @endphp
            @if (!$isApprover)
            <div class="card shadow-sm mb-3">
                <div class="card-body text-center py-5">
                    <i class="fas fa-file-alt fa-4x text-muted mb-3"></i>
                    <h4>No Clearance Form Found</h4>
                    <p class="text-muted">You haven't submitted a clearance form yet.</p>
                    @if (Auth::user()->hasRole('requester'))
                        <a href="{{ route('clearance.create') }}" class="btn btn-primary mt-2"><i class="fas fa-plus me-2"></i>Create Clearance Form</a>
                    @elseif (Auth::user()->hasRole('line-manager'))
                        <a href="{{ route('clearance.create') }}" class="btn btn-primary mt-2"><i class="fas fa-user-plus me-2"></i>Request Clearance for Staff</a>
                    @else
                        <div class="alert alert-warning mt-3 d-inline-block"><i class="fas fa-exclamation-triangle me-2"></i>Only requesters or line managers can create clearance forms.</div>
                    @endif
                </div>
            </div>
            @endif
        @endif

        {{-- ── Approver Section ─────────────────────────────────────────────── --}}
        @php
            $isAnyApprover = ($isLineManager && isset($staffClearances) && $staffClearances->count() > 0)
                || ($isHR && isset($hrClearances) && $hrClearances->count() > 0)
                || ($isFinanceOfficer && isset($financeClearances) && $financeClearances->count() > 0)
                || ($isITOfficer && isset($itClearances) && $itClearances->count() > 0);
            $hasManagementClearances = ($canManageAllClearances ?? false) && isset($allClearancesForManagement) && $allClearancesForManagement->count() > 0;
            $hasFilterableClearances = $isAnyApprover || $hasManagementClearances;
        @endphp
        @if($hasFilterableClearances)

        {{-- Filter toolbar --}}
        <div class="clearance-filter-panel mb-3">
            <div class="clearance-filter-header">
                <div>
                    <h6 class="mb-0"><i class="fas fa-filter me-2"></i>Filter Clearances</h6>
                </div>
            </div>
            <div class="clearance-filter-body">
                <div class="clearance-filter-grid">
                    <div class="clearance-filter-field clearance-filter-status">
                        <label class="filter-label"><i class="fas fa-circle-check me-1"></i>Status</label>
                        <select id="clearanceStatusFilter" class="form-select form-select-sm">
                            <option value="all">All Statuses</option>
                            <option value="pending">Pending</option>
                            <option value="completed">Completed</option>
                            <option value="rejected">Rejected</option>
                        </select>
                    </div>
                    <div class="clearance-filter-field clearance-filter-department">
                        <label class="filter-label"><i class="fas fa-sitemap me-1"></i>Department</label>
                        <select id="clearanceDepartmentFilter" class="form-select form-select-sm">
                            <option value="">All Departments</option>
                        </select>
                    </div>
                    <div class="clearance-filter-field clearance-filter-step">
                        <label class="filter-label"><i class="fas fa-route me-1"></i>Current Step</label>
                        <select id="clearanceStepFilter" class="form-select form-select-sm">
                            <option value="">All Steps</option>
                        </select>
                    </div>
                    <div class="clearance-filter-field clearance-filter-date">
                        <label class="filter-label"><i class="fas fa-calendar-day me-1"></i>From</label>
                        <input type="date" id="clearanceDateFrom" class="form-control form-control-sm">
                    </div>
                    <div class="clearance-filter-field clearance-filter-date">
                        <label class="filter-label"><i class="fas fa-calendar-check me-1"></i>To</label>
                        <input type="date" id="clearanceDateTo" class="form-control form-control-sm">
                    </div>
                    <div class="clearance-filter-field clearance-filter-reset">
                        <label class="filter-label d-none d-lg-block">&nbsp;</label>
                        <button type="button" id="clearanceResetFilters" class="btn btn-outline-secondary btn-sm w-100">
                            <i class="fas fa-rotate-left me-1"></i>Reset
                        </button>
                    </div>
                </div>
            </div>
        </div>

        {{-- Clearance analytics --}}
        <div class="clearance-analytics-grid mb-3" id="clearanceAnalyticsPanel">
            <div class="clearance-analytics-card">
                <div class="clearance-analytics-title"><i class="fas fa-chart-column me-2"></i>Monthly Submissions</div>
                <div class="clearance-chart-wrap"><canvas id="clearanceMonthlyChart"></canvas></div>
            </div>
            <div class="clearance-analytics-card">
                <div class="clearance-analytics-title"><i class="fas fa-building me-2"></i>Top Departments</div>
                <div class="clearance-chart-wrap"><canvas id="clearanceDepartmentChart"></canvas></div>
            </div>
        </div>

        @endif

        @if($isAnyApprover)

        {{-- Approver tabs --}}
        <div class="card shadow-sm">
            <div class="card-header bg-light py-2 d-flex justify-content-between align-items-center">
                <h6 class="mb-0"><i class="fas fa-table me-2 text-muted"></i>Clearance Approvals</h6>
            </div>
            <div class="card-body p-3">
                @php
                    $tabs = [];
                    if ($isLineManager && isset($staffClearances) && $staffClearances->count() > 0)
                        $tabs[] = ['id'=>'lm','label'=>'Line Manager','icon'=>'user-tie','count'=>$staffClearances->count(),'items'=>$staffClearances];
                    if ($isFinanceOfficer && isset($financeClearances) && $financeClearances->count() > 0)
                        $tabs[] = ['id'=>'finance','label'=>'Finance Officer','icon'=>'coins','count'=>$financeClearances->count(),'items'=>$financeClearances];
                    if ($isITOfficer && isset($itClearances) && $itClearances->count() > 0)
                        $tabs[] = ['id'=>'it','label'=>'IT Officer','icon'=>'laptop','count'=>$itClearances->count(),'items'=>$itClearances];
                    if ($isHR && isset($hrClearances) && $hrClearances->count() > 0)
                        $tabs[] = ['id'=>'hr','label'=>'HR Officer','icon'=>'users','count'=>$hrClearances->count(),'items'=>$hrClearances];
                @endphp
                @if(count($tabs) > 1)
                <ul class="nav nav-tabs mb-3" id="approvalTabs">
                    @foreach($tabs as $ti => $tab)
                    <li class="nav-item">
                        <button class="nav-link {{ $ti === 0 ? 'active' : '' }}" data-bs-toggle="tab" data-bs-target="#tab-{{ $tab['id'] }}">
                            <i class="fas fa-{{ $tab['icon'] }} me-1"></i>{{ $tab['label'] }}
                            <span class="badge bg-secondary ms-1">{{ $tab['count'] }}</span>
                        </button>
                    </li>
                    @endforeach
                </ul>
                @endif

                <div class="tab-content">
                    @foreach($tabs as $ti => $tab)
                    <div class="tab-pane fade {{ $ti === 0 ? 'show active' : '' }}" id="tab-{{ $tab['id'] }}">
                        <div class="table-responsive mt-2">
                            <table class="table table-hover table-striped align-middle" id="clearanceTable_{{ $tab['id'] }}" style="width:100%">
                                <thead class="table-light">
                                    <tr>
                                        <th class="no-sort" style="width:2rem">#</th>
                                        <th>Employee</th>
                                        <th>Hired</th>
                                        <th>Last Day</th>
                                        <th>Current Step</th>
                                        <th>Status</th>
                                        <th>Submitted</th>
                                        <th class="text-center no-sort">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($tab['items'] as $idx => $cf)
                                        @php
                                            $wf = $cf->workflow;
                                            $pendingHistory = $wf
                                                ? \App\Models\Clearance_work_flow_history::where('work_flow_id',$wf->id)->where('status',0)->orderBy('created_at','desc')->first()
                                                : null;
                                            $pendingStep = $pendingHistory
                                                ? $pendingHistory->step_name
                                                : ($wf ? ($wf->work_flow_completed ? 'Completed' : 'Pending') : 'N/A');
                                            $tabStepMap = ['lm'=>'Line Manager','finance'=>'Finance Officer','it'=>'IT Officer','hr'=>'HR Officer'];
                                            $myStep = $tabStepMap[$tab['id']] ?? null;
                                            $myHistory = null;
                                            if ($wf && $myStep) {
                                                $myHistory = \App\Models\Clearance_work_flow_history::where('work_flow_id',$wf->id)
                                                    ->where('step_name',$myStep)->where('attended_by', Auth::id())->orderBy('id','desc')->first();
                                                if (!$myHistory && in_array($myStep,['IT Officer','HR Officer'])) {
                                                    $myHistory = \App\Models\Clearance_work_flow_history::where('work_flow_id',$wf->id)
                                                        ->where('step_name',$myStep)->orderBy('id','desc')->first();
                                                }
                                            }
                                            $myHistoryStatus = $myHistory ? (int)$myHistory->status : null;
                                            $isCompleted = $wf && $wf->work_flow_completed;
                                            $isRejected  = $cf->status === 'rejected';
                                            $isPendingMe = !$isCompleted && !$isRejected && $myHistoryStatus === 0 && $pendingStep === $myStep;
                                            $iDoneMyStep = $myHistoryStatus === 1;
                                            // Row status for filtering
                                            $rowStatus = $isRejected ? 'rejected' : ($isCompleted ? 'completed' : 'pending');
                                        @endphp
                                        <tr data-clearance-id="{{ $cf->id }}" data-status="{{ $rowStatus }}" data-department="{{ $cf->user->department->dept_name ?? 'Unassigned' }}" data-step="{{ $pendingStep }}" data-submitted="{{ \Carbon\Carbon::parse($cf->created_at)->format('Y-m-d') }}">
                                            <td class="text-muted small">{{ $idx + 1 }}</td>
                                            <td>
                                                <strong>{{ trim(($cf->user->fname ?? '') . ' ' . ($cf->user->lname ?? '')) }}</strong>
                                                <br><small class="text-muted">{{ $cf->user->email ?? '' }}</small>
                                                <br><small class="text-muted"><i class="fas fa-sitemap me-1"></i>{{ $cf->user->department->dept_name ?? 'Unassigned' }}</small>
                                            </td>
                                            <td class="small" data-order="{{ $cf->date_of_hire ? \Carbon\Carbon::parse($cf->date_of_hire)->format('Y-m-d') : '' }}">
                                                {{ $cf->date_of_hire ? \Carbon\Carbon::parse($cf->date_of_hire)->format('d M Y') : '—' }}
                                            </td>
                                            <td class="small" data-order="{{ $cf->last_working_day ? \Carbon\Carbon::parse($cf->last_working_day)->format('Y-m-d') : '' }}">
                                                {{ $cf->last_working_day ? \Carbon\Carbon::parse($cf->last_working_day)->format('d M Y') : '—' }}
                                            </td>
                                            <td>
                                                @if($isCompleted)
                                                    <span class="text-success small fw-semibold"><i class="fas fa-check-circle me-1"></i>All Steps Done</span>
                                                @elseif($isRejected)
                                                    <span class="text-danger small fw-semibold"><i class="fas fa-times-circle me-1"></i>Rejected</span>
                                                @else
                                                    <span class="text-dark small fw-semibold">{{ $pendingStep }}</span>
                                                    @if(in_array($pendingStep,['IT Officer','HR Officer']))
                                                        <br><small class="text-muted">Any {{ explode(' ',explode(' Officer',$pendingStep)[0])[0] }} Officer</small>
                                                    @endif
                                                @endif
                                            </td>
                                            <td>
                                                @if($isRejected)
                                                    <span class="badge bg-danger"><i class="fas fa-times me-1"></i>Rejected</span>
                                                @elseif($isCompleted)
                                                    <span class="badge bg-success"><i class="fas fa-check me-1"></i>Completed</span>
                                                @elseif($isPendingMe)
                                                    <span class="badge" style="background:#e67e22;color:#fff;"><i class="fas fa-exclamation-circle me-1"></i>Action Required</span>
                                                @elseif($iDoneMyStep)
                                                    <span class="badge bg-success bg-opacity-75"><i class="fas fa-check me-1"></i>Approved by me</span>
                                                    @if($pendingStep !== 'Completed')
                                                        <br><small class="text-muted">Pending: {{ $pendingStep }}</small>
                                                    @endif
                                                @else
                                                    <span class="badge bg-light text-muted border">In Progress</span>
                                                @endif
                                            </td>
                                            <td class="small text-muted" data-order="{{ \Carbon\Carbon::parse($cf->created_at)->format('Y-m-d') }}">
                                                {{ \Carbon\Carbon::parse($cf->created_at)->format('d M Y') }}
                                            </td>
                                            <td class="text-center">
                                                <a href="{{ route('exit_forms.show', $cf->id) }}"
                                                   class="btn btn-sm {{ $isPendingMe ? 'btn-warning' : 'btn-outline-secondary' }}">
                                                    <i class="fas fa-eye me-1"></i>{{ $isPendingMe ? 'Review' : 'View' }}
                                                </a>
                                                                @if((Auth::user()->hasRole('hr') || Auth::user()->hasRole('super-admin') || Auth::user()->can('edit users')) && $cf->user)
                                                <a href="{{ route('user.edit', $cf->user->id) }}"
                                                   class="btn btn-sm btn-outline-success" target="_blank" title="Edit staff joining, starting, and contract dates">
                                                    <i class="fas fa-user-edit me-1"></i>Staff
                                                </a>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>

        @elseif($isLineManager ?? false)
        <div class="card shadow-sm mt-3">
            <div class="card-body text-center py-4">
                <i class="fas fa-check-circle fa-2x text-success mb-2"></i>
                <p class="text-muted mb-3">No clearance forms pending your approval.</p>
                <a href="{{ route('clearance.create') }}" class="btn btn-outline-primary btn-sm">
                    <i class="fas fa-user-plus me-1"></i>Request Clearance for Staff
                </a>
            </div>
        </div>
        @endif

        {{-- ── Management Monitoring Dashboard ─────────────────────────────── --}}
        @if(($canManageAllClearances ?? false) && isset($allClearancesForManagement) && $allClearancesForManagement->count() > 0)
        @php
            $steps = ['Line Manager','Finance Officer','IT Officer','HR Officer'];
            $managementLabel  = ($isHR ?? false) && !($isCOO ?? false) ? 'HR Monitoring' : 'COO Management';
        @endphp
        <div class="card shadow-sm mt-4">
            <div class="card-header bg-white border-bottom py-2">
                <h6 class="mb-0"><i class="fas fa-chart-line me-2" style="color:#007A33;"></i>All Clearance Forms — {{ $managementLabel }}</h6>
            </div>
            <div class="card-body p-3">
                <div class="table-responsive">
                    <table class="table table-hover table-striped align-middle" id="hrMonitorTable" style="width:100%">
                        <thead class="table-light">
                            <tr>
                                <th class="no-sort" style="width:2rem">#</th>
                                <th>Employee</th>
                                <th class="no-sort">Approval Progress</th>
                                <th>Submitted</th>
                                <th class="text-center no-sort">Certificate</th>
                                <th class="text-center no-sort">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($allClearancesForManagement as $mi => $mf)
                            @php
                                $mwf = $mf->workflow;
                                $mPending = $mwf ? \App\Models\Clearance_work_flow_history::where('work_flow_id',$mwf->id)->where('status',0)->orderBy('created_at','desc')->first() : null;
                                $mCurrentStep = $mPending ? $mPending->step_name : ($mwf && $mwf->work_flow_completed ? 'Completed' : 'Submitted');
                                $stepStatus = [];
                                if ($mwf) {
                                    foreach ($steps as $s) {
                                        $h = \App\Models\Clearance_work_flow_history::where('work_flow_id',$mwf->id)->where('step_name',$s)->orderBy('id','desc')->first();
                                        if (!$h) $stepStatus[$s] = 'none';
                                        elseif ($h->status == 1) $stepStatus[$s] = 'done';
                                        elseif ($h->status == 0) $stepStatus[$s] = 'pending';
                                        else $stepStatus[$s] = 'none';
                                    }
                                }
                                $mRowStatus = ($mf->status === 'rejected') ? 'rejected' : (($mwf && $mwf->work_flow_completed) ? 'completed' : 'pending');
                            @endphp
                            <tr data-clearance-id="{{ $mf->id }}" data-status="{{ $mRowStatus }}" data-department="{{ $mf->user->department->dept_name ?? 'Unassigned' }}" data-step="{{ $mCurrentStep }}" data-submitted="{{ \Carbon\Carbon::parse($mf->created_at)->format('Y-m-d') }}">
                                <td class="text-muted small">{{ $mi + 1 }}</td>
                                <td>
                                    <strong>{{ trim(($mf->user->fname ?? '') . ' ' . ($mf->user->lname ?? '')) }}</strong>
                                    <br><small class="text-muted">{{ $mf->user->email ?? '' }}</small>
                                    <br><small class="text-muted"><i class="fas fa-sitemap me-1"></i>{{ $mf->user->department->dept_name ?? 'Unassigned' }}</small>
                                </td>
                                <td style="min-width:200px">
                                    <div class="d-flex gap-1 flex-wrap">
                                    @foreach($steps as $s)
                                        @php $ss = $stepStatus[$s] ?? 'none'; @endphp
                                        <span class="badge {{ $ss==='done' ? 'bg-success' : ($ss==='pending' ? 'bg-warning text-dark' : 'bg-light text-muted border') }}" style="font-size:.68rem">
                                            @if($ss==='done')<i class="fas fa-check"></i>@elseif($ss==='pending')<i class="fas fa-clock"></i>@else<i class="fas fa-minus"></i>@endif
                                            {{ explode(' ', $s)[0] }}
                                        </span>
                                    @endforeach
                                    </div>
                                </td>
                                <td class="small text-muted" data-order="{{ \Carbon\Carbon::parse($mf->created_at)->format('Y-m-d') }}">
                                    {{ \Carbon\Carbon::parse($mf->created_at)->format('d M Y') }}
                                </td>
                                {{-- Certificate of Service column --}}
                                <td class="text-center" style="min-width:165px;">
                                    @if($mwf && $mwf->work_flow_completed)
                                        @php $staffUserId = $mf->userId; @endphp
                                        @if(isset($cosExistsUserIds[$staffUserId]))
                                            <span class="badge bg-success bg-opacity-75" style="font-size:.72rem;">
                                                <i class="fas fa-certificate me-1"></i>Certificate Created
                                            </span>
                                        @elseif($mf->cos_notified_at)
                                            <div>
                                                <span class="badge bg-info text-dark" style="font-size:.7rem;">
                                                    <i class="fas fa-envelope-open-text me-1"></i>COO Notified
                                                </span>
                                                <div class="text-muted" style="font-size:.68rem;margin-top:2px;">
                                                    {{ \Carbon\Carbon::parse($mf->cos_notified_at)->format('d M Y') }}
                                                </div>
                                                @if($canManageAllClearances ?? false)
                                                <form method="POST" action="{{ route('clearance.notify-cos', $mf->id) }}" class="mt-1"
                                                      onsubmit="return confirm('Resend COO notification for {{ trim(($mf->user->fname ?? '').' '.($mf->user->lname ?? '')) }}?')">
                                                    @csrf
                                                    <button type="submit" class="btn btn-outline-secondary btn-sm" style="font-size:.7rem;padding:.15rem .4rem;">
                                                        <i class="fas fa-redo me-1"></i>Resend
                                                    </button>
                                                </form>
                                                @endif
                                            </div>
                                        @else
                                            @if($canManageAllClearances ?? false)
                                            <form method="POST" action="{{ route('clearance.notify-cos', $mf->id) }}"
                                                  onsubmit="return confirm('Notify COO to create Certificate of Service for {{ trim(($mf->user->fname ?? '').' '.($mf->user->lname ?? '')) }}?\n\nAn email will be sent to the COO.')">
                                                @csrf
                                                <button type="submit" class="btn btn-success btn-sm" style="font-size:.75rem;white-space:nowrap;">
                                                    <i class="fas fa-paper-plane me-1"></i>Initialize Certificate
                                                </button>
                                            </form>
                                            @else
                                                <span class="text-muted small">—</span>
                                            @endif
                                        @endif
                                    @else
                                        <span class="text-muted small">—</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <div class="d-flex justify-content-center gap-1 flex-wrap">
                                        <a href="{{ route('exit_forms.show', $mf->id) }}" class="btn btn-sm btn-outline-secondary">
                                            <i class="fas fa-eye me-1"></i>View
                                        </a>
                                                    @if((Auth::user()->hasRole('hr') || Auth::user()->hasRole('super-admin') || Auth::user()->can('edit users')) && $mf->user)
                                        <a href="{{ route('user.edit', $mf->user->id) }}"
                                           class="btn btn-sm btn-outline-success" target="_blank" title="Edit staff joining, starting, and contract dates">
                                            <i class="fas fa-user-edit me-1"></i>Staff
                                        </a>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        @endif

    </div>
</div>
@endsection

@push('styles')
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" />
    <style>
        .page-wrapper { visibility: visible !important; }

        /* ── DataTables controls padding ── */
        .dataTables_wrapper .dataTables_length,
        .dataTables_wrapper .dataTables_filter,
        .dataTables_wrapper .dataTables_info,
        .dataTables_wrapper .dataTables_paginate {
            padding: 0.5rem 0.25rem;
        }
        .dataTables_wrapper .dataTables_filter input {
            border: 1px solid #dee2e6;
            border-radius: 6px;
            padding: 0.25rem 0.6rem;
            font-size: 0.85rem;
        }
        .dataTables_wrapper .dataTables_length select {
            border: 1px solid #dee2e6;
            border-radius: 6px;
            padding: 0.2rem 0.4rem;
            font-size: 0.85rem;
        }

        .no-sort { cursor: default !important; }
        .no-sort::after, .no-sort::before { display: none !important; }

        .clearance-filter-panel {
            background: #fff;
            border: 1px solid #e4e9ee;
            border-radius: 8px;
            box-shadow: 0 1px 5px rgba(15, 23, 42, 0.06);
            overflow: hidden;
        }
        .clearance-filter-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 0.75rem;
            padding: 0.75rem 0.9rem;
            background: #fbfcfd;
            border-bottom: 1px solid #e8edf1;
        }
        .clearance-filter-header h6 {
            color: #25313f;
            font-size: 0.9rem;
            font-weight: 700;
        }
        .clearance-filter-header small {
            display: block;
            margin-top: 0.1rem;
            font-size: 0.76rem;
        }
        .clearance-filter-body {
            padding: 0.85rem 0.9rem 0.95rem;
        }
        .clearance-filter-grid {
            display: grid;
            grid-template-columns: minmax(150px, 1fr) minmax(210px, 1.25fr) minmax(190px, 1.15fr) minmax(135px, 0.75fr) minmax(135px, 0.75fr) 92px;
            gap: 0.65rem;
            align-items: end;
        }
        .clearance-filter-field {
            min-width: 0;
        }
        .filter-label {
            display: block;
            margin-bottom: 0.3rem;
            color: #667085;
            font-size: 0.72rem;
            font-weight: 700;
            line-height: 1.1;
            text-transform: uppercase;
        }
        .clearance-filter-panel .form-control,
        .clearance-filter-panel .form-select {
            border-color: #d9e1e8;
            border-radius: 7px;
            color: #25313f;
            font-size: 0.84rem;
            min-height: 34px;
        }
        .clearance-filter-panel .form-control:focus,
        .clearance-filter-panel .form-select:focus {
            border-color: #198754;
            box-shadow: 0 0 0 0.15rem rgba(25, 135, 84, 0.13);
        }

        .clearance-analytics-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 0.9rem;
        }
        .clearance-analytics-card {
            background: #fff;
            border: 1px solid #e4e9ee;
            border-radius: 8px;
            padding: 0.9rem 1rem 1rem;
            box-shadow: 0 1px 5px rgba(15, 23, 42, 0.05);
            min-width: 0;
        }
        .clearance-analytics-title {
            color: #25313f;
            font-size: 0.9rem;
            font-weight: 700;
            margin-bottom: 0.75rem;
        }
        .clearance-analytics-title i {
            color: #007A33;
        }
        .clearance-chart-wrap {
            position: relative;
            height: 240px;
            width: 100%;
        }

        table.dataTable { border-collapse: collapse !important; width: 100% !important; }
        table.dataTable thead th {
            font-weight: 600;
            white-space: nowrap;
            background: #f8f9fa;
            font-size: 0.85rem;
            padding: 0.65rem 0.75rem;
        }
        table.dataTable tbody td {
            vertical-align: middle;
            font-size: 0.875rem;
            padding: 0.6rem 0.75rem;
        }
        table.dataTable tbody tr:hover { background-color: #f0f7f3 !important; }

        /* ── Cards ── */
        .card { border-radius: 10px; border: 1px solid #e9ecef; }
        .card-header {
            font-weight: 600;
            border-bottom: 1px solid #e9ecef;
            background-color: #f8f9fa !important;
        }
        .card.shadow-sm { box-shadow: 0 1px 4px rgba(0,0,0,.07) !important; }

        /* ── Nav tabs ── */
        .nav-tabs { border-bottom: 1px solid #dee2e6; }
        .nav-tabs .nav-link {
            font-size: 0.85rem;
            color: #6c757d;
            border-radius: 6px 6px 0 0;
            padding: 0.45rem 0.85rem;
        }
        .nav-tabs .nav-link.active {
            color: #007A33;
            font-weight: 600;
            border-color: #dee2e6 #dee2e6 #fff;
        }
        .nav-tabs .nav-link:hover:not(.active) { background: #f0f0f0; }

        .info-item { padding: 0.75rem 1rem; background: #f8f9fa; border-radius: 8px; border-left: 3px solid #007A33; }
        .info-item strong { color: #495057; display: block; margin-bottom: 0.25rem; }

        /* ── Page header ── */
        .page-title { font-size: 1.2rem; font-weight: 700; color: #343a40; }
        .page-header { padding-bottom: 0.5rem; border-bottom: 1px solid #f0f0f0; margin-bottom: 1.25rem !important; }

        .clearance-export-modal {
            border: 0;
            border-radius: 8px;
            box-shadow: 0 18px 42px rgba(15, 23, 42, 0.22);
            overflow: hidden;
        }
        .clearance-export-modal .modal-header {
            background: #fff;
            border-bottom: 1px solid #e7edf2;
            padding: 1rem 1.15rem;
        }
        .clearance-export-modal .modal-title {
            display: flex;
            align-items: center;
            gap: 0.7rem;
            color: #25313f;
            font-size: 1.05rem;
            font-weight: 700;
        }
        .clearance-export-icon {
            width: 36px;
            height: 36px;
            border-radius: 8px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: #198754;
            background: #edf8f1;
            flex: 0 0 36px;
        }
        .clearance-export-modal .modal-body {
            padding: 1.1rem 1.15rem;
            background: #fff;
        }
        .clearance-export-modal .modal-footer {
            background: #fbfcfd;
            border-top: 1px solid #e7edf2;
            padding: 0.85rem 1.15rem;
        }
        .clearance-export-modal .form-label {
            color: #667085;
            font-size: 0.72rem;
            font-weight: 700;
            margin-bottom: 0.35rem;
            text-transform: uppercase;
        }
        .clearance-export-modal .form-control,
        .clearance-export-modal .form-select {
            border-color: #d9e1e8;
            border-radius: 7px;
            color: #25313f;
            min-height: 36px;
        }
        .clearance-export-modal .form-control:focus,
        .clearance-export-modal .form-select:focus {
            border-color: #198754;
            box-shadow: 0 0 0 0.15rem rgba(25, 135, 84, 0.13);
        }
        .clearance-export-modal .btn {
            border-radius: 7px;
            font-weight: 600;
            min-height: 34px;
            padding-left: 0.85rem;
            padding-right: 0.85rem;
        }
        .clearance-export-modal .btn-close {
            opacity: 0.55;
        }
        .clearance-export-modal .btn-close:hover {
            opacity: 0.9;
        }

        @media (max-width: 767.98px) {
            .clearance-filter-header {
                align-items: flex-start;
                flex-direction: column;
            }
            .clearance-filter-header .btn {
                width: 100%;
            }
            .clearance-filter-grid {
                grid-template-columns: 1fr;
            }
            .clearance-analytics-grid {
                grid-template-columns: 1fr;
            }
            .clearance-chart-wrap {
                height: 220px;
            }
        }

        @media (min-width: 768px) and (max-width: 1199.98px) {
            .clearance-filter-grid {
                grid-template-columns: repeat(3, minmax(0, 1fr));
            }
        }
    </style>
@endpush

@push('scripts')
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
    <script>
    $(document).ready(function () {

        var currentStatusFilter = 'all';
        var currentDepartmentFilter = '';
        var currentStepFilter = '';
        var currentDateFrom = '';
        var currentDateTo = '';
        var allDtInstances = {};
        var clearanceCharts = {};

        var chartPalette = ['#007A33', '#28a745', '#20c997', '#17a2b8', '#6f42c1', '#fd7e14', '#ffc107', '#6c757d'];

        var dtOptions = {
            pageLength: 25,
            order: [[6, 'desc']],
            columnDefs: [{ orderable: false, targets: [0, 7] }],
            language: {
                search: '', searchPlaceholder: 'Search…',
                info: 'Showing _START_–_END_ of _TOTAL_',
                infoEmpty: 'No entries',
                infoFiltered: '(filtered from _MAX_)',
                zeroRecords: 'No matching records found',
                lengthMenu: 'Show _MENU_',
                paginate: { first: '«', last: '»', next: '›', previous: '‹' }
            }
        };

        function rowDateInRange(submittedDate) {
            if (!submittedDate) return !currentDateFrom && !currentDateTo;
            if (currentDateFrom && submittedDate < currentDateFrom) return false;
            if (currentDateTo && submittedDate > currentDateTo) return false;
            return true;
        }

        // Register custom clearance search on all DataTables
        $.fn.dataTable.ext.search.push(function (settings, data, dataIndex, row) {
            var tableId = settings.nTable ? settings.nTable.id : '';
            if (!/^clearanceTable_/.test(tableId) && tableId !== 'hrMonitorTable') return true;

            var rowNode = settings.aoData && settings.aoData[dataIndex] ? settings.aoData[dataIndex].nTr : row;
            var $row = $(rowNode);

            var rowStatus = String($row.data('status') || '');
            var rowDepartment = String($row.data('department') || '');
            var rowStep = String($row.data('step') || '');
            var rowSubmitted = String($row.data('submitted') || '');

            if (currentStatusFilter !== 'all' && rowStatus !== currentStatusFilter) return false;
            if (currentDepartmentFilter && rowDepartment !== currentDepartmentFilter) return false;
            if (currentStepFilter && rowStep !== currentStepFilter) return false;
            return rowDateInRange(rowSubmitted);
        });

        // Init approver tab tables
        ['lm','finance','it','hr'].forEach(function (tabId) {
            var $t = $('#clearanceTable_' + tabId);
            if ($t.length && $t.find('tbody tr').length && !$t.find('tbody tr td[colspan]').length) {
                allDtInstances[tabId] = $t.DataTable($.extend({}, dtOptions));
            }
        });

        // Init management monitoring table (different column order)
        var $hrMonitor = $('#hrMonitorTable');
        if ($hrMonitor.length && $hrMonitor.find('tbody tr').length) {
            allDtInstances['hrMonitor'] = $hrMonitor.DataTable($.extend({}, dtOptions, {
                columnDefs: [{ orderable: false, targets: [0, 2, 4, 5] }],
                order: [[3, 'desc']]
            }));
        }

        function populateSelectFromRows(selector, dataKey) {
            var values = new Set();
            Object.values(allDtInstances).forEach(function (dt) {
                dt.rows().nodes().each(function (row) {
                    var value = String($(row).data(dataKey) || '').trim();
                    if (value && value !== 'N/A') values.add(value);
                });
            });

            var $select = $(selector);
            Array.from(values).sort().forEach(function (value) {
                $select.append($('<option>', { value: value, text: value }));
            });
        }

        populateSelectFromRows('#clearanceDepartmentFilter', 'department');
        populateSelectFromRows('#clearanceStepFilter', 'step');

        // Re-adjust columns on tab switch
        document.querySelectorAll('#approvalTabs button[data-bs-toggle="tab"]').forEach(function (btn) {
            btn.addEventListener('shown.bs.tab', function (e) {
                var tabId = (e.target.getAttribute('data-bs-target') || '').replace('#tab-', '');
                if (allDtInstances[tabId]) allDtInstances[tabId].columns.adjust().draw(false);
                updateSummaryCards();
            });
        });

        $('#clearanceDepartmentFilter').on('change', function () {
            currentDepartmentFilter = this.value;
            redrawClearanceTables();
        });

        $('#clearanceStepFilter').on('change', function () {
            currentStepFilter = this.value;
            redrawClearanceTables();
        });

        $('#clearanceDateFrom, #clearanceDateTo').on('change', function () {
            currentDateFrom = $('#clearanceDateFrom').val();
            currentDateTo = $('#clearanceDateTo').val();
            redrawClearanceTables();
        });

        $('#clearanceResetFilters').on('click', function () {
            $('#clearanceDepartmentFilter').val('');
            $('#clearanceStepFilter').val('');
            $('#clearanceDateFrom').val('');
            $('#clearanceDateTo').val('');
            currentDepartmentFilter = '';
            currentStepFilter = '';
            currentDateFrom = '';
            currentDateTo = '';
            Object.values(allDtInstances).forEach(function (dt) { dt.search(''); });
            setClearanceFilter('all');
        });

        $('#clearanceStatusFilter').on('change', function () {
            setClearanceFilter(this.value || 'all');
        });

        window.setClearanceFilter = function (status) {
            currentStatusFilter = status;
            $('#clearanceStatusFilter').val(status);
            redrawClearanceTables();
        };

        function redrawClearanceTables() {
            Object.values(allDtInstances).forEach(function (dt) { dt.draw(); });
            updateSummaryCards();
        }

        function getFilteredClearanceRows() {
            var rows = [];
            var countedIds = new Set();

            Object.values(allDtInstances).forEach(function (dt) {
                dt.rows({ search: 'applied' }).nodes().each(function (row) {
                    var $row = $(row);
                    var clearanceId = String($row.data('clearance-id') || '');
                    if (clearanceId && countedIds.has(clearanceId)) return;
                    if (clearanceId) countedIds.add(clearanceId);

                    rows.push({
                        id: clearanceId,
                        status: String($row.data('status') || 'pending'),
                        department: String($row.data('department') || 'Unassigned'),
                        step: String($row.data('step') || 'Submitted'),
                        submitted: String($row.data('submitted') || '')
                    });
                });
            });

            return rows;
        }

        function monthKey(dateString) {
            if (!dateString) return 'Unknown';
            var date = new Date(dateString + 'T00:00:00');
            if (Number.isNaN(date.getTime())) return 'Unknown';
            return date.toLocaleString('en-US', { month: 'short', year: 'numeric' });
        }

        function countBy(rows, key) {
            return rows.reduce(function (acc, row) {
                var value = row[key] || 'Unassigned';
                acc[value] = (acc[value] || 0) + 1;
                return acc;
            }, {});
        }

        function sortedEntries(counts, limit) {
            var entries = Object.entries(counts).sort(function (a, b) { return b[1] - a[1]; });
            return limit ? entries.slice(0, limit) : entries;
        }

        function createClearanceCharts() {
            if (typeof Chart === 'undefined') return;

            var monthlyCanvas = document.getElementById('clearanceMonthlyChart');
            var departmentCanvas = document.getElementById('clearanceDepartmentChart');
            if (!monthlyCanvas || !departmentCanvas) return;

            clearanceCharts.monthly = new Chart(monthlyCanvas, {
                type: 'bar',
                data: { labels: [], datasets: [{ label: 'Clearances', data: [], backgroundColor: '#007A33', borderRadius: 4 }] },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: { y: { beginAtZero: true, ticks: { precision: 0 } }, x: { grid: { display: false } } }
                }
            });

            clearanceCharts.department = new Chart(departmentCanvas, {
                type: 'bar',
                data: { labels: [], datasets: [{ label: 'Clearances', data: [], backgroundColor: [], borderRadius: 4 }] },
                options: {
                    indexAxis: 'y',
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: { x: { beginAtZero: true, ticks: { precision: 0 } }, y: { grid: { display: false } } }
                }
            });
        }

        function updateClearanceAnalytics() {
            if (!clearanceCharts.monthly) return;

            var rows = getFilteredClearanceRows();
            var monthlyEntries = Object.entries(rows.reduce(function (acc, row) {
                var key = monthKey(row.submitted);
                acc[key] = (acc[key] || 0) + 1;
                return acc;
            }, {}));
            monthlyEntries.sort(function (a, b) {
                return new Date('01 ' + a[0]) - new Date('01 ' + b[0]);
            });

            var departmentEntries = sortedEntries(countBy(rows, 'department'), 8);

            clearanceCharts.monthly.data.labels = monthlyEntries.map(function (entry) { return entry[0]; });
            clearanceCharts.monthly.data.datasets[0].data = monthlyEntries.map(function (entry) { return entry[1]; });
            clearanceCharts.monthly.update();

            clearanceCharts.department.data.labels = departmentEntries.map(function (entry) {
                return entry[0].length > 24 ? entry[0].substring(0, 21) + '...' : entry[0];
            });
            clearanceCharts.department.data.datasets[0].data = departmentEntries.map(function (entry) { return entry[1]; });
            clearanceCharts.department.data.datasets[0].backgroundColor = chartPalette.slice(0, departmentEntries.length);
            clearanceCharts.department.update();
        }

        function updateSummaryCards() {
            var total = 0, pending = 0, completed = 0, rejected = 0;
            var countedIds = new Set();
            Object.values(allDtInstances).forEach(function (dt) {
                dt.rows({ search: 'applied' }).nodes().each(function (row) {
                    var clearanceId = String($(row).data('clearance-id') || '');
                    if (clearanceId && countedIds.has(clearanceId)) return;
                    if (clearanceId) countedIds.add(clearanceId);

                    total++;
                    var s = $(row).data('status');
                    if (s === 'pending')   pending++;
                    else if (s === 'completed') completed++;
                    else if (s === 'rejected')  rejected++;
                });
            });
            $('#count-total').text(total);
            $('#count-pending').text(pending);
            $('#count-completed').text(completed);
            $('#count-rejected').text(rejected);

            updateClearanceAnalytics();

        }

        // Initial count
        createClearanceCharts();
        updateSummaryCards();
    });
    </script>

    {{-- ── Clearance Export Modal ─────────────────────────────────── --}}
    <div class="modal fade" id="clearanceExportModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content clearance-export-modal">
                <form method="GET" action="{{ route('clearance.export') }}" id="clearanceExportForm" target="clearanceExportFrame">
                    <div class="modal-header">
                        <h5 class="modal-title">
                            <span class="clearance-export-icon"><i class="fas fa-file-excel"></i></span>
                            <span>Export Clearance Report</span>
                        </h5>
                        <button type="button" class="btn-close" id="clrExportCloseBtn" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div id="clrExportFields" class="row g-3">
                            <div class="col-6">
                                <label class="form-label"><i class="fas fa-calendar-day me-1"></i>Date From</label>
                                <input type="date" name="date_from" id="clr_date_from" class="form-control form-control-sm">
                            </div>
                            <div class="col-6">
                                <label class="form-label"><i class="fas fa-calendar-check me-1"></i>Date To</label>
                                <input type="date" name="date_to" id="clr_date_to" class="form-control form-control-sm">
                            </div>
                            <div class="col-12 col-md-6">
                                <label class="form-label"><i class="fas fa-circle-check me-1"></i>Status</label>
                                <select name="status" id="clr_export_status" class="form-select form-select-sm">
                                    <option value="all">All Statuses</option>
                                    <option value="pending">Pending</option>
                                    <option value="completed">Completed</option>
                                    <option value="rejected">Rejected</option>
                                </select>
                            </div>
                            <div class="col-12 col-md-6">
                                <label class="form-label"><i class="fas fa-sitemap me-1"></i>Department</label>
                                <select name="department_id" class="form-select form-select-sm">
                                    <option value="all">All Departments</option>
                                    @foreach ($departments ?? [] as $dept)
                                        <option value="{{ $dept->id }}">{{ $dept->dept_name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <input type="hidden" name="download_token" id="clrDownloadToken">
                        <div id="clrExportProgress" style="display:none;" class="mt-3">
                            <div class="d-flex align-items-center gap-2 mb-2">
                                <div class="spinner-border spinner-border-sm text-success" role="status"></div>
                                <span class="small fw-semibold text-muted" id="clrProgressLabel">Preparing report…</span>
                            </div>
                            <div class="progress" style="height:6px; border-radius:3px;">
                                <div id="clrProgressBar" class="progress-bar progress-bar-striped progress-bar-animated bg-success" role="progressbar" style="width:0%"></div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary btn-sm" id="clrExportCancelBtn" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-success btn-sm" id="clrExportSubmitBtn">
                            <i class="fas fa-download me-1"></i> Download Excel
                        </button>
                    </div>
                </form>
                <iframe name="clearanceExportFrame" style="display:none;"></iframe>
            </div>
        </div>
    </div>

    <script>
    (function() {
        var modal = document.getElementById('clearanceExportModal');
        if (!modal) return;
        var form       = document.getElementById('clearanceExportForm');
        var fields     = document.getElementById('clrExportFields');
        var progress   = document.getElementById('clrExportProgress');
        var label      = document.getElementById('clrProgressLabel');
        var bar        = document.getElementById('clrProgressBar');
        var submitBtn  = document.getElementById('clrExportSubmitBtn');
        var cancelBtn  = document.getElementById('clrExportCancelBtn');
        var closeBtn   = document.getElementById('clrExportCloseBtn');
        var tokenInput = document.getElementById('clrDownloadToken');
        var exporting  = false;
        var pollTimer  = null;
        var stepTimer  = null;

        var steps = [
            {label:'Querying clearance records…', pct:20},
            {label:'Processing data…',            pct:45},
            {label:'Building spreadsheet…',       pct:65},
            {label:'Finalising file…',            pct:85},
            {label:'Almost done…',                pct:95},
        ];

        function getCookie(name) {
            var v = document.cookie.match('(^|;)\\s*' + name + '\\s*=\\s*([^;]+)');
            return v ? decodeURIComponent(v.pop()) : '';
        }

        function reset() {
            exporting = false;
            clearInterval(pollTimer); clearTimeout(stepTimer);
            fields.style.display = ''; progress.style.display = 'none';
            bar.style.width = '0%'; label.textContent = 'Preparing report…';
            submitBtn.disabled = false; cancelBtn.disabled = false; closeBtn.disabled = false;
        }

        modal.addEventListener('show.bs.modal', function() {
            var df = document.getElementById('clr_date_from');
            var dt = document.getElementById('clr_date_to');
            var pageFrom = document.getElementById('clearanceDateFrom');
            var pageTo = document.getElementById('clearanceDateTo');
            var pageStatus = document.getElementById('clearanceStatusFilter');
            var exportStatus = document.getElementById('clr_export_status');
            var pageDepartment = document.getElementById('clearanceDepartmentFilter');
            var exportDepartment = modal.querySelector('select[name="department_id"]');
            if (pageFrom && pageFrom.value) df.value = pageFrom.value;
            if (pageTo && pageTo.value) dt.value = pageTo.value;
            if (pageStatus && exportStatus) exportStatus.value = pageStatus.value || 'all';
            if (pageDepartment && pageDepartment.value && exportDepartment) {
                Array.from(exportDepartment.options).forEach(function(option) {
                    if (option.text === pageDepartment.value) exportDepartment.value = option.value;
                });
            }
            if (!df.value) {
                var now = new Date(), from = new Date(); from.setDate(now.getDate() - 30);
                df.value = from.toISOString().slice(0,10);
                dt.value = now.toISOString().slice(0,10);
            }
        });
        modal.addEventListener('hidden.bs.modal', reset);
        modal.addEventListener('hide.bs.modal', function(e) { if (exporting) e.preventDefault(); });

        form.addEventListener('submit', function() {
            exporting = true;
            var token = Date.now().toString();
            tokenInput.value = token;
            fields.style.display = 'none'; progress.style.display = '';
            submitBtn.disabled = true; cancelBtn.disabled = true; closeBtn.disabled = true;
            document.cookie = 'clearance_download_token=; expires=Thu, 01 Jan 1970 00:00:00 GMT; path=/';

            var si = 0;
            function nextStep() {
                if (si >= steps.length) return;
                label.textContent = steps[si].label;
                bar.style.width   = steps[si].pct + '%';
                si++;
                stepTimer = setTimeout(nextStep, 1800);
            }
            nextStep();

            pollTimer = setInterval(function() {
                if (getCookie('clearance_download_token') === token) {
                    clearInterval(pollTimer); clearTimeout(stepTimer);
                    bar.style.width = '100%'; label.textContent = 'Download complete!';
                    setTimeout(function() {
                        exporting = false;
                        var m = bootstrap.Modal.getInstance(modal);
                        if (m) m.hide();
                    }, 1000);
                }
            }, 500);
        });
    })();
    </script>
@endpush
