<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome to CCBRT</title>
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

        /* .header {
            background-color: #007A33;
            color: white;
            padding: 20px;
            text-align: center;
        }

        .header img {
            height: 60px;
            width: auto;
        } */

        .content {
            padding: 15px;
            font-size: 16px;
            line-height: 1.6;
        }

        a {
            color: #007A33;
            text-decoration: none;
        }

        .footer {
            text-align: center;
            margin-top: 20px;
            padding: 20px;
            background-color: #f1f1f1;
        }

        .footer img {
            width: 100%;
            object-fit: cover;
        }

        .footer-text {
            margin-top: 10px;
            font-size: 12px;
            color: #888;
        }

        @media only screen and (max-width: 600px) {
            .container {
                width: 100%;
                padding: 10px;
            }

            .header img {
                height: 50px;
            }
        }
    </style>
</head>

<body>

    <div class="container">
        <!-- Header Section -->
        {{-- <div class="header">
            <img src="https://ccbrt.org/wp-content/uploads/2021/12/cropped-CCBRT_OFFICIAL_LOGO.jpg" alt="Logo">
        </div> --}}

        <!-- Main Content Section -->
        <div class="content">
            <p>Dear {{ $user->fname }},</p>

            <p>Welcome to the <strong>eDocs System</strong>! Your account has been successfully created.</p>

            <p><strong>Login credentials:</strong></p>
            <ul>
                <li><strong>Username:</strong> {{ strtolower($user->fname) }}.{{ strtolower($user->lname) }}</li>
                <li><strong>Password:</strong> The one you set during registration</li>
            </ul>

            <p>Please log in and complete your HR form details as the next step.</p>

            <p>
                <a href="http://192.168.2.93/" target="_blank">Click here to login to the system</a>
            </p>

            <p>If you need assistance or have questions, don’t hesitate to contact the HR team.</p>

            <p>We’re excited to have you onboard!</p>

            <p>Warm regards,<br>
                <strong>CCBRT HR Department</strong>
            </p>
        </div>

        <!-- Footer Section -->
        <div class="footer">
            <img src="https://ci3.googleusercontent.com/mail-sig/AIorK4yVBcxuubxZyVOqjc2pjJQQbWTCGT47UwuYDQJVgQEUIePd3iKM7pgwWnpdqElgXvkwKnBFWAk"
                alt="Footer Logo">
            <div class="footer-text">Powered by CCBRT</div>
        </div>
    </div>

</body>

</html>
