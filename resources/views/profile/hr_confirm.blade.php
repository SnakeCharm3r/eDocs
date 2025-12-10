@extends('layouts.template2')
@include('includes.loader')

@section('breadcrumb')
    <div class="content container-fluid" style="background-color: #ffffff;">
        <div class="page-header">
            <div class="row">
                <div class="col-sm-12">
                    <div class="page-sub-header">
                        <h3 class="page-title">HR Form</h3>
                    </div>
                </div>
            </div>
        </div>
        <style>
            @media (max-width: 768px) {
                .fw-bold {
                    font-size: 1.5rem !important;
                }
            }
        </style>
        <div class="container my-4">
            <!-- Header Section -->
            <div class="text-center position-relative mb-3">
                <img src="{{ asset('assets/img/line.png') }}" alt="Header Image" class="img-fluid" style="height: 35px;">
                <div class="position-absolute fw-bold"
                    style="right: 0; top: 50%; transform: translate(-10%, -50%); font-size: 1.2rem;
                            background-color: white; color: grey; padding: 5px 10px; border-radius: 5px;">
                    HR.8 v2022
                </div>
            </div>

            <!-- Title Section -->
            <div class="d-flex flex-column flex-md-row align-items-center justify-content-start">
                <div class="text-center w-100">
                    <div class="fw-bold" style="color: #459c51; font-size: 2rem;">HR DETAILS FORM</div>
                </div>
                <img src="{{ asset('assets/img/ccbrt.jpg') }}" alt="CCBRT Logo" class="img-fluid"
                    style="max-width: 80px; height: auto;">
            </div>

            <!-- User Details Section -->
            <div class="border p-4 mt-4 rounded shadow-sm">
                <table class="table table-sm table-borderless align-middle">
                    <tr>
                        <!-- Passport Photo Section -->
                        <td rowspan="2" class="text-center align-top" style="width: 20%; background-color: #f8f9fa;">
                            <strong class="d-block mb-2">Passport Photo</strong>
                            <div>
                                @if ($user->profile_picture)
                                    <!-- Display Passport Image -->
                                    <img src="{{ asset('storage/' . $user->profile_picture) }}" alt="Passport Image"
                                        class="img-fluid rounded border"
                                        style="max-width: 140px; max-height: 180px; width: auto; height: auto; object-fit: cover; object-position: center;">
                                @else
                                    <!-- Display message if no image is uploaded -->
                                    <p class="text-muted" style="font-size: 0.9rem;">No image uploaded</p>
                                @endif
                            </div>
                        </td>

                        <!-- Personal Information Row -->
                        <td class="p-3" colspan="3">
                            <table style="width: 100%; border-collapse: collapse;">
                                <tr>
                                    <td style="border-right: 1px solid #ccc; padding-right: 10px; width: 33%;">
                                        <strong>First Name:</strong>
                                        <span class="text-muted">{{ $user->fname }}</span>
                                    </td>
                                    <td style="border-right: 1px solid #ccc; padding-right: 10px; width: 33%;">
                                        <strong>Middle Name:</strong>
                                        <span class="text-muted">{{ $user->mname }}</span>
                                    </td>
                                    <td style="padding-right: 10px; width: 34%;">
                                        <strong>Surname:</strong>
                                        <span class="text-muted">{{ $user->lname }}</span>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <tr>
                        <!-- Job Information Row -->
                        <td class="p-3" colspan="2">
                            <div class="mb-3">
                                <strong> CCBRT Job Title:</strong>
                                <span class="text-muted">{{ $user->jobTitle->job_title ?? 'N/A' }}</span>
                            </div>
                            <div class="mb-3">
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
        </div>

        <div class="container my-5">
            <!-- Personal Information Section -->
            <div class="border p-4 mb-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h4 class="section-title mb-0" style="color: #495057; font-size: 1.5rem;">Personal Information</h4>
                    @if ($workflow)
                        @if ($isApproved)
                            <span class="badge badge-success" style="background-color: #28a745; color: white; padding: 8px 16px; font-size: 0.9rem;">
                                <i class="fas fa-check-circle"></i> Verified
                            </span>
                        @elseif ($isRejected)
                            <span class="badge badge-danger" style="background-color: #dc3545; color: white; padding: 8px 16px; font-size: 0.9rem;">
                                <i class="fas fa-times-circle"></i> HR Form Rejected
                            </span>
                        @endif
                    @else
                        <span class="badge badge-secondary" style="background-color: #6c757d; color: white; padding: 8px 16px; font-size: 0.9rem;">
                            <i class="fas fa-info-circle"></i> No Workflow Found
                        </span>
                    @endif
                </div>
                <table class="table table-bordered">
                    <!-- Personal Info Row 1 -->
                    <tr>
                        <td class="w-33">
                            <strong>Full Name:</strong> <span class="text-muted">{{ $user->fname }} {{ $user->mname }}
                                {{ $user->lname }}</span>
                        </td>
                        <td class="w-33">
                            <strong>Gender:</strong> <span
                                class="text-muted">{{ $user->gender === 'Male' ? 'Male' : 'Female' }}</span>
                        </td>
                        <td class="w-33">
                            <strong>Date of Birth:</strong> <span class="text-muted">{{ $user->DOB ?? 'N/A' }}</span>
                        </td>
                    </tr>
                    <tr>
                        <td class="w-33">
                            <strong>Marital Status:</strong> <span
                                class="text-muted">{{ $user->marital_status ?? 'N/A' }}</span>
                        </td>
                        <td class="w-33">
                            <strong>Nationality:</strong> <span class="text-muted">{{ $user->nationality ?? 'N/A' }}</span>
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
                            <strong>Passport No:</strong> <span class="text-muted">{{ $user->passport_no ?? 'N/A' }}</span>
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
                    <!-- Address & Contact Information -->
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
                            <strong>Street Address:</strong> <span class="text-muted">{{ $user->street ?? 'N/A' }}</span>
                        </td>
                        <td class="w-33">
                            <strong>NSSF No:</strong> <span class="text-muted">{{ $user->nssf_no ?? 'N/A' }}</span>
                        </td>
                    </tr>
                </table>

                <br>
                <h4 class="section-title mb-3" style="color: #495057; font-size: 1.5rem;">Family Data</h4>
                <table class="table table-bordered">
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
                <!-- Next of Kin and Emergency Contact Details -->
                <h4 class="section-title mb-3" style="color: #495057; font-size: 1.5rem;">Next of Kin and Emergency
                    Contact Details</h4>
                <table class="table table-bordered">
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

                <br>
                <!-- Health Data -->
                <h4 class="section-title mb-3" style="color: #495057; font-size: 1.5rem;">Health Data</h4>
                <table class="table table-bordered">
                    <tr>
                        <td class="w-33">
                            <strong>Physical Disability:</strong> <span
                                class="text-muted">{{ $healthDetails->physical_disability ?? 'None' }}</span>
                        </td>
                        <td class="w-33">
                            <strong>Blood Group:</strong> <span
                                class="text-muted">{{ $healthDetails->blood_group ?? 'Unknown' }}</span>
                        </td>
                        <td class="w-33">
                            <strong>Insurance Name:</strong> <span
                                class="text-muted">{{ $healthDetails->insur_name ?? 'N/A' }}</span>
                        </td>
                    </tr>
                    <tr>
                        <td class="w-33">
                            <strong>Insurance Number:</strong> <span
                                class="text-muted">{{ $healthDetails->insur_no ?? 'N/A' }}</span>
                        </td>
                        <td class="w-33">
                            <strong>Major Illness/Surgery:</strong> <span
                                class="text-muted">{{ $healthDetails->illness_history ?? 'None' }}</span>
                        </td>
                        <td class="w-33">
                            <strong>Allergies:</strong> <span
                                class="text-muted">{{ $healthDetails->allergies ?? 'None' }}</span>
                        </td>
                    </tr>
                </table>

                <br>
                <!-- Language Knowledge Section -->
                <h4 class="section-title mb-3" style="color: #495057; font-size: 1.5rem;">Knowledge of Languages</h4>
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
                    <h4 class="section-title mb-3" style="color: #495057; font-size: 1.5rem;">
                        CCBRT Relationship
                    </h4>
                    <div class="border p-4 mb-4">
                        <table class="table table-bordered table-striped">
                            <thead class="table-light">
                                <tr>
                                    <th class="text-center">#</th>
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
                                        <td>{{ $relation->relation }}</td>
                                        <td>{{ $relation->dept_name ?? 'N/A' }}</td>
                                        <td>{{ $relation->position }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif

                <h4 class="section-title mb-3" style="color: #495057; font-size: 1.5rem;">Disclosure of conflict of
                    Interest
                </h4>

                @if ($user)
                    <table class="table table-bordered">
                        <!-- Table Header -->
                        <thead>
                            <tr>
                                <th class="text-center" style="width: 5%"><strong>#</strong></th>
                                <th><strong>Question</strong></th>
                                <th><strong>Answer</strong></th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Question 1 -->
                            <tr>
                                <th class="text-center">1</th>
                                <td>Are you or a member of your immediate family an officer, director, trustee, partner,
                                    employee, or regularly retained consultant of any company that presently has business
                                    dealings with CCBRT?</td>
                                <td>{{ $user->conflict_officer_role }}</td>
                            </tr>
                            <!-- Justification for Question 1 -->
                            @if ($user->conflict_officer_role === 'yes')
                                <tr>
                                    <th class="text-center"></th>
                                    <td>Company name, position held, and nature of the business:
                                        <span style="text-decoration: underline;">{{ $user->officer_details }}</span>
                                    </td>
                                </tr>
                            @endif
                            <!-- Question 2 -->
                            <tr>
                                <th class="text-center">2</th>
                                <td>Do you or a member of your family have a material financial interest in a company with
                                    business dealings with CCBRT?</td>
                                <td>{{ $user->financial_interest }}</td>
                            </tr>
                            <!-- Justification for Question 2 -->
                            @if ($user->financial_interest === 'Yes')
                                <tr>
                                    <th class="text-center"></th>
                                    <td>Please provide the details: <span
                                            style="text-decoration: underline;">{{ $user->financial_details }}</span></td>
                                </tr>
                            @endif
                            <!-- Question 3 -->
                            <tr>
                                <th class="text-center">3</th>
                                <td>Do you or a member of your family have any other interests that might create a conflict
                                    of interest?</td>
                                <td>{{ $user->other_interests }}</td>
                            </tr>
                            <!-- Justification for Question 3 -->
                            @if ($user->other_interests === 'Yes')
                                <tr>
                                    <th class="text-center"></th>
                                    <td>Please provide details below: <span
                                            style="text-decoration: underline;">{{ $user->interest_details }}</span></td>
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
                                            style="text-decoration: underline;">{{ $user->court_details }}</span></td>
                                </tr>
                            @endif
                        </tbody>
                    </table>
                @else
                    <p>No user authenticated</p>
                @endif
            </div>

            @if ($user)
                <div class="border p-4 mb-4">
                    <h5 class="section-title mb-3" style="color: #495057; font-size: 1.5rem;">User Attachments</h4>
                        @php
                            $documents = [
                                'NIDA' => $user->nida,
                                'Driving License' => $user->driving_license,
                                'Transport ID' => $user->transport_id,
                                'Voting ID' => $user->voting_id,
                                'Marriage Certificate' => $user->marriage_certificate,
                                'Divorce Certificate' => $user->divorced_certificate ?? $user->divorce_certificate,
                                'Employee CV' => $user->employee_cv,
                            ];
                        @endphp

                        @php $docIndex = 1; @endphp
                        <table class="table table-bordered">
                            <thead class="table-light">
                                <tr>
                                    <th class="text-center" style="width: 5%;">#</th>
                                    <th>Document attached</th>
                                    {{-- <th>Preview</th> --}}
                                    <th class="text-center" style="width: 10%;">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($documents as $label => $path)
                                    @if ($path)
                                        <tr>
                                            <td class="text-center">{{ $docIndex++ }}</td>
                                            <td>{{ $label }}</td>
                                            {{-- <td>
                                @if (Str::endsWith($path, ['.jpg', '.jpeg', '.png']))
                                    <img src="{{ asset('storage/' . $path) }}" alt="{{ $label }}" width="80">
                                @else
                                    {{ basename($path) }}
                                @endif
                            </td> --}}
                                            <td class="text-center">
                                                <a href="{{ asset('storage/' . $path) }}" target="_blank"
                                                    class="btn btn-sm btn-primary">
                                                    View
                                                </a>
                                                {{-- <a href="{{ asset('storage/' . $path) }}" download class="btn btn-sm btn-secondary">
                                    Download
                                </a> --}}
                                            </td>
                                        </tr>
                                    @endif
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center text-muted">No attachments found.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                </div>
            @else
                <div class="border p-4 mb-4">
                    <h4 class="section-title mb-3" style="color: #495057; font-size: 1.5rem;">User Attachments</h4>
                    <p class="text-muted">No user found.</p>
                </div>
            @endif


            <div class="border p-4 mb-4">
                <h4 class="section-title mb-4" style="font-size: 1.5rem; color: #495057;">
                    Declaration
                </h4>
                <p class="mb-4" style="font-size: 1rem; color: #6c757d;">
                    I declare that the information provided in this form is true and correct to the best of my
                    knowledge, and acknowledge that I will be liable to action against me as per the rules of the
                    organization if, at any point of time during my employment with the organization, any of the above
                    details are found to be untrue. I also undertake to periodically inform the organization and update
                    the HR department in case of any relevant changes in the details mentioned above or on other
                    relevant matters (e.g., completed courses, training).
                </p>

                <!-- Declaration Table -->
                <table class="table table-bordered">
                    <tbody>
                        <!-- Full Name -->
                        <tr>
                            <th class="text-right" style="width: 30%;">Name of Employee:</th>
                            <td class="text-muted">
                                <strong>{{ $user->fname }} {{ $user->mname }} {{ $user->lname }}</strong>
                            </td>
                        </tr>
                        <!-- Signature -->
                        <tr>
                            <th class="text-right" style="width: 30%;">Signature:</th>
                            <td class="text-muted">
                                <div id="user_signature" style="display: inline-block; border: none; padding: 0;">
                                    @if ($user->signature)
                                        <img src="data:image/png;base64,{{ $user->signature }}" alt="User Signature"
                                            style="max-width: 120px; height: auto;">
                                    @else
                                        <p class="text-muted">No Signature</p>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        <!-- Date -->
                        <tr>
                            <th class="text-right" style="width: 30%;">Date:</th>
                            <td class="text-muted">
                                <strong>{{ \Carbon\Carbon::now()->format('d F, Y') }}</strong>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Professional Registration Verification Section (for Clinical Departments) -->
            @if ($isClinicalDepartment && $user->professional_reg_number && !$isApproved && !$isRejected)
                <div class="border p-4 mb-4" style="background-color: #fff3cd; border-left: 4px solid #ffc107;">
                    <h4 class="section-title mb-3" style="color: #856404; font-size: 1.5rem;">
                        <i class="fas fa-certificate"></i> Professional Registration Verification
                        <span class="badge badge-warning">Required for Clinical Departments</span>
                    </h4>
                    
                    @if ($currentHrHistory && $currentHrHistory->professional_reg_verified)
                        <div class="alert alert-success">
                            <i class="fas fa-check-circle"></i> 
                            <strong>Verified:</strong> Professional registration has been verified.
                        </div>
                        <div class="card mt-3">
                            <div class="card-body">
                                <h5 class="mb-3"><strong>Verification Details:</strong></h5>
                                <table class="table table-bordered">
                                    <tbody>
                                        <tr>
                                            <td style="width: 30%; background-color: #f8f9fa;"><strong>Professional Registration Number:</strong></td>
                                            <td style="font-size: 1.1rem; font-weight: 600; color: #007A33;">{{ $user->professional_reg_number }}</td>
                                        </tr>
                                        @if ($currentHrHistory->license_provider)
                                            <tr>
                                                <td style="background-color: #f8f9fa;"><strong>License Provider:</strong></td>
                                                <td>{{ $currentHrHistory->license_provider }}</td>
                                            </tr>
                                        @endif
                                        @if ($currentHrHistory->license_valid_until)
                                            <tr>
                                                <td style="background-color: #f8f9fa;"><strong>License Valid Until:</strong></td>
                                                <td>
                                                    {{ \Carbon\Carbon::parse($currentHrHistory->license_valid_until)->format('d F Y') }}
                                                    @php
                                                        $daysUntilExpiry = \Carbon\Carbon::parse($currentHrHistory->license_valid_until)->diffInDays(\Carbon\Carbon::now(), false);
                                                    @endphp
                                                    @if ($daysUntilExpiry < 0)
                                                        <span class="badge badge-success ml-2">Valid</span>
                                                    @elseif ($daysUntilExpiry <= 90)
                                                        <span class="badge badge-warning ml-2">Expiring Soon ({{ abs($daysUntilExpiry) }} days)</span>
                                                    @else
                                                        <span class="badge badge-danger ml-2">Expired</span>
                                                    @endif
                                                </td>
                                            </tr>
                                        @endif
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @else
                        @php
                            // Parse professional registration number to extract type
                            $regType = '';
                            $regNumber = $user->professional_reg_number ?? '';
                            $licenseProviderName = '';
                            
                            if (preg_match('/^([A-Z]+):\s*(.+)$/', $regNumber, $matches)) {
                                $regType = $matches[1];
                                $regNumber = $matches[1] . ': ' . $matches[2];
                                
                                // Map type codes to full names
                                $licenseProviders = [
                                    'MCT' => 'Medical Council of Tanzania',
                                    'TNMC' => 'Tanzania Nursing and Midwifery Council',
                                    'TPB' => 'Tanzania Pharmacy Board',
                                    'TPC' => 'Tanzania Physiotherapy Council',
                                    'TMDC' => 'Tanzania Medical and Dental Council',
                                    'Other' => 'Other'
                                ];
                                
                                $licenseProviderName = $licenseProviders[$regType] ?? $regType;
                            } else {
                                $regNumber = $user->professional_reg_number ?? '';
                            }
                        @endphp
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i> 
                            <strong>Verification Required:</strong> Please verify that the professional registration number is active and the user is working under a provider of that license.
                        </div>

                        <div class="card">
                            <div class="card-body">
                                <div class="form-group mb-3">
                                    <label><strong>Professional Registration Number:</strong></label>
                                    <p class="form-control-plaintext" style="font-size: 1.1rem; font-weight: 600; color: #007A33;">
                                        {{ $user->professional_reg_number }}
                                    </p>
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group mb-3">
                                            <label for="license_valid_until"><strong>License Valid Until:</strong> <span class="text-danger">*</span></label>
                                            <input type="date" class="form-control" id="license_valid_until" 
                                                value="{{ $currentHrHistory->license_valid_until ?? '' }}" 
                                                min="{{ date('Y-m-d') }}">
                                            <small class="form-text text-muted">
                                                Enter the expiration date of the professional license
                                            </small>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group mb-3">
                                            <label for="license_provider"><strong>License Provider/Authority:</strong></label>
                                            <input type="text" class="form-control" id="license_provider" 
                                                value="{{ $currentHrHistory->license_provider ?? $licenseProviderName }}" 
                                                maxlength="255" readonly style="background-color: #e9ecef;">
                                            <small class="form-text text-muted">
                                                Auto-filled based on registration type
                                            </small>
                                        </div>
                                    </div>
                                </div>

                                <div class="form-group mb-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="professional_reg_verified" 
                                            {{ ($currentHrHistory && $currentHrHistory->professional_reg_verified) ? 'checked' : '' }}>
                                        <label class="form-check-label" for="professional_reg_verified">
                                            <strong>I verify that the professional registration number is active, valid, and the user is working under a provider of this license.</strong>
                                        </label>
                                    </div>
                                </div>

                                <div class="d-flex gap-2">
                                    <button type="button" class="btn btn-primary" id="saveVerificationBtn" 
                                        style="background-color: #007A33; border-color: #007A33;">
                                        <i class="fas fa-save"></i> Save Verification
                                    </button>
                                    <span id="verificationStatus" class="ml-3 align-self-center"></span>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            @elseif ($isClinicalDepartment && $user->professional_reg_number && ($isApproved || $isRejected) && $currentHrHistory && $currentHrHistory->professional_reg_verified)
                <!-- Show verification status if already verified and form is approved/rejected -->
                <div class="border p-4 mb-4" style="background-color: #d4edda; border-left: 4px solid #28a745;">
                    <h4 class="section-title mb-3" style="color: #155724; font-size: 1.5rem;">
                        <i class="fas fa-certificate"></i> Professional Registration Verification
                    </h4>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i> 
                        <strong>Verified:</strong> Professional registration has been verified.
                    </div>
                    <div class="card mt-3">
                        <div class="card-body">
                            <h5 class="mb-3"><strong>Verification Details:</strong></h5>
                            <table class="table table-bordered">
                                <tbody>
                                    <tr>
                                        <td style="width: 30%; background-color: #f8f9fa;"><strong>Professional Registration Number:</strong></td>
                                        <td style="font-size: 1.1rem; font-weight: 600; color: #007A33;">{{ $user->professional_reg_number }}</td>
                                    </tr>
                                    @if ($currentHrHistory->license_provider)
                                        <tr>
                                            <td style="background-color: #f8f9fa;"><strong>License Provider:</strong></td>
                                            <td>{{ $currentHrHistory->license_provider }}</td>
                                        </tr>
                                    @endif
                                    @if ($currentHrHistory->license_valid_until)
                                        <tr>
                                            <td style="background-color: #f8f9fa;"><strong>License Valid Until:</strong></td>
                                            <td>
                                                {{ \Carbon\Carbon::parse($currentHrHistory->license_valid_until)->format('d F Y') }}
                                                @php
                                                    $daysUntilExpiry = \Carbon\Carbon::parse($currentHrHistory->license_valid_until)->diffInDays(\Carbon\Carbon::now(), false);
                                                @endphp
                                                @if ($daysUntilExpiry < 0)
                                                    <span class="badge badge-success ml-2">Valid</span>
                                                @elseif ($daysUntilExpiry <= 90)
                                                    <span class="badge badge-warning ml-2">Expiring Soon ({{ abs($daysUntilExpiry) }} days)</span>
                                                @else
                                                    <span class="badge badge-danger ml-2">Expired</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endif
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            @endif

            <!-- Status Display if Approved or Rejected -->
            @if ($isApproved)
                <div class="border p-4 mb-4" style="background-color: #d4edda; border-left: 4px solid #28a745;">
                    <div class="d-flex align-items-center justify-content-between">
                        <div class="w-100">
                            <h4 class="mb-3" style="color: #155724;">
                                <i class="fas fa-check-circle"></i> Form Approved
                            </h4>
                            <p class="mb-2 text-muted">
                                This HR form has been approved. The user's status has been updated to active.
                            </p>
                            @if ($workflow && $workflow->work_flow_status)
                                <p class="mb-3">
                                    <strong>Status:</strong> <span class="badge badge-success">{{ $workflow->work_flow_status }}</span>
                                </p>
                            @endif
                            
                            <!-- Approval Details -->
                            @if ($approver)
                                <div class="mt-4 pt-3 border-top">
                                    <h5 class="mb-3" style="color: #155724; font-size: 1.1rem;">
                                        <i class="fas fa-user-check"></i> Approval Details
                                    </h5>
                                    <table class="table table-bordered" style="background-color: white;">
                                        <tbody>
                                            <tr>
                                                <td style="width: 30%; background-color: #f8f9fa;">
                                                    <strong>Approved By:</strong>
                                                </td>
                                                <td>
                                                    {{ $approver->fname }} {{ $approver->mname }} {{ $approver->lname }}
                                                </td>
                                            </tr>
                                            <tr>
                                                <td style="background-color: #f8f9fa;">
                                                    <strong>Signature:</strong>
                                                </td>
                                                <td>
                                                    @if ($approver->signature)
                                                        <div style="display: inline-block; border: 1px solid #dee2e6; padding: 5px; background-color: white;">
                                                            <img src="data:image/png;base64,{{ $approver->signature }}" 
                                                                alt="Approver Signature" 
                                                                style="max-width: 200px; height: auto; display: block;">
                                                        </div>
                                                    @else
                                                        <span class="text-muted">No signature available</span>
                                                    @endif
                                                </td>
                                            </tr>
                                            <tr>
                                                <td style="background-color: #f8f9fa;">
                                                    <strong>Date Approved:</strong>
                                                </td>
                                                <td>
                                                    @if ($approvalDate)
                                                        {{ \Carbon\Carbon::parse($approvalDate)->format('d F Y, h:i A') }}
                                                    @else
                                                        <span class="text-muted">N/A</span>
                                                    @endif
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            @elseif ($isRejected)
                <div class="border p-4 mb-4" style="background-color: #f8d7da; border-left: 4px solid #dc3545;">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <h4 class="mb-2" style="color: #721c24;">
                                <i class="fas fa-times-circle"></i> Form Rejected
                            </h4>
                            <p class="mb-0 text-muted">
                                This HR form has been rejected.
                            </p>
                            @php
                                $rejectedHistory = \App\Models\WorkFlowHistory::where('work_flow_id', $workflow->id)
                                    ->where('attended_by', Auth::id())
                                    ->where('status', 2)
                                    ->first();
                            @endphp
                            @if ($rejectedHistory && $rejectedHistory->rejection_reason)
                                <p class="mb-0 mt-2">
                                    <strong>Rejection Reason:</strong> {{ $rejectedHistory->rejection_reason }}
                                </p>
                            @endif
                        </div>
                    </div>
                </div>
            @endif

            <!-- Buttons for Approve and Reject (only show if not approved/rejected) -->
            @if (!$isApproved && !$isRejected)
                <div class="mt-3">
                    <div class="d-flex justify-content-center mt-3 gap-2">
                        <button class="btn btn-secondary" id="backButton">Back</button>

                        <script>
                            document.getElementById('backButton').addEventListener('click', function() {
                                window.location.href = '/requestapprove';
                            });
                        </script>
                        <button class="btn btn-success" id="approveButton">Approve</button>
                        <button class="btn btn-danger" id="rejectButton">Reject</button>
                    </div>
                </div>
            @else
                <!-- Back button only if approved/rejected -->
                <div class="mt-3">
                    <div class="d-flex justify-content-center mt-3">
                        <button class="btn btn-secondary" id="backButton">Back</button>
                        <script>
                            document.getElementById('backButton').addEventListener('click', function() {
                                window.location.href = '/requestapprove';
                            });
                        </script>
                    </div>
                </div>
            @endif
        </div>

        <!-- Consolidated JavaScript -->
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.4.12/dist/sweetalert2.all.min.js"></script>
        <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
        <script>
            @if ($isClinicalDepartment && $user->professional_reg_number && !$isApproved && !$isRejected && (!$currentHrHistory || !$currentHrHistory->professional_reg_verified))
            // Professional Registration Verification Handler
            const saveVerificationBtn = document.getElementById('saveVerificationBtn');
            if (saveVerificationBtn) {
                saveVerificationBtn.addEventListener('click', function() {
                const verifiedCheckbox = document.getElementById('professional_reg_verified');
                let verified = verifiedCheckbox.checked;
                const userId = {{ $user->id }};
                const statusSpan = document.getElementById('verificationStatus');

                // License valid until is required when verifying
                const licenseValidUntil = document.getElementById('license_valid_until').value;
                if (verified && !licenseValidUntil) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'License Valid Until Required',
                        text: 'Please enter the license expiration date before verifying.',
                        confirmButtonColor: '#007A33'
                    });
                    return;
                }

                // Warn if unchecking verification
                if (!verified && verifiedCheckbox.hasAttribute('data-verified')) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Unverify Registration?',
                        text: 'Are you sure you want to mark this as not verified?',
                        showCancelButton: true,
                        confirmButtonColor: '#007A33',
                        cancelButtonColor: '#6c757d',
                        confirmButtonText: 'Yes, unverify',
                        cancelButtonText: 'Cancel'
                    }).then((result) => {
                        if (!result.isConfirmed) {
                            verifiedCheckbox.checked = true;
                            return;
                        }
                        // Continue with save
                        verified = false;
                        saveVerification();
                    });
                    return;
                }

                // Save verification
                saveVerification();

                function saveVerification() {
                    // Get current values
                    const currentVerified = document.getElementById('professional_reg_verified').checked;
                    const licenseValidUntil = document.getElementById('license_valid_until').value;
                    const licenseProvider = document.getElementById('license_provider').value;

                    // Validate required fields if verifying
                    if (currentVerified && !licenseValidUntil) {
                        Swal.fire({
                            icon: 'warning',
                            title: 'License Valid Until Required',
                            text: 'Please enter the license expiration date before verifying.',
                            confirmButtonColor: '#007A33'
                        });
                        return;
                    }

                    // Show loading
                    const btn = document.getElementById('saveVerificationBtn');
                    const originalText = btn.innerHTML;
                    btn.disabled = true;
                    btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Saving...';

                    $.ajax({
                        url: '{{ route("hr_form.verify_professional_reg") }}',
                        method: 'POST',
                        dataType: 'json',
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json'
                        },
                        data: {
                            user_id: userId,
                            verified: currentVerified ? 1 : 0,
                            license_valid_until: licenseValidUntil,
                            license_provider: licenseProvider,
                            _token: '{{ csrf_token() }}'
                        },
                        success: function(response) {
                            btn.disabled = false;
                            btn.innerHTML = originalText;
                            
                            if (response.success) {
                                const verifiedCheckbox = document.getElementById('professional_reg_verified');
                                if (currentVerified) {
                                    statusSpan.innerHTML = '<span class="badge badge-success"><i class="fas fa-check-circle"></i> Verified</span>';
                                    // Mark checkbox as verified in the UI
                                    verifiedCheckbox.setAttribute('data-verified', 'true');
                                    verifiedCheckbox.setAttribute('data-saved', 'true');
                                } else {
                                    statusSpan.innerHTML = '<span class="badge badge-secondary">Not Verified</span>';
                                    verifiedCheckbox.removeAttribute('data-verified');
                                    verifiedCheckbox.removeAttribute('data-saved');
                                }
                                
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Success',
                                    text: response.message,
                                    confirmButtonColor: '#007A33',
                                    timer: 2000,
                                    timerProgressBar: true
                                });
                            }
                        },
                        error: function(xhr) {
                            btn.disabled = false;
                            btn.innerHTML = originalText;
                            let errorMsg = 'Failed to save verification.';
                            if (xhr.responseJSON && xhr.responseJSON.message) {
                                errorMsg = xhr.responseJSON.message;
                            } else if (xhr.responseText) {
                                try {
                                    const response = JSON.parse(xhr.responseText);
                                    if (response.message) {
                                        errorMsg = response.message;
                                    }
                                } catch (e) {
                                    // Use default message
                                }
                            }
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: errorMsg,
                                confirmButtonColor: '#007A33'
                            });
                        }
                    });
                }
            });
            }
            @endif

            // Approve Button Handler
            @if (!$isApproved && !$isRejected)
            const approveButton = document.getElementById('approveButton');
            if (approveButton) {
                approveButton.addEventListener('click', function() {
                @if ($isClinicalDepartment && $user->professional_reg_number)
                // Check if professional registration is verified for clinical departments
                // Check if checkbox exists and is checked, OR if verification section shows "already verified"
                const verificationCheckbox = document.getElementById('professional_reg_verified');
                const isVerified = verificationCheckbox ? verificationCheckbox.checked : 
                    {{ ($currentHrHistory && $currentHrHistory->professional_reg_verified) ? 'true' : 'false' }};
                
                if (!isVerified) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Verification Required',
                        text: 'Please verify the professional registration number before approving. This is required for clinical departments.',
                        confirmButtonColor: '#007A33',
                        confirmButtonText: 'OK'
                    });
                    return;
                }
                @endif

                var comment = document.getElementById('approvalComment') ? document.getElementById('approvalComment')
                    .value : '';
                var id = {{ $user->id }};

                Swal.fire({
                    title: 'Are you sure?',
                    text: 'Do you want to approve this request?',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Yes, approve it!',
                    cancelButtonText: 'No, cancel',
                    reverseButtons: true
                }).then((result) => {
                    if (result.isConfirmed) {
                        // Show loading spinner
                        Swal.fire({
                            title: 'Processing...',
                            text: 'Please wait while the request is being approved.',
                            allowOutsideClick: false,
                            allowEscapeKey: false,
                            didOpen: () => {
                                Swal.showLoading();
                            }
                        });

                        // Send AJAX request
                        $.ajax({
                            url: '/hr_form_approve/' + id,
                            method: 'POST',
                            dataType: 'json',
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest',
                                'Accept': 'application/json'
                            },
                            data: {
                                status: 'approved',
                                comment: comment,
                                _token: '{{ csrf_token() }}'
                            },
                            success: function(response) {
                                if (response.success) {
                                    Swal.fire({
                                        icon: 'success',
                                        title: 'Success!',
                                        text: response.message || 'HR form approved successfully.',
                                        confirmButtonColor: '#007A33',
                                        confirmButtonText: 'OK'
                                    }).then(() => {
                                        window.location.href = '/requestapprove';
                                    });
                                } else {
                                    Swal.fire({
                                        icon: 'error',
                                        title: 'Error!',
                                        text: response.message || 'There was an error approving the request.',
                                        confirmButtonColor: '#007A33',
                                        confirmButtonText: 'OK'
                                    });
                                }
                            },
                            error: function(xhr, status, error) {
                                console.error('AJAX error:', xhr.responseText);
                                let errorMessage = 'There was an error approving the request.';
                                
                                // Try to parse JSON error response
                                if (xhr.responseJSON && xhr.responseJSON.message) {
                                    errorMessage = xhr.responseJSON.message;
                                } else if (xhr.responseText) {
                                    try {
                                        const response = JSON.parse(xhr.responseText);
                                        if (response.message) {
                                            errorMessage = response.message;
                                        }
                                    } catch (e) {
                                        // If not JSON, use default message
                                    }
                                }

                                Swal.fire({
                                    icon: 'error',
                                    title: 'Error!',
                                    text: errorMessage,
                                    confirmButtonColor: '#007A33',
                                    confirmButtonText: 'OK'
                                });
                            }
                        });
                    }
                });
            });
            }
            @endif

            // Reject Button Handler
            @if (!$isApproved && !$isRejected)
            const rejectButton = document.getElementById('rejectButton');
            if (rejectButton) {
                rejectButton.addEventListener('click', function() {
                var id = {{ $user->id }};

                Swal.fire({
                    title: 'Are you sure?',
                    text: 'You are about to reject this submission.',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Reject',
                    cancelButtonText: 'Cancel',
                    input: 'textarea',
                    inputPlaceholder: 'Please provide a rejection reason...',
                    inputAttributes: {
                        'aria-label': 'Type your rejection reason here'
                    },
                    showLoaderOnConfirm: true,
                    preConfirm: (comment) => {
                        if (!comment) {
                            Swal.showValidationMessage('Please provide a rejection reason');
                            return false;
                        }
                        return comment;
                    }
                }).then((result) => {
                    if (result.isConfirmed) {
                        var comment = result.value;

                        // Show loading spinner
                        Swal.fire({
                            title: 'Processing...',
                            text: 'Please wait while the request is being rejected.',
                            allowOutsideClick: false,
                            allowEscapeKey: false,
                            didOpen: () => {
                                Swal.showLoading();
                            }
                        });

                        // Send AJAX request
                        $.ajax({
                            url: '/hr_form_reject',
                            method: 'POST',
                            data: {
                                id: id,
                                status: 'rejected',
                                comment: comment,
                                _token: '{{ csrf_token() }}'
                            },
                            success: function(response) {
                                // Skip success dialog, redirect immediately
                                window.location.href = '/requestapprove';
                            },
                            error: function(xhr, status, error) {
                                console.error('AJAX error:', xhr.responseText);
                                if (xhr.status === 302 || xhr.getResponseHeader('Location')) {
                                    // Server sent a redirect, force page reload to follow it
                                    window.location.reload(true);
                                } else {
                                    Swal.fire({
                                        title: 'Error!',
                                        text: 'There was an error rejecting the submission.',
                                        icon: 'error',
                                        confirmButtonText: 'OK'
                                    });
                                }
                            }
                        });
                    }
                });
            });
            }
            @endif
        </script>
    </div>
@endsection
