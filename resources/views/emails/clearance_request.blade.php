<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Exit Clearance Form Notification</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            color: #333;
            background-color: #f9f9f9;
            margin: 0;
            padding: 0;
        }

        .email-wrapper {
            max-width: 600px;
            margin: 20px auto;
            background-color: #fff;
            border: 1px solid #ddd;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }

        /* Header */
        .email-header {
            background-color: #007A33;
            color: white;
            padding: 20px;
            text-align: center;
        }

        .email-header img {
            height: 50px;
            width: auto;
            margin-bottom: 10px;
        }

        .email-header h2 {
            margin: 0;
            font-size: 22px;
            color: #fff;
        }

        /* Content */
        .email-content {
            padding: 20px;
            font-size: 16px;
            line-height: 1.6;
        }

        .email-content ul {
            list-style-type: none;
            padding-left: 0;
        }

        .email-content ul li {
            margin-bottom: 5px;
        }

        .email-content a {
            color: #007A33;
            text-decoration: none;
        }

        /* Footer */
        .email-footer {
            background-color: #f1f1f1;
            text-align: center;
            padding: 15px;
            font-size: 12px;
            color: #888;
        }

        .email-footer img {
            width: 100%;
            object-fit: cover;
            margin-bottom: 5px;
        }

    </style>
</head>
<body>

<div class="email-wrapper">
    <!-- HEADER -->
    <div class="email-header">
        <img src="https://ccbrt.org/wp-content/uploads/2021/12/cropped-CCBRT_OFFICIAL_LOGO.jpg" alt="CCBRT Logo">
        <h2>CCBRT e-Docs System</h2>
    </div>

    <!-- CONTENT -->
    <div class="email-content">
        <p>Dear {{ $approver->fname }},</p>

        <p>You have a new request awaiting your approval in the e-Docs system.</p>

        <p><strong>Request Details:</strong></p>
        <ul>
            <li><strong>Requester Name:</strong> {{ $requestDetails['forwarded_by'] }}</li>
            <li><strong>Requested Form:</strong> {{ $requestDetails['request'] }}</li>
            <li><strong>Date of Request:</strong> {{ $requestDetails['requestDate'] }}</li>
        </ul>

        <p>Please log in to the e-Docs system to review the request using the following link:</p>
        <p><a href="http://192.168.2.93/">Go to e-Docs System</a></p>

        <p>Regards,<br>
        <strong>CCBRT IT Support Desk</strong></p>
    </div>

    <!-- FOOTER -->
    <div class="email-footer">
        <img src="https://ci3.googleusercontent.com/mail-sig/AIorK4yVBcxuubxZyVOqjc2pjJQQbWTCGT47UwuYDQJVgQEUIePd3iKM7pgwWnpdqElgXvkwKnBFWAk" alt="Footer Banner">
        <div>Powered by CCBRT e-Docs System</div>
    </div>
</div>

</body>
</html>
