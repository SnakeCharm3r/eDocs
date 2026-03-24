<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Requisition Expiring Soon</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            border: 1px solid #ddd;
            border-radius: 8px;
        }
        .header {
            background: #f8f9fa;
            padding: 20px;
            text-align: center;
            border-bottom: 1px solid #ddd;
        }
        .content {
            padding: 20px;
        }
        .alert {
            background: #fff3cd;
            border: 1px solid #ffeeba;
            color: #856404;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
        }
        .footer {
            background: #f8f9fa;
            padding: 15px;
            text-align: center;
            border-top: 1px solid #ddd;
            font-size: 12px;
            color: #666;
        }
        .btn {
            display: inline-block;
            padding: 10px 20px;
            background: #007bff;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin: 10px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2>⚠️ Requisition Expiration Notice</h2>
        </div>
        
        <div class="content">
            <p>Dear <strong>{{ $user->fname }} {{ $user->lname }}</strong>,</p>
            
            <div class="alert">
                <strong>⏰ Time-Sensitive Action Required</strong><br>
                Your requisition <strong>{{ $requisition->access_id }}</strong> will expire in <strong>{{ $daysRemaining }} days</strong>.
            </div>
            
            <h3>Requisition Details:</h3>
            <ul>
                <li><strong>Access ID:</strong> {{ $requisition->access_id }}</li>
                <li><strong>Position:</strong> {{ $requisition->jobTitle->title ?? 'N/A' }}</li>
                <li><strong>Department:</strong> {{ $requisition->dept_name ?? 'N/A' }}</li>
                <li><strong>Current Status:</strong> {{ $requisition->getStatusLabel() }}</li>
                <li><strong>Expiration Date:</strong> {{ $expiryDate }}</li>
            </ul>
            
            <h3>What happens next?</h3>
            <p>If you don't take action before the expiration date, your requisition will be:</p>
            <ul>
                <li>❌ <strong>Permanently expired</strong></li>
                <li>❌ <strong>No longer editable</strong></li>
                <li>❌ <strong>Cannot be resubmitted</strong> without creating a new requisition</li>
            </ul>
            
            <h3>📋 Required Actions:</h3>
            <ol>
                <li><strong>Log in</strong> to the eDocs system</li>
                <li><strong>Navigate</strong> to your requisitions</li>
                <li><strong>Open</strong> requisition {{ $requisition->access_id }}</li>
                <li><strong>Make necessary edits</strong> and resubmit for approval</li>
            </ol>
            
            <p style="text-align: center;">
                <a href="{{ url('/requisitions') }}" class="btn">View My Requisitions</a>
            </p>
            
            <hr>
            
            <p><strong>Need assistance?</strong></p>
            <p>If you have questions or need help with your requisition, please contact:</p>
            <ul>
                <li>📧 Email: support@edocs.system</li>
                <li>📞 Phone: System Administrator</li>
                <li>🌐 Visit: <a href="{{ url('/') }}">eDocs Portal</a></li>
            </ul>
        </div>
        
        <div class="footer">
            <p>This is an automated notification from the eDocs system.</p>
            <p>Generated on: {{ now()->format('d M Y, H:i') }}</p>
        </div>
    </div>
</body>
</html>
