<!-- resources/views/profile/show.blade.php -->
@extends('layouts.template')

@section('breadcrumb')
    @include('sweetalert::alert')

    <div class="page-wrapper">
        <div class="content container-fluid">
            <div class="page-header">
                <div class="row">
                    <div class="col-sm-12">
                        <h3 class="page-title">User Profile</h3>
                    </div>
                </div>
            </div>

            <link href="https://maxcdn.bootstrapcdn.com/font-awesome/4.3.0/css/font-awesome.min.css" rel="stylesheet">

            <div class="container">
                <div class="row flex-lg-nowrap">
                    <div class="col">
                        <div class="card mb-3">
                            <div class="card-body">
                                <div class="e-profile">

                                    <!-- Display Validation Errors -->
                                    @if ($errors->any())
                                        <div class="alert alert-danger"></div>
                                        <ul class="mb-0">
                                            @foreach ($errors->all() as $error)
                                                <li>{{ $error }}</li>
                                            @endforeach
                                        </ul>
                                </div>
                                @endif
                                @if (session('success'))
                                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                                        {{ session('success') }}
                                        <button type="button" class="btn-close" data-bs-dismiss="alert"
                                            aria-label="Close"></button>
                                    </div>
                                @endif


                                <div class="row">
                                    <!-- Profile Picture Section -->
                                    <div class="col-12 col-sm-auto mb-3 text-center">
                                        <div class="mx-auto" style="width: 140px; position: relative;">
                                            <div class="d-flex justify-content-center align-items-center rounded"
                                                style="height: 140px; background-color: rgb(233, 236, 239);">

                                                @if ($user->profile_picture)
                                                    <!-- Profile Picture -->
                                                    <img src="{{ asset('storage/' . $user->profile_picture) }}"
                                                        alt="Profile Picture" class="img-fluid rounded-circle"
                                                        style="max-width: 140px; height: 140px; border: 2px solid #ccc; padding: 5px; object-fit: cover;">

                                                    <!-- Delete Button -->
                                                    <form action="{{ route('profile.delete.picture') }}" method="POST"
                                                        style="position: absolute; top: 5px; right: 5px; z-index: 10;">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="btn btn-danger btn-sm rounded-circle"
                                                            title="Delete Picture">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </form>
                                                @else
                                                    <!-- Default User Icon -->
                                                    <img src="{{ asset('assets/img/icon.png') }}" alt="Default User Icon"
                                                        class="img-fluid rounded-circle"
                                                        style="max-width: 160px; height: 140px; padding: 1px; object-fit: cover;">
                                                @endif

                                                <!-- Profile Picture Update Form (Hidden) -->
                                                <form id="profilePictureForm" action="{{ route('profile.update.picture') }}"
                                                    method="POST" enctype="multipart/form-data" style="display: none;">
                                                    @csrf
                                                    <input type="file" class="form-control" id="profile_picture"
                                                        name="profile_picture" accept="image/*"
                                                        onchange="handleProfilePictureChange(this)">
                                                </form>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- User Info Section -->
                                    <div class="col d-flex flex-column flex-sm-row justify-content-between mb-3">
                                        <div class="text-center text-sm-left mb-2 mb-sm-0">
                                            <h4 class="pt-sm-2 pb-1 mb-0 text-nowrap">{{ $user->username }}</h4>
                                            <p class="mb-0">{{ $user->email }}</p>
                                            <p class="mb-0">{{ $user->department->name }}</p>
                                            <div class="mt-2">
                                                <button class="btn btn-primary" type="button"
                                                    onclick="document.getElementById('profile_picture').click()">
                                                    <i class="fa fa-fw fa-camera"></i>
                                                    <span>Change Photo</span>
                                                </button>
                                            </div>
                                        </div>
                                    </div>

                                    <script>
                                        function handleProfilePictureChange(input) {
                                            document.getElementById('profilePictureForm').submit();
                                        }
                                    </script>
                                </div>

                                <!-- Navigation Tabs -->
                                <ul class="nav nav-tabs">
                                    <li class="nav-item">
                                        <a href="{{ route('profile.index') }}" class="active nav-link">User Info</a>
                                    </li>
                                    <li class="nav-item">
                                        <a href="{{ route('policies.user') }}" class="nav-link">Policies</a>
                                    </li>
                                    <li class="nav-item">
                                        <a href="{{ route('user_profile.pass') }}" class="nav-link">Password</a>
                                    </li>

                                </ul>

                                <div class="mt-4">
                                    <div class="row">
                                        <!-- First Column: Personal Information Table -->
                                        <div class="col-md-6">
                                            <table class="table table-bordered">
                                                <tbody>
                                                    <tr>
                                                        <th colspan="2" class="text-center">Personal Information</th>
                                                    </tr>
                                                    <tr>
                                                        <th scope="row" style="width: 200px;">Full Name</th>
                                                        <td>{{ $user->fname . ' ' . $user->mname . ' ' . $user->lname }}
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <th scope="row">Date of Birth</th>
                                                        <td>{{ \Carbon\Carbon::parse($user->DOB)->format('d F, Y') }}
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <th scope="row">Marital Status</th>
                                                        <td>{{ $user->marital_status }}</td>
                                                    </tr>
                                                    <tr>
                                                        <th scope="row">Department</th>
                                                        <td>{{ $user->department->dept_name ?? 'Not Specified' }}</td>
                                                    </tr>
                                                    <tr>
                                                        <th scope="row">Job Title</th>
                                                        <td>{{ $user->jobTitle->job_title ?? 'Not Specified' }}</td>
                                                    </tr>
                                                    <tr>
                                                        <th scope="row">Contract Type</th>
                                                        <td>{{ $user->employmentType->employment_type ?? 'Not Specified' }}
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <th scope="row">TIN Number</th>
                                                        <td>{{ $user->tin_no }}</td>
                                                    </tr>
                                                    <tr>
                                                        <th scope="row">Nationality</th>
                                                        <td>{{ $user->nationality }}</td>
                                                    </tr>
                                                    <tr>
                                                        <th scope="row">Passport No</th>
                                                        <td>{{ $user->passport_no }}</td>
                                                    </tr>
                                                    <tr>
                                                        <th scope="row">Place of Birth</th>
                                                        <td>{{ $user->place_of_birth }}</td>
                                                    </tr>
                                                    <tr>
                                                        <th scope="row">Religion</th>
                                                        <td>{{ $user->religion }}</td>
                                                    </tr>

                                                </tbody>
                                            </table>
                                        </div>

                                        <!-- Second Column: Contact Information Table -->
                                        <div class="col-md-6">
                                            <table class="table table-bordered">
                                                <tbody>
                                                    <tr>
                                                        <th colspan="2" class="text-center">Contact Information</th>
                                                    </tr>
                                                    <tr>
                                                        <th scope="row">Phone Number</th>
                                                        <td>{{ $user->mobile }}</td>
                                                    </tr>
                                                    <tr>
                                                        <th scope="row">Email</th>
                                                        <td>{{ $user->email }}</td>
                                                    </tr>
                                                    <tr>
                                                        <th scope="row">District</th>
                                                        <td>{{ $user->district }}</td>
                                                    </tr>
                                                    <tr>
                                                        <th scope="row">Box No</th>
                                                        <td>{{ $user->box_no }}</td>
                                                    </tr>
                                                    <tr>
                                                        <th scope="row">Plot No</th>
                                                        <td>{{ $user->plot_no }}</td>
                                                    </tr>
                                                    <tr>
                                                        <th scope="row">Street</th>
                                                        <td>{{ $user->street }}</td>
                                                    </tr>
                                                    <tr>
                                                        <th scope="row">Popular Landmark</th>
                                                        <td>{{ $user->popular_landmark }}</td>
                                                    </tr>
                                                    <tr>
                                                        <th scope="row">Domicile</th>
                                                        <td>{{ $user->domicile }}</td>
                                                    </tr>
                                                    <tr>
                                                        <th scope="row">NIDA</th>
                                                        <td>{{ $user->NIN }}</td>
                                                    </tr>
                                                </tbody>
                                            </table>

                                            <!-- Attachments Section -->
                                            <table class="table table-bordered mt-4">
                                                <thead>
                                                    <tr>
                                                        <th colspan="2" class="text-center">Uploaded Documents</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach ([
            'nida' => 'NIDA',
            'driving_license' => 'Driving License',
            'transport_id' => 'Transport ID',
            'voting_id' => 'Voting ID',
            'other_document' => 'Other Document',
        ] as $key => $label)
                                                        @if (!empty($user->$key))
                                                            <tr>
                                                                <th scope="row">{{ $label }}</th>
                                                                <td>
                                                                    @php
                                                                        $fileExtension = pathinfo(
                                                                            $user->$key,
                                                                            PATHINFO_EXTENSION,
                                                                        );
                                                                        $iconClass = 'fa fa-file'; // Default icon for unknown types
                                                                        switch (strtolower($fileExtension)) {
                                                                            case 'pdf':
                                                                                $iconClass = 'fa fa-file-pdf-o';
                                                                                break;
                                                                            case 'doc':
                                                                            case 'docx':
                                                                                $iconClass = 'fa fa-file-word-o';
                                                                                break;
                                                                            case 'jpg':
                                                                            case 'jpeg':
                                                                            case 'png':
                                                                                $iconClass = 'fa fa-file-image-o';
                                                                                break;
                                                                            case 'xlsx':
                                                                            case 'xls':
                                                                                $iconClass = 'fa fa-file-excel-o';
                                                                                break;
                                                                        }
                                                                    @endphp
                                                                    <a href="{{ asset('storage/' . $user->$key) }}"
                                                                        target="_blank">
                                                                        <i class="{{ $iconClass }}"
                                                                            aria-hidden="true"></i> View
                                                                        {{ $label }}
                                                                    </a>
                                                                </td>
                                                            </tr>
                                                        @endif
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>

                                    </div>

                                    <div class="row mt-3">
                                        <!-- First Column (Row 2): Professional Information Table -->
                                        <div class="col-md-6">
                                            <table class="table table-bordered">
                                                <tbody>
                                                    <tr>
                                                        <th colspan="2" class="text-center">Professional
                                                            Information
                                                        </th>
                                                    </tr>
                                                    <tr>
                                                        <th scope="row">Professional Reg. Number</th>
                                                        <td>{{ $user->professional_reg_number }}</td>
                                                    </tr>
                                                    <tr>
                                                        <th scope="row">NSSF Number</th>
                                                        <td>{{ $user->nssf_no }}</td>
                                                    </tr>
                                                    <tr>
                                                        <th scope="row">CCBRT Number</th>
                                                        <td>{{ $user->ccbrt_code }}</td>
                                                    </tr>
                                                </tbody>
                                            </table>

                                            <!-- Attachments Section -->
                                            {{-- <table class="table table-bordered mt-4">
                                                    <thead>
                                                        <tr>
                                                            <th colspan="2" class="text-center">Uploaded Certificates
                                                            </th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        @foreach ([
        'form_4_certificate' => 'Form 4 Certificate',
        'form_6_certificate' => 'Form 6 Certificate',
        'diploma_certificate' => 'Diploma Certificate',
        'bachelor_certificate' => 'Bachelor Certificate',
        'masters_certificate' => 'Masters Certificate',
        'phd_certificate' => 'PhD Certificate',
        'diploma_transcript' => 'Diploma Transcript',
        'bachelor_transcript' => 'Bachelor Transcript',
        'masters_transcript' => 'Masters Transcript',
        'phd_transcript' => 'PhD Transcript',
    ] as $key => $label)
                                                            @if (!empty($user->$key))
                                                                <tr>
                                                                    <th scope="row">{{ $label }}</th>
                                                                    <td>
                                                                        <a href="{{ asset('storage/' . $user->$key) }}"
                                                                            target="_blank">
                                                                            <i class="fa fa-file-pdf-o"
                                                                                aria-hidden="true"></i> View
                                                                        </a>
                                                                    </td>
                                                                </tr>
                                                            @endif
                                                        @endforeach
                                                    </tbody>
                                                </table> --}}
                                        </div>


                                        <!-- Second Column (Row 2): Account Information Table -->
                                        <div class="col-md-6">
                                            <table class="table table-bordered">
                                                <tbody>
                                                    <tr>
                                                        <th colspan="2" class="text-center">Account Information
                                                        </th>
                                                    </tr>
                                                    <tr>
                                                        <th scope="row">Joined Date</th>
                                                        <td>{{ \Carbon\Carbon::parse($user->created_at)->format('d F, Y') }}
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <th scope="row">Last Login</th>
                                                        <td>{{ \Carbon\Carbon::parse($user->last_login)->format('d F, Y h:i A') ?? 'N/A' }}
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <th scope="row">Status</th>
                                                        <td>{{ $user->status ?? 'Active' }}</td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>

                                <!-- Assuming userId is dynamically passed or available -->
                                <button class="btn btn-primary" onclick="redirectToEditProfile(123)">Update
                                    Profile</button>

                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <script>
            function redirectToEditProfile(userId) {
                window.location.href = `/profile/edit/${userId}`;
            }
        </script>


    </div>
    </div>
@endsection
