<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Your Password - CCBRT eDOCS</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: #333;
            background-color: #f5f5f5;
            margin: 0;
            padding: 0;
            line-height: 1.6;
        }

        .email-wrapper {
            max-width: 600px;
            margin: 40px auto;
            background-color: #ffffff;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .email-header {
            background: linear-gradient(135deg, #0f813c 0%, #0a6b2f 100%);
            color: white;
            padding: 30px 20px;
            text-align: center;
        }

        .email-header img {
            max-width: 150px;
            height: auto;
            margin-bottom: 15px;
        }

        .email-header h1 {
            margin: 0;
            font-size: 24px;
            font-weight: 600;
            color: white;
        }

        .email-body {
            padding: 40px 30px;
        }

        .greeting {
            font-size: 18px;
            color: #333;
            margin-bottom: 20px;
            font-weight: 600;
        }

        .message {
            font-size: 16px;
            color: #555;
            margin-bottom: 25px;
            line-height: 1.8;
        }

        .button-container {
            text-align: center;
            margin: 35px 0;
        }

        .reset-button {
            display: inline-block;
            padding: 14px 40px;
            background: linear-gradient(135deg, #0f813c 0%, #0a6b2f 100%);
            color: #ffffff !important;
            text-decoration: none;
            border-radius: 6px;
            font-weight: 600;
            font-size: 16px;
            box-shadow: 0 4px 12px rgba(15, 129, 60, 0.3);
            transition: all 0.3s ease;
        }

        .reset-button:hover {
            background: linear-gradient(135deg, #0a6b2f 0%, #085025 100%);
            box-shadow: 0 6px 16px rgba(15, 129, 60, 0.4);
            transform: translateY(-2px);
        }

        .info-box {
            background-color: #f8f9fa;
            border-left: 4px solid #0f813c;
            padding: 15px 20px;
            margin: 25px 0;
            border-radius: 4px;
        }

        .info-box p {
            margin: 0;
            font-size: 14px;
            color: #666;
        }

        .info-box strong {
            color: #0f813c;
        }

        .expiry-notice {
            background-color: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 15px 20px;
            margin: 25px 0;
            border-radius: 4px;
        }

        .expiry-notice p {
            margin: 0;
            font-size: 14px;
            color: #856404;
        }

        .alternative-link {
            margin-top: 30px;
            padding-top: 25px;
            border-top: 1px solid #e0e0e0;
        }

        .alternative-link p {
            font-size: 14px;
            color: #666;
            margin: 10px 0;
            word-break: break-all;
        }

        .alternative-link a {
            color: #0f813c;
            text-decoration: none;
        }

        .email-footer {
            background-color: #f8f9fa;
            padding: 25px 30px;
            text-align: center;
            border-top: 1px solid #e0e0e0;
        }

        .email-footer p {
            margin: 8px 0;
            font-size: 14px;
            color: #666;
        }

        .email-footer .security-note {
            color: #999;
            font-size: 12px;
            margin-top: 15px;
        }

        @media only screen and (max-width: 600px) {
            .email-wrapper {
                margin: 20px 10px;
                border-radius: 4px;
            }

            .email-body {
                padding: 30px 20px;
            }

            .reset-button {
                padding: 12px 30px;
                font-size: 15px;
            }

            .email-header {
                padding: 25px 15px;
            }

            .email-header h1 {
                font-size: 20px;
            }
        }
    </style>
</head>

<body>
    <div class="email-wrapper">
        <!-- Header -->
        <div class="email-header">
            <h1>🔒 Password Reset Request</h1>
        </div>

        <!-- Body -->
        <div class="email-body">
            <div class="greeting">
                Hello {{ $user->fname ?? $user->username }},
            </div>

            <div class="message">
                <p>We received a request to reset your password for your <strong>CCBRT eDOCS</strong> account.</p>
                <p>If you made this request, please click the button below to reset your password:</p>
            </div>

            <div class="button-container">
                <a href="{{ $url }}" class="reset-button">Reset My Password</a>
            </div>

            <div class="expiry-notice">
                <p>
                    <strong>⏰ Important:</strong> This password reset link will expire in {{ $count }} minutes.
                    Please reset your password as soon as possible.
                </p>
            </div>

            <div class="info-box">
                <p>
                    <strong>🔐 Security Tip:</strong> If you did not request a password reset, please ignore this email.
                    Your password will remain unchanged. For security reasons, never share this link with anyone.
                </p>
            </div>

            <div class="alternative-link">
                <p><strong>Having trouble clicking the button?</strong></p>
                <p>Copy and paste this URL into your browser:</p>
                <p><a href="{{ $url }}">{{ $url }}</a></p>
            </div>
        </div>

        <!-- Footer -->
        <div class="email-footer">
            <p><strong>CCBRT eDOCS System</strong></p>
            <p>This is an automated email. Please do not reply to this message.</p>
            <p class="security-note">
                If you have any concerns about your account security, please contact the IT support team immediately.
            </p>
        </div>
    </div>
</body>

</html>

