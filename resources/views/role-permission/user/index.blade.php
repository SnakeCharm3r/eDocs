{{-- resources/views/role-permission/user/index.blade.php --}}
@extends('layouts.template')

@section('breadcrumb')
    @include('sweetalert::alert')

    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">

    <style>
        /* ── Stat Cards ── */
        .stat-card {
            border: none;
            border-radius: 12px;
            transition: transform .2s, box-shadow .2s;
            cursor: pointer;
            overflow: hidden;
        }
        .stat-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(0,0,0,.1);
        }
        .stat-card .card-body { padding: 1.1rem 1.25rem; }
        .stat-card .stat-icon {
            width: 48px; height: 48px;
            border-radius: 12px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.2rem;
        }
        .stat-card .stat-value { font-size: 1.6rem; font-weight: 700; line-height: 1; }
        .stat-card .stat-label { font-size: .8rem; color: #6b7280; margin-top: 2px; }

        /* ── SweetAlert overrides ── */
        .swal2-popup { border-radius: 12px !important; }
        .swal2-title { font-size: 1.15rem !important; }
        .swal2-html-container { text-align: left !important; }
        .swal2-select, .swal2-input, .swal2-textarea {
            width: 100% !important; border: 1px solid #ced4da !important;
            border-radius: 8px !important; font-size: 14px !important; padding: .6rem .75rem !important;
        }

        /* ── Avatar ── */
        .user-avatar {
            width: 36px; height: 36px; border-radius: 10px;
            display: inline-flex; align-items: center; justify-content: center;
            font-weight: 700; font-size: .82rem; color: #fff;
            flex-shrink: 0; text-transform: uppercase;
        }

        /* ── Role badges ── */
        .role-pill {
            display: inline-block; padding: .18rem .5rem; border-radius: 20px;
            font-size: 11px; font-weight: 600; line-height: 1.4;
            white-space: nowrap; margin: 1px 2px;
        }
        .role-pill-super-admin { background: #fee2e2; color: #991b1b; }
        .role-pill-admin       { background: #dbeafe; color: #1e40af; }
        .role-pill-hr          { background: #d1fae5; color: #065f46; }
        .role-pill-it          { background: #e0e7ff; color: #3730a3; }
        .role-pill-coo         { background: #fef3c7; color: #92400e; }
        .role-pill-cfo         { background: #fce7f3; color: #9d174d; }
        .role-pill-cms         { background: #f3e8ff; color: #6b21a8; }
        .role-pill-default     { background: #f3f4f6; color: #374151; }
        .role-pill-finance     { background: #ccfbf1; color: #134e4a; }

        /* ── Status badge ── */
        .status-badge {
            display: inline-flex; align-items: center; gap: 5px;
            padding: .25rem .6rem; border-radius: 20px;
            font-size: 12px; font-weight: 600;
        }
        .status-badge .status-dot {
            width: 7px; height: 7px; border-radius: 50%; flex-shrink: 0;
        }
        .status-active  { background: #d1fae5; color: #065f46; }
        .status-active .status-dot { background: #059669; }
        .status-inactive { background: #f3f4f6; color: #4b5563; }
        .status-inactive .status-dot { background: #9ca3af; }
        .status-pending { background: #fef3c7; color: #92400e; }
        .status-pending .status-dot { background: #d97706; }
        .status-deactivated { background: #fee2e2; color: #991b1b; }
        .status-deactivated .status-dot { background: #dc2626; }

        /* ── Table ── */
        #usersTable { font-size: .875rem; }
        #usersTable thead th {
            font-weight: 600; font-size: .78rem; text-transform: uppercase;
            letter-spacing: .04em; color: #6b7280; border-bottom: 2px solid #e5e7eb;
            padding: .65rem .75rem;
        }
        #usersTable tbody tr { transition: background .15s; }
        #usersTable tbody tr:hover { background: #f8fafc; }
        #usersTable td { padding: .6rem .75rem; vertical-align: middle; }

        .table-responsive { overflow-x: auto; overflow-y: visible; position: relative; }

        /* ── Filters ── */
        .filter-bar { background: #f8fafc; border-radius: 12px; padding: 1rem 1.25rem; border: 1px solid #e5e7eb; }
        .filter-bar label { font-size: .78rem; font-weight: 600; color: #6b7280; text-transform: uppercase; letter-spacing: .03em; }

        /* ── Misc ── */
        .spinner { display: inline-block; width: 14px; height: 14px; border: 2px solid #fff; border-top-color: transparent; border-radius: 50%; animation: spin .7s linear infinite; vertical-align: -2px; margin-left: .4rem; }
        @keyframes spin { to { transform: rotate(360deg); } }

        .user-details-modal .modal-body { max-height: 70vh; overflow-y: auto; }

        .empty-state { padding: 3rem 1rem; text-align: center; color: #9ca3af; }
        .empty-state i { font-size: 2.5rem; margin-bottom: .75rem; }

        .locked-indicator { font-size: 11px; color: #dc2626; display: flex; align-items: center; gap: 3px; margin-top: 3px; }

        /* DataTables length dropdown */
        .dataTables_wrapper .dataTables_length select,
        #usersTable_wrapper .dataTables_length select {
            -webkit-appearance: none !important; -moz-appearance: none !important; appearance: none !important;
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16'%3e%3cpath fill='none' stroke='%23343a40' stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M2 5l6 6 6-6'/%3e%3c/svg%3e") !important;
            background-repeat: no-repeat !important; background-position: right .5rem center !important;
            background-size: 16px 12px !important; padding-right: 2rem !important;
        }
        .page-header-bar { margin-bottom: 1.25rem; }
        .page-header-bar h3 { font-weight: 700; color: #1f2937; margin: 0; }


    </style>

    <div class="page-wrapper">
        <div class="content container-fluid">

            {{-- Page Header --}}
            <div class="page-header-bar d-flex align-items-center justify-content-between flex-wrap gap-2">
                <div>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb mb-1" style="font-size:.82rem;">
                            <li class="breadcrumb-item"><a href="{{ url('/') }}">Dashboard</a></li>
                            <li class="breadcrumb-item active">User Management</li>
                        </ol>
                    </nav>

                </div>
                <a href="{{ route('users.export') }}" class="btn btn-sm btn-success" style="border-radius:8px;">
                    <i class="fas fa-file-excel me-1"></i> Export Excel
                </a>
            </div>

            @if (session('error'))
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    {!! session('error') !!}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif
            @if (session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    {!! session('success') !!}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            {{-- ─── Stat Cards ─── --}}
            <div class="row g-3 mb-4">
                <div class="col-6 col-md-4 col-xl">
                    <div class="card stat-card shadow-sm" onclick="filterByCard('')" id="card-total">
                        <div class="card-body d-flex align-items-center gap-3">
                            <div class="stat-icon" style="background:#eff6ff;color:#3b82f6;"><i class="fas fa-users"></i></div>
                            <div>
                                <div class="stat-value text-dark">{{ $totalUsers }}</div>
                                <div class="stat-label">Total Users</div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-md-4 col-xl">
                    <div class="card stat-card shadow-sm" onclick="filterByCard('Active')" id="card-active">
                        <div class="card-body d-flex align-items-center gap-3">
                            <div class="stat-icon" style="background:#d1fae5;color:#059669;"><i class="fas fa-user-check"></i></div>
                            <div>
                                <div class="stat-value" style="color:#059669;">{{ $activeUsers }}</div>
                                <div class="stat-label">Active</div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-md-4 col-xl">
                    <div class="card stat-card shadow-sm" onclick="filterByCard('Pending')" id="card-pending">
                        <div class="card-body d-flex align-items-center gap-3">
                            <div class="stat-icon" style="background:#fef3c7;color:#d97706;"><i class="fas fa-user-clock"></i></div>
                            <div>
                                <div class="stat-value" style="color:#d97706;">{{ $pendingUsers }}</div>
                                <div class="stat-label">Pending</div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-md-4 col-xl">
                    <div class="card stat-card shadow-sm" onclick="filterByCard('Inactive')" id="card-inactive">
                        <div class="card-body d-flex align-items-center gap-3">
                            <div class="stat-icon" style="background:#f3f4f6;color:#6b7280;"><i class="fas fa-user-minus"></i></div>
                            <div>
                                <div class="stat-value text-secondary">{{ $inactiveUsers }}</div>
                                <div class="stat-label">Inactive / Deactivated</div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-md-4 col-xl">
                    <div class="card stat-card shadow-sm" onclick="filterByCard('locked')" id="card-locked">
                        <div class="card-body d-flex align-items-center gap-3">
                            <div class="stat-icon" style="background:#fee2e2;color:#dc2626;"><i class="fas fa-lock"></i></div>
                            <div>
                                <div class="stat-value" style="color:#dc2626;">{{ $lockedUsersCount }}</div>
                                <div class="stat-label">Locked Accounts</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ─── Main Card ─── --}}
            <div class="card border-0 shadow-sm" style="border-radius:14px;">
                <div class="card-body">
                    @php
                        $sortedUsers = $users->sortBy('username', SORT_NATURAL | SORT_FLAG_CASE);
                    @endphp

                    {{-- ─── Filters ─── --}}
                    <div class="filter-bar mb-3">
                        <div class="row g-2 align-items-end">
                            <div class="col-6 col-md-3 col-lg-2">
                                <label for="filterDepartment" class="form-label mb-1">Department</label>
                                <select id="filterDepartment" class="form-select form-select-sm">
                                    <option value="">All Departments</option>
                                    @foreach($departments as $dept)
                                        <option value="{{ $dept->dept_name }}">{{ $dept->dept_name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-6 col-md-3 col-lg-2">
                                <label for="filterStatus" class="form-label mb-1">Status</label>
                                <select id="filterStatus" class="form-select form-select-sm">
                                    <option value="">All Status</option>
                                    <option value="Active">Active</option>
                                    <option value="Inactive">Inactive</option>
                                    <option value="Pending">Pending</option>
                                    <option value="Deactivated">Deactivated</option>
                                </select>
                            </div>
                            <div class="col-6 col-md-3 col-lg-2">
                                <label for="filterRole" class="form-label mb-1">Role</label>
                                <select id="filterRole" class="form-select form-select-sm">
                                    <option value="">All Roles</option>
                                    @foreach($allRoles as $role)
                                        <option value="{{ $role }}">{{ $role }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-6 col-md-3 col-lg-2">
                                <label for="filterPermission" class="form-label mb-1">Permission</label>
                                <select id="filterPermission" class="form-select form-select-sm">
                                    <option value="">All Permissions</option>
                                    @foreach($allPermissions as $perm)
                                        <option value="{{ $perm }}">{{ $perm }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-auto d-flex gap-2 align-items-end pb-1">
                                <button type="button" class="btn btn-sm btn-outline-danger" id="filterLocked" title="Show locked users only" style="border-radius:8px;">
                                    <i class="fas fa-lock me-1"></i>Locked
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-secondary" id="clearFilters" style="border-radius:8px;">
                                    <i class="fas fa-times me-1"></i>Clear
                                </button>
                            </div>
                        </div>
                        <div class="mt-2">
                            <small class="text-muted" id="filterInfo"></small>
                        </div>
                    </div>



                    {{-- ─── Users Table ─── --}}
                    <div class="table-responsive">
                        <table id="usersTable" class="table table-hover align-middle" style="width:100%">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>CCBRT Code</th>
                                    <th>User</th>
                                    <th>Job Title</th>
                                    <th>Role</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($sortedUsers as $user)
                                    @php
                                        $initials = strtoupper(substr($user->fname ?? '', 0, 1) . substr($user->lname ?? '', 0, 1));
                                        if (empty(trim($initials))) $initials = strtoupper(substr($user->username ?? '?', 0, 2));
                                        $avatarColors = ['#3b82f6','#059669','#d97706','#8b5cf6','#ec4899','#06b6d4','#f97316','#6366f1','#14b8a6','#e11d48'];
                                        $colorIdx = crc32($user->username ?? '') % count($avatarColors);
                                        $avatarBg = $avatarColors[abs($colorIdx)];

                                        $isLocked = $user->is_locked && $user->locked_until && \Carbon\Carbon::now()->lt(\Carbon\Carbon::parse($user->locked_until));

                                        $statusClass = match ($user->status) {
                                            'active' => 'active',
                                            'inactive' => 'inactive',
                                            'pending' => 'pending',
                                            'deactivated' => 'deactivated',
                                            default => 'inactive',
                                        };
                                    @endphp
                                    <tr data-permissions="{{ $user->getAllPermissions()->pluck('name')->implode(',') }}"
                                        data-locked="{{ $isLocked ? '1' : '0' }}">
                                        <td class="text-muted row-num"></td>
                                        <td><code class="text-dark">{{ $user->ccbrt_code ?? '—' }}</code></td>
                                        <td>
                                            <div class="d-flex align-items-center gap-2">
                                                <div class="user-avatar" style="background:{{ $avatarBg }}">{{ $initials }}</div>
                                                <div>
                                                    <button type="button" class="btn btn-link p-0 text-decoration-none fw-semibold text-dark"
                                                        onclick="showUserDetails({{ $user->id }})" title="Quick View"
                                                        style="font-size:.875rem;">
                                                        {{ $user->fname }} {{ $user->lname }}
                                                    </button>
                                                    <div class="text-muted" style="font-size:.78rem;">{{ $user->username }}</div>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <span style="font-size:.83rem;">{{ optional($user->jobTitle)->job_title ?? '—' }}</span>
                                            <div class="text-muted" style="font-size:.75rem;">{{ $user->department ? $user->department->dept_name : '—' }}</div>
                                        </td>
                                        <td>
                                            @if (!empty($user->getRoleNames()) && $user->getRoleNames()->count() > 0)
                                                @foreach ($user->getRoleNames() as $rolename)
                                                    @php
                                                        $roleLower = strtolower((string) $rolename);
                                                        $pillClass = match(true) {
                                                            str_contains($roleLower, 'super-admin') => 'role-pill-super-admin',
                                                            str_contains($roleLower, 'admin') => 'role-pill-admin',
                                                            $roleLower === 'hr' => 'role-pill-hr',
                                                            $roleLower === 'it' => 'role-pill-it',
                                                            $roleLower === 'coo' => 'role-pill-coo',
                                                            $roleLower === 'cfo' => 'role-pill-cfo',
                                                            $roleLower === 'cms' => 'role-pill-cms',
                                                            str_contains($roleLower, 'finance') => 'role-pill-finance',
                                                            default => 'role-pill-default',
                                                        };
                                                    @endphp
                                                    <span class="role-pill {{ $pillClass }}">{{ $rolename }}</span>
                                                @endforeach
                                            @else
                                                <span class="text-muted" style="font-size:.8rem;">No roles</span>
                                            @endif
                                        </td>
                                        <td>
                                            <span class="status-badge status-{{ $statusClass }}">
                                                <span class="status-dot"></span>
                                                {{ ucfirst($user->status ?? 'inactive') }}
                                            </span>
                                            @if ($isLocked)
                                                @php
                                                    $lockedUntil = \Carbon\Carbon::parse($user->locked_until);
                                                    $minutesRemaining = \Carbon\Carbon::now()->diffInMinutes($lockedUntil);
                                                    $hoursRemaining = floor($minutesRemaining / 60);
                                                    $minsRemaining = $minutesRemaining % 60;
                                                @endphp
                                                <div class="locked-indicator">
                                                    <i class="fas fa-lock"></i>
                                                    Locked
                                                    @if ($hoursRemaining > 0)
                                                        ({{ $hoursRemaining }}h {{ $minsRemaining }}m)
                                                    @else
                                                        ({{ $minsRemaining }}m)
                                                    @endif
                                                </div>
                                            @endif
                                        </td>
                                        <td>
                                            <button type="button" class="btn btn-sm btn-light border"
                                                onclick="showActionsMenu({{ $user->id }})"
                                                style="border-radius:8px;font-size:.8rem;">
                                                <i class="fas fa-ellipsis-h"></i>
                                            </button>
                                            <form id="reset-form-{{ $user->id }}"
                                                action="{{ route('users.reset-password', $user->id) }}"
                                                method="POST" style="display:none;">
                                                @csrf
                                            </form>
                                            @if (auth()->user()->hasAnyRole(['super-admin', 'it']) && !$user->hasRole('super-admin'))
                                                <form id="change-password-form-{{ $user->id }}"
                                                    action="{{ route('users.change-password', $user->id) }}"
                                                    method="POST" style="display:none;">
                                                    @csrf
                                                    <input type="hidden" name="password" id="password-input-{{ $user->id }}">
                                                </form>
                                            @endif
                                            {{-- Hidden data for action menu --}}
                                            <script type="application/json" id="user-actions-{{ $user->id }}">
                                                {!! json_encode([
                                                    'id' => $user->id,
                                                    'name' => trim(($user->fname ?? '') . ' ' . ($user->lname ?? '')),
                                                    'editUrl' => route('users.showEditForm', $user->id),
                                                    'hasFinanceRole' => $user->hasFinanceOfficerRole(),
                                                    'isSuperAdmin' => $user->hasRole('super-admin'),
                                                    'isLocked' => $isLocked,
                                                    'unlockUrl' => route('users.unlock', $user->id),
                                                    'canManagePerms' => auth()->user()->hasRole('super-admin'),
                                                    'canResetPw' => auth()->user()->hasAnyRole(['super-admin','admin','hr','it','coo','cfo','cms']),
                                                    'canChangePw' => auth()->user()->hasAnyRole(['super-admin','it']) && !$user->hasRole('super-admin'),
                                                ]) !!}
                                            </script>
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

    {{-- Quick View Modal --}}
    <div class="modal fade user-details-modal" id="userDetailsModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content" style="border-radius:14px;border:0;">
                <div class="modal-header border-0 pb-0">
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body pt-0" id="userDetailsContent">
                    <div class="text-center py-4"><div class="spinner-border text-primary"></div></div>
                </div>
            </div>
        </div>
    </div>

@push('scripts')
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>


    <script>
        var lockedFilterActive = false;

        $(document).ready(function() {
            // Select2 for filters


            var $table = $('#usersTable');
            var usersTable = $table.DataTable({
                pageLength: 25,
                lengthChange: true,
                order: [[2, 'asc']],
                columnDefs: [
                    { orderable: false, targets: [6] },
                    { targets: -1, className: 'text-nowrap' }
                ],
                language: {
                    search: '',
                    searchPlaceholder: 'Search users...',
                    lengthMenu: 'Show _MENU_',
                    info: 'Showing _START_ to _END_ of _TOTAL_ users',
                    emptyTable: '<div class="empty-state"><i class="fas fa-users"></i><p>No users found</p></div>'
                },
                drawCallback: function() {
                    var api = this.api();
                    updateFilterInfo(api);
                    api.column(0, {search:'applied', order:'applied'}).nodes().each(function(cell, i) {
                        cell.innerHTML = i + 1;
                    });
                }
            });
            window.usersTable = usersTable;

            // ── Column filters ──
            // Department is now inside Job Title column (col 3)
            $('#filterDepartment').on('change', function() {
                var val = $(this).val();
                usersTable.column(3).search(val ? $.fn.dataTable.util.escapeRegex(val) : '', true, false).draw();
            });
            $('#filterStatus').on('change', function() {
                var val = $(this).val();
                usersTable.column(5).search(val ? $.fn.dataTable.util.escapeRegex(val) : '', true, false).draw();
            });
            // Role filter on column 4
            $('#filterRole').on('change', function() {
                var val = $(this).val();
                usersTable.column(4).search(val ? $.fn.dataTable.util.escapeRegex(val) : '', true, false).draw();
            });

            // Permission filter (custom search on data-permissions attribute)
            $('#filterPermission').on('change', function() {
                var val = $(this).val();
                $.fn.dataTable.ext.search = $.fn.dataTable.ext.search.filter(function(fn) { return fn._permFilter !== true; });
                if (val) {
                    var filterFn = function(settings, data, dataIndex) {
                        var row = usersTable.row(dataIndex).node();
                        var perms = ($(row).data('permissions') || '').split(',');
                        return perms.indexOf(val) !== -1;
                    };
                    filterFn._permFilter = true;
                    $.fn.dataTable.ext.search.push(filterFn);
                }
                usersTable.draw();
            });

            // Locked filter toggle
            $('#filterLocked').on('click', function() {
                lockedFilterActive = !lockedFilterActive;
                $(this).toggleClass('btn-outline-danger btn-danger text-white');
                $.fn.dataTable.ext.search = $.fn.dataTable.ext.search.filter(function(fn) { return fn._lockedFilter !== true; });
                if (lockedFilterActive) {
                    var lockedFn = function(settings, data, dataIndex) {
                        var row = usersTable.row(dataIndex).node();
                        return $(row).data('locked') === '1' || $(row).data('locked') === 1;
                    };
                    lockedFn._lockedFilter = true;
                    $.fn.dataTable.ext.search.push(lockedFn);
                }
                usersTable.draw();
            });

            // Clear all filters
            $('#clearFilters').on('click', function() {
                $('#filterDepartment, #filterStatus, #filterRole, #filterPermission').val('').trigger('change');
                if (lockedFilterActive) { $('#filterLocked').click(); }
                $.fn.dataTable.ext.search = $.fn.dataTable.ext.search.filter(function(fn) {
                    return !fn._permFilter && !fn._lockedFilter;
                });
                usersTable.search('').columns().search('').draw();
            });


        });

        function updateFilterInfo(api) {
            var tbl = api || window.usersTable;
            if (!tbl) return;
            var info = tbl.page.info();
            var el = document.getElementById('filterInfo');
            if (info.recordsDisplay < info.recordsTotal) {
                el.textContent = 'Showing ' + info.recordsDisplay + ' of ' + info.recordsTotal + ' users (filtered)';
            } else {
                el.textContent = '';
            }
        }

        // ── Stat card click → filter ──
        function filterByCard(status) {
            if (status === 'locked') {
                $('#filterDepartment, #filterStatus, #filterRole, #filterPermission').val('').trigger('change');
                if (!lockedFilterActive) { $('#filterLocked').click(); }
                return;
            }
            if (lockedFilterActive) { $('#filterLocked').click(); }
            $('#filterDepartment, #filterRole, #filterPermission').val('').trigger('change');

            if (status === 'Inactive') {
                window.usersTable.column(5).search('Inactive|Deactivated', true, false).draw();

            } else {
                $('#filterStatus').val(status);
                $('#filterStatus').trigger('change');
            }
        }

        // ── Permission Modal ──
        function showPermissionModal(userId) {
            $.ajax({
                url: '/users/' + userId + '/permissions',
                method: 'GET',
                success: function(data) {
                    Swal.fire({
                        title: '<i class="fas fa-user-shield text-info me-2"></i>Manage Permissions',
                        html: data,
                        width: '800px',
                        showCancelButton: true,
                        confirmButtonText: '<i class="fas fa-save me-2"></i>Save',
                        cancelButtonText: 'Cancel',
                        confirmButtonColor: '#3b82f6',
                        cancelButtonColor: '#6c757d',
                        didOpen: function() {
                            $('.role-permission-group').each(function() {
                                var checkboxes = $(this).find('input[type="checkbox"]');
                                checkboxes.on('change', function() {
                                    var allChecked = checkboxes.filter(':checked').length === checkboxes.length;
                                    $(this).closest('.role-permission-group').find('.select-all-role').prop('checked', allChecked);
                                });
                                $(this).find('.select-all-role').on('change', function() {
                                    checkboxes.prop('checked', $(this).is(':checked'));
                                });
                            });
                            $('#selectAllPermissions').on('change', function() {
                                $('.permission-checkbox').prop('checked', $(this).is(':checked'));
                                $('.select-all-role').prop('checked', $(this).is(':checked'));
                            });
                        },
                        preConfirm: function() {
                            var selected = [];
                            $('.permission-checkbox:checked').each(function() {
                                selected.push({ permission: $(this).val(), role: $(this).closest('.role-permission-group').data('role') });
                            });
                            return selected;
                        }
                    }).then(function(result) {
                        if (result.isConfirmed && result.value) {
                            $.ajax({
                                url: '/users/' + userId + '/permissions',
                                method: 'POST',
                                data: { _token: '{{ csrf_token() }}', permissions: result.value },
                                success: function() {
                                    Swal.fire('Success!', 'Permissions updated.', 'success').then(function() { location.reload(); });
                                },
                                error: function(xhr) {
                                    Swal.fire('Error!', xhr.responseJSON?.message || 'Failed to update permissions.', 'error');
                                }
                            });
                        }
                    });
                },
                error: function() { Swal.fire('Error', 'Failed to load permissions.', 'error'); }
            });
        }

        // ── Actions Menu (SweetAlert popup) ──
        function showActionsMenu(userId) {
            var dataEl = document.getElementById('user-actions-' + userId);
            if (!dataEl) return;
            var u = JSON.parse(dataEl.textContent);

            // Build grouped action items
            var html = '';

            // Section: General
            html += '<div class="act-section">';
            html += '<div class="act-section-label">General</div>';
            html += '<button class="act-btn" onclick="Swal.close();showUserDetails(' + u.id + ')"><i class="fas fa-eye" style="color:#06b6d4;"></i><span>Quick View</span></button>';
            html += '<button class="act-btn" onclick="window.location.href=\'' + u.editUrl + '\'"><i class="fas fa-user-edit" style="color:#059669;"></i><span>Edit Roles</span></button>';
            if (u.hasFinanceRole) {
                html += '<button class="act-btn" onclick="window.location.href=\'' + u.editUrl + '#assigned-entity-section\'"><i class="fas fa-building" style="color:#3b82f6;"></i><span>Manage Entity</span></button>';
            }
            if (u.canManagePerms) {
                html += '<button class="act-btn" onclick="Swal.close();showPermissionModal(' + u.id + ')"><i class="fas fa-user-shield" style="color:#6366f1;"></i><span>Manage Permissions</span></button>';
            }
            html += '</div>';

            // Section: Account unlock (if locked)
            if (u.isLocked && u.canResetPw) {
                html += '<div class="act-section">';
                html += '<div class="act-section-label">Account</div>';
                html += '<form action="' + u.unlockUrl + '" method="POST" style="margin:0;">' +
                    '<input type="hidden" name="_token" value="{{ csrf_token() }}">' +
                    '<button type="submit" class="act-btn act-btn-warn" onclick="return confirm(\'Unlock this user?\')">' +
                    '<i class="fas fa-unlock" style="color:#059669;"></i><span>Unlock Account</span></button></form>';
                html += '</div>';
            }

            // Section: Security
            if (u.canResetPw || u.canChangePw) {
                html += '<div class="act-section">';
                html += '<div class="act-section-label">Security</div>';
                if (u.canResetPw) {
                    html += '<button class="act-btn" onclick="Swal.close();resetPasswordConfirmation(\'' + u.id + '\')"><i class="fas fa-envelope" style="color:#d97706;"></i><span>Reset Password (Email)</span></button>';
                }
                if (u.canChangePw) {
                    html += '<button class="act-btn" onclick="Swal.close();changePasswordConfirmation(\'' + u.id + '\')"><i class="fas fa-key" style="color:#3b82f6;"></i><span>Manual Password Reset</span></button>';
                }
                html += '</div>';
            }

            Swal.fire({
                html: '<div class="act-header">' +
                    '<div class="act-avatar">' + (u.name ? u.name.split(' ').map(function(w){return w[0]}).join('').substring(0,2).toUpperCase() : '?') + '</div>' +
                    '<div class="act-name">' + (u.name || 'User') + '</div>' +
                    '</div>' +
                    '<div class="act-body">' + html + '</div>',
                showConfirmButton: false,
                showCloseButton: true,
                width: '300px',
                padding: 0,
                customClass: { popup: 'actions-popup', htmlContainer: 'actions-html' },
                didOpen: function() {
                    if (!document.getElementById('act-menu-style')) {
                        var s = document.createElement('style');
                        s.id = 'act-menu-style';
                        s.textContent =
                            '.actions-popup{border-radius:16px!important;overflow:hidden!important;padding:0!important;}' +
                            '.actions-popup .swal2-close{top:10px!important;right:10px!important;font-size:1.2rem!important;color:#9ca3af!important;}' +
                            '.actions-popup .swal2-close:hover{color:#374151!important;}' +
                            '.actions-html{margin:0!important;padding:0!important;text-align:left!important;}' +
                            '.act-header{display:flex;align-items:center;gap:12px;padding:18px 20px 14px;border-bottom:1px solid #f3f4f6;background:#fafbfc;}' +
                            '.act-avatar{width:40px;height:40px;border-radius:10px;background:linear-gradient(135deg,#6366f1,#8b5cf6);color:#fff;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:.85rem;flex-shrink:0;}' +
                            '.act-name{font-weight:600;font-size:.95rem;color:#1f2937;line-height:1.2;}' +
                            '.act-body{padding:8px 10px 12px;}' +
                            '.act-section{margin-bottom:4px;}' +
                            '.act-section:last-child{margin-bottom:0;}' +
                            '.act-section-label{font-size:.68rem;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:#9ca3af;padding:6px 10px 2px;margin-top:2px;}' +
                            '.act-btn{display:flex;align-items:center;gap:10px;width:100%;padding:9px 10px;border:none;background:none;border-radius:8px;font-size:.85rem;color:#374151;cursor:pointer;text-align:left;transition:all .15s;}' +
                            '.act-btn:hover{background:#f3f4f6;color:#111827;}' +
                            '.act-btn i{width:18px;text-align:center;font-size:.85rem;flex-shrink:0;}' +
                            '.act-btn span{flex:1;}';
                        document.head.appendChild(s);
                    }
                }
            });
        }

        // ── Quick View Modal ──
        function showUserDetails(userId) {
            $('#userDetailsContent').html('<div class="text-center py-4"><div class="spinner-border text-primary"></div></div>');
            var modal = new bootstrap.Modal(document.getElementById('userDetailsModal'));
            modal.show();
            $.ajax({
                url: '/users/' + userId + '/details',
                method: 'GET',
                success: function(data) { $('#userDetailsContent').html(data); },
                error: function() { $('#userDetailsContent').html('<div class="alert alert-danger">Failed to load user details.</div>'); }
            });
        }

        // ── Password Helpers ──
        function resetPasswordConfirmation(userId) {
            Swal.fire({
                title: 'Reset Password',
                text: "Send a password reset to user's email?",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3b82f6',
                confirmButtonText: 'Yes, send',
                cancelButtonText: 'Cancel'
            }).then(function(result) {
                if (result.isConfirmed) { document.getElementById('reset-form-' + userId).submit(); }
            });
        }

        function changePasswordConfirmation(userId) {
            Swal.fire({
                title: '<i class="fas fa-key text-primary me-2"></i>Change Password',
                html: '<div class="text-start">' +
                    '<div class="alert alert-info py-2 mb-3" style="font-size:.85rem;border-radius:8px;">' +
                    '<i class="fas fa-info-circle me-1"></i> Minimum 6 characters required.</div>' +
                    '<label class="form-label fw-bold mb-2" for="swal-password"><i class="fas fa-key me-1 text-muted"></i>New Password</label>' +
                    '<div class="position-relative">' +
                    '<input type="password" id="swal-password" class="form-control" value="123.ccbrt" minlength="6" required placeholder="Enter new password" style="padding-right:45px;font-size:14px;border-radius:8px;">' +
                    '<button type="button" id="toggle-password" class="btn btn-link position-absolute end-0 top-50 translate-middle-y p-0 pe-2" style="border:none;background:none;color:#6c757d;"><i class="fas fa-eye"></i></button>' +
                    '</div>' +
                    '<div id="password-strength" class="mt-2" style="font-size:12px;"></div>' +
                    '</div>',
                icon: null,
                focusConfirm: false,
                showCancelButton: true,
                confirmButtonText: '<i class="fas fa-save me-1"></i>Change',
                confirmButtonColor: '#3b82f6',
                cancelButtonText: 'Cancel',
                cancelButtonColor: '#6c757d',
                width: '420px',
                didOpen: function() {
                    var input = document.getElementById('swal-password');
                    var toggleBtn = document.getElementById('toggle-password');
                    var strengthDiv = document.getElementById('password-strength');
                    toggleBtn.addEventListener('click', function(e) {
                        e.preventDefault();
                        if (input.type === 'password') { input.type = 'text'; toggleBtn.querySelector('i').classList.replace('fa-eye','fa-eye-slash'); }
                        else { input.type = 'password'; toggleBtn.querySelector('i').classList.replace('fa-eye-slash','fa-eye'); }
                    });
                    input.addEventListener('input', function() {
                        var val = this.value;
                        if (!val.length) { strengthDiv.innerHTML = ''; return; }
                        var s, c;
                        if (val.length < 6) { s = '<i class="fas fa-exclamation-circle me-1"></i>Too short'; c = 'text-danger'; }
                        else if (val.length < 8) { s = '<i class="fas fa-check-circle me-1"></i>Weak'; c = 'text-warning'; }
                        else if (val.length < 12) { s = '<i class="fas fa-check-circle me-1"></i>Medium'; c = 'text-info'; }
                        else { s = '<i class="fas fa-check-circle me-1"></i>Strong'; c = 'text-success'; }
                        strengthDiv.innerHTML = '<span class="' + c + '">' + s + '</span>';
                    });
                    setTimeout(function() { input.focus(); }, 100);
                },
                preConfirm: function() {
                    var val = document.getElementById('swal-password').value || '';
                    if (val.length < 6) { Swal.showValidationMessage('Password must be at least 6 characters.'); return false; }
                    return val;
                }
            }).then(function(result) {
                if (result.isConfirmed) {
                    document.getElementById('password-input-' + userId).value = result.value;
                    document.getElementById('change-password-form-' + userId).submit();
                }
            });
        }
    </script>
@endpush

@endsection
