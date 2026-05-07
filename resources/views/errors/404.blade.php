<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>404 - Page Not Found</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background: #f5f5f5;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
            color: #333;
        }

        .error-container {
            max-width: 500px;
            width: 100%;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }

        .error-header {
            background: white;
            padding: 40px 30px 20px;
            text-align: center;
            border-bottom: 1px solid #dee2e6;
        }

        .error-icon {
            font-size: 80px;
            margin-bottom: 20px;
            color: #28a745;
        }

        .error-code {
            font-size: 72px;
            font-weight: 700;
            margin: 10px 0;
            color: #212529;
        }

        .error-title {
            font-size: 24px;
            font-weight: 600;
            margin-top: 10px;
            color: #212529;
        }

        .error-body {
            padding: 30px;
            text-align: center;
        }

        .error-message {
            font-size: 16px;
            color: #6c757d;
            line-height: 1.6;
            margin-bottom: 30px;
        }

        .button-group {
            display: flex;
            gap: 10px;
            justify-content: center;
            flex-wrap: wrap;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 20px;
            border-radius: 4px;
            text-decoration: none;
            font-weight: 500;
            font-size: 14px;
            transition: all 0.2s ease;
            border: 1px solid transparent;
            cursor: pointer;
        }

        .btn-secondary {
            background: #28a745;
            color: white;
            border-color: #28a745;
        }

        .btn-secondary:hover {
            background: #218838;
            border-color: #1e7e34;
            color: white;
            text-decoration: none;
        }

        .btn-outline-secondary {
            background: white;
            color: #28a745;
            border-color: #28a745;
        }

        .btn-outline-secondary:hover {
            background: #28a745;
            color: white;
            text-decoration: none;
        }

        @media (max-width: 768px) {
            .error-code {
                font-size: 56px;
            }

            .error-title {
                font-size: 20px;
            }

            .error-icon {
                font-size: 60px;
            }

            .button-group {
                flex-direction: column;
            }

            .btn {
                width: 100%;
                justify-content: center;
            }
        }
    </style>
</head>

<body>
    <div class="error-container">
        <div class="error-header">
            <div class="error-icon">
                <i class="fas fa-exclamation-triangle"></i>
            </div>
            <div class="error-code">404</div>
            <div class="error-title">Page Not Found</div>
        </div>

        <div class="error-body">
            <p class="error-message">
                The page you are looking for could not be found.
            </p>

            <div class="button-group">
                <a href="javascript:history.back()" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i>
                    Go Back
                </a>
                @auth
                    @if(Route::has('dashboard'))
                        <a href="{{ route('dashboard') }}" class="btn btn-outline-secondary">
                            <i class="fas fa-home"></i>
                            Dashboard
                        </a>
                    @else
                        <a href="{{ url('/') }}" class="btn btn-outline-secondary">
                            <i class="fas fa-home"></i>
                            Home
                        </a>
                    @endif
                @else
                    <a href="{{ url('/') }}" class="btn btn-outline-secondary">
                        <i class="fas fa-home"></i>
                        Home
                    </a>
                @endauth
            </div>
        </div>
    </div>
</body>

</html>
