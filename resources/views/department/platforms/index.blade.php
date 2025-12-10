{{-- resources/views/department/platforms/index.blade.php --}}
@extends('layouts.template')

@section('title', 'Platforms')

@section('breadcrumb')
    @include('sweetalert::alert')
@endsection

@section('content')
    <div class="page-wrapper">
        <div class="content container-fluid">

            <meta name="csrf-token" content="{{ csrf_token() }}">

            {{-- ===================== Header ===================== --}}
            <div class="page-header">
                <div class="row align-items-center">
                    <div class="col">
                        <h3 class="page-title mb-1">Platforms</h3>
                        <div class="text-muted small">Create, edit, delete, and assign a platform manager.</div>
                    </div>
                    <div class="col-auto d-flex gap-2">
                        <a href="{{ url('/units') }}" class="btn btn-outline-info btn-sm">
                            <i class="fas fa-layer-group"></i> All Units
                        </a>
                        <button class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#createPlatformModal">
                            <i class="fas fa-plus"></i> New Platform
                        </button>
                    </div>
                </div>
            </div>

            {{-- ===================== Route templates for JS ===================== --}}
            <input type="hidden" id="rtPlatformsStore" value="{{ url('/platforms') }}">
            <input type="hidden" id="rtPlatformsOne" value="{{ url('/platforms/PLATFORM_ID') }}">
            <input type="hidden" id="rtAssignManager" value="{{ url('/platforms/PLATFORM_ID/assign-manager') }}">
            <input type="hidden" id="rtDeptUsers" value="{{ url('/departments/DEPT_ID/users') }}">

            {{-- ===================== Platforms Table ===================== --}}
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center py-2">
                    <span class="fw-semibold">All Platforms</span>
                    <span class="text-muted small">Manage platform details & manager</span>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped align-middle" id="platformsTable">
                            <thead class="table-light">
                                <tr>
                                    <th style="width:5%">#</th>
                                    <th style="width:22%">Name</th>
                                    <th style="width:30%">Description</th>
                                    <th style="width:21%">Platform Manager</th>
                                    <th style="width:10%" class="text-center">Units</th>
                                    <th style="width:12%" class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($platforms as $p)
                                    @php
                                        $mgr = $p->manager ?? null;
                                        $mgrFull = $mgr
                                            ? trim(
                                                trim(($mgr->fname ?? '') . ' ' . ($mgr->mname ?? '')) .
                                                    ' ' .
                                                    ($mgr->lname ?? ''),
                                            )
                                            : null;
                                        $mgrName = $mgr
                                            ? ($mgrFull !== ''
                                                ? $mgrFull
                                                : $mgr->username ?? ($mgr->email ?? 'User #' . $mgr->id))
                                            : null;
                                        $unitsCount = isset($p->units) ? $p->units->count() : $p->units_count ?? 0;
                                    @endphp
                                    <tr data-platform-id="{{ $p->id }}" data-platform-name="{{ $p->name }}">
                                        <td class="fw-semibold">{{ $loop->iteration }}</td>
                                        <td class="fw-semibold">{{ $p->name }}</td>
                                        <td class="text-truncate" style="max-width:360px">{{ $p->description }}</td>
                                        <td>
                                            @if ($mgr)
                                                <span
                                                    class="badge bg-primary-subtle text-primary">{{ $mgrName }}</span>
                                            @else
                                                <span class="text-muted">None</span>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            <span
                                                class="badge bg-secondary-subtle text-secondary">{{ $unitsCount }}</span>
                                        </td>
                                        <td class="text-end">
                                            <div class="btn-group btn-group-sm">
                                                <button class="btn btn-outline-dark btn-set-manager" title="Assign Manager"
                                                    data-platform-id="{{ $p->id }}"
                                                    data-platform-name="{{ $p->name }}">
                                                    <i class="fas fa-user-tie"></i>
                                                </button>
                                                <button class="btn btn-outline-secondary btn-edit-platform" title="Edit"
                                                    data-id="{{ $p->id }}" data-name="{{ $p->name }}"
                                                    data-description="{{ $p->description }}">
                                                    <i class="fas fa-pen"></i>
                                                </button>
                                                <form action="{{ url('/platforms/' . $p->id) }}" method="POST"
                                                    class="d-inline"
                                                    onsubmit="return confirm('Delete this platform? This cannot be undone.');">
                                                    @csrf @method('DELETE')
                                                    <button class="btn btn-outline-danger" title="Delete">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center text-muted py-4">No platforms yet.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </div>
    </div>

    {{-- ===================== Create Platform Modal ===================== --}}
    <div class="modal fade" id="createPlatformModal" tabindex="-1" aria-labelledby="createPlatformModalLabel"
        aria-hidden="true">
        <div class="modal-dialog">
            <form id="createPlatformForm" class="modal-content" method="POST" action="{{ url('/platforms') }}">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title" id="createPlatformModalLabel">Create Platform</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    @if ($errors->any())
                        <div class="alert alert-danger">
                            <ul class="m-0 ps-3">
                                @foreach ($errors->all() as $e)
                                    <li>{{ $e }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <div class="mb-3">
                        <label class="form-label">Name <span class="text-danger">*</span></label>
                        <input name="name" class="form-control" maxlength="100" required
                            placeholder="e.g., OPD, IPD">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <input name="description" class="form-control" maxlength="255" placeholder="Optional">
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-primary" id="btnCreatePlatformSave">
                        <span class="label">Save</span>
                        <span class="spinner-border spinner-border-sm ms-2 d-none" role="status"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- ===================== Edit Platform Modal ===================== --}}
    <div class="modal fade" id="editPlatformModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <form id="editPlatformForm" class="modal-content" method="POST">
                @csrf @method('PUT')
                <div class="modal-header">
                    <h5 class="modal-title">Edit Platform</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Name <span class="text-danger">*</span></label>
                        <input id="edit_platform_name" name="name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <input id="edit_platform_description" name="description" class="form-control">
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-primary">Update</button>
                </div>
            </form>
        </div>
    </div>

    {{-- ===================== Assign Platform Manager Modal (Department → Users) ===================== --}}
    <div class="modal fade" id="assignManagerModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <form id="assignManagerForm" class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Assign Platform Manager</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="mgr_platform_id" name="platform_id">

                    <div class="mb-2">
                        <label class="form-label">Platform</label>
                        <input id="mgr_platform_name" class="form-control" disabled>
                    </div>

                    <div class="mb-2">
                        <label class="form-label">Department <span class="text-danger">*</span></label>
                        <select id="mgr_department_id" class="form-select" required>
                            <option value="">— Select a department —</option>
                            @foreach ($departments as $d)
                                <option value="{{ $d->id }}">
                                    {{ $d->display_name ?? ($d->dept_name ?? 'Dept #' . $d->id) }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-2">
                        <label class="form-label d-flex justify-content-between">
                            <span>User</span>
                            <small class="text-muted" id="mgr_user_hint">Choose department first</small>
                        </label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-search"></i></span>
                            <input id="mgr_user_search" class="form-control" placeholder="Search within department…"
                                disabled>
                        </div>
                        <select id="mgr_user_id" name="user_id" class="form-select mt-2" disabled>
                            <option value="">— Select a user —</option>
                        </select>
                        <div class="form-text">Clear the selection and Save to remove platform manager.</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-primary" id="btnAssignManagerSave">
                        <span class="label">Save</span>
                        <span class="spinner-border spinner-border-sm ms-2 d-none" role="status"></span>
                    </button>
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
                </div>
            </form>
        </div>
    </div>

    {{-- ===================== Assets ===================== --}}
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <style>
        .badge.bg-secondary-subtle {
            background-color: rgba(108, 117, 125, .15) !important;
            color: #6c757d !important;
        }

        .badge.bg-primary-subtle {
            background-color: rgba(13, 110, 253, .12) !important;
            color: #0d6efd !important;
        }
    </style>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const CSRF = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': CSRF
                }
            });

            const rt = id => document.getElementById(id)?.value || '';
            const rtPlatformsStore = () => rt('rtPlatformsStore');
            const rtPlatformsOne = id => rt('rtPlatformsOne').replace('PLATFORM_ID', encodeURIComponent(id));
            const rtAssignManager = id => rt('rtAssignManager').replace('PLATFORM_ID', encodeURIComponent(id));
            const rtDeptUsers = deptId => rt('rtDeptUsers').replace('DEPT_ID', encodeURIComponent(deptId));

            /* ===== Create Platform (AJAX) ===== */
            const $createBtn = $('#btnCreatePlatformSave');
            const spinCreate = (on = true) => {
                const $sp = $createBtn.find('.spinner-border'),
                    $lb = $createBtn.find('.label');
                if (on) {
                    $sp.removeClass('d-none');
                    $createBtn.prop('disabled', true);
                    $lb.text('Saving...');
                } else {
                    $sp.addClass('d-none');
                    $createBtn.prop('disabled', false);
                    $lb.text('Save');
                }
            };

            $('#createPlatformForm').on('submit', function(e) {
                e.preventDefault();
                spinCreate(true);
                $.post(rtPlatformsStore(), $(this).serialize())
                    .done(() => location.reload())
                    .fail(xhr => {
                        spinCreate(false);
                        const errors = xhr.responseJSON?.errors;
                        if (errors) {
                            let list = '';
                            Object.values(errors).forEach(arr => arr.forEach(e => list +=
                                `<li>${e}</li>`));
                            Swal.fire({
                                icon: 'error',
                                title: 'Validation error',
                                html: `<ul class="text-start m-0">${list}</ul>`
                            });
                        } else {
                            Swal.fire('Error', xhr.responseJSON?.message ||
                                'Failed to create platform.', 'error');
                        }
                    });
            });

            /* ===== Edit Platform ===== */
            $(document).on('click', '.btn-edit-platform', function() {
                const id = $(this).data('id');
                const name = $(this).data('name') || '';
                const desc = $(this).data('description') || '';
                $('#edit_platform_name').val(name);
                $('#edit_platform_description').val(desc);
                $('#editPlatformForm').attr('action', rtPlatformsOne(id));
                bootstrap.Modal.getOrCreateInstance('#editPlatformModal').show();
            });

            $('#editPlatformForm').on('submit', function(e) {
                e.preventDefault();
                const action = $(this).attr('action');
                const payload = {
                    _method: 'PUT',
                    name: $('#edit_platform_name').val(),
                    description: $('#edit_platform_description').val()
                };
                $.post(action, payload)
                    .done(() => location.reload())
                    .fail(xhr => Swal.fire('Error', xhr.responseJSON?.message ||
                        'Failed to update platform.', 'error'));
            });

            /* ===== Assign Platform Manager (Department → Users) ===== */
            const $mgrBtn = $('#btnAssignManagerSave');
            const spinMgr = (on = true) => {
                const $sp = $mgrBtn.find('.spinner-border'),
                    $lb = $mgrBtn.find('.label');
                if (on) {
                    $sp.removeClass('d-none');
                    $mgrBtn.prop('disabled', true);
                    $lb.text('Saving...');
                } else {
                    $sp.addClass('d-none');
                    $mgrBtn.prop('disabled', false);
                    $lb.text('Save');
                }
            };

            function loadUsersForDepartment(deptId, searchTerm) {
                const $sel = $('#mgr_user_id');
                const $hint = $('#mgr_user_hint');
                $sel.prop('disabled', true).empty().append('<option value="">— Select a user —</option>');
                $hint.text('Loading users…');
                $.get(rtDeptUsers(deptId), {
                        active: 1,
                        q: (searchTerm || '').trim()
                    })
                    .done(resp => {
                        const users = resp?.data || [];
                        users.forEach(u => $sel.append(new Option(u.display_name ?? ('User #' + u.id), u.id)));
                        $sel.prop('disabled', false);
                        $hint.text(`${users.length} user(s) found`);
                    })
                    .fail(() => {
                        $hint.text('Failed to load users.');
                        $sel.prop('disabled', true);
                    });
            }

            $(document).on('click', '.btn-set-manager', function() {
                const pid = $(this).data('platform-id');
                const pname = $(this).data('platform-name');
                $('#mgr_platform_id').val(pid);
                $('#mgr_platform_name').val(pname);
                $('#mgr_department_id').val('');
                $('#mgr_user_search').val('').prop('disabled', true);
                $('#mgr_user_id').empty().append('<option value="">— Select a user —</option>').prop(
                    'disabled', true);
                $('#mgr_user_hint').text('Choose department first');
                bootstrap.Modal.getOrCreateInstance('#assignManagerModal').show();
            });

            $('#mgr_department_id').on('change', function() {
                const deptId = this.value;
                $('#mgr_user_id').empty().append('<option value="">— Select a user —</option>').prop(
                    'disabled', true);
                $('#mgr_user_search').val('').prop('disabled', !deptId);
                if (!deptId) {
                    $('#mgr_user_hint').text('Choose department first');
                    return;
                }
                loadUsersForDepartment(deptId, '');
            });

            let mgrSearchTimer = null;
            $('#mgr_user_search').on('input', function() {
                const deptId = $('#mgr_department_id').val();
                if (!deptId) return;
                const term = $(this).val();
                clearTimeout(mgrSearchTimer);
                mgrSearchTimer = setTimeout(() => loadUsersForDepartment(deptId, term), 300);
            });

            $('#assignManagerForm').on('submit', function(e) {
                e.preventDefault();
                spinMgr(true);
                const pid = $('#mgr_platform_id').val();
                const deptId = $('#mgr_department_id').val();
                const user_id = $('#mgr_user_id').val() || null;

                if (!deptId && user_id) {
                    spinMgr(false);
                    return Swal.fire('Department required', 'Choose a department first.', 'warning');
                }

                $.post(rtAssignManager(pid), {
                        user_id,
                        department_id: deptId || null
                    })
                    .done(resp => {
                        spinMgr(false);
                        bootstrap.Modal.getInstance(document.getElementById('assignManagerModal'))
                            .hide();
                        Swal.fire('Done', resp?.message || 'Platform manager saved.', 'success')
                            .then(() => location.reload());
                    })
                    .fail(xhr => {
                        spinMgr(false);
                        Swal.fire('Error', xhr.responseJSON?.message ||
                            'Could not assign platform manager.', 'error');
                    });
            });

        });
    </script>
@endsection
