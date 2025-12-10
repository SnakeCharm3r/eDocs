@extends('layouts.template')
@section('breadcrumb')
    @include('sweetalert::alert')
    <div class="page-wrapper">
        <div class="content container-fluid">
            <div class="page-header">
                <div class="row">
                    <div class="col-sm-12">
                        <div class="page-sub-header">
                            <h3 class="page-title"> HR Form </h3>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">

                <style>
                    @media (max-width: 768px) {
                        .fw-bold {
                            font-size: 1.5rem !important;
                        }
                    }
                </style>
                <div class="card card-body p-3">
                    <div class="form-container">
                        <div class="container my-4">
                            <!-- Header Section -->
                            <div class="header-section text-center position-relative mb-3">
                                <img src="{{ asset('assets/img/line.png') }}" alt="Header Image" class="img-fluid">
                                <div class="position-absolute fw-bold header-label">
                                    HR.8 v2022
                                </div>
                            </div>

                            <!-- Title Section -->
                            <div class="d-flex flex-column flex-md-row align-items-center justify-content-start">
                                <div class="text-center w-100">
                                    <div class="fw-bold title-text">HR DETAILS FORM</div>
                                </div>
                                <img src="{{ asset('assets/img/ccbrt.jpg') }}" alt="CCBRT Logo"
                                    class="img-fluid logo-image">
                            </div>

                            <!-- User Details Section -->
                            <div class="user-details-section border p-4 mt-4 rounded shadow-sm">
                                <table class="table table-sm table-borderless align-middle">
                                    <tr>
                                        <!-- Passport Photo Section -->
                                        <td rowspan="2" class="passport-photo text-center align-top">
                                            <strong class="d-block mb-2">Passport Photo</strong>
                                            <div>
                                                @if ($user->profile_picture)
                                                    <!-- Display Passport Image -->
                                                    <img src="{{ asset('storage/' . $user->profile_picture) }}"
                                                        alt="Passport Image" class="img-fluid rounded border">
                                                @else
                                                    <!-- Display message if no image is uploaded -->
                                                    <p class="text-muted">No image uploaded</p>
                                                @endif
                                            </div>
                                        </td>

                                        <!-- Personal Information Row -->
                                        <td class="p-3" colspan="3">
                                            <table class="personal-info-table">
                                                <tr>
                                                    <td class="personal-info-cell">
                                                        <strong>First Name:</strong>
                                                        <span class="text-value">{{ $user->fname }}</span>
                                                    </td>
                                                    <td class="personal-info-cell">
                                                        <strong>Middle Name:</strong>
                                                        <span class="text-value">{{ $user->mname }}</span>
                                                    </td>
                                                    <td class="personal-info-cell">
                                                        <strong>Surname:</strong>
                                                        <span class="text-value">{{ $user->lname }}</span>
                                                    </td>
                                                </tr>
                                            </table>
                                        </td>
                                    </tr>

                                    <tr>
                                        <!-- Job Information Row -->
                                        <td class="p-3" colspan="2">
                                            <div class="mb-3">
                                                <strong>CCBRT Job Title:</strong>
                                                <span class="text-value">{{ $user->jobTitle->job_title ?? 'N/A' }}</span>
                                            </div>
                                            <div class="mb-3">
                                                <strong>Department / Programme:</strong>
                                                <span class="text-value">{{ $user->department->dept_name ?? 'N/A' }}</span>
                                            </div>

                                            <div>
                                                <strong>Employment Type at CCBRT:</strong>
                                                <span
                                                    class="text-value">{{ $user->employmentType->employment_type ?? 'N/A' }}</span>
                                            </div>
                                        </td>
                                    </tr>
                                </table>
                            </div>

                        </div>

                        <!-- CSS Styles -->
                        <style>
                            .header-section img {
                                height: 35px;
                            }

                            .header-label {
                                right: 0;
                                top: 50%;
                                transform: translate(-10%, -50%);
                                font-size: 1.2rem;
                                background-color: white;
                                color: grey;
                                padding: 5px 10px;
                                border-radius: 5px;
                            }

                            .title-text {
                                color: #459c51;
                                font-size: 2rem;
                            }

                            .logo-image {
                                max-width: 80px;
                                height: auto;
                            }

                            .user-details-section .passport-photo {
                                width: 20%;
                                background-color: #f8f9fa;
                            }

                            .user-details-section .passport-photo img {
                                max-width: 140px;
                                max-height: 180px;
                                width: auto;
                                height: auto;
                                object-fit: cover;
                                object-position: center;
                            }

                            .personal-info-table {
                                width: 100%;
                                border-collapse: collapse;
                            }

                            .personal-info-cell {
                                border-right: 1px solid #ccc;
                                padding-right: 10px;
                                width: 33%;
                            }

                            .personal-info-cell:last-child {
                                border-right: none;
                            }

                            .text-value {
                                color: rgb(35, 30, 30);
                            }

                            .user-details-section .text-muted {
                                font-size: 0.9rem;
                            }
                        </style>



                        <div class="container my-5">
                            <!-- Personal Information Section -->
                            <div class="personal-info-section">
                                <div class="border p-4 mb-4">

                                    <h4 class="section-title mb-3" style="color: #495057; font-size: 1.5rem;">
                                        Personal Information
                                    </h4>
                                    <table class="table table-bordered user-details-table">
                                        <!-- Personal Info Row 1 -->
                                        <tr>
                                            <td class="info-cell">
                                                <strong>Full Name:</strong> <span class="text-value">{{ $user->fname }}
                                                    {{ $user->mname }} {{ $user->lname }}</span>
                                            </td>
                                            <td class="info-cell">
                                                <strong>Gender:</strong> <span
                                                    class="text-value">{{ $user->gender === 'Male' ? 'Male' : 'Female' }}</span>
                                            </td>
                                            <td class="info-cell">
                                                <strong>Date of Birth:</strong> <span
                                                    class="text-value">{{ $user->DOB ?? 'N/A' }}</span>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td class="info-cell">
                                                <strong>Marital Status:</strong> <span
                                                    class="text-value">{{ $user->marital_status ?? 'N/A' }}</span>
                                            </td>
                                            <td class="info-cell">
                                                <strong>Nationality:</strong> <span
                                                    class="text-value">{{ $user->nationality ?? 'N/A' }}</span>
                                            </td>
                                            <td class="info-cell">
                                                <strong>Place of Birth:</strong> <span
                                                    class="text-value">{{ $user->place_of_birth ?? 'N/A' }}</span>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td class="info-cell">
                                                <strong>Religion:</strong> <span
                                                    class="text-value">{{ $user->religion ?? 'N/A' }}</span>
                                            </td>
                                            <td class="info-cell">
                                                <strong>Domicile:</strong> <span
                                                    class="text-value">{{ $user->domicile ?? 'N/A' }}</span>
                                            </td>
                                            <td class="info-cell">
                                                <strong>Passport No:</strong> <span
                                                    class="text-value">{{ $user->passport_no ?? 'N/A' }}</span>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td class="info-cell">
                                                <strong>TIN Number:</strong> <span
                                                    class="text-value">{{ $user->tin_no ?? 'N/A' }}</span>
                                            </td>
                                            <td class="info-cell">
                                                <strong>NIDA Number:</strong> <span
                                                    class="text-value">{{ $user->NIN ?? 'N/A' }}</span>
                                            </td>
                                            <td class="info-cell">
                                                <strong>Professional Registration No:</strong> <span
                                                    class="text-value">{{ $user->professional_reg_number ?? 'N/A' }}</span>
                                            </td>
                                        </tr>
                                        <!-- Address & Contact Information -->
                                        <tr>
                                            <td class="info-cell">
                                                <strong>Region:</strong> <span
                                                    class="text-value">{{ $user->region ?? 'N/A' }}</span>
                                            </td>
                                            <td class="info-cell">
                                                <strong>District:</strong> <span
                                                    class="text-value">{{ $user->district ?? 'N/A' }}</span>
                                            </td>
                                            <td class="info-cell">
                                                <strong>Street:</strong> <span
                                                    class="text-value">{{ $user->street ?? 'N/A' }}</span>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td class="info-cell">
                                                <strong>Popular Landmark:</strong> <span
                                                    class="text-value">{{ $user->popular_landmark ?? 'N/A' }}</span>
                                            </td>
                                            <td class="info-cell">
                                                <strong>House No:</strong> <span
                                                    class="text-value">{{ $user->house_no ?? 'N/A' }}</span>
                                            </td>
                                            <td class="info-cell">
                                                <strong>Mobile:</strong> <span
                                                    class="text-value">{{ $user->mobile ?? 'N/A' }}</span>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td class="info-cell">
                                                <strong>Email:</strong> <span
                                                    class="text-value">{{ $user->email ?? 'N/A' }}</span>
                                            </td>
                                            <td class="info-cell">
                                                <strong>Street Address:</strong> <span
                                                    class="text-value">{{ $user->street ?? 'N/A' }}</span>
                                            </td>
                                            <td class="info-cell">
                                                <strong>NSSF No:</strong> <span
                                                    class="text-value">{{ $user->nssf_no ?? 'N/A' }}</span>
                                            </td>
                                        </tr>
                                    </table>

                                    <!-- CSS Styles -->
                                    <style>
                                        .user-details-table {
                                            width: 100%;
                                            border-collapse: collapse;
                                        }

                                        .info-cell {
                                            width: 33%;
                                            padding: 10px;
                                            vertical-align: middle;
                                            text-align: left;
                                            border: 1px solid #ccc;
                                        }

                                        .text-value {
                                            color: rgb(35, 30, 30);
                                        }

                                        .table-bordered {
                                            border: 1px solid #ccc;
                                        }

                                        .page-break {
                                            page-break-before: always;
                                            margin: 0;
                                        }
                                    </style>
                                </div>
                                <div class="page-break"></div>
                                <br>



                                <div class="personal-info-section">

                                    <!-- Family Data Section -->

                                    <div class="header-section text-center position-relative mb-3 hide-for-pdf">
                                        <img src="{{ asset('assets/img/line.png') }}" alt="Header Image"
                                            class="img-fluid">
                                        <div class="position-absolute fw-bold header-label">
                                            HR.8 v2022
                                        </div>
                                    </div>

                                    <!-- Title Section -->
                                    <div
                                        class="d-flex flex-column flex-md-row align-items-center justify-content-start hide-for-pdf2">
                                        <div class="text-center w-100">
                                            <div class="fw-bold title-text hide-for-pdf2">HR DETAILS FORM</div>
                                        </div>
                                        <img src="{{ asset('assets/img/ccbrt.jpg') }}" alt="CCBRT Logo"
                                            class="img-fluid logo-image hide-for-pdf2">
                                    </div>

                                    <style>
                                        /* Hide the header section in the browser */
                                        .hide-for-pdf {
                                            display: none;
                                        }

                                        .hide-for-pdf2 {
                                            display: none;
                                        }
                                    </style>

                                    <h4 class="section-title family-data-title mb-3">Family Data</h4>
                                    <table class="table table-bordered family-data-table">
                                        <thead class="table-light">
                                            <tr>
                                                <th class="text-center">#</th>
                                                <th>Name</th>
                                                <th>Relationship</th>
                                                <th>Occupation</th>
                                                <th>Phone Number</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($familyDetails as $index => $family)
                                                <tr>
                                                    <td class="text-center">{{ $index + 1 }}</td>
                                                    <td>{{ $family->full_name }}</td>
                                                    <td>{{ $family->relationship }}</td>
                                                    <td>{{ $family->occupation ?? 'N/A' }}</td>
                                                    <td>{{ $family->phone_number }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>

                                    <br>

                                    <!-- Next of Kin and Emergency Contact Details Section -->
                                    <h4 class="section-title next-of-kin-title mb-3">Next of Kin and Emergency Contact
                                        Details
                                    </h4>
                                    <table class="table table-bordered next-of-kin-table">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Name</th>
                                                <th>Relationship</th>
                                                <th>Phone Number</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($familyDetails as $family)
                                                @if ($family->next_of_kin)
                                                    <tr>
                                                        <td>{{ $family->full_name }}</td>
                                                        <td>{{ $family->relationship }}</td>
                                                        <td>{{ $family->phone_number }}</td>
                                                    </tr>
                                                @endif
                                            @endforeach
                                        </tbody>
                                    </table>

                                    <!-- CSS Styling -->
                                    <style>
                                        /* General Styles */
                                        body {
                                            font-family: Arial, sans-serif;
                                            margin: 0;
                                            padding: 0;
                                            box-sizing: border-box;
                                        }

                                        /* Title Styling */
                                        .section-title {
                                            color: #495057;
                                            font-size: 1.5rem;
                                            font-weight: bold;
                                            margin-bottom: 1rem;
                                        }

                                        /* Family Data Table Styling */
                                        .family-data-table,
                                        .next-of-kin-table {
                                            width: 100%;
                                            margin-bottom: 1.5rem;
                                            border-collapse: collapse;
                                        }

                                        /* Border for Tables */
                                        .family-data-table th,
                                        .family-data-table td,
                                        .next-of-kin-table th,
                                        .next-of-kin-table td {
                                            padding: 12px;
                                            text-align: left;
                                            border: 1px solid #ddd;
                                        }

                                        /* Heading for tables */
                                        .family-data-table th,
                                        .next-of-kin-table th {
                                            background-color: #f1f1f1;
                                            font-weight: bold;
                                        }

                                        /* Centered Numbering in Family Data Table */
                                        .family-data-table td.text-center {
                                            text-align: center;
                                        }

                                        /* Mobile Responsiveness */
                                        @media (max-width: 768px) {
                                            .section-title {
                                                font-size: 1.2rem;
                                            }

                                            .family-data-table th,
                                            .family-data-table td,
                                            .next-of-kin-table th,
                                            .next-of-kin-table td {
                                                padding: 8px;
                                            }

                                            .family-data-table,
                                            .next-of-kin-table {
                                                margin-bottom: 1rem;
                                            }
                                        }
                                    </style>
                                </div>
                                <br>

                                <div class="page-break"></div>

                                {{-- health details --}}
                                <br>
                                <div class="Health-info-section">
                                    <div class="header-section text-center position-relative mb-3 hide-for-pdf">
                                        <img src="{{ asset('assets/img/line.png') }}" alt="Header Image"
                                            class="img-fluid">
                                        <div class="position-absolute fw-bold header-label">
                                            HR.8 v2022
                                        </div>
                                    </div>

                                    <!-- Title Section -->
                                    <div
                                        class="d-flex flex-column flex-md-row align-items-center justify-content-start hide-for-pdf2">
                                        <div class="text-center w-100">
                                            <div class="fw-bold title-text hide-for-pdf2">HR DETAILS FORM</div>
                                        </div>
                                        <img src="{{ asset('assets/img/ccbrt.jpg') }}" alt="CCBRT Logo"
                                            class="img-fluid logo-image hide-for-pdf2">
                                    </div>

                                    <style>
                                        /* Hide the header section in the browser */
                                        .hide-for-pdf {
                                            display: none;
                                        }

                                        .hide-for-pdf2 {
                                            display: none;
                                        }
                                    </style>
                                    <h4 class="section-title mb-3" style="color: #495057; font-size: 1.5rem;">
                                        Health Data</h4>
                                    <table class="table table-bordered">
                                        <tr>
                                            <td class="w-33">
                                                <strong>Physical Disability:</strong> <span
                                                    style="color: rgb(35, 30, 30);">{{ $healthDetails->physical_disability ?? 'None' }}</span>
                                            </td>
                                            <td class="w-33">
                                                <strong>Blood Group:</strong> <span
                                                    style="color: rgb(35, 30, 30);">{{ $healthDetails->blood_group ?? 'Unknown' }}</span>
                                            </td>
                                            <td class="w-33">
                                                <strong>Insurance Name:</strong> <span
                                                    style="color: rgb(35, 30, 30);">{{ $healthDetails->insur_name ?? 'N/A' }}</span>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td class="w-33">
                                                <strong>Insurance Number:</strong> <span
                                                    style="color: rgb(35, 30, 30);">{{ $healthDetails->insur_no ?? 'N/A' }}</span>
                                            </td>
                                            <td class="w-33">
                                                <strong>Major Illness/Surgery:</strong> <span
                                                    style="color: rgb(35, 30, 30);">{{ $healthDetails->illness_history ?? 'None' }}</span>
                                            </td>
                                            <td class="w-33">
                                                <strong>Allergies:</strong> <span
                                                    style="color: rgb(35, 30, 30);">{{ $healthDetails->allergies ?? 'None' }}</span>
                                            </td>
                                        </tr>
                                    </table>

                                    <br>
                                    <!-- Language Knowledge Section -->
                                    <h4 class="section-title mb-3" style="color: #495057; font-size: 1.5rem;">
                                        Knowledge of
                                        Languages</h4>
                                    <table class="table table-bordered">
                                        <thead class="table-light">
                                            <tr>
                                                <th class="text-center">#</th>
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
                                                    <td>{{ $language->language }}</td>
                                                    <td>{{ $language->speaking }}</td>
                                                    <td>{{ $language->reading }}</td>
                                                    <td>{{ $language->writing }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                    <br>
                                    <!-- CCBRT Relationship Section -->
                                    @if ($relations->isNotEmpty())
                                        <h4 class="ccb-relationship-title">
                                            CCBRT Relationship
                                        </h4>

                                        <div class="ccb-relationship-table-container">
                                            <table class="ccb-relationship-table">
                                                <thead>
                                                    <tr>
                                                        <th class="ccb-relationship-text-center">#</th>
                                                        <th>Name</th>
                                                        <th>Relation</th>
                                                        <th>Department</th>
                                                        <th>Position</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach ($relations as $relation)
                                                        <tr>
                                                            <td class="ccb-relationship-text-center">
                                                                {{ $loop->iteration }}</td>
                                                            <td>{{ $relation->names }}</td>
                                                            <td>{{ $relation->relation }}</td>
                                                            <td>{{ $relation->dept_name ?? 'N/A' }}</td>
                                                            <td>{{ $relation->position }}</td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    @endif


                                    <style>
                                        /* Unique class for the section title */
                                        .ccb-relationship-title {
                                            color: #495057;
                                            font-size: 1.5rem;
                                            margin-bottom: 1rem;
                                        }

                                        /* Unique class for the table container (no border, same as other table) */
                                        .ccb-relationship-table-container {
                                            margin-bottom: 1rem;
                                        }

                                        /* Unique class for the table (matching the default Bootstrap width and border styles) */
                                        .ccb-relationship-table {
                                            width: 100%;
                                            /* Ensure it spans the full width like the other table */
                                            border: 1px solid #ddd;
                                            /* Use standard border style */
                                            border-collapse: collapse;
                                        }

                                        /* Table header styles to match the other table */
                                        .ccb-relationship-table thead {
                                            background-color: #f8f9fa;
                                        }

                                        /* Table cell padding and border, consistent with Bootstrap */
                                        .ccb-relationship-table th,
                                        .ccb-relationship-table td {
                                            padding: 8px 12px;
                                            /* Standard padding */
                                            text-align: left;
                                            border: 1px solid #ddd;
                                            /* Standard border style */
                                        }

                                        /* Center the text in the first column */
                                        .ccb-relationship-text-center {
                                            text-align: center;
                                        }

                                        /* Alternate row shading (matches Bootstrap's default row shading behavior) */
                                        .ccb-relationship-table tbody tr:nth-child(even) {
                                            background-color: #f2f2f2;
                                        }

                                        /* Bold table headers (like the other table) */
                                        .ccb-relationship-table th {
                                            font-weight: bold;
                                        }
                                    </style>
                                </div>

                                <div class="page-break"></div>
                                <br>
                                <div class="header-section text-center position-relative mb-3 hide-for-pdf">
                                    <img src="{{ asset('assets/img/line.png') }}" alt="Header Image" class="img-fluid">
                                    <div class="position-absolute fw-bold header-label">
                                        HR.8 v2022
                                    </div>
                                </div>

                                <!-- Title Section -->
                                <div
                                    class="d-flex flex-column flex-md-row align-items-center justify-content-start hide-for-pdf2">
                                    <div class="text-center w-100">
                                        <div class="fw-bold title-text hide-for-pdf2">HR DETAILS FORM</div>
                                    </div>
                                    <img src="{{ asset('assets/img/ccbrt.jpg') }}" alt="CCBRT Logo"
                                        class="img-fluid logo-image hide-for-pdf2">
                                </div>

                                <style>
                                    /* Hide the header section in the browser */
                                    .hide-for-pdf {
                                        display: none;
                                    }

                                    .hide-for-pdf2 {
                                        display: none;
                                    }
                                </style>
                                <h4 class="section-title mb-3" style="color: #495057; font-size: 1.5rem;">
                                    Disclosure of
                                    conflict of
                                    Interest
                                </h4>

                                @if ($user)
                                    <table class="table table-bordered">
                                        <!-- Table Header -->
                                        <thead>
                                            <tr>
                                                <th class="text-center" style="width: 5%"><strong>#</strong>
                                                </th>
                                                <th><strong>Question</strong></th>
                                                <th><strong>Answer</strong></th>
                                            </tr>
                                        </thead>

                                        <tbody>
                                            <!-- Question 1 -->
                                            <tr>
                                                <th class="text-center">1</th>
                                                <td>Are you or a member of your immediate family an officer,
                                                    director, trustee,
                                                    partner,
                                                    employee, or regularly retained consultant of any company
                                                    that presently has
                                                    business
                                                    dealings with CCBRT?</td>
                                                <td>{{ $user->conflict_officer_role }}</td>
                                            </tr>

                                            <!-- Justification for Question 1 -->
                                            @if ($user->conflict_officer_role === 'yes')
                                                <tr>
                                                    <th class="text-center"></th>
                                                    <td>Company name, position held, and nature of the business:
                                                        <span
                                                            style="text-decoration: underline;">{{ $user->officer_details }}</span>
                                                    </td>
                                                </tr>
                                            @endif

                                            <!-- Question 2 -->
                                            <tr>
                                                <th class="text-center">2</th>
                                                <td>Do you or a member of your family have a material financial
                                                    interest in a
                                                    company with
                                                    business dealings with CCBRT?</td>
                                                <td>{{ $user->financial_interest }}</td>
                                            </tr>

                                            <!-- Justification for Question 2 -->
                                            @if ($user->financial_interest === 'Yes')
                                                <tr>
                                                    <th class="text-center"></th>
                                                    <td>Please provide the details: <span
                                                            style="text-decoration: underline;">{{ $user->financial_details }}</span>
                                                    </td>
                                                </tr>
                                            @endif

                                            <!-- Question 3 -->
                                            <tr>
                                                <th class="text-center">3</th>
                                                <td>Do you or a member of your family have any other interests
                                                    that might create
                                                    a conflict
                                                    of interest?</td>
                                                <td>{{ $user->other_interests }}</td>
                                            </tr>

                                            <!-- Justification for Question 3 -->
                                            @if ($user->other_interests === 'Yes')
                                                <tr>
                                                    <th class="text-center"></th>
                                                    <td>Please provide details below: <span
                                                            style="text-decoration: underline;">{{ $user->interest_details }}</span>
                                                    </td>
                                                </tr>
                                            @endif

                                            <!-- Question 4 -->
                                            <tr>
                                                <th class="text-center">4</th>
                                                <td>Please declare CCBRT as your primary employer:</td>
                                                <td>{{ $user->primary_employer_ccbrt }}</td>
                                            </tr>

                                            <!-- Justification for Question 4 -->
                                            @if ($user->primary_employer_ccbrt === 'No')
                                                <tr>
                                                    <th class="text-center"></th>
                                                    <td>Please explain: <span
                                                            style="text-decoration: underline;">{{ $user->primary_employer_details }}</span>
                                                    </td>
                                                </tr>
                                            @endif

                                            <!-- Question 5 -->
                                            <tr>
                                                <th class="text-center">5</th>
                                                <td>Have you ever been involved in any court proceedings?</td>
                                                <td>{{ $user->court_proceedings }}</td>
                                            </tr>

                                            <!-- Justification for Question 5 -->
                                            @if ($user->court_proceedings === 'Yes')
                                                <tr>
                                                    <th class="text-center"></th>
                                                    <td>Please provide details below: <span
                                                            style="text-decoration: underline;">{{ $user->court_details }}</span>
                                                    </td>
                                                </tr>
                                            @endif
                                        </tbody>
                                    </table>
                                @else
                                    <p>No user authenticated</p>
                                @endif
                            </div>



                            <div class="border p-4 mb-4">
                                <h4 class="section-title mb-4" style="font-size: 1.5rem; color: #495057;">
                                    Declaration
                                </h4>
                                <p class=" mb-4" style="font-size: 1rem; color: #1d1e20;">
                                    I declare that the information provided in this form is true and correct to
                                    the best of my
                                    knowledge, and acknowledge that I will be liable to action against me as per
                                    the rules of
                                    the
                                    organization if, at any point of time during my employment with the
                                    organization, any of the
                                    above
                                    details are found to be untrue. I also undertake to periodically inform the
                                    organization and
                                    update
                                    the HR department in case of any relevant changes in the details mentioned
                                    above or on other
                                    relevant matters (e.g., completed courses, training).
                                </p>

                                <!-- Declaration Table -->
                                <table class="table table-bordered">
                                    <tbody>
                                        <!-- Full Name -->
                                        <tr>
                                            <th class="text-right" style="width: 30%;">Name of Employee:</th>
                                            <td style="color: rgb(35, 30, 30);">
                                                <strong>{{ $user->fname }} {{ $user->mname }}
                                                    {{ $user->lname }}</strong>
                                            </td>
                                        </tr>

                                        <!-- Signature -->
                                        <tr>
                                            <th class="text-right" style="width: 30%;">Signature:</th>
                                            <td style="color: rgb(35, 30, 30);">
                                                <div id="user_signature"
                                                    style="display: inline-block; border: none; padding: 0;">
                                                    @if ($user->signature)
                                                        <img src="data:image/png;base64,{{ $user->signature }}"
                                                            alt="User Signature" style="max-width: 120px; height: auto;">
                                                    @else
                                                        <p class="text-muted">No Signature</p>
                                                    @endif
                                                </div>
                                            </td>
                                        </tr>
                                        <!-- Date -->
                                        <tr>
                                            <th class="text-right" style="width: 30%;">Date:</th>
                                            <td style="color: rgb(43, 38, 38);">
                                                <strong>{{ \Carbon\Carbon::parse($user->created_at)->format('d F Y') }}</strong>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                                <!-- Include the SweetAlert2 script -->
                                <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.4.12/dist/sweetalert2.all.min.js"></script>
                            </div>
                            <!-- Buttons for Approve and Reject with Comment Section -->
                            <div class="mt-3">
                                <div class="d-flex justify-content-between mt-3">
                                    <button type="button" class="btn btn-primary"
                                        onclick="generatePDF()">Download</button>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.9.2/html2pdf.bundle.js"></script>

<script>
    function generatePDF() {
        const downloadButton = document.querySelector('.btn-primary');
        downloadButton.style.display = 'none';

        const element = document.querySelector('.form-container').cloneNode(true);

        const headerSections = element.querySelectorAll('.hide-for-pdf, .hide-for-pdf2'); // Target both classes
        headerSections.forEach(function(section) {
            section.style.display = 'block'; // Make the hidden section visible for PDF
        });

        const buttonToRemove = element.querySelector('.btn-primary');
        if (buttonToRemove) {
            buttonToRemove.remove(); // Remove the download button from the cloned content
        }

        window.scrollTo(0, 0);

        // Options for html2pdf
        const options = {
            margin: [10, 10, 10, 10],
            filename: 'Hr_Form.pdf',
            image: {
                type: 'jpeg',
                quality: 0.98
            },
            html2canvas: {
                scale: 2,
                scrollX: 0,
                scrollY: 0
            },
            jsPDF: {
                unit: 'mm',
                format: 'a4',
                orientation: 'portrait',
                putOnlyUsedFonts: true,
                compress: true,
                callback: function(doc) {

                    const pageCount = doc.internal.getNumberOfPages();

                    for (let i = 1; i <= pageCount; i++) {
                        doc.setPage(i);

                        doc.setFontSize(10);
                        doc.setTextColor(100, 100, 100);

                        const footerText = "Page " + i + " of " + pageCount;
                        // Page number is centered at the bottom
                        doc.text(footerText, 105, 297 - 10, {
                            align: 'center'
                        });
                    }
                }  
            }
        };

        html2pdf().from(element).set(options).save(); // Save the PDF
    }
</script>
