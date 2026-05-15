<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HR Form Approval</title>
    <style>
        body{font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,'Helvetica Neue',Arial,sans-serif;color:#333;background-color:#f5f5f5;margin:0;padding:0;line-height:1.6}
        .email-wrapper{max-width:600px;margin:0 auto;background-color:#ffffff}
        .header{background:linear-gradient(135deg,#007A33 0%,#005a25 100%);color:white;padding:30px 20px;text-align:center}
        .header h1{margin:0;font-size:26px;font-weight:600;color:#fff;letter-spacing:0.5px}
        .content{padding:30px 25px}
        .greeting{font-size:16px;color:#333;margin-bottom:20px}
        .highlight{background-color:#e7f3ff;border-left:3px solid #007A33;padding:12px 15px;margin:15px 0;border-radius:4px}
        .highlight strong{color:#007A33}
        .info-card{background-color:#f8f9fa;border:1px solid #e9ecef;border-radius:8px;padding:20px;margin:25px 0}
        .info-card h3{margin:0 0 15px 0;font-size:16px;color:#007A33;font-weight:600;text-transform:uppercase;letter-spacing:0.5px;border-bottom:2px solid #007A33;padding-bottom:8px}
        .info-row{display:table;width:100%;table-layout:fixed;padding:10px 0;border-bottom:1px solid #e9ecef}
        .info-row:last-child{border-bottom:none}
        .info-label{font-weight:600;color:#555;display:table-cell;width:40%;vertical-align:top}
        .info-value{color:#333;display:table-cell;width:60%;text-align:right;font-weight:500;vertical-align:top}
        .action-button{display:inline-block;background-color:#007A33;color:#ffffff !important;text-decoration:none;padding:12px 30px;border-radius:6px;font-weight:600;font-size:16px;text-align:center;margin:25px 0}
        .button-container{text-align:center}
        .signature{margin-top:25px;color:#333}
        .signature strong{color:#007A33}
        .footer{background-color:#f8f9fa;padding:25px;text-align:center;border-top:1px solid #e9ecef}
        .footer img{max-width:100%;height:auto;margin-bottom:15px}
        .footer-text{font-size:12px;color:#888;margin-top:10px}
        @media only screen and (max-width:600px){.content{padding:20px 15px}.header{padding:25px 15px}.header h1{font-size:22px}.info-row{display:block}.info-label{display:block;width:100%}.info-value{display:block;width:100%;text-align:left;margin-top:4px}.action-button{display:block;width:100%;box-sizing:border-box}}
    </style>
</head>

<body>
    <div class="email-wrapper">
        <div class="header">
            <h1>✅ HR Form Approved</h1>
        </div>

        <div class="content">
            <div class="greeting">
                <p>Dear <strong>{{ $user->fname }}</strong>,</p>
            </div>

            <div class="highlight">
                <p style="margin: 0;">Your HR form has been <strong>approved</strong> successfully.</p>
            </div>

            <div class="info-card">
                <h3>Approval Details</h3>
                <div class="info-row">
                    <span class="info-label">Approval Date:</span>
                    <span class="info-value">{{ $approvalDate }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Status:</span>
                    <span class="info-value" style="color: #007A33; font-weight: 600;">Approved</span>
                </div>
            </div>

            <div class="button-container">
                <a href="{{ config('app.url') }}" class="action-button" target="_blank">
                    Go to e-Docs
                </a>
            </div>

            <div class="signature">
                <p>Best regards,</p>
                <p><strong>CCBRT HR Department</strong></p>
            </div>

            <div class="footer-text" style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #e9ecef; color: #666; font-size: 14px;">
                <p>This is an automated notification. Please do not reply to this email.</p>
            </div>
        </div>

        <div class="footer">
            <img src="https://ci3.googleusercontent.com/mail-sig/AIorK4yVBcxuubxZyVOqjc2pjJQQbWTCGT47UwuYDQJVgQEUIePd3iKM7pgwWnpdqElgXvkwKnBFWAk"
                alt="CCBRT Footer">
            <div class="footer-text">Powered by CCBRT e-Docs System</div>
        </div>
    </div>
</body>

</html>
