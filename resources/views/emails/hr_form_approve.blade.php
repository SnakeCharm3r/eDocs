<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HR Form Approval</title>
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

        ul {
            list-style-type: none;
            padding-left: 0;
        }

        ul li {
            margin-bottom: 5px;
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

        .content {
            padding: 15px;
            font-size: 16px;
            line-height: 1.6;
        }

        .footer-text {
            margin-top: 10px;
            font-size: 12px;
            color: #888;
        }

        /* Responsive adjustments */
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
        {{-- <!-- Header Section -->
        <div class="header">
            <img src="https://ccbrt.org/wp-content/uploads/2021/12/cropped-CCBRT_OFFICIAL_LOGO.jpg" alt="Logo">
            <h2>e-Docs - HR Form</h2>
        </div> --}}

        <!-- Main Content Section -->
        <div class="content">
            <p>Dear {{ $user->fname }},</p>

            <p>We are pleased to inform you that your HR form has been approved.</p>

            <p><strong>Form Details:</strong></p>
            <ul>
                {{-- <li><strong>Submitted On:</strong> {{ \Carbon\Carbon::parse($workflow->created_at)->format('F j, Y') }}
                </li> --}}
                <li><strong>Approval Date:</strong> {{ $approvalDate }}</li>
                <li><strong>Status:</strong> Approved</li>
            </ul>

            <p>You can proceed with other requests by clicking the link below:</p>
            <p><a href="http://192.168.2.93/" target="_blank">Click here</a></p>

            <p>Regards,</p>
            <p><strong>CCBRT HR Department</strong></p>
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
