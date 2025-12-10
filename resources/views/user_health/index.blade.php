<!-- resources/views/profile/show.blade.php -->

@extends('layouts.template')

@section('breadcrumb')
    @include('sweetalert::alert')

    <div class="page-wrapper">
        <div class="content container-fluid">
            <div class="page-header">
                <div class="row">
                    <div class="col-sm-12">
                        <div class="page-sub-header">
                            <h3 class="page-title">User Profile</h3>
                        </div>
                    </div>
                </div>
            </div>
            <link href="https://maxcdn.bootstrapcdn.com/font-awesome/4.3.0/css/font-awesome.min.css" rel="stylesheet">
            <div class="container">
                <div class="row flex-lg-nowrap">

                    <div class="col">
                        <div class="row">
                            <div class="col mb-3">
                                <div class="card">
                                    <div class="card-body">
                                        <div class="e-profile">
                                            <div class="row">
                                                <div class="col-12 col-sm-auto mb-3">
                                                    <div class="mx-auto" style="width: 140px;">
                                                        <div class="d-flex justify-content-center align-items-center rounded"
                                                            style="height: 140px; background-color: rgb(233, 236, 239); position: relative;">
                                                            @if ($user->profile_picture)
                                                                <img src="{{ asset('storage/' . $user->profile_picture) }}"
                                                                    alt="Profile Picture" class="img-fluid rounded-circle"
                                                                    style="max-width: 140px; height: 140px; border: 2px solid #ccc; padding: 5px; object-fit: cover;">
                                                            @else
                                                                <img src="{{ asset('assets/img/icon.png') }}"
                                                                    alt="Default User Icon" class="img-fluid rounded-circle"
                                                                    style="max-width: 150px; height: 140px; border: 1px solid #ccc; padding: 1px; object-fit: cover;">
                                                            @endif
                                                            <form id="profilePictureForm"
                                                                action="{{ route('profile.update.picture') }}"
                                                                method="POST" enctype="multipart/form-data"
                                                                style="display: none;">
                                                                @csrf
                                                                <input type="file" class="form-control"
                                                                    id="profile_picture" name="profile_picture"
                                                                    accept="image/*"
                                                                    onchange="handleProfilePictureChange(this)">
                                                            </form>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div
                                                    class="col d-flex flex-column flex-sm-row justify-content-between mb-3">
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
                                                    <div class="text-sm-right ml-auto">
                                                        <div class="text-muted"><small>Joined 09 July 2024</small></div>
                                                    </div>
                                                </div>
                                                <script>
                                                    function handleProfilePictureChange(input) {
                                                        document.getElementById('profilePictureForm').submit();
                                                    }
                                                </script>
                                            </div>
                                        </div>
                                        <ul class="nav nav-tabs">
                                            <li class="nav-item"><a href="#family" class="nav-link"
                                                    data-bs-toggle="tab">Family Details</a></li>

                                        </ul>
                                        <div class="tab-content pt-3">
                                            <div class="tab-pane" id="family">
                                                <form action="{{ route('profile.edit', $id) }}" method="POST">
                                                    @csrf
                                                    @method('PATCH')

                                                    <div class="row">
                                                        <!-- Next of Kin 1 -->
                                                        <div class="col-12 col-md-6">
                                                            <div class="form-group">
                                                                <label>Full Name (Next of Kin 1)</label>
                                                                <input type="text" class="form-control"
                                                                    name="full_name_1"
                                                                    value="{{ old('full_name_1', $profile->full_name_1) }}"
                                                                    required>
                                                            </div>
                                                            <div class="form-group">
                                                                <label>Relationship (Next of Kin 1)</label>
                                                                <input type="text" class="form-control"
                                                                    name="relationship_1"
                                                                    value="{{ old('relationship_1', $profile->relationship_1) }}"
                                                                    required>
                                                            </div>
                                                        </div>
                                                        <div class="col-12 col-md-6">
                                                            <div class="form-group">
                                                                <label>Mobile (Next of Kin 1)</label>
                                                                <input type="text" class="form-control"
                                                                    name="phone_number_1"
                                                                    value="{{ old('phone_number_1', $profile->phone_number_1) }}"
                                                                    required>
                                                            </div>
                                                            <div class="form-group">
                                                                <label>Date of Birth (Next of Kin 1)</label>
                                                                <input type="date" class="form-control" name="dob_1"
                                                                    value="{{ old('dob_1', $profile->dob_1) }}" required>
                                                            </div>
                                                        </div>
                                                        <div class="col-12 col-md-6">
                                                            <div class="form-group">
                                                                <label>Occupation (Next of Kin 1)</label>
                                                                <input type="text" class="form-control"
                                                                    name="occupation_1"
                                                                    value="{{ old('occupation_1', $profile->occupation_1) }}"
                                                                    required>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <hr>
                                                    <div class="row">
                                                        <!-- Next of Kin 2 -->
                                                        <div class="col-12 col-md-6">
                                                            <div class="form-group">
                                                                <label>Full Name (Next of Kin 2)</label>
                                                                <input type="text" class="form-control"
                                                                    name="full_name_2"
                                                                    value="{{ old('full_name_2', $profile->full_name_2) }}"
                                                                    required>
                                                            </div>
                                                            <div class="form-group">
                                                                <label>Relationship (Next of Kin 2)</label>
                                                                <input type="text" class="form-control"
                                                                    name="relationship_2"
                                                                    value="{{ old('relationship_2', $profile->relationship_2) }}"
                                                                    required>
                                                            </div>
                                                        </div>
                                                        <div class="col-12 col-md-6">
                                                            <div class="form-group">
                                                                <label>Mobile (Next of Kin 2)</label>
                                                                <input type="text" class="form-control"
                                                                    name="phone_number_2"
                                                                    value="{{ old('phone_number_2', $profile->phone_number_2) }}"
                                                                    required>
                                                            </div>
                                                            <div class="form-group">
                                                                <label>Date of Birth (Next of Kin 2)</label>
                                                                <input type="date" class="form-control" name="dob_2"
                                                                    value="{{ old('dob_2', $profile->dob_2) }}" required>
                                                            </div>
                                                        </div>
                                                        <div class="col-12 col-md-6">
                                                            <div class="form-group">
                                                                <label>Occupation (Next of Kin 2)</label>
                                                                <input type="text" class="form-control"
                                                                    name="occupation_2"
                                                                    value="{{ old('occupation_2', $profile->occupation_2) }}"
                                                                    required>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="row mt-3">
                                                        <div class="col-md-12">
                                                            <div class="form-group">
                                                                <label>First Person to Contact in Case of Emergency:</label>
                                                                <select class="form-control" name="emergency_contact"
                                                                    required>
                                                                    <option value="" disabled
                                                                        {{ old('emergency_contact', $profile->emergency_contact) == '' ? 'selected' : '' }}>
                                                                        Select Emergency Contact</option>
                                                                    <option value="next_of_kin1"
                                                                        {{ old('emergency_contact', $profile->emergency_contact) == 'next_of_kin1' ? 'selected' : '' }}>
                                                                        Next of Kin 1</option>
                                                                    <option value="next_of_kin2"
                                                                        {{ old('emergency_contact', $profile->emergency_contact) == 'next_of_kin2' ? 'selected' : '' }}>
                                                                        Next of Kin 2</option>
                                                                </select>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="row mt-3">
                                                        <div class="col d-flex justify-content-end">
                                                            <button class="btn btn-secondary me-2"
                                                                type="button">Edit</button>
                                                            <button class="btn btn-primary" type="submit">Save
                                                                Changes</button>
                                                        </div>
                                                    </div>
                                                </form>


                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <!-- Add other content for profile page here -->
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
