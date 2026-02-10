<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Access Denied</title>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background: #f5f5f5;
            margin: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            text-align: center;
            color: #333;
        }

        .loading-container {
            max-width: 400px;
            padding: 20px;
        }

        .spinner {
            border: 4px solid #f3f3f3;
            border-top: 4px solid #007A33;
            border-radius: 50%;
            width: 50px;
            height: 50px;
            animation: spin 1s linear infinite;
            margin: 0 auto 20px;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        p {
            color: #666;
            font-size: 16px;
        }
    </style>
</head>

<body>
    <div class="loading-container">
        <div class="spinner"></div>
        <p>Redirecting...</p>
    </div>

    <script>
        $(document).ready(function() {
            // Show SweetAlert popup
            Swal.fire({
                icon: 'error',
                title: 'Access Denied',
                text: 'You do not have permission to access this page.',
                confirmButtonText: 'Go Back',
                confirmButtonColor: '#007A33',
                allowOutsideClick: false,
                allowEscapeKey: false,
                showCancelButton: false,
                timer: 5000,
                timerProgressBar: true,
                didOpen: () => {
                    Swal.showLoading();
                }
            }).then((result) => {
                // Redirect back or to home
                if (document.referrer && document.referrer !== window.location.href) {
                    window.location.href = document.referrer;
                } else {
                    window.location.href = '{{ url("/") }}';
                }
            });

            // Auto-redirect after 5 seconds if user doesn't click
            setTimeout(function() {
                if (document.referrer && document.referrer !== window.location.href) {
                    window.location.href = document.referrer;
                } else {
                    window.location.href = '{{ url("/") }}';
                }
            }, 5000);
        });
    </script>
</body>

</html>
