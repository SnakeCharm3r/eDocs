<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Password Reset Notification</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f9f9f9;
            color: #333;
            padding: 40px;
        }

        .container {
            max-width: 600px;
            background-color: #ffffff;
            border: 1px solid #ddd;
            border-radius: 6px;
            padding: 30px;
            margin: auto;
        }

        h1 {
            color: #2c3e50;
        }

        a {
            color: #3498db;
            text-decoration: none;
        }

        .password {
            font-size: 18px;
            font-weight: bold;
            background-color: #f4f4f4;
            padding: 10px;
            border-radius: 4px;
            display: inline-block;
            margin: 10px 0;
        }

        .footer {
            margin-top: 30px;
            font-size: 14px;
            color: #777;
        }
    </style>
</head>

<body>
    <div class="container">
        <h1>Password Reset Notification</h1>

        <p>Hello {{ $user->username }},</p>

        <p>Your password has been reset by an administrator. Your new password is:</p>

        <p class="password">{{ $newPassword }}</p>

        <p>Please log in using this new password and change it immediately for your security.</p>

        <p>
            <a href="{{ url('/login') }}">Click here to log in</a>
        </p>

        <p>Thank you,</p>
        <p class="footer">The Support Team</p>
        {{-- <p class="footer">Your Company Name</p> --}}
    </div>
</body>

</html>
