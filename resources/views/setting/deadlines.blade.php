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
                            <h3 class="page-title">Submission Deadline Settings</h3>
                        </div>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title">
                                <i class="fas fa-calendar-alt me-2"></i>Locum & On-Call Submission Deadlines
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="alert alert-info" role="alert">
                                <i class="fas fa-info-circle me-2"></i>
                                <strong>Note:</strong> Set the day of each month when locum and on-call submissions close.
                                Submissions for the previous month must be completed by the specified day of the current
                                month.
                            </div>

                            <form action="{{ route('settings.deadlines.update') }}" method="POST" id="deadlineForm">
                                @csrf

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="card bg-light mb-4">
                                            <div class="card-body">
                                                <h6 class="card-title">
                                                    <i class="fas fa-user-md me-2 text-primary"></i>Locum Request Deadline
                                                </h6>
                                                <div class="form-group">
                                                    <label for="locum_submission_deadline" class="form-label">
                                                        Submission closes on day <span class="text-danger">*</span>
                                                    </label>
                                                    <div class="input-group" style="max-width: 200px;">
                                                        <input type="number"
                                                            class="form-control @error('locum_submission_deadline') is-invalid @enderror"
                                                            id="locum_submission_deadline" name="locum_submission_deadline"
                                                            value="{{ old('locum_submission_deadline', $locumDeadline) }}"
                                                            min="1" max="28" required>
                                                        <span class="input-group-text">of month</span>
                                                    </div>
                                                    @error('locum_submission_deadline')
                                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                                    @enderror
                                                    <small class="form-text text-muted mt-2 d-block">
                                                        Example: If set to 5, locum submissions for December must be
                                                        submitted by January 5th.
                                                    </small>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="card bg-light mb-4">
                                            <div class="card-body">
                                                <h6 class="card-title">
                                                    <i class="fas fa-phone me-2 text-success"></i>On-Call Request Deadline
                                                </h6>
                                                <div class="form-group">
                                                    <label for="oncall_submission_deadline" class="form-label">
                                                        Submission closes on day <span class="text-danger">*</span>
                                                    </label>
                                                    <div class="input-group" style="max-width: 200px;">
                                                        <input type="number"
                                                            class="form-control @error('oncall_submission_deadline') is-invalid @enderror"
                                                            id="oncall_submission_deadline"
                                                            name="oncall_submission_deadline"
                                                            value="{{ old('oncall_submission_deadline', $oncallDeadline) }}"
                                                            min="1" max="28" required>
                                                        <span class="input-group-text">of month</span>
                                                    </div>
                                                    @error('oncall_submission_deadline')
                                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                                    @enderror
                                                    <small class="form-text text-muted mt-2 d-block">
                                                        Example: If set to 5, on-call submissions for December must be
                                                        submitted by January 5th.
                                                    </small>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
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
                                            <i class="fas fa-info-circle me-2"></i>Current Settings
                                        </h6>
                                        <div class="row">
                                            <div class="col-md-6">
                                                <p class="card-text mb-1">
                                                    <span class="badge bg-primary">
                                                        <i class="fas fa-user-md me-1"></i>Locum Deadline:
                                                        <strong>{{ $locumDeadline }}{{ $locumDeadline == 1 ? 'st' : ($locumDeadline == 2 ? 'nd' : ($locumDeadline == 3 ? 'rd' : 'th')) }}</strong>
                                                        of month
                                                    </span>
                                                </p>
                                            </div>
                                            <div class="col-md-6">
                                                <p class="card-text mb-1">
                                                    <span class="badge bg-success">
                                                        <i class="fas fa-phone me-1"></i>On-Call Deadline:
                                                        <strong>{{ $oncallDeadline }}{{ $oncallDeadline == 1 ? 'st' : ($oncallDeadline == 2 ? 'nd' : ($oncallDeadline == 3 ? 'rd' : 'th')) }}</strong>
                                                        of month
                                                    </span>
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
        </div>
    </div>

    <script>
        $(document).ready(function() {
            // Show toastr notifications
            @if (session('success'))
                toastr.success('{{ session('success') }}', 'Success');
            @endif

            @if (session('error'))
                toastr.error('{{ session('error') }}', 'Error');
            @endif
        });
    </script>
@endsection
