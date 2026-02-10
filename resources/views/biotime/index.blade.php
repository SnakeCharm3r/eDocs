@extends('layouts.template')

@section('breadcrumb')
    @include('includes.loader')
    @include('sweetalert::alert')
@endsection

@section('content')
    {{-- Vendor CSS --}}
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" />
    <link rel="stylesheet" href="https://cdn.datatables.net/2.1.2/css/dataTables.dataTables.css" />

    <style>
        .card {
            border-radius: 14px;
        }

        .table thead th {
            white-space: nowrap;
        }

        .kpi {
            border: 1px solid rgba(0, 0, 0, .05);
        }

        .kpi h4 {
            font-size: 18px;
            margin: 0;
        }

        .badge-soft {
            background: rgba(13, 110, 253, .08);
            color: #0d6efd;
        }

        .sticky-actions {
            position: sticky;
            top: 0;
            z-index: 5;
            background: #fff;
        }

        .spinner-inline {
            width: 1rem;
            height: 1rem;
            border-width: .2em;
        }

        .btn-icon {
            display: inline-flex;
            align-items: center;
            gap: .35rem;
        }

        .dt-length,
        .dt-search {
            margin-bottom: .75rem !important;
        }

        .text-mono {
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace;
        }

        .btn-soft-primary {
            background: rgba(13, 110, 253, .08);
            color: #0d6efd;
            border-color: transparent;
        }

        .btn-soft-primary:hover {
            background: rgba(13, 110, 253, .15);
            color: #0b5ed7;
        }

        .form-text-small {
            font-size: .82rem;
            color: #6c757d;
        }

        .child-wrap {
            background: #fbfcfd;
            border: 1px solid #eef2f7;
            border-radius: 12px;
            padding: 12px;
        }

        .child-table td,
        .child-table th {
            padding: .4rem .5rem;
        }

        .avatar-32 {
            width: 34px;
            height: 34px;
            border-radius: 50%;
            object-fit: cover;
        }

        .avatar-fallback {
            width: 34px;
            height: 34px;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: #f1f3f5;
            color: #6c757d;
            font-weight: 600;
        }
    </style>
    <style>
        .card.kpi {
            border: 1px solid rgba(14, 236, 14, 0.08);
            background: radial-gradient(120% 120% at 0% 0%, #ffffff 0%, #f7faff 100%);
            transition: transform 180ms ease, box-shadow 180ms ease, border-color 180ms ease;
            will-change: transform;
        }

        .card.kpi:hover {
            transform: translateY(-6px);
            box-shadow: 0 16px 40px rgba(14, 236, 14, .12);
            border-color: rgba(14, 236, 14, .25);
        }

        .card.kpi:active {
            transform: translateY(-2px);
        }

        .card.kpi .card-body i {
            transition: transform 220ms ease, filter 220ms ease;
        }

        .card.kpi:hover .card-body i {
            transform: translateY(-2px) scale(1.05);
            filter: drop-shadow(0 6px 10px rgba(14, 236, 14, .25));
        }

        @media (prefers-reduced-motion: reduce) {

            .card.kpi,
            .card.kpi .card-body i {
                transition: none;
            }
        }
    </style>

    <div class="page-wrapper">
        <div class="content container-fluid">

            <div class="page-header">
                <div class="row align-items-center">
                    <div class="col">
                        <h3 class="page-title mb-1">BioTime Staff</h3>
                        <ul class="breadcrumb">
                            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                            <li class="breadcrumb-item active">BioTime Staff</li>
                        </ul>
                    </div>
                </div>
            </div>

            @if (session('error'))
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    {{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            <div class="row g-3 mb-3">
                <div class="col-6 col-md-3">
                    <div class="card kpi shadow-sm">
                        <div class="card-body d-flex align-items-center justify-content-between">
                            <div>
                                <div class="text-muted small">Month</div>
                                <h4 class="mb-0">{{ \Carbon\Carbon::create($year, $month, 1)->format('F Y') }}</h4>
                            </div>
                            <i class="fas fa-calendar-alt text-primary fs-4"></i>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="card kpi shadow-sm">
                        <div class="card-body d-flex align-items-center justify-content-between">
                            <div>
                                <div class="text-muted small">Staff Listed</div>
                                <h4 class="mb-0">{{ count($records) }}</h4>
                            </div>
                            <i class="fas fa-users text-primary fs-4"></i>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="card kpi shadow-sm">
                        <div class="card-body d-flex align-items-center justify-content-between">
                            <div>
                                <div class="text-muted small">Departments</div>
                                <h4 class="mb-0">
                                    {{ max(1, collect($records)->pluck('department')->filter()->unique()->count()) }}
                                </h4>
                            </div>
                            <i class="fas fa-sitemap text-primary fs-4"></i>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="card kpi shadow-sm">
                        <div class="card-body d-flex align-items-center justify-content-between">
                            <div>
                                <div class="text-muted small">Latest Punch</div>
                                @php
                                    $lpText = $latestPunchText ?? null;
                                    $lpName = $latestPunchName ?? null;
                                @endphp
                                <h4 class="mb-0 text-mono" style="font-size: 16px;">
                                    {{ $lpText ? $lpText . ($lpName ? ' • ' . $lpName : '') : 'N/A' }}
                                </h4>
                            </div>
                            <i class="fas fa-fingerprint text-primary fs-4"></i>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card mb-4 shadow-sm">
                <div class="card-header bg-light">
                    <h5 class="card-title mb-0">Filter Attendance Records</h5>
                </div>
                <div class="card-body">
                    <form method="GET" action="{{ route('biotime.index') }}" id="searchForm">
                        <div class="row g-3">
                            <div class="col-md-3">
                                <label for="month" class="form-label fw-medium">Month</label>
                                <select name="month" id="month" class="form-select">
                                    @for ($m = 1; $m <= 12; $m++)
                                        <option value="{{ $m }}" {{ (int) $month === $m ? 'selected' : '' }}>
                                            {{ date('F', mktime(0, 0, 0, $m, 1)) }}
                                        </option>
                                    @endfor
                                </select>
                                <div class="form-text form-text-small">Auto-applies on change</div>
                            </div>
                            <div class="col-md-3">
                                <label for="year" class="form-label fw-medium">Year</label>
                                <select name="year" id="year" class="form-select">
                                    @for ($y = date('Y') - 5; $y <= date('Y'); $y++)
                                        <option value="{{ $y }}"
                                            {{ (int) $year === (int) $y ? 'selected' : '' }}>
                                            {{ $y }}
                                        </option>
                                    @endfor
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label for="department_id" class="form-label fw-medium">Department</label>
                                <select name="department_id" id="department_id" class="form-select">
                                    <option value="">— All Departments —</option>
                                    @foreach ($departments as $d)
                                        <option value="{{ $d->id }}"
                                            {{ (string) ($department_id ?? '') === (string) $d->id ? 'selected' : '' }}>
                                            {{ $d->dept_name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label for="search" class="form-label fw-medium">Quick Search</label>
                                <input type="text" name="search" id="search" class="form-control"
                                    placeholder="Name, Code, or Dept" value="{{ $search }}">
                            </div>
                        </div>
                        <div class="mt-3 d-flex align-items-center gap-2">
                            <button type="submit" class="btn btn-primary btn-icon">
                                <i class="fas fa-filter"></i><span>Apply Filters</span>
                            </button>
                            <a href="{{ route('biotime.index') }}" class="btn btn-outline-secondary btn-icon">
                                <i class="fas fa-rotate-left"></i><span>Reset</span>
                            </a>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card mb-3 shadow-sm sticky-actions">
                <div class="card-body d-flex align-items-center justify-content-between flex-wrap gap-2">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="selectAll">
                        <label class="form-check-label" for="selectAll">Select All</label>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <form id="exportForm" method="POST" action="{{ route('biotime.export') }}">
                            @csrf
                            <input type="hidden" name="department_id" id="exportDept"
                                value="{{ $department_id ?? '' }}">
                            <input type="hidden" name="month" value="{{ $month }}">
                            <input type="hidden" name="year" value="{{ $year }}">
                            <button type="submit" class="btn btn-success btn-icon">
                                <i class="fas fa-file-excel"></i><span>Export Selected</span>
                            </button>
                        </form>
                        <button type="button" class="btn btn-soft-primary btn-icon" id="btnExpandChecked">
                            <i class="fas fa-chevron-down"></i><span>Expand Selected</span>
                        </button>
                    </div>
                </div>
            </div>

            <div class="card shadow-sm">
                <div class="card-header bg-light">
                    <h5 class="card-title mb-0">Attendance Summary</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover table-striped align-middle" id="userTable" style="width:100%">
                            <thead class="table-light">
                                <tr>
                                    <th style="width:36px;"><input type="checkbox" id="selectAllTable"></th>
                                    <th style="width:50px;">#</th>
                                    <th>Department</th>
                                    <th>User ID</th>
                                    <th>Name</th>
                                    <th>Punch Days</th>
                                    <th>Latest Punch</th>
                                    <th style="width: 180px;">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($records as $record)
                                    <tr data-userid="{{ $record->userid }}" data-name="{{ $record->name }}"
                                        data-code="{{ $record->userid }}" data-month="{{ $month }}"
                                        data-year="{{ $year }}">
                                        <td><input type="checkbox" class="row-check" value="{{ $record->userid }}"></td>
                                        <td>{{ $loop->iteration }}</td>
                                        <td>{{ $record->department }}</td>
                                        <td class="text-mono">{{ $record->userid }}</td>
                                        <td>
                                            <div class="d-flex align-items-center gap-2">
                                                @php
                                                    $initials = collect(explode(' ', trim($record->name ?? '')))
                                                        ->filter()
                                                        ->map(fn($w) => mb_substr($w, 0, 1))
                                                        ->take(2)
                                                        ->implode('');
                                                    $hasPhoto = !empty($record->photo_url);
                                                @endphp

                                                @if ($hasPhoto)
                                                    <img src="{{ $record->photo_url }}" alt="{{ $record->name }}"
                                                        class="avatar-32"
                                                        onerror="this.style.display='none'; this.nextElementSibling.classList.remove('d-none');">
                                                    <span class="avatar-fallback d-none">{{ $initials ?: 'U' }}</span>
                                                @else
                                                    <span class="avatar-fallback">{{ $initials ?: 'U' }}</span>
                                                @endif

                                                <span>{{ $record->name }}</span>
                                            </div>
                                        </td>
                                        <td><span class="badge rounded-pill badge-soft">{{ $record->punch_days }}</span>
                                        </td>
                                        <td class="text-mono">{{ $record->latest_punch }}</td>
                                        <td>
                                            <div class="btn-group">
                                                <button type="button"
                                                    class="btn btn-sm btn-outline-secondary toggle-row btn-icon">
                                                    <i class="fas fa-chevron-down"></i><span>Expand</span>
                                                </button>
                                                <button type="button"
                                                    class="btn btn-sm btn-outline-primary view-details btn-icon">
                                                    <i class="fas fa-eye"></i><span>View</span>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="text-center text-muted py-4">
                                            No records found. Adjust filters or check the selected period.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="modal fade" id="detailModal" tabindex="-1" aria-labelledby="detailModalLabel"
                aria-hidden="true">
                <div class="modal-dialog modal-xxl modal-fullscreen-md-down modal-dialog-scrollable">
                    <div class="modal-content">
                        <div class="modal-header">
                            <div>
                                <h5 class="modal-title" id="detailModalLabel">Attendance Details</h5>
                                <div class="small text-muted" id="detailSub"></div>
                            </div>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"
                                aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <div class="table-responsive">
                                <table class="table table-sm table-hover table-bordered" id="tableAllTx">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Date</th>
                                            <th>Time</th>
                                            <th>Direction</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td colspan="3" class="text-center text-muted">
                                                <div class="d-inline-flex align-items-center gap-2">
                                                    <div class="spinner-border spinner-inline" role="status"></div>
                                                    <span>Loading…</span>
                                                </div>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <div class="me-auto">
                                <span class="fw-medium">Total Month Hours:</span>
                                <span class="text-mono" id="modalTotalHours">Calculating...</span>
                            </div>
                            <button type="button" class="btn btn-outline-secondary"
                                data-bs-dismiss="modal">Close</button>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
@endsection

@push('scripts')
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/2.1.2/js/dataTables.js"></script>

    <script>
        (function($) {
            const routeIndex = @json(route('biotime.index'));
            const month = @json($month);
            const year = @json($year);

            const pad2 = n => (n < 10 ? '0' + n : '' + n);
            const minutesBetween = (start, end) => Math.round((end - start) / 60000);

            function toHHMM(mins) {
                mins = Math.max(0, Math.round(mins));
                const h = Math.floor(mins / 60),
                    m = mins % 60;
                return (h < 10 ? '0' : '') + h + ':' + (m < 10 ? '0' : '') + m;
            }

            function parseDT(dateStr, timeStr) {
                return new Date(`${dateStr}T${(timeStr || '00:00')}:00`);
            }

            function cmpDT(a, b) {
                return a.getTime() - b.getTime();
            }

            function normalizePunches(raw) {
                const out = [];
                raw.forEach(r => {
                    const d = (r.punch_date || '').trim();
                    const t = (r.punch_time || '').trim();
                    if (!d || !/^\d{4}-\d{2}-\d{2}$/.test(d)) return;
                    const dt = parseDT(d, (t || '00:00:00').slice(0, 5));
                    const dirTxt = (r.direction || '').toLowerCase();
                    out.push({
                        date: d,
                        time: (t || '00:00:00'),
                        dt,
                        dir: dirTxt.includes('in') ? 'IN' : (dirTxt.includes('out') ? 'OUT' : 'UNK')
                    });
                });
                out.sort((a, b) => cmpDT(a.dt, b.dt));
                return out;
            }

            function buildSessions(punches, MAX_GAP_HOURS = 36) {
                const sessions = [];
                let totalMinutes = 0;

                const pushSession = (start, end) => {
                    if (!start || !end) return;
                    const mins = minutesBetween(start.dt, end.dt);
                    if (mins <= 0) return;
                    sessions.push({
                        startDate: start.date,
                        startTime: start.time,
                        startDT: start.dt,
                        endDate: end.date,
                        endTime: end.time,
                        endDT: end.dt,
                        minutes: mins
                    });
                    totalMinutes += mins;
                    console.log(`Session: ${start.date} ${start.time} -> ${end.date} ${end.time} = ${mins} mins`);
                };

                for (let i = 0; i < punches.length; i++) {
                    const p = punches[i];
                    if (p.dir !== 'IN') continue; // Only start with IN
                    let j = i + 1;
                    while (j < punches.length) {
                        const next = punches[j];
                        const gapH = minutesBetween(p.dt, next.dt) / 60;
                        if (gapH > MAX_GAP_HOURS || gapH <= 0) break;
                        if (next.dir === 'OUT') {
                            pushSession(p, next);
                            i = j; // Skip the paired OUT
                            break;
                        }
                        j++;
                    }
                }

                console.log('Total sessions:', sessions.length, 'Total minutes:', totalMinutes);
                return {
                    sessions,
                    totalMinutes
                };
            }

            function sessionsToDaily(sessions, totalMinutes) {
                const days = {};
                sessions.forEach(s => {
                    const key = s.startDate;
                    if (!days[key]) {
                        days[key] = {
                            firstIn: s.startTime,
                            lastOut: s.endTime,
                            totalMin: 0,
                            lastOutDT: s.endDT
                        };
                    } else {
                        if (s.startDT < parseDT(key, days[key].firstIn.slice(0, 5))) days[key].firstIn = s
                            .startTime;
                        if (s.endDT > days[key].lastOutDT) {
                            days[key].lastOut = s.endTime;
                            days[key].lastOutDT = s.endDT;
                        }
                    }
                    days[key].totalMin += s.minutes;
                });
                return {
                    daily: Object.keys(days).sort().map(d => {
                        const total = days[d].totalMin,
                            ot = Math.max(0, total - 8 * 60);
                        return {
                            date: d,
                            in: days[d].firstIn || '-',
                            out: days[d].lastOut || '-',
                            total: toHHMM(total),
                            ot: toHHMM(ot)
                        };
                    }),
                    totalHours: toHHMM(totalMinutes)
                };
            }

            function fetchRaw(userId) {
                return $.ajax({
                    url: routeIndex,
                    method: 'GET',
                    dataType: 'json',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    },
                    data: {
                        user_id: userId,
                        month: month,
                        year: year
                    }
                });
            }

            const dt = new DataTable('#userTable', {
                paging: true,
                searching: true,
                ordering: true,
                order: [
                    [2, 'asc'],
                    [4, 'asc']
                ],
                stateSave: true,
                columnDefs: [{
                    orderable: false,
                    targets: [0, 7]
                }]
            });

            const $form = $('#searchForm');

            function autoSubmit() {
                $form.find('button[type=submit]').prop('disabled', true)
                    .html('<i class="fas fa-spinner fa-spin me-2"></i>Filtering…');
                $form.trigger('submit');
            }

            $(document).on('change', '#month, #year, #department_id', autoSubmit);
            $(document).on('input', '#search', function() {
                dt.search(this.value).draw();
            });
            $(document).on('change', '#department_id', function() {
                $('#exportDept').val($(this).val());
            });

            function syncSelectAllState() {
                const total = $('.row-check').length,
                    checked = $('.row-check:checked').length;
                $('#selectAll, #selectAllTable').prop('checked', total > 0 && checked === total);
            }

            $(document).on('change', '#selectAll, #selectAllTable', function() {
                const checked = this.checked;
                $('.row-check').prop('checked', checked);
                $('#selectAll, #selectAllTable').prop('checked', checked);
            });

            $(document).on('change', '.row-check', syncSelectAllState);

            $(document).on('submit', '#exportForm', function() {
                $(this).find('input[name="user_ids[]"]').remove();
                const selected = $('.row-check:checked').map(function() {
                    return this.value;
                }).get();
                if (!selected.length) {
                    alert('Please select at least one user to export.');
                    return false;
                }
                selected.forEach(uid => $('<input>').attr({
                    type: 'hidden',
                    name: 'user_ids[]',
                    value: uid
                }).appendTo('#exportForm'));
            });

            function buildChildHTML(dailyRows, totalHours) {
                return `
                <div class="child-wrap">
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered mb-0 child-table">
                            <thead class="table-light">
                                <tr>
                                    <th>Date</th>
                                    <th>Check In</th>
                                    <th>Check Out</th>
                                    <th>Total (HH:MM)</th>
                                    <th>OT > 8h (HH:MM)</th>
                                </tr>
                            </thead>
                            <tbody>
                                ${
                                    dailyRows.length
                                        ? dailyRows.map(r => `
                                                    <tr>
                                                        <td>${r.date}</td>
                                                        <td class="text-mono">${r.in}</td>
                                                        <td class="text-mono">${r.out}</td>
                                                        <td class="text-mono">${r.total}</td>
                                                        <td class="text-mono">${r.ot}</td>
                                                    </tr>`).join('')
                                        : `<tr><td colspan="5" class="text-center text-muted">No data.</td></tr>`
                                }
                                <tr>
                                    <td colspan="3" class="text-end fw-medium">Total Month Hours:</td>
                                    <td class="text-mono fw-medium">${totalHours}</td>
                                    <td></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>`;
            }

            $(document).on('click', '.toggle-row', function() {
                const tr = $(this).closest('tr');
                const row = dt.row(tr);
                if (row.child.isShown()) {
                    row.child.hide();
                    tr.removeClass('shown');
                    return;
                }
                row.child(`<div class="child-wrap"><div class="d-inline-flex align-items-center gap-2">
                    <div class="spinner-border spinner-inline" role="status"></div><span>Loading daily summary…</span>
                </div></div>`).show();
                tr.addClass('shown');

                const userId = tr.data('userid');
                fetchRaw(userId).done(resp => {
                    const punches = normalizePunches(resp.rawRecords || []);
                    console.log('Raw punches:', punches);
                    const {
                        sessions,
                        totalMinutes
                    } = buildSessions(punches, 36);
                    const {
                        daily,
                        totalHours
                    } = sessionsToDaily(sessions, totalMinutes);
                    row.child(buildChildHTML(daily, totalHours)).show();
                }).fail(() => row.child(`<div class="child-wrap text-danger">Failed to load summary.</div>`)
                    .show());
            });

            function renderAllTransactions(rawRows) {
                const $body = $('#tableAllTx tbody').empty();
                rawRows.sort((a, b) => ((a.punch_date || '') + ' ' + (a.punch_time || '')).localeCompare((b
                    .punch_date || '') + ' ' + (b.punch_time || '')));
                if (rawRows.length) {
                    rawRows.forEach(r => $body.append(`<tr>
                        <td>${r.punch_date || '-'}</td>
                        <td class="text-mono">${r.punch_time || '-'}</td>
                        <td>${r.direction || 'Punch'}</td>
                    </tr>`));
                } else {
                    $body.append(`<tr><td colspan="3" class="text-center text-muted">No transactions found.</td></tr>`);
                }
            }

            $(document).on('click', '.view-details', function() {
                const tr = $(this).closest('tr');
                const userId = tr.data('userid');
                const name = tr.data('name');
                const code = tr.data('code');

                $('#tableAllTx tbody').html(`<tr><td colspan="3" class="text-center text-muted">
                    <div class="d-inline-flex align-items-center gap-2">
                        <div class="spinner-border spinner-inline" role="status"></div>
                        <span>Loading…</span>
                    </div>
                </td></tr>`);
                $('#modalTotalHours').text('Calculating...');

                const modalEl = document.getElementById('detailModal');
                const modal = new bootstrap.Modal(modalEl);
                modal.show();

                $('#detailSub').text(`User: ${name} • Code: ${code} • Period: ${pad2(month)}/${year}`);

                fetchRaw(userId).done(resp => {
                    const punches = normalizePunches(resp.rawRecords || []);
                    console.log('Raw punches for modal:', punches);
                    const {
                        sessions,
                        totalMinutes
                    } = buildSessions(punches, 36);
                    const {
                        totalHours
                    } = sessionsToDaily(sessions, totalMinutes);
                    renderAllTransactions(resp.rawRecords || []);
                    $('#modalTotalHours').text(totalHours);
                }).fail(xhr => {
                    const msg = (xhr.responseJSON && xhr.responseJSON.error) ? xhr.responseJSON.error :
                        'Failed to load details.';
                    $('#tableAllTx tbody').html(
                        `<tr><td colspan="3" class="text-danger">${msg}</td></tr>`);
                    $('#modalTotalHours').text('Error');
                });
            });

            $(document).on('click', '#btnExpandChecked', function() {
                $('.row-check:checked').each(function() {
                    const tr = $(this).closest('tr');
                    const row = dt.row(tr);
                    if (!row.child.isShown()) tr.find('.toggle-row').trigger('click');
                });
            });

            if (!window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
                document.querySelectorAll('.card.kpi').forEach(card => {
                    card.style.transformStyle = 'preserve-3d';
                    card.addEventListener('mousemove', e => {
                        const r = card.getBoundingClientRect();
                        const x = (e.clientX - r.left) / r.width;
                        const y = (e.clientY - r.top) / r.height;
                        const rx = (0.5 - y) * 10;
                        const ry = (x - 0.5) * 10;
                        card.style.transform =
                            `perspective(800px) rotateX(${rx}deg) rotateY(${ry}deg) translateY(-4px)`;
                        card.style.boxShadow = '0 16px 40px rgba(13,110,253,.12)';
                    });
                    card.addEventListener('mouseleave', () => {
                        card.style.transform = '';
                        card.style.boxShadow = '';
                    });
                });
            }
        })(jQuery);
    </script>
@endpush
