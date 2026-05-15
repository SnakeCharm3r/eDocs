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
            padding: 30px;
            background: #fff;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            border-radius: 8px;
        }

        .pdf-header {
            margin-bottom: 20px;
        }

        .pdf-header h3 {
            font-size: 24px;
            font-weight: bold;
            color: #459c51;
            margin: 0;
        }

        .pdf-header p {
            font-style: italic;
            color: #6c757d;
            margin: 0;
        }

        .pdf-section {
            margin-bottom: 30px;
        }

        .pdf-section h5 {
            font-size: 16px;
            font-weight: 600;
            text-transform: uppercase;
            color: #333;
            margin-bottom: 15px;
            border-bottom: 2px solid #459c51;
            padding-bottom: 5px;
        }

        .pdf-section p {
            margin-bottom: 12px;
            color: #444;
        }

        .pdf-section ol {
            padding-left: 20px;
            margin-bottom: 0;
        }

        .pdf-section ol li {
            margin-bottom: 10px;
            color: #444;
        }

        .claim-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
            font-size: 14px;
        }

        .claim-table th,
        .claim-table td {
            border: 1px solid #ddd;
            padding: 12px;
            text-align: left;
        }

        .claim-table th {
            width: 30%;
            font-weight: 600;
            background: #f8f8f8;
            color: #333;
        }

        .claim-table td {
            background: #fff;
        }

        .form-control {
            font-size: 14px;
            border-radius: 4px;
            padding: 8px;
        }

        .invalid-feedback {
            font-size: 12px;
            color: #dc3545;
        }

        .alert {
            font-size: 14px;
            border-radius: 4px;
            margin-bottom: 20px;
        }

        .btn-primary,
        .btn-secondary {
            font-size: 14px;
            padding: 10px 20px;
            border-radius: 4px;
            transition: background-color 0.3s;
        }

        .btn-primary {
            background-color: #459c51;
            border-color: #459c51;
        }

        .btn-primary:hover {
            background-color: #3a8043;
            border-color: #3a8043;
        }

        .btn-secondary {
            background-color: #6c757d;
            border-color: #6c757d;
        }

        .btn-secondary:hover {
            background-color: #5a6268;
            border-color: #5a6268;
        }

        .button-group {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 20px;
        }
    </style>

    <div class="page-wrapper">
        <div class="content container-fluid">
            <!-- PDF-Like Content -->
            <div class="pdf-like-container">
                <!-- Custom Header -->
                <div class="pdf-header text-center border-bottom pb-3 mb-4">
                    <div class="d-flex flex-column align-items-center">
                        <img src="{{ asset('assets/img/ccbrt.jpg') }}" alt="CCBRT Stamp" class="img-fluid"
                            style="max-width: 80px;">
                        <div class="mt-3">
                            <h3>Edit & Resubmit Locum Agreement</h3>
                            <p>For CCBRT Employees</p>
                        </div>
                    </div>
                </div>

                <!-- Alerts -->
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
                        <ul>
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif

                @if (in_array((int) ($agreement->status ?? 0), [3, 4]) && !empty($agreement->rejection_status))
                    <div class="alert alert-warning">
                        <strong>Rejection Reason:</strong> {{ $agreement->rejection_status }}
                    </div>
                @endif

                <form action="{{ route('locum-agreements.update', $agreement->id) }}" method="POST">
                    @csrf
                    @method('PUT')

                    <!-- Preamble (show this agreement's contract dates; on resubmit backend will set new period) -->
                    <div class="pdf-section">
                        <h5>Preamble</h5>
                        @php
                            $editStart = $agreement->start_date
                                ? \Carbon\Carbon::parse($agreement->start_date)
                                : \Carbon\Carbon::parse($agreement->created_at);
                            $editEnd = $agreement->end_date
                                ? \Carbon\Carbon::parse($agreement->end_date)
                                : \Carbon\Carbon::parse($agreement->created_at)->addYear();
                        @endphp
                        <p>This document, dated <strong>{{ $editStart->format('j F Y') }}</strong>, serves as a special
                            agreement
                            in
                            addition to the existing 'Contract of Employment' between <strong>CCBRT (Comprehensive Community
                                Based Rehabilitation in Tanzania)</strong>, P.O. Box 23310, Dar es Salaam, and
                            <strong>{{ auth()->user()->fname ?? 'N/A' }} {{ auth()->user()->lname ?? '' }}
                            </strong> residing in Dar es Salaam, hereinafter
                            called the EMPLOYEE.
                        </p>
                        <p>
                            The EMPLOYEE voluntarily agrees to enter into this agreement for LOCUM work from
                            <strong>{{ $editStart->format('j F Y') }}</strong>
                            and this agreement will be valid until
                            <strong>{{ $editEnd->format('j F Y') }}</strong>.
                        </p>
                    </div>

                    <!-- Output Criteria -->
                    <div class="pdf-section">
                        <h5>Output Criteria</h5>
                        <ol>
                            <li>The Employee contributes positively to service provision of the respective
                                department and performs all required duties as per job/task description provided or
                                additional tasks as demanded in the shift.</li>
                            <li>The Employee will perform duties in their technical capacity.</li>
                            <li>The Employee meets all registration requirements and documentation standards as per
                                CCBRT guidelines and holds a valid license from the respective body (if
                                applicable).</li>
                        </ol>
                    </div>

                    <!-- Compensation & Payments -->
                    <div class="pdf-section">
                        <h5>Compensation & Payments</h5>
                        <ol>
                            <li>The locum provision serves as full compensation for the output provided and related
                                transport.</li>
                            <li>The applicable locum rate is based on education level as per CCBRT guidelines.</li>
                            <li>Daily claims must be signed by both Employee and Supervisor.</li>
                            <li>Daily claims are processed to the payroll desk for payment through the payroll.</li>
                            <li>Claims received before the 10th of the next month are processed in that month's
                                payroll. Late submissions are processed the following month.</li>
                            <li>All locum payments are subject to statutory deductions.</li>
                        </ol>
                    </div>

                    <!-- Claim -->
                    <div class="pdf-section">
                        <h5>Claim</h5>
                        <table class="claim-table">
                            <tr>
                                <th>Start Date <span class="text-danger">*</span></th>
                                <td>
                                    <input type="date"
                                        class="form-control {{ $errors->has('start_date') ? 'is-invalid' : '' }}"
                                        name="start_date"
                                        value="{{ old('start_date', $agreement->start_date ? \Carbon\Carbon::parse($agreement->start_date)->format('Y-m-d') : '') }}"
                                        required>
                                    @error('start_date')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </td>
                            </tr>
                            <tr>
                                <th>End Date <span class="text-danger">*</span></th>
                                <td>
                                    <input type="date"
                                        class="form-control {{ $errors->has('end_date') ? 'is-invalid' : '' }}"
                                        name="end_date"
                                        value="{{ old('end_date', $agreement->end_date ? \Carbon\Carbon::parse($agreement->end_date)->format('Y-m-d') : '') }}"
                                        required>
                                    @error('end_date')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </td>
                            </tr>
                            <tr>
                                <th>Education Level <span class="text-danger">*</span></th>
                                <td>
                                    <select class="form-control {{ $errors->has('education_level') ? 'is-invalid' : '' }}"
                                        id="education_level" name="education_level" required>
                                        <option value="" disabled>Select Education Level</option>
                                        @foreach ($locumRates as $rate)
                                            <option value="{{ $rate->education_level }}"
                                                {{ old('education_level', $agreement->education_level) == $rate->education_level ? 'selected' : '' }}
                                                data-rate="{{ $rate->rate }}">
                                                {{ $rate->education_level }}</option>
                                        @endforeach
                                    </select>
                                    @error('education_level')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </td>
                            </tr>
                            <tr>
                                <th>Locum Rate <span class="text-danger">*</span></th>
                                <td>
                                    <input type="number"
                                        class="form-control {{ $errors->has('locum_rate') ? 'is-invalid' : '' }}"
                                        id="locum_rate" name="locum_rate"
                                        value="{{ old('locum_rate', $agreement->locum_rate) }}" readonly required>
                                    @error('locum_rate')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </td>
                            </tr>
                        </table>
                    </div>

                    <!-- Buttons -->
                    <div class="button-group d-flex justify-content-start">
                        <a href="{{ route('locum-agreements.view') }}" class="btn btn-secondary me-2">
                            <i class="fas fa-arrow-left"></i> Back
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Update & Resubmit Agreement
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        document.getElementById('education_level').addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            const rate = selectedOption.getAttribute('data-rate') || 0;
            const locumRateInput = document.getElementById('locum_rate');
            locumRateInput.value = rate;
        });

        // Trigger change event on page load if a value is selected
        const educationLevelSelect = document.getElementById('education_level');
        if (educationLevelSelect.value) {
            educationLevelSelect.dispatchEvent(new Event('change'));
        }
    </script>
@endsection
