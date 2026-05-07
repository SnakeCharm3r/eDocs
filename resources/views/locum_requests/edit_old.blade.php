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
            border: 1px solid #000;
            padding: 10px;
            text-align: left
        }

        .request-table th {
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
            text-align: center
        }

        .days-table th {
            background: #f8f8f8
        }

        .weekend {
            background: #ffe6e6
        }

        .holiday {
            background: #e6f3ff
        }

        .hours-input {
            width: 80px;
            text-align: center
        }

        .worked-checkbox {
            width: 20px;
            height: 20px;
            cursor: pointer;
            vertical-align: middle;
            transform: scale(1.2)
        }

        .worked-checkbox:checked {
            accent-color: #459c51
        }

        .status-badge {
            font-size: .9em;
            padding: .4em .8em
        }

        .invalid-feedback {
            font-size: 12px;
            color: #dc3545;
            display: none
        }

        .is-invalid+.invalid-feedback {
            display: block
        }

        .bio-time-diff {
            color: #e74c3c;
            font-size: 12px;
            font-style: italic
        }
    </style>

    <div class="page-wrapper">
        <div class="content container-fluid">
            <div class="page-header">
                <div class="row">
                    <div class="col-sm-6">
                        <h3 class="page-title">Edit & Resubmit Locum Request</h3>
                    </div>
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
                        <strong>Please fix the following:</strong>
                        <ul class="mb-0">
                            @foreach ($errors->all() as $e)
                                <li>{{ $e }}</li>
                            @endforeach
                        </ul>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif

                @php
                    /** @var \App\Models\User $user */
                    $user = auth()->user();
                    $months = $months ?? [
                        'January',
                        'February',
                        'March',
                        'April',
                        'May',
                        'June',
                        'July',
                        'August',
                        'September',
                        'October',
                        'November',
                        'December',
                    ];
                    $selectedMonth = old('locum_month', $request->locum_month);
                    $selectedYear = old('locum_year', $request->locum_year);
                    $currentYear = (int) ($selectedYear ?: date('Y'));

                    // holidays for selectedYear
                    $holidays = [
                        "$currentYear-01-01" => "New Year's Day",
                        "$currentYear-01-12" => 'Zanzibar Revolution Day',
                        "$currentYear-03-29" => 'Eid al-Fitr (Estimated)',
                        "$currentYear-04-07" => 'Karume Day',
                        "$currentYear-04-18" => 'Good Friday',
                        "$currentYear-04-21" => 'Easter Monday',
                        "$currentYear-04-26" => 'Union Day',
                        "$currentYear-05-01" => "Workers' Day",
                        "$currentYear-06-05" => 'Eid al-Adha (Estimated)',
                        "$currentYear-07-07" => 'Saba Saba',
                        "$currentYear-08-08" => 'Nane Nane',
                        "$currentYear-09-27" => 'Maulid (Estimated)',
                        "$currentYear-10-14" => 'Nyerere Day',
                        "$currentYear-12-09" => 'Independence Day',
                        "$currentYear-12-25" => 'Christmas Day',
                        "$currentYear-12-26" => 'Boxing Day',
                    ];

                    // prior worked_days from the existing request
                    $existingWorkedDays = $request->worked_days;
                    if (is_string($existingWorkedDays)) {
                        $existingWorkedDays = json_decode($existingWorkedDays, true);
                    }
                    $existingWorkedDays = is_array($existingWorkedDays) ? $existingWorkedDays : [];
                @endphp

                {{-- @if ($agreement && $agreement->has_contract) --}}
                <form action="{{ route('locum-requests.update', $request->id) }}" method="POST" id="locum-request-form"
                    novalidate>
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="locum_agreement_id" value="{{ $agreement->id }}">
                    <input type="hidden" id="locum_year" name="locum_year" value="{{ $selectedYear }}">

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
                                <td>{{ $user->department->dept_name ?? 'N/A' }}</td>
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

                    <div class="pdf-section">
                        <h5>REQUEST DETAILS</h5>
                        <table class="request-table">
                            <tr>
                                <th>Locum Month</th>
                                <td>
                                    <select class="form-control" id="locum_month" name="locum_month" required>
                                        @foreach ($months as $m)
                                            <option value="{{ $m }}"
                                                {{ $selectedMonth === $m ? 'selected' : '' }}>{{ $m }} {{ $selectedYear }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <div class="invalid-feedback">Please select a month.</div>
                                </td>
                            </tr>

                            <tr>
                                <th>Select Days Worked <span class="text-danger">*</span></th>
                                <td>
                                    <div id="days-selection" style="display:none;">
                                        <table class="table table-sm table-striped days-table">
                                            <thead>
                                                <tr>
                                                    <th>Date</th>
                                                    <th>Day</th>
                                                    <th>Type</th>
                                                    <th>Worked</th>
                                                    <th>Hours</th>
                                                    <th>BioTime</th>
                                                </tr>
                                            </thead>
                                            <tbody id="days-table-body"></tbody>
                                        </table>
                                        <div class="totals-box mt-2 p-3 border rounded bg-light">
                                            <p><strong>Total Days Selected: <span id="total-days">0</span></strong></p>
                                            <p><strong>Total Hours: <span id="total-hours">0</span></strong></p>
                                            <p><strong>Total BioTime: <span id="total-biostime">0</span> hrs</strong>
                                            </p>
                                        </div>
                                    </div>
                                    <div class="invalid-feedback" id="worked-days-error">Please select at least one day
                                        worked.</div>
                                </td>
                            </tr>

                            <tr>
                                <th>Total Amount Payable</th>
                                <td id="total_amount_payable">TZS
                                    {{ number_format($request->total_amount_payable, 2) }}</td>
                            </tr>
                            <tr>
                                <th>Description</th>
                                <td>
                                    <textarea name="description" class="form-control" rows="3"
                                        placeholder="Short reason for this resubmission (optional)">{{ old('description', $request->description) }}</textarea>
                                </td>
                            </tr>

                        </table>
                    </div>

                    <a href="{{ route('locum-requests.index') }}" class="btn btn-secondary mt-3 me-2">
                        <i class="fas fa-arrow-left"></i> Back
                    </a>
                    <button type="submit" class="btn btn-primary mt-3" style="background:#459c51;border-color:#459c51;">
                        <i class="fas fa-save"></i> Update & Resubmit
                    </button>
                </form>

                <script>
                    const existingWorkedDays = @json($existingWorkedDays);
                    const holidays = @json($holidays);
                    const selectedYear = parseInt(document.getElementById('locum_year').value || '{{ $currentYear }}', 10);
                    const rate = {{ (float) ($agreement->locum_rate ?? 0) }};

                    function buildMonthTable(monthName, year, bioTimeData) {
                        const validMonths = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September',
                            'October', 'November', 'December'
                        ];
                        const monthIndex = validMonths.indexOf(monthName);
                        const daysInMonth = new Date(year, monthIndex + 1, 0).getDate();
                        const tbody = document.getElementById('days-table-body');

                        tbody.innerHTML = '';
                        let totalBioTimeHours = 0;

                        for (let day = 1; day <= daysInMonth; day++) {
                            const date = new Date(year, monthIndex, day);
                            const dateStr = date.toLocaleString('en-GB', {
                                    day: '2-digit',
                                    month: 'long',
                                    year: 'numeric'
                                })
                                .replace(/(\d+) (\w+) (\d+)/, '$1 $2 $3');
                            const dayName = date.toLocaleString('en-US', {
                                weekday: 'long'
                            });
                            const isWeekend = date.getDay() === 0 || date.getDay() === 6;
                            const isoDate = `${year}-${String(monthIndex+1).padStart(2, '0')}-${String(day).padStart(2,'0')}`;
                            const holiday = holidays[isoDate] || '';
                            const dayType = holiday ? `Holiday (${holiday})` : (isWeekend ? 'Weekend' : 'Normal');

                            const bioTime = (bioTimeData && bioTimeData[isoDate]) ? bioTimeData[isoDate] : {
                                hours: '0',
                                worked: '0'
                            };
                            const bioTimeHours = parseFloat(bioTime.hours) || 0;
                            if (bioTime.worked === '1') totalBioTimeHours += bioTimeHours;

                            const tr = document.createElement('tr');
                            tr.className = holiday ? 'holiday' : (isWeekend ? 'weekend' : '');
                            tr.innerHTML = `
                                <td>${dateStr}</td>
                                <td>${dayName}</td>
                                <td>${dayType}</td>
                                <td>
                                    <input type="checkbox" id="worked-${isoDate}" class="worked-checkbox" value="1">
                                    <input type="hidden" name="worked_days[${dateStr}][worked]" value="0">
                                </td>
                                <td>
                                    <input type="number" id="hours-${isoDate}" name="worked_days[${dateStr}][hours]" class="hours-input" min="0" max="24" step="0.5" disabled>
                                    <div class="invalid-feedback">Please enter valid hours (greater than 0).</div>
                                </td>
                                <td data-biostime-hours="${bioTimeHours}">
                                    ${bioTime.worked === '1' ? `${bioTimeHours.toFixed(2)} hours` : 'Null'}
                                </td>
                            `;
                            tbody.appendChild(tr);

                            const cb = tr.querySelector('.worked-checkbox');
                            const hiddenWorked = tr.querySelector('input[type="hidden"]');
                            const hoursInput = tr.querySelector('.hours-input');

                            // Prefill from previous submission
                            const prev = existingWorkedDays[dateStr] || null;
                            if (prev) {
                                const wasWorked = String(prev.worked ?? '0') === '1';
                                if (wasWorked) {
                                    cb.checked = true;
                                    hoursInput.disabled = false;
                                    const val = prev.hours !== undefined ? parseFloat(prev.hours) : 0;
                                    hoursInput.value = val > 0 ? val : (bioTimeHours > 0 ? bioTimeHours.toFixed(2) : '8');
                                    hiddenWorked.value = '1';
                                }
                            }

                            cb.addEventListener('change', function() {
                                hoursInput.disabled = !this.checked;
                                if (!this.checked) {
                                    hoursInput.value = '';
                                    hoursInput.classList.remove('is-invalid');
                                    hiddenWorked.value = '0';
                                } else {
                                    hiddenWorked.value = '1';
                                    if (!hoursInput.value) {
                                        hoursInput.value = bioTimeHours > 0 ? bioTimeHours.toFixed(2) : '8';
                                    }
                                }
                                updateTotals();
                            });

                            hoursInput.addEventListener('input', function() {
                                if (this.value && parseFloat(this.value) > 0) this.classList.remove('is-invalid');
                                updateTotals();
                            });
                        }

                        document.getElementById('days-selection').style.display = 'block';
                        document.getElementById('total-biostime').textContent = totalBioTimeHours.toFixed(2);
                        updateTotals();
                    }

                    function updateTotals() {
                        const checked = Array.from(document.querySelectorAll('.worked-checkbox:checked'));
                        const totalDays = checked.length;
                        const totalHours = Array.from(document.querySelectorAll('.hours-input'))
                            .reduce((sum, i) => sum + (parseFloat(i.value) || 0), 0);

                        document.getElementById('total-days').textContent = totalDays;
                        document.getElementById('total-hours').textContent = totalHours.toFixed(2);

                        const totalAmount = totalDays * rate;
                        document.getElementById('total_amount_payable').textContent =
                            'TZS ' + totalAmount.toLocaleString('en-US', {
                                minimumFractionDigits: 2,
                                maximumFractionDigits: 2
                            });

                        // Show BioTime diff per row
                        document.querySelectorAll('.days-table tbody tr').forEach(row => {
                            const cb = row.querySelector('.worked-checkbox');
                            const hoursInput = row.querySelector('.hours-input');
                            const isoDate = cb.id.replace('worked-', '');
                            const bioTimeCell = row.cells[5];
                            const bioTimeHours = parseFloat(bioTimeCell.dataset.biostimeHours) || 0;
                            const entered = parseFloat(hoursInput.value) || 0;
                            const diff = entered - bioTimeHours;
                            const prev = bioTimeCell.textContent.includes('hours');
                            bioTimeCell.innerHTML = bioTimeCell.textContent.includes('hours') || bioTimeHours > 0 ?
                                `${bioTimeHours.toFixed(2)} hours${entered>0 && diff!==0 ? '<br><span class="bio-time-diff">(Diff: '+diff.toFixed(2)+' hours)</span>' : ''}` :
                                'Null';
                        });
                    }

                    const monthSelect = document.getElementById('locum_month');

                    function loadMonth() {
                        const month = monthSelect.value;
                        if (!month) return;

                        fetch('{{ route('locum-requests.fetch-biotime') }}', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                                },
                                body: JSON.stringify({
                                    month,
                                    year: selectedYear
                                })
                            })
                            .then(r => r.ok ? r.json() : r.json().then(x => {
                                throw new Error(x.error || 'Failed');
                            }))
                            .then(data => {
                                buildMonthTable(month, selectedYear, data.bioTimeData || {});
                            })
                            .catch(err => {
                                console.error(err);
                                // still build from previous without BioTime if fetch fails
                                buildMonthTable(month, selectedYear, {});
                            });
                    }

                    monthSelect.addEventListener('change', loadMonth);

                    // Initialize on load with the preselected month/year
                    if (monthSelect.value) loadMonth();

                    // Form validation
                    document.getElementById('locum-request-form').addEventListener('submit', function(e) {
                        const month = monthSelect.value;
                        const checked = document.querySelectorAll('.worked-checkbox:checked');
                        const hoursInputs = document.querySelectorAll(
                            '.worked-checkbox:checked + input[type="hidden"] + input.hours-input');

                        let hasErrors = false;
                        if (!month) {
                            monthSelect.classList.add('is-invalid');
                            hasErrors = true;
                        } else {
                            monthSelect.classList.remove('is-invalid');
                        }
                        if (checked.length === 0) {
                            document.getElementById('worked-days-error').style.display = 'block';
                            hasErrors = true;
                        } else {
                            document.getElementById('worked-days-error').style.display = 'none';
                        }
                        hoursInputs.forEach(input => {
                            if (!input.value || parseFloat(input.value) <= 0) {
                                input.classList.add('is-invalid');
                                hasErrors = true;
                            } else {
                                input.classList.remove('is-invalid');
                            }
                        });

                        if (hasErrors) {
                            e.preventDefault();
                            this.scrollIntoView({
                                behavior: 'smooth'
                            });
                        }
                    });
                </script>
                {{-- @else
                    <div class="alert alert-warning">
                        You must have an active locum agreement to edit a request.
                    </div>
                @endif --}}
            </div>
        </div>
    </div>
@endsection
