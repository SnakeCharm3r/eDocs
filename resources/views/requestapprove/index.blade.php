@extends('layouts.template')

@section('breadcrumb')
    @include('sweetalert::alert')

    @if (session('error'))
        <div class="alert alert-danger">
            {!! session('error') !!}
        </div>
    @endif

    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
    <link rel="stylesheet" href="https://cdn.datatables.net/2.1.2/css/dataTables.dataTables.css" />
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/3.1.0/css/buttons.dataTables.css" />
    <script src="https://cdn.datatables.net/2.1.2/js/dataTables.js"></script>

    <div class="page-wrapper">
        <div class="content container-fluid">
            <div class="page-header">
                <div class="row">
                    <div class="col-sm-12">
                        <div class="page-sub-header">
                            <h3 class="page-title">Requests To Approve</h3>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Filter Buttons --}}
            <div class="card mb-3">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-md-6">
                            <h6 class="mb-0">Filter by Status:</h6>
                        </div>
                        <div class="col-md-6 text-end">
                            <div class="btn-group btn-group-sm" role="group" aria-label="Status filter">
                                <button type="button" class="btn btn-outline-primary active" data-filter="all" id="filter-all">
                                    <i class="fas fa-list me-1"></i>All
                                </button>
                                <button type="button" class="btn btn-outline-warning" data-filter="pending" id="filter-pending">
                                    <i class="fas fa-clock me-1"></i>Pending
                                </button>
                                <button type="button" class="btn btn-outline-success" data-filter="approved" id="filter-approved">
                                    <i class="fas fa-check-circle me-1"></i>Approved
                                </button>
                                <button type="button" class="btn btn-outline-danger" data-filter="rejected" id="filter-rejected">
                                    <i class="fas fa-times-circle me-1"></i>Rejected
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card card-body p-3">
                <div class="table-responsive">
                    <table id="example" class="display nowrap" style="width:100%">
                        <thead class="table-success">
                            <tr>
                                <th>#</th>
                                <th>Request Type</th>
                                <th>Requester Name</th>
                                <th>Submitted Date</th>
                                <th>Status</th>
                                <th>Approved/Rejected Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php
                                $i = 1;

                                // Default mapping for all other forms
                                $defaultStatusMap = [
                                    0 => ['class' => 'warning', 'text' => 'Pending'],
                                    1 => ['class' => 'success', 'text' => 'Approved'],
                                    -1 => ['class' => 'danger', 'text' => 'Rejected'],
                                ];

                                // Requisition mapping
                                $requisitionStatusMap = [
                                    -1 => ['class' => 'info', 'text' => 'Approved'],
                                    0 => ['class' => 'warning', 'text' => 'Pending'],
                                    1 => ['class' => 'secondary', 'text' => 'Under Review'],
                                    2 => ['class' => 'danger', 'text' => 'Rejected'],
                                    3 => ['class' => 'success', 'text' => 'Completed'],
                                ];
                            @endphp

                            {{-- PENDING REQUESTS --}}
                            @foreach ($pending as $pendingRequest)
                                @php
                                    // Determine status for filtering
                                    $rowStatus = 'pending';
                                    if ($pendingRequest->requisition_id) {
                                        if ($pendingRequest->requisition_status == -1 || $pendingRequest->requisition_status == 3) {
                                            $rowStatus = 'approved';
                                        } elseif ($pendingRequest->requisition_status == 2) {
                                            $rowStatus = 'rejected';
                                        } elseif ($pendingRequest->requisition_status == 0) {
                                            $rowStatus = 'pending';
                                        } else {
                                            $rowStatus = 'pending';
                                        }
                                    } else {
                                        if ($pendingRequest->status == 1) {
                                            $rowStatus = 'approved';
                                        } elseif ($pendingRequest->status == 2 || $pendingRequest->status == -1) {
                                            $rowStatus = 'rejected';
                                        } else {
                                            $rowStatus = 'pending';
                                        }
                                    }
                                @endphp
                                <tr data-status="{{ $rowStatus }}" data-request-type="general">
                                    <td>{{ $i++ }}</td>
                                    <td>
                                        @if ($pendingRequest->ict_request_resource_id)
                                            ICT Access Form
                                        @elseif ($pendingRequest->hr_form)
                                            HR Form
                                        @elseif ($pendingRequest->bank_form)
                                            Bank Details Form
                                        @elseif ($pendingRequest->heslb_form)
                                            Loan Board Form
                                        @elseif ($pendingRequest->nhif_form)
                                            NHIF Form
                                        @elseif ($pendingRequest->id_form)
                                            ID Form
                                        @elseif ($pendingRequest->change_request_id)
                                            Change Request
                                        @elseif ($pendingRequest->requisition_id)
                                            RRF Request
                                        @else
                                            No Form
                                        @endif
                                    </td>

                                    <td>{{ $pendingRequest->requester_name }}</td>

                                    <td>
                                        {{ \Carbon\Carbon::parse($pendingRequest->attend_date ?? $pendingRequest->created_at)->format('d F Y') }}
                                    </td>

                                    {{-- STATUS --}}
                                    <td>
                                        @if ($pendingRequest->requisition_id)
                                            @php
                                                $statusInfo = $requisitionStatusMap[$pendingRequest->requisition_status] ?? ['class' => 'secondary', 'text' => 'Unknown'];
                                            @endphp
                                            <span class="badge bg-{{ $statusInfo['class'] }} text-dark font-size-11">
                                                {{ $statusInfo['text'] }}
                                            </span>
                                        @else
                                            @php
                                                $statusInfo = $defaultStatusMap[$pendingRequest->status] ?? ['class' => 'secondary', 'text' => 'Unknown'];
                                            @endphp
                                            <span class="badge bg-{{ $statusInfo['class'] }} text-dark font-size-11">
                                                {{ $statusInfo['text'] }}
                                            </span>
                                        @endif
                                    </td>

                                    {{-- APPROVED / REJECTED DATE --}}
                                    <td>
                                        @if ($pendingRequest->requisition_id && in_array($pendingRequest->requisition_status, [-1, 1, 2, 3]))
                                            {{ \Carbon\Carbon::parse($pendingRequest->decision_date ?? $pendingRequest->updated_at)->format('d F Y') }}
                                        @elseif (in_array($pendingRequest->status, [1, -1]))
                                            {{ \Carbon\Carbon::parse($pendingRequest->decision_date ?? $pendingRequest->updated_at)->format('d F Y') }}
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>

                                    {{-- ACTIONS --}}
                                    <td>
                                        @if ($pendingRequest->ict_request_resource_id)
                                            <button type="button" class="btn btn-primary btn-sm" title="View"
                                                onclick="showForm('ict', {{ $pendingRequest->ict_request_resource_id }})">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                        @elseif ($pendingRequest->hr_form)
                                            <a href="{{ route('hr_form', ['id' => $pendingRequest->hr_form]) }}"
                                               class="btn btn-primary btn-sm" title="View">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                        @elseif ($pendingRequest->bank_form)
                                            <a href="{{ route('bank_form', ['id' => $pendingRequest->bank_form]) }}"
                                               class="btn btn-primary btn-sm" title="View">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                        @elseif ($pendingRequest->heslb_form)
                                            <a href="{{ route('heslb_form', ['id' => $pendingRequest->heslb_form]) }}"
                                               class="btn btn-primary btn-sm" title="View">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                        @elseif ($pendingRequest->nhif_form)
                                            <a href="{{ route('nhif_form', ['id' => $pendingRequest->nhif_form]) }}"
                                               class="btn btn-primary btn-sm" title="View">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                        @elseif ($pendingRequest->id_form)
                                            <a href="{{ route('id_form', ['id' => $pendingRequest->id_form]) }}"
                                               class="btn btn-primary btn-sm" title="View ID Form">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                        @elseif ($pendingRequest->change_request_id)
                                            <a href="{{ route('change_request.show', ['id' => $pendingRequest->change_request_id]) }}"
                                               class="btn btn-primary btn-sm" title="View">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                        @elseif ($pendingRequest->requisition_id)
                                            <a href="{{ route('requisitions.show', ['id' => $pendingRequest->requisition_id]) }}"
                                               class="btn btn-primary btn-sm" title="View RRF Request">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach

                            {{-- CLEARANCE FORMS REMOVED - Now shown in separate "Clearance Forms" navigation --}}
                            {{-- Clearance forms should only appear in clearance.index view, not in General Requests --}}
                            @if(false)
                            @foreach ($clear as $clearRequest)
                                @php
                                    $workflow = \App\Models\Clearance_work_flow::find($clearRequest->work_flow_id);
                                    $workflowStatus = $workflow ? $workflow->work_flow_status : 'Unknown';
                                    $stepName = $clearRequest->step_name ?? 'Pending';
                                    $isHR = Auth::user()->hasRole('hr');
                                    
                                    // For non-HR users: Skip approved forms (they should have moved to next level)
                                    // For HR: Show all forms
                                    if (!$isHR && $clearRequest->status == 1) {
                                        continue; // Skip approved forms for non-HR
                                    }
                                    
                                    // Determine status badge
                                    if ($clearRequest->status == 1) {
                                        $statusClass = 'success';
                                        $statusText = 'Approved';
                                        $rowStatus = 'approved';
                                    } elseif ($clearRequest->status == 2) {
                                        $statusClass = 'danger';
                                        $statusText = 'Rejected';
                                        $rowStatus = 'rejected';
                                    } else {
                                        $statusClass = 'warning';
                                        $statusText = $workflowStatus;
                                        $rowStatus = 'pending';
                                    }
                                @endphp
                                <tr data-status="{{ $rowStatus }}" data-request-type="clearance">
                                    <td>{{ $i++ }}</td>
                                    <td>
                                        <strong>Clearance Form</strong>
                                        @if($stepName && $stepName !== 'Pending')
                                            <br><small class="text-muted">Step: {{ $stepName }}</small>
                                        @endif
                                    </td>
                                    <td>
                                        @if(isset($clearRequest->fname) && isset($clearRequest->lname))
                                            {{ trim($clearRequest->fname . ' ' . $clearRequest->lname) }}
                                        @else
                                            {{ $clearRequest->requester_name }}
                                        @endif
                                    </td>
                                    <td>{{ \Carbon\Carbon::parse($clearRequest->attend_date ?? $clearRequest->created_at)->format('d F Y, h:i A') }}</td>
                                    <td>
                                        <span class="badge bg-{{ $statusClass }} text-white font-size-11">
                                            {{ $statusText }}
                                        </span>
                                        @if($workflow && $workflow->work_flow_completed == 1)
                                            <br><small class="text-success"><i class="fas fa-check-circle"></i> Completed</small>
                                        @endif
                                    </td>
                                    <td>
                                        @if (in_array($clearRequest->status, [1, 2]))
                                            {{ \Carbon\Carbon::parse($clearRequest->updated_at)->format('d F Y, h:i A') }}
                                        @else
                                            <span class="text-muted">Pending</span>
                                        @endif
                                    </td>
                                    <td>
                                        <button class="btn btn-primary btn-sm" title="View & Review"
                                            onclick="showForm('clearance', {{ $clearRequest->requested_resource_id }})">
                                            <i class="fas fa-eye me-1"></i> Review
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                            @endif
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    {{-- JS Scripts --}}
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/pdfmake.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/vfs_fonts.js"></script>
    <script src="https://cdn.datatables.net/buttons/3.1.0/js/dataTables.buttons.js"></script>
    <script src="https://cdn.datatables.net/buttons/3.1.0/js/buttons.dataTables.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/3.1.0/js/buttons.html5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/3.1.0/js/buttons.print.min.js"></script>

    <script>
        // Initialize DataTable
        var table = new DataTable('#example');

        // Filter functionality
        document.addEventListener('DOMContentLoaded', function() {
            const filterButtons = document.querySelectorAll('[data-filter]');
            const tableRows = document.querySelectorAll('#example tbody tr');

            filterButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const filter = this.getAttribute('data-filter');

                    // Update active button
                    filterButtons.forEach(btn => {
                        btn.classList.remove('active');
                    });
                    this.classList.add('active');

                    // Filter table rows
                    tableRows.forEach(row => {
                        const rowStatus = row.getAttribute('data-status');
                        if (filter === 'all') {
                            row.style.display = '';
                        } else if (rowStatus === filter) {
                            row.style.display = '';
                        } else {
                            row.style.display = 'none';
                        }
                    });

                    // Update DataTable search to reflect filter
                    table.draw();
                });
            });
        });

        function showForm(type, id) {
            let routes = {
                ict: '/show_form/',
                clearance: '/clearance_forms/',
                hr_form: '/hr_form/',
                requisition: '/requisitions/'
            };

            if (routes[type]) {
                window.location.href = routes[type] + id;
            }
        }
    </script>

    <style>
        .btn-group-sm .btn {
            padding: 0.25rem 0.5rem;
            font-size: 0.875rem;
            border-radius: 0;
        }
        .btn-group-sm .btn:first-child {
            border-top-left-radius: 0.25rem;
            border-bottom-left-radius: 0.25rem;
        }
        .btn-group-sm .btn:last-child {
            border-top-right-radius: 0.25rem;
            border-bottom-right-radius: 0.25rem;
        }
        .btn-group-sm .btn.active {
            box-shadow: 0 0 0 0.15rem rgba(0, 123, 255, 0.25);
        }
    </style>
@endsection
