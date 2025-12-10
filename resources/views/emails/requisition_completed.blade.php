<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Approval Request Notification</title>
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
            /* height: 100px; */
            /* Increased the height for a larger footer image */
            width: 100%;
            /* Ensures the image spans the entire footer width */
            object-fit: cover;
            /* Maintains aspect ratio while covering the footer */
        }

        /* Improve text layout */
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
        {{-- <div class="header">
            <img src="https://ccbrt.org/wp-content/uploads/2021/12/cropped-CCBRT_OFFICIAL_LOGO.jpg"
                alt="Logo">
            <h2>e-Docs System - Approval Request</h2>
        </div> --}}

        <div class="content">
            <p>Dear {{ $user->fname }},</p>

         <p>Your Recruitment Requisition Form has been approved by all necessary parties and submitted to HR for recruitment processing.</p>

            <p><strong>Request Summary:</strong></p>
            <ul>
             <li><strong>Job Title:</strong> {{ $requisition->job_title }}</li>
             <li><strong>Submission Date:</strong> {{ $requisition->created_at->format('d F Y') }}</li>
             <li><strong>Status:</strong> Approved and forwarded to HR</li>

            </ul>

     <p>HR will now begin the recruitment process. You will be updated accordingly.</p>

     <p><a href="http://192.168.2.93/">e-Docs System</a></p>

            <p>Regards,</p>
            <p><strong>CCBRT IT Support Desk</strong></p>
        </div>

        <!-- Footer Section -->
        <div class="footer">
            <img src="https://ci3.googleusercontent.com/mail-sig/AIorK4yVBcxuubxZyVOqjc2pjJQQbWTCGT47UwuYDQJVgQEUIePd3iKM7pgwWnpdqElgXvkwKnBFWAk"
                alt="Footer Logo">
            <div class="footer-text">Powered by CCBRT e-Docs System</div>
        </div>
    </div>
    {{-- {{ dd($requestDetails) }} --}}

</body>

</html>
