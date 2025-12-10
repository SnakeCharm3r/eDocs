@extends('layouts.template')

@section('breadcrumb')
    @include('sweetalert::alert')
@endsection

@section('content')
    <div class="page-wrapper">
        <div class="content container-fluid">

            {{-- Alerts --}}
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

            {{-- Header --}}
            <div class="page-header">
                <div class="row align-items-center">
                    <div class="col-sm-6">
                        <h6 class="page-title mb-0">Claim Locum for Staff</h6>
                    </div>
                    <div class="col-sm-6 d-flex justify-content-end">
                        <a href="{{ route('locum-requests.index') }}" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Back to List
                        </a>
                    </div>
                </div>
            </div>

            <form action="{{ route('locum-requests.claim-for-staff') }}" method="POST" id="claim-form">
                @csrf

                <div class="row g-3">
                    {{-- Left: Main form --}}
                    <div class="col-lg-8">
                        <div class="card">
                            <div class="card-body">
                                {{-- Staff & Month --}}
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label for="user_id" class="form-label">Staff Member</label>
                                        <select name="user_id" id="user_id" class="form-select">
                                            <option value="">Select a staff member</option>
                                            @foreach ($users as $staff)
                                                @php $ag = $staff->locumAgreements->first(); @endphp
                                                <option value="{{ $staff->id }}"
                                                    data-ccbrt-code="{{ $staff->ccbrt_code ?? '' }}"
                                                    data-dept-name="{{ $staff->department ? $staff->department->dept_name : 'N/A' }}"
                                                    data-full-name="{{ trim($staff->fname . ' ' . $staff->mname . ' ' . $staff->lname) }}"
                                                    data-rate="{{ (float) ($ag->locum_rate ?? 0) }}"
                                                    data-edu-level="{{ $ag->education_level ?? '' }}"
                                                    data-edu-mult="{{ (float) ($ag->education_multiplier ?? 1) }}">
                                                    {{ $staff->fname }} {{ $staff->mname }} {{ $staff->lname }}
                                                </option>
                                            @endforeach
                                        </select>
                                        @error('user_id')
                                            <div class="text-danger small mt-1">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="col-md-6">
                                        <label for="locum_month" class="form-label">Locum Month</label>
                                        <select name="locum_month" id="locum_month" class="form-select">
                                            @foreach ($months as $month)
                                                <option value="{{ $month }}"
                                                    {{ $month == now()->format('F Y') ? 'selected' : '' }}>
                                                    {{ $month }}
                                                </option>
                                            @endforeach
                                        </select>
                                        @error('locum_month')
                                            <div class="text-danger small mt-1">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>

                                {{-- Staff Details strip --}}
                                <div id="user-details" class="bg-light rounded p-3 mt-3 d-none">
                                    <div class="row g-3">
                                        <div class="col-md-4">
                                            <label class="form-label mb-0 text-muted">Full Name</label>
                                            <input type="text" class="form-control-plaintext fw-semibold"
                                                id="user-full-name" readonly>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label mb-0 text-muted">CCBRT Code</label>
                                            <input type="text" class="form-control-plaintext" id="user-ccbrt-code"
                                                readonly>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label mb-0 text-muted">Department</label>
                                            <input type="text" class="form-control-plaintext" id="user-dept-name"
                                                readonly>
                                        </div>
                                    </div>
                                </div>

                                {{-- Actions above table --}}
                                <div class="d-flex flex-wrap gap-2 mt-3">
                                    <button type="button" class="btn btn-outline-primary btn-sm" id="btn-copy-biotime">Copy
                                        BioTime → Claim</button>
                                    <button type="button" class="btn btn-outline-secondary btn-sm"
                                        id="btn-select-all">Select All Worked</button>
                                    <button type="button" class="btn btn-outline-danger btn-sm" id="btn-clear-all">Clear
                                        All</button>
                                </div>

                                {{-- Worked Days Table --}}
                                <div class="table-responsive mt-3" style="max-height:60vh;">
                                    <table class="table table-sm table-bordered align-middle" id="worked-days-table">
                                        <thead class="table-light sticky-top">
                                            <tr>
                                                <th style="min-width:140px;">Date</th>
                                                <th style="min-width:120px;">Time In</th>
                                                <th style="min-width:120px;">Time Out</th>
                                                <th style="min-width:120px;">BioTime (hrs)</th>
                                                <th style="min-width:100px;">Worked</th>
                                                <th style="min-width:140px;">Claim (hrs)</th>
                                            </tr>
                                        </thead>
                                        <tbody id="worked-days-container">
                                            {{-- Filled by JS --}}
                                        </tbody>
                                        <tfoot class="table-light">
                                            <tr>
                                                <th colspan="3" class="text-end">Totals:</th>
                                                <th><span id="t-bio-hrs">0.00</span></th>
                                                <th><span id="t-claimed-days">0</span></th>
                                                <th><span id="t-claimed-hrs">0.00</span></th>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                                @error('worked_days')
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror

                                {{-- Reason (shows only when modified) --}}
                                <div id="reason-container" class="mt-3 d-none">
                                    <label for="reason" class="form-label">Reason for Modifying BioTime Data</label>
                                    <textarea name="reason" id="reason" class="form-control" rows="3"
                                        placeholder="Explain the discrepancy (e.g., corrected from 0 to 5 due to device outage)"></textarea>
                                    @error('reason')
                                        <div class="text-danger small mt-1">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Right: Summary / Submit --}}
                    <div class="col-lg-4">
                        <div class="card position-sticky" style="top:90px;">
                            <div class="card-body">
                                <h6 class="card-title mb-3">Monthly Summary</h6>

                                <div class="d-flex justify-content-between mini-kv mb-1">
                                    <span class="text-muted">BioTime Total Hours</span>
                                    <span id="sum-bio-hours" class="fw-semibold">0.00</span>
                                </div>
                                <div class="d-flex justify-content-between mini-kv mb-1">
                                    <span class="text-muted">Claimed Days</span>
                                    <span id="sum-claimed-days" class="fw-semibold">0</span>
                                </div>
                                <div class="d-flex justify-content-between mini-kv mb-1">
                                    <span class="text-muted">Claimed Hours</span>
                                    <span id="sum-claimed-hours" class="fw-semibold">0.00</span>
                                </div>

                                <hr class="my-2">

                                <div class="d-flex justify-content-between mini-kv mb-1">
                                    <span class="text-muted">Agreement Rate (TZS / day)</span>
                                    <span id="sum-rate" class="fw-semibold">0.00</span>
                                </div>
                                <div class="d-flex justify-content-between mini-kv mb-1">
                                    <span class="text-muted">Education Level</span>
                                    <span id="sum-edu-level" class="fw-semibold">—</span>
                                </div>
                                <div class="d-flex justify-content-between mini-kv mb-1">
                                    <span class="text-muted">Education Multiplier</span>
                                    <span id="sum-edu-mult" class="fw-semibold">1.00</span>
                                </div>

                                <hr class="my-2">

                                <div class="d-flex justify-content-between mini-kv">
                                    <span class="text-muted">Claimed Amount</span>
                                    <span id="sum-claimed-amount" class="fw-bold">0.00</span>
                                </div>

                                <div class="d-grid gap-2 mt-3">
                                    <button type="submit" class="btn btn-primary">Submit Locum Request</button>
                                    <a href="{{ route('locum-requests.index') }}"
                                        class="btn btn-outline-secondary">Cancel</a>
                                </div>
                                <small class="text-muted d-block mt-2">
                                    Note: Changing “Worked” or “Claim (hrs)” will require a reason.
                                </small>
                            </div>
                        </div>
                    </div>
                </div>
            </form>

        </div>
    </div>

    {{-- Styles --}}
    <style>
        .table th,
        .table td {
            vertical-align: middle;
        }

        .form-control-plaintext {
            padding: .375rem 0;
        }

        .sticky-top {
            position: sticky;
            top: 0;
            z-index: 5;
        }

        .custom-checkbox .form-check-input {
            width: 1.25em;
            height: 1.25em;
            cursor: pointer;
        }

        .mini-kv {
            font-size: .95rem;
        }
    </style>

    {{-- Script --}}
    <script>
        let originalBiotimeData = {};
        let isLoading = false;

        // Current agreement terms (from selected staff option)
        let currentRate = 0.0;
        let currentEduMult = 1.0;
        let currentEduLevel = '—';

        const $$ = (sel, ctx = document) => ctx.querySelector(sel);
        const $$$ = (sel, ctx = document) => Array.from(ctx.querySelectorAll(sel));

        function setLoadingTable(loading) {
            const tbody = $$('#worked-days-container');
            if (loading) {
                tbody.innerHTML = `
        <tr><td colspan="6" class="text-center text-muted py-4">
          <div class="spinner-border spinner-border-sm me-2" role="status"></div>
          Loading BioTime data...
        </td></tr>`;
            } else if (!tbody.children.length) {
                tbody.innerHTML = `<tr><td colspan="6" class="text-center text-muted py-4">No data</td></tr>`;
            }
        }

        function fetchUserDetails() {
            const userSelect = $$('#user_id');
            const option = userSelect.options[userSelect.selectedIndex];
            const monthYear = $$('#locum_month').value;
            const details = $$('#user-details');

            if (!option || !option.value) {
                details.classList.add('d-none');
                originalBiotimeData = {};
                updateWorkedDays();
                // Reset agreement summary
                currentRate = 0;
                currentEduMult = 1;
                currentEduLevel = '—';
                $$('#sum-rate').textContent = '0.00';
                $$('#sum-edu-level').textContent = '—';
                $$('#sum-edu-mult').textContent = '1.00';
                $$('#sum-claimed-amount').textContent = '0.00';
                return;
            }

            // Show details strip from option data
            $$('#user-full-name').value = option.getAttribute('data-full-name') || '';
            const ccbrtCodeValue = option.getAttribute('data-ccbrt-code') || 'N/A';
            $$('#user-ccbrt-code').value = ccbrtCodeValue;
            $$('#user-dept-name').value = option.getAttribute('data-dept-name') || 'N/A';
            details.classList.remove('d-none');

            // Agreement terms
            currentRate = Number.parseFloat(option.getAttribute('data-rate') || '0') || 0;
            currentEduMult = Number.parseFloat(option.getAttribute('data-edu-mult') || '1') || 1;
            currentEduLevel = (option.getAttribute('data-edu-level') || '—').toString();

            $$('#sum-rate').textContent = currentRate.toFixed(2);
            $$('#sum-edu-level').textContent = currentEduLevel || '—';
            $$('#sum-edu-mult').textContent = currentEduMult.toFixed(2);

            // Optional: refresh details from /api/users/{code}
            if (ccbrtCodeValue && ccbrtCodeValue !== 'N/A') {
                fetch(`/api/users/${ccbrtCodeValue}`)
                    .then(r => r.ok ? r.json() : null)
                    .then(d => {
                        if (!d) return;
                        $$('#user-full-name').value = d.full_name || $$('#user-full-name').value;
                        $$('#user-ccbrt-code').value = d.ccbrt_code || $$('#user-ccbrt-code').value;
                        $$('#user-dept-name').value = (d.department && d.department.dept_name) || $$('#user-dept-name')
                            .value;
                    })
                    .catch(() => {});
            }

            // Fetch BioTime rows for month
            if (monthYear) {
                isLoading = true;
                setLoadingTable(true);
                fetch('{{ route('locum-requests.fetch-biotime-incharge') }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify({
                            ccbrt_code: ccbrtCodeValue,
                            month_year: monthYear
                        })
                    })
                    .then(r => r.json())
                    .then(data => {
                        originalBiotimeData = data && !data.error ? (data.worked_days || {}) : {};
                        updateWorkedDays(originalBiotimeData);
                    })
                    .catch(() => {
                        originalBiotimeData = {};
                        updateWorkedDays();
                    })
                    .finally(() => {
                        isLoading = false;
                    });
            } else {
                originalBiotimeData = {};
                updateWorkedDays();
            }
        }

        function updateWorkedDays(biotimeData = {}) {
            const monthYear = $$('#locum_month').value;
            const tbody = $$('#worked-days-container');
            const reason = $$('#reason-container');

            if (!monthYear) {
                tbody.innerHTML = '';
                reason.classList.add('d-none');
                return;
            }

            const [month, year] = monthYear.split(' ');
            const daysInMonth = new Date(year, new Date(Date.parse(month + ' 1,' + year)).getMonth() + 1, 0).getDate();
            let html = '';

            for (let day = 1; day <= daysInMonth; day++) {
                const dateStr = `${day} ${month} ${year}`;
                const bi = biotimeData[dateStr] || {
                    worked: 0,
                    hours: '0.00',
                    time_in: '-',
                    time_out: '-'
                };

                const checked = String(bi.worked) === '1';
                const claimHours = bi.hours || '0.00'; // start with BioTime value
                const disabled = checked ? '' : 'disabled';

                html += `
        <tr data-date="${dateStr}">
          <td>${dateStr}</td>
          <td>${bi.time_in || '-'}</td>
          <td>${bi.time_out || '-'}</td>
          <td class="text-end"><span class="bio-hrs">${Number.parseFloat(bi.hours || '0').toFixed(2)}</span></td>
          <td class="text-center">
            <input type="checkbox" class="form-check-input worked-toggle" ${checked ? 'checked' : ''}>
          </td>
          <td>
            <input type="number" step="0.01" min="0" max="24" class="form-control form-control-sm claim-hrs" value="${Number.parseFloat(claimHours || '0').toFixed(2)}" ${disabled}>
            <input type="hidden" name="worked_days[${dateStr}][worked]" value="${checked ? 1 : 0}">
            <input type="hidden" name="worked_days[${dateStr}][hours]" value="${Number.parseFloat(claimHours || '0').toFixed(2)}">
          </td>
        </tr>`;
            }

            tbody.innerHTML = html || `<tr><td colspan="6" class="text-center text-muted py-4">No data</td></tr>`;
            bindRowEvents();
            recomputeTotals();
            checkForModifications();
        }

        function bindRowEvents() {
            // Toggle enable/disable claim hours + sync hidden inputs
            $$$('#worked-days-container .worked-toggle').forEach(cb => {
                cb.addEventListener('change', e => {
                    const tr = e.target.closest('tr');
                    const claimInput = $$('.claim-hrs', tr);
                    claimInput.disabled = !e.target.checked;

                    // If checking and claim is 0, default to BioTime hours
                    if (e.target.checked && Number.parseFloat(claimInput.value || '0') === 0) {
                        const bio = Number.parseFloat($$('.bio-hrs', tr).textContent || '0') || 0;
                        claimInput.value = bio.toFixed(2);
                    }
                    // Update hidden fields
                    $$('input[type="hidden"][name^="worked_days"][name$="[worked]"]', tr).value = e.target
                        .checked ? 1 : 0;
                    $$('input[type="hidden"][name^="worked_days"][name$="[hours]"]', tr).value = (Number
                        .parseFloat(claimInput.value || '0') || 0).toFixed(2);

                    recomputeTotals();
                    checkForModifications();
                });
            });

            // Change claim hours -> auto-check Worked when > 0
            $$$('#worked-days-container .claim-hrs').forEach(inp => {
                inp.addEventListener('input', e => {
                    const tr = e.target.closest('tr');
                    const cb = $$('.worked-toggle', tr);
                    let v = Number.parseFloat(e.target.value || '0');
                    if (Number.isNaN(v) || v < 0) v = 0;
                    if (v > 24) v = 24;
                    e.target.value = v.toFixed(2);

                    if (v > 0 && !cb.checked) {
                        cb.checked = true;
                        cb.dispatchEvent(new Event('change'));
                        return; // change handler will set hidden fields & totals
                    }

                    // Sync hidden hours
                    $$('input[type="hidden"][name^="worked_days"][name$="[hours]"]', tr).value = v.toFixed(
                        2);
                    recomputeTotals();
                    checkForModifications();
                });
            });
        }

        function recomputeClaimedAmount(claimedDays) {
            const amount = (claimedDays * currentRate * currentEduMult) || 0;
            $$('#sum-claimed-amount').textContent = amount.toFixed(2);
        }

        function recomputeTotals() {
            let bioHours = 0,
                claimedHours = 0,
                claimedDays = 0;

            $$$('#worked-days-container tr').forEach(tr => {
                const bio = Number.parseFloat($$('.bio-hrs', tr)?.textContent || '0') || 0;
                const cb = $$('.worked-toggle', tr);
                const claim = Number.parseFloat($$('.claim-hrs', tr)?.value || '0') || 0;

                bioHours += bio;
                if (cb && cb.checked) {
                    claimedDays += 1;
                    claimedHours += claim;
                }
            });

            // Footer totals
            $$('#t-bio-hrs').textContent = bioHours.toFixed(2);
            $$('#t-claimed-days').textContent = String(claimedDays);
            $$('#t-claimed-hrs').textContent = claimedHours.toFixed(2);

            // Summary card
            $$('#sum-bio-hours').textContent = bioHours.toFixed(2);
            $$('#sum-claimed-days').textContent = String(claimedDays);
            $$('#sum-claimed-hours').textContent = claimedHours.toFixed(2);

            // Live claimed amount = days × rate × multiplier
            recomputeClaimedAmount(claimedDays);
        }

        function checkForModifications() {
            const reason = $$('#reason-container');
            let modified = false;

            $$$('#worked-days-container tr').forEach(tr => {
                const dateStr = tr.getAttribute('data-date');
                const orig = originalBiotimeData[dateStr] || {
                    worked: 0,
                    hours: '0.00'
                };
                const currWorked = $$('.worked-toggle', tr)?.checked ? 1 : 0;
                const currHours = Number.parseFloat($$('.claim-hrs', tr)?.value || '0').toFixed(2);
                const origHours = Number.parseFloat(orig.hours || '0').toFixed(2);

                if (currWorked !== Number(orig.worked) || currHours !== origHours) {
                    modified = true;
                }
            });

            reason.classList.toggle('d-none', !modified);
        }

        // Bulk controls
        $$('#btn-copy-biotime')?.addEventListener('click', () => {
            $$$('#worked-days-container tr').forEach(tr => {
                const bio = Number.parseFloat($$('.bio-hrs', tr)?.textContent || '0') || 0;
                const cb = $$('.worked-toggle', tr);
                const inp = $$('.claim-hrs', tr);

                cb.checked = bio > 0;
                inp.disabled = !cb.checked;
                inp.value = bio.toFixed(2);
                $$('input[type="hidden"][name^="worked_days"][name$="[worked]"]', tr).value = cb.checked ?
                    1 : 0;
                $$('input[type="hidden"][name^="worked_days"][name$="[hours]"]', tr).value = bio.toFixed(2);
            });
            recomputeTotals();
            checkForModifications();
        });

        $$('#btn-select-all')?.addEventListener('click', () => {
            $$$('#worked-days-container tr').forEach(tr => {
                const cb = $$('.worked-toggle', tr);
                const inp = $$('.claim-hrs', tr);
                if (!cb.checked) {
                    cb.checked = true;
                    inp.disabled = false;
                    // If claim is 0, default to 8.00
                    let v = Number.parseFloat(inp.value || '0');
                    if (v === 0) v = 8;
                    inp.value = v.toFixed(2);
                }
                $$('input[type="hidden"][name^="worked_days"][name$="[worked]"]', tr).value = 1;
                $$('input[type="hidden"][name^="worked_days"][name$="[hours]"]', tr).value = Number
                    .parseFloat(inp.value || '0').toFixed(2);
            });
            recomputeTotals();
            checkForModifications();
        });

        $$('#btn-clear-all')?.addEventListener('click', () => {
            $$$('#worked-days-container tr').forEach(tr => {
                const cb = $$('.worked-toggle', tr);
                const inp = $$('.claim-hrs', tr);
                cb.checked = false;
                inp.disabled = true;
                inp.value = '0.00';
                $$('input[type="hidden"][name^="worked_days"][name$="[worked]"]', tr).value = 0;
                $$('input[type="hidden"][name^="worked_days"][name$="[hours]"]', tr).value = '0.00';
            });
            recomputeTotals();
            checkForModifications();
        });

        // Wiring: load on change
        $$('#locum_month').addEventListener('change', fetchUserDetails);
        $$('#user_id').addEventListener('change', fetchUserDetails);

        // Initial state
        setLoadingTable(false);
        updateWorkedDays();
    </script>
@endsection
