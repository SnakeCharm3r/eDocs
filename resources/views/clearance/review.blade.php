@extends('layouts.template')

@section('breadcrumb')
    @include('sweetalert::alert')
    @include('includes.loader')
@endsection

@section('content')
    <div class="page-wrapper">
        <div class="content container-fluid">
            <br>
            <!-- Page Header -->
            <div class="page-header">
                <div class="row">
                    <div class="col-sm-12">
                        <div class="page-sub-header">
                            <h3 class="page-title">Clearance Form Review</h3>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-12">
                    <!-- Employee Information Card -->
                    <div class="card shadow-sm mb-4">
                        <div class="card-header bg-white border-bottom">
                            <h5 class="card-title mb-0 section-title">
                                <i class="fas fa-user-circle me-2" style="color: #007A33;"></i>1. Employee Information
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label class="text-muted small">Date of Hire</label>
                                        <p class="mb-0">
                                            {{ isset($formData['date_of_hire']) && !empty($formData['date_of_hire']) ? \Carbon\Carbon::parse($formData['date_of_hire'])->format('d F Y') : 'N/A' }}
                                        </p>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label class="text-muted small">End of Contract</label>
                                        <p class="mb-0">
                                            {{ isset($formData['end_of_contract']) && !empty($formData['end_of_contract']) ? \Carbon\Carbon::parse($formData['end_of_contract'])->format('d F Y') : (isset($formData['last_working_day']) && !empty($formData['last_working_day']) ? \Carbon\Carbon::parse($formData['last_working_day'])->format('d F Y') : 'N/A') }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>


                    @php
                        $isLineManager = Auth::user()->hasRole('line-manager');
                        $isRequestingForStaff = isset($formData['userId']) && $formData['userId'] != Auth::id();
                        // Show Line Manager section in review when line manager is requesting for staff
                        // This allows them to review and confirm the data before submitting
                        $showLineManagerSection = $isLineManager && $isRequestingForStaff;
                    @endphp

                    {{-- @if ($showLineManagerSection)
                        <!-- Line Manager Section - Unified -->
                        <div class="card shadow-sm mb-4">
                            <div class="card-header bg-white border-bottom">
                                <h5 class="card-title mb-0 section-title">
                                    <i class="fas fa-clipboard-list me-2" style="color: #007A33;"></i>Line Manager Review
                                </h5>
                            </div>
                            <div class="card-body">
                                <p class="text-muted mb-3">As a Line Manager, please review and complete the following
                                    information:</p>

                                @php
                                    // Prioritize formData over old() for fresh submissions
                                    $changingRoomKeysValue = isset($formData['changing_room_keys'])
                                        ? $formData['changing_room_keys']
                                        : old('changing_room_keys', 'N/A');
                                    $officeKeysValue = isset($formData['office_keys'])
                                        ? $formData['office_keys']
                                        : old('office_keys', 'N/A');
                                    $mobilePhoneValue = isset($formData['mobile_phone'])
                                        ? $formData['mobile_phone']
                                        : old('mobile_phone', 'N/A');
                                    $cameraValue = isset($formData['camera'])
                                        ? $formData['camera']
                                        : old('camera', 'N/A');
                                    $ccbrtUniformsValue = isset($formData['ccbrt_uniforms'])
                                        ? $formData['ccbrt_uniforms']
                                        : old('ccbrt_uniforms', 'N/A');
                                    $officeCarKeysValue = isset($formData['office_car_keys'])
                                        ? $formData['office_car_keys']
                                        : old('office_car_keys', 'N/A');
                                    $otherItemsValue = isset($formData['other_items'])
                                        ? $formData['other_items']
                                        : old('other_items', '');
                                    $handoverReportValue = isset($formData['handover_report_received'])
                                        ? $formData['handover_report_received']
                                        : old('handover_report_received', 'No');
                                    $workResponsibilitiesValue = isset($formData['work_responsibilities_transferred'])
                                        ? $formData['work_responsibilities_transferred']
                                        : old('work_responsibilities_transferred', 'No');
                                    $projectsTasksValue = isset($formData['projects_tasks_closed'])
                                        ? $formData['projects_tasks_closed']
                                        : old('projects_tasks_closed', 'No');
                                    $lmCommentsValue = isset($formData['line_manager_comments'])
                                        ? $formData['line_manager_comments']
                                        : old('line_manager_comments', '');
                                @endphp

                                <table class="table table-bordered">
                                    <thead>
                                        <tr style="background-color: hsl(86, 43%, 84%);">
                                            <th style="width: 50%; padding: 10px;">Item/Field</th>
                                            <th style="width: 50%; padding: 10px;">Status/Details</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <!-- Items Collected Section -->
                                        <tr>
                                            <td colspan="2"
                                                style="padding: 10px; background-color: #e7f3ff; font-weight: bold;">
                                                Items Collected by Last Day of Work
                                            </td>
                                        </tr>
                                        <tr>
                                            <td style="padding: 10px; background-color: #f8f9fa;"><strong>Changing Room
                                                    Keys</strong></td>
                                            <td style="padding: 10px;">
                                                <select name="changing_room_keys" class="form-control"
                                                    id="changing_room_keys">
                                                    <option value="N/A"
                                                        {{ $changingRoomKeysValue == 'N/A' ? 'selected' : '' }}>N/A</option>
                                                    <option value="Yes"
                                                        {{ $changingRoomKeysValue == 'Yes' ? 'selected' : '' }}>Yes</option>
                                                    <option value="No"
                                                        {{ $changingRoomKeysValue == 'No' ? 'selected' : '' }}>No</option>
                                                </select>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td style="padding: 10px; background-color: #f8f9fa;"><strong>Office
                                                    Keys</strong></td>
                                            <td style="padding: 10px;">
                                                <select name="office_keys" class="form-control" id="office_keys">
                                                    <option value="N/A"
                                                        {{ $officeKeysValue == 'N/A' ? 'selected' : '' }}>N/A</option>
                                                    <option value="Yes"
                                                        {{ $officeKeysValue == 'Yes' ? 'selected' : '' }}>Yes</option>
                                                    <option value="No" {{ $officeKeysValue == 'No' ? 'selected' : '' }}>
                                                        No</option>
                                                </select>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td style="padding: 10px; background-color: #f8f9fa;"><strong>Mobile
                                                    Phone</strong></td>
                                            <td style="padding: 10px;">
                                                <select name="mobile_phone" class="form-control" id="mobile_phone">
                                                    <option value="N/A"
                                                        {{ $mobilePhoneValue == 'N/A' ? 'selected' : '' }}>N/A</option>
                                                    <option value="Yes"
                                                        {{ $mobilePhoneValue == 'Yes' ? 'selected' : '' }}>Yes</option>
                                                    <option value="No"
                                                        {{ $mobilePhoneValue == 'No' ? 'selected' : '' }}>No</option>
                                                </select>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td style="padding: 10px; background-color: #f8f9fa;"><strong>Camera</strong>
                                            </td>
                                            <td style="padding: 10px;">
                                                <select name="camera" class="form-control" id="camera">
                                                    <option value="N/A" {{ $cameraValue == 'N/A' ? 'selected' : '' }}>
                                                        N/A</option>
                                                    <option value="Yes" {{ $cameraValue == 'Yes' ? 'selected' : '' }}>
                                                        Yes</option>
                                                    <option value="No" {{ $cameraValue == 'No' ? 'selected' : '' }}>No
                                                    </option>
                                                </select>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td style="padding: 10px; background-color: #f8f9fa;"><strong>CCBRT
                                                    Uniforms</strong></td>
                                            <td style="padding: 10px;">
                                                <select name="ccbrt_uniforms" class="form-control" id="ccbrt_uniforms">
                                                    <option value="N/A"
                                                        {{ $ccbrtUniformsValue == 'N/A' ? 'selected' : '' }}>N/A</option>
                                                    <option value="Yes"
                                                        {{ $ccbrtUniformsValue == 'Yes' ? 'selected' : '' }}>Yes</option>
                                                    <option value="No"
                                                        {{ $ccbrtUniformsValue == 'No' ? 'selected' : '' }}>No</option>
                                                </select>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td style="padding: 10px; background-color: #f8f9fa;"><strong>Office Car
                                                    Keys</strong></td>
                                            <td style="padding: 10px;">
                                                <select name="office_car_keys" class="form-control" id="office_car_keys">
                                                    <option value="N/A"
                                                        {{ $officeCarKeysValue == 'N/A' ? 'selected' : '' }}>N/A</option>
                                                    <option value="Yes"
                                                        {{ $officeCarKeysValue == 'Yes' ? 'selected' : '' }}>Yes</option>
                                                    <option value="No"
                                                        {{ $officeCarKeysValue == 'No' ? 'selected' : '' }}>No</option>
                                                </select>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td style="padding: 10px; background-color: #f8f9fa;"><strong>Any other CCBRT
                                                    items given (Please specify)</strong></td>
                                            <td style="padding: 10px;">
                                                <input type="text" name="other_items" class="form-control"
                                                    id="other_items" placeholder="Specify other items"
                                                    value="{{ $otherItemsValue }}">
                                            </td>
                                        </tr>

                                        <!-- Review Section -->
                                        <tr>
                                            <td colspan="2"
                                                style="padding: 10px; background-color: #fff3cd; font-weight: bold;">
                                                Review Section
                                            </td>
                                        </tr>
                                        <tr>
                                            <td style="padding: 10px; background-color: #f8f9fa; font-weight: bold;">
                                                Handover Report Received:</td>
                                            <td style="padding: 10px;">
                                                <select name="handover_report_received" class="form-control"
                                                    id="handover_report_received">
                                                    <option value="No"
                                                        {{ $handoverReportValue == 'No' ? 'selected' : '' }}>No</option>
                                                    <option value="Yes"
                                                        {{ $handoverReportValue == 'Yes' ? 'selected' : '' }}>Yes</option>
                                                </select>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td style="padding: 10px; background-color: #f8f9fa; font-weight: bold;">Work
                                                Responsibilities Transferred:</td>
                                            <td style="padding: 10px;">
                                                <select name="work_responsibilities_transferred" class="form-control"
                                                    id="work_responsibilities_transferred">
                                                    <option value="No"
                                                        {{ $workResponsibilitiesValue == 'No' ? 'selected' : '' }}>No
                                                    </option>
                                                    <option value="Yes"
                                                        {{ $workResponsibilitiesValue == 'Yes' ? 'selected' : '' }}>Yes
                                                    </option>
                                                </select>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td style="padding: 10px; background-color: #f8f9fa; font-weight: bold;">
                                                Projects/Tasks Closed:</td>
                                            <td style="padding: 10px;">
                                                <select name="projects_tasks_closed" class="form-control"
                                                    id="projects_tasks_closed">
                                                    <option value="No"
                                                        {{ $projectsTasksValue == 'No' ? 'selected' : '' }}>No</option>
                                                    <option value="Yes"
                                                        {{ $projectsTasksValue == 'Yes' ? 'selected' : '' }}>Yes</option>
                                                </select>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td style="padding: 10px; background-color: #f8f9fa; font-weight: bold;">
                                                Comments:</td>
                                            <td style="padding: 10px;">
                                                <textarea name="line_manager_comments" class="form-control" id="line_manager_comments" rows="3"
                                                    placeholder="Enter any comments or notes">{{ $lmCommentsValue }}</textarea>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @endif --}}


                    <!-- Confirmation Card -->
                    <div class="card shadow-sm mb-4">
                        <div class="card-header bg-white border-bottom">
                            <h5 class="card-title mb-0 section-title">
                                <i class="fas fa-check-circle me-2" style="color: #007A33;"></i>Confirmation
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="form-check mb-3">
                                <input class="form-check-input" type="checkbox" id="confirmSubmission" required
                                    style="width: 20px; height: 20px; margin-top: 0.25rem;">
                                <label class="form-check-label ms-2" for="confirmSubmission" style="font-size: 0.95rem;">
                                    I confirm that the information provided is accurate and complete.
                                    <br>
                                    <small class="text-muted">I understand that after submission, I will need to coordinate
                                        with approvers to complete the clearance process.</small>
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- Error Messages -->
                    @php
                        $hasErrors = $errors->any() || session('error');
                        $errorCount = $errors->count();
                    @endphp
                    @if ($hasErrors)
                        <div class="card shadow-sm mb-4 border-danger" style="border-width: 2px;">
                            <div class="card-body">
                                <div class="alert alert-danger">
                                    <h6 class="alert-heading mb-3">
                                        <i class="fas fa-exclamation-triangle me-2" style="color: #dc3545;"></i>
                                        @if ($errorCount > 0)
                                            Please correct the following {{ $errorCount }} error(s):
                                        @else
                                            Error:
                                        @endif
                                    </h6>
                                    <ul class="mb-0" style="padding-left: 1.5rem;">
                                        @if ($errors->any())
                                            @foreach ($errors->all() as $error)
                                                <li class="mb-2" style="font-weight: 500;">{{ $error }}</li>
                                            @endforeach
                                        @endif
                                        @if (session('error'))
                                            <li class="mb-2" style="font-weight: 500;">{{ session('error') }}</li>
                                        @endif
                                        @if ($errorCount == 0 && !session('error'))
                                            <li class="mb-2" style="font-weight: 500;">An unknown error occurred. Please
                                                try again.</li>
                                        @endif
                                    </ul>
                                </div>
                            </div>
                        </div>
                    @endif

                    <!-- Action Buttons -->
                    <div class="card shadow-sm mb-4">
                        <div class="card-body">
                            <form method="POST" action="{{ route('clearance.store') }}" id="submitForm">
                                @csrf

                                {{-- Use old() helper to get form data from previous request or current formData --}}
                                @php
                                    $userId = old('userId', $formData['userId'] ?? Auth::id());
                                    $dateOfHire = old('date_of_hire', $formData['date_of_hire'] ?? '');
                                    $endOfContract = old(
                                        'end_of_contract',
                                        $formData['end_of_contract'] ?? ($formData['last_working_day'] ?? ''),
                                    );
                                    $exitReason = old('exit_reason', $formData['exit_reason'] ?? []);
                                    $exitExplanation = old('exit_explanation', $formData['exit_explanation'] ?? '');
                                    $suggestions = old('suggestions', $formData['suggestions'] ?? '');
                                    $wouldRecommend = old('would_recommend', $formData['would_recommend'] ?? '');
                                @endphp

                                <input type="hidden" name="userId" value="{{ $userId }}" id="hidden_userId">
                                <input type="hidden" name="date_of_hire" value="{{ $dateOfHire }}"
                                    id="hidden_date_of_hire">
                                <input type="hidden" name="end_of_contract" value="{{ $endOfContract }}"
                                    id="hidden_end_of_contract">

                                @if (is_array($exitReason) && count($exitReason) > 0)
                                    @foreach ($exitReason as $reason)
                                        <input type="hidden" name="exit_reason[]" value="{{ $reason }}">
                                    @endforeach
                                @endif

                                @if (!empty($exitExplanation))
                                    <input type="hidden" name="exit_explanation" value="{{ $exitExplanation }}"
                                        id="hidden_exit_explanation">
                                @endif

                                @if (!empty($suggestions))
                                    <input type="hidden" name="suggestions" value="{{ $suggestions }}"
                                        id="hidden_suggestions">
                                @endif

                                @if (!empty($wouldRecommend))
                                    <input type="hidden" name="would_recommend" value="{{ $wouldRecommend }}"
                                        id="hidden_would_recommend">
                                @endif

                                {{-- Line Manager Section Data - No hidden fields needed, form fields will be submitted directly --}}

                                <div class="d-flex gap-2">
                                    <a href="{{ route('clearance.create') }}" class="btn btn-secondary">
                                        <i class="fas fa-arrow-left me-1"></i> Back to Edit
                                    </a>
                                    <button type="submit" class="btn btn-primary" id="submitBtn" disabled>
                                        <i class="fas fa-paper-plane me-1"></i> Submit Clearance Form
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Enable submit button only when checkbox is checked
        document.getElementById('confirmSubmission').addEventListener('change', function() {
            document.getElementById('submitBtn').disabled = !this.checked;
        });

        // Form submission confirmation and validation
        document.getElementById('submitForm').addEventListener('submit', function(e) {
            if (!document.getElementById('confirmSubmission').checked) {
                e.preventDefault();
                alert('Please confirm that the information is accurate before submitting.');
                return false;
            }

            // Validate hidden inputs before submission
            const userId = document.getElementById('hidden_userId').value;
            const dateOfHire = document.getElementById('hidden_date_of_hire').value;
            const endOfContract = document.getElementById('hidden_end_of_contract').value;

            if (!userId || !dateOfHire || !endOfContract) {
                e.preventDefault();
                alert('Error: Some required fields are missing. Please go back and fill in all required fields.');
                console.error('Missing fields:', {
                    userId: !userId,
                    dateOfHire: !dateOfHire,
                    endOfContract: !endOfContract
                });
                return false;
            }

            // Log form data before submission (for debugging)
            if ({{ config('app.debug') ? 'true' : 'false' }}) {
                console.log('Submitting form with data:', {
                    userId: userId,
                    dateOfHire: dateOfHire,
                    endOfContract: endOfContract
                });
            }
        });
    </script>
@endsection

@section('styles')
    <style>
        .page-wrapper {
            padding: 20px;
        }

        .text-muted {
            font-size: 0.95rem;
            color: #6c757d;
        }

        .card {
            border: none;
            border-radius: 8px;
            background-color: #ffffff;
        }

        .card-body {
            padding: 2rem;
        }

        .section-title {
            font-size: 1.15rem;
            font-weight: 600;
            color: #007A33;
            margin-bottom: 0;
            border-bottom: 2px solid #007A33;
            padding-bottom: 0.5rem;
        }

        .section-title i {
            color: #007A33;
        }

        .card-body h6 {
            font-weight: 500;
        }

        .card-header.bg-white {
            background-color: #ffffff !important;
            border-bottom: 1px solid #e9ecef;
        }

        .list-unstyled li {
            padding: 0.25rem 0;
        }

        .gap-2 {
            gap: 0.5rem;
        }
    </style>
@endsection
