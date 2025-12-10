@extends('layouts.template')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
@section('breadcrumb')
    @include('sweetalert::alert')
    @include('includes.loader')
@endsection

@section('content')
    @include('sweetalert::alert')
    <div class="page-wrapper">
        <div class="content container-fluid">
            <div class="page-header">
                <div class="row">
                    <div class="col-sm-12">
                        <div class="page-sub-header">
                            <h3 class="page-title">Maintenance Mode Settings</h3>
                        </div>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title">
                                <i class="fas fa-tools me-2"></i>System Maintenance Control
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="alert alert-warning" role="alert">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                <strong>Warning:</strong> When maintenance mode is enabled, only users with the <strong>super-admin</strong> or <strong>hr</strong> roles will be able to login. All other users will receive a "system under maintenance" message.
                            </div>

                            <form action="{{ route('settings.maintenance.update') }}" method="POST" id="maintenanceForm">
                                @csrf
                                <div class="form-group mb-4">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" name="maintenance_mode" 
                                               id="maintenance_mode" value="1" 
                                               {{ $maintenanceMode ? 'checked' : '' }}
                                               style="width: 3rem; height: 1.5rem; cursor: pointer;">
                                        <label class="form-check-label ms-3" for="maintenance_mode" style="cursor: pointer;">
                                            <strong>Enable Maintenance Mode</strong>
                                        </label>
                                    </div>
                                    <small class="form-text text-muted mt-2 d-block">
                                        Toggle this switch to enable or disable maintenance mode for the system.
                                    </small>
                                </div>

                                <div class="form-group">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save me-2"></i>Save Settings
                                    </button>
                                    <a href="{{ route('dashboard') }}" class="btn btn-secondary">
                                        <i class="fas fa-times me-2"></i>Cancel
                                    </a>
                                </div>
                            </form>

                            <div class="mt-4">
                                <div class="card bg-light">
                                    <div class="card-body">
                                        <h6 class="card-title">
                                            <i class="fas fa-info-circle me-2"></i>Current Status
                                        </h6>
                                        <p class="card-text mb-0">
                                            @if ($maintenanceMode)
                                                <span class="badge bg-danger">
                                                    <i class="fas fa-lock me-1"></i>Maintenance Mode: <strong>ENABLED</strong>
                                                </span>
                                                <br><small class="text-muted mt-2 d-block">
                                                    Only super-admin and hr users can currently access the system.
                                                </small>
                                            @else
                                                <span class="badge bg-success">
                                                    <i class="fas fa-unlock me-1"></i>Maintenance Mode: <strong>DISABLED</strong>
                                                </span>
                                                <br><small class="text-muted mt-2 d-block">
                                                    All users with valid credentials can access the system.
                                                </small>
                                            @endif
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        $(document).ready(function() {
            // Show confirmation before enabling maintenance mode
            $('#maintenanceForm').on('submit', function(e) {
                const isEnabled = $('#maintenance_mode').is(':checked');
                
                if (isEnabled) {
                    if (!confirm('Are you sure you want to enable maintenance mode? Only super-admin and hr users will be able to login. All other users will be blocked.')) {
                        e.preventDefault();
                        return false;
                    }
                }
            });

            // Show toastr notifications
            @if(session('success'))
                toastr.success('{{ session('success') }}', 'Success');
            @endif

            @if(session('error'))
                toastr.error('{{ session('error') }}', 'Error');
            @endif
        });
    </script>
@endsection

