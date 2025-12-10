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
                                                            <img src="{{ asset('storage/' . $user->profile_picture) }}"
                                                                alt="Profile Picture" class="img-fluid rounded-circle"
                                                                style="max-width: 140px; height: 140px; border: 2px solid #ccc; padding: 5px; object-fit: cover;">
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
                                                        <h4 class="pt-sm-2 pb-1 mb-0 text-nowrap">{{ $user->username }}
                                                        </h4>
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
                                            <li class="nav-item"><a href="#CCBRT_Relation" class="nav-link"
                                                    data-bs-toggle="tab">CCBRT Relation</a></li>
                                        </ul>
                                        <div class="tab-content pt-3">

                                            <div class="tab-pane" id="CCBRT_Relation">
                                                <form action="#">
                                                    <div class="row">
                                                        <div class="col-12 col-md-6">
                                                            <div class="form-group">
                                                                <label>Names</label>
                                                                <input type="text" class="form-control"
                                                                    name="names">
                                                            </div>
                                                            <div class="form-group">
                                                                <label>Relation</label>
                                                                <input type="text" class="form-control"
                                                                    name="relation">
                                                            </div>
                                                        </div>
                                                        <div class="col-12 col-md-6">
                                                            <div class="form-group">
                                                                <label>Department</label>
                                                                <input type="text" class="form-control"
                                                                    name="department">
                                                            </div>
                                                            <div class="form-group">
                                                                <label>Position</label>
                                                                <input type="text" class="form-control"
                                                                    name="position">
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <div class="row">
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
