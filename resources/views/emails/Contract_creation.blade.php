<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contract Notification</title>
    <style>
        body { font-family: Arial, sans-serif; color: #333; background-color: #f9f9f9; margin: 0; padding: 0; }
        .container { max-width: 600px; margin: 20px auto; background-color: #fff; padding: 20px; border: 1px solid #ddd; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
        p { margin: 10px 0; }
        a { color: #007A33; text-decoration: none; }
        .footer { text-align: center; margin-top: 20px; padding: 20px; background-color: #f1f1f1; }
        .footer img { width: 100%; object-fit: cover; }
        .content { padding: 15px; font-size: 16px; line-height: 1.6; }
        .footer-text { margin-top: 10px; font-size: 12px; color: #888; }
        table { width: 100%; border-collapse: collapse; font-size: 15px; margin-top: 10px; }
        table td { border: 1px solid #ddd; padding: 8px; }
        table tr:nth-child(even) { background-color: #f9f9f9; }
        table tr:nth-child(odd) { background-color: #f1f1f1; }
        table td:first-child { font-weight: bold; width: 40%; }
    </style>
</head>

<body>
    <div class="container">
        <div class="content">
            <p>Dear {{ $contract->recipientName ?? 'Team' }},</p>

            <p>
                A new contract has been <strong style="color:#007A33;">successfully added</strong>
                to the Contracts database in the <strong>e-Docs system</strong>.
            </p>

            <p><strong>📄 Contract Details</strong></p>
            <table>
                <tr>
                    <td>Contract Name</td>
                    <td>{{ $contract->title }}</td>
                </tr>
                <tr>
                    <td>Vendor</td>
                    <td>{{ $contract->vendor->name ?? 'N/A' }}</td>
                </tr>
                <tr>
                    <td>Department</td>
                    <td>{{ $contract->department->dept_name ?? 'N/A' }}</td>
                </tr>
                <tr>
                    <td>Contract Value</td>
                    <td>{{ $contract->cost }} {{ $contract->currency ?? '' }}</td>
                </tr>
                <tr>
                    <td>Start Date</td>
                    <td>{{ $contract->creation_date }}</td>
                </tr>
                <tr>
                    <td>End Date</td>
                    <td>{{ $contract->end_date }}</td>
                </tr>
                <tr>
                    <td>Uploaded By</td>
                    <td>{{ $contract->creator->fname ?? 'N/A' }} {{ $contract->creator->lname ?? '' }}</td>
                </tr>
            </table>

            <p style="margin-top:20px;">
                You can log in to the 
                <a href="http://192.168.2.93/" style="color:#007A33;font-weight:bold;">e-Docs system</a> 
                to review the full contract.
            </p>

            <p>Best regards,</p>
            <p><strong>CCBRT IT Support Desk</strong></p>
        </div>

        <div class="footer">
            <img src="https://ci3.googleusercontent.com/mail-sig/AIorK4yVBcxuubxZyVOqjc2pjJQQbWTCGT47UwuYDQJVgQEUIePd3iKM7pgwWnpdqElgXvkwKnBFWAk"
                alt="Footer Logo">
            <div class="footer-text">Powered by CCBRT e-Docs System</div>
        </div>
    </div>
</body>
</html>
