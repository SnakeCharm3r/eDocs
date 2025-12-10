@extends('layouts.template')

@section('breadcrumb')
    @include('sweetalert::alert')
@endsection

@section('content')
    <style>
        .request-table th,
        .request-table td {
            border: 1px solid #000;
            padding: 10px;
        }

        .request-table th {
            background: #f8f8f8;
        }

        .rejection-feedback {
            color: #dc3545;
            font-size: 0.9em;
        }

        .status-badge {
            font-size: 0.9em;
            padding: 0.4em 0.8em;
        }

        .table-hover tbody tr:hover {
            background-color: #f8f9fa;
        }

        .status-badge.bg-success {
            background-color: #198754 !important;
            color: white;
        }

        .status-badge.bg-danger {
            background-color: #dc3545 !important;
            color: white;
        }

        .status-badge.bg-warning {
            background-color: #ffc107 !important;
            color: black;
        }

        .filter-form .form-select {
            min-width: 150px;
        }
    </style>

    <div class="page-wrapper">
        <div class="content container-fluid">
            <!-- Page Header -->
            <div class="page-header">
                <div class="row">
                    <div class="col-sm-12">
                        <div class="page-sub-header d-flex justify-content-between align-items-center">
                            <div class="btn-group" role="group" aria-label="Locum Navigation">
                                <a href="/locum-agreement-show" class="btn btn-outline-secondary btn-sm me-2">Locum
                                    Agreements</a>
                                <a href="{{ route('locum-requests.view') }}"
                                    class="btn btn-outline-primary btn-sm me-2">Locum Requests</a>
                                @if (auth()->user()->hasAnyRole(['in_charge', 'line-manager', 'hr']))
                                    <a href="{{ route('locum-requests.report') }}"
                                        class="btn btn-outline-secondary btn-sm me-2">Reports</a>
                                @endif
                                <a href="{{ route('locum-requests.approved') }}" class="btn btn-primary btn-sm">All
                                    Approved</a>
                            </div>
                            <h4 class="page-title mb-0 fs-6">All Approved Locum Requests</h4>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Alerts -->
            @if (session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif
            @if (session('error'))
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    {{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            <!-- Back Button -->
            <div class="d-flex mb-3">
                <a href="/locum-agreement-show" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back to Locum Agreements
                </a>
            </div>

            <!-- Month Filter -->
            <div class="row mb-3">
                <div class="col-auto">
                    <label for="filterMonth" class="col-form-label">Filter by Month</label>
                </div>
                <div class="col-auto">
                    <select id="filterMonth" class="form-select">
                        <option value="">All Months</option>
                        @foreach ([
            1 => 'January',
            2 => 'February',
            3 => 'March',
            4 => 'April',
            5 => 'May',
            6 => 'June',
            7 => 'July',
            8 => 'August',
            9 => 'September',
            10 => 'October',
            11 => 'November',
            12 => 'December',
        ] as $num => $name)
                            <option value="{{ $num }}" {{ request('month') == $num ? 'selected' : '' }}>
                                {{ $name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <!-- Grouped Requests by Month -->
            @php
                $groupedRequests = $approvedRequests
                    ->filter(fn($r) => !is_null($r->created_at) && !is_null($r->locum_month))
                    ->groupBy(fn($r) => \Carbon\Carbon::parse($r->locum_month)->month)
                    ->sortKeys();
            @endphp

            <div class="row">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-body">
                            <div class="accordion" id="requestsAccordion">
                                @forelse ($groupedRequests as $month => $requests)
                                    @php
                                        $monthName = \Carbon\Carbon::create()->month($month)->format('F');
                                        $year = $requests->first()->created_at
                                            ? \Carbon\Carbon::parse($requests->first()->created_at)->year
                                            : '';
                                    @endphp
                                    <div class="accordion-item" data-month="{{ $month }}">
                                        <h2 class="accordion-header" id="heading-{{ $month }}">
                                            <button class="accordion-button collapsed" type="button"
                                                data-bs-toggle="collapse" data-bs-target="#collapse-{{ $month }}"
                                                aria-expanded="false" aria-controls="collapse-{{ $month }}">
                                                {{ $monthName }} {{ $year }}
                                            </button>
                                        </h2>
                                        <div id="collapse-{{ $month }}" class="accordion-collapse collapse"
                                            aria-labelledby="heading-{{ $month }}"
                                            data-bs-parent="#requestsAccordion">
                                            <div class="accordion-body">
                                                <div class="table-responsive">
                                                    <table
                                                        class="table table-striped table-hover table-bordered request-table w-100"
                                                        id="table-{{ $month }}" data-month="{{ $month }}"
                                                        data-year="{{ $year }}">
                                                        <thead class="table-success">
                                                            <tr>
                                                                <th>#</th>
                                                                <th>Employee</th>
                                                                <th>Emp Code</th>
                                                                <th>Department</th>
                                                                <th>Month</th>
                                                                <th>Days</th>
                                                                <th>Hours</th>
                                                                <th>Amount (TZS)</th>
                                                                <th>Status</th>
                                                                <th>Feedback</th>
                                                                <th>Actions</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            @foreach ($requests as $index => $request)
                                                                @php
                                                                    $latestHistory = $request->workflow
                                                                        ? $request->workflow->histories->first()
                                                                        : null;

                                                                    // Expanded status map for two- and three-level locum approval
                                                                    $statusMap = [
                                                                        1 => 'Pending Line Manager Approval',
                                                                        2 => 'Pending HEC Approval',
                                                                        3 => 'Pending HR Approval',
                                                                        4 => 'Approved',
                                                                        5 => 'Rejected',
                                                                    ];

                                                                    $status =
                                                                        $latestHistory &&
                                                                        isset($latestHistory->locum_request_status)
                                                                            ? $statusMap[
                                                                                    $latestHistory->locum_request_status
                                                                                ] ?? 'Pending'
                                                                            : 'Pending';

                                                                    // Badge colors
                                                                    $badgeClass =
                                                                        $latestHistory &&
                                                                        isset($latestHistory->locum_request_status)
                                                                            ? match (
                                                                                $latestHistory->locum_request_status
                                                                            ) {
                                                                                4 => 'bg-success', // Approved
                                                                                5 => 'bg-danger', // Rejected
                                                                                default => 'bg-warning', // Any pending
                                                                            }
                                                                            : 'bg-warning';

                                                                    $workedDays = $request->worked_days;
                                                                    if (is_string($workedDays)) {
                                                                        $workedDays = json_decode($workedDays, true);
                                                                    }

                                                                    $requestYear = $request->created_at
                                                                        ? \Carbon\Carbon::parse($request->created_at)
                                                                            ->year
                                                                        : $year;
                                                                @endphp

                                                                <tr>
                                                                    <td>{{ $index + 1 }}</td>
                                                                    <td>{{ $request->user ? $request->user->username ?? 'N/A' : 'N/A' }}
                                                                    </td>
                                                                    <td>{{ $request->user ? $request->user->ccbrt_code ?? 'N/A' : 'N/A' }}
                                                                    </td>
                                                                    <td>{{ $request->department ? $request->department->name ?? 'N/A' : 'N/A' }}
                                                                    </td>
                                                                    <td>{{ $request->locum_month ?? 'N/A' }}
                                                                        {{ $requestYear }}</td>
                                                                    <td>{{ $request->number_of_days ?? 0 }}</td>
                                                                    <td>{{ number_format($request->total_hours ?? 0, 2) }}
                                                                    </td>
                                                                    <td>{{ number_format($request->total_amount_payable ?? 0, 2, '.', ',') }}
                                                                    </td>
                                                                    <td>
                                                                        <span
                                                                            class="badge status-badge {{ $badgeClass }}"
                                                                            title="{{ $status }}{{ $latestHistory && $latestHistory->rejection_reason ? ': ' . $latestHistory->rejection_reason : '' }}">
                                                                            {{ $status }}
                                                                        </span>
                                                                    </td>
                                                                    <td>
                                                                        @if ($latestHistory && $latestHistory->rejection_reason)
                                                                            <span class="text-danger small">
                                                                                {{ $latestHistory->rejection_reason }}
                                                                            </span>
                                                                        @endif
                                                                    </td>
                                                                    <td>
                                                                        <button type="button"
                                                                            class="btn btn-sm btn-outline-primary action-modal-btn"
                                                                            data-bs-toggle="modal"
                                                                            data-bs-target="#actionModal"
                                                                            data-id="{{ $request->id }}"
                                                                            data-action="view-worked-days"
                                                                            data-worked-days="{{ json_encode($workedDays) }}"
                                                                            data-month="{{ $request->locum_month }}"
                                                                            data-year="{{ $requestYear }}"
                                                                            title="View Worked Days">
                                                                            <i class="fas fa-calendar-day"></i>
                                                                        </button>
                                                                        <button type="button"
                                                                            class="btn btn-sm btn-outline-primary action-modal-btn"
                                                                            data-bs-toggle="modal"
                                                                            data-bs-target="#actionModal"
                                                                            data-id="{{ $request->id }}"
                                                                            data-action="view-details" title="View Details">
                                                                            <i class="fas fa-eye"></i>
                                                                        </button>
                                                                        <button type="button"
                                                                            class="btn btn-sm btn-success export-excel-btn"
                                                                            data-year="{{ $requestYear }}"
                                                                            data-month="{{ $month }}"
                                                                            title="Export to Excel">
                                                                            <i class="fas fa-file-excel"></i>
                                                                        </button>
                                                                    </td>
                                                                </tr>
                                                            @endforeach
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @empty
                                    <div class="alert alert-info text-center" id="no-results">
                                        No approved locum requests found.
                                    </div>
                                @endforelse
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Unified Action Modal -->
            <div class="modal fade" id="actionModal" tabindex="-1" aria-labelledby="actionModalLabel"
                aria-hidden="true">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="actionModalLabel">Locum Request</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"
                                aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <!-- Worked Days Section -->
                            <div id="worked-days-section" style="display: none;">
                                <h6 class="mb-3">Worked Days</h6>
                                <div id="worked-days-data">
                                    <table class="table table-bordered table-sm">
                                        <thead>
                                            <tr>
                                                <th>Date</th>
                                                <th>Worked</th>
                                                <th>Hours</th>
                                            </tr>
                                        </thead>
                                        <tbody id="worked-days-table-body"></tbody>
                                    </table>
                                </div>
                                <p id="no-worked-days" class="text-muted" style="display: none;">No worked days data
                                    available.</p>
                            </div>

                            <!-- Details Section -->
                            <div id="details-section" style="display: none;">
                                <h6 class="mb-3">User Information</h6>
                                <div class="table-responsive">
                                    <table class="table table-bordered">
                                        <tbody>
                                            <tr>
                                                <th style="width: 30%;">Username</th>
                                                <td id="user-username"></td>
                                            </tr>
                                            <tr>
                                                <th>Emp Code</th>
                                                <td id="user-ccbrt_code"></td>
                                            </tr>
                                            <tr>
                                                <th>Department</th>
                                                <td id="user-dept_name"></td>
                                            </tr>
                                            <tr>
                                                <th>Status</th>
                                                <td id="user-status"></td>
                                            </tr>
                                            <tr>
                                                <th>End Date</th>
                                                <td id="user-end_date"></td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                                <h6 class="mb-3 mt-4">Locum Agreement Details</h6>
                                <div id="agreement-details">
                                    <p id="no-agreement" class="text-muted">No agreement found.</p>
                                    <div id="agreement-data" style="display: none;">
                                        <div class="table-responsive">
                                            <table class="table table-bordered">
                                                <tbody>
                                                    <tr>
                                                        <th style="width: 30%;">Locum Rate</th>
                                                        <td id="agreement-locum_rate"></td>
                                                    </tr>
                                                    <tr>
                                                        <th>Has Contract</th>
                                                        <td id="agreement-has_contract"></td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                                <h6 class="mb-3 mt-4">BioTime Data</h6>
                                <div id="bioTime-details">
                                    <p id="no-biotime" class="text-muted">No BioTime data available.</p>
                                    <div id="bioTime-data" style="display: none;">
                                        <div class="table-responsive">
                                            <table class="table table-bordered table-sm">
                                                <thead>
                                                    <tr>
                                                        <th>Date</th>
                                                        <th>Worked</th>
                                                        <th>Hours</th>
                                                    </tr>
                                                </thead>
                                                <tbody id="bioTime-table-body"></tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- DataTables CSS & JS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" />
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        $(document).ready(function() {
            // Store DataTable instances
            const dataTables = {};

            // Initialize DataTables for each table
            $('table.request-table').each(function() {
                const tableId = $(this).attr('id');
                dataTables[tableId] = $(this).DataTable({
                    pageLength: 10,
                    ordering: true,
                    searching: false, // No search bar
                    paging: true,
                    info: true,
                    order: [
                        [3, 'asc']
                    ], // Default sort by Department
                    language: {
                        emptyTable: "No approved locum requests found",
                        info: "Showing _START_ to _END_ of _TOTAL_ entries",
                        lengthMenu: "Show _MENU_ entries"
                    }
                });
            });

            // Handle month filter
            $('#filterMonth').on('change', function() {
                const month = $(this).val();
                const url = new URL(window.location.href);
                if (month) {
                    url.searchParams.set('month', month);
                } else {
                    url.searchParams.delete('month');
                }
                window.location.href = url.toString();
            });

            // Handle action modal
            $('.action-modal-btn').on('click', function() {
                const action = $(this).data('action');
                const id = $(this).data('id');
                const modal = $('#actionModal');
                const modalTitle = $('#actionModalLabel');
                const workedDaysSection = $('#worked-days-section');
                const detailsSection = $('#details-section');

                // Reset modal state
                workedDaysSection.hide();
                detailsSection.hide();

                if (action === 'view-worked-days') {
                    modalTitle.text('Worked Days');
                    workedDaysSection.show();
                    const workedDays = $(this).data('worked-days') || {};
                    const tableBody = $('#worked-days-table-body');
                    tableBody.empty();
                    if (workedDays && Object.keys(workedDays).length > 0) {
                        $('#no-worked-days').hide();
                        $('#worked-days-data').show();
                        Object.keys(workedDays).forEach(date => {
                            const info = workedDays[date];
                            const workedBadge = info.worked == '1' ?
                                '<span class="badge bg-success">Yes</span>' :
                                '<span class="badge bg-secondary">No</span>';
                            const row = `
                                <tr>
                                    <td>${new Date(date).toLocaleDateString('en-GB', { day: '2-digit', month: 'long' })}</td>
                                    <td>${workedBadge}</td>
                                    <td>${info.hours || '-'}</td>
                                </tr>
                            `;
                            tableBody.append(row);
                        });
                    } else {
                        $('#no-worked-days').show();
                        $('#worked-days-data').hide();
                    }
                } else if (action === 'view-details') {
                    modalTitle.text('Locum Details');
                    detailsSection.show();
                    $.ajax({
                        url: '{{ route('locum-requests.showLocumRequestActioned', ':id') }}'
                            .replace(':id', id),
                        method: 'GET',
                        success: function(data) {
                            // Populate user details
                            $('#user-username').text(data.user.username);
                            $('#user-ccbrt_code').text(data.user.ccbrt_code || 'N/A');
                            $('#user-dept_name').text(data.user.dept_name || 'N/A');
                            $('#user-status').text(data.user.status);
                            $('#user-end_date').text(data.user.end_date);

                            // Populate agreement details
                            if (data.agreement) {
                                $('#no-agreement').hide();
                                $('#agreement-data').show();
                                $('#agreement-locum_rate').text(data.agreement.locum_rate);
                                $('#agreement-has_contract').text(data.agreement.has_contract);
                            } else {
                                $('#no-agreement').show();
                                $('#agreement-data').hide();
                            }

                            // Populate BioTime data
                            if (data.bioTimeData && Object.keys(data.bioTimeData).length > 0) {
                                $('#no-biotime').hide();
                                $('#bioTime-data').show();
                                const tableBody = $('#bioTime-table-body');
                                tableBody.empty();
                                Object.keys(data.bioTimeData).forEach(date => {
                                    const info = data.bioTimeData[date];
                                    const workedBadge = info.worked == '1' ?
                                        '<span class="badge bg-success">Yes</span>' :
                                        '<span class="badge bg-secondary">No</span>';
                                    const row = `
                                        <tr>
                                            <td>${new Date(date).toLocaleDateString('en-GB', { day: '2-digit', month: 'long' })}</td>
                                            <td>${workedBadge}</td>
                                            <td>${info.hours || '-'}</td>
                                        </tr>
                                    `;
                                    tableBody.append(row);
                                });
                            } else {
                                $('#no-biotime').show();
                                $('#bioTime-data').hide();
                            }
                        },
                        error: function(xhr) {
                            Swal.fire('Error', 'Failed to load details: ' + (xhr.responseJSON
                                ?.message || 'Unknown error'), 'error');
                        }
                    });
                }
            });

            // Handle Excel export
            $('.export-excel-btn').on('click', function() {
                const year = $(this).data('year');
                const month = $(this).data('month');
                const url = '{{ route('locum-requests.approved.export-excel') }}' +
                    `?year=${year}&month=${month}`;
                window.location.href = url;
            });
        });
    </script>
@endsection
