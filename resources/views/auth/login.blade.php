<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=0">
    <title>eDocs | Login</title>
    <link rel="shortcut icon" href="assets/img/favicon.png">
    <link
        href="https://fonts.googleapis.com/css2?family=Roboto:ital,wght@0,400;0,500;0,700;0,900;1,400;1,500;1,700&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="assets/plugins/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="assets/plugins/fontawesome/css/fontawesome.min.css">
    <link rel="stylesheet" href="assets/plugins/fontawesome/css/all.min.css">
    <link rel="stylesheet" href="assets/plugins/feather/feather.css">
    <link rel="stylesheet" href="assets/plugins/icons/flags/flags.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
</head>

<style>
    .account-subtitle {
        font-size: 16px;
        color: #333;
        text-align: center;
        margin-top: 20px;
    }

    .account-subtitle a {
        color: #0f813c;
        font-weight: bold;
        text-decoration: underline;
    }

    .account-subtitle a:hover {
        color: #0c642f;
    }


    @media (max-width: 768px) {
        .account-subtitle {
            font-size: 14px;
            padding: 10px;
        }
    }
</style>

<body>
    <div class="main-wrapper login-body">
        <div class="login-wrapper" style="background-color: #eff8f3;">
            <div class="container">
                <div class="loginbox">
                    <div class="login-left">
                        <img class="img-fluid" src="assets/img/login.jpg" alt="Logo">
                    </div>
                    <div class="login-right">
                        <div class="login-right-wrap">
                            <div style="text-align: center;">
                                <img src="assets/img/ccbrt.jpg" alt="CCBRT eDOCS Logo" style="max-width: 150px;">
                            </div>
                            <h1
                                style="text-align: center; font-family: 'Roboto', sans-serif; font-size: medium; color: #0f813c;">
                                CCBRT eDocs
                            </h1>
                            <br>
                            @if ($errors->has('login_error'))
                                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                    {{ $errors->first('login_error') }}
                                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                        <span aria-hidden="true">&times;</span>
                                    </button>
                                </div>
                            @endif
                            @if (session('error'))
                                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                    {{ session('error') }}
                                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                        <span aria-hidden="true">&times;</span>
                                    </button>
                                </div>
                            @endif
                            <form method="POST" action="{{ route('login.handleLogin') }}"
                                class="login100-form validate-form">
                                @csrf
                                <div class="form-group">
                                    <label for="username">Username <span class="login-danger">*</span></label>
                                    <input id="username" class="form-control" type="text" name="username"
                                        value="{{ old('username', session('registered_username')) }}"
                                        placeholder="First Name.Last Name" required>
                                    <span class="profile-views"><i class="fas fa-user-circle"></i></span>
                                    @error('username')
                                        <div class="text-danger">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="form-group">
                                    <label for="password">Password <span class="login-danger">*</span></label>
                                    <input id="password" class="form-control pass-input" type="password"
                                        name="password" placeholder="*************" required>
                                    <span class="profile-views feather-eye toggle-password"></span>
                                    @error('password')
                                        <div class="text-danger">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="forgotpass">
                                    <div class="remember-me">
                                        <label class="custom_check mr-2 mb-0 d-inline-flex remember-me"
                                            style="color: #0f813c;"> Remember me
                                            <input type="checkbox" name="radio">
                                            <span class="checkmark"></span>
                                        </label>
                                    </div>
                                    <a href="{{ route('password.forget') }}" style="color: #0f813c;">Forgot
                                        Password?</a>

                                </div>
                                <div class="form-group">
                                    <button class="btn btn-primary btn-block" type="submit"
                                        style="background-color: #0f813c;">Login</button>
                                </div>
                            </form>
                            <p class="account-subtitle" style="font-size: 16px; color: #333; text-align: center;">
                                First time?
                                <a href="{{ route('register') }}"
                                    style="color: #0f813c; font-weight: bold; text-decoration: underline;">
                                    Sign up
                                </a>
                            </p>


                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="assets/js/jquery-3.6.0.min.js"></script>
    <script src="assets/plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/feather.min.js"></script>
    <script src="assets/js/script.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        $(document).ready(function() {
            // Configure toastr
            toastr.options = {
                "closeButton": true,
                "debug": false,
                "newestOnTop": true,
                "progressBar": true,
                "positionClass": "toast-top-right",
                "preventDuplicates": false,
                "onclick": null,
                "showDuration": "300",
                "hideDuration": "1000",
                "timeOut": "5000",
                "extendedTimeOut": "1000",
                "showEasing": "swing",
                "hideEasing": "linear",
                "showMethod": "fadeIn",
                "hideMethod": "fadeOut"
            };

            // Show registration success modal with username
            @if (session('registration_success') && session('registered_username'))
                const username = '{{ session('registered_username') }}';
                Swal.fire({
                    icon: 'success',
                    title: 'Registration Successful!',
                    html: `
                        <div style="text-align: left; padding: 10px 0;">
                            <p style="font-size: 15px; margin-bottom: 10px;">
                                Please use the following username to login:
                            </p>
                            <div style="background-color: #f8f9fa; border: 2px solid #0f813c; border-radius: 5px; padding: 15px; margin: 15px 0; text-align: center;">
                                <p style="margin: 0; font-size: 18px; font-weight: bold; color: #0f813c;">
                                    <i class="fas fa-user"></i> ${username}
                                </p>
                            </div>

                            <p style="font-size: 14px; color: #666; margin-top: 10px;">
                                <strong>Note:</strong> After logging in, please complete your HR form details.
                            </p>
                        </div>
                    `,
                    confirmButtonText: 'Got it, I understand',
                    confirmButtonColor: '#0f813c',
                    allowOutsideClick: false,
                    allowEscapeKey: false,
                    showCloseButton: false,
                    customClass: {
                        popup: 'swal2-popup-custom',
                        title: 'swal2-title-custom',
                        htmlContainer: 'swal2-html-container-custom'
                    },
                    didOpen: () => {
                        // Focus on password field after modal closes
                        Swal.getConfirmButton().addEventListener('click', function() {
                            setTimeout(() => {
                                document.getElementById('password').focus();
                            }, 100);
                        });
                    }
                });
            @endif

            // Show error toast for login errors
            @if ($errors->has('login_error'))
                toastr.error('{{ $errors->first('login_error') }}', 'Login Failed');
            @endif

            // Show error toast for maintenance mode or other errors
            @if (session('error'))
                toastr.error('{{ session('error') }}', 'Access Denied');
            @endif

            // Show success message if any
            @if (session('success'))
                toastr.success('{{ session('success') }}', 'Success');
            @endif

            // Handle login success - show loader then redirect
            @if (session('login_success'))
                // Show loader overlay
                var loaderHTML =
                    '<div id="login-loader" style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(255, 255, 255, 0.95); z-index: 9999; display: flex; flex-direction: column; align-items: center; justify-content: center;">' +
                    '<div class="spinner-border text-success" role="status" style="width: 3rem; height: 3rem; margin-bottom: 1rem;">' +
                    '<span class="visually-hidden">Loading...</span>' +
                    '</div>' +
                    '<p style="font-size: 1.1rem; color: #007A33; font-weight: 500;">Login successful, loading...</p>' +
                    '</div>';
                $('body').append(loaderHTML);

                // Redirect after a brief delay
                setTimeout(function() {
                    window.location.href = '{{ session('redirect_url', route('dashboard')) }}';
                }, 1000);
            @endif
        });
    </script>
    <style>
        .swal2-popup-custom {
            border-radius: 10px;
            padding: 20px;
        }

        .swal2-title-custom {
            color: #0f813c;
            font-size: 24px;
            font-weight: bold;
        }

        .swal2-html-container-custom {
            font-size: 15px;
            line-height: 1.6;
        }
    </style>
</body>

</html>
