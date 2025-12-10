@extends('layouts.template')

@section('breadcrumb')
    @include('includes.loader')
    @include('sweetalert::alert')
@endsection

@section('content')
    <style>
        .text-mono {
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace;
        }

        .child-wrap {
            background: #fbfcfd;
            border: 1px solid #eef2f7;
            border-radius: 12px;
            padding: 12px;
        }
    </style>

    <div class="page-wrapper">
        <div class="content container-fluid">

            <!-- Header -->
            <div class="page-header">
                <div class="row">
                    <div class="col-sm-12">
                        <div class="page-sub-header d-flex align-items-center justify-content-between flex-wrap gap-2">
                            <div>
                                <h3 class="page-title mb-1">
                                    Clock History for
                                    <span class="text-primary text-decoration-underline">
                                        {{ $user->name ?? 'User ID: ' . $user->userid }}
                                    </span>
                                </h3>
                                <div class="text-muted">
                                    <strong>Employee Code:</strong> {{ $user->userid ?? '—' }}
                                    @if (!empty($user->department))
                                        &nbsp; | &nbsp; <strong>Department:</strong> {{ $user->department }}
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            @if (session('error'))
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    {{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            <!-- Filters + Export -->
            <div class="card mb-3">
                <div class="card-body">
                    <div class="row g-3 align-items-end">
                        <div class="col-md-3">
                            <label for="year" class="form-label">Year</label>
                            <form id="filterForm" method="GET">
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
                            <label for="month" class="form-label">Month</label>
                            <select name="month" id="month" class="form-select">
                                @for ($m = 1; $m <= 12; $m++)
                                    <option value="{{ $m }}" {{ (int) $month === $m ? 'selected' : '' }}>
                                        {{ date('F', mktime(0, 0, 0, $m, 1)) }}
                                    </option>
                                @endfor
                            </select>
                        </div>
                        <div class="col-md-6 d-flex gap-2 justify-content-end">
                            <button type="submit" class="btn btn-primary">Filter</button>
                            </form>

                            <form id="exportForm" method="POST" action="{{ route('biotime.export') }}">
                                @csrf
                                <input type="hidden" name="department_id" value="">
                                <input type="hidden" name="month" id="exportMonth" value="{{ $month }}">
                                <input type="hidden" name="year" id="exportYear" value="{{ $year }}">
                                <input type="hidden" name="user_ids[]" value="{{ $user->userid }}">
                                <button type="submit" class="btn btn-success">
                                    <i class="fas fa-file-excel me-1"></i> Export
                                </button>
                            </form>

                        </div>
                    </div>
                </div>
            </div>

            <!-- Daily table -->
            <div class="row">
                <div class="col-md-12">
                    <div class="card shadow-sm">
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered table-hover align-middle" id="userPunchTable">
                                    <thead class="table-light">
                                        <tr>
                                            <th>#</th>
                                            <th>Date (MM/DD/YYYY)</th>
                                            <th>Clock In</th>
                                            <th>Clock Out</th>
                                            <th>Total (HH:MM)</th>
                                            <th>OT &gt; 8 hrs (HH:MM)</th>
                                            <th>Status</th>
                                            <th style="width:220px;">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td colspan="8" class="text-center text-muted">Loading…</td>
                                        </tr>
                                    </tbody>
                                    <tfoot>
                                        <tr>
                                            <th colspan="4" class="text-end">Monthly Totals (<span
                                                    id="daysCount">0</span> day(s))</th>
                                            <th id="sumHM" class="text-mono">00:00</th>
                                            <th id="sumOTHM" class="text-mono">00:00</th>
                                            <th colspan="2"></th>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                    </div>

                    <div class="mt-3">
                        <a href="{{ url()->previous() }}" class="btn btn-secondary">← Back</a>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <!-- Day Modal -->
    <div class="modal fade" id="dayModal" tabindex="-1" aria-labelledby="dayModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="dayModalLabel">Day Transactions</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>Date</th>
                                    <th>Time</th>
                                    <th>Direction</th>
                                </tr>
                            </thead>
                            <tbody id="dayModalBody">
                                <tr>
                                    <td colspan="3" class="text-center text-muted">No data.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        (function($) {
            // Raw punches for this user/month from controller
            const RAW = @json($rawRecords ?? []);

            // ---------- Utils ----------
            const ONE_HOUR = 3600000;

            function localTodayIso() {
                const d = new Date();
                const y = d.getFullYear();
                const m = String(d.getMonth() + 1).padStart(2, '0');
                const day = String(d.getDate()).padStart(2, '0');
                return `${y}-${m}-${day}`;
            }

            // Parse in local time (robust across browsers)
            function parseDT(d, t) {
                if (!d) return new Date(NaN);
                const [y, m, day] = d.split('-').map(Number);
                let HH = 0,
                    MM = 0,
                    SS = 0;
                if (t) {
                    const parts = String(t).split(':');
                    HH = Number(parts[0] || 0);
                    MM = Number(parts[1] || 0);
                    SS = Number((parts[2] || '0').slice(0, 2));
                }
                return new Date(y, (m || 1) - 1, day || 1, HH, MM, SS);
            }

            const toHM = mins => {
                mins = Math.max(0, Math.round(mins));
                const h = Math.floor(mins / 60),
                    m = mins % 60;
                return String(h).padStart(2, '0') + ':' + String(m).padStart(2, '0');
            };

            // US formatting helpers (presentation only)
            function usDate(iso) {
                if (!iso || !/^\d{4}-\d{2}-\d{2}$/.test(iso)) return iso || '';
                const [y, m, d] = iso.split('-');
                return `${m}/${d}/${y}`;
            }

            function usTime(hms) {
                if (!hms) return '';
                // Return the time in 24-hour format (HH:MM)
                return String(hms).slice(0, 5); // e.g., '14:30:00' -> '14:30'
            }

            const labelDirection = txt => {
                const t = (txt || '').toLowerCase();
                return t.includes('in') ? 'Clock In' : t.includes('out') ? 'Clock Out' : 'Clock';
            };

            // Normalize & sort
            function normalize(raw) {
                const out = [];
                for (const r of raw) {
                    const d = (r.punch_date || '').trim();
                    if (!d) continue;
                    const t = (r.punch_time || '00:00:00').trim();
                    const txt = (r.direction || '').toLowerCase();
                    const dir = txt.includes('in') ? 'IN' : (txt.includes('out') ? 'OUT' : 'UNK');
                    out.push({
                        date: d,
                        time: t,
                        dt: parseDT(d, t),
                        dir
                    });
                }
                out.sort((a, b) => a.dt - b.dt);
                return out;
            }

            // Pair IN→OUT across midnight (up to 36h)
            function buildSessions(punches, MAX_GAP_HOURS = 36) {
                const sessions = [];
                let open = null;

                const push = (a, b) => {
                    if (!a || !b) return;
                    const mins = Math.round((b.dt - a.dt) / 60000);
                    if (mins > 0) sessions.push({
                        startDate: a.date,
                        startTime: a.time,
                        startDT: a.dt,
                        endDate: b.date,
                        endTime: b.time,
                        endDT: b.dt,
                        minutes: mins
                    });
                };

                for (const p of punches) {
                    const dir = (p.dir === 'UNK') ? (open ? 'OUT' : 'IN') : p.dir;
                    if (dir === 'IN') {
                        if (open) {
                            const gap = (p.dt - open.dt) / ONE_HOUR;
                            if (gap > 0 && gap <= MAX_GAP_HOURS) push(open, p);
                        }
                        open = {
                            dt: p.dt,
                            date: p.date,
                            time: p.time
                        };
                    } else { // OUT
                        if (open) {
                            const gap = (p.dt - open.dt) / ONE_HOUR;
                            if (gap > 0 && gap <= MAX_GAP_HOURS) {
                                push(open, p);
                                open = null;
                            } else {
                                open = null;
                            }
                        }
                    }
                }
                return {
                    sessions,
                    openStart: open
                };
            }

            // Aggregate per start-day, handle open sessions
            function sessionsToDaily({
                sessions,
                openStart
            }) {
                const days = {};
                const ensure = d => days[d] ??= {
                    firstIn: null,
                    firstInDT: null,
                    lastOut: null,
                    lastOutDT: null,
                    totalMin: 0,
                    hasOpen: false,
                    openDT: null
                };
                for (const s of sessions) {
                    const d = ensure(s.startDate);
                    if (!d.firstInDT || s.startDT < d.firstInDT) {
                        d.firstInDT = s.startDT;
                        d.firstIn = s.startTime;
                    }
                    if (!d.lastOutDT || s.endDT > d.lastOutDT) {
                        d.lastOutDT = s.endDT;
                        d.lastOut = s.endTime;
                    }
                    d.totalMin += s.minutes;
                }
                if (openStart) {
                    const d = ensure(openStart.date);
                    if (!d.firstInDT || openStart.dt < d.firstInDT) {
                        d.firstInDT = openStart.dt;
                        d.firstIn = openStart.time;
                    }
                    d.hasOpen = true;
                    d.openDT = openStart.dt;
                }

                const out = [],
                    keys = Object.keys(days).sort(),
                    today = localTodayIso();
                for (const k of keys) {
                    const d = days[k];
                    let total = d.totalMin;
                    let status = 'OK';
                    if (d.hasOpen) {
                        if (k === today) {
                            total += Math.max(0, Math.round((Date.now() - d.openDT.getTime()) / 60000));
                            status = 'Still In';
                        } else {
                            status = 'Incomplete';
                        }
                    }
                    out.push({
                        date: k,
                        in: d.firstIn || '',
                        out: d.lastOut || '',
                        totalMin: total,
                        totalHM: toHM(total),
                        otHM: toHM(Math.max(0, total - 8 * 60)),
                        status
                    });
                }
                return out;
            }

            // Group for expand/modal with US-friendly direction labels
            const byDate = {};
            (RAW || []).forEach(r => {
                const d = (r.punch_date || '').trim();
                if (!d) return;
                (byDate[d] ||= []).push({
                    punch_date: r.punch_date,
                    punch_time: r.punch_time,
                    direction: labelDirection(r.direction)
                });
            });
            Object.keys(byDate).forEach(d => {
                byDate[d].sort((a, b) =>
                    ((a.punch_date || '') + ' ' + (a.punch_time || '')).localeCompare((b.punch_date || '') +
                        ' ' + (b.punch_time || ''))
                );
            });

            const badge = s => s === 'Still In' ? '<span class="badge bg-info">Still In</span>' :
                s === 'Incomplete' ? '<span class="badge bg-warning text-dark">Incomplete</span>' :
                s === 'OK' ? '<span class="badge bg-success">OK</span>' :
                '<span class="badge bg-secondary">—</span>';

            // Render daily rows (US formatting)
            const punches = normalize(RAW);
            const daily = sessionsToDaily(buildSessions(punches, 36));

            const $tb = $('#userPunchTable tbody').empty();
            let sum = 0,
                sumOT = 0,
                days = 0;

            if (!daily.length) {
                $tb.append('<tr><td colspan="8" class="text-center text-muted">No records for this period.</td></tr>');
            } else {
                daily.forEach((r, i) => {
                    const count = (r.status === 'OK' || r.status === 'Still In') && r.totalMin > 0;
                    if (count) {
                        sum += r.totalMin;
                        sumOT += Math.max(0, r.totalMin - 8 * 60);
                        days++;
                    }

                    $tb.append(`
                        <tr data-date="${r.date}">
                            <td>${i + 1}</td>
                            <td>${usDate(r.date)}</td>
                            <td class="text-mono">${r.in ? usTime(r.in) : ''}</td>
                            <td class="text-mono">${r.out ? usTime(r.out) : ''}</td>
                            <td class="text-mono">${count ? r.totalHM : ''}</td>
                            <td class="text-mono">${count ? r.otHM : ''}</td>
                            <td>${badge(r.status)}</td>
                            <td>
                                <div class="btn-group">
                                    <button type="button" class="btn btn-sm btn-outline-secondary btn-expand">
                                        <i class="fas fa-chevron-down me-1"></i>Expand
                                    </button>
                                    <button type="button" class="btn btn-sm btn-outline-primary btn-view">
                                        <i class="fas fa-eye me-1"></i>View
                                    </button>
                                </div>
                            </td>
                        </tr>
                    `);
                });
            }

            $('#daysCount').text(days);
            $('#sumHM').text(toHM(sum));
            $('#sumOTHM').text(toHM(sumOT));

            // Expand inline (US date/time + Clock labels)
            function childHtml(date) {
                const arr = byDate[date] || [];
                if (!arr.length) return `<div class="p-2 text-muted">No transactions for ${usDate(date)}.</div>`;
                return `<div class="child-wrap"><div class="table-responsive">
                    <table class="table table-sm table-bordered mb-0">
                        <thead class="table-light"><tr><th>Date</th><th>Time</th><th>Direction</th></tr></thead>
                        <tbody>${
                            arr.map(r => `
                                            <tr>
                                                <td>${usDate(r.punch_date)}</td>
                                                <td class="text-mono">${usTime(r.punch_time)}</td>
                                                <td>${r.direction}</td>
                                            </tr>
                                        `).join('')
                        }</tbody>
                    </table>
                </div></div>`;
            }

            $(document).on('click', '.btn-expand', function() {
                const $tr = $(this).closest('tr'),
                    date = $tr.data('date'),
                    $next = $tr.next('.child-row');
                if ($next.length) {
                    $next.remove();
                    return;
                }
                $('<tr class="child-row"><td colspan="8">' + childHtml(date) + '</td></tr>').insertAfter($tr);
            });

            // Modal (all transactions for a day) with US formatting
            const dayModal = new bootstrap.Modal(document.getElementById('dayModal'));
            $(document).on('click', '.btn-view', function() {
                const date = $(this).closest('tr').data('date');
                $('#dayModalLabel').text(`Day Transactions • ${usDate(date)}`);
                const $body = $('#dayModalBody').empty();
                const arr = byDate[date] || [];
                if (arr.length) {
                    arr.forEach(r => $body.append(
                        `<tr><td>${usDate(r.punch_date)}</td><td class="text-mono">${usTime(r.punch_time)}</td><td>${r.direction}</td></tr>`
                    ));
                } else {
                    $body.html('<tr><td colspan="3" class="text-center text-muted">No data.</td></tr>');
                }
                dayModal.show();
            });

            // Auto-submit on filter change + keep export values in sync
            $('#month,#year').on('change', function() {
                $('#exportMonth').val($('#month').val());
                $('#exportYear').val($('#year').val());
                $('#filterForm').find('button[type=submit]').prop('disabled', true).text('Filtering…');
                $('#filterForm').trigger('submit');
            });

        })(jQuery);
    </script>
@endpush
