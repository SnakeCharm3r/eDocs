@extends('layouts.template2')
@include('includes.head')

@section('breadcrumb')
    <div class="content container-fluid" style="background-color: #eff8f3;">
        <!-- Page Header -->
        <div class="page-header">
            <div class="row">
                <div class="col-sm-12">
                    <div class="page-sub-header">
                        <h3 class="page-title">HR Details Form Feedback</h3>
                    </div>
                </div>
            </div>
        </div>

        <!-- Feedback Section -->
        <div class="container my-5">
            <!-- Info Alert for Pending Status -->
            @if ($formFeedback['status'] == 'Pending')
                <div class="alert alert-info alert-dismissible fade show" role="alert" style="border-left: 4px solid #007A33;">
                    <h5 class="alert-heading">
                        <i class="fas fa-clock"></i> Please Wait for Review
                    </h5>
                    <p class="mb-2">
                        Your HR form has been submitted successfully and is currently under review by the Human Resources department.
                        <strong>Please wait for feedback.</strong> You will be notified once a decision has been made.
                    </p>
                    <hr>
                    <p class="mb-0">
                        <small>
                            <i class="fas fa-info-circle"></i> You can logout and check back later, or wait on this page for updates.
                        </small>
                    </p>
                </div>
            @endif

            <!-- Section Title -->
            <div class="page-sub-header">
                <h4 class="page-title">Form Details</h4>
            </div>

            <!-- Request Details -->
            <div class="card shadow-sm">
                <div class="card-body">
                    <div class="row">
                        <!-- Request Date and Requester Name -->
                        <div class="col-md-12">
                            <p><strong>Requester Name:</strong> {{ Auth::user()->fname . ' ' . Auth::user()->lname }}</p>
                            <p><strong>Request Date:</strong> {{ $formFeedback['request_date'] }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Form Status Section -->
            <div class="my-4">
                <h4 class="page-title">Form Status</h4>
                <div class="card shadow-sm">
                    <div class="card-body">
                        <!-- Dynamic Status Display -->
                        <div class="mb-3">
                            <strong>Current Status:</strong>
                            @php
                                if ($formFeedback['status'] == 'Pending') {
                                    $color = 'warning';
                                    $icon = 'clock';
                                } elseif ($formFeedback['status'] == 'Accepted') {
                                    $color = 'success';
                                    $icon = 'check-circle';
                                } else {
                                    $color = 'danger';
                                    $icon = 'times-circle';
                                }
                            @endphp
                            <span class="badge badge-{{ $color }} badge-lg" style="font-size: 1rem; padding: 0.5rem 1rem;">
                                <i class="fas fa-{{ $icon }}"></i> {{ $formFeedback['status'] }}
                            </span>
                        </div>
                        
                        @if ($formFeedback['decision_date'])
                            <p class="mb-3">
                                <strong>Decision Date:</strong> {{ $formFeedback['decision_date'] }}
                            </p>
                        @endif

                        <!-- Link to Continue with Requests -->
                        @if ($formFeedback['status'] == 'Accepted')
                            <div class="alert alert-success">
                                <h5 class="alert-heading">
                                    <i class="fas fa-check-circle"></i> Form Approved!
                                </h5>
                                <p>Your HR form has been approved. You can now continue with other requests.</p>
                                <a href="/dashboard" class="btn btn-primary mt-2">
                                    <i class="fas fa-home"></i> Go to Dashboard
                                </a>
                            </div>
                        @endif

                        <!-- Rejection Message Area -->
                        @if ($formFeedback['status'] == 'Rejected')
                            <div class="alert alert-danger">
                                <h5 class="alert-heading">
                                    <i class="fas fa-exclamation-triangle"></i> Form Rejected
                                </h5>
                                <p><strong>Reason for Rejection:</strong></p>
                                <p>{{ $formFeedback['feedback'] ?? 'No reason provided.' }}</p>
                                <hr>
                                <p class="mb-0">
                                    <a href="{{ $formFeedback['url'] ?? '/profile/personal-details' }}"
                                        class="btn btn-secondary">
                                        <i class="fas fa-edit"></i> Update Form
                                    </a>
                                </p>
                            </div>
                        @endif

                        @if ($formFeedback['status'] == 'Pending')
                            <div class="alert alert-warning">
                                <h5 class="alert-heading">
                                    <i class="fas fa-hourglass-half"></i> Under Review
                                </h5>
                                <p class="mb-2">Your form is currently being reviewed by the Human Resources department.</p>
                                <p class="mb-0">
                                    <small>
                                        <i class="fas fa-bell"></i> You will receive an email notification once a decision has been made.
                                    </small>
                                </p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="action-buttons my-4 text-center">
                @if ($formFeedback['status'] == 'Pending')
                    <div class="card shadow-sm">
                        <div class="card-body">
                            <h5 class="card-title">What's Next?</h5>
                            <p class="card-text">
                                Please wait for HR to review your form. You can logout and check back later, 
                                or refresh this page to see if there are any updates.
                            </p>
                            <div class="d-flex justify-content-center gap-3 flex-wrap">
                                <button type="button" onclick="location.reload();" class="btn btn-outline-primary">
                                    <i class="fas fa-sync-alt"></i> Refresh Status
                                </button>
                                <a href="{{ route('logout') }}" class="btn btn-outline-secondary" onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                                    <i class="fas fa-sign-out-alt"></i> Logout
                                </a>
                            </div>
                            <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
                                @csrf
                            </form>
                        </div>
                    </div>
                @elseif ($formFeedback['status'] == 'Rejected')
                    <a href="{{ $formFeedback['url'] ?? '/profile/personal-details' }}" class="btn btn-secondary">
                        <i class="fas fa-edit"></i> Update Form
                    </a>
                @endif
            </div>
        </div>
    </div>

    <style>
        .badge-lg {
            font-size: 1rem;
            padding: 0.5rem 1rem;
        }

        .alert-heading {
            margin-bottom: 0.75rem;
        }

        .action-buttons .card {
            border: 1px solid #dee2e6;
        }

        .action-buttons .card-title {
            color: #007A33;
            font-weight: 600;
        }
    </style>

    <script>
        // Prevent back navigation when status is Pending
        @if ($formFeedback['status'] == 'Pending')
            (function() {
                // Disable back button
                history.pushState(null, null, location.href);
                window.onpopstate = function(event) {
                    history.pushState(null, null, location.href);
                    // Show alert
                    Swal.fire({
                        icon: 'info',
                        title: 'Please Wait',
                        text: 'Your form is under review. Please wait for HR feedback. You can logout if needed.',
                        confirmButtonText: 'OK',
                        confirmButtonColor: '#007A33',
                        allowOutsideClick: false,
                        allowEscapeKey: false
                    });
                };

                // Also prevent browser back button
                window.addEventListener('popstate', function(event) {
                    history.pushState(null, null, location.href);
                });

                // Show initial message
                document.addEventListener('DOMContentLoaded', function() {
                    // Optional: Show a toast notification
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            icon: 'info',
                            title: 'Form Submitted',
                            text: 'Your HR form has been submitted. Please wait for review feedback.',
                            confirmButtonText: 'OK',
                            confirmButtonColor: '#007A33',
                            timer: 5000,
                            timerProgressBar: true
                        });
                    }
                });
            })();
        @endif

        // Auto-refresh every 30 seconds if status is Pending (optional)
        @if ($formFeedback['status'] == 'Pending')
            let refreshInterval = setInterval(function() {
                // Check if user is still on this page
                if (document.visibilityState === 'visible') {
                    // Optionally refresh the page to check for status updates
                    // Uncomment the line below if you want auto-refresh
                    // location.reload();
                }
            }, 30000); // 30 seconds

            // Clear interval when page is hidden
            document.addEventListener('visibilitychange', function() {
                if (document.visibilityState === 'hidden') {
                    clearInterval(refreshInterval);
                } else {
                    refreshInterval = setInterval(function() {
                        if (document.visibilityState === 'visible') {
                            // location.reload();
                        }
                    }, 30000);
                }
            });
        @endif
    </script>
@endsection
