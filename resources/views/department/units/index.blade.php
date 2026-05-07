{{-- resources/views/department/units/units.blade.php --}}
@extends('layouts.template')

@section('title', 'Unit Management')

@section('breadcrumb')
    @include('sweetalert::alert')
@endsection

@section('content')
    <div class="page-wrapper">
        <div class="content container-fluid">

            <meta name="csrf-token" content="{{ csrf_token() }}">

            {{-- ===== Page Header (Units only) ===== --}}
            <div class="page-header">
                <div class="row align-items-center g-2">
                    <div class="col-auto d-flex gap-2">
                        {{-- 🔙 Back Button --}}
                        <a href="{{ url()->previous() }}" class="btn btn-secondary btn-sm">
                            <i class="fas fa-arrow-left me-1"></i> Back
                        </a>

                        {{-- ➕ Create Unit Button --}}
                        <button id="btnNewUnitGlobal" type="button" class="btn btn-primary btn-sm">
                            <i class="fas fa-plus"></i> Create Unit
                        </button>
                    </div>
                </div>
            </div>


            {{-- ===== Route templates used by JS ===== --}}
            <input type="hidden" id="rtDeptUsers" value="{{ route('departments.users', ['department' => 'DEPT_ID']) }}">
            <input type="hidden" id="rtAssignIncharge"
                value="{{ route('platforms.units.assignIncharge', ['platform' => 'PLATFORM_ID', 'unit' => 'UNIT_ID']) }}">
            <input type="hidden" id="rtStoreUnit"
                value="{{ route('platforms.units.store', ['platform' => 'PLATFORM_ID']) }}">
            <input type="hidden" id="rtUpdateUnit"
                value="{{ route('platforms.units.update', ['platform' => 'PLATFORM_ID', 'unit' => 'UNIT_ID']) }}">
            <input type="hidden" id="rtDeleteUnit"
                value="{{ route('platforms.units.destroy', ['platform' => 'PLATFORM_ID', 'unit' => 'UNIT_ID']) }}">

            {{-- ===================== UNITS ONLY ===================== --}}
            <div id="section-units" class="mt-2">

                <div class="mb-2">
                    <h6 class="text-uppercase text-muted mb-2">Units by Platform</h6>
                    <hr class="mt-0 mb-3">
                </div>

                <div class="row g-4">
                    @forelse ($platforms as $platform)
                        @php
                            $unitCount = $platform->units?->count() ?? 0;
                        @endphp

                        <div class="col-12" id="pf-{{ $platform->id }}">
                            <div class="card platform-card" data-platform-id="{{ $platform->id }}">

                                {{-- Platform Header --}}
                                <div class="card-header platform-header py-3">
                                    <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 w-100">
                                        <div class="flex-grow-1">
                                            <div class="d-flex align-items-center gap-2">
                                                <span class="h5 mb-0">{{ $platform->name }}</span>
                                                <span class="badge bg-secondary-subtle text-secondary"
                                                    id="badge-count-{{ $platform->id }}">{{ $unitCount }}</span>
                                            </div>
                                            @if ($platform->description)
                                                <small class="text-muted d-block">{{ $platform->description }}</small>
                                            @endif
                                        </div>

                                        <div class="text-end d-flex flex-wrap gap-2">
                                            <button type="button"
                                                class="btn btn-outline-primary btn-sm btnNewUnitForPlatform"
                                                data-platform-id="{{ $platform->id }}"
                                                data-platform-name="{{ $platform->name }}">
                                                <i class="fas fa-plus"></i> Create Unit
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                {{-- Units Table --}}
                                <div class="card-body pt-3">
                                    <div class="d-flex align-items-center justify-content-between mb-2">
                                        <div class="text-uppercase text-muted small fw-semibold">Units for
                                            {{ $platform->name }}</div>
                                    </div>

                                    <div class="table-responsive">
                                        <table class="table table-hover table-sm align-middle units-table"
                                            data-platform-id="{{ $platform->id }}">
                                            <thead class="table-light">
                                                <tr>
                                                    <th style="width:22%">Unit</th>
                                                    <th style="width:18%">Locum Hours</th>
                                                    <th style="width:26%">Incharge</th>
                                                    <th style="width:24%">Description</th>
                                                    <th style="width:6%">Active</th>
                                                    <th class="text-end" style="width:4%">Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody id="units-body-{{ $platform->id }}">
                                                @forelse ($platform->units as $u)
                                                    @php
                                                        $inc = $u->incharge;
                                                        $full = $inc
                                                            ? trim(
                                                                trim(($inc->fname ?? '') . ' ' . ($inc->mname ?? '')) .
                                                                    ' ' .
                                                                    ($inc->lname ?? ''),
                                                            )
                                                            : null;
                                                        $incName = $inc
                                                            ? ($full !== ''
                                                                ? $full
                                                                : $inc->username ??
                                                                    ($inc->email ?? 'User #' . $inc->id))
                                                            : null;
                                                        $deptLabel =
                                                            $inc && $inc->relationLoaded('department')
                                                                ? $inc->department->dept_name ?? null
                                                                : null;
                                                    @endphp
                                                    <tr id="unit-row-{{ $u->id }}"
                                                        data-unit-id="{{ $u->id }}"
                                                        data-platform-id="{{ $platform->id }}">
                                                        <td class="unit-name fw-semibold">{{ $u->name }}</td>

                                                        <td class="unit-locum-hours">
                                                            @php
                                                                $platformDefault = $platform->locum_hours ?? 8;
                                                            @endphp
                                                            @if ($u->locum_hours)
                                                                <span class="badge bg-info-subtle text-info">
                                                                    {{ $u->locum_hours }} hrs / locum
                                                                </span>
                                                            @else
                                                                <span class="text-muted">
                                                                    Default ({{ $platformDefault }} hrs)
                                                                </span>
                                                            @endif
                                                        </td>

                                                        <td class="unit-incharge">
                                                            @if ($inc)
                                                                <div class="d-flex flex-column">
                                                                    <span
                                                                        class="badge bg-primary-subtle text-primary incharge-label">{{ $incName }}</span>
                                                                    <small
                                                                        class="text-muted incharge-dept">{{ $deptLabel ? $deptLabel : ($inc->deptId ? 'Dept #' . $inc->deptId : 'No department') }}</small>
                                                                </div>
                                                            @else
                                                                <span class="text-muted incharge-empty">None</span>
                                                            @endif
                                                        </td>

                                                        <td class="unit-description text-truncate"
                                                            title="{{ $u->description ?? '' }}">
                                                            {{ \Illuminate\Support\Str::limit($u->description ?? '', 60) }}
                                                        </td>

                                                        <td class="unit-active" data-active="{{ (int) $u->is_active }}">
                                                            @if ($u->is_active)
                                                                <span class="badge bg-success">Yes</span>
                                                            @else
                                                                <span class="badge bg-secondary">No</span>
                                                            @endif
                                                        </td>

                                                        <td class="text-end">
                                                            <div class="btn-group btn-group-sm">
                                                                <button type="button"
                                                                    class="btn btn-outline-primary btn-set-incharge"
                                                                    data-unit-id="{{ $u->id }}"
                                                                    data-unit-name="{{ $u->name }}"
                                                                    data-platform-id="{{ $platform->id }}"
                                                                    data-current-id="{{ $u->incharge?->id ?? '' }}"
                                                                    data-current-dept-id="{{ $u->incharge?->deptId ?? '' }}"
                                                                    title="Assign Incharge">
                                                                    <i class="fas fa-user-check"></i>
                                                                </button>
                                                                <button type="button"
                                                                    class="btn btn-outline-secondary btn-edit-unit"
                                                                    data-id="{{ $u->id }}"
                                                                    data-platform-id="{{ $platform->id }}"
                                                                    data-name="{{ $u->name }}"
                                                                    data-description="{{ $u->description }}"
                                                                    data-locum-hours="{{ $u->locum_hours ?? '' }}"
                                                                    data-active="{{ (int) $u->is_active }}"
                                                                    title="Edit">
                                                                    <i class="fas fa-pen"></i>
                                                                </button>
                                                                <button type="button"
                                                                    class="btn btn-outline-danger btn-delete-unit"
                                                                    data-platform-id="{{ $platform->id }}"
                                                                    data-id="{{ $u->id }}" title="Delete">
                                                                    <i class="fas fa-trash"></i>
                                                                </button>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                @empty
                                                    <tr class="units-empty">
                                                        <td colspan="6" class="text-center text-muted py-4">No units
                                                            for this platform.</td>
                                                    </tr>
                                                @endforelse
                                            </tbody>
                                        </table>
                                    </div>
                                </div>

                            </div>
                        </div>
                    @empty
                        <div class="col-12">
                            <div class="alert alert-info">No platforms found.</div>
                        </div>
                    @endforelse
                </div>
            </div>

        </div>
    </div>

    {{-- ===================== Create Unit Modal ===================== --}}
    <div class="modal fade" id="createUnitModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <form id="createUnitForm" class="modal-content" novalidate>
                <div class="modal-header">
                    <h6 class="modal-title">Create Unit</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-2">
                        <label class="form-label">Platform <span class="text-danger">*</span></label>
                        <select name="platform_id" id="create_platform_id" class="form-select" required>
                            <option value="">— Select a platform —</option>
                            @foreach ($platforms as $p)
                                <option value="{{ $p->id }}">{{ $p->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" required maxlength="120"
                            placeholder="Unit name">
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Description</label>
                        <textarea name="description" class="form-control" maxlength="255" placeholder="Optional"></textarea>
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Locum hours (optional)</label>
                        <input type="number" min="1" max="24" name="locum_hours" class="form-control"
                            placeholder="e.g. 8 or 12. Leave empty to use platform default.">
                        <div class="form-text">
                            If left empty, this unit will use the platform's locum hours setting.
                        </div>
                    </div>
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="is_active" id="create_is_active" checked>
                        <label class="form-check-label" for="create_is_active">Active</label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-primary" id="btnCreateUnitSave">
                        <span class="label">Save</span>
                        <span class="spinner-border spinner-border-sm ms-2 d-none" role="status"></span>
                    </button>
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
                </div>
            </form>
        </div>
    </div>

    {{-- ===================== Assign Incharge Modal (Unit) ===================== --}}
    <div class="modal fade" id="assignInchargeModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form id="assignInchargeForm" class="modal-content">
                <div class="modal-header">
                    <h6 class="modal-title">Assign Incharge</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="unit_id" id="assign_unit_id">
                    <input type="hidden" name="platform_id" id="assign_platform_id">

                    <div class="mb-2">
                        <label class="form-label">Unit</label>
                        <input type="text" id="assign_unit_name" class="form-control" disabled>
                    </div>

                    <div class="mb-2">
                        <label class="form-label">Department</label>
                        <select name="department_id" id="assign_department_id" class="form-select">
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
                            <small class="text-muted" id="assign_user_hint">Choose department first</small>
                        </label>
                        <select name="user_id" id="assign_user_id" class="form-select" disabled>
                            <option value="">— Select a user —</option>
                        </select>
                        <div class="form-text">Clear the selection and Save to remove incharge.</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-primary" id="btnAssignSave">
                        <span class="label">Save</span>
                        <span class="spinner-border spinner-border-sm ms-2 d-none" role="status"></span>
                    </button>
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
                </div>
            </form>
        </div>
    </div>

    {{-- ===== Assets (omit if already in layout) ===== --}}
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

        .platform-card .platform-header {
            background: var(--bs-light);
            border-bottom: 1px solid var(--bs-border-color);
        }

        .units-table td {
            vertical-align: middle;
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

            const swal = (t, m, i = 'info') => window.Swal && Swal.fire(t, m, i);
            const rt = id => document.getElementById(id)?.value || '';
            const rtDeptUsers = deptId => rt('rtDeptUsers').replace('DEPT_ID', encodeURIComponent(deptId));
            const rtAssignIncharge = (pid, uid) => rt('rtAssignIncharge').replace('PLATFORM_ID', encodeURIComponent(
                pid)).replace('UNIT_ID', encodeURIComponent(uid));
            const rtStoreUnit = pid => rt('rtStoreUnit').replace('PLATFORM_ID', encodeURIComponent(pid));
            const rtUpdateUnit = (pid, uid) => rt('rtUpdateUnit').replace('PLATFORM_ID', encodeURIComponent(pid))
                .replace('UNIT_ID', encodeURIComponent(uid));
            const rtDeleteUnit = (pid, uid) => rt('rtDeleteUnit').replace('PLATFORM_ID', encodeURIComponent(pid))
                .replace('UNIT_ID', encodeURIComponent(uid));
            const esc = s => $('<div>').text(s ?? '').html();

            const spin = ($btn, on = true) => {
                const $sp = $btn.find('.spinner-border'),
                    $lb = $btn.find('.label');
                if (on) {
                    $sp.removeClass('d-none');
                    $btn.prop('disabled', true);
                    $lb.text('Saving...');
                } else {
                    $sp.addClass('d-none');
                    $btn.prop('disabled', false);
                    $lb.text('Save');
                }
            };
            const updateBadge = (platformId, delta) => {
                const $b = $(`#badge-count-${platformId}`);
                if (!$b.length) return;
                const n = Math.max(0, (parseInt($b.text() || '0', 10) + delta));
                $b.text(n);
            };

            /* ===== Create Unit ===== */
            $('#btnNewUnitGlobal').on('click', () => {
                document.getElementById('createUnitForm')?.reset();
                new bootstrap.Modal('#createUnitModal').show();
            });
            $(document).on('click', '.btnNewUnitForPlatform', function() {
                document.getElementById('createUnitForm')?.reset();
                $('#create_platform_id').val(String($(this).data('platform-id')));
                new bootstrap.Modal('#createUnitModal').show();
            });
            $('#createUnitForm').on('submit', function(e) {
                e.preventDefault();
                const $btn = $('#btnCreateUnitSave');
                spin($btn, true);
                const platformId = $('#create_platform_id').val();
                if (!platformId) {
                    spin($btn, false);
                    return swal('Platform required', 'Choose a platform.', 'warning');
                }
                const f = $(this),
                    checked = f.find('[name="is_active"]').is(':checked');
                const payload = f.serializeArray().concat([{
                    name: 'is_active',
                    value: checked ? 1 : 0
                }]);

                $.post(rtStoreUnit(platformId), $.param(payload))
                    .done(resp => {
                        const data = resp?.data,
                            $tbody = $(`#units-body-${platformId}`);
                        if ($tbody.length && data) {
                            $tbody.find('tr.units-empty').remove();
                            const platformDefaults = @json($platforms->pluck('locum_hours', 'id'));
                            const platformDefault = platformDefaults[platformId] ?? 8;
                            const row = `
<tr id="unit-row-${data.id}" data-unit-id="${data.id}" data-platform-id="${platformId}">
  <td class="unit-name fw-semibold">${esc(data.name)}</td>
  <td class="unit-locum-hours">
    ${
        data.locum_hours
            ? `<span class="badge bg-info-subtle text-info">${esc(String(data.locum_hours))} hrs / locum</span>`
            : `<span class="text-muted">Default (${esc(String(platformDefault))} hrs)</span>`
    }
  </td>
  <td class="unit-incharge"><span class="text-muted incharge-empty">None</span></td>
  <td class="unit-description text-truncate" title="${esc(data.description||'')}">${esc((data.description||'').slice(0,60))}</td>
  <td class="unit-active" data-active="${data.is_active?1:0}">
    ${data.is_active?'<span class="badge bg-success">Yes</span>':'<span class="badge bg-secondary">No</span>'}
  </td>
  <td class="text-end">
    <div class="btn-group btn-group-sm">
      <button class="btn btn-outline-primary btn-set-incharge" title="Assign Incharge"
              data-unit-id="${data.id}" data-unit-name="${esc(data.name)}"
              data-platform-id="${platformId}" data-current-id="" data-current-dept-id="">
        <i class="fas fa-user-check"></i>
      </button>
      <button class="btn btn-outline-secondary btn-edit-unit" title="Edit"
              data-id="${data.id}" data-platform-id="${platformId}"
              data-name="${esc(data.name)}" data-description="${esc(data.description||'')}"
              data-locum-hours="${esc(data.locum_hours ?? '')}"
              data-active="${data.is_active?1:0}">
        <i class="fas fa-pen"></i>
      </button>
      <button class="btn btn-outline-danger btn-delete-unit" title="Delete"
              data-platform-id="${platformId}" data-id="${data.id}">
        <i class="fas fa-trash"></i>
      </button>
    </div>
  </td>
</tr>`;
                            $tbody.prepend(row);
                            updateBadge(platformId, +1);
                        }
                        bootstrap.Modal.getInstance(document.getElementById('createUnitModal')).hide();
                        spin($btn, false);
                        swal('Success', resp.message || 'Unit created.', 'success');
                        this.reset();
                    })
                    .fail(xhr => {
                        spin($btn, false);
                        swal('Error', xhr.responseJSON?.message || 'Failed to create unit.', 'error');
                    });
            });

            /* ===== Edit Unit ===== */
            $(document).on('click', '.btn-edit-unit', function() {
                const unitId = $(this).data('id'),
                    platformId = $(this).data('platform-id');
                const nm = $(this).data('name') || '',
                    ds = $(this).data('description') || '',
                    lh = $(this).data('locum-hours') || '',
                    ac = !!+($(this).data('active') || 0);
                Swal.fire({
                    title: 'Edit Unit',
                    html: `<div class="text-start">
        <label class="form-label">Name</label>
        <input id="sw_name" class="swal2-input" value="${esc(nm)}">
        <label class="form-label">Description</label>
        <textarea id="sw_desc" class="swal2-textarea" rows="3">${esc(ds)}</textarea>
        <label class="form-label mt-2">Locum hours (leave empty to use platform default)</label>
        <input id="sw_locum_hours" type="number" min="1" max="24" class="swal2-input" value="${esc(lh)}">
        <div class="form-check mt-2">
          <input id="sw_active" class="form-check-input" type="checkbox" ${ac?'checked':''}>
          <label class="form-check-label" for="sw_active">Active</label>
        </div>
      </div>`,
                    focusConfirm: false,
                    showCancelButton: true,
                    preConfirm: () => {
                        const rawHours = $('#sw_locum_hours').val().trim();
                        return {
                            name: $('#sw_name').val().trim(),
                            description: $('#sw_desc').val().trim(),
                            locum_hours: rawHours === '' ? '' : rawHours,
                            is_active: $('#sw_active').is(':checked') ? 1 : 0
                        };
                    }
                }).then(res => {
                    if (!res.isConfirmed) return;
                    const d = res.value;
                    $.post(rtUpdateUnit(platformId, unitId), $.param({
                            _method: 'PUT',
                            name: d.name,
                            description: d.description,
                            locum_hours: d.locum_hours,
                            is_active: d.is_active
                        }))
                        .done(resp => {
                            const $r = $(`#unit-row-${unitId}`);
                            $r.find('.unit-name').text(d.name);
                            $r.find('.unit-description').attr('title', d.description || '')
                                .text((d.description || '').slice(0, 60));

                            // Update locum hours cell + button data
                            const platformDefaults = @json($platforms->pluck('locum_hours', 'id'));
                            const platformDefault = platformDefaults[platformId] ?? 8;
                            const $lhCell = $r.find('.unit-locum-hours');
                            if (d.locum_hours && String(d.locum_hours).trim() !== '') {
                                $lhCell.html(
                                    `<span class="badge bg-info-subtle text-info">${esc(String(d.locum_hours))} hrs / locum</span>`
                                );
                                $r.find('.btn-edit-unit').data('locum-hours', d.locum_hours);
                            } else {
                                $lhCell.html(
                                    `<span class="text-muted">Default (${esc(String(platformDefault))} hrs)</span>`
                                );
                                $r.find('.btn-edit-unit').data('locum-hours', '');
                            }

                            $r.find('.unit-active').attr('data-active', d.is_active ? 1 : 0)
                                .html(d.is_active ?
                                    '<span class="badge bg-success">Yes</span>' :
                                    '<span class="badge bg-secondary">No</span>');
                            swal('Success', resp.message || 'Unit updated.', 'success');
                        })
                        .fail(xhr => swal('Error', xhr.responseJSON?.message ||
                            'Failed to update unit.', 'error'));
                });
            });

            /* ===== Delete Unit ===== */
            $(document).on('click', '.btn-delete-unit', function() {
                const unitId = $(this).data('id'),
                    platformId = $(this).data('platform-id');
                Swal.fire({
                        title: 'Delete this unit?',
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#d33'
                    })
                    .then(r => {
                        if (!r.isConfirmed) return;
                        $.post(rtDeleteUnit(platformId, unitId), {
                                _method: 'DELETE'
                            })
                            .done(resp => {
                                $(`#unit-row-${unitId}`).remove();
                                const $tb = $(`#units-body-${platformId}`);
                                if (!$tb.children('tr').length) {
                                    $tb.append(
                                        '<tr class="units-empty"><td colspan="5" class="text-center text-muted py-4">No units for this platform.</td></tr>'
                                    );
                                }
                                updateBadge(platformId, -1);
                                swal('Deleted', resp.message || 'Unit deleted.', 'success');
                            })
                            .fail(xhr => swal('Error', xhr.responseJSON?.message ||
                                'Failed to delete unit.', 'error'));
                    });
            });

            /* ===== Assign Unit Incharge (Department → Users) ===== */
            function loadUsersForDepartment(deptId, $select, $hint, preselectUserId) {
                if (!$select || !$hint) return;
                $hint.text('Loading users…');
                $.get(rtDeptUsers(deptId), {
                        active: 1
                    })
                    .done(resp => {
                        const users = resp?.data || [];
                        $select.empty().append('<option value="">— Select a user —</option>');
                        users.forEach(u => {
                            const txt = u.display_name ?? u.text ?? ((u.fname || u.lname) ? [u.fname, u
                                .lname
                            ].filter(Boolean).join(' ') : ('User #' + u.id));
                            $select.append(new Option(txt, u.id));
                        });
                        $select.prop('disabled', false);
                        $hint.text(`${users.length} user(s) found`);
                        if (preselectUserId) $select.val(preselectUserId);
                    })
                    .fail(xhr => {
                        $hint.text(xhr.responseJSON?.message || `Failed to load users (HTTP ${xhr.status}).`);
                        $select.prop('disabled', true);
                    });
            }

            function openInchargeModal({
                unitId,
                unitName,
                platformId,
                curUser,
                curDept
            }) {
                $('#assign_unit_id').val(unitId);
                $('#assign_platform_id').val(platformId);
                $('#assign_unit_name').val(unitName);
                $('#assign_department_id').val(curDept || '');
                const $sel = $('#assign_user_id');
                $sel.empty().append('<option value="">— Select a user —</option>').prop('disabled', true);
                $('#assign_user_hint').text('Choose department first');
                new bootstrap.Modal('#assignInchargeModal').show();
                if (curDept) loadUsersForDepartment(curDept, $sel, $('#assign_user_hint'), curUser || null);
            }

            $(document).on('click', '.btn-set-incharge', function() {
                openInchargeModal({
                    unitId: $(this).data('unit-id'),
                    unitName: $(this).data('unit-name'),
                    platformId: $(this).data('platform-id'),
                    curUser: $(this).data('current-id'),
                    curDept: $(this).data('current-dept-id') || ''
                });
            });

            $(document).on('change', '#assign_department_id', function() {
                const deptId = this.value;
                const $sel = $('#assign_user_id');
                $sel.empty().append('<option value="">— Select a user —</option>').prop('disabled', true);
                if (!deptId) return $('#assign_user_hint').text('Choose department first');
                loadUsersForDepartment(deptId, $sel, $('#assign_user_hint'), null);
            });

            $(document).on('submit', '#assignInchargeForm', function(e) {
                e.preventDefault();
                const $btn = $('#btnAssignSave');
                spin($btn, true);
                const unitId = $('#assign_unit_id').val(),
                    platformId = $('#assign_platform_id').val();
                const deptId = $('#assign_department_id').val(),
                    userId = $('#assign_user_id').val();
                if (!deptId && userId) {
                    spin($btn, false);
                    return swal('Department required', 'Choose a department first.', 'warning');
                }

                $.post(rtAssignIncharge(platformId, unitId), {
                        department_id: deptId || null,
                        user_id: userId || null
                    })
                    .done(resp => {
                        const $row = $(`#unit-row-${unitId}`),
                            $cell = $row.find('.unit-incharge');
                        if (!userId) {
                            $cell.html('<span class="text-muted incharge-empty">None</span>');
                        } else {
                            const userLabel = $('#assign_user_id option:selected').text() || ('User #' +
                                userId);
                            const deptLabel = $('#assign_department_id option:selected').text() ||
                                'No department';
                            $cell.html(`<div class="d-flex flex-column">
            <span class="badge bg-primary-subtle text-primary incharge-label">${esc(userLabel)}</span>
            <small class="text-muted incharge-dept">${esc(deptLabel)}</small>
          </div>`);
                        }
                        bootstrap.Modal.getInstance(document.getElementById('assignInchargeModal'))
                            .hide();
                        spin($btn, false);
                        swal('Done', resp.message || 'Saved.', 'success');
                    })
                    .fail(xhr => {
                        spin($btn, false);
                        swal('Error', xhr.responseJSON?.message || 'Could not assign incharge.',
                            'error');
                    });
            });

        });
    </script>
@endsection
