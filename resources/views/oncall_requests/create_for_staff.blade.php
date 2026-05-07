@extends('layouts.template')

@section('content')
    @include('sweetalert::alert')

    <div class="page-wrapper">
        <div class="content container-fluid">

            {{-- Header --}}
            <div class="page-header">
                <div class="row align-items-center">
                    <div class="col-sm-7">
                        <h4 class="page-title mb-0">Claim On-Call for Staff</h4>
                        <div class="text-muted small">Submit an on-call claim on behalf of a staff member in your department.</div>
                    </div>
                    <div class="col-sm-5 text-sm-end">
                        <a href="{{ route('oncall_requests.index') }}" class="btn btn-secondary btn-sm">
                            <i class="fas fa-arrow-left me-1"></i> Back to On-Call
                        </a>
                    </div>
                </div>
            </div>

            {{-- Alerts --}}
            @if ($errors->any())
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <ul class="mb-0">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            @if (session('error'))
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    {!! session('error') !!}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            @if (session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            {{-- Form --}}
            <form action="{{ route('oncall_requests.claim_for_staff') }}" method="POST" enctype="multipart/form-data" id="claimForm" novalidate>
                @csrf

                <div class="row g-3">
                    {{-- Staff selector --}}
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Staff Member <span class="text-danger">*</span></label>
                        <select name="user_id" id="user_id" class="form-select" required>
                            <option value="">— Select staff —</option>
                            @foreach ($users as $u)
                                @php
                                    $full = trim(collect([$u->fname, $u->mname, $u->lname])->filter()->implode(' '));
                                @endphp
                                <option
                                    value="{{ $u->id }}"
                                    data-ccbrt="{{ $u->ccbrt_code ?? '' }}"
                                    data-full="{{ $full }}"
                                    data-dept="{{ $u->department->dept_name ?? '—' }}"
                                    data-username="{{ $u->username ?? '—' }}"
                                    {{ old('user_id') == $u->id ? 'selected' : '' }}
                                >
                                    {{ $full ?: $u->username }} ({{ $u->ccbrt_code ?? '—' }})
                                </option>
                            @endforeach
                        </select>
                        @error('user_id') <small class="text-danger">{{ $message }}</small> @enderror
                    </div>

                    {{-- Month --}}
                    <div class="col-md-3">
                        <label class="form-label fw-semibold">Month <span class="text-danger">*</span></label>
                        <select name="locum_month" id="locum_month" class="form-select" required>
                            <option value="">— Select month —</option>
                            @foreach ($months as $m)
                                <option value="{{ $m }}" {{ old('locum_month') === $m ? 'selected' : '' }}>
                                    {{ $m }}
                                </option>
                            @endforeach
                        </select>
                        @error('locum_month') <small class="text-danger">{{ $message }}</small> @enderror
                    </div>

                    {{-- Education level --}}
                    <div class="col-md-3">
                        <label class="form-label fw-semibold">Education Level <span class="text-danger">*</span></label>
                        <select name="education_level" id="education_level" class="form-select" required>
                            <option value="">— Select level —</option>
                            @foreach ($educationLevels as $level)
                                <option
                                    value="{{ $level }}"
                                    data-rate="{{ $onCallRates[$level] ?? 0 }}"
                                    {{ old('education_level') === $level ? 'selected' : '' }}
                                >
                                    {{ $level }} (TZS {{ number_format($onCallRates[$level] ?? 0) }}/day)
                                </option>
                            @endforeach
                        </select>
                        @error('education_level') <small class="text-danger">{{ $message }}</small> @enderror
                    </div>
                </div>

                {{-- Staff details + Summary --}}
                <div class="row g-3 mt-2">
                    <div class="col-md-6">
                        <div class="card border-0 shadow-sm h-100">
                            <div class="card-body">
                                <div class="fw-semibold mb-2">Staff Details</div>
                                <div class="d-grid" style="grid-template-columns: 140px 1fr; row-gap: .25rem;">
                                    <div class="text-muted">Full Name:</div><div id="s_full">—</div>
                                    <div class="text-muted">Username:</div><div id="s_username">—</div>
                                    <div class="text-muted">CCBRT Code:</div><div id="s_ccbrt">—</div>
                                    <div class="text-muted">Department:</div><div id="s_dept">—</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="card border-0 shadow-sm h-100">
                            <div class="card-body">
                                <div class="fw-semibold mb-2">Claim Summary</div>
                                <div class="d-grid" style="grid-template-columns: 180px 1fr; row-gap: .25rem;">
                                    <div class="text-muted">Selected Month:</div><div id="sum_month">—</div>
                                    <div class="text-muted">Rate (per day):</div><div id="sum_rate">TZS 0</div>
                                    <div class="text-muted">Claimed Days:</div><div id="sum_days">0</div>
                                    <div class="text-muted">Claimed Hours (sum):</div><div id="sum_hours">0.00</div>
                                    <div class="text-muted">Total Amount:</div><div id="sum_amount" class="fw-bold">TZS 0</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Description & Special Task --}}
                <div class="row g-3 mt-2">
                    <div class="col-md-8">
                        <label class="form-label fw-semibold">Description (optional)</label>
                        <textarea name="description" class="form-control" rows="3" placeholder="Add any notes here...">{{ old('description') }}</textarea>
                        @error('description') <small class="text-danger">{{ $message }}</small> @enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold mb-1">Routine ?</label>
                        @php $oldSpecial = old('has_special_task', '0'); @endphp
                        <div class="d-flex align-items-center gap-3">
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="has_special_task" id="st_no" value="0" {{ $oldSpecial === '0' ? 'checked' : '' }}>
                                <label class="form-check-label" for="st_no">Yes</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="has_special_task" id="st_yes" value="1" {{ $oldSpecial === '1' ? 'checked' : '' }}>
                                <label class="form-check-label" for="st_yes">No</label>
                            </div>
                        </div>
                        <div class="mt-2">
                            <input
                                type="file"
                                name="special_task_document"
                                id="special_task_document"
                                class="form-control"
                                accept=".pdf,.doc,.docx,.jpg,.jpeg,.png"
                                {{ $oldSpecial === '1' ? '' : 'disabled' }}
                                {{ $oldSpecial === '1' ? 'required' : '' }}
                            >
                            <small class="text-muted">PDF/DOC/JPG/PNG up to 10 MB</small><br>
                            @error('special_task_document') <small class="text-danger">{{ $message }}</small> @enderror
                        </div>
                    </div>
                </div>

                {{-- Worked Days --}}
                <div class="card border-0 shadow-sm mt-3">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <div class="fw-semibold">Worked Days</div>
                            <div>
                                <button type="button" class="btn btn-outline-secondary btn-sm" id="btnLoadBio">
                                    <i class="fas fa-sync-alt me-1"></i> Load BioTime
                                </button>
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-bordered table-sm align-middle" id="daysTable">
                                <thead class="table-light">
                                    <tr>
                                        <th style="width:140px;">Date</th>
                                        <th>Time In</th>
                                        <th>Time Out</th>
                                        <th>BioTime Hours</th>
                                        <th style="width:90px;">Worked?</th>
                                        <th style="width:120px;">Hours</th>
                                    </tr>
                                </thead>
                                <tbody id="daysBody">
                                    {{-- rows injected by JS --}}
                                </tbody>
                            </table>
                        </div>

                        <small class="text-muted d-block mt-1">
                            Note: BioTime is for reference only. Tick only the days you want to claim and adjust hours if needed.
                        </small>
                        @error('worked_days') <small class="text-danger d-block mt-1">{{ $message }}</small> @enderror
                    </div>
                </div>

                <div class="mt-3 d-flex gap-2">
                    <button type="submit" class="btn btn-primary">Submit Claim</button>
                    <a href="{{ route('oncall_requests.index') }}" class="btn btn-secondary">Cancel</a>
                </div>

            </form>
        </div>
    </div>

    {{-- Page styles --}}
    <style>
        #daysTable td, #daysTable th { vertical-align: middle; }
        .hours-disabled { background: #f8f9fa; }
    </style>

    {{-- Page scripts --}}
    <script>
        (function() {
            const usersSelect = document.getElementById('user_id');
            const monthSelect = document.getElementById('locum_month');
            const eduSelect   = document.getElementById('education_level');
            const daysBody    = document.getElementById('daysBody');
            const btnLoadBio  = document.getElementById('btnLoadBio');
            const claimForm   = document.getElementById('claimForm');

            const sFull = document.getElementById('s_full');
            const sUser = document.getElementById('s_username');
            const sCode = document.getElementById('s_ccbrt');
            const sDept = document.getElementById('s_dept');

            const sumMonth  = document.getElementById('sum_month');
            const sumRate   = document.getElementById('sum_rate');
            const sumDays   = document.getElementById('sum_days');
            const sumHours  = document.getElementById('sum_hours');
            const sumAmount = document.getElementById('sum_amount');

            const stNo   = document.getElementById('st_no');
            const stYes  = document.getElementById('st_yes');
            const stFile = document.getElementById('special_task_document');

            const CSRF = '{{ csrf_token() }}';
            const OLD  = @json(old('worked_days', [])); // {'YYYY-MM-DD': {worked:'0|1', hours:'N.NN'}}

            function getRate() {
                const opt = eduSelect.options[eduSelect.selectedIndex];
                const r = parseFloat(opt?.getAttribute('data-rate') || '0');
                return isFinite(r) ? r : 0;
            }

            function recalcSummary() {
                const rate = getRate();
                let days = 0, hours = 0;
                document.querySelectorAll('#daysBody tr').forEach(tr => {
                    const worked = tr.querySelector('input[type="checkbox"][data-role="worked"]');
                    const hrsInp = tr.querySelector('input[type="number"][data-role="hours"]');
                    if (worked && worked.checked) {
                        days += 1;
                        const v = parseFloat(hrsInp?.value || '0');
                        if (isFinite(v)) hours += v;
                    }
                });
                sumMonth.textContent  = monthSelect.value || '—';
                sumRate.textContent   = 'TZS ' + (getRate() ? getRate().toLocaleString() : '0');
                sumDays.textContent   = String(days);
                sumHours.textContent  = hours.toFixed(2);
                sumAmount.textContent = 'TZS ' + ((days * rate) || 0).toLocaleString(undefined, { maximumFractionDigits: 0 });
            }

            function updateStaffDetails() {
                const opt = usersSelect.options[usersSelect.selectedIndex];
                sFull.textContent = opt?.getAttribute('data-full')     || '—';
                sUser.textContent = opt?.getAttribute('data-username') || '—';
                sCode.textContent = opt?.getAttribute('data-ccbrt')    || '—';
                sDept.textContent = opt?.getAttribute('data-dept')     || '—';
            }

            function daysInMonth(year, monthIndex) { return new Date(year, monthIndex + 1, 0).getDate(); }

            function monthYearToParts(label) {
                if (!label) return null;
                const parts = label.trim().split(' ');
                if (parts.length !== 2) return null;
                const monthName = parts[0], year = parseInt(parts[1], 10);
                const map = {January:0,February:1,March:2,April:3,May:4,June:5,July:6,August:7,September:8,October:9,November:10,December:11};
                const mi = map[monthName];
                if (mi === undefined || !year) return null;
                return { monthIndex: mi, year, monthName };
            }

            function enableHours(input, bioHours) {
                input.disabled = false;
                input.classList.remove('hours-disabled');
                // If empty or 0, prime with BioTime hours (if > 0) else leave blank
                const current = parseFloat(input.value || '0');
                if (!current || current <= 0) {
                    const bh = parseFloat(bioHours || '0');
                    input.value = bh > 0 ? bh.toFixed(2) : '';
                }
                input.focus();
                input.select?.();
            }

            function disableHours(input) {
                input.disabled = true;
                input.classList.add('hours-disabled');
                input.value = '';
            }

            function buildGrid(bioData = {}) {
                const label = monthSelect.value;
                const parts = monthYearToParts(label);
                daysBody.innerHTML = '';
                if (!parts) return;

                const totalDays = daysInMonth(parts.year, parts.monthIndex);
                for (let d = 1; d <= totalDays; d++) {
                    const date = new Date(parts.year, parts.monthIndex, d);
                    const iso  = date.toISOString().slice(0, 10); // YYYY-MM-DD

                    const bi = bioData[iso] || { worked: 0, hours: '0.00', time_in: null, time_out: null };
                    const bioHours = typeof bi.hours === 'string' ? bi.hours : Number(bi.hours || 0).toFixed(2);

                    const oldForDay   = OLD[iso] || null;
                    const workedCheck = oldForDay ? String(oldForDay.worked ?? '0') === '1' : false;
                    const hoursValue  = (oldForDay && oldForDay.hours !== undefined && oldForDay.hours !== null && oldForDay.hours !== '')
                        ? String(oldForDay.hours)
                        : ''; // keep empty until user ticks

                    const tr = document.createElement('tr');
                    tr.innerHTML = `
                        <td>${date.toLocaleDateString('en-GB', { day:'2-digit', month:'short', year:'numeric' })}</td>
                        <td>${bi.time_in  ?? '—'}</td>
                        <td>${bi.time_out ?? '—'}</td>
                        <td>${bioHours}</td>
                        <td class="text-center">
                            <input type="hidden" name="worked_days[${iso}][worked]" value="0">
                            <input type="checkbox"
                                   class="form-check-input"
                                   data-role="worked"
                                   name="worked_days[${iso}][worked]"
                                   value="1" ${workedCheck ? 'checked' : ''}>
                        </td>
                        <td>
                            <input type="number" step="0.01" min="0.01" max="24"
                                   class="form-control form-control-sm"
                                   data-role="hours"
                                   data-biohours="${bioHours}"
                                   name="worked_days[${iso}][hours]"
                                   value="${hoursValue}">
                        </td>
                    `;
                    daysBody.appendChild(tr);

                    // Init hours enabled/disabled state
                    const chk = tr.querySelector('input[data-role="worked"]');
                    const hrs = tr.querySelector('input[data-role="hours"]');
                    if (workedCheck) {
                        enableHours(hrs, hrs.getAttribute('data-biohours'));
                    } else {
                        disableHours(hrs);
                    }

                    // Change handlers
                    chk.addEventListener('change', () => {
                        if (chk.checked) {
                            enableHours(hrs, hrs.getAttribute('data-biohours'));
                        } else {
                            disableHours(hrs);
                        }
                        recalcSummary();
                    });
                    hrs.addEventListener('input', recalcSummary);
                    hrs.addEventListener('change', recalcSummary);
                }

                recalcSummary();
            }

            async function loadBioTime() {
                const userOpt = usersSelect.options[usersSelect.selectedIndex];
                const ccbrt = userOpt?.getAttribute('data-ccbrt') || '';
                const monthLabel = monthSelect.value;
                if (!monthLabel) {
                    alert('Please select Month first.');
                    return;
                }
                try {
                    const resp = await fetch('{{ route('oncall_requests.fetch_biotime_incharge') }}', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
                        body: JSON.stringify({ ccbrt_code: ccbrt, month_year: monthLabel })
                    });
                    if (!resp.ok) { buildGrid(); return; }
                    const data = await resp.json();
                    buildGrid(data?.worked_days || {});
                } catch (e) {
                    buildGrid();
                }
            }

            // Client-side guard: no zero/blank hours for checked days
            claimForm.addEventListener('submit', function(e) {
                const bad = [];
                document.querySelectorAll('#daysBody tr').forEach(tr => {
                    const chk = tr.querySelector('input[data-role="worked"]');
                    const hrs = tr.querySelector('input[data-role="hours"]');
                    if (chk && chk.checked) {
                        const v = parseFloat(hrs.value || '0');
                        if (!isFinite(v) || v <= 0) {
                            const dateText = tr.children[0]?.textContent?.trim() || '(unknown date)';
                            bad.push(dateText);
                        }
                    }
                });
                if (bad.length) {
                    e.preventDefault();
                    alert('Please enter hours > 0 for:\n- ' + bad.join('\n- '));
                }
            });

            // Events
            usersSelect.addEventListener('change', () => { updateStaffDetails(); recalcSummary(); });
            monthSelect.addEventListener('change', () => { buildGrid({}); recalcSummary(); });
            eduSelect.addEventListener('change', recalcSummary);
            btnLoadBio.addEventListener('click', loadBioTime);

            stNo.addEventListener('change', () => {
                if (stNo.checked) { stFile.disabled = true; stFile.value = ''; stFile.removeAttribute('required'); }
            });
            stYes.addEventListener('change', () => {
                if (stYes.checked) { stFile.disabled = false; stFile.setAttribute('required', 'required'); }
            });

            // Initialize
            updateStaffDetails();
            if (monthSelect.value) { buildGrid({}); }
            sumMonth.textContent = monthSelect.value || '—';
            recalcSummary();
        })();
    </script>
@endsection
