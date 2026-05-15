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
        box-shadow: 0 0 10px rgba(0,0,0,.1);
    }
    .pdf-section { margin-bottom: 25px; }
    .pdf-section h5 {
        font-size: 16px; font-weight: 700; margin-bottom: 12px;
        text-transform: uppercase; color: #333;
    }
    .request-table {
        width: 100%; border-collapse: collapse; margin-bottom: 20px; font-size: 14px;
    }
    .request-table th, .request-table td {
        border: 1px solid #8b8787; padding: 10px; text-align: left;
    }
    .request-table th { width: 22%; font-weight: 700; background: #f8f8f8; }

    .days-table { width: 100%; border-collapse: collapse; margin-top: 10px; }
    .days-table th, .days-table td {
        border: 1px solid #ddd; padding: 8px; vertical-align: middle;
    }
    .days-table th { background: #f8f8f8; text-align: center; }

    .weekend  { background: #ffe6e6; }
    .holiday  { background: #e6f3ff; }

    .worked-checkbox {
        width: 20px; height: 20px; cursor: pointer;
        vertical-align: middle; transform: scale(1.1);
    }
    .worked-checkbox:checked { accent-color: #2fb565; }

    .btn-primary { background-color: #2fb565; border-color: #2fb565; }
    .btn-primary:hover { filter: brightness(.95); }

    .summary-card {
        border: 1px solid #ddd; border-radius: 6px;
        padding: 14px; background: #fafafa;
    }
    .summary-title { font-weight: 700; margin-bottom: 8px; }
    .summary-table { width: 100%; border-collapse: collapse; }
    .summary-table th, .summary-table td {
        border: 1px solid #e6e6e6; padding: 8px; text-align: left;
    }
    .summary-table th { background: #f5f5f5; }
    .muted { color: #777; }

    .metrics { display: flex; gap: 24px; flex-wrap: wrap; margin-top: 10px; }
    .metric {
        padding: 10px 14px; border: 1px dashed #ddd;
        border-radius: 6px; background: #fff;
    }

    .day-entry-wrap {
        padding: 8px; background: #fdfdfd;
        border: 1px dashed #e5e5e5; border-radius: 4px;
    }
    .entry-grid {
        display: grid;
        grid-template-columns: 1fr 1fr auto;
        gap: 8px; align-items: end;
    }
    .hours-input { width: 80px; text-align: center; }
    .soft-note { color: #666; font-size: 12px; }
    .night-badge {
        display: inline-block;
        font-size: 13px; font-weight: 600; color: #1a1a2e;
    }
    .btn-loading { position: relative; pointer-events: none; }
    .btn-loading .btn-text { opacity: 1; }
    .btn-loading .js-spinner { display: inline-block !important; }

    .rejection-banner {
        border-left: 4px solid #dc3545;
        background: #fff5f5;
        border-radius: 4px;
        padding: 12px 16px;
        margin-bottom: 18px;
    }
    .expiry-info {
        background: #fff8e1;
        border: 1px solid #ffc107;
        border-radius: 4px;
        padding: 8px 14px;
        margin-bottom: 16px;
        font-size: 0.82rem;
    }
</style>

<div class="page-wrapper">
    <div class="content container-fluid">
        <div class="page-header">
            <div class="row align-items-center">
                <div class="col-sm-8">
                    <h3 class="page-title mb-2">
                        Edit &amp; Resubmit — Claim #{{ $nightShift->id }}
                    </h3>
                </div>
                <div class="col-sm-4 d-flex justify-content-end">
                    <a href="{{ route('night-shift.index') }}" class="btn btn-outline-secondary btn-sm">
                        <i class="fas fa-arrow-left me-1"></i> Back
                    </a>
                </div>
            </div>
        </div>

        <div class="pdf-like-container">

            @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show">
                {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            @endif
            @if($errors->any())
            <div class="alert alert-danger alert-dismissible fade show">
                <strong>Please fix the following:</strong>
                <ul class="mb-0">
                    @foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach
                </ul>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            @endif

            {{-- Rejection reason banner --}}
            @if($rejectionReason)
            <div class="rejection-banner">
                <div class="fw-bold text-danger mb-1"><i class="fas fa-times-circle me-1"></i>Reason for Rejection</div>
                <div>{{ $rejectionReason }}</div>
            </div>
            @endif

            {{-- Expiry notice --}}
            <div class="expiry-info">
                <i class="fas fa-clock me-1 text-warning"></i>
                <strong>Edit window:</strong>
                You can resubmit this claim until <strong>{{ $expiresAt->format('d M Y') }}</strong>
                ({{ $expiresAt->diffForHumans() }}).
            </div>

            <form action="{{ route('night-shift.resubmit', $nightShift) }}" method="POST" id="ns-form" novalidate>
                @csrf
                @method('PUT')

                {{-- EMPLOYEE DETAILS --}}
                <div class="pdf-section">
                    <h5>Employee Details</h5>
                    <table class="request-table">
                        <tr>
                            <th>Employee Name</th>
                            <td>{{ $user->username ?? trim($user->fname.' '.($user->mname??'').' '.$user->lname) }}</td>
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
                            <th>Shift Type</th>
                            <td><span class="night-badge">Night Allowances</span></td>
                        </tr>
                        @if($user->signature)
                        <tr>
                            <th>Signature</th>
                            <td>
                                <div style="border:1px solid #e9ecef; border-radius:4px; padding:4px 6px; background:#fff; display:inline-block;">
                                    <img src="data:image/png;base64,{{ $user->signature }}"
                                         style="max-height:40px; max-width:130px; object-fit:contain;">
                                </div>
                            </td>
                        </tr>
                        @endif
                    </table>
                    <input type="hidden" name="type_of_allowance" value="Night Allowances">
                    <input type="hidden" name="department_id" value="{{ $user->deptId }}">
                </div>

                {{-- REQUEST DETAILS --}}
                <div class="pdf-section">
                    <table class="request-table table table-bordered">
                        {{-- Year + Month --}}
                        <tr>
                            <td style="width:50%;">
                                <label class="form-label fw-semibold small text-uppercase d-block mb-1">
                                    Year <span class="text-danger">*</span>
                                </label>
                                <select class="form-control" name="year" id="ns_year" required>
                                    @for($y = date('Y')-1; $y <= date('Y')+1; $y++)
                                    <option value="{{ $y }}" {{ $y == $nightShift->year ? 'selected' : '' }}>{{ $y }}</option>
                                    @endfor
                                </select>
                            </td>
                            <td style="width:50%;">
                                <label class="form-label fw-semibold small text-uppercase d-block mb-1">
                                    Month <span class="text-danger">*</span>
                                </label>
                                <select class="form-control" name="month" id="ns_month" required>
                                    @foreach($months as $m)
                                    <option value="{{ $m }}" {{ $m === $nightShift->month ? 'selected' : '' }}>{{ $m }}</option>
                                    @endforeach
                                </select>
                            </td>
                        </tr>

                        {{-- Days Selection --}}
                        <tr>
                            <td colspan="2">
                                <div class="d-flex align-items-center justify-content-between mb-2">
                                    <label class="form-label fw-semibold small text-uppercase mb-0">
                                        Select Days Worked <span class="text-danger">*</span>
                                    </label>
                                    <span class="soft-note">
                                        Tick a day, then select Platform and Unit.
                                        &nbsp;|&nbsp;
                                        <strong>Rate:</strong>
                                        TZS {{ number_format($nightAllowanceAmountPerDay, 2) }} / night
                                    </span>
                                </div>

                                <div id="days-selection">
                                    <table class="table table-sm days-table">
                                        <thead>
                                            <tr>
                                                <th style="width:20%; text-align:left;">Day</th>
                                                <th style="width:8%; text-align:center;">Worked</th>
                                                <th style="text-align:left;">Platform, Unit &amp; Hours</th>
                                            </tr>
                                        </thead>
                                        <tbody id="days-tbody"></tbody>
                                    </table>
                                </div>
                            </td>
                        </tr>

                        {{-- Summary --}}
                        <tr>
                            <td colspan="2">
                                <div class="summary-card">
                                    <div class="summary-title">Night Allowances Summary</div>
                                    <div class="muted" id="summary-empty">No days selected yet.</div>
                                    <div id="summary-wrap" style="display:none;">
                                        <table class="summary-table">
                                            <thead>
                                                <tr>
                                                    <th>Date</th>
                                                    <th>Platform</th>
                                                    <th>Unit</th>
                                                    <th>Hours</th>
                                                    <th>Amount</th>
                                                </tr>
                                            </thead>
                                            <tbody id="summary-body"></tbody>
                                        </table>
                                        <div class="metrics mt-2">
                                            <div class="metric">
                                                <strong>Total Nights</strong>
                                                <div id="total-nights">0</div>
                                            </div>
                                            <div class="metric">
                                                <strong>Total Hours</strong>
                                                <div id="total-hours">0</div>
                                            </div>
                                            <div class="metric">
                                                <strong>Total Amount</strong>
                                                <div id="total-amount">TZS 0.00</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </td>
                        </tr>

                        {{-- Description --}}
                        <tr>
                            <td colspan="2">
                                <label class="form-label fw-semibold small text-uppercase d-block mb-1">
                                    Description / Notes
                                </label>
                                <textarea name="description" class="form-control" rows="3"
                                          placeholder="Optional notes…">{{ old('description', $nightShift->description ?? '') }}</textarea>
                            </td>
                        </tr>
                    </table>
                </div>

                <a href="{{ route('night-shift.index') }}" class="btn btn-secondary mt-2 me-2">
                    <i class="fas fa-arrow-left"></i> Cancel
                </a>
                <button type="submit" class="btn btn-warning mt-2 js-submit-btn" id="submitBtn"
                        data-loading-label="Resubmitting...">
                    <span class="btn-text"><i class="fas fa-redo me-1"></i> Resubmit Claim</span>
                    <span class="spinner-border spinner-border-sm ms-2 d-none js-spinner" role="status"></span>
                </button>
            </form>

        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {

    const platforms    = @json($platforms);   // [{id, name, manager_user_id}]
    const allUnits     = @json($units);        // [{id, platform_id, name, incharge_user_id}]
    const flowKey      = @json($flowKey);
    const needsIncharge   = ['incharge_platform', 'incharge_lm_hec'].includes(flowKey);
    const needsPlatformMgr = flowKey === 'incharge_platform';
    const existingDays = @json($existingDays);   // {iso: {platform_id, unit_id, hours}}
    const monthEl      = document.getElementById('ns_month');
    const yearEl       = document.getElementById('ns_year');
    const tbody        = document.getElementById('days-tbody');
    const summaryEmpty = document.getElementById('summary-empty');
    const summaryWrap  = document.getElementById('summary-wrap');
    const summaryBody  = document.getElementById('summary-body');
    const totalNightsEl = document.getElementById('total-nights');
    const totalHoursEl  = document.getElementById('total-hours');
    const totalAmountEl = document.getElementById('total-amount');
    const ratePerDay    = {{ $nightAllowanceAmountPerDay }};

    const validMonths = ['January','February','March','April','May','June',
                         'July','August','September','October','November','December'];

    let isFirstBuild = true;

    function getMonthIndex() { return validMonths.indexOf(monthEl.value); }
    function getYear()       { return parseInt(yearEl.value, 10); }
    function daysInMonth(y, m) { return new Date(y, m + 1, 0).getDate(); }

    function platformOpts(sel = '') {
        let h = `<option value="">— Platform —</option>`;
        platforms.forEach(p => {
            h += `<option value="${p.id}" ${String(p.id) === String(sel) ? 'selected' : ''}>${p.name}</option>`;
        });
        return h;
    }

    function unitOpts(platformId, sel = '') {
        const list = allUnits.filter(u => String(u.platform_id) === String(platformId));
        if (!list.length) return `<option value="">— No units —</option>`;
        let h = `<option value="">— Unit (optional) —</option>`;
        list.forEach(u => {
            h += `<option value="${u.id}" ${String(u.id) === String(sel) ? 'selected' : ''}>${u.name}</option>`;
        });
        return h;
    }

    function buildDays() {
        tbody.innerHTML = '';
        const mIdx = getMonthIndex();
        const year = getYear();
        if (mIdx < 0 || isNaN(year)) return;

        const today = new Date();
        today.setHours(0, 0, 0, 0);

        const total = daysInMonth(year, mIdx);
        for (let d = 1; d <= total; d++) {
            const date    = new Date(year, mIdx, d);
            const weekday = date.toLocaleString('en-US', { weekday: 'long' });
            const isWE    = date.getDay() === 0 || date.getDay() === 6;
            const isFuture = date > today;
            const iso     = `${year}-${String(mIdx+1).padStart(2,'0')}-${String(d).padStart(2,'0')}`;

            const tr = document.createElement('tr');
            tr.className = isWE ? 'weekend' : '';
            if (isFuture) tr.style.opacity = '0.45';
            tr.dataset.iso = iso;

            tr.innerHTML = `
                <td>
                    <span>${String(d).padStart(2,'0')} – ${weekday}</span>
                    ${isFuture ? '<span class="text-muted small ms-1">(future)</span>' : ''}
                </td>
                <td class="text-center">
                    <input type="hidden" name="worked_days[${iso}][worked]" value="0">
                    <input type="checkbox" class="worked-checkbox" id="cb-${iso}"
                           name="worked_days[${iso}][worked]" value="1"
                           ${isFuture ? 'disabled title="Cannot claim future days"' : ''}>
                </td>
                <td>
                    <div class="day-entry-wrap" id="entry-${iso}" style="display:none;">
                        <div class="entry-grid">
                            <div>
                                <label class="small fw-semibold d-block mb-1">Platform</label>
                                <select name="worked_days[${iso}][platform_id]"
                                        class="form-control form-control-sm platform-sel"
                                        data-iso="${iso}">
                                    ${platformOpts()}
                                </select>
                            </div>
                            <div>
                                <label class="small fw-semibold d-block mb-1">Unit <span class="soft-note">(optional)</span></label>
                                <select name="worked_days[${iso}][unit_id]"
                                        class="form-control form-control-sm unit-sel"
                                        id="unit-${iso}">
                                    <option value="">— Select platform first —</option>
                                </select>
                            </div>
                            <div>
                                <label class="small fw-semibold d-block mb-1">Hours <span class="text-danger">*</span></label>
                                <input type="number" name="worked_days[${iso}][hours]"
                                       class="form-control form-control-sm hours-input hours-sel"
                                       min="1" max="24" placeholder="hrs"
                                       data-iso="${iso}">
                            </div>
                        </div>
                    </div>
                </td>`;

            tbody.appendChild(tr);

            const cb       = tr.querySelector('.worked-checkbox');
            const entryDiv = tr.querySelector(`#entry-${iso}`);

            cb.addEventListener('change', () => {
                entryDiv.style.display = cb.checked ? 'block' : 'none';
                updateSummary();
            });

            const platSel = tr.querySelector('.platform-sel');
            const unitSel = tr.querySelector(`#unit-${iso}`);
            platSel.addEventListener('change', function () {
                unitSel.innerHTML = unitOpts(this.value);
                checkUnitWarning(iso);
                updateSummary();
            });
            unitSel.addEventListener('change', function () {
                checkUnitWarning(iso);
                updateSummary();
            });

            const hoursSel = tr.querySelector('.hours-sel');
            hoursSel.addEventListener('input', updateSummary);
        }

        // Pre-populate existing days on first build
        if (isFirstBuild) {
            isFirstBuild = false;
            Object.entries(existingDays).forEach(([iso, data]) => {
                const cb = document.getElementById(`cb-${iso}`);
                if (!cb) return;
                cb.checked = true;
                const entryDiv = document.getElementById(`entry-${iso}`);
                if (entryDiv) entryDiv.style.display = 'block';

                const platSel = document.querySelector(`select[name="worked_days[${iso}][platform_id]"]`);
                if (platSel && data.platform_id) {
                    platSel.value = String(data.platform_id);
                    const unitSel = document.getElementById(`unit-${iso}`);
                    if (unitSel) unitSel.innerHTML = unitOpts(data.platform_id, data.unit_id);
                }

                const hoursSel = document.querySelector(`input[name="worked_days[${iso}][hours]"]`);
                if (hoursSel && data.hours) {
                    hoursSel.value = data.hours;
                }
            });
        }

        updateSummary();
    }

    function updateSummary() {
        const rows = [];
        tbody.querySelectorAll('tr').forEach(tr => {
            const iso  = tr.dataset.iso;
            const cb   = tr.querySelector('.worked-checkbox');
            if (!cb?.checked) return;
            const platSel  = tr.querySelector('.platform-sel');
            const unitSel  = tr.querySelector(`#unit-${iso}`);
            const hoursSel = tr.querySelector('.hours-sel');
            const platName = platSel?.options[platSel.selectedIndex]?.text || '—';
            const unitName = unitSel?.options[unitSel.selectedIndex]?.text || '—';
            const hours    = hoursSel?.value || '';
            rows.push({ iso, platName, unitName, hours });
        });

        if (rows.length === 0) {
            summaryEmpty.style.display = 'block';
            summaryWrap.style.display  = 'none';
            totalNightsEl.textContent  = '0';
            totalHoursEl.textContent   = '0';
            totalAmountEl.textContent  = 'TZS 0.00';
            return;
        }

        summaryEmpty.style.display = 'none';
        summaryWrap.style.display  = 'block';
        totalNightsEl.textContent  = rows.length;
        totalHoursEl.textContent   = rows.reduce((s, r) => s + (parseFloat(r.hours) || 0), 0);

        const totalAmt = rows.length * ratePerDay;
        totalAmountEl.textContent = 'TZS ' + totalAmt.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

        summaryBody.innerHTML = rows.map(r => `
            <tr>
                <td>${r.iso}</td>
                <td>${r.platName === '— Platform —' ? '<span class="text-danger">Not selected</span>' : r.platName}</td>
                <td>${r.unitName === '— Select platform first —' || r.unitName === '— Unit (optional) —' || r.unitName === '— No units —' ? '—' : r.unitName}</td>
                <td>${r.hours ? r.hours + ' hrs' : '<span class="text-danger">—</span>'}</td>
                <td>TZS ${ratePerDay.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</td>
            </tr>`).join('');
    }

    /* ── Unit/platform approval-chain warning ── */
    function checkUnitWarning(iso) {
        let warnEl = document.getElementById(`unit-warn-${iso}`);
        if (!warnEl) {
            warnEl = document.createElement('div');
            warnEl.id = `unit-warn-${iso}`;
            warnEl.className = 'text-danger small mt-1';
            warnEl.style.display = 'none';
            const entryDiv = document.getElementById(`entry-${iso}`);
            entryDiv?.appendChild(warnEl);
        }
        const unitSel = document.getElementById(`unit-${iso}`);
        const platSel = document.querySelector(`select[name="worked_days[${iso}][platform_id]"]`);
        const unitId  = unitSel?.value;
        const platId  = platSel?.value;

        let msg = '';
        if (needsIncharge && unitId) {
            const unit = allUnits.find(u => String(u.id) === String(unitId));
            if (unit && !unit.incharge_user_id) {
                msg = `Unit "${unit.name}" has no In-Charge assigned — submission will be blocked.`;
            }
        }
        if (!msg && needsPlatformMgr && platId) {
            const plat = platforms.find(p => String(p.id) === String(platId));
            if (plat && !plat.manager_user_id) {
                msg = `Platform "${plat.name}" has no Platform Manager assigned — submission will be blocked.`;
            }
        }
        warnEl.textContent = msg;
        warnEl.style.display = msg ? 'block' : 'none';
    }

    document.getElementById('ns-form').addEventListener('submit', function (e) {
        const checked = tbody.querySelectorAll('.worked-checkbox:checked');
        if (checked.length === 0) {
            e.preventDefault();
            alert('Please select at least one worked day.');
            return;
        }

        // Validate unit/platform approval chain
        let approvalError = '';
        checked.forEach(cb => {
            if (approvalError) return;
            const iso     = cb.closest('tr')?.dataset.iso;
            const unitSel = iso ? document.getElementById(`unit-${iso}`) : null;
            const platSel = iso ? document.querySelector(`select[name="worked_days[${iso}][platform_id]"]`) : null;
            const unitId  = unitSel?.value;
            const platId  = platSel?.value;

            if (needsIncharge && unitId) {
                const unit = allUnits.find(u => String(u.id) === String(unitId));
                if (unit && !unit.incharge_user_id) {
                    approvalError = `Unit "${unit.name}" has no In-Charge assigned. Please contact the administrator or select a different unit.`;
                }
            }
            if (!approvalError && needsPlatformMgr && platId) {
                const plat = platforms.find(p => String(p.id) === String(platId));
                if (plat && !plat.manager_user_id) {
                    approvalError = `Platform "${plat.name}" has no Platform Manager assigned. Please contact the administrator or select a different platform.`;
                }
            }
        });

        if (approvalError) {
            e.preventDefault();
            let errBox = document.getElementById('js-approval-error');
            if (!errBox) {
                errBox = document.createElement('div');
                errBox.id = 'js-approval-error';
                errBox.className = 'alert alert-danger alert-dismissible fade show';
                document.getElementById('ns-form').prepend(errBox);
            }
            errBox.innerHTML = `<strong>Cannot resubmit:</strong> ${approvalError} <button type="button" class="btn-close" data-bs-dismiss="alert"></button>`;
            errBox.scrollIntoView({ behavior: 'smooth', block: 'center' });
            return;
        }

        const btn = document.getElementById('submitBtn');
        btn.classList.add('btn-loading');
        btn.querySelector('.btn-text').textContent = btn.dataset.loadingLabel;
        btn.querySelector('.js-spinner').classList.remove('d-none');
        btn.disabled = true;
    });

    buildDays();
    monthEl.addEventListener('change', buildDays);
    yearEl.addEventListener('change', buildDays);
});
</script>
@endsection
