<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Locum Rate Change Notification</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            background-color: #459c51;
            color: white;
            padding: 10px;
            text-align: center;
            border-radius: 5px 5px 0 0;
        }
        .header h2 {
            margin: 0;
            font-size: 16px;
            font-weight: normal;
        }
        .content {
            background-color: #f9f9f9;
            padding: 20px;
            border: 1px solid #ddd;
            border-top: none;
        }
        .info-box {
            background-color: white;
            border-left: 4px solid #459c51;
            padding: 15px;
            margin: 15px 0;
        }
        .rate-change {
            display: table;
            width: 100%;
            table-layout: fixed;
            padding: 10px;
            background-color: #fff;
            border: 1px solid #ddd;
            border-radius: 4px;
            margin: 10px 0;
        }
        .old-rate {
            color: #dc3545;
            text-decoration: line-through;
        }
        .new-rate {
            color: #28a745;
            font-weight: bold;
        }
        .arrow {
            font-size: 20px;
            color: #666;
        }
        .footer {
            text-align: center;
            padding: 20px;
            color: #666;
            font-size: 12px;
        }
        .button {
            display: inline-block;
            padding: 10px 20px;
            background-color: #459c51;
            color: white !important;
            text-decoration: none;
            border-radius: 4px;
            margin: 15px 0;
            font-weight: normal;
        }
    </style>
</head>
<body>
    <div class="header">
        <h2>Locum Rate Change Notification</h2>
    </div>
    
    <div class="content">
        @if($isLineManager)
            <p>Dear Line Manager,</p>
            <p>The locum rate for <strong>{{ $user->fname }} {{ $user->lname }}</strong> (CCBRT Code: {{ $user->ccbrt_code }}) has been changed by <strong>HRBP</strong>.</p>
        @else
            <p>Dear {{ $user->fname }} {{ $user->lname }},</p>
            <p>Your locum rate has been updated by <strong>HRBP</strong>. Please see the details below:</p>
        @endif

        <div class="info-box">
            <h3>Rate Change Details</h3>
            
            <div class="rate-change">
                <div>
                    <strong>Education Level:</strong><br>
                    <span class="old-rate">{{ $oldEducationLevel }}</span>
                    <span class="arrow"> → </span>
                    <span class="new-rate">{{ $newEducationLevel }}</span>
                </div>
            </div>
            
            <div class="rate-change">
                <div>
                    <strong>Locum Rate:</strong><br>
                    <span class="old-rate">TZS {{ number_format($oldLocumRate, 2) }}</span>
                    <span class="arrow"> → </span>
                    <span class="new-rate">TZS {{ number_format($newLocumRate, 2) }}</span>
                </div>
            </div>

            <p><strong>Changed by:</strong> {{ $changedBy->fname }} {{ $changedBy->lname }} ({{ $changedBy->username }})</p>
            <p><strong>Date:</strong> {{ now()->format('d F Y, h:i A') }}</p>
        </div>

        @if(!$isLineManager)
            <p>This change will apply to all future locum claims. If you have any questions or concerns, please contact <strong>HRBP</strong>.</p>
        @else
            <p>Please note this change for your records. If you have any questions, please contact <strong>HRBP</strong>.</p>
        @endif

        <div style="text-align: center;">
            <a href="{{ $viewUrl }}" class="button">View Agreement</a>
        </div>
    </div>

    <div class="footer">
        <p>This is an automated notification from CCBRT Hospital.</p>
        <p>Please do not reply to this email.</p>
    </div>
</body>
</html>

