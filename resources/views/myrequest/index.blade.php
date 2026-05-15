@extends('layouts.template')

@section('breadcrumb')
    @include('sweetalert::alert')
@endsection

@section('content')
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" />

    <style>
        .page-wrapper { visibility: hidden; }
        .page-header-bar {
            background: #fff; border-radius: 12px; padding: 1rem 1.25rem;
            box-shadow: 0 1px 3px rgba(0,0,0,.06); margin-bottom: 1.25rem;
        }
        .stat-card {
            background: #fff; border-radius: 10px; padding: .85rem 1rem;
            box-shadow: 0 1px 3px rgba(0,0,0,.06); display: flex;
            align-items: center; gap: .75rem; min-width: 140px;
        }
        .stat-card-icon {
            width: 40px; height: 40px; border-radius: 10px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1rem; flex-shrink: 0;
        }
        .stat-card-value { font-size: 1.3rem; font-weight: 700; line-height: 1; }
        .stat-card-label { font-size: .75rem; color: #6b7280; }

        /* Approval flow badges */
        .approval-flow { display: flex; align-items: center; gap: 2px; flex-wrap: wrap; }
        .approval-step {
            font-size: .7rem; font-weight: 600; padding: .2rem .45rem;
            border-radius: 4px; white-space: nowrap;
        }
        .approval-step-approved { background: #d1fae5; color: #065f46; }
        .approval-step-pending { background: #fef3c7; color: #92400e; }
        .approval-step-rejected { background: #fee2e2; color: #991b1b; }
        .approval-arrow { color: #d1d5db; font-size: .6rem; }

        /* Form type pills */
        .form-type-pill {
            display: inline-flex; align-items: center; gap: .35rem;
            font-size: .82rem; font-weight: 600;
        }
        .form-type-icon {
            width: 28px; height: 28px; border-radius: 7px;
            display: inline-flex; align-items: center; justify-content: center;
            font-size: .75rem;
        }

        /* Overall status */
        .overall-status {
            display: inline-flex; align-items: center; gap: .3rem;
            font-size: .78rem; font-weight: 600; padding: .25rem .6rem;
            border-radius: 20px;
        }
        .overall-approved { background: #d1fae5; color: #065f46; }
        .overall-pending { background: #fef3c7; color: #92400e; }
        .overall-rejected { background: #fee2e2; color: #991b1b; }

        /* Filter buttons */
        .status-filter-group { display: flex; gap: 0; }
        .status-btn {
            border: 1px solid #e5e7eb; background: #fff; padding: .3rem .75rem;
            font-size: .8rem; font-weight: 500; cursor: pointer; transition: all .15s;
            color: #374151;
        }
        .status-btn:first-child { border-radius: 6px 0 0 6px; }
        .status-btn:last-child { border-radius: 0 6px 6px 0; }
        .status-btn.active { color: #fff; border-color: transparent; }
        .status-btn-all.active { background: #374151; }
        .status-btn-pending.active { background: #f59e0b; }
        .status-btn-approved.active { background: #10b981; }
        .status-btn-rejected.active { background: #ef4444; }

        /* Table styling */
        #requestsTable thead th { font-size: .82rem; font-weight: 600; white-space: nowrap; }
        #requestsTable tbody td { font-size: .85rem; vertical-align: middle; }

        /* Rejection tooltip */
        .rejection-peek {
            display: block; font-size: .72rem; color: #dc2626; margin-top: 2px;
            max-width: 200px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
        }
    </style>

    <div class="page-wrapper">
        <div class="content container-fluid">
            {{-- Header --}}
            <div class="page-header-bar d-flex align-items-center justify-content-between flex-wrap gap-2">
                <div>
                    <h4 class="mb-0 fw-bold" style="font-size:1.15rem;">My Requests</h4>
                    <small class="text-muted">Track all your submitted forms and their approval status</small>
                </div>
            </div>

            @php
                // Pre-process all requests into a flat list
                $allRequests = collect();
                $roleDisplayNames = [
                    'line-manager' => 'Line Manager', 'it' => 'IT', 'hr' => 'HR',
                    'finance officer' => 'Finance', 'price_committee' => 'Price Committee',
                    'payroll_accountant' => 'Payroll', 'coo' => 'COO', 'cfo' => 'CFO',
                    'cms' => 'CMS', 'ceo' => 'CEO', 'quality_assurance' => 'QA',
                    'quality-assurance' => 'QA', 'ccdro' => 'CCDRO',
                ];
                $defaultStatusMap = [
                    0 => ['class' => 'pending', 'text' => 'Pending'],
                    1 => ['class' => 'approved', 'text' => 'Approved'],
                    2 => ['class' => 'rejected', 'text' => 'Rejected'],
                    -1 => ['class' => 'rejected', 'text' => 'Rejected'],
                ];

                // Form type config
                $formTypeConfig = [
                    'ict_request_resource_id' => ['name' => 'ICT Access Form', 'icon' => 'fa-laptop', 'color' => '#3b82f6', 'bg' => '#eff6ff'],
                    'hr_form' => ['name' => 'HR Form', 'icon' => 'fa-users', 'color' => '#8b5cf6', 'bg' => '#f5f3ff'],
                    'bank_form' => ['name' => 'Bank Details', 'icon' => 'fa-university', 'color' => '#059669', 'bg' => '#ecfdf5'],
                    'heslb_form' => ['name' => 'HESLB Form', 'icon' => 'fa-graduation-cap', 'color' => '#d97706', 'bg' => '#fffbeb'],
                    'nhif_form' => ['name' => 'NHIF Form', 'icon' => 'fa-heartbeat', 'color' => '#dc2626', 'bg' => '#fef2f2'],
                    'id_form' => ['name' => 'ID Card', 'icon' => 'fa-id-card', 'color' => '#0891b2', 'bg' => '#ecfeff'],
                    'change_request_id' => ['name' => 'Change Request', 'icon' => 'fa-exchange-alt', 'color' => '#7c3aed', 'bg' => '#f5f3ff'],
                ];

                foreach ($form as $aform) {
                    // Determine form type
                    $formType = 'other';
                    $formTypeName = 'Unknown';
                    $formTypeIcon = 'fa-file';
                    $formTypeColor = '#6b7280';
                    $formTypeBg = '#f9fafb';
                    $viewUrl = '#';

                    foreach ($formTypeConfig as $col => $cfg) {
                        if ($aform->$col) {
                            $formType = $col;
                            $formTypeName = $cfg['name'];
                            $formTypeIcon = $cfg['icon'];
                            $formTypeColor = $cfg['color'];
                            $formTypeBg = $cfg['bg'];
                            break;
                        }
                    }

                    // View URL
                    if ($aform->ict_request_resource_id) $viewUrl = '/show_form/' . $aform->ict_request_resource_id;
                    elseif ($aform->hr_form) $viewUrl = route('hr_form', ['id' => $aform->hr_form]);
                    elseif ($aform->bank_form) $viewUrl = route('bank_form', ['id' => $aform->bank_form]);
                    elseif ($aform->heslb_form) $viewUrl = route('heslb_form', ['id' => $aform->heslb_form]);
                    elseif ($aform->nhif_form) $viewUrl = route('nhif_form', ['id' => $aform->nhif_form]);
                    elseif ($aform->id_form) $viewUrl = route('id_form', ['id' => $aform->id_form]);
                    elseif ($aform->change_request_id) $viewUrl = route('change_request.show', ['id' => $aform->change_request_id]);

                    // Build approval chain
                    $approvalSteps = [];
                    $overallStatus = 'pending';
                    $rejectionReason = null;
                    $lastDecisionDate = null;
                    $uniqueRoles = [];
                    $requesterId = $aform->user_id;

                    $wfStatus = strtolower($aform->work_flow_status ?? '');
                    $wfCompleted = $aform->work_flow_completed;
                    $isRejectedByStatus = str_contains($wfStatus, 'rejected') || $wfStatus === '2' || $wfCompleted == 2;
                    $isApprovedByStatus = $wfCompleted == 1 && !$isRejectedByStatus &&
                        (str_contains($wfStatus, 'approved') || str_contains($wfStatus, 'confirmed') ||
                         str_contains($wfStatus, 'fully approved') || $wfStatus === '1');
                    $isCompleted = $isApprovedByStatus;
                    $isRejected = $isRejectedByStatus;

                    foreach ($histories[$aform->id] as $ahistory) {
                        $hUser = \App\Models\User::find($ahistory->attended_by);
                        if (!$hUser || $ahistory->attended_by == $requesterId) continue;
                        $hRoles = $hUser->roles;
                        foreach ($hRoles as $role) {
                            if ($role->name == 'requester' || in_array($role->name, $uniqueRoles)) continue;
                            $uniqueRoles[] = $role->name;
                            $statusInfo = $defaultStatusMap[$ahistory->status] ?? ['class' => 'pending', 'text' => 'Pending'];
                            $displayRole = $roleDisplayNames[strtolower($role->name)] ?? ucwords(str_replace(['-','_'], ' ', $role->name));

                            $approvalSteps[] = [
                                'role' => $displayRole,
                                'status' => $statusInfo['class'],
                                'text' => $statusInfo['text'],
                            ];

                            if ($ahistory->status == -1 || $ahistory->status == 2) {
                                $isRejected = true;
                                $rejectionReason = $ahistory->rejection_reason ?? null;
                            }
                            if (in_array($ahistory->status, [1, -1, 2]) && ($ahistory->decision_date || $ahistory->updated_at)) {
                                $lastDecisionDate = $ahistory->decision_date ?? $ahistory->updated_at;
                            }
                            break; // one role per history entry
                        }
                    }

                    if ($isCompleted) $overallStatus = 'approved';
                    elseif ($isRejected) $overallStatus = 'rejected';

                    $allRequests->push([
                        'id' => $aform->id,
                        'formType' => $formType,
                        'formTypeName' => $formTypeName,
                        'formTypeIcon' => $formTypeIcon,
                        'formTypeColor' => $formTypeColor,
                        'formTypeBg' => $formTypeBg,
                        'viewUrl' => $viewUrl,
                        'approvalSteps' => $approvalSteps,
                        'overallStatus' => $overallStatus,
                        'rejectionReason' => $rejectionReason,
                        'submittedDate' => $aform->created_at,
                        'lastDecisionDate' => $lastDecisionDate,
                        'isClearance' => false,
                    ]);
                }

                // Clearance forms
                foreach ($clearForm as $exit) {
                    $approvalSteps = [];
                    $overallStatus = 'pending';
                    $rejectionReason = null;
                    $lastDecisionDate = null;
                    $uniqueRoles = [];
                    $clearRequesterId = $exit->user_id;
                    $isRejected = false;

                    foreach ($clearHistories[$exit->id] as $ch) {
                        $hUser = \App\Models\User::find($ch->attended_by);
                        if (!$hUser || $ch->attended_by == $clearRequesterId) continue;
                        $hRoles = $hUser->roles;
                        foreach ($hRoles as $role) {
                            if ($role->name == 'requester' || in_array($role->name, $uniqueRoles)) continue;
                            $uniqueRoles[] = $role->name;
                            $statusInfo = $defaultStatusMap[$ch->status] ?? ['class' => 'pending', 'text' => 'Pending'];
                            $displayRole = $roleDisplayNames[strtolower($role->name)] ?? ucwords(str_replace(['-','_'], ' ', $role->name));
                            $approvalSteps[] = ['role' => $displayRole, 'status' => $statusInfo['class'], 'text' => $statusInfo['text']];
                            if ($ch->status == -1 || $ch->status == 2) { $isRejected = true; $rejectionReason = $ch->rejection_reason ?? null; }
                            if (in_array($ch->status, [1, -1, 2]) && ($ch->decision_date ?? $ch->updated_at)) { $lastDecisionDate = $ch->decision_date ?? $ch->updated_at; }
                            break;
                        }
                    }

                    $isCompleted = $exit->work_flow_completed == 1;
                    if ($isCompleted && !$isRejected) $overallStatus = 'approved';
                    elseif ($isRejected) $overallStatus = 'rejected';

                    $allRequests->push([
                        'id' => $exit->id,
                        'formType' => 'clearance',
                        'formTypeName' => 'Clearance Form',
                        'formTypeIcon' => 'fa-sign-out-alt',
                        'formTypeColor' => '#ec4899',
                        'formTypeBg' => '#fdf2f8',
                        'viewUrl' => '/clearance_forms/' . ($exit->requested_resource_id ?? $exit->id),
                        'approvalSteps' => $approvalSteps,
                        'overallStatus' => $overallStatus,
                        'rejectionReason' => $rejectionReason,
                        'submittedDate' => $exit->created_at,
                        'lastDecisionDate' => $lastDecisionDate,
                        'isClearance' => true,
                    ]);
                }

                // Stats
                $totalCount = $allRequests->count();
                $pendingCount = $allRequests->where('overallStatus', 'pending')->count();
                $approvedCount = $allRequests->where('overallStatus', 'approved')->count();
                $rejectedCount = $allRequests->where('overallStatus', 'rejected')->count();
            @endphp


            {{-- Filters --}}
            <div class="card mb-3 shadow-sm border-0" style="border-radius:10px;">
                <div class="card-body py-3">
                    <div class="row g-2 align-items-end">
                        <div class="col-12 col-md-5">
                            <label class="form-label mb-1 small fw-semibold text-muted">
                                <i class="fas fa-file-alt me-1"></i>Form Type:
                            </label>
                            <select class="form-select form-select-sm" id="formTypeFilter" style="border-radius:6px;">
                                <option value="all" selected>All Form Types</option>
                                <option value="ict_request_resource_id">ICT Access Form</option>
                                <option value="hr_form">HR Form</option>
                                <option value="bank_form">Bank Details Form</option>
                                <option value="heslb_form">HESLB Form</option>
                                <option value="nhif_form">NHIF Form</option>
                                <option value="id_form">ID Card</option>
                                <option value="change_request_id">Change Request</option>
                                <option value="clearance">Clearance Form</option>
                            </select>
                        </div>
                        <div class="col-12 col-md-7">
                            <label class="form-label mb-1 small fw-semibold text-muted">
                                <i class="fas fa-filter me-1"></i>Status:
                            </label>
                            <div class="status-filter-group">
                                <button type="button" class="status-btn status-btn-all active" data-filter="all">
                                    <i class="fas fa-list me-1"></i>All
                                </button>
                                <button type="button" class="status-btn status-btn-pending" data-filter="pending">
                                    <i class="fas fa-clock me-1"></i>Pending
                                </button>
                                <button type="button" class="status-btn status-btn-approved" data-filter="approved">
                                    <i class="fas fa-check-circle me-1"></i>Approved
                                </button>
                                <button type="button" class="status-btn status-btn-rejected" data-filter="rejected">
                                    <i class="fas fa-times-circle me-1"></i>Rejected
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Table --}}
            <div class="card shadow-sm border-0" style="border-radius:10px;">
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="requestsTable" class="table table-hover align-middle" style="width:100%">
                            <thead class="table-light">
                                <tr>
                                    <th style="width:2.5rem">#</th>
                                    <th>Request Type</th>
                                    <th>Approval Flow</th>
                                    <th>Status</th>
                                    <th>Submitted</th>
                                    <th>Decision Date</th>
                                    <th class="text-center" style="width:5rem">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($allRequests as $idx => $req)
                                    <tr data-status="{{ $req['overallStatus'] }}" data-form-type="{{ $req['formType'] }}">
                                        <td>{{ $idx + 1 }}</td>
                                        <td>
                                            <div class="form-type-pill">
                                                <span class="form-type-icon" style="background:{{ $req['formTypeBg'] }};color:{{ $req['formTypeColor'] }};">
                                                    <i class="fas {{ $req['formTypeIcon'] }}"></i>
                                                </span>
                                                {{ $req['formTypeName'] }}
                                            </div>
                                        </td>
                                        <td>
                                            @if (count($req['approvalSteps']) > 0)
                                                <div class="approval-flow">
                                                    @foreach ($req['approvalSteps'] as $stepIdx => $step)
                                                        @if ($stepIdx > 0)
                                                            <i class="fas fa-chevron-right approval-arrow"></i>
                                                        @endif
                                                        <span class="approval-step approval-step-{{ $step['status'] }}" title="{{ $step['role'] }}: {{ $step['text'] }}">
                                                            {{ $step['role'] }}
                                                        </span>
                                                    @endforeach
                                                </div>
                                            @else
                                                <span class="text-muted" style="font-size:.82rem;">No approval steps</span>
                                            @endif
                                        </td>
                                        <td>
                                            <span class="overall-status overall-{{ $req['overallStatus'] }}">
                                                @if ($req['overallStatus'] === 'approved')
                                                    <i class="fas fa-check-circle"></i> Approved
                                                @elseif ($req['overallStatus'] === 'rejected')
                                                    <i class="fas fa-times-circle"></i> Rejected
                                                @else
                                                    <i class="fas fa-clock"></i> Pending
                                                @endif
                                            </span>
                                            @if ($req['overallStatus'] === 'rejected' && $req['rejectionReason'])
                                                <span class="rejection-peek" title="{{ $req['rejectionReason'] }}">
                                                    <i class="fas fa-info-circle"></i> {{ \Illuminate\Support\Str::limit($req['rejectionReason'], 40) }}
                                                </span>
                                            @endif
                                        </td>
                                        <td>{{ \Carbon\Carbon::parse($req['submittedDate'])->format('d M Y') }}</td>
                                        <td>
                                            @if ($req['lastDecisionDate'])
                                                {{ \Carbon\Carbon::parse($req['lastDecisionDate'])->format('d M Y') }}
                                            @else
                                                <span class="text-muted">—</span>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            @if ($req['viewUrl'] !== '#')
                                                <a href="{{ $req['viewUrl'] }}" class="btn btn-sm btn-primary" title="View Form" style="border-radius:6px;">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                            @else
                                                <span class="text-muted">—</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Rejection Reason Modal --}}
    <div class="modal fade" id="rejectionModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content" style="border-radius:12px;">
                <div class="modal-header bg-danger text-white" style="border-radius:12px 12px 0 0;">
                    <h5 class="modal-title"><i class="fas fa-info-circle me-2"></i>Rejection Reason</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p id="rejectionModalText"></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <script>
        var currentStatusFilter = 'all';
        var currentFormTypeFilter = 'all';

        var table = $('#requestsTable').DataTable({
            dom: '<"row mb-2"<"col-sm-6"l><"col-sm-6 d-flex justify-content-end"f>>rtip',
            pageLength: 25,
            order: [[4, 'desc']],
            columnDefs: [
                { orderable: false, targets: [2, 6] }
            ],
            drawCallback: function() {
                var api = this.api();
                var start = api.page.info().start;
                api.column(0, { search: 'applied', order: 'applied', page: 'current' }).nodes().each(function(cell, i) {
                    cell.innerHTML = start + i + 1;
                });
            },
            language: {
                search: 'Search:',
                info: 'Showing _START_ to _END_ of _TOTAL_ requests',
                infoEmpty: 'No requests found',
                infoFiltered: '(filtered from _MAX_ total)',
                zeroRecords: 'No matching requests found',
                lengthMenu: 'Show _MENU_ entries',
                paginate: { first: 'First', last: 'Last', next: 'Next', previous: 'Previous' }
            }
        });

        // Custom filter
        $.fn.dataTable.ext.search.push(function(settings, data, dataIndex) {
            if (settings.nTable.id !== 'requestsTable') return true;
            var row = table.row(dataIndex).node();
            if (!row) return true;
            var rowStatus = $(row).data('status') || '';
            var rowFormType = $(row).data('form-type') || '';
            var statusMatch = currentStatusFilter === 'all' || rowStatus === currentStatusFilter;
            var formTypeMatch = currentFormTypeFilter === 'all' || rowFormType === currentFormTypeFilter;
            return statusMatch && formTypeMatch;
        });

        function setFilter(status) {
            currentStatusFilter = status;
            document.querySelectorAll('[data-filter]').forEach(function(btn) {
                btn.classList.toggle('active', btn.getAttribute('data-filter') === status);
            });
            table.draw();
        }

        // Status buttons
        document.querySelectorAll('[data-filter]').forEach(function(btn) {
            btn.addEventListener('click', function() {
                setFilter(this.getAttribute('data-filter'));
            });
        });

        // Form type filter
        document.getElementById('formTypeFilter').addEventListener('change', function() {
            currentFormTypeFilter = this.value;
            table.draw();
        });

        // Rejection peek click
        document.querySelectorAll('.rejection-peek').forEach(function(el) {
            el.style.cursor = 'pointer';
            el.addEventListener('click', function() {
                document.getElementById('rejectionModalText').textContent = this.getAttribute('title');
                new bootstrap.Modal(document.getElementById('rejectionModal')).show();
            });
        });

        // Reveal page
        document.querySelector('.page-wrapper').style.visibility = 'visible';
    </script>

    <style>.page-wrapper { visibility: visible !important; }</style>
@endsection
