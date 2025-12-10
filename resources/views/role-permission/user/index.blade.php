{{-- resources/views/users/index.blade.php --}}
@extends('layouts.template')

@section('breadcrumb')
    @include('sweetalert::alert')

    {{-- DataTables + Buttons + SweetAlert2 + Font Awesome --}}
    <link rel="stylesheet" href="https://cdn.datatables.net/2.1.2/css/dataTables.dataTables.css" />
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/3.1.0/css/buttons.dataTables.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">

    <style>
        .swal2-popup {
            border-radius: 12px !important
        }

        .swal2-title {
            font-size: 1.15rem !important
        }

        .swal2-html-container {
            text-align: left !important
        }

        .swal2-select,
        .swal2-input,
        .swal2-textarea {
            width: 100% !important;
            border: 1px solid #ced4da !important;
            border-radius: 8px !important;
            font-size: 14px !important;
            padding: .6rem .75rem !important
        }

        .swal2-label {
            font-weight: 600;
            margin-bottom: .35rem;
            display: block
        }

        .badge-role {
            display: inline-block;
            padding: .15rem .4rem;
            border-radius: 6px;
            font-size: 12px;
            color: #fff;
            background: #60656b
        }

        .dt-container .dt-buttons {
            margin-bottom: .5rem
        }

        .spinner {
            display: inline-block;
            width: 14px;
            height: 14px;
            border: 2px solid #fff;
            border-top-color: transparent;
            border-radius: 50%;
            animation: spin .7s linear infinite;
            vertical-align: -2px;
            margin-left: .4rem
        }

        @keyframes spin {
            to {
                transform: rotate(360deg)
            }
        }

        .user-details-modal .modal-body {
            max-height: 70vh;
            overflow-y: auto;
        }

        .detail-row {
            padding: 0.5rem 0;
            border-bottom: 1px solid #eee;
        }

        .detail-label {
            font-weight: 600;
            color: #666;
        }

        /* Ensure action column is always visible and clickable */
        #usersTable th:last-child,
        #usersTable td:last-child {
            position: sticky;
            right: 0;
            background-color: white;
            z-index: 10;
            min-width: 150px;
        }

        #usersTable th:last-child {
            background-color: #f8f9fa;
            z-index: 11;
        }

        #usersTable td:last-child {
            box-shadow: -2px 0 5px rgba(0, 0, 0, 0.1);
        }

        /* Ensure buttons are clickable */
        #usersTable td:last-child .btn {
            pointer-events: auto;
            z-index: 12;
            position: relative;
        }
    </style>

    <div class="page-wrapper">
        <div class="content container-fluid">
            <div class="row">
                <div class="col-sm-12 d-flex align-items-center justify-content-between">
                    <h3 class="page-title mb-0">Users</h3>
                </div>
            </div>

            @if (session('error'))
                <div class="alert alert-danger alert-dismissible fade show mt-2" role="alert">
                    {!! session('error') !!}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            @if (session('success'))
                <div class="alert alert-success alert-dismissible fade show mt-2" role="alert">
                    {!! session('success') !!}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            <div class="row">
                <div class="card mt-3">
                    <div class="card-body">
                        @php
                            $sortedUsers = $users->sortBy('username', SORT_NATURAL | SORT_FLAG_CASE);
                        @endphp

                        {{-- Advanced Filters and Export --}}
                        <div class="row mb-3">
                            <div class="col-md-3">
                                <label class="form-label">Filter by Department</label>
                                <select id="filterDepartment" class="form-select form-select-sm">
                                    <option value="">All Departments</option>
                                    @foreach ($departments ?? [] as $dept)
                                        <option value="{{ $dept->dept_name }}">{{ $dept->dept_name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Filter by Status</label>
                                <select id="filterStatus" class="form-select form-select-sm">
                                    <option value="">All Statuses</option>
                                    <option value="active">Active</option>
                                    <option value="inactive">Inactive</option>
                                    <option value="pending">Pending</option>
                                    <option value="deactivated">Deactivated</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Filter by Role</label>
                                <select id="filterRole" class="form-select form-select-sm">
                                    <option value="">All Roles</option>
                                    @foreach ($allRoles ?? [] as $role)
                                        <option value="{{ $role }}">{{ $role }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3 d-flex align-items-end gap-2">
                                <button type="button" class="btn btn-sm btn-secondary flex-fill" onclick="clearFilters()">
                                    <i class="fas fa-times"></i> Clear Filters
                                </button>
                                <button type="button" class="btn btn-sm btn-primary flex-fill"
                                    onclick="showExportOptions()">
                                    <i class="fas fa-download"></i> Export
                                </button>
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
                                                <span id="selectedCount" class="fw-bold">0 users selected</span>
                                                <div>
                                                    <button type="button" class="btn btn-sm btn-success me-2"
                                                        onclick="bulkActivate()">
                                                        <i class="fas fa-check"></i> Activate Selected
                                                    </button>
                                                    <button type="button" class="btn btn-sm btn-warning me-2"
                                                        onclick="bulkDeactivate()">
                                                        <i class="fas fa-ban"></i> Deactivate Selected
                                                    </button>
                                                    <button type="button" class="btn btn-sm btn-danger" onclick="bulkDelete()">
                                                        <i class="fas fa-trash"></i> Delete Selected
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endrole

                        <table id="usersTable" class="display nowrap" style="width:100%">
                            <thead>
                                <tr>
                                    <th>
                                        @role('super-admin|admin|hr|it|coo|cfo|cms')
                                            <input type="checkbox" id="selectAll" class="form-check-input">
                                        @endrole
                                    </th>
                                    <th>No</th>
                                    <th>CCBRT Code</th>
                                    <th>Name</th>
                                    <th>Department</th>
                                    <th>Status</th>
                                    <th>Lock Status</th>
                                    <th>Roles</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($sortedUsers as $user)
                                    <tr>
                                        <td>
                                            @role('super-admin|admin|hr|it|coo|cfo|cms')
                                                <input type="checkbox" class="form-check-input user-checkbox"
                                                    value="{{ $user->id }}"
                                                    @if($user->hasRole('super-admin')) disabled title="Super-admin users cannot be deactivated" @endif>
                                            @endrole
                                        </td>
                                        <td>{{ $loop->iteration }}</td>
                                        <td>{{ $user->ccbrt_code ?? 'N/A' }}</td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <button type="button" class="btn btn-link p-0 text-decoration-none"
                                                    onclick="showUserDetails({{ $user->id }})" title="Quick View">
                                                    {{ $user->username }}
                                                </button>
                                            </div>
                                        </td>
                                        <td>{{ $user->department ? $user->department->dept_name : 'None' }}</td>
                                        <td>
                                            @php
                                                $statusClass = match ($user->status) {
                                                    'active' => 'success',
                                                    'inactive' => 'secondary',
                                                    'pending' => 'warning',
                                                    'deactivated' => 'danger',
                                                    default => 'secondary',
                                                };
                                            @endphp
                                            <span
                                                class="badge bg-{{ $statusClass }}">{{ ucfirst($user->status ?? 'N/A') }}</span>
                                        </td>
                                        <td>
                                            @if ($user->is_locked && $user->locked_until)
                                                @php
                                                    $lockedUntil = \Carbon\Carbon::parse($user->locked_until);
                                                    $isStillLocked = \Carbon\Carbon::now()->lt($lockedUntil);
                                                @endphp
                                                @if ($isStillLocked)
                                                    @php
                                                        $minutesRemaining = \Carbon\Carbon::now()->diffInMinutes(
                                                            $lockedUntil,
                                                        );
                                                        $hoursRemaining = floor($minutesRemaining / 60);
                                                        $minsRemaining = $minutesRemaining % 60;
                                                    @endphp
                                                    <span class="badge bg-danger"
                                                        title="Locked until {{ $lockedUntil->format('Y-m-d H:i:s') }}">
                                                        <i class="fas fa-lock me-1"></i>Locked
                                                        @if ($hoursRemaining > 0)
                                                            ({{ $hoursRemaining }}h {{ $minsRemaining }}m)
                                                        @else
                                                            ({{ $minsRemaining }}m)
                                                        @endif
                                                    </span>
                                                    <br>
                                                    <small class="text-muted">Failed:
                                                        {{ $user->failed_login_attempts ?? 0 }}
                                                        attempts</small>
                                                @else
                                                    <span class="badge bg-success">
                                                        <i class="fas fa-unlock me-1"></i>Unlocked
                                                    </span>
                                                @endif
                                            @else
                                                <span class="badge bg-success">
                                                    <i class="fas fa-unlock me-1"></i>Unlocked
                                                </span>
                                            @endif
                                        </td>
                                        <td>
                                            @if (!empty($user->getRoleNames()))
                                                @foreach ($user->getRoleNames() as $rolename)
                                                    <span class="badge-role">{{ $rolename }}</span>
                                                @endforeach
                                            @else
                                                <span class="text-muted">No roles</span>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="d-flex justify-content-center">
                                                <button type="button" onclick="showUserDetails({{ $user->id }})"
                                                    class="btn btn-sm mx-1" title="Quick View">
                                                    <i class="fas fa-eye text-info"></i>
                                                </button>
                                                <a href="{{ route('users.edit', $user->id) }}" class="btn btn-sm mx-1"
                                                    title="Edit">
                                                    <i class="fas fa-edit text-success"></i>
                                                </a>

                                                @role('super-admin|admin|hr|it|coo|cfo|cms')
                                                    @if ($user->is_locked && $user->locked_until)
                                                        @php
                                                            $lockedUntil = \Carbon\Carbon::parse($user->locked_until);
                                                            $isStillLocked = \Carbon\Carbon::now()->lt($lockedUntil);
                                                        @endphp
                                                        @if ($isStillLocked)
                                                            <form action="{{ route('users.unlock', $user->id) }}"
                                                                method="POST" style="display:inline;">
                                                                @csrf
                                                                @method('POST')
                                                                <button type="submit" class="btn btn-sm mx-1"
                                                                    title="Unlock Account"
                                                                    onclick="return confirm('Are you sure you want to unlock this user account?')">
                                                                    <i class="fas fa-unlock text-success"></i>
                                                                </button>
                                                            </form>
                                                        @endif
                                                    @endif
                                                    <button onclick="resetPasswordConfirmation('{{ $user->id }}')"
                                                        class="btn btn-sm mx-1" title="Reset Password (Email)">
                                                        <i class="fas fa-key text-warning"></i>
                                                    </button>
                                                    <form id="reset-form-{{ $user->id }}"
                                                        action="{{ route('users.reset-password', $user->id) }}"
                                                        method="POST" style="display:none;">
                                                        @csrf
                                                        @method('POST')
                                                    </form>

                                                    @if (!$user->hasAnyRole(['super-admin', 'line-manager', 'hr', 'it', 'coo', 'cfo', 'cms']))
                                                        <button onclick="changePasswordConfirmation('{{ $user->id }}')"
                                                            class="btn btn-sm mx-1" title="Manual Password Reset">
                                                            <i class="fas fa-lock text-primary"></i>
                                                        </button>
                                                        <form id="change-password-form-{{ $user->id }}"
                                                            action="{{ route('users.change-password', $user->id) }}"
                                                            method="POST" style="display: none;">
                                                            @csrf
                                                            @method('POST')
                                                            <input type="hidden" name="password"
                                                                id="password-input-{{ $user->id }}">
                                                        </form>
                                                    @endif
                                                @endrole
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        {{-- Scripts --}}
        <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
        <script src="https://cdn.datatables.net/2.1.2/js/dataTables.js"></script>
        <script src="https://cdn.datatables.net/buttons/3.1.0/js/dataTables.buttons.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/pdfmake.min.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/vfs_fonts.js"></script>
        <script src="https://cdn.datatables.net/buttons/3.1.0/js/buttons.html5.min.js"></script>
        <script src="https://cdn.datatables.net/buttons/3.1.0/js/buttons.print.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js"></script>

        {{-- User Details Modal --}}
        <div class="modal fade user-details-modal" id="userDetailsModal" tabindex="-1">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">User Details</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body" id="userDetailsContent">
                        <div class="text-center">
                            <div class="spinner-border" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <script>
            // ====== DataTable ======
            let usersTable = new DataTable('#usersTable', {
                scrollX: true,
                scrollCollapse: false,
                order: [
                    [2, 'asc']
                ], // Order by CCBRT Code
                pageLength: 50,
                lengthMenu: [
                    [10, 25, 50, 100, -1],
                    [10, 25, 50, 100, "All"]
                ],
                columnDefs: [{
                        targets: [1],
                        visible: false
                    }, // Hide No column
                    {
                        orderable: false,
                        targets: [0, -1]
                    }, // Disable sorting on checkbox and action columns
                    {
                        targets: -1,
                        className: 'text-nowrap'
                    } // Prevent action column from wrapping
                ],
                dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>rt<"row"<"col-sm-12 col-md-6"i><"col-sm-12 col-md-6"p>>',
                language: {
                    info: "Showing _START_ to _END_ of _TOTAL_ entries",
                    infoEmpty: "Showing 0 to 0 of 0 entries",
                    infoFiltered: "(filtered from _MAX_ total entries)"
                }
            });

            // Create export buttons (hidden)
            // Export columns: CCBRT Code(2), Name(3), Department(4), Status(5), Lock Status(6), Roles(7)
            let exportButtons = new $.fn.dataTable.Buttons(usersTable, {
                buttons: [{
                        extend: 'excel',
                        text: 'Excel',
                        className: 'buttons-excel',
                        exportOptions: {
                            columns: [2, 3, 4, 5, 6, 7]
                        }
                    },
                    {
                        extend: 'csv',
                        text: 'CSV',
                        className: 'buttons-csv',
                        exportOptions: {
                            columns: [2, 3, 4, 5, 6, 7]
                        }
                    },
                    {
                        extend: 'pdf',
                        text: 'PDF',
                        className: 'buttons-pdf',
                        exportOptions: {
                            columns: [2, 3, 4, 5, 6, 7]
                        }
                    },
                    {
                        extend: 'print',
                        text: 'Print',
                        className: 'buttons-print',
                        exportOptions: {
                            columns: [2, 3, 4, 5, 6, 7]
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
                    switch (format) {
                        case 'excel':
                            usersTable.button('.buttons-excel').trigger();
                            break;
                        case 'csv':
                            usersTable.button('.buttons-csv').trigger();
                            break;
                        case 'pdf':
                            usersTable.button('.buttons-pdf').trigger();
                            break;
                        case 'print':
                            usersTable.button('.buttons-print').trigger();
                            break;
                    }
                } catch (e) {
                    console.error('Export error:', e);
                    Swal.fire('Error', 'Failed to export data. Please try again.', 'error');
                }
            }

            // Advanced Filters
            $('#filterDepartment, #filterStatus, #filterRole').on('change', function() {
                usersTable.draw();
            });

            $.fn.dataTable.ext.search.push(
                function(settings, data, dataIndex) {
                    var department = $('#filterDepartment').val();
                    var status = $('#filterStatus').val();
                    var role = $('#filterRole').val();

                    // Column indices: 0=Checkbox, 1=No(hidden), 2=CCBRT Code, 3=Name, 4=Department, 5=Status, 6=Lock Status, 7=Roles, 8=Action
                    var rowDepartment = data[4] || ''; // Department column
                    var rowStatus = data[5] || ''; // Status column
                    var rowRoles = data[7] || ''; // Roles column

                    // Remove HTML tags from roles for better matching
                    var rowRolesText = $('<div>').html(rowRoles).text().toLowerCase();

                    if (department && !rowDepartment.includes(department)) return false;
                    if (status && !rowStatus.toLowerCase().includes(status.toLowerCase())) return false;
                    if (role && !rowRolesText.includes(role.toLowerCase())) return false;

                    return true;
                }
            );

            function clearFilters() {
                $('#filterDepartment, #filterStatus, #filterRole').val('');
                usersTable.draw();
            }

            // Bulk Selection
            $('#selectAll').on('change', function() {
                $('.user-checkbox').prop('checked', this.checked);
                updateBulkActions();
            });

            $('.user-checkbox').on('change', function() {
                updateBulkActions();
                $('#selectAll').prop('checked', $('.user-checkbox:checked').length === $('.user-checkbox').length);
            });

            function updateBulkActions() {
                var count = $('.user-checkbox:checked').length;
                $('#selectedCount').text(count + ' user(s) selected');
                $('#bulkActions').toggle(count > 0);
            }

            // Bulk Actions
            function bulkActivate() {
                var ids = $('.user-checkbox:checked').map(function() {
                    return this.value;
                }).get();
                if (ids.length === 0) return;
                performBulkAction(ids, 'activate', 'Activate');
            }

            function bulkDeactivate() {
                var ids = $('.user-checkbox:checked').map(function() {
                    return this.value;
                }).get();
                if (ids.length === 0) return;
                
                // Filter out super-admin users from selection
                const superAdminIds = [];
                const regularIds = [];
                
                ids.forEach(id => {
                    const checkbox = document.querySelector(`input.user-checkbox[value="${id}"]`);
                    if (checkbox && checkbox.disabled) {
                        superAdminIds.push(id);
                    } else {
                        regularIds.push(id);
                    }
                });
                
                if (superAdminIds.length > 0 && regularIds.length === 0) {
                    Swal.fire('Cannot Deactivate', 'Super-admin users cannot be deactivated.', 'error');
                    return;
                }
                
                if (superAdminIds.length > 0) {
                    Swal.fire({
                        title: 'Super-admin Users Excluded',
                        html: `${regularIds.length} user(s) will be deactivated. ${superAdminIds.length} super-admin user(s) cannot be deactivated.`,
                        icon: 'info',
                        showCancelButton: true,
                        confirmButtonText: 'Continue',
                        cancelButtonText: 'Cancel'
                    }).then((result) => {
                        if (result.isConfirmed && regularIds.length > 0) {
                            performBulkAction(regularIds, 'deactivate', 'Deactivate');
                        }
                    });
                } else {
                    performBulkAction(regularIds, 'deactivate', 'Deactivate');
                }
            }

            function bulkDelete() {
                var ids = $('.user-checkbox:checked').map(function() {
                    return this.value;
                }).get();
                if (ids.length === 0) return;

                Swal.fire({
                    title: 'Delete Users?',
                    text: `Are you sure you want to delete ${ids.length} user(s)?`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    confirmButtonText: 'Yes, delete them!'
                }).then((result) => {
                    if (result.isConfirmed) {
                        performBulkAction(ids, 'delete', 'Delete');
                    }
                });
            }

            function performBulkAction(ids, action, label) {
                // Double-check: filter out any super-admin users that might have been selected
                const filteredIds = ids.filter(id => {
                    const checkbox = document.querySelector(`input.user-checkbox[value="${id}"]`);
                    return !(checkbox && checkbox.disabled);
                });
                
                if (filteredIds.length === 0) {
                    Swal.fire('No Valid Selection', 'No valid users selected for this action.', 'warning');
                    return;
                }
                
                $.ajax({
                    url: '{{ route('users.bulk-action') }}',
                    method: 'POST',
                    data: {
                        _token: '{{ csrf_token() }}',
                        ids: filteredIds,
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

            // Quick View Modal
            function showUserDetails(userId) {
                $('#userDetailsContent').html('<div class="text-center"><div class="spinner-border"></div></div>');
                var modal = new bootstrap.Modal(document.getElementById('userDetailsModal'));
                modal.show();

                $.ajax({
                    url: `/users/${userId}/details`,
                    method: 'GET',
                    success: function(data) {
                        $('#userDetailsContent').html(data);
                    },
                    error: function() {
                        $('#userDetailsContent').html(
                            '<div class="alert alert-danger">Failed to load user details.</div>');
                    }
                });
            }

            // ====== Password helpers ======
            function resetPasswordConfirmation(userId) {
                Swal.fire({
                    title: 'Reset Password',
                    text: "Send a password reset to user's email?",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Yes, send',
                    cancelButtonText: 'Cancel'
                }).then((result) => {
                    if (result.isConfirmed) {
                        document.getElementById('reset-form-' + userId).submit();
                    }
                });
            }

            function changePasswordConfirmation(userId) {
                Swal.fire({
                    title: '<i class="fas fa-key text-primary me-2"></i>Change User Password',
                    html: `
                        <div class="text-start">
                            <div class="alert alert-info mb-3">
                                <i class="fas fa-info-circle me-2"></i>
                                <small>Enter a new password for this user. Minimum 6 characters required.</small>
                            </div>
                            <label class="form-label fw-bold mb-2" for="swal-password">
                                <i class="fas fa-lock me-2 text-muted"></i>New Password
                            </label>
                            <div class="position-relative">
                                <input type="password"
                                    id="swal-password"
                                    class="form-control"
                                    value="123.ccbrt"
                                    minlength="6"
                                    required
                                    placeholder="Enter new password"
                                    style="padding-right: 45px; font-size: 14px;">
                                <button type="button"
                                    id="toggle-password"
                                    class="btn btn-link position-absolute end-0 top-50 translate-middle-y p-0 pe-2"
                                    style="border: none; background: none; color: #6c757d; text-decoration: none;"
                                    title="Toggle password visibility">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                            <div id="password-strength" class="mt-2" style="font-size: 12px;"></div>
                        </div>
                    `,
                    icon: null,
                    focusConfirm: false,
                    showCancelButton: true,
                    confirmButtonText: '<i class="fas fa-save me-2"></i>Change Password',
                    confirmButtonColor: '#007bff',
                    cancelButtonText: '<i class="fas fa-times me-2"></i>Cancel',
                    cancelButtonColor: '#6c757d',
                    width: '450px',
                    didOpen: () => {
                        const input = document.getElementById('swal-password');
                        const toggleBtn = document.getElementById('toggle-password');
                        const strengthDiv = document.getElementById('password-strength');

                        // Password visibility toggle
                        toggleBtn.addEventListener('click', (e) => {
                            e.preventDefault();
                            if (input.type === 'password') {
                                input.type = 'text';
                                toggleBtn.querySelector('i').classList.replace('fa-eye', 'fa-eye-slash');
                                toggleBtn.setAttribute('title', 'Hide password');
                            } else {
                                input.type = 'password';
                                toggleBtn.querySelector('i').classList.replace('fa-eye-slash', 'fa-eye');
                                toggleBtn.setAttribute('title', 'Show password');
                            }
                        });

                        // Password strength indicator
                        input.addEventListener('input', function() {
                            const val = this.value;
                            if (val.length === 0) {
                                strengthDiv.innerHTML = '';
                                return;
                            }

                            let strength = '';
                            let color = '';
                            if (val.length < 6) {
                                strength =
                                    '<i class="fas fa-exclamation-circle me-1"></i>Too short (minimum 6 characters)';
                                color = 'text-danger';
                            } else if (val.length < 8) {
                                strength = '<i class="fas fa-check-circle me-1"></i>Weak';
                                color = 'text-warning';
                            } else if (val.length < 12) {
                                strength = '<i class="fas fa-check-circle me-1"></i>Medium';
                                color = 'text-info';
                            } else {
                                strength = '<i class="fas fa-check-circle me-1"></i>Strong';
                                color = 'text-success';
                            }
                            strengthDiv.innerHTML = `<span class="${color}">${strength}</span>`;
                        });

                        // Focus on input
                        setTimeout(() => input.focus(), 100);
                    },
                    preConfirm: () => {
                        const val = document.getElementById('swal-password').value || '';
                        if (val.length < 6) {
                            Swal.showValidationMessage(
                                '<i class="fas fa-exclamation-triangle me-2"></i>Password must be at least 6 characters long.'
                            );
                            return false;
                        }
                        return val;
                    }
                }).then((result) => {
                    if (result.isConfirmed) {
                        document.getElementById('password-input-' + userId).value = result.value;
                        document.getElementById('change-password-form-' + userId).submit();
                    }
                });
            }
        </script>
    @endsection
