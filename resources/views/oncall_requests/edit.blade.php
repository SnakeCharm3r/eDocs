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

        .is-invalid .invalid-feedback {
            display: block;
        }

        .btn-primary {
            font-size: 14px;
            padding: 8px 16px;
        }

        .doc-preview {
            border: 1px solid #e5e7eb;
            border-radius: .5rem;
            overflow: hidden;
            background: #fff;
        }

        .doc-preview .doc-embed {
            width: 100%;
            height: 78vh;
            display: block;
            border: 0;
        }

        .doc-preview .doc-image {
            max-width: 100%;
            height: auto;
            display: block;
            margin: 0 auto;
        }

        @media (max-width: 768px) {
            .doc-preview .doc-embed {
                height: 65vh;
            }
        }
    </style>

    <div class="page-wrapper">
        <div class="content container-fluid">
            <div class="page-header">
                <div class="row">
                    <div class="col-sm-6">
                        <h3 class="page-title">Edit On-Call Claim</h3>
                        <small class="text-muted">Update and resubmit your rejected request.</small>
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
                    $user = auth()->user();
                    // Fallbacks
                    $selectedMonth =
                        $selectedMonth ??
                        old('locum_month', \Carbon\Carbon::now()->subMonthNoOverflow()->format('F Y'));
                    $existingDays = is_array($existingDays ?? null) ? $existingDays : [];
                @endphp

                <form action="{{ route('oncall_requests.update', $onCallRequest) }}" method="POST" id="oncall-request-form"
                    enctype="multipart/form-data" novalidate> @csrf
                    @method('PUT')

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
                                <th>Education Level <span class="text-danger">*</span></th>
                                <td>
                                    <select class="form-control" name="education_level" id="education_level" required>
                                        <option value="" disabled
                                            {{ old('education_level', $onCallRequest->education_level) ? '' : 'selected' }}>
                                            Select Education Level
                                        </option>
                                        @foreach ($educationLevels as $level)
                                            <option value="{{ $level }}"
                                                {{ old('education_level', $onCallRequest->education_level) == $level ? 'selected' : '' }}>
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
                                    <select class="form-control" id="locum_month" name="locum_month" required>
                                        <option value="" disabled {{ $selectedMonth ? '' : 'selected' }}>Select Month
                                        </option>
                                        @foreach ($months as $month)
                                            <option value="{{ $month }}"
                                                {{ $selectedMonth === $month ? 'selected' : '' }}>
                                                {{ $month }}
                                            </option>
                                        @endforeach
                                    </select>
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
                                                    <th>Day</th>
                                                    <th>Type</th>
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
                                <th>Routine?</th>
                                <td>
                                    @php
                                        $oldHasSpecial = old(
                                            'has_special_task',
                                            (string) ($onCallRequest->has_special_task ? 1 : 0),
                                        );
                                        $hasFile = !empty($onCallRequest->special_task_path);
                                        $relativePath = $hasFile ? $onCallRequest->special_task_path : null;
                                        $docUrl = $relativePath ? asset('storage/' . ltrim($relativePath, '/')) : null;
                                        $name =
                                            $onCallRequest->special_task_original_name ?:
                                            ($relativePath
                                                ? basename($relativePath)
                                                : null);
                                        $sizeKb = $onCallRequest->special_task_size
                                            ? number_format($onCallRequest->special_task_size / 1024, 1) . ' KB'
                                            : null;
                                        $ext = $name ? strtolower(pathinfo($name, PATHINFO_EXTENSION)) : null;
                                        $collapseId = 'specialDocPreview-' . $onCallRequest->id;
                                    @endphp

                                    <div class="d-flex flex-wrap align-items-center gap-3">
                                        <label class="me-2">
                                            <input type="radio" name="has_special_task" value="0"
                                                {{ $oldHasSpecial === '0' ? 'checked' : '' }}>
                                            Yes
                                        </label>
                                        <label>
                                            <input type="radio" name="has_special_task" value="1"
                                                {{ $oldHasSpecial === '1' ? 'checked' : '' }}>
                                            No
                                        </label>

                                        @error('has_special_task')
                                            <span class="invalid-feedback d-block ms-2">{{ $message }}</span>
                                        @enderror
                                    </div>

                                    <div id="special-task-upload" class="mt-3" style="display:none;">
                                        <label class="form-label mb-1">
                                            Supportive Document
                                            <span class="text-danger" id="special-required-star"
                                                style="display:none;">*</span>
                                        </label>
                                        <input type="file" name="special_task_document" id="special_task_document"
                                            class="form-control" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png">
                                        <small class="text-muted">
                                            Allowed: PDF, DOC, DOCX, JPG, JPEG, PNG (max 10MB).
                                            @if ($hasFile)
                                                Leave empty to keep the current file.
                                            @endif
                                        </small>
                                        @error('special_task_document')
                                            <div class="invalid-feedback d-block">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    {{-- Existing file (no auto-preview) --}}
                                    @if ($hasFile)
                                        <div class="mt-3 p-2 bg-light border rounded">
                                            <div class="d-flex flex-wrap align-items-center gap-2">
                                                <div class="me-auto">
                                                    <i class="fas fa-paperclip me-1"></i>
                                                    <span class="fw-semibold">{{ $name }}</span>
                                                    @if ($sizeKb)
                                                        <span class="text-muted">({{ $sizeKb }})</span>
                                                    @endif
                                                </div>

                                                {{-- Open in a new tab --}}
                                                <a href="{{ $docUrl }}" target="_blank" rel="noopener"
                                                    class="btn btn-sm btn-outline-primary">
                                                    View
                                                </a>

                                                {{-- Inline preview on demand (lazy) --}}
                                                <button class="btn btn-sm btn-outline-secondary" type="button"
                                                    data-preview-target="#{{ $collapseId }}"
                                                    data-doc-url="{{ $docUrl }}"
                                                    data-doc-type="{{ $ext }}">
                                                    Preview here
                                                </button>
                                            </div>

                                            <div class="collapse mt-2" id="{{ $collapseId }}">
                                                <div class="doc-preview card card-body p-0">
                                                    @if ($ext === 'pdf')
                                                        <embed class="doc-embed" src="" type="application/pdf">
                                                    @elseif(in_array($ext, ['jpg', 'jpeg', 'png']))
                                                        <img class="doc-image" src="" alt="Supportive Document">
                                                    @else
                                                        <div class="p-3 small text-muted">
                                                            Preview not available for <code>.{{ $ext }}</code>.
                                                            Use <em>View</em>.
                                                        </div>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    @endif
                                </td>
                            </tr>

                            <tr>
                                <th>Description</th>
                                <td>
                                    <textarea class="form-control" name="description" rows="4">{{ old('description', $onCallRequest->description) }}</textarea>
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
                            <i class="fas fa-save"></i> Update & Resubmit
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
        const existingDays = @json($existingDays);

        function debounce(fn, wait) {
            let t;
            return (...a) => {
                clearTimeout(t);
                t = setTimeout(() => fn(...a), wait);
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
                const iso = `${year}-${String(monthIndex+1).padStart(2,'0')}-${String(day).padStart(2,'0')}`;
                const dateStr = `${day} ${month} ${year}`;
                const dayName = date.toLocaleString('en-US', {
                    weekday: 'long'
                });
                const isWeekend = date.getDay() === 0 || date.getDay() === 6;

                const prev = existingDays[iso] || null; // if existed previously

                const row = document.createElement('tr');
                row.className = isWeekend ? 'weekend' : '';
                row.innerHTML = `
                    <td>${dateStr}</td>
                    <td>${dayName}</td>
                    <td>${isWeekend ? 'Weekend' : 'Normal'}</td>
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

                // prefill from previous submission
                if (prev && Number(prev.worked) === 1 && Number(prev.hours) > 0) {
                    checkbox.checked = true;
                    checkbox.nextElementSibling.value = '1';
                    hoursInput.disabled = false;
                    hoursInput.value = Number(prev.hours).toFixed(2).replace(/\.00$/, '');
                }

                checkbox.addEventListener('change', () => {
                    hoursInput.disabled = !checkbox.checked;
                    if (!checkbox.checked) {
                        hoursInput.value = '';
                        checkbox.nextElementSibling.value = '0';
                        hoursInput.classList.remove('is-invalid');
                    } else {
                        checkbox.nextElementSibling.value = '1';
                        if (!hoursInput.value) hoursInput.value = '8';
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
            let numberOfDays = 0,
                totalHours = 0,
                valid = true;

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

            document.getElementById('worked-days-error').style.display = numberOfDays === 0 || !valid ? 'block' : 'none';
        }
        const debouncedUpdateTotals = debounce(updateTotals, 250);

        // Events
        document.getElementById('education_level').addEventListener('change', function() {
            const rate = onCallRates[this.value] || 0;
            document.getElementById('oncall-rate').textContent =
                'TZS ' + rate.toLocaleString('en-US', {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                });
            debouncedUpdateTotals();
        });

        document.getElementById('locum_month').addEventListener('change', function() {
            const valid =
                /^(January|February|March|April|May|June|July|August|September|October|November|December) \d{4}$/
                .test(this.value);
            if (!valid) {
                this.classList.add('is-invalid');
                document.getElementById('days-selection').style.display = 'none';
                return;
            }
            this.classList.remove('is-invalid');
            generateDays(this.value);
        });

        document.getElementById('oncall-request-form').addEventListener('submit', function(e) {
            e.preventDefault();

            const educationLevel = document.getElementById('education_level');
            const locumMonth = document.getElementById('locum_month');
            const checked = document.querySelectorAll('.worked-checkbox:checked');

            let isValid = true;

            if (!educationLevel.value) {
                educationLevel.classList.add('is-invalid');
                isValid = false;
            } else {
                educationLevel.classList.remove('is-invalid');
            }

            if (!locumMonth.value || !
                /^(January|February|March|April|May|June|July|August|September|October|November|December) \d{4}$/
                .test(locumMonth.value)) {
                locumMonth.classList.add('is-invalid');
                isValid = false;
            } else {
                locumMonth.classList.remove('is-invalid');
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

            if (isValid && daysOK) {
                // Show loading state
                const submitBtn = document.getElementById('submit-btn');
                if (submitBtn) {
                    submitBtn.disabled = true;
                    submitBtn.querySelector('.btn-content').classList.add('d-none');
                    submitBtn.querySelector('.btn-loading').classList.remove('d-none');
                }
                this.submit();
            }
        });

        // Prefill on load
        window.addEventListener('load', function() {
            const el = document.getElementById('education_level');
            if (el.value) el.dispatchEvent(new Event('change'));
            const lm = document.getElementById('locum_month');
            if (lm.value) lm.dispatchEvent(new Event('change'));
        });
        // Show/hide upload area based on Yes/No and toggle the required star
        function toggleSpecialTaskUpload() {
            const yes = document.querySelector('input[name="has_special_task"][value="1"]').checked;
            const holder = document.getElementById('special-task-upload');
            const star = document.getElementById('special-required-star');
            holder.style.display = yes ? 'block' : 'none';
            if (star) star.style.display = yes ? 'inline' : 'none';
            if (!yes) {
                const file = document.getElementById('special_task_document');
                if (file) {
                    file.value = '';
                    file.classList.remove('is-invalid');
                }
            }
        }
        document.querySelectorAll('input[name="has_special_task"]').forEach(r =>
            r.addEventListener('change', toggleSpecialTaskUpload)
        );
        window.addEventListener('load', toggleSpecialTaskUpload);

        // Click-to-load inline preview (no auto-preview)
        document.addEventListener('click', function(e) {
            const btn = e.target.closest('[data-preview-target]');
            if (!btn) return;

            const targetSel = btn.getAttribute('data-preview-target');
            const url = btn.getAttribute('data-doc-url');
            const type = (btn.getAttribute('data-doc-type') || '').toLowerCase();
            const panel = document.querySelector(targetSel);
            if (!panel) return;

            // Open collapse (with fallback if Bootstrap isn't present)
            if (window.bootstrap && bootstrap.Collapse) {
                bootstrap.Collapse.getOrCreateInstance(panel).show();
            } else {
                panel.classList.add('show');
                panel.style.height = 'auto';
            }

            // Lazy-load content once
            if (type === 'pdf') {
                const emb = panel.querySelector('.doc-embed');
                if (emb && !emb.getAttribute('src')) emb.setAttribute('src', url +
                    '#toolbar=1&navpanes=0&scrollbar=1');
            } else if (['jpg', 'jpeg', 'png'].includes(type)) {
                const img = panel.querySelector('.doc-image');
                if (img && !img.getAttribute('src')) img.setAttribute('src', url);
            }
        });

        // Optional: free memory when closed (only if Bootstrap present)
        document.addEventListener('hidden.bs.collapse', function(ev) {
            if (!String(ev.target.id).startsWith('specialDocPreview-')) return;
            const emb = ev.target.querySelector('.doc-embed');
            if (emb) emb.setAttribute('src', '');
            const img = ev.target.querySelector('.doc-image');
            if (img) img.setAttribute('src', '');
        });
    </script>
@endsection
