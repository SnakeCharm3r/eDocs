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

        .stat-card {
            background: #ffffff;
            color: #333;
            border: 1px solid #e0e0e0;
            border-radius: 10px;
            padding: 1rem 1.2rem;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.06);
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.12);
        }

        .stat-card.primary {
            background: #ffffff;
            border-left: 4px solid #4e73df;
        }

        .stat-card.success {
            background: #ffffff;
            border-left: 4px solid #1cc88a;
        }

        .stat-card.info {
            background: #ffffff;
            border-left: 4px solid #36b9cc;
        }

        .stat-card.warning {
            background: #ffffff;
            border-left: 4px solid #f6c23e;
        }

        .stat-value {
            font-size: 1.5rem;
            font-weight: 700;
            margin: 0.3rem 0;
            color: #333;
        }

        .stat-label {
            font-size: 0.8rem;
            color: #666;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-weight: 600;
        }

        .stat-card small {
            color: #888;
            font-size: 0.78rem;
        }

        .stat-icon {
            font-size: 1.8rem;
            position: absolute;
            right: 1rem;
            top: 50%;
            transform: translateY(-50%);
            opacity: 0.6;
        }
        
        .stat-icon i {
            color: inherit;
        }

        .attendance-table {
            font-size: 0.9rem;
        }

        .attendance-table thead th {
            background-color: #f8f9fa;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.75rem;
            letter-spacing: 0.5px;
            border-bottom: 2px solid #dee2e6;
        }

        .attendance-table tbody tr:hover {
            background-color: #f8f9fa;
        }

        .late-arrival {
            color: #dc3545;
            font-weight: 600;
        }

        .early-departure {
            color: #fd7e14;
            font-weight: 600;
        }

        .on-time {
            color: #198754;
        }

        .pattern-badge {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
        }

        @media (max-width: 768px) {
            .stat-card {
                margin-bottom: 1rem;
            }
            .attendance-table {
                font-size: 0.8rem;
            }
            .stat-value {
                font-size: 1.2rem;
            }
            .stat-icon {
                font-size: 1.4rem;
            }
        }
    </style>

    <div class="page-wrapper">
        <div class="content container-fluid">

            <!-- Header -->
            <div class="page-header mb-4">
                <div class="card shadow-sm border-0" style="border-left: 5px solid #61ce70 !important;">
                    <div class="card-body py-3">
                        <div class="d-flex align-items-center gap-3 flex-wrap">
                            <div class="rounded-circle d-flex align-items-center justify-content-center"
                                 style="width: 56px; height: 56px; background: linear-gradient(135deg, #61ce70, #36b9cc); color: #fff; font-size: 1.5rem; font-weight: 700; flex-shrink: 0;">
                                {{ strtoupper(substr($user->name ?? 'U', 0, 1)) }}{{ strtoupper(substr(explode(' ', $user->name ?? 'U')[1] ?? '', 0, 1)) }}
                            </div>
                            <div class="flex-grow-1">
                                <h3 class="page-title mb-1" style="font-size: 1.35rem;">
                                    {{ $user->name ?? 'User ID: ' . $user->userid }}
                                </h3>
                                <div class="d-flex flex-wrap gap-3 text-muted" style="font-size: 0.9rem;">
                                    <span><i class="fas fa-id-badge me-1" style="color: #4e73df;"></i> {{ $user->userid ?? '—' }}</span>
                                    @if (!empty($user->department))
                                        <span><i class="fas fa-building me-1" style="color: #1cc88a;"></i> {{ $user->department }}</span>
                                    @endif
                                    <span><i class="fas fa-calendar-alt me-1" style="color: #f6c23e;"></i> {{ date('F Y', mktime(0, 0, 0, $month, 1, $year)) }}</span>
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
            <div class="card mb-4 shadow-sm">
                <div class="card-body">
                    <div class="row g-3 align-items-end">
                        <div class="col-md-3">
                            <label for="year" class="form-label fw-semibold">Year</label>
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
                            <label for="month" class="form-label fw-semibold">Month</label>
                            <select name="month" id="month" class="form-select">
                                @for ($m = 1; $m <= 12; $m++)
                                    <option value="{{ $m }}" {{ (int) $month === $m ? 'selected' : '' }}>
                                        {{ date('F', mktime(0, 0, 0, $m, 1)) }}
                                    </option>
                                @endfor
                            </select>
                        </div>
                        <div class="col-md-6 d-flex gap-2 justify-content-end align-items-end">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-filter me-1"></i> Filter
                            </button>
                            </form>

                            <form id="exportForm" method="POST" action="{{ route('biotime.export') }}">
                                @csrf
                                <input type="hidden" name="department_id" value="">
                                <input type="hidden" name="month" id="exportMonth" value="{{ $month }}">
                                <input type="hidden" name="year" id="exportYear" value="{{ $year }}">
                                <input type="hidden" name="user_ids[]" value="{{ $user->userid }}">
                                <button type="submit" class="btn btn-success">
                                    <i class="fas fa-file-excel me-1"></i> Export Excel
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Summary Statistics Cards -->
            <div class="row mb-4" id="summaryCards">
                <div class="col-md-3 col-sm-6 mb-3">
                    <div class="stat-card primary position-relative">
                        <div class="stat-icon"><i class="far fa-calendar-check" style="color: #4e73df;"></i></div>
                        <div class="stat-label">Days Worked</div>
                        <div class="stat-value" id="statDays">0</div>
                        <small>This month</small>
                    </div>
                </div>
                <div class="col-md-3 col-sm-6 mb-3">
                    <div class="stat-card success position-relative">
                        <div class="stat-icon"><i class="far fa-clock" style="color: #1cc88a;"></i></div>
                        <div class="stat-label">Total Hours</div>
                        <div class="stat-value" id="statTotalHours">00:00</div>
                        <small>Regular + Overtime</small>
                    </div>
                </div>
                <div class="col-md-3 col-sm-6 mb-3">
                    <div class="stat-card info position-relative">
                        <div class="stat-icon"><i class="far fa-hourglass" style="color: #36b9cc;"></i></div>
                        <div class="stat-label">Overtime Hours</div>
                        <div class="stat-value" id="statOvertime">00:00</div>
                        <small>Hours over 8/day</small>
                    </div>
                </div>
                <div class="col-md-3 col-sm-6 mb-3">
                    <div class="stat-card warning position-relative">
                        <div class="stat-icon"><i class="fas fa-chart-line" style="color: #f6c23e;"></i></div>
                        <div class="stat-label">Avg Hours/Day</div>
                        <div class="stat-value" id="statAvgHours">00:00</div>
                        <small>Per working day</small>
                    </div>
                </div>
            </div>




            <!-- Daily table -->
            <div class="row">
                <div class="col-md-12">
                    <div class="card shadow-sm">
                        <div class="card-header bg-light">
                            <h5 class="mb-0"><i class="fas fa-table me-2" style="color: #61ce70;"></i>Daily Attendance Records</h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered table-hover align-middle attendance-table" id="userPunchTable">
                                    <thead>
                                        <tr>
                                            <th style="width:50px;">#</th>
                                            <th>Date</th>
                                            <th>Clock In</th>
                                            <th>Clock Out</th>
                                            <th>Total Hours</th>
                                            <th>Overtime</th>
                                            <th>Status</th>
                                            <th style="width:180px;">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td colspan="8" class="text-center text-muted py-4">
                                                <i class="fas fa-spinner fa-spin me-2"></i>Loading attendance data…
                                            </td>
                                        </tr>
                                    </tbody>
                                    <tfoot class="table-light">
                                        <tr>
                                            <th colspan="4" class="text-end fw-bold">Monthly Totals (<span
                                                    id="daysCount">0</span> day(s))</th>
                                            <th id="sumHM" class="text-mono fw-bold">00:00</th>
                                            <th id="sumOTHM" class="text-mono fw-bold">00:00</th>
                                            <th colspan="2"></th>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                    </div>

                    <div class="mt-4 d-flex justify-content-between align-items-center">
                        <a href="{{ url()->previous() }}" class="btn btn-outline-secondary">
                            <i class="fas fa-arrow-left me-1"></i> Back
                        </a>
                        <div class="text-muted small">
                            <i class="fas fa-info-circle me-1"></i>
                            Showing attendance records for <strong>{{ date('F Y', mktime(0, 0, 0, $month, 1, $year)) }}</strong>
                        </div>
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
            // function buildSessions(punches, MAX_GAP_HOURS = 36) {
            //     const sessions = [];
            //     let open = null;

            //     const push = (a, b) => {
            //         if (!a || !b) return;
            //         const mins = Math.round((b.dt - a.dt) / 60000);
            //         if (mins > 0) sessions.push({
            //             startDate: a.date,
            //             startTime: a.time,
            //             startDT: a.dt,
            //             endDate: b.date,
            //             endTime: b.time,
            //             endDT: b.dt,
            //             minutes: mins
            //         });
            //     };

            //     for (const p of punches) {
            //         const dir = (p.dir === 'UNK') ? (open ? 'OUT' : 'IN') : p.dir;
            //         if (dir === 'IN') {
            //             if (open) {
            //                 const gap = (p.dt - open.dt) / ONE_HOUR;
            //                 if (gap > 0 && gap <= MAX_GAP_HOURS) push(open, p);
            //             }
            //             open = {
            //                 dt: p.dt,
            //                 date: p.date,
            //                 time: p.time
            //             };
            //         } else { // OUT
            //             if (open) {
            //                 const gap = (p.dt - open.dt) / ONE_HOUR;
            //                 if (gap > 0 && gap <= MAX_GAP_HOURS) {
            //                     push(open, p);
            //                     open = null;
            //                 } else {
            //                     open = null;
            //                 }
            //             }
            //         }
            //     }
            //     return {
            //         sessions,
            //         openStart: open
            //     };
            // }

            // Pair IN→OUT, stop if another IN appears first (don't jump over IN)
            function buildSessions(punches, MAX_GAP_HOURS = 36) {
                const ONE_HOUR = 3600000;
                const sessions = [];
                let open = null;

                const push = (a, b) => {
                    if (!a || !b) return;
                    const mins = Math.round((b.dt - a.dt) / 60000);
                    if (mins > 0) {
                        sessions.push({
                            startDate: a.date,
                            startTime: a.time,
                            startDT: a.dt,
                            endDate: b.date,
                            endTime: b.time,
                            endDT: b.dt,
                            minutes: mins
                        });
                    }
                };

                for (const p of punches) {
                    const dir = (p.dir === 'UNK') ? (open ? 'OUT' : 'IN') : p.dir;

                    if (dir === 'IN') {
                        // New IN replaces any open IN (abandon orphan)
                        open = {
                            dt: p.dt,
                            date: p.date,
                            time: p.time
                        };
                        continue;
                    }

                    if (dir === 'OUT') {
                        if (!open) continue;
                        const gapH = (p.dt - open.dt) / ONE_HOUR;
                        if (gapH > 0 && gapH <= MAX_GAP_HOURS) {
                            push(open, p);
                        }
                        open = null; // close regardless
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

            // Calculate statistics and patterns
            const $tb = $('#userPunchTable tbody').empty();
            let sum = 0,
                sumOT = 0,
                days = 0,
                incompleteDays = 0;
            
            // Calculate shift patterns from actual data (shift-agnostic approach)
            const clockInTimes = [];
            const clockOutTimes = [];
            const workDurations = [];
            
            // Calculate average shift times from actual data
            daily.forEach(r => {
                if (r.in) {
                    const [h, m] = r.in.split(':').map(Number);
                    clockInTimes.push(h * 60 + m); // minutes from midnight
                }
                if (r.out) {
                    const [h, m] = r.out.split(':').map(Number);
                    clockOutTimes.push(h * 60 + m);
                }
                if (r.totalMin > 0) {
                    workDurations.push(r.totalMin);
                }
            });
            
            // Calculate averages - make accessible globally
            window.avgClockIn = clockInTimes.length > 0 
                ? Math.round(clockInTimes.reduce((a, b) => a + b, 0) / clockInTimes.length)
                : null;
            window.avgClockOut = clockOutTimes.length > 0
                ? Math.round(clockOutTimes.reduce((a, b) => a + b, 0) / clockOutTimes.length)
                : null;
            window.avgWorkDuration = workDurations.length > 0
                ? Math.round(workDurations.reduce((a, b) => a + b, 0) / workDurations.length)
                : null;
            
            const avgClockIn = window.avgClockIn;
            const avgClockOut = window.avgClockOut;
            const avgWorkDuration = window.avgWorkDuration;
            
            // Calculate standard deviation for consistency
            const calcStdDev = (arr, avg) => {
                if (!arr.length || !avg) return 0;
                const variance = arr.reduce((sum, val) => sum + Math.pow(val - avg, 2), 0) / arr.length;
                return Math.round(Math.sqrt(variance));
            };
            
            const clockInStdDev = calcStdDev(clockInTimes, avgClockIn);
            const clockOutStdDev = calcStdDev(clockOutTimes, avgClockOut);
            
            // Detect patterns: late/early relative to their own average (not fixed times)
            let lateArrivals = 0;
            let earlyDepartures = 0;
            let onTimeDays = 0;
            window.LATE_THRESHOLD = 30; // 30 minutes after average
            window.EARLY_THRESHOLD = 30; // 30 minutes before average
            const LATE_THRESHOLD = window.LATE_THRESHOLD;
            const EARLY_THRESHOLD = window.EARLY_THRESHOLD;

            if (!daily.length) {
                $tb.append('<tr><td colspan="8" class="text-center text-muted py-4">No records for this period.</td></tr>');
            } else {
                daily.forEach((r, i) => {
                    const count = (r.status === 'OK' || r.status === 'Still In') && r.totalMin > 0;
                    if (count) {
                        sum += r.totalMin;
                        sumOT += Math.max(0, r.totalMin - 8 * 60);
                        days++;
                    }

                    // Check for late arrivals and early departures relative to their own average
                    let timeClass = '';
                    let patternBadge = '';
                    let isLate = false;
                    let isEarly = false;
                    
                    if (r.in && avgClockIn) {
                        const [h, m] = r.in.split(':').map(Number);
                        const inMinutes = h * 60 + m;
                        // Late if more than 30 minutes after their average clock-in time
                        if (inMinutes > avgClockIn + LATE_THRESHOLD) {
                            lateArrivals++;
                            isLate = true;
                            timeClass = 'late-arrival';
                            patternBadge = '<span class="pattern-badge bg-danger text-white ms-2">Late</span>';
                        }
                    }

                    if (r.out && avgClockOut) {
                        const [h, m] = r.out.split(':').map(Number);
                        const outMinutes = h * 60 + m;
                        // Early if more than 30 minutes before their average clock-out time
                        if (outMinutes < avgClockOut - EARLY_THRESHOLD && r.totalMin > 0) {
                            earlyDepartures++;
                            isEarly = true;
                            if (!timeClass) timeClass = 'early-departure';
                            patternBadge = '<span class="pattern-badge bg-warning text-dark ms-2">Early</span>';
                        }
                    }

                    // Count on-time days (within 15 minutes of average)
                    if (r.status === 'OK' && r.in && r.out && !isLate && !isEarly) {
                        if (avgClockIn && avgClockOut) {
                            const [hIn, mIn] = r.in.split(':').map(Number);
                            const [hOut, mOut] = r.out.split(':').map(Number);
                            const inMinutes = hIn * 60 + mIn;
                            const outMinutes = hOut * 60 + mOut;
                            if (Math.abs(inMinutes - avgClockIn) <= 15 && Math.abs(outMinutes - avgClockOut) <= 15) {
                                onTimeDays++;
                            }
                        } else {
                            onTimeDays++;
                        }
                    }

                    if (r.status === 'Incomplete') {
                        incompleteDays++;
                    }

                    $tb.append(`
                        <tr data-date="${r.date}">
                            <td>${i + 1}</td>
                            <td>${usDate(r.date)}</td>
                            <td class="text-mono ${timeClass}">${r.in ? usTime(r.in) : '<span class="text-muted">—</span>'}</td>
                            <td class="text-mono ${timeClass}">${r.out ? usTime(r.out) : '<span class="text-muted">—</span>'}</td>
                            <td class="text-mono fw-semibold">${count ? r.totalHM : '<span class="text-muted">—</span>'}</td>
                            <td class="text-mono">${count ? r.otHM : '<span class="text-muted">—</span>'}</td>
                            <td>${badge(r.status)}</td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    <button type="button" class="btn btn-outline-secondary btn-expand" title="Expand details">
                                        <i class="fas fa-chevron-down"></i>
                                    </button>
                                    <button type="button" class="btn btn-outline-primary btn-view" title="View all transactions">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    `);
                });
            }

            // Update summary statistics
            $('#daysCount').text(days);
            $('#sumHM').text(toHM(sum));
            $('#sumOTHM').text(toHM(sumOT));

            // Update stat cards
            $('#statDays').text(days);
            $('#statTotalHours').text(toHM(sum));
            $('#statOvertime').text(toHM(sumOT));
            $('#statAvgHours').text(days > 0 ? toHM(Math.round(sum / days)) : '00:00');






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
                const $btn = $(this);
                const $tr = $btn.closest('tr');
                const date = $tr.data('date');
                const $next = $tr.next('.child-row');
                
                if ($next.length) {
                    $next.remove();
                    $btn.html('<i class="fas fa-chevron-down"></i>');
                    $btn.attr('title', 'Expand details');
                } else {
                    $('<tr class="child-row"><td colspan="8">' + childHtml(date) + '</td></tr>').insertAfter($tr);
                    $btn.html('<i class="fas fa-chevron-up"></i>');
                    $btn.attr('title', 'Collapse details');
                }
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
