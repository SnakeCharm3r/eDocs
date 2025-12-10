@extends('layouts.template2')
@section('breadcrumb')
    <div class="content container-fluid" style="background-color: #eff8f3;">
        <div class="page-header">
            <div class="row">
                <div class="col-sm-12">
                    <div class="page-sub-header d-flex justify-content-between align-items-center">
                        <h3 class="page-title mb-0">
                            <i class="fas fa-exclamation-triangle me-2"></i>Conflict of Interest - {{ $user->fname }} {{ $user->lname }}
                        </h3>
                        <a href="{{ route('employees_details.show', $user->id) }}" class="btn btn-secondary">
                            <i class="fas fa-arrow-left me-2"></i>Back to Employee Details
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <div class="container mt-5">
            <div class="alert alert-info mb-4">
                <i class="fas fa-info-circle me-2"></i>
                <strong>HR Mode:</strong> You are filling conflict of interest details for <strong>{{ $user->fname }} {{ $user->lname }}</strong>.
                <br><small>Please answer all questions truthfully. All fields marked with <span class="text-danger">*</span> are required.</small>
            </div>

            <!-- Check for success or error messages -->
            @if (session('success'))
                <div class="alert alert-success alert-dismissible fade show">
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            @if (session('error'))
                <div class="alert alert-danger alert-dismissible fade show">
                    {{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            <!-- Display validation errors -->
            @if ($errors->any())
                <div class="alert alert-danger alert-dismissible fade show">
                    <strong>Please fix the following errors:</strong>
                    <ul class="mb-0 mt-2">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            <div class="row">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-body">
                            <form action="{{ route('hr.employee.save-conflict-interest', $user->id) }}" method="POST" id="conflictForm">
                                @csrf

                                <table class="table table-bordered">
                                    <tbody>
                                        <!-- Question 1 -->
                                        <tr>
                                            <td class="col-md-8">
                                                <strong>1.</strong> Are you or a member of your immediate family an officer,
                                                director, trustee, partner, employee, or regularly retained consultant
                                                of any company that presently has business dealings with CCBRT?
                                                <span class="text-danger">*</span>
                                            </td>
                                            <td class="col-md-4">
                                                <select name="conflict_officer_role" id="conflict_officer_role"
                                                    class="form-control @error('conflict_officer_role') is-invalid @enderror" 
                                                    required onchange="toggleQuestion(1)">
                                                    <option value="" disabled selected>Select</option>
                                                    <option value="Yes"
                                                        {{ old('conflict_officer_role', $user->conflict_officer_role) == 'Yes' ? 'selected' : '' }}>
                                                        Yes</option>
                                                    <option value="No"
                                                        {{ old('conflict_officer_role', $user->conflict_officer_role) == 'No' ? 'selected' : '' }}>
                                                        No</option>
                                                </select>
                                                @error('conflict_officer_role')
                                                    <span class="invalid-feedback" role="alert">
                                                        <strong>{{ $message }}</strong>
                                                    </span>
                                                @enderror
                                            </td>
                                        </tr>

                                        <tr id="question1_details" class="hidden">
                                            <td colspan="2">
                                                <div class="form-group">
                                                    <label class="mb-2">If yes, please list the company, position held, and nature of the business:</label>
                                                    <textarea name="officer_details" id="officer_details" 
                                                        class="form-control @error('officer_details') is-invalid @enderror" 
                                                        rows="3" placeholder="Provide details...">{{ old('officer_details', $user->officer_details) }}</textarea>
                                                    @error('officer_details')
                                                        <span class="invalid-feedback" role="alert">
                                                            <strong>{{ $message }}</strong>
                                                        </span>
                                                    @enderror
                                                </div>
                                            </td>
                                        </tr>

                                        <!-- Question 2 -->
                                        <tr>
                                            <td class="col-md-8">
                                                <strong>2.</strong> Do you or a member of your family have a material financial
                                                interest in a company with business dealings with CCBRT?
                                                <span class="text-danger">*</span>
                                            </td>
                                            <td class="col-md-4">
                                                <select name="financial_interest" id="financial_interest"
                                                    class="form-control @error('financial_interest') is-invalid @enderror" 
                                                    required onchange="toggleQuestion(2)">
                                                    <option value="" disabled selected>Select</option>
                                                    <option value="Yes"
                                                        {{ old('financial_interest', $user->financial_interest) == 'Yes' ? 'selected' : '' }}>
                                                        Yes</option>
                                                    <option value="No"
                                                        {{ old('financial_interest', $user->financial_interest) == 'No' ? 'selected' : '' }}>
                                                        No</option>
                                                </select>
                                                @error('financial_interest')
                                                    <span class="invalid-feedback" role="alert">
                                                        <strong>{{ $message }}</strong>
                                                    </span>
                                                @enderror
                                            </td>
                                        </tr>
                                        <tr id="question2_details" class="hidden">
                                            <td colspan="2">
                                                <div class="form-group">
                                                    <label class="mb-2">If yes, please provide the details:</label>
                                                    <textarea name="financial_details" id="financial_details" 
                                                        class="form-control @error('financial_details') is-invalid @enderror" 
                                                        rows="3" placeholder="Provide details...">{{ old('financial_details', $user->financial_details) }}</textarea>
                                                    @error('financial_details')
                                                        <span class="invalid-feedback" role="alert">
                                                            <strong>{{ $message }}</strong>
                                                        </span>
                                                    @enderror
                                                </div>
                                            </td>
                                        </tr>

                                        <!-- Question 3 -->
                                        <tr>
                                            <td class="col-md-8">
                                                <strong>3.</strong> Do you or a member of your family have any other interests that
                                                might create a conflict of interest?
                                                <span class="text-danger">*</span>
                                            </td>
                                            <td class="col-md-4">
                                                <select name="other_interests" id="other_interests" 
                                                    class="form-control @error('other_interests') is-invalid @enderror"
                                                    required onchange="toggleQuestion(3)">
                                                    <option value="" disabled selected>Select</option>
                                                    <option value="Yes"
                                                        {{ old('other_interests', $user->other_interests) == 'Yes' ? 'selected' : '' }}>
                                                        Yes</option>
                                                    <option value="No"
                                                        {{ old('other_interests', $user->other_interests) == 'No' ? 'selected' : '' }}>
                                                        No</option>
                                                </select>
                                                @error('other_interests')
                                                    <span class="invalid-feedback" role="alert">
                                                        <strong>{{ $message }}</strong>
                                                    </span>
                                                @enderror
                                            </td>
                                        </tr>
                                        <tr id="question3_details" class="hidden">
                                            <td colspan="2">
                                                <div class="form-group">
                                                    <label class="mb-2">If yes, please provide details below:</label>
                                                    <textarea name="interest_details" id="interest_details" 
                                                        class="form-control @error('interest_details') is-invalid @enderror" 
                                                        rows="3" placeholder="Provide details...">{{ old('interest_details', $user->interest_details) }}</textarea>
                                                    @error('interest_details')
                                                        <span class="invalid-feedback" role="alert">
                                                            <strong>{{ $message }}</strong>
                                                        </span>
                                                    @enderror
                                                </div>
                                            </td>
                                        </tr>

                                        <!-- Question 4 -->
                                        <tr>
                                            <td class="col-md-8">
                                                <strong>4.</strong> Please declare CCBRT as your primary employer:
                                                <span class="text-danger">*</span>
                                            </td>
                                            <td class="col-md-4">
                                                <select name="primary_employer_ccbrt" id="primary_employer_ccbrt"
                                                    class="form-control @error('primary_employer_ccbrt') is-invalid @enderror" 
                                                    required onchange="toggleQuestion(4)">
                                                    <option value="" disabled selected>Select</option>
                                                    <option value="Yes"
                                                        {{ old('primary_employer_ccbrt', $user->primary_employer_ccbrt) == 'Yes' ? 'selected' : '' }}>
                                                        Yes</option>
                                                    <option value="No"
                                                        {{ old('primary_employer_ccbrt', $user->primary_employer_ccbrt) == 'No' ? 'selected' : '' }}>
                                                        No</option>
                                                </select>
                                                @error('primary_employer_ccbrt')
                                                    <span class="invalid-feedback" role="alert">
                                                        <strong>{{ $message }}</strong>
                                                    </span>
                                                @enderror
                                            </td>
                                        </tr>
                                        <tr id="question4_details" class="hidden">
                                            <td colspan="2">
                                                <div class="form-group">
                                                    <label class="mb-2">If no, please explain:</label>
                                                    <textarea name="primary_employer_details" id="primary_employer_details" 
                                                        class="form-control @error('primary_employer_details') is-invalid @enderror" 
                                                        rows="3" placeholder="Provide details...">{{ old('primary_employer_details', $user->primary_employer_details) }}</textarea>
                                                    @error('primary_employer_details')
                                                        <span class="invalid-feedback" role="alert">
                                                            <strong>{{ $message }}</strong>
                                                        </span>
                                                    @enderror
                                                </div>
                                            </td>
                                        </tr>

                                        <!-- Question 5 -->
                                        <tr>
                                            <td class="col-md-8">
                                                <strong>5.</strong> Have you ever been involved in any court proceedings? If yes,
                                                were you convicted/punished? Please give details.
                                            </td>
                                            <td class="col-md-4">
                                                <select name="court_proceedings" id="court_proceedings" 
                                                    class="form-control @error('court_proceedings') is-invalid @enderror"
                                                    onchange="toggleQuestion(5)">
                                                    <option value="" disabled selected>Select</option>
                                                    <option value="Yes"
                                                        {{ old('court_proceedings', $user->court_proceedings) == 'Yes' ? 'selected' : '' }}>
                                                        Yes</option>
                                                    <option value="No"
                                                        {{ old('court_proceedings', $user->court_proceedings) == 'No' ? 'selected' : '' }}>
                                                        No</option>
                                                </select>
                                                @error('court_proceedings')
                                                    <span class="invalid-feedback" role="alert">
                                                        <strong>{{ $message }}</strong>
                                                    </span>
                                                @enderror
                                            </td>
                                        </tr>
                                        <tr id="question5_details" class="hidden">
                                            <td colspan="2">
                                                <div class="form-group">
                                                    <label class="mb-2">If yes, please provide details below:</label>
                                                    <textarea name="court_details" id="court_details" 
                                                        class="form-control @error('court_details') is-invalid @enderror" 
                                                        rows="3" placeholder="Provide details...">{{ old('court_details', $user->court_details) }}</textarea>
                                                    @error('court_details')
                                                        <span class="invalid-feedback" role="alert">
                                                            <strong>{{ $message }}</strong>
                                                        </span>
                                                    @enderror
                                                </div>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>

                                <div class="form-group mt-4 p-3" style="background-color: #f8f9fa; border-radius: 5px; border-left: 4px solid #007A33;">
                                    <label class="mb-2">
                                        <strong>Declaration:</strong>
                                    </label>
                                    <p class="mb-2" style="font-size: 0.95rem; line-height: 1.6;">
                                        I declare that the above details are correct and true to the best of my knowledge and acknowledge that
                                        I will be liable to action against me as per the rules of the organization if, at any point of time during
                                        my employment with the organization, any of the above details are found to be untrue. I also undertake
                                        to periodically inform the organization and to update the HR department in case of any relevant
                                        changes in the details mentioned above or on other relevant matters (e.g. completed courses, training).
                                    </p>
                                </div>

                                <div class="form-group mt-3">
                                    <div class="form-check p-3" style="background-color: #f8f9fa; border-radius: 5px; border: 2px solid #e9ecef;">
                                        <input type="checkbox" name="hr_detail_declare" id="hr_detail_declare" 
                                            value="on" required
                                            class="form-check-input @error('hr_detail_declare') is-invalid @enderror"
                                            style="width: 20px; height: 20px; margin-top: 0.25rem; accent-color: #007A33;"
                                            {{ old('hr_detail_declare', ($user->hr_detail_declare === 'on' || $user->hr_detail_declare == 1 || $user->hr_detail_declare === 'I agree')) ? 'checked' : '' }}>
                                        <label for="hr_detail_declare" class="form-check-label ms-2 fw-semibold" style="cursor: pointer; user-select: none;">
                                            I agree to the terms and conditions stated above.
                                            <span class="text-danger">*</span>
                                        </label>
                                        @error('hr_detail_declare')
                                            <span class="invalid-feedback d-block" role="alert">
                                                <strong>{{ $message }}</strong>
                                            </span>
                                        @enderror
                                    </div>
                                </div>

                                <div class="d-flex justify-content-between mt-4">
                                    <a href="{{ route('profile.ccbrt_relation') }}" class="btn btn-secondary">Previous</a>
                                    <button type="submit" class="btn btn-primary">Save and Continue</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <script>
                function toggleQuestion(questionNumber) {
                    const Yes = "Yes";
                    const No = "No";

                    if (questionNumber === 1) {
                        const value = document.getElementById('conflict_officer_role').value;
                        const detailsRow = document.getElementById('question1_details');
                        if (detailsRow) {
                            detailsRow.classList.toggle('hidden', value !== Yes);
                            if (value === Yes) {
                                document.getElementById('officer_details').required = true;
                            } else {
                                document.getElementById('officer_details').required = false;
                                document.getElementById('officer_details').value = '';
                            }
                        }
                    }

                    if (questionNumber === 2) {
                        const value = document.getElementById('financial_interest').value;
                        const detailsRow = document.getElementById('question2_details');
                        if (detailsRow) {
                            detailsRow.classList.toggle('hidden', value !== Yes);
                            if (value === Yes) {
                                document.getElementById('financial_details').required = true;
                            } else {
                                document.getElementById('financial_details').required = false;
                                document.getElementById('financial_details').value = '';
                            }
                        }
                    }

                    if (questionNumber === 3) {
                        const value = document.getElementById('other_interests').value;
                        const detailsRow = document.getElementById('question3_details');
                        if (detailsRow) {
                            detailsRow.classList.toggle('hidden', value !== Yes);
                            if (value === Yes) {
                                document.getElementById('interest_details').required = true;
                            } else {
                                document.getElementById('interest_details').required = false;
                                document.getElementById('interest_details').value = '';
                            }
                        }
                    }

                    if (questionNumber === 4) {
                        const value = document.getElementById('primary_employer_ccbrt').value;
                        const detailsRow = document.getElementById('question4_details');
                        if (detailsRow) {
                            detailsRow.classList.toggle('hidden', value !== No);
                            if (value === No) {
                                document.getElementById('primary_employer_details').required = true;
                            } else {
                                document.getElementById('primary_employer_details').required = false;
                                document.getElementById('primary_employer_details').value = '';
                            }
                        }
                    }

                    if (questionNumber === 5) {
                        const value = document.getElementById('court_proceedings').value;
                        const detailsRow = document.getElementById('question5_details');
                        if (detailsRow) {
                            detailsRow.classList.toggle('hidden', value !== Yes);
                            if (value === Yes) {
                                document.getElementById('court_details').required = true;
                            } else {
                                document.getElementById('court_details').required = false;
                                document.getElementById('court_details').value = '';
                            }
                        }
                    }
                }

                // Initialize on page load
                document.addEventListener('DOMContentLoaded', function() {
                    toggleQuestion(1);
                    toggleQuestion(2);
                    toggleQuestion(3);
                    toggleQuestion(4);
                    toggleQuestion(5);

                    // Form validation
                    const form = document.getElementById('conflictForm');
                    if (form) {
                        form.addEventListener('submit', function(e) {
                            // Validate conditional required fields
                            const conflictOfficerRole = document.getElementById('conflict_officer_role').value;
                            if (conflictOfficerRole === 'Yes') {
                                const officerDetails = document.getElementById('officer_details').value;
                                if (!officerDetails || officerDetails.trim() === '') {
                                    e.preventDefault();
                                    alert('Please provide details for question 1.');
                                    return false;
                                }
                            }

                            const financialInterest = document.getElementById('financial_interest').value;
                            if (financialInterest === 'Yes') {
                                const financialDetails = document.getElementById('financial_details').value;
                                if (!financialDetails || financialDetails.trim() === '') {
                                    e.preventDefault();
                                    alert('Please provide details for question 2.');
                                    return false;
                                }
                            }

                            const otherInterests = document.getElementById('other_interests').value;
                            if (otherInterests === 'Yes') {
                                const interestDetails = document.getElementById('interest_details').value;
                                if (!interestDetails || interestDetails.trim() === '') {
                                    e.preventDefault();
                                    alert('Please provide details for question 3.');
                                    return false;
                                }
                            }

                            const primaryEmployer = document.getElementById('primary_employer_ccbrt').value;
                            if (primaryEmployer === 'No') {
                                const primaryEmployerDetails = document.getElementById('primary_employer_details').value;
                                if (!primaryEmployerDetails || primaryEmployerDetails.trim() === '') {
                                    e.preventDefault();
                                    alert('Please provide details for question 4.');
                                    return false;
                                }
                            }

                            const courtProceedings = document.getElementById('court_proceedings').value;
                            if (courtProceedings === 'Yes') {
                                const courtDetails = document.getElementById('court_details').value;
                                if (!courtDetails || courtDetails.trim() === '') {
                                    e.preventDefault();
                                    alert('Please provide details for question 5.');
                                    return false;
                                }
                            }
                        });
                    }
                });
            </script>

            <style>
                .hidden {
                    display: none;
                }

                .table td {
                    vertical-align: middle;
                }

                .form-control:focus, .form-select:focus {
                    border-color: #007A33;
                    box-shadow: 0 0 0 0.2rem rgba(0, 122, 51, 0.25);
                }
            </style>
        </div>
    </div>
@endsection
