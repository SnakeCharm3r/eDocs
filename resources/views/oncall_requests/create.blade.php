@extends('layouts.template')

@section('breadcrumb')
    @include('sweetalert::alert')
@endsection

@section('content')
    <style>
        .pdf-like-container {
            max-width: 1200px;
            margin: 0 auto;
            font-family: 'Arial', sans-serif;
            font-size: 14px;
            line-height: 1.6;
            border: 1px solid #ddd;
            padding: 20px;
            background: #fff;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }

        .pdf-section {
            margin-bottom: 25px;
        }

        .pdf-section h5 {
            font-size: 16px;
            font-weight: bold;
            margin-bottom: 12px;
            text-transform: uppercase;
            color: #333;
        }

        .request-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
            font-size: 14px;
        }

        .request-table th,
        .request-table td {
            border: 1px solid #000;
            padding: 10px;
            text-align: left;
        }

        .request-table th {
            width: 20%;
            font-weight: bold;
            background: #f8f8f8;
        }

        .days-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        .days-table th,
        .days-table td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: center;
        }

        .days-table th {
            background: #f8f8f8;
        }

        .weekend {
            background: #ffe6e6;
        }

        .hours-input {
            width: 80px;
            text-align: center;
        }

        .worked-checkbox {
            width: 20px;
            height: 20px;
            cursor: pointer;
            vertical-align: middle;
            transform: scale(1.2);
        }

        .worked-checkbox:checked {
            accent-color: #459c51;
        }

        .invalid-feedback {
            font-size: 12px;
            color: #dc3545;
            display: none;
        }

        .is-invalid .invalid-feedback,
        .hours-input.is-invalid+.invalid-feedback,
        .form-control.is-invalid+.invalid-feedback {
            display: block;
        }

        .btn-primary {
            font-size: 14px;
            padding: 8px 16px;
        }
    </style>

    <div class="page-wrapper">
        <div class="content container-fluid">
            <div class="page-header">
                <div class="row">
                    <div class="col-sm-6">
                        <h3 class="page-title">On-Call Claims</h3>
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
                        <strong>Oops! There were some problems:</strong>
                        <ul class="mb-0">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif

                @php
                    /** @var \App\Models\User $user */
                    $user = auth()->user();

                    // Ensure $months and $defaultClaimMonth exist (controller should provide them).
                    // Fallback: build previous month from now.
                    $now = \Carbon\Carbon::now();
                    $fallbackPrev = $now->copy()->subMonthNoOverflow()->format('F Y');

                    $months = isset($months) && is_array($months) && count($months) ? $months : [$fallbackPrev];
                    $defaultClaimMonth = $defaultClaimMonth ?? $fallbackPrev;

                    // Selected value prefers old() after validation, else default previous month
                    $selectedMonth = old('locum_month', $defaultClaimMonth);

                    // onCallRates and educationLevels should be provided by controller
                    $onCallRates = $onCallRates ?? [];
                    $educationLevels = $educationLevels ?? [];
                @endphp

                <form action="{{ route('oncall_requests.store') }}" method="POST" id="oncall-request-form"
                    enctype="multipart/form-data" novalidate>
                    @csrf

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
                                <td>{{ optional($user->department)->dept_name ?? 'N/A' }}</td>
                            </tr>
                            <tr>
                                <th>Select Education Level <span class="text-danger">*</span></th>
                                <td>
                                    <select class="form-control" name="education_level" id="education_level" required>
                                        <option value="" disabled {{ old('education_level') ? '' : 'selected' }}>
                                            Select education level
                                        </option>
                                        @foreach ($educationLevels as $level)
                                            <option value="{{ $level }}"
                                                {{ old('education_level') == $level ? 'selected' : '' }}>
                                                {{ $level }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <div class="invalid-feedback">Please select an education level.</div>
                                </td>
                            </tr>
                            <tr>
                                <th>On-Call Rate</th>
                                <td id="oncall-rate">TZS 0.00</td>
                            </tr>
                        </table>
                    </div>

                    {{-- REQUEST DETAILS --}}
                    <div class="pdf-section">
                        <h5>REQUEST DETAILS</h5>
                        <table class="request-table">
                            <tr>
                                <th>On-Call Month <span class="text-danger">*</span></th>
                                <td>
                                    {{-- Visible but disabled so user sees the locked month --}}
                                    <select class="form-control" id="locum_month_display" disabled aria-disabled="true"
                                        aria-describedby="locum-month-help">
                                        @foreach ($months as $month)
                                            <option value="{{ $month }}"
                                                {{ $selectedMonth === $month ? 'selected' : '' }}>
                                                {{ $month }}
                                            </option>
                                        @endforeach
                                    </select>

                                    {{-- Hidden real input that will be submitted --}}
                                    <input type="hidden" id="locum_month" name="locum_month" value="{{ $selectedMonth }}">


                                    <div class="invalid-feedback">Please select a valid month (e.g., August 2025).</div>
                                </td>
                            </tr>
                            <tr>
                                <th>Select Days Worked <span class="text-danger">*</span></th>
                                <td>
                                    <div id="days-selection" style="display:none;">
                                        <table class="days-table">
                                            <thead>
                                                <tr>
                                                    <th>Date</th>
                                                    <th>Worked</th>
                                                    <th>Hours</th>
                                                </tr>
                                            </thead>
                                            <tbody id="days-table-body"></tbody>
                                        </table>
                                        <div class="totals-box mt-2 p-3 border rounded bg-light">
                                            <p><strong>Number of Days Selected: <span id="number-of-days">0</span></strong>
                                            </p>
                                            <p><strong>Total Hours: <span id="total-hours">0</span></strong></p>
                                            <p><strong>Total Amount Payable: <span id="total_amount_payable">TZS
                                                        0.00</span></strong></p>
                                        </div>
                                    </div>
                                    <div class="invalid-feedback" id="worked-days-error">
                                        Please select at least one day worked and enter hours.
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <th>Routine</th>
                                <td>
                                    <div class="d-flex gap-3 align-items-center">
                                        <label class="me-3">
                                            <input type="radio" name="has_special_task" value="0"
                                                {{ old('has_special_task', '0') == '0' ? 'checked' : '' }}>
                                            Yes
                                        </label>
                                        <label>
                                            <input type="radio" name="has_special_task" value="1"
                                                {{ old('has_special_task') == '1' ? 'checked' : '' }}>
                                            No
                                        </label>
                                    </div>

                                    <div id="special-task-upload" class="mt-3" style="display:none;">
                                        <label class="form-label">Supportive Document <span
                                                class="text-danger">*</span></label>
                                        <input type="file" name="special_task_document" id="special_task_document"
                                            class="form-control" accept=".pdf,.doc,.docx,.png,.jpg,.jpeg">
                                        <small class="text-muted d-block mt-1">Allowed: PDF, DOC, DOCX, PNG, JPG (max
                                            3MB).</small>
                                        @error('special_task_document')
                                            <div class="invalid-feedback d-block">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    @error('has_special_task')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                </td>
                            </tr>
                            <tr>
                                <th>Description</th>
                                <td>
                                    <textarea class="form-control" name="description" rows="4">{{ old('description') }}</textarea>
                                </td>
                            </tr>
                        </table>
                    </div>

                    <a href="{{ route('oncall_requests.index') }}" class="btn btn-secondary mt-3 me-2">
                        <i class="fas fa-arrow-left"></i> Back
                    </a>
                    <button type="submit" id="submit-btn" class="btn btn-primary mt-3"
                        style="background-color:#459c51;border-color:#459c51;">
                        <span class="btn-content">
                            <i class="fas fa-save"></i> Submit Request
                        </span>
                        <span class="btn-loading d-none">
                            <span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                            Submitting...
                        </span>
                    </button>
                </form>
            </div>
        </div>
    </div>

    <script>
        const onCallRates = @json($onCallRates);

        // Debounce utility
        function debounce(func, wait) {
            let t;
            return function(...args) {
                clearTimeout(t);
                t = setTimeout(() => func.apply(this, args), wait);
            };
        }

        function generateDays(monthYear) {
            const [month, year] = monthYear.split(' ');
            if (!month || !year) return;

            const monthIndex = new Date(Date.parse(month + " 1, " + year)).getMonth();
            const daysInMonth = new Date(year, monthIndex + 1, 0).getDate();
            const tbody = document.getElementById('days-table-body');
            tbody.innerHTML = '';

            for (let day = 1; day <= daysInMonth; day++) {
                const date = new Date(year, monthIndex, day);
                const iso = `${year}-${String(monthIndex + 1).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
                const dayName = date.toLocaleString('en-US', {
                    weekday: 'long'
                });
                // Show only day number and weekday (e.g., "2 Monday")
                const dateDisplay = `${day} ${dayName}`;

                const row = document.createElement('tr');
                row.className = (date.getDay() === 0 || date.getDay() === 6) ? 'weekend' : '';
                row.innerHTML = `
                    <td style="text-align:left;padding-left:12px">${dateDisplay}</td>
                    <td>
                        <input type="checkbox" class="worked-checkbox" data-date="${iso}" value="1">
                        <input type="hidden" name="worked_days[${iso}][worked]" value="0">
                    </td>
                    <td>
                        <input type="number" id="hours-${iso}" name="worked_days[${iso}][hours]" class="hours-input" min="0" max="24" step="0.5" disabled>
                        <div class="invalid-feedback">Please enter valid hours (greater than 0).</div>
                    </td>
                `;
                tbody.appendChild(row);

                const checkbox = row.querySelector('.worked-checkbox');
                const hoursInput = row.querySelector('.hours-input');

                checkbox.addEventListener('change', () => {
                    hoursInput.disabled = !checkbox.checked;
                    if (!checkbox.checked) {
                        hoursInput.value = '';
                        checkbox.nextElementSibling.value = '0';
                        hoursInput.classList.remove('is-invalid');
                    } else {
                        checkbox.nextElementSibling.value = '1';
                        hoursInput.value = hoursInput.value || '8';
                    }
                    debouncedUpdateTotals();
                });

                hoursInput.addEventListener('input', () => {
                    const v = parseFloat(hoursInput.value) || 0;
                    if (v > 0) hoursInput.classList.remove('is-invalid');
                    debouncedUpdateTotals();
                });
            }

            document.getElementById('days-selection').style.display = 'block';
            debouncedUpdateTotals();
        }

        function updateTotals() {
            const checkboxes = document.querySelectorAll('.worked-checkbox:checked');
            let numberOfDays = 0;
            let totalHours = 0;
            let valid = true;

            checkboxes.forEach(cb => {
                const iso = cb.dataset.date;
                const hoursInput = document.getElementById(`hours-${iso}`);
                const hours = parseFloat(hoursInput.value) || 0;
                if (hours <= 0) {
                    hoursInput.classList.add('is-invalid');
                    valid = false;
                } else {
                    hoursInput.classList.remove('is-invalid');
                    totalHours += hours;
                    numberOfDays++;
                }
            });

            document.getElementById('number-of-days').textContent = numberOfDays;
            document.getElementById('total-hours').textContent = totalHours.toFixed(2);

            const educationLevel = document.getElementById('education_level').value;
            const rate = onCallRates[educationLevel] || 0;
            const totalAmount = numberOfDays * rate;

            document.getElementById('total_amount_payable').textContent =
                'TZS ' + totalAmount.toLocaleString('en-US', {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                });

            document.getElementById('worked-days-error').style.display = (numberOfDays === 0 || !valid) ? 'block' : 'none';
            return {
                numberOfDays,
                totalHours,
                valid
            };
        }
        const debouncedUpdateTotals = debounce(updateTotals, 250);

        // Events
        const educationEl = document.getElementById('education_level');
        if (educationEl) {
            educationEl.addEventListener('change', function() {
                const rate = onCallRates[this.value] || 0;
                document.getElementById('oncall-rate').textContent =
                    'TZS ' + rate.toLocaleString('en-US', {
                        minimumFractionDigits: 2,
                        maximumFractionDigits: 2
                    });
                debouncedUpdateTotals();
            });
        }

        // Form validation & submit
        (function() {
            const form = document.getElementById('oncall-request-form');
            if (!form) return;

            form.addEventListener('submit', function(e) {
                // Basic front-end validation similar to earlier logic
                const educationLevel = document.getElementById('education_level');
                const locumMonth = document.getElementById('locum_month');
                const checked = document.querySelectorAll('.worked-checkbox:checked');

                let isValid = true;

                if (!educationLevel || !educationLevel.value) {
                    educationLevel.classList.add('is-invalid');
                    isValid = false;
                } else {
                    educationLevel.classList.remove('is-invalid');
                }

                if (!locumMonth || !locumMonth.value) {
                    // should not happen because hidden input is set, but guard anyway
                    isValid = false;
                }

                let daysOK = checked.length > 0;
                checked.forEach(cb => {
                    const iso = cb.dataset.date;
                    const hoursInput = document.getElementById(`hours-${iso}`);
                    const v = parseFloat(hoursInput.value) || 0;
                    if (v <= 0) {
                        hoursInput.classList.add('is-invalid');
                        isValid = false;
                        daysOK = false;
                    } else {
                        hoursInput.classList.remove('is-invalid');
                    }
                });

                document.getElementById('worked-days-error').style.display = daysOK ? 'none' : 'block';

                // Special task file check
                const wantsSpecial = document.querySelector('input[name="has_special_task"][value="1"]')
                    ?.checked;
                const file = document.getElementById('special_task_document');
                if (wantsSpecial) {
                    if (!file || !file.files || file.files.length === 0) {
                        isValid = false;
                        if (file) file.classList.add('is-invalid');
                        if (file) file.scrollIntoView({
                            behavior: 'smooth',
                            block: 'center'
                        });
                    } else {
                        file.classList.remove('is-invalid');
                    }
                }

                if (!isValid || !daysOK) {
                    e.preventDefault();
                    window.scrollTo({
                        top: 0,
                        behavior: 'smooth'
                    });
                    return;
                }

                // Show loading state
                const submitBtn = document.getElementById('submit-btn');
                if (submitBtn) {
                    submitBtn.disabled = true;
                    submitBtn.querySelector('.btn-content').classList.add('d-none');
                    submitBtn.querySelector('.btn-loading').classList.remove('d-none');
                }

                // else let it submit (server will still validate)
            });
        })();

        // Special Task toggle
        function toggleSpecialTaskUpload() {
            const yes = document.querySelector('input[name="has_special_task"][value="1"]')?.checked;
            const holder = document.getElementById('special-task-upload');
            const file = document.getElementById('special_task_document');
            if (holder) holder.style.display = yes ? 'block' : 'none';

            if (!yes && file) {
                file.value = '';
                file.classList.remove('is-invalid');
            }
        }

        document.querySelectorAll('input[name="has_special_task"]').forEach(r => r.addEventListener('change',
            toggleSpecialTaskUpload));

        // Initialize on load: set rate, toggle special, generate days for hidden month
        window.addEventListener('load', function() {
            const el = document.getElementById('education_level');
            if (el && el.value) el.dispatchEvent(new Event('change'));

            // Sync visible select with hidden real value (safety)
            const lmHidden = document.getElementById('locum_month'); // hidden input (value submitted)
            const displaySelect = document.getElementById('locum_month_display'); // visible disabled select
            if (displaySelect && lmHidden && lmHidden.value) {
                for (const opt of displaySelect.options) {
                    if (opt.value === lmHidden.value) {
                        opt.selected = true;
                        break;
                    }
                }
            }

            // Generate days based on the hidden value (previous month)
            if (lmHidden && lmHidden.value) {
                generateDays(lmHidden.value);
            }

            toggleSpecialTaskUpload();
        });
    </script>
@endsection
