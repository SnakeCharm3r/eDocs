@extends('layouts.template2')
@include('includes.loader')

@section('breadcrumb')
    <div class="content container-fluid" style="background-color: #ffffff;">
        <div class="page-header">
            <div class="row">
                <div class="col-sm-12">
                    <div class="page-sub-header">
                        <h3 class="page-title">Review HR Form</h3>
                    </div>
                </div>
            </div>
        </div>
        <style>
            @media (max-width: 768px) {
                .fw-bold {
                    font-size: 1.5rem !important;
                }

                .section-title {
                    font-size: 1.25rem !important;
                }

                .table-sm td,
                .table-sm th {
                    font-size: 0.85rem;
                }
            }

            .section-frame {
                border: 1px solid #dee2e6;
                border-radius: 8px;
                background-color: #fff;
                box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
                margin-bottom: 2rem;
                padding: 1.5rem;
            }

            .section-title {
                color: #007A33 !important;
                font-size: 1.5rem;
                font-weight: 600;
                border-bottom: 2px solid #007A33;
                padding-bottom: 0.5rem;
                margin-bottom: 1.5rem;
            }

            .section-title i {
                margin-right: 0.5rem;
            }

            .table th,
            .table td {
                vertical-align: middle;
                padding: 0.75rem;
            }

            .table thead {
                background-color: #007A33;
                color: white;
            }

            .table thead th {
                border-color: #005a25;
                font-weight: 600;
            }

            .text-muted {
                color: #6c757d !important;
            }

            .edit-link {
                color: #007A33;
                text-decoration: none;
                font-size: 0.9rem;
                margin-left: 10px;
            }

            .edit-link:hover {
                text-decoration: underline;
                color: #005a25;
            }

            .badge-empty {
                background-color: #e9ecef;
                color: #6c757d;
                padding: 0.25rem 0.5rem;
                border-radius: 0.25rem;
            }

            .signature-display {
                border: 2px solid #dee2e6;
                border-radius: 5px;
                padding: 10px;
                background-color: #f8f9fa;
                display: inline-block;
            }

            .signature-display img {
                max-width: 200px;
                height: auto;
            }

            .action-buttons {
                position: sticky;
                bottom: 0;
                background-color: white;
                padding: 1rem;
                border-top: 2px solid #dee2e6;
                box-shadow: 0 -2px 10px rgba(0, 0, 0, 0.1);
                z-index: 100;
            }

            .info-alert {
                background-color: #e7f3ed;
                border-left: 4px solid #007A33;
                padding: 1rem;
                border-radius: 5px;
                margin-bottom: 2rem;
            }

            .info-alert i {
                color: #007A33;
                margin-right: 0.5rem;
            }
        </style>

        <div class="container my-4">
            <!-- Header Section -->
            <div class="text-center position-relative mb-4">
                <img src="{{ asset('assets/img/line.png') }}" alt="Header Image" class="img-fluid" style="height: 35px;">
                <div class="position-absolute fw-bold"
                    style="right: 0; top: 50%; transform: translate(-10%, -50%); font-size: 1rem; 
                           background-color: white; color: grey; padding: 5px 10px; border-radius: 5px;">
                    HR.8 v2022
                </div>
            </div>

            <!-- Title Section -->
            <div class="d-flex flex-column flex-md-row align-items-center justify-content-between mb-4">
                <div class="text-center text-md-start">
                    <h1 class="fw-bold" style="color: #007A33; font-size: 2rem; margin: 0;">HR DETAILS FORM</h1>
                    <p class="text-muted mt-2">Please review all your details before submitting</p>
                </div>
                <img src="{{ asset('assets/img/ccbrt.jpg') }}" alt="CCBRT Logo" class="img-fluid"
                    style="max-width: 80px; height: auto;">
            </div>

            <!-- Info Alert -->
            <div class="info-alert">
                <i class="fas fa-info-circle"></i>
                <strong>Review Your Information:</strong> Please carefully review all sections below. If you need to make changes, click the "Edit" links next to each section or use the navigation buttons at the bottom.
            </div>

            <!-- User Details Section -->
            <div class="section-frame">
                <h4 class="section-title">
                    <i class="fas fa-user"></i> User Details
                    <a href="{{ route('profile.personalDetails') }}" class="edit-link">
                        <i class="fas fa-edit"></i> Edit
                    </a>
                </h4>
                <table class="table table-sm table-borderless align-middle">
                    <tr>
                        <!-- Passport Photo Section -->
                        <td rowspan="2" class="text-center align-top"
                            style="width: 20%; background-color: #f8f9fa; padding: 1rem;">
                            <strong class="d-block mb-2">Passport Photo</strong>
                            <div>
                                @if ($user->profile_picture)
                                    <img src="{{ asset('storage/' . $user->profile_picture) }}" alt="Passport Image"
                                        class="img-fluid rounded border"
                                        style="max-width: 140px; max-height: 180px; width: auto; height: auto; object-fit: cover; object-position: center;">
                                @else
                                    <p class="text-muted" style="font-size: 0.9rem;">No image uploaded</p>
                                @endif
                            </div>
                        </td>

                        <!-- Personal Information Row -->
                        <td class="p-3" colspan="3">
                            <table style="width: 100%; border-collapse: collapse;">
                                <tr>
                                    <td style="border-right: 1px solid #dee2e6; padding-right: 15px; width: 33%;">
                                        <strong>First Name:</strong>
                                        <span class="text-muted">{{ $user->fname ?? 'N/A' }}</span>
                                    </td>
                                    <td
                                        style="border-right: 1px solid #dee2e6; padding-right: 15px; padding-left: 15px; width: 33%;">
                                        <strong>Middle Name:</strong>
                                        <span class="text-muted">{{ $user->mname ?? 'N/A' }}</span>
                                    </td>
                                    <td style="padding-left: 15px; width: 34%;">
                                        <strong>Surname:</strong>
                                        <span class="text-muted">{{ $user->lname ?? 'N/A' }}</span>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <tr>
                        <!-- Job Information Row -->
                        <td class="p-3" colspan="2">
                            <div class="mb-2">
                                <strong>CCBRT Job Title:</strong>
                                <span class="text-muted">{{ $user->jobTitle->job_title ?? 'N/A' }}</span>
                            </div>
                            <div class="mb-2">
                                <strong>Department / Programme:</strong>
                                <span class="text-muted">{{ $user->department->dept_name ?? 'N/A' }}</span>
                            </div>
                            <div>
                                <strong>Employment Type at CCBRT:</strong>
                                <span class="text-muted">{{ $user->employmentType->employment_type ?? 'N/A' }}</span>
                            </div>
                        </td>
                    </tr>
                </table>
            </div>

            <!-- Personal Information Section -->
            <div class="section-frame">
                <h4 class="section-title">
                    <i class="fas fa-id-card"></i> Personal Information
                    <a href="{{ route('profile.personalDetails') }}" class="edit-link">
                        <i class="fas fa-edit"></i> Edit
                    </a>
                </h4>
                <table class="table table-bordered table-striped">
                    <tbody>
                        <tr>
                            <td class="w-33">
                                <strong>Full Name:</strong> <span class="text-muted">{{ $user->fname }}
                                    {{ $user->mname }} {{ $user->lname }}</span>
                            </td>
                            <td class="w-33">
                                <strong>Gender:</strong> <span
                                    class="text-muted">{{ $user->gender ?? 'N/A' }}</span>
                            </td>
                            <td class="w-33">
                                <strong>Date of Birth:</strong> <span class="text-muted">{{ $user->DOB ? \Carbon\Carbon::parse($user->DOB)->format('F j, Y') : 'N/A' }}</span>
                            </td>
                        </tr>
                        <tr>
                            <td class="w-33">
                                <strong>Marital Status:</strong> <span
                                    class="text-muted">{{ $user->marital_status ?? 'N/A' }}</span>
                            </td>
                            <td class="w-33">
                                <strong>Nationality:</strong> <span
                                    class="text-muted">{{ $user->nationality ?? 'N/A' }}</span>
                            </td>
                            <td class="w-33">
                                <strong>Place of Birth:</strong> <span
                                    class="text-muted">{{ $user->place_of_birth ?? 'N/A' }}</span>
                            </td>
                        </tr>
                        <tr>
                            <td class="w-33">
                                <strong>Religion:</strong> <span class="text-muted">{{ $user->religion ?? 'N/A' }}</span>
                            </td>
                            <td class="w-33">
                                <strong>Domicile:</strong> <span class="text-muted">{{ $user->domicile ?? 'N/A' }}</span>
                            </td>
                            <td class="w-33">
                                <strong>Passport No:</strong> <span
                                    class="text-muted">{{ $user->passport_no ?? 'N/A' }}</span>
                            </td>
                        </tr>
                        <tr>
                            <td class="w-33">
                                <strong>TIN Number:</strong> <span class="text-muted">{{ $user->tin_no ?? 'N/A' }}</span>
                            </td>
                            <td class="w-33">
                                <strong>NIDA Number:</strong> <span class="text-muted">{{ $user->NIN ?? 'N/A' }}</span>
                            </td>
                            <td class="w-33">
                                <strong>Professional Registration No:</strong> <span
                                    class="text-muted">{{ $user->professional_reg_number ?? 'N/A' }}</span>
                            </td>
                        </tr>
                        <tr>
                            <td class="w-33">
                                <strong>Region:</strong> <span class="text-muted">{{ $user->region ?? 'N/A' }}</span>
                            </td>
                            <td class="w-33">
                                <strong>District:</strong> <span class="text-muted">{{ $user->district ?? 'N/A' }}</span>
                            </td>
                            <td class="w-33">
                                <strong>Street:</strong> <span class="text-muted">{{ $user->street ?? 'N/A' }}</span>
                            </td>
                        </tr>
                        <tr>
                            <td class="w-33">
                                <strong>Popular Landmark:</strong> <span
                                    class="text-muted">{{ $user->popular_landmark ?? 'N/A' }}</span>
                            </td>
                            <td class="w-33">
                                <strong>House No:</strong> <span class="text-muted">{{ $user->house_no ?? 'N/A' }}</span>
                            </td>
                            <td class="w-33">
                                <strong>Mobile:</strong> <span class="text-muted">{{ $user->mobile ?? 'N/A' }}</span>
                            </td>
                        </tr>
                        <tr>
                            <td class="w-33">
                                <strong>Email:</strong> <span class="text-muted">{{ $user->email ?? 'N/A' }}</span>
                            </td>
                            <td class="w-33">
                                <strong>Street Address:</strong> <span
                                    class="text-muted">{{ $user->street ?? 'N/A' }}</span>
                            </td>
                            <td class="w-33">
                                <strong>NSSF No:</strong> <span class="text-muted">{{ $user->nssf_no ?? 'N/A' }}</span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Family Data Section -->
            <div class="section-frame">
                <h4 class="section-title">
                    <i class="fas fa-users"></i> Family Data
                    <a href="{{ route('profile.familyDetails') }}" class="edit-link">
                        <i class="fas fa-edit"></i> Edit
                    </a>
                </h4>
                @if ($familyDetails && $familyDetails->count() > 0)
                    <table class="table table-bordered table-striped">
                        <thead class="table-light">
                            <tr>
                                <th class="text-center" style="width: 5%">#</th>
                                <th>Name</th>
                                <th>Relationship</th>
                                <th>Occupation</th>
                                <th>Phone Number</th>
                                <th>Next of Kin</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($familyDetails as $index => $family)
                                <tr>
                                    <td class="text-center">{{ $index + 1 }}</td>
                                    <td>{{ $family->full_name }}</td>
                                    <td>{{ $family->relationship }}{{ $family->other_relationship ? ' (' . $family->other_relationship . ')' : '' }}</td>
                                    <td>{{ $family->occupation ?? 'N/A' }}{{ $family->other_occupation ? ' (' . $family->other_occupation . ')' : '' }}</td>
                                    <td>{{ $family->phone_number ?? 'N/A' }}</td>
                                    <td class="text-center">
                                        @if ($family->next_of_kin)
                                            <span class="badge bg-success">Yes</span>
                                        @else
                                            <span class="badge bg-secondary">No</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @else
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i> No family members added. 
                        <a href="{{ route('profile.familyDetails') }}" class="alert-link">Add family members</a>
                    </div>
                @endif
            </div>

            <!-- Next of Kin and Emergency Contact Details Section -->
            <div class="section-frame">
                <h4 class="section-title">
                    <i class="fas fa-phone-alt"></i> Next of Kin and Emergency Contact Details
                </h4>
                @php
                    $nextOfKin = $familyDetails ? $familyDetails->where('next_of_kin', 1) : collect([]);
                @endphp
                @if ($nextOfKin->count() > 0)
                    <table class="table table-bordered table-striped">
                        <thead class="table-light">
                            <tr>
                                <th>Name</th>
                                <th>Relationship</th>
                                <th>Phone Number</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($nextOfKin as $family)
                                <tr>
                                    <td>{{ $family->full_name }}</td>
                                    <td>{{ $family->relationship }}{{ $family->other_relationship ? ' (' . $family->other_relationship . ')' : '' }}</td>
                                    <td>{{ $family->phone_number ?? 'N/A' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @else
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle"></i> No next of kin specified. 
                        <a href="{{ route('profile.familyDetails') }}" class="alert-link">Please add at least one next of kin</a>
                    </div>
                @endif
            </div>

            <!-- Health Data Section -->
            <div class="section-frame">
                <h4 class="section-title">
                    <i class="fas fa-heartbeat"></i> Health Data
                    <a href="{{ route('profile.healthDetails') }}" class="edit-link">
                        <i class="fas fa-edit"></i> Edit
                    </a>
                </h4>
                @if ($healthDetails)
                    <table class="table table-bordered table-striped">
                        <tbody>
                            <tr>
                                <td class="w-33">
                                    <strong>Physical Disability:</strong> 
                                    <span class="text-muted">
                                        {{ $healthDetails->physical_disability ?? 'None' }}
                                        @if ($healthDetails->other_disability)
                                            ({{ $healthDetails->other_disability }})
                                        @endif
                                    </span>
                                </td>
                                <td class="w-33">
                                    <strong>Blood Group:</strong> 
                                    <span class="text-muted">{{ $healthDetails->blood_group ?? 'Unknown' }}</span>
                                </td>
                                <td class="w-33">
                                    <strong>Health Insurance:</strong> 
                                    <span class="text-muted">{{ $healthDetails->health_insurance ?? 'No' }}</span>
                                </td>
                            </tr>
                            @if ($healthDetails->health_insurance === 'yes')
                                <tr>
                                    <td class="w-33">
                                        <strong>Insurance Name:</strong> 
                                        <span class="text-muted">{{ $healthDetails->insur_name ?? 'N/A' }}</span>
                                    </td>
                                    <td class="w-33">
                                        <strong>Insurance Number:</strong> 
                                        <span class="text-muted">{{ $healthDetails->insur_no ?? 'N/A' }}</span>
                                    </td>
                                    <td class="w-33"></td>
                                </tr>
                            @endif
                            <tr>
                                <td colspan="3">
                                    <strong>Major Illness/Surgery:</strong> 
                                    <span class="text-muted">{{ $healthDetails->illness_history ?? 'None' }}</span>
                                </td>
                            </tr>
                            <tr>
                                <td colspan="3">
                                    <strong>Allergies:</strong> 
                                    <span class="text-muted">{{ $healthDetails->allergies ?? 'None' }}</span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                @else
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i> No health details added. 
                        <a href="{{ route('profile.healthDetails') }}" class="alert-link">Add health details</a>
                    </div>
                @endif
            </div>

            <!-- Language Knowledge Section -->
            <div class="section-frame">
                <h4 class="section-title">
                    <i class="fas fa-language"></i> Knowledge of Languages
                    <a href="{{ route('profile.languageKnowledge') }}" class="edit-link">
                        <i class="fas fa-edit"></i> Edit
                    </a>
                </h4>
                @if ($languageKnowledge && $languageKnowledge->count() > 0)
                    <table class="table table-bordered table-striped">
                        <thead class="table-light">
                            <tr>
                                <th class="text-center" style="width: 5%">#</th>
                                <th>Language</th>
                                <th>Speaking</th>
                                <th>Reading</th>
                                <th>Writing</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($languageKnowledge as $language)
                                <tr>
                                    <td class="text-center">{{ $loop->iteration }}</td>
                                    <td>{{ $language->language }}{{ $language->other_language ? ' (' . $language->other_language . ')' : '' }}</td>
                                    <td>
                                        <span class="badge {{ $language->speaking === 'Yes' ? 'bg-success' : 'bg-secondary' }}">
                                            {{ $language->speaking }}
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge {{ $language->reading === 'Yes' ? 'bg-success' : 'bg-secondary' }}">
                                            {{ $language->reading }}
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge {{ $language->writing === 'Yes' ? 'bg-success' : 'bg-secondary' }}">
                                            {{ $language->writing }}
                                        </span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @else
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i> No languages added. 
                        <a href="{{ route('profile.languageKnowledge') }}" class="alert-link">Add languages</a>
                    </div>
                @endif
            </div>

            <!-- CCBRT Relationship Section -->
            @if ($relations && $relations->isNotEmpty())
                <div class="section-frame">
                    <h4 class="section-title">
                        <i class="fas fa-handshake"></i> CCBRT Relationship
                        <a href="{{ route('profile.ccbrt_relation') }}" class="edit-link">
                            <i class="fas fa-edit"></i> Edit
                        </a>
                    </h4>
                    <table class="table table-bordered table-striped">
                        <thead class="table-light">
                            <tr>
                                <th class="text-center" style="width: 5%">#</th>
                                <th>Name</th>
                                <th>Relation</th>
                                <th>Department</th>
                                <th>Position</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($relations as $relation)
                                <tr>
                                    <td class="text-center">{{ $loop->iteration }}</td>
                                    <td>{{ $relation->names }}</td>
                                    <td>{{ $relation->relation }}{{ $relation->other_relation ? ' (' . $relation->other_relation . ')' : '' }}</td>
                                    <td>{{ $relation->department->dept_name ?? 'N/A' }}</td>
                                    <td>{{ $relation->position }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif

            <!-- Conflict of Interest Section -->
            <div class="section-frame">
                <h4 class="section-title">
                    <i class="fas fa-balance-scale"></i> Disclosure of Conflict of Interest
                    <a href="{{ route('conflict-interest.viewit') }}" class="edit-link">
                        <i class="fas fa-edit"></i> Edit
                    </a>
                </h4>
                @if ($user)
                    <table class="table table-bordered table-striped">
                        <thead class="table-light">
                            <tr>
                                <th class="text-center" style="width: 5%">#</th>
                                <th>Question</th>
                                <th>Answer</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <th class="text-center">1</th>
                                <td>Are you or a member of your immediate family an officer, director, trustee, partner,
                                    employee, or regularly retained consultant of any company that presently has business
                                    dealings with CCBRT?</td>
                                <td>
                                    <span class="badge {{ strtolower($user->conflict_officer_role ?? '') === 'yes' ? 'bg-warning' : 'bg-success' }}">
                                        {{ $user->conflict_officer_role ?? 'N/A' }}
                                    </span>
                                </td>
                            </tr>
                            @if (strtolower($user->conflict_officer_role ?? '') === 'yes' && $user->officer_details)
                                <tr>
                                    <td></td>
                                    <td colspan="2">
                                        <strong>Details:</strong> 
                                        <span class="text-muted">{{ $user->officer_details }}</span>
                                    </td>
                                </tr>
                            @endif
                            <tr>
                                <th class="text-center">2</th>
                                <td>Do you or a member of your family have a material financial interest in a company with
                                    business dealings with CCBRT?</td>
                                <td>
                                    <span class="badge {{ strtolower($user->financial_interest ?? '') === 'yes' ? 'bg-warning' : 'bg-success' }}">
                                        {{ $user->financial_interest ?? 'N/A' }}
                                    </span>
                                </td>
                            </tr>
                            @if (strtolower($user->financial_interest ?? '') === 'yes' && $user->financial_details)
                                <tr>
                                    <td></td>
                                    <td colspan="2">
                                        <strong>Details:</strong> 
                                        <span class="text-muted">{{ $user->financial_details }}</span>
                                    </td>
                                </tr>
                            @endif
                            <tr>
                                <th class="text-center">3</th>
                                <td>Do you or a member of your family have any other interests that might create a conflict
                                    of interest?</td>
                                <td>
                                    <span class="badge {{ strtolower($user->other_interests ?? '') === 'yes' ? 'bg-warning' : 'bg-success' }}">
                                        {{ $user->other_interests ?? 'N/A' }}
                                    </span>
                                </td>
                            </tr>
                            @if (strtolower($user->other_interests ?? '') === 'yes' && $user->interest_details)
                                <tr>
                                    <td></td>
                                    <td colspan="2">
                                        <strong>Details:</strong> 
                                        <span class="text-muted">{{ $user->interest_details }}</span>
                                    </td>
                                </tr>
                            @endif
                            <tr>
                                <th class="text-center">4</th>
                                <td>Please declare CCBRT as your primary employer:</td>
                                <td>
                                    <span class="badge {{ strtolower($user->primary_employer_ccbrt ?? '') === 'yes' ? 'bg-success' : 'bg-warning' }}">
                                        {{ $user->primary_employer_ccbrt ?? 'N/A' }}
                                    </span>
                                </td>
                            </tr>
                            @if (strtolower($user->primary_employer_ccbrt ?? '') === 'no' && $user->primary_employer_details)
                                <tr>
                                    <td></td>
                                    <td colspan="2">
                                        <strong>Explanation:</strong> 
                                        <span class="text-muted">{{ $user->primary_employer_details }}</span>
                                    </td>
                                </tr>
                            @endif
                            <tr>
                                <th class="text-center">5</th>
                                <td>Have you ever been involved in any court proceedings?</td>
                                <td>
                                    <span class="badge {{ strtolower($user->court_proceedings ?? '') === 'yes' ? 'bg-warning' : 'bg-success' }}">
                                        {{ $user->court_proceedings ?? 'N/A' }}
                                    </span>
                                </td>
                            </tr>
                            @if (strtolower($user->court_proceedings ?? '') === 'yes' && $user->court_details)
                                <tr>
                                    <td></td>
                                    <td colspan="2">
                                        <strong>Details:</strong> 
                                        <span class="text-muted">{{ $user->court_details }}</span>
                                    </td>
                                </tr>
                            @endif
                        </tbody>
                    </table>
                @else
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle"></i> Conflict of interest information not available.
                    </div>
                @endif
            </div>

            <!-- Declaration Section -->
            <div class="section-frame">
                <h4 class="section-title">
                    <i class="fas fa-file-signature"></i> Declaration
                </h4>
                <p class="mb-4" style="font-size: 1rem; color: #6c757d; line-height: 1.8;">
                    I declare that the information provided in this form is true and correct to the best of my
                    knowledge, and acknowledge that I will be liable to action against me as per the rules of the
                    organization if, at any point of time during my employment with the organization, any of the above
                    details are found to be untrue. I also undertake to periodically inform the organization and update
                    the HR department in case of any relevant changes in the details mentioned above or on other
                    relevant matters (e.g., completed courses, training).
                </p>

                <table class="table table-bordered">
                    <tbody>
                        <tr>
                            <th class="text-right" style="width: 30%; background-color: #f8f9fa;">Name of Employee:</th>
                            <td class="text-muted">
                                <strong>{{ $user->fname }} {{ $user->mname }} {{ $user->lname }}</strong>
                            </td>
                        </tr>
                        <tr>
                            <th class="text-right" style="width: 30%; background-color: #f8f9fa;">Signature:</th>
                            <td class="text-muted">
                                @if (auth()->user()->signature)
                                    <div class="signature-display">
                                        <img src="data:image/png;base64,{{ auth()->user()->signature }}"
                                            alt="User Signature">
                                    </div>
                                @else
                                    <div class="alert alert-warning mb-0">
                                        <i class="fas fa-exclamation-triangle"></i> No signature found. 
                                        <a href="{{ route('signature.index') }}" class="alert-link">Please add your signature</a>
                                    </div>
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <th class="text-right" style="width: 30%; background-color: #f8f9fa;">Date:</th>
                            <td class="text-muted">
                                <strong>{{ \Carbon\Carbon::now()->format('F j, Y') }}</strong>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Action Buttons -->
            <div class="action-buttons">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                    <div class="d-flex gap-2">
                        <a href="{{ route('signature.index') }}" class="btn btn-secondary px-4">
                            <i class="fas fa-arrow-left"></i> Previous
                        </a>
                        <button type="button" onclick="window.print()" class="btn btn-outline-primary px-4">
                            <i class="fas fa-print"></i> Print
                        </button>
                    </div>
                    <div>
                        <button type="button" id="submitButton" class="btn btn-primary px-5" style="background-color: #007A33; border-color: #007A33;">
                            <i class="fas fa-paper-plane"></i> Submit HR Details Form
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Include SweetAlert2 -->
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.4.12/dist/sweetalert2.all.min.js"></script>
        <script>
            document.getElementById('submitButton').addEventListener('click', function(e) {
                e.preventDefault();
                
                // Check if signature exists
                @if (!auth()->user()->signature)
                    Swal.fire({
                        icon: 'warning',
                        title: 'Signature Required',
                        text: 'Please add your signature before submitting the form.',
                        confirmButtonColor: '#007A33',
                        confirmButtonText: 'Go to Signature Page'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            window.location.href = "{{ route('signature.index') }}";
                        }
                    });
                    return;
                @endif

                Swal.fire({
                    title: 'Submit HR Form?',
                    text: "Are you sure you want to submit this form? Please ensure all information is correct.",
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#007A33',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Yes, submit!',
                    cancelButtonText: 'Cancel',
                    reverseButtons: true
                }).then((result) => {
                    if (result.isConfirmed) {
                        // Show loading spinner
                        Swal.fire({
                            title: 'Processing...',
                            text: 'Please wait while the form is being submitted.',
                            allowOutsideClick: false,
                            allowEscapeKey: false,
                            didOpen: () => {
                                Swal.showLoading();
                            }
                        });

                        // Submit the form
                        setTimeout(() => {
                            window.location.href = "{{ route('hr.send') }}";
                        }, 500);
                    }
                });
            });
        </script>
    </div>
@endsection
