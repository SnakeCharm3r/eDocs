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

            <div class="container">
                <div class="row flex-lg-nowrap">
                    <div class="col">
                        <div class="row">
                            <div class="col mb-3">
                                <div class="card">
                                    <div class="card-body">
                                        <ul class="nav nav-tabs">
                                            <li class="nav-item">
                                                <a href="{{ route('profile.index') }}" class="nav-link">User Info</a>
                                            </li>
                                            <li class="nav-item">
                                                <a href="{{ route('policies.user') }}" class="nav-link">Policies</a>
                                            </li>
                                            {{-- <li class="nav-item">
                                                <a href="{{ route('user_profile.pass') }}"
                                                    class="nav-link active">Password</a>
                                            </li>
                                            <li class="nav-item">
                                                <a href="{{ route('family-details.index') }}" class="nav-link">Family
                                                    Details</a>
                                            </li>
                                            <li class="nav-item">
                                                <a href="{{ route('health-details.index') }}" class="nav-link">Health
                                                    Details</a>
                                            </li>
                                            <li class="nav-item">
                                                <a href="{{ route('ccbrt_relation.index') }}" class="nav-link">CCBRT
                                                    Reation</a>
                                            </li>
                                            <li class="nav-item">
                                                <a href="{{ route('language_knowledge.index') }}"
                                                    class="nav-link">Language</a>
                                            </li> --}}
                                        </ul>
                                        <br>
                                        <div class="container">
                                            <div class="row justify-content-center">
                                                <div class="col-md-8">
                                                    <div class="card">
                                                        <div class="card-header">{{ __('Change Password') }}</div>
                                                        <div class="card-body">
                                                            @if (session('error'))
                                                                <div class="alert alert-danger" role="alert">
                                                                    {{ session('error') }}
                                                                </div>
                                                            @endif

                                                            @if (session('success'))
                                                                <div class="alert alert-success" role="alert">
                                                                    {{ session('success') }}
                                                                </div>
                                                            @endif

                                                            <form method="POST"
                                                                action="{{ route('change.password.update') }}">
                                                                @csrf
                                                                @method('PUT')

                                                                <div class="mb-3">
                                                                    <label for="current_password"
                                                                        class="form-label">{{ __('Current Password') }}</label>
                                                                    <input id="current_password" type="password"
                                                                        class="form-control @error('current_password') is-invalid @enderror"
                                                                        name="current_password" required
                                                                        autocomplete="current-password">
                                                                    @error('current_password')
                                                                        <div class="invalid-feedback">
                                                                            {{ $message }}
                                                                        </div>
                                                                    @enderror
                                                                </div>

                                                                <div class="mb-3">
                                                                    <label for="new_password"
                                                                        class="form-label">{{ __('New Password') }}</label>
                                                                    <input id="new_password" type="password"
                                                                        class="form-control @error('new_password') is-invalid @enderror"
                                                                        name="new_password" required
                                                                        autocomplete="new-password">
                                                                    @error('new_password')
                                                                        <div class="invalid-feedback">
                                                                            {{ $message }}
                                                                        </div>
                                                                    @enderror
                                                                </div>

                                                                <div class="mb-3">
                                                                    <label for="new_password_confirmation"
                                                                        class="form-label">{{ __('Confirm New Password') }}</label>
                                                                    <input id="new_password_confirmation" type="password"
                                                                        class="form-control"
                                                                        name="new_password_confirmation" required
                                                                        autocomplete="new-password">
                                                                </div>

                                                                <div class="text-center">
                                                                    <button type="submit"
                                                                        class="btn btn-primary">{{ __('Change Password') }}</button>
                                                                </div>
                                                            </form>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
