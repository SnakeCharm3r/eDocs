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
                            <h3 class="page-title">Mail Server Settings</h3>
                        </div>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-body">

                            <div class="row">
                                <!-- Mail Settings Form -->
                                <div class="col-md-6">
                                    <form action="{{ route('settings.email.update') }}" method="POST">
                                        @csrf
                                        <div class="form-group">
                                            <label for="host">Mail Host</label>
                                            <input type="text" name="host" class="form-control"
                                                value="{{ $mailSettings['host'] }}">
                                        </div>
                                        <div class="form-group">
                                            <label for="port">Mail Port</label>
                                            <input type="text" name="port" class="form-control"
                                                value="{{ $mailSettings['port'] }}">
                                        </div>
                                        <div class="form-group">
                                            <label for="username">Username</label>
                                            <input type="text" name="username" class="form-control"
                                                value="{{ $mailSettings['username'] }}">

                                        </div>
                                        <div class="form-group">
                                            <label for="password">Password</label>
                                            <input type="password" name="password" class="form-control"
                                                placeholder="Leave blank to keep current password">
                                            <small class="text-muted">Current password is hidden. Fill only if you want to
                                                change it.</small>
                                        </div>
                                        <select name="encryption" class="form-control">
                                            <option value="tls"
                                                {{ $mailSettings['encryption'] === 'tls' ? 'selected' : '' }}>TLS
                                            </option>
                                            <option value="ssl"
                                                {{ $mailSettings['encryption'] === 'ssl' ? 'selected' : '' }}>SSL
                                            </option>
                                        </select>
                                        <br>
                                        <button type="submit" class="btn btn-success">Update Mail Settings</button>

                                    </form>
                                </div>

                                <!-- Email Test Form -->
                                <div class="col-md-6">
                                    <form action="{{ route('settings.email.test') }}" method="POST">
                                        @csrf
                                        <div class="card">
                                            <div class="card-body">
                                                <h5 class="card-title">Send Test Email</h5>
                                                <div class="form-group">
                                                    <label for="test_email">Recipient Email</label>
                                                    <input type="email" name="test_email" class="form-control"
                                                        placeholder="example@domain.com" required>
                                                </div>
                                                <button type="submit" class="btn btn-primary">Send Test Email</button>
                                            </div>
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
    <script>
        toastr.options = {
            "closeButton": true,
            "debug": false,
            "newestOnTop": true,
            "progressBar": true,
            "positionClass": "toast-custom-position",
            "preventDuplicates": true,
            "showDuration": "300",
            "hideDuration": "1000",
            "timeOut": "5000",
            "extendedTimeOut": "1000",
            "showEasing": "swing",
            "hideEasing": "linear",
            "showMethod": "fadeIn",
            "hideMethod": "fadeOut"
        };

        @if (session('success'))
            toastr.success("{{ session('success') }}");
        @elseif (session('error'))
            toastr.error("{{ session('error') }}");
        @endif
    </script>

    <style>
        /* Custom green style */
        #toast-container>.toast-success {
            background-color: #61ce70 !important;
            color: white !important;
        }

        /* Custom position ~95% from top (close to bottom) */
        .toast-custom-position {
            top: 10vh;
            right: 12px;
            left: auto;
            bottom: auto;
            position: fixed;
            z-index: 999999;
        }
    </style>
@endsection
