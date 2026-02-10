<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contract Renewal Notification</title>
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

        p {
            margin: 10px 0;
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
    </style>
</head>

<body>
    <div class="container">
        <div class="content">
            <p>Dear {{ $contractRenewal->department?->line_manager->fname ?? 'Team' }},</p>

            <p>
                A <strong>new contract renewal process</strong> has been initiated in the e-Docs system.
                Kindly log in to review and take necessary action.
            </p>

            <p><strong>Contract:</strong> {{ $contractRenewal->contract->title ?? 'N/A' }}</p>
            <p><strong>Department:</strong> {{ $contractRenewal->department?->dept_name ?? 'N/A' }}</p>
            <p><strong>Vendor:</strong> {{ $contractRenewal->vendor?->name ?? 'N/A' }}</p>
            <p><strong>Initiated By:</strong> {{ $contractRenewal->creator->fname ?? '' }} {{ $contractRenewal->creator->lname ?? '' }}</p>

            <p>Please log in to the e-Docs system using the following link:</p>
            <p><a href="http://192.168.2.93/">e-Docs System</a></p>

            <p>Regards,</p>
            <p><strong>CCBRT IT Support Desk</strong></p>
        </div>

        <div class="footer">
            <img src="https://ci3.googleusercontent.com/mail-sig/AIorK4yVBcxuubxZyVOqjc2pjJQQbWTCGT47UwuYDQJVgQEUIePd3iKM7pgwWnpdqElgXvkwKnBFWAk" alt="Footer Logo">
            <div class="footer-text">Powered by CCBRT e-Docs System</div>
        </div>
    </div>
</body>
</html>
