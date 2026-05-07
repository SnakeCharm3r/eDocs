@extends('layouts.template')

@section('breadcrumb')
    @include('sweetalert::alert')
@endsection

@section('content')
    <div class="page-wrapper">
        <div class="content container-fluid">
            <div class="row">
                <div class="col-sm-12">
                    <h3 class="page-title mb-3">Edit User Roles</h3>
                </div>
            </div>

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

            {{-- Display Validation Errors --}}
            @if ($errors->any())
                <div class="alert alert-warning alert-dismissible fade show" role="alert">
                    <ul class="mb-0">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            <div class="row">
                <div class="col-md-8 offset-md-2">
                    <div class="card">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0">
                                <i class="fas fa-user-edit me-2"></i>Edit User: {{ $user->username }}
                            </h5>
                        </div>

                        <div class="card-body">
                            <form action="{{ route('users.edit.role', $user->id) }}" method="POST">
                                @csrf

                                {{-- User Information Section --}}
                                <div class="mb-4">
                                    <h6 class="text-muted mb-3">
                                        <i class="fas fa-info-circle me-2"></i>User Information
                                    </h6>
                                    
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="username" class="form-label fw-bold">
                                                <i class="fas fa-user me-1"></i> Username
                                            </label>
                                            <input type="text" name="username" id="username" 
                                                value="{{ $user->username }}" class="form-control" readonly>
                                        </div>

                                        <div class="col-md-6 mb-3">
                                            <label for="email" class="form-label fw-bold">
                                                <i class="fas fa-envelope me-1"></i> Email
                                            </label>
                                            <input type="text" name="email" id="email" 
                                                value="{{ $user->email }}" class="form-control" readonly>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label fw-bold">
                                                <i class="fas fa-building me-1"></i> Department
                                            </label>
                                            <input type="text" value="{{ optional($user->department)->dept_name ?? 'N/A' }}" 
                                                class="form-control" readonly>
                                        </div>

                                        <div class="col-md-6 mb-3">
                                            <label class="form-label fw-bold">
                                                <i class="fas fa-briefcase me-1"></i> Job Title
                                            </label>
                                            <input type="text" value="{{ optional($user->jobTitle)->job_title ?? 'N/A' }}" 
                                                class="form-control" readonly>
                                        </div>
                                    </div>
                                </div>

                                <hr>

                                {{-- Roles Section --}}
                                <div class="mb-4">
                                    <h6 class="text-muted mb-3">
                                        <i class="fas fa-user-shield me-2"></i>Assign Roles
                                    </h6>
                                    
                                    <div class="alert alert-info">
                                        <i class="fas fa-info-circle me-2"></i>
                                        Select one or more roles to assign to this user.
                                    </div>

                                    <div class="row">
                                        @foreach ($roles as $role)
                                            @php
                                                $isSuperAdmin = $role->name === 'super-admin';
                                                $currentUserIsSuperAdmin = auth()->user()->hasRole('super-admin');
                                                $userHasSuperAdmin = $user->hasRole('super-admin');
                                                $superAdminCount = \App\Models\User::role('super-admin')->count();
                                                $isLastSuperAdmin = $isSuperAdmin && $userHasSuperAdmin && $superAdminCount <= 1;
                                            @endphp
                                            <div class="col-md-6 mb-3">
                                                <div class="form-check p-3 border rounded {{ $isSuperAdmin && !$currentUserIsSuperAdmin ? 'opacity-50' : '' }}">
                                                    <input class="form-check-input" 
                                                        type="checkbox" 
                                                        name="roles[]"
                                                        value="{{ $role->name }}" 
                                                        id="role-{{ $role->id }}"
                                                        {{ $user->hasRole($role->name) ? 'checked' : '' }}
                                                        {{ ($isSuperAdmin && !$currentUserIsSuperAdmin) ? 'disabled' : '' }}
                                                        {{ $isLastSuperAdmin ? 'disabled' : '' }}>
                                                    <label class="form-check-label fw-bold" for="role-{{ $role->id }}">
                                                        <i class="fas fa-shield-alt me-2 text-primary"></i>
                                                        {{ ucwords(str_replace('-', ' ', $role->name)) }}
                                                        @if($isSuperAdmin && !$currentUserIsSuperAdmin)
                                                            <small class="text-muted d-block">(Only super-admin can manage this role)</small>
                                                        @elseif($isLastSuperAdmin)
                                                            <small class="text-danger d-block">(Cannot remove - must have at least one super-admin)</small>
                                                        @endif
                                                    </label>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                    
                                    @error('roles')
                                        <div class="text-danger mt-2">
                                            <i class="fas fa-exclamation-circle me-1"></i>{{ $message }}
                                        </div>
                                    @enderror
                                </div>

                                {{-- Action Buttons --}}
                                <div class="d-flex justify-content-between mt-4">
                                    <a href="{{ route('users.index') }}" class="btn btn-secondary">
                                        <i class="fas fa-arrow-left me-2"></i>Back to Users
                                    </a>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save me-2"></i>Update Roles
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
