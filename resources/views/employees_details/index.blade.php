@extends('layouts.template')

@section('breadcrumb')
    @include('sweetalert::alert')

    {{-- DataTables CSS --}}
    <link rel="stylesheet" href="https://cdn.datatables.net/2.1.2/css/dataTables.dataTables.css" />
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/3.1.0/css/buttons.dataTables.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">

    <style>
        /* Ensure action column is always visible and clickable */
        #example th:last-child,
        #example td:last-child {
            position: sticky;
            right: 0;
            background-color: white;
            z-index: 10;
            min-width: 150px;
        }

        #example th:last-child {
            background-color: #f8f9fa;
            z-index: 11;
        }

        #example td:last-child {
            box-shadow: -2px 0 5px rgba(0,0,0,0.1);
        }

        /* Ensure buttons are clickable */
        #example td:last-child .btn {
            pointer-events: auto;
            z-index: 12;
            position: relative;
        }

        /* Stats Cards Styling */
        .stats-card {
            transition: all 0.2s ease;
        }

        .stats-card:hover {
            border-color: #007bff !important;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
    </style>
@endsection

@section('content')
    <div class="page-wrapper">
        <div class="content container-fluid">
            <!-- Page Header -->
            <div class="page-header mb-4">
                <div class="row align-items-center">
                    <div class="col-sm-6">
                        <h3 class="page-title mb-0">
                            <i class="fas fa-users me-2"></i>Staff Details
                        </h3>
                    </div>
                    <div class="col-sm-6 text-end">
                        <a href="{{ route('staff.add') }}" class="btn btn-success">
                            <i class="fas fa-user-plus me-1"></i> Add New Staff
                        </a>
                    </div>
                </div>
            </div>

            @if (session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    {!! session('success') !!}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            @if (session('error'))
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    {!! session('error') !!}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            {{-- <div class="page-header">
                <div class="row align-items-center">
                    <div class="col-md-6">
                        <h3 class="page-title mb-0">Employees</h3>
                    </div>
                    <div class="col-md-6 text-md-end mt-3 mt-md-0">
                        <a href="{{ route('biotime.index') }}" class="btn btn-outline-success" id="bioTimeBtn">
                            <i class="fas fa-users me-1"></i> BioTime Staff
                        </a>
                    </div>
                </div>
            </div> --}}

            {{-- Loader Spinner --}}
            <div id="loadingWrapper" style="display: none;">
                <div id="loadingIndicator" class="text-center my-3">
                    <div class="spinner-border text-success" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p>Please wait...</p>
                </div>
            </div>

            {{-- ===== Overall Status Summary Cards ===== --}}
            @php
                $totalAll = collect($statusCounts ?? [])->sum();
                $activeCount = (int) (($statusCounts ?? collect())['active'] ?? 0);
                $inactiveCount = (int) (($statusCounts ?? collect())['inactive'] ?? 0);
                $deactivatedCount = (int) (($statusCounts ?? collect())['deactivated'] ?? 0);
            @endphp

            <div class="row g-2 mb-3">
                <!-- Total Users Card -->
                <div class="col-md-3 col-sm-6">
                    <div class="card border h-100 stats-card" style="cursor: pointer;" onclick="filterByStatus('')">
                        <div class="card-body p-3">
                            <div class="d-flex align-items-center justify-content-between">
                                <div>
                                    <div class="text-muted small mb-1">Total Users</div>
                                    <div class="h5 fw-bold mb-0">{{ number_format($totalAll) }}</div>
                                </div>
                                <i class="fas fa-users text-muted"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Active Users Card -->
                <div class="col-md-3 col-sm-6">
                    <div class="card border h-100 stats-card" style="cursor: pointer;" onclick="filterByStatus('active')">
                        <div class="card-body p-3">
                            <div class="d-flex align-items-center justify-content-between">
                                <div>
                                    <div class="text-muted small mb-1">Active</div>
                                    <div class="h5 fw-bold mb-0">{{ number_format($activeCount) }}</div>
                                </div>
                                <i class="fas fa-user-check text-muted"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Inactive Users Card -->
                <div class="col-md-3 col-sm-6">
                    <div class="card border h-100 stats-card" style="cursor: pointer;" onclick="filterByStatus('inactive')">
                        <div class="card-body p-3">
                            <div class="d-flex align-items-center justify-content-between">
                                <div>
                                    <div class="text-muted small mb-1">Inactive</div>
                                    <div class="h5 fw-bold mb-0">{{ number_format($inactiveCount) }}</div>
                                </div>
                                <i class="fas fa-user-slash text-muted"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Deactivated Users Card -->
                <div class="col-md-3 col-sm-6">
                    div class="card border h-100 stats-card" style="cursor: pointer;" onclick="filterByStatus('deactivated')">
                        <div class="card-body p-3">
                            <div class="d-flex align-items-center justify-content-between">
                                <div>
                                    <div class="text-muted small mb-1">Deactivated</div>
                                    <div class="h5 fw-bold mb-0">{{ number_format($deactivatedCount) }}</div>
                                </div>
                                <i class="fas fa-user-times text-muted"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Advanced Filters and Export --}}
            <div class="card mb-3">
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-2">
                            <label class="form-label">Filter by Department</label>
                            <select id="filterDepartment" class="form-select form-select-sm">
                                <option value="">All Departments</option>
                                @foreach ($departments ?? [] as $dept)
                                    <option value="{{ $dept->dept_name }}">{{ $dept->dept_name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Filter by Status</label>
                            <select id="filterStatus" class="form-select form-select-sm">
                                <option value="">All Statuses</option>
                                @foreach ($statusColumns ?? [] as $status)
                                    <option value="{{ $status }}">{{ ucfirst($status) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Filter by License Status</label>
                            <select id="filterLicenseStatus" class="form-select form-select-sm">
                                <option value="">All License Statuses</option>
                                <option value="Valid">Valid</option>
                                <option value="Expiring Soon">Expiring Soon</option>
                                <option value="Expired">Expired</option>
                                <option value="Not Set">Not Set</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Search</label>
                            <input type="text" id="searchInput" class="form-control form-control-sm" placeholder="Search by name, code...">
                        </div>
                        <div class="col-md-3 d-flex align-items-end gap-2">
                            <button type="button" class="btn btn-sm btn-secondary flex-fill" onclick="clearFilters()">
                                <i class="fas fa-times"></i> Clear Filters
                            </button>
                            <button type="button" class="btn btn-sm btn-primary flex-fill" onclick="showExportOptions()">
                                <i class="fas fa-download"></i> Export
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            <div id="exportButtonContainer" style="display: none;"></div>

            {{-- Bulk Actions --}}
            @role('super-admin|admin|hr|it|coo|cfo|cms')
            <div class="row mb-3" id="bulkActions" style="display: none;">
                <div class="col-12">
                    <div class="card bg-light">
                        <div class="card-body py-2">
                            <div class="d-flex align-items-center justify-content-between">
                                <span id="selectedCount" class="fw-bold">0 staff selected</span>
                                <div>
                                    <button type="button" class="btn btn-sm btn-success me-2" onclick="bulkActivate()">
                                        <i class="fas fa-check"></i> Activate Selected
                                    </button>
                                    <button type="button" class="btn btn-sm btn-warning me-2" onclick="bulkDeactivate()">
                                        <i class="fas fa-ban"></i> Deactivate Selected
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            @endrole

            {{-- Department Summary Table (Collapsible) --}}
            @if(isset($deptSummaries) && count($deptSummaries) > 0)
            <div class="card mb-3">
                <div class="card-header">
                    <h5 class="mb-0">
                        <button class="btn btn-link text-decoration-none w-100 text-start" type="button" data-bs-toggle="collapse" data-bs-target="#deptSummary">
                            <i class="fas fa-chart-bar me-2"></i>Department Summary
                            <i class="fas fa-chevron-down float-end"></i>
                        </button>
                    </h5>
                </div>
                <div id="deptSummary" class="collapse">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered">
                                <thead>
                                    <tr>
                                        <th>Department</th>
                                        @foreach ($statusColumns ?? [] as $status)
                                            <th class="text-center">{{ ucfirst($status) }}</th>
                                        @endforeach
                                        <th class="text-center"><strong>Total</strong></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($deptSummaries ?? [] as $deptId => $summary)
                                        <tr>
                                            <td><strong>{{ $summary['name'] }}</strong></td>
                                            @foreach ($statusColumns ?? [] as $status)
                                                <td class="text-center">{{ $summary['totals'][$status] ?? 0 }}</td>
                                            @endforeach
                                            <td class="text-center"><strong>{{ $summary['sum'] ?? 0 }}</strong></td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            @endif

            {{-- ===== Users Table ===== --}}
            <div class="card card-body p-3">
                <div class="table-responsive">
                    <table id="example" class="display nowrap" style="width: 100%;">
                        <thead>
                            <tr>
                                <th>
                                    @role('super-admin|admin|hr|it|coo|cfo|cms')
                                    <input type="checkbox" id="selectAll" class="form-check-input">
                                    @endrole
                                </th>
                                <th>#</th>
                                <th>CCBRT Code</th>
                                <th>User Name</th>
                                <th>Phone Number</th>
                                <th>Email</th>
                                <th>Department</th>
                                <th>Job Title</th>
                                <th>Status</th>
                                <th>Professional Reg.</th>
                                <th>License Status</th>
                                <th>User Forms</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($users as $user)
                                @php
                                    $isClinical = optional($user->jobTitle)->clinical_or_non_clinical === 'Clinical';
                                    // Get HR workflow history for license info
                                    $hrWorkflowHistory = null;
                                    if ($isClinical) {
                                        $hrWorkflow = \App\Models\Workflow::where('hr_form', $user->id)->orderBy('id', 'desc')->first();
                                        if ($hrWorkflow) {
                                            $hrWorkflowHistory = \App\Models\WorkFlowHistory::where('work_flow_id', $hrWorkflow->id)
                                                ->whereNotNull('license_valid_until')
                                                ->orderBy('id', 'desc')
                                                ->first();
                                        }
                                    }
                                @endphp
                                <tr>
                                    <td>
                                        @role('super-admin|admin|hr|it|coo|cfo|cms')
                                        <input type="checkbox" class="form-check-input staff-checkbox" value="{{ $user->id }}">
                                        @endrole
                                    </td>
                                    <td>{{ $loop->iteration }}</td>
                                    <td>{{ $user->ccbrt_code ?? 'N/A' }}</td>
                                    <td>{{ $user->fname }} {{ $user->lname }}</td>
                                    <td>{{ $user->mobile ?? 'N/A' }}</td>
                                    <td>{{ $user->email ?? 'N/A' }}</td>
                                    <td>{{ optional($user->department)->dept_name ?? '—' }}</td>
                                    <td>{{ optional($user->jobTitle)->job_title ?? '—' }}</td>
                                    <td>
                                        @php
                                            $statusClass = match($user->status) {
                                                'active' => 'success',
                                                'inactive' => 'secondary',
                                                'pending' => 'warning',
                                                'deactivated' => 'danger',
                                                default => 'secondary'
                                            };
                                        @endphp
                                        <span class="badge bg-{{ $statusClass }}">{{ ucfirst($user->status ?? '—') }}</span>
                                    </td>
                                    <td>
                                        @if ($isClinical)
                                            @if ($user->professional_reg_number)
                                                <span class="badge bg-info">{{ $user->professional_reg_number }}</span>
                                            @else
                                                <span class="badge bg-warning">Not Set</span>
                                            @endif
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if ($isClinical && $hrWorkflowHistory && $hrWorkflowHistory->license_valid_until)
                                            @php
                                                $daysUntilExpiry = \Carbon\Carbon::parse($hrWorkflowHistory->license_valid_until)->diffInDays(\Carbon\Carbon::now(), false);
                                            @endphp
                                            @if ($daysUntilExpiry < 0)
                                                <span class="badge bg-success">Valid</span>
                                            @elseif ($daysUntilExpiry <= 90)
                                                <span class="badge bg-warning">Expiring Soon</span>
                                            @else
                                                <span class="badge bg-danger">Expired</span>
                                            @endif
                                        @elseif ($isClinical)
                                            <span class="badge bg-secondary">Not Set</span>
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>
                                    <td>
                                        <a href="{{ route('employees_details.forms', ['id' => $user->id]) }}"
                                            class="btn btn-outline-secondary btn-sm">
                                            <i class="fas fa-download"></i> Forms
                                        </a>
                                    </td>
                                    <td class="text-center" style="width: 150px;">
                                        <div class="d-flex justify-content-center">
                                            @role('super-admin|admin|hr|it|coo|cfo|cms')
                                                <a href="{{ route('employees_details.show', ['id' => $user->id]) }}"
                                                    class="btn btn-sm btn-outline-success mx-1" title="View Details">
                                                    <i class="fas fa-eye"></i>
                                                </a>

                                                <form action="{{ route('user.edit', $user->id) }}" method="get"
                                                    id="editForm{{ $user->id }}">
                                                    @csrf
                                                    <button type="submit" class="btn btn-sm btn-primary mx-1" title="Edit">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                </form>

                                                @if ($isClinical)
                                                    <button type="button" class="btn btn-sm btn-outline-warning mx-1"
                                                        title="Manage Professional License"
                                                        onclick="openLicenseModal({{ $user->id }}, '{{ addslashes($user->fname . ' ' . $user->lname) }}', '{{ addslashes($user->professional_reg_number ?? '') }}', @if($hrWorkflowHistory){{ json_encode([
                                                            'license_provider' => $hrWorkflowHistory->license_provider,
                                                            'license_valid_until' => $hrWorkflowHistory->license_valid_until,
                                                            'professional_reg_verified' => (bool)$hrWorkflowHistory->professional_reg_verified
                                                        ]) }}@else null @endif)">
                                                        <i class="fas fa-certificate"></i>
                                                    </button>
                                                @endif
                                            @endrole
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr>
                                <th></th>
                                <th>#</th>
                                <th>CCBRT Code</th>
                                <th>User Name</th>
                                <th>Phone Number</th>
                                <th>Email</th>
                                <th>Department</th>
                                <th>Job Title</th>
                                <th>Status</th>
                                <th>Professional Reg.</th>
                                <th>License Status</th>
                                <th>User Forms</th>
                                <th>Actions</th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

        </div>
    </div>

    {{-- Professional License Management Modal --}}
    <div class="modal fade" id="licenseModal" tabindex="-1" aria-labelledby="licenseModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-warning text-dark">
                    <h5 class="modal-title" id="licenseModalLabel">
                        <i class="fas fa-certificate me-2"></i>Manage Professional License
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="licenseForm" method="POST">
                    @csrf
                    <div class="modal-body">
                        <input type="hidden" id="license_user_id" name="user_id">
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            <strong>Staff:</strong> <span id="license_user_name"></span>
                        </div>

                        <div class="mb-3">
                            <label for="professional_reg_number" class="form-label">
                                Professional Registration Number <span class="text-danger">*</span>
                            </label>
                            <input type="text" class="form-control" id="professional_reg_number"
                                name="professional_reg_number" placeholder="e.g., MCT: 12345" required>
                            <small class="form-text text-muted">
                                Format: Type:Number (e.g., MCT:12345, TNMC:67890)
                            </small>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="license_provider" class="form-label">
                                        License Provider/Authority <span class="text-danger">*</span>
                                    </label>
                                    <select class="form-control" id="license_provider" name="license_provider" required>
                                        <option value="">--- Select Provider ---</option>
                                        <option value="Medical Council of Tanzania">Medical Council of Tanzania (MCT)</option>
                                        <option value="Tanzania Nursing and Midwifery Council">Tanzania Nursing and Midwifery Council (TNMC)</option>
                                        <option value="Tanzania Pharmacy Board">Tanzania Pharmacy Board (TPB)</option>
                                        <option value="Tanzania Physiotherapy Council">Tanzania Physiotherapy Council (TPC)</option>
                                        <option value="Tanzania Medical and Dental Council">Tanzania Medical and Dental Council (TMDC)</option>
                                        <option value="Other">Other</option>
                                    </select>
                                    <small class="form-text text-muted">
                                        Select the licensing authority for this professional registration
                                    </small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="license_valid_until" class="form-label">
                                        License Valid Until <span class="text-danger">*</span>
                                    </label>
                                    <input type="date" class="form-control" id="license_valid_until"
                                        name="license_valid_until" min="{{ date('Y-m-d') }}" required>
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="professional_reg_verified"
                                    name="professional_reg_verified" value="1">
                                <label class="form-check-label" for="professional_reg_verified">
                                    <strong>I verify that the professional registration number is active, valid, and the user is working under a provider of this license.</strong>
                                </label>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-warning">
                            <i class="fas fa-save me-2"></i>Save License Information
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- JS Libraries --}}
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.7.1.js"></script>
    <script src="https://cdn.datatables.net/2.1.2/js/dataTables.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/pdfmake.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/vfs_fonts.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/3.1.0/js/dataTables.buttons.js"></script>
    <script src="https://cdn.datatables.net/buttons/3.1.0/js/buttons.dataTables.js"></script>
    <script src="https://cdn.datatables.net/buttons/3.1.0/js/buttons.print.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/3.1.0/js/buttons.html5.min.js"></script>

    <script>
        // Show loader when BioTime button is clicked
        $('#bioTimeBtn').on('click', function() {
            $('#loadingWrapper').show();
        });

        // Initialize DataTable
        let staffTable = new DataTable('#example', {
            scrollX: true,
            scrollCollapse: false,
            pageLength: 50,
            lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
            order: [[2, 'asc']], // Order by CCBRT Code
            columnDefs: [
                { orderable: false, targets: [0, -1] }, // Disable sorting on checkbox and action columns
                { targets: -1, className: 'text-nowrap' } // Prevent action column from wrapping
            ],
            dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>rt<"row"<"col-sm-12 col-md-6"i><"col-sm-12 col-md-6"p>>',
            language: {
                info: "Showing _START_ to _END_ of _TOTAL_ entries",
                infoEmpty: "Showing 0 to 0 of 0 entries",
                infoFiltered: "(filtered from _MAX_ total entries)"
            }
        });

        // Create export buttons (hidden)
        // Export columns: CCBRT Code(2), User Name(3), Phone Number(4), Email(5), Department(6), Job Title(7), Status(8)
        let exportButtons = new $.fn.dataTable.Buttons(staffTable, {
            buttons: [
                {
                    extend: 'excel',
                    text: 'Excel',
                    className: 'buttons-excel',
                    exportOptions: {
                        columns: [2, 3, 4, 5, 6, 7, 8, 9, 10]
                    }
                },
                {
                    extend: 'csv',
                    text: 'CSV',
                    className: 'buttons-csv',
                    exportOptions: {
                        columns: [2, 3, 4, 5, 6, 7, 8, 9, 10]
                    }
                },
                {
                    extend: 'pdf',
                    text: 'PDF',
                    className: 'buttons-pdf',
                    exportOptions: {
                        columns: [2, 3, 4, 5, 6, 7, 8, 9, 10]
                    }
                },
                {
                    extend: 'print',
                    text: 'Print',
                    className: 'buttons-print',
                    exportOptions: {
                        columns: [2, 3, 4, 5, 6, 7, 8, 9, 10]
                    }
                }
            ]
        }).container().appendTo($('#exportButtonContainer'));

        // Export function
        function showExportOptions() {
            Swal.fire({
                title: '<i class="fas fa-download text-primary me-2"></i>Export Data',
                html: `
                    <div class="text-center mb-3">
                        <p class="text-muted mb-3">Choose your preferred export format:</p>
                        <div class="row g-2">
                            <div class="col-6">
                                <button id="export-excel-btn" class="btn btn-success w-100 p-2 export-option-btn">
                                    <i class="fas fa-file-excel me-2"></i>
                                    <span>Excel</span>
                                </button>
                            </div>
                            <div class="col-6">
                                <button id="export-pdf-btn" class="btn btn-danger w-100 p-2 export-option-btn">
                                    <i class="fas fa-file-pdf me-2"></i>
                                    <span>PDF</span>
                                </button>
                            </div>
                            <div class="col-6">
                                <button id="export-csv-btn" class="btn btn-info w-100 p-2 export-option-btn">
                                    <i class="fas fa-file-csv me-2"></i>
                                    <span>CSV</span>
                                </button>
                            </div>
                            <div class="col-6">
                                <button id="export-print-btn" class="btn btn-secondary w-100 p-2 export-option-btn">
                                    <i class="fas fa-print me-2"></i>
                                    <span>Print</span>
                                </button>
                            </div>
                        </div>
                    </div>
                `,
                icon: null,
                showConfirmButton: false,
                showCancelButton: true,
                cancelButtonText: '<i class="fas fa-times me-2"></i> Cancel',
                cancelButtonColor: '#6c757d',
                showCloseButton: true,
                width: '400px',
                didOpen: () => {
                    // Add hover effects
                    document.querySelectorAll('.export-option-btn').forEach(btn => {
                        btn.addEventListener('mouseenter', function() {
                            this.style.transform = 'scale(1.02)';
                            this.style.transition = 'transform 0.2s';
                        });
                        btn.addEventListener('mouseleave', function() {
                            this.style.transform = 'scale(1)';
                        });
                    });

                    document.getElementById('export-excel-btn')?.addEventListener('click', () => {
                        Swal.close();
                        exportToFormat('excel');
                    });
                    document.getElementById('export-pdf-btn')?.addEventListener('click', () => {
                        Swal.close();
                        exportToFormat('pdf');
                    });
                    document.getElementById('export-csv-btn')?.addEventListener('click', () => {
                        Swal.close();
                        exportToFormat('csv');
                    });
                    document.getElementById('export-print-btn')?.addEventListener('click', () => {
                        Swal.close();
                        exportToFormat('print');
                    });
                }
            });
        }

        function exportToFormat(format) {
            try {
                switch(format) {
                    case 'excel':
                        staffTable.button('.buttons-excel').trigger();
                        break;
                    case 'csv':
                        staffTable.button('.buttons-csv').trigger();
                        break;
                    case 'pdf':
                        staffTable.button('.buttons-pdf').trigger();
                        break;
                    case 'print':
                        staffTable.button('.buttons-print').trigger();
                        break;
                }
            } catch(e) {
                console.error('Export error:', e);
                Swal.fire('Error', 'Failed to export data. Please try again.', 'error');
            }
        }

        // Advanced Filters
        $('#filterDepartment, #filterStatus, #filterLicenseStatus').on('change', function() {
            staffTable.draw();
        });

        $('#searchInput').on('keyup', function() {
            staffTable.search(this.value).draw();
        });

            $.fn.dataTable.ext.search.push(
                function(settings, data, dataIndex) {
                    var department = $('#filterDepartment').val();
                    var status = $('#filterStatus').val();
                    var licenseStatus = $('#filterLicenseStatus').val();

                    var rowDepartment = data[6] || ''; // Column index for Department
                    var rowStatus = data[8] || ''; // Column index for Status
                    var rowLicenseStatus = data[10] || ''; // Column index for License Status

                    if (department && !rowDepartment.includes(department)) return false;
                    if (status && !rowStatus.toLowerCase().includes(status.toLowerCase())) return false;

                    // Filter by license status
                    if (licenseStatus) {
                        // Check if the row's license status matches the filter
                        var licenseStatusText = rowLicenseStatus.toLowerCase().trim();
                        var filterText = licenseStatus.toLowerCase().trim();

                        // Handle different badge text formats
                        if (filterText === 'valid') {
                            // Must contain "valid" but not "expir" (to exclude "expiring soon")
                            if (!licenseStatusText.includes('valid') || licenseStatusText.includes('expir')) return false;
                        } else if (filterText === 'expiring soon') {
                            // Must contain "expiring"
                            if (!licenseStatusText.includes('expiring')) return false;
                        } else if (filterText === 'expired') {
                            // Must contain "expired"
                            if (!licenseStatusText.includes('expired')) return false;
                        } else if (filterText === 'not set') {
                            // Must contain "not set" or be empty/dash (for non-clinical users)
                            if (!licenseStatusText.includes('not set') && !licenseStatusText.includes('—') && licenseStatusText !== '') return false;
                        }
                    }

                    return true;
                }
            );

        function clearFilters() {
            $('#filterDepartment, #filterStatus, #filterLicenseStatus').val('');
            $('#searchInput').val('');
            staffTable.search('').draw();
            // Remove active state from cards
            $('.stats-card').removeClass('active');
        }

        // Filter by status when clicking on stats cards
        function filterByStatus(status) {
            if (status) {
                $('#filterStatus').val(status).trigger('change');
                // Add active state to clicked card
                $('.stats-card').removeClass('active');
                event.currentTarget.classList.add('active');
            } else {
                // Show all users
                $('#filterStatus').val('').trigger('change');
                $('.stats-card').removeClass('active');
            }
        }

        // Bulk Selection
        $('#selectAll').on('change', function() {
            $('.staff-checkbox').prop('checked', this.checked);
            updateBulkActions();
        });

        $('.staff-checkbox').on('change', function() {
            updateBulkActions();
            $('#selectAll').prop('checked', $('.staff-checkbox:checked').length === $('.staff-checkbox').length);
        });

        function updateBulkActions() {
            var count = $('.staff-checkbox:checked').length;
            $('#selectedCount').text(count + ' staff selected');
            $('#bulkActions').toggle(count > 0);
        }

        // Bulk Actions
        function bulkActivate() {
            var ids = $('.staff-checkbox:checked').map(function() { return this.value; }).get();
            if (ids.length === 0) return;
            performBulkAction(ids, 'activate', 'Activate');
        }

        function bulkDeactivate() {
            var ids = $('.staff-checkbox:checked').map(function() { return this.value; }).get();
            if (ids.length === 0) return;
            performBulkAction(ids, 'deactivate', 'Deactivate');
        }

        function performBulkAction(ids, action, label) {
            $.ajax({
                url: '{{ route("staff.bulk-action") }}',
                method: 'POST',
                data: {
                    _token: '{{ csrf_token() }}',
                    ids: ids,
                    action: action
                },
                success: function(response) {
                    Swal.fire('Success!', response.message || `${label} completed.`, 'success')
                        .then(() => location.reload());
                },
                error: function(xhr) {
                    Swal.fire('Error!', xhr.responseJSON?.message || 'An error occurred.', 'error');
                }
            });
        }

        // Professional License Management
        function openLicenseModal(userId, userName, professionalRegNumber, licenseData) {
            $('#license_user_id').val(userId);
            $('#license_user_name').text(userName);
            $('#professional_reg_number').val(professionalRegNumber || '');
            $('#licenseForm').attr('action', '{{ route("staff.manage-license") }}');

            // Set license data if available
            if (licenseData) {
                $('#license_provider').val(licenseData.license_provider || '');
                $('#license_valid_until').val(licenseData.license_valid_until || '');
                $('#professional_reg_verified').prop('checked', licenseData.professional_reg_verified || false);
            } else {
                $('#license_provider').val('');
                $('#license_valid_until').val('');
                $('#professional_reg_verified').prop('checked', false);
            }

            // Reset form and set values
            $('#licenseForm')[0].reset();
            $('#license_user_id').val(userId);
            $('#license_user_name').text(userName);
            if (professionalRegNumber) {
                $('#professional_reg_number').val(professionalRegNumber);
                // Suggest provider based on registration number
                suggestLicenseProvider(professionalRegNumber);
            }
            if (licenseData) {
                $('#license_provider').val(licenseData.license_provider || '');
                $('#license_valid_until').val(licenseData.license_valid_until || '');
                $('#professional_reg_verified').prop('checked', licenseData.professional_reg_verified || false);
            }

            $('#licenseModal').modal('show');
        }

        // Auto-suggest provider from registration number (optional helper)
        function suggestLicenseProvider(regNumber) {
            const licenseProviders = {
                'MCT': 'Medical Council of Tanzania',
                'TNMC': 'Tanzania Nursing and Midwifery Council',
                'TPB': 'Tanzania Pharmacy Board',
                'TPC': 'Tanzania Physiotherapy Council',
                'TMDC': 'Tanzania Medical and Dental Council',
                'Other': 'Other'
            };

            if (regNumber) {
                const match = regNumber.match(/^([A-Z]+):/);
                if (match) {
                    const regType = match[1];
                    const suggestedProvider = licenseProviders[regType] || 'Other';
                    // Set the select value if it matches
                    const $select = $('#license_provider');
                    if ($select.find(`option:contains("${suggestedProvider}")`).length > 0) {
                        $select.val(suggestedProvider);
                    }
                }
            }
        }

        // Optional: Auto-suggest provider when registration number changes
        $('#professional_reg_number').on('input', function() {
            suggestLicenseProvider($(this).val());
        });

        // Handle form submission
        $('#licenseForm').on('submit', function(e) {
            e.preventDefault();

            const formData = $(this).serialize();
            const userId = $('#license_user_id').val();

            $.ajax({
                url: $(this).attr('action'),
                method: 'POST',
                data: formData,
                success: function(response) {
                    Swal.fire('Success!', response.message || 'License information saved successfully.', 'success')
                        .then(() => {
                            $('#licenseModal').modal('hide');
                            location.reload();
                        });
                },
                error: function(xhr) {
                    let errorMessage = 'An error occurred while saving license information.';
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        errorMessage = xhr.responseJSON.message;
                    } else if (xhr.responseJSON && xhr.responseJSON.errors) {
                        const errors = Object.values(xhr.responseJSON.errors).flat();
                        errorMessage = errors.join('<br>');
                    }
                    Swal.fire('Error!', errorMessage, 'error');
                }
            });
        });
    </script>
@endsection
