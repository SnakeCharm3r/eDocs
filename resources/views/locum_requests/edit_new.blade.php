{{-- resources/views/locum_requests/create.blade.php --}}
@extends('layouts.template')

@section('breadcrumb')
    @include('sweetalert::alert')
@endsection

@section('content')
    <style>
        .pdf-like-container {
            max-width: 1200px;
            margin: 0 auto;
            font-family: Arial, sans-serif;
            font-size: 14px;
            line-height: 1.6;
            border: 1px solid #ddd;
            padding: 20px;
            background: #fff;
            box-shadow: 0 0 10px rgba(0, 0, 0, .1)
        }

        .pdf-section {
            margin-bottom: 25px
        }

        .pdf-section h5 {
            font-size: 16px;
            font-weight: 700;
            margin-bottom: 12px;
            text-transform: uppercase;
            color: #333
        }

        .request-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
            font-size: 14px
        }

        .request-table th,
        .request-table td {
            border: 1px solid #8b8787;
            padding: 10px;
            text-align: left
        }

        .request-table th {
            width: 22%;
            font-weight: 700;
            background: #f8f8f8
        }

        .days-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px
        }

        .days-table th,
        .days-table td {
            border: 1px solid #ddd;
            padding: 8px;
            vertical-align: middle
        }

        .days-table th {
            background: #f8f8f8;
            text-align: center
        }

        .weekend {
            background: #ffe6e6
        }

        .holiday {
            background: #e6f3ff
        }

        .hours-input {
            width: 90px;
            text-align: center
        }

        .worked-checkbox {
            width: 20px;
            height: 20px;
            cursor: pointer;
            vertical-align: middle;
            transform: scale(1.1)
        }

        .worked-checkbox:checked {
            accent-color: #2fb565
        }

        #locum_month {
            pointer-events: none;
            background-color: #e9ecef
        }

        .btn-primary {
            background-color: #2fb565;
            border-color: #2fb565
        }

        .btn-primary:hover {
            filter: brightness(.95)
        }

        /* Loading button styles */
        .btn-loading {
            position: relative;
            pointer-events: none;
            opacity: 0.7;
        }

        .btn-loading::after {
            content: "";
            position: absolute;
            width: 16px;
            height: 16px;
            top: 50%;
            left: 50%;
            margin-left: -8px;
            margin-top: -8px;
            border: 2px solid #ffffff;
            border-radius: 50%;
            border-top-color: transparent;
            animation: spinner 0.6s linear infinite;
        }

        @keyframes spinner {
            to {
                transform: rotate(360deg);
            }
        }

        .btn-loading .btn-text {
            opacity: 0;
        }

        .btn-loading .btn-icon {
            opacity: 0;
        }

        .summary-card {
            border: 1px solid #ddd;
            border-radius: 6px;
            padding: 14px;
            background: #fafafa
        }

        .summary-title {
            font-weight: 700;
            margin-bottom: 8px
        }

        .summary-table {
            width: 100%;
            border-collapse: collapse
        }

        .summary-table th,
        .summary-table td {
            border: 1px solid #e6e6e6;
            padding: 8px;
            text-align: left
        }

        .summary-table th {
            background: #f5f5f5
        }

        .muted {
            color: #777
        }

        .metrics {
            display: flex;
            gap: 24px;
            flex-wrap: wrap;
            margin-top: 10px
        }

        .metric {
            padding: 10px 14px;
            border: 1px dashed #ddd;
            border-radius: 6px;
            background: #fff
        }

        .inline-warning {
            color: #b02a37;
            font-size: 12px;
            margin-top: 6px
        }

        .rule-pill {
            border: 1px solid #2fb565;
            color: #2fb565;
            background: #ecfbf2;
            border-radius: 999px;
            padding: .2rem .6rem;
            font-weight: 600
        }

        .entry-row {
            background: #fff
        }

        .entry-controls {
            display: flex;
            gap: 8px;
            align-items: center
        }

        .btn-xs {
            padding: .15rem .35rem;
            font-size: .75rem;
            line-height: 1;
            border-radius: .2rem
        }

        .day-entries-wrap {
            padding: 8px;
            background: #fdfdfd;
            border: 1px dashed #e5e5e5;
            border-radius: 4px
        }

        .entry-grid {
            display: grid;
            grid-template-columns: 1.2fr 0.8fr 1.2fr 1.2fr auto;
            gap: 8px;
            align-items: center
        }

        .soft-note {
            color: #666;
            font-size: 12px
        }
    </style>

    <div class="page-wrapper">
        <div class="content container-fluid">
            <div class="page-header">
                <div class="row align-items-center">
                    <div class="col-sm-8">
                        <h3 class="page-title mb-2">
                            Request Locum
                        </h3>
                    </div>
                    <div class="col-sm-4 d-flex justify-content-end"></div>
                </div>
            </div>

            <div class="pdf-like-container">
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
                @if ($errors->any())
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <strong>Oops! There were some problems:</strong>
                        <ul class="mb-0">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif

                @if ($agreement && $agreement->has_contract)
                    <form action="{{ route('locum-requests.store') }}" method="POST" id="locum-request-form" novalidate>
                        @csrf
                        <input type="hidden" name="locum_agreement_id" value="{{ $agreement->id }}">
                        <input type="hidden" id="locum_year" name="locum_year" value="{{ $previousYear }}">

                        {{-- EMPLOYEE DETAILS --}}
                        <div class="pdf-section">
                            <h5>EMPLOYEE DETAILS</h5>
                            <table class="request-table">
                                <tr>
                                    <th>Employee Name</th>
                                    <td>{{ $user->username ?? 'N/A' }}</td>
                                </tr>
                                <tr>
                                    <th>Employee Code</th>
                                    <td>{{ $user->ccbrt_code ?? 'N/A' }}</td>
                                </tr>
                                <tr>
                                    <th>Department</th>
                                    <td>{{ $user->department?->dept_name ?? 'N/A' }}</td>
                                </tr>
                                <tr>
                                    <th>Education Level</th>
                                    <td>{{ $agreement->education_level ?? 'N/A' }}</td>
                                </tr>
                                <tr>
                                    <th>Locum Rate</th>
                                    <td>TZS {{ $agreement->locum_rate ? number_format($agreement->locum_rate, 2) : 'N/A' }}
                                    </td>
                                </tr>
                            </table>
                        </div>

                        {{-- REQUEST DETAILS --}}
                        <div class="pdf-section">
                            <table class="request-table table table-bordered">
                                {{-- Locum Month --}}
                                <tr>
                                    <td colspan="2">
                                        <label for="locum_month"
                                            class="form-label fw-semibold small text-uppercase d-block mb-1">
                                            Locum Month <span class="text-danger">*</span>
                                        </label>
                                        <select class="form-control" id="locum_month" name="locum_month" required>
                                            @foreach ($months as $m)
                                                <option value="{{ $m }}" @selected($m === $previousMonth)>
                                                    {{ $m }} {{ $previousYear }}</option>
                                            @endforeach
                                        </select>
                                        @error('locum_month')
                                            <div class="invalid-feedback d-block">{{ $message }}</div>
                                        @enderror
                                    </td>
                                </tr>

                                {{-- Worked Days --}}
                                <tr>
                                    <td colspan="2">
                                        <div class="d-flex align-items-center justify-content-between">
                                            <label class="form-label fw-semibold small text-uppercase mb-1">
                                                Select Days Worked <span class="text-danger">*</span>
                                            </label>
                                            <span class="text-muted small">Tick a day, then add 1+ entries (Shift, Hours,
                                                Platform, Unit).</span>
                                        </div>

                                        <div id="days-selection" style="display:none;">
                                            <table class="table table-sm days-table">
                                                <thead>
                                                    <tr>
                                                        <th style="width:22%; text-align:left">Day (dd – Weekday)</th>
                                                        <th style="width:8%; text-align:center">Worked</th>
                                                        <th style="width:70%; text-align:left">Entries for the Day</th>
                                                    </tr>
                                                </thead>
                                                <tbody id="days-table-body"></tbody>
                                            </table>
                                        </div>
                                    </td>
                                </tr>

                                {{-- Per (Shift × Platform) summary --}}
                                <tr>
                                    <td colspan="2">
                                        <div class="summary-card">
                                            <div class="summary-title">Locum Summary (grouped by Shift × Platform)</div>
                                            <div class="muted" id="per-sp-empty">No worked entries yet.</div>
                                            <div id="per-sp-wrap" style="display:none;">
                                                <table class="summary-table">
                                                    <thead>
                                                        <tr>
                                                            <th>Shift</th>
                                                            <th>Platform</th>
                                                            <th>Total Hours</th>
                                                            <th>Hours / Locum</th>
                                                            <th>Locum(s)</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody id="per-sp-body"></tbody>
                                                </table>

                                                <div class="metrics">
                                                    <div class="metric">
                                                        <strong>Grand Total Locum(s)</strong>
                                                        <div id="grand-locums">0</div>
                                                    </div>
                                                    <div class="metric">
                                                        <strong>Total Amount Payable</strong>
                                                        <div id="grand-amount">TZS 0.00</div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                </tr>

                                {{-- Description --}}
                                <tr>
                                    <td colspan="2">
                                        <label
                                            class="form-label fw-semibold small text-uppercase d-block mb-1">Description</label>
                                        <textarea name="description" class="form-control" rows="3" placeholder="Optional description...">{{ old('description') }}</textarea>
                                        @error('description')
                                            <div class="invalid-feedback d-block">{{ $message }}</div>
                                        @enderror
                                    </td>
                                </tr>
                            </table>
                        </div>

                        {{-- Detailed selection --}}
                        <div class="pdf-section">
                            <h5>DETAILED SELECTION</h5>
                            <div class="summary-card">
                                <div class="summary-title">Selected Entries</div>
                                <div class="muted" id="summary-empty">No entries yet.</div>
                                <div id="summary-table-wrap" style="display:none;">
                                    <table class="summary-table">
                                        <thead>
                                            <tr>
                                                <th style="width:18%">Date</th>
                                                <th style="width:18%">Shift</th>
                                                <th style="width:12%">Hours</th>
                                                <th style="width:26%">Platform</th>
                                                <th style="width:26%">Unit</th>
                                            </tr>
                                        </thead>
                                        <tbody id="summary-body"></tbody>
                                    </table>
                                </div>
                            </div>
                   
                        </div>

                        <a href="{{ route('locum-requests.index') }}" class="btn btn-secondary mt-3 me-2" id="backBtn">
                            <i class="fas fa-arrow-left"></i> <span class="btn-text">Back</span>
                        </a>
                        <button type="submit" class="btn btn-primary mt-3" id="submitBtn">
                            <span class="btn-icon"><i class="fas fa-save"></i></span>
                            <span class="btn-text">Submit Request</span>
                        </button>
                    </form>
                @else
                    <div class="alert alert-warning">
                        You must have an active locum agreement to create a request. Please
                        <a href="{{ route('locum-agreements.create') }}">create an agreement</a> first.
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- jQuery (if not already in layout) --}}
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // ----- Data from server -----
            const holidays = @json($holidays);
            const fallbackYr = {{ $previousYear ?? now()->subMonth()->year }};
            const rate = {{ (float) ($agreement->locum_rate ?? 0) }};
            const shifts = @json($shifts); // [{id,name}]
            const platforms = @json($platforms); // [{id,name,locum_hours}]
            const platformHours = @json($platformHours); // { [id]: 8|12 }

            // ----- Elements -----
            const monthEl = document.getElementById('locum_month');
            const yearInput = document.getElementById('locum_year');
            const daysWrap = document.getElementById('days-selection');
            const tbody = document.getElementById('days-table-body');
            const form = document.getElementById('locum-request-form');

            // Per Shift × Platform UI
            const perSpEmpty = document.getElementById('per-sp-empty');
            const perSpWrap = document.getElementById('per-sp-wrap');
            const perSpBody = document.getElementById('per-sp-body');
            const grandLocumsEl = document.getElementById('grand-locums');
            const grandAmountEl = document.getElementById('grand-amount');

            // Detailed summary UI
            const summaryEmpty = document.getElementById('summary-empty');
            const summaryWrap = document.getElementById('summary-table-wrap');
            const summaryBody = document.getElementById('summary-body');

            if (!monthEl || !tbody) return;

            const validMonths = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August',
                'September', 'October', 'November', 'December'
            ];

            // ---------- Helpers ----------
            function getYear() {
                const y = parseInt(yearInput?.value ?? '', 10);
                return Number.isFinite(y) ? y : fallbackYr;
            }

            function fmtMoney(v) {
                return 'TZS ' + Number(v || 0).toLocaleString('en-US', {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                });
            }

            function shiftOptionsHtml(selectedId = '') {
                let opts = `<option value="">— Select shift —</option>`;
                shifts.forEach(s => {
                    const sel = String(s.id) === String(selectedId) ? 'selected' : '';
                    opts += `<option value="${s.id}" ${sel}>${s.name}</option>`;
                });
                return opts;
            }

            function platformOptionsHtml(selectedId = '') {
                let opts = `<option value="">— Select platform —</option>`;
                platforms.forEach(p => {
                    const sel = String(p.id) === String(selectedId) ? 'selected' : '';
                    const lh = platformHours[p.id] || '';
                    const hint = lh ? ` (${lh}h/locum)` : '';
                    opts += `<option value="${p.id}" ${sel}>${p.name}${hint}</option>`;
                });
                return opts;
            }

            function setRowWarning(el, key, message) {
                let box = el.querySelector(`.warn-${key}`);
                if (!box) {
                    box = document.createElement('div');
                    box.className = `warn-${key} inline-warning`;
                    el.appendChild(box);
                }
                box.textContent = message;
                box.style.display = message ? 'block' : 'none';
            }

            function clearRowWarning(el, key) {
                const box = el.querySelector(`.warn-${key}`);
                if (box) box.style.display = 'none';
            }

            function buildDayRow(year, mIdx, day) {
                const date = new Date(year, mIdx, day);
                const weekday = date.toLocaleString('en-US', {
                    weekday: 'long'
                });
                const isWeekend = (date.getDay() === 0 || date.getDay() === 6);
                const iso = `${year}-${String(mIdx+1).padStart(2,'0')}-${String(day).padStart(2,'0')}`;
                const holiday = holidays[iso] || '';

                const tr = document.createElement('tr');
                tr.className = holiday ? 'holiday' : (isWeekend ? 'weekend' : '');
                tr.dataset.iso = iso;

                tr.innerHTML = `
                    <td>
                        <span class="day-label">${String(day).padStart(2,'0')} – ${weekday}</span>
                        ${holiday ? ` <small title="${holiday}">(${holiday})</small>` : ''}
                    </td>
                    <td class="text-center">
                        <input type="hidden" name="worked_days[${iso}][worked]" value="0">
                        <input type="checkbox" class="worked-checkbox" id="worked-${iso}" name="worked_days[${iso}][worked]" value="1">
                    </td>
                    <td>
                        <div class="day-entries-wrap" data-iso="${iso}" style="display:none;">
                            <div class="entry-controls mb-2">
                                <button type="button" class="btn btn-success btn-xs add-entry" data-iso="${iso}">
                                    + Add entry
                                </button>
                                <span class="soft-note">Each entry has: Shift, Hours, Platform, Unit</span>
                            </div>
                            <div class="entries" data-iso="${iso}"></div>
                        </div>
                    </td>
                `;
                tbody.appendChild(tr);

                const cb = tr.querySelector('.worked-checkbox');
                const wrap = tr.querySelector('.day-entries-wrap');
                const entriesBox = tr.querySelector('.entries');
                const addBtn = tr.querySelector('.add-entry');

                cb.addEventListener('change', () => {
                    const enabled = cb.checked;
                    wrap.style.display = enabled ? 'block' : 'none';
                    entriesBox.innerHTML = '';
                    if (enabled) {
                        addEntryRow(entriesBox, iso); // add first entry
                    }
                    updateSummaries();
                });

                addBtn.addEventListener('click', () => {
                    addEntryRow(entriesBox, iso);
                });
            }

            // Build an entry row UI inside a day's entries container
            function addEntryRow(entriesBox, iso, preset = {}) {
                const idx = entriesBox.children.length; // 0-based
                const row = document.createElement('div');
                row.className = 'entry-row mb-2';
                row.innerHTML = `
                    <div class="entry-grid">
                        <select class="form-control form-control-sm entry-shift" name="worked_days[${iso}][entries][${idx}][shift_id]">
                            ${shiftOptionsHtml(preset.shift_id || '')}
                        </select>

                        <input type="number" step="0.5" min="0" max="36" class="form-control form-control-sm entry-hours"
                            name="worked_days[${iso}][entries][${idx}][hours]" value="${preset.hours ?? ''}" placeholder="Hours">

                        <select class="form-control form-control-sm entry-platform" name="worked_days[${iso}][entries][${idx}][platform_id]">
                            ${platformOptionsHtml(preset.platform_id || '')}
                        </select>

                        <select class="form-control form-control-sm entry-unit" name="worked_days[${iso}][entries][${idx}][unit_id]">
                            <option value="">— Select unit —</option>
                        </select>

                        <div class="entry-controls">
                            <button type="button" class="btn btn-outline-danger btn-xs remove-entry">&times;</button>
                        </div>
                    </div>
                    <div class="warn-platform inline-warning" style="display:none;"></div>
                    <div class="warn-unit inline-warning" style="display:none;"></div>
                `;
                entriesBox.appendChild(row);

                const shiftSel = row.querySelector('.entry-shift');
                const hoursInp = row.querySelector('.entry-hours');
                const platSel = row.querySelector('.entry-platform');
                const unitSel = row.querySelector('.entry-unit');
                const rmBtn = row.querySelector('.remove-entry');

                rmBtn.addEventListener('click', () => {
                    row.remove();
                    reindexEntries(entriesBox, iso);
                    updateSummaries();
                });

                platSel.addEventListener('change', async () => {
                    const pid = platSel.value;
                    unitSel.innerHTML = `<option value="">Loading…</option>`;
                    row.querySelector('.warn-platform').style.display = 'none';
                    row.querySelector('.warn-unit').style.display = 'none';

                    if (!pid) {
                        unitSel.innerHTML = `<option value="">— Select unit —</option>`;
                        updateSummaries();
                        return;
                    }

                    try {
                        const [unitsRes, metaRes] = await Promise.all([
                            fetch(`/platforms/${encodeURIComponent(pid)}/units/json/public`),
                            fetch(`/platforms/${encodeURIComponent(pid)}/meta/json/public`)
                        ]);

                        if (!unitsRes.ok || !metaRes.ok) throw new Error('Bad response');

                        const unitsData = await unitsRes.json();
                        const metaData = await metaRes.json();

                        if (!metaData?.has_manager) {
                            row.querySelector('.warn-platform').textContent =
                                'This platform has no manager assigned.';
                            row.querySelector('.warn-platform').style.display = 'block';
                        } else {
                            row.querySelector('.warn-platform').style.display = 'none';
                        }

                        const units = Array.isArray(unitsData?.units) ? unitsData.units : [];
                        let opts = `<option value="">— Select unit —</option>`;
                        units.forEach(u => {
                            const label = u.has_incharge ? u.name :
                            `${u.name} — (no in-charge)`;
                            opts +=
                                `<option value="${u.id}" data-has-incharge="${u.has_incharge ? 1 : 0}">${label}</option>`;
                        });
                        unitSel.innerHTML = opts;
                    } catch (e) {
                        unitSel.innerHTML = `<option value="">— Select unit —</option>`;
                        row.querySelector('.warn-platform').textContent =
                            'Failed to load units/meta. Check connection.';
                        row.querySelector('.warn-platform').style.display = 'block';
                        console.error('Platform units/meta fetch error:', e);
                    }

                    updateSummaries();
                });

                unitSel.addEventListener('change', () => {
                    const opt = unitSel.selectedOptions?.[0];
                    if (opt && opt.value && opt.getAttribute('data-has-incharge') === '0') {
                        row.querySelector('.warn-unit').textContent =
                            'Selected unit has no in-charge assigned.';
                        row.querySelector('.warn-unit').style.display = 'block';
                    } else {
                        row.querySelector('.warn-unit').style.display = 'none';
                    }
                    updateSummaries();
                });

                shiftSel.addEventListener('change', updateSummaries);
                hoursInp.addEventListener('input', updateSummaries);
            }

            // After deletions, re-number name indices to keep inputs contiguous for backend parsing
            function reindexEntries(entriesBox, iso) {
                Array.from(entriesBox.children).forEach((row, newIdx) => {
                    row.querySelectorAll('[name$="[shift_id]"]').forEach(el => {
                        el.name = `worked_days[${iso}][entries][${newIdx}][shift_id]`;
                    });
                    row.querySelectorAll('[name$="[hours]"]').forEach(el => {
                        el.name = `worked_days[${iso}][entries][${newIdx}][hours]`;
                    });
                    row.querySelectorAll('[name$="[platform_id]"]').forEach(el => {
                        el.name = `worked_days[${iso}][entries][${newIdx}][platform_id]`;
                    });
                    row.querySelectorAll('[name$="[unit_id]"]').forEach(el => {
                        el.name = `worked_days[${iso}][entries][${newIdx}][unit_id]`;
                    });
                });
            }

            function buildCalendar() {
                const month = monthEl.value;
                if (!month || !validMonths.includes(month)) {
                    if (daysWrap) daysWrap.style.display = 'none';
                    return;
                }

                const year = getYear();
                const mIdx = validMonths.indexOf(month);
                const daysInMonth = new Date(year, mIdx + 1, 0).getDate();

                tbody.innerHTML = '';

                for (let day = 1; day <= daysInMonth; day++) {
                    buildDayRow(year, mIdx, day);
                }

                if (daysWrap) daysWrap.style.display = 'block';
                updateSummaries();
            }

            function updateSummaries() {
                const groups = {}; // key: `${shiftId}|${platformId}` -> { shiftName, platName, threshold, hours }
                const rows = [];

                tbody.querySelectorAll('tr').forEach(tr => {
                    const worked = tr.querySelector('.worked-checkbox');
                    if (!worked || !worked.checked) return;

                    const iso = tr.dataset.iso;
                    const dLabel = tr.querySelector('.day-label')?.textContent?.trim() || iso;

                    const entriesBox = tr.querySelector('.entries');
                    if (!entriesBox) return;

                    Array.from(entriesBox.children).forEach(row => {
                        const shiftSel = row.querySelector('.entry-shift');
                        const hoursInp = row.querySelector('.entry-hours');
                        const platSel = row.querySelector('.entry-platform');
                        const unitSel = row.querySelector('.entry-unit');

                        const shiftId = shiftSel?.value || '';
                        const shiftNm = shiftSel?.selectedOptions?.[0]?.textContent || '';
                        const hours = parseFloat(hoursInp?.value || '0');
                        const platId = platSel?.value || '';
                        const platNm = platSel?.selectedOptions?.[0]?.textContent || '';
                        const unitNm = unitSel?.selectedOptions?.[0]?.textContent || '';

                        if (!isNaN(hours) && hours > 0) {
                            rows.push({
                                date: dLabel,
                                shift: shiftNm || '—',
                                hours: hours.toFixed(2),
                                platform: platId ? platNm : '—',
                                unit: unitNm || '—'
                            });
                        }

                        if (shiftId && platId && !isNaN(hours) && hours > 0) {
                            const key = `${shiftId}|${platId}`;
                            const thresh = Number(platformHours[platId] ||
                            8); // default 8 if missing
                            if (!groups[key]) {
                                groups[key] = {
                                    shiftName: shiftNm || `Shift ${shiftId}`,
                                    platName: platNm || `Platform ${platId}`,
                                    threshold: thresh,
                                    hours: 0
                                };
                            }
                            groups[key].hours += hours;
                        }
                    });
                });

                // Detailed rows table
                if (rows.length === 0) {
                    summaryEmpty.style.display = 'block';
                    summaryWrap.style.display = 'none';
                    summaryBody.innerHTML = '';
                } else {
                    summaryEmpty.style.display = 'none';
                    summaryWrap.style.display = 'block';
                    summaryBody.innerHTML = rows.map(r => `
                        <tr>
                            <td>${r.date}</td>
                            <td>${r.shift}</td>
                            <td>${r.hours}</td>
                            <td>${r.platform}</td>
                            <td>${r.unit}</td>
                        </tr>`).join('');
                }

                // Grouped by Shift × Platform (ignore partials; no carry)
                const keys = Object.keys(groups);
                if (keys.length === 0) {
                    perSpEmpty.style.display = 'block';
                    perSpWrap.style.display = 'none';
                    perSpBody.innerHTML = '';
                    grandLocumsEl.textContent = '0';
                    grandAmountEl.textContent = fmtMoney(0);
                    return;
                }

                perSpEmpty.style.display = 'none';
                perSpWrap.style.display = 'block';

                let grandLocums = 0;
                const rowsSP = keys.map(k => {
                    const g = groups[k];
                    const locums = Math.floor(g.hours / g.threshold); // partial hours ignored
                    grandLocums += locums;
                    return `
                        <tr>
                            <td>${g.shiftName}</td>
                            <td>${g.platName}</td>
                            <td>${g.hours.toFixed(2)}</td>
                            <td>${g.threshold.toFixed(0)}</td>
                            <td>${locums}</td>
                        </tr>`;
                });

                perSpBody.innerHTML = rowsSP.join('');
                grandLocumsEl.textContent = String(grandLocums);
                grandAmountEl.textContent = fmtMoney(grandLocums * rate);
            }

            // Init + listeners
            buildCalendar();
            monthEl.addEventListener('change', buildCalendar);

            // Submit guard and loading state
            const submitBtn = document.getElementById('submitBtn');
            const backBtn = document.getElementById('backBtn');
            
            form.addEventListener('submit', function(e) {
                const anyChecked = tbody.querySelector('.worked-checkbox:checked');
                if (!anyChecked) {
                    e.preventDefault();
                    alert('Please select at least one worked day.');
                    return;
                }

                // Must have at least one entry where hours > 0
                const anyEntry = tbody.querySelector('.entries .entry-hours');
                if (!anyEntry) {
                    e.preventDefault();
                    alert('Please add at least one entry (Shift, Hours, Platform, Unit).');
                    return;
                }

                let bad = false;

                // Ensure all selected units have in-charge, and basic requireds are present
                tbody.querySelectorAll('tr').forEach(tr => {
                    const worked = tr.querySelector('.worked-checkbox');
                    if (!worked || !worked.checked) return;

                    const entriesBox = tr.querySelector('.entries');
                    Array.from(entriesBox.children).forEach(row => {
                        const shiftSel = row.querySelector('.entry-shift');
                        const hoursInp = row.querySelector('.entry-hours');
                        const platSel = row.querySelector('.entry-platform');
                        const unitSel = row.querySelector('.entry-unit');

                        const hours = parseFloat(hoursInp?.value || '0');
                        const platId = platSel?.value || '';
                        const shiftId = shiftSel?.value || '';

                        // required fields
                        if (!shiftId || !platId || isNaN(hours) || hours <= 0) {
                            bad = true;
                        }

                        const unitOpt = unitSel?.selectedOptions?.[0];
                        if (unitOpt && unitOpt.value && unitOpt.getAttribute(
                                'data-has-incharge') === '0') {
                            bad = true;
                            row.querySelector('.warn-unit').textContent =
                                'Selected unit has no in-charge assigned.';
                            row.querySelector('.warn-unit').style.display = 'block';
                        }
                    });
                });

                if (bad) {
                    e.preventDefault();
                    alert(
                        'Please fix the highlighted issues (missing fields/hours or unit without in-charge).');
                    return;
                }

                // Show loading state
                submitBtn.classList.add('btn-loading');
                submitBtn.disabled = true;
                backBtn.style.pointerEvents = 'none';
                backBtn.style.opacity = '0.5';
                
                // Update button text
                const btnText = submitBtn.querySelector('.btn-text');
                if (btnText) {
                    btnText.textContent = 'Submitting...';
                }
            });
        });
    </script>
@endsection
