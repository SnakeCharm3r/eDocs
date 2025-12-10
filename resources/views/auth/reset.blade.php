<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Password Reset</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            color: #333;
            background-color: #f9f9f9;
            margin: 0;
            padding: 0;
        }

        .container {
            max-width: 600px;
            margin: 20px auto;
            background-color: #fff;
            padding: 20px;
            border: 1px solid #ddd;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }

        .header {
            background-color: #007A33;
            color: white;
            padding: 20px;
            text-align: center;
            position: relative;
        }

        .header img {
            height: 60px;
            width: auto;
        }

        .header h2 {
            margin: 0;
            font-size: 24px;
            color: #fff;
        }

        p {
            margin: 10px 0;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
        }

        .form-group input {
            width: 96%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }

        .btn {
            background-color: #007A33;
            color: white;
            padding: 10px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            width: 100%;
        }

        .footer {
            text-align: center;
            margin-top: 20px;
            padding: 20px;
            background-color: #f1f1f1;
        }

        .footer img {
            /* height: 100px; */
            width: 100%;
            object-fit: cover;
        }

        .footer-text {
            margin-top: 10px;
            font-size: 12px;
            color: #888;
        }

        /* Responsive Styles */
        @media (max-width: 768px) {
            .container {
                margin: 10px;
                padding: 15px;
            }

            .header h2 {
                font-size: 20px;
            }

            .form-group input {
                padding: 8px;
            }

            .btn {
                padding: 10px;
            }

            .footer img {
                height: 80px;
            }

            .footer-text {
                font-size: 10px;
            }
        }

        @media (max-width: 480px) {
            .header h2 {
                font-size: 18px;
            }

            .form-group label,
            .form-group input {
                font-size: 14px;
            }

            .btn {
                padding: 8px;
            }

            .footer img {
                height: 60px;
            }
        }
    </style>
</head>

<body>

    <div class="container">
        <div class="header">
            <img src="https://newsite.ccbrt.org/wp-content/uploads/2021/12/cropped-CCBRT_OFFICIAL_LOGO.jpg"
                alt="Logo">
            <h2>Reset Your Password</h2>
        </div>

        <div class="content">
            <p>Dear User,</p>

            <p>To reset your password, please fill out the form below:</p>
            @if (session('status'))
                <div class="alert alert-success">
                    {{ session('status') }}
                </div>
            @endif


            @if ($errors->any())
                <div class="alert alert-danger">
                    @foreach ($errors->all() as $error)
                        <p>{{ $error }}</p>
                    @endforeach
                </div>
            @endif
            <form action="{{ route('password.update') }}" method="POST">
                @csrf
                <input type="hidden" name="token" value="{{ $token }}">

                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input type="email" name="email" class="form-control" required>
                </div>

                <div class="form-group">
                    <label for="password">New Password</label>
                    <input type="password" name="password" class="form-control" required>
                </div>

                <div class="form-group">
                    <label for="password_confirmation">Confirm Password</label>
                    <input type="password" name="password_confirmation" class="form-control" required>
                </div>

                <button type="submit" class="btn">Reset Password</button>
            </form>
        </div>

        <!-- Footer Section -->
        <div class="footer">
            <img src="https://ci3.googleusercontent.com/mail-sig/AIorK4yVBcxuubxZyVOqjc2pjJQQbWTCGT47UwuYDQJVgQEUIePd3iKM7pgwWnpdqElgXvkwKnBFWAk"
                alt="Footer Logo">
            <div class="footer-text">Powered by CCBRT e-Docs System</div>
        </div>
    </div>

</body>

</html>
