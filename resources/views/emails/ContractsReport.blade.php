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
        a { color: #2da55f; text-decoration: none; }
        .footer { text-align: center; margin-top: 20px; padding: 20px; background-color: #f1f1f1; }
        .footer img { width: 100%; object-fit: cover; }
        .content { padding: 15px; font-size: 16px; line-height: 1.6; }
        .footer-text { margin-top: 10px; font-size: 12px; color: #888; }
        table { width: 100%; border-collapse: collapse; font-size: 15px; margin-top: 10px; }
        table td, table th { border: 1px solid #ddd; padding: 8px; }
        table tr:nth-child(even) { background-color: #f9f9f9; }
        table tr:nth-child(odd) { background-color: #f1f1f1; }
        table td:first-child, table th:first-child { font-weight: bold; width: 40%; }
    </style>
</head>

<body>
    <div class="container">
        <div class="content">
            <p>Dear Team,</p>

            <p>The following contracts have <strong style="color:#D32F2F;">expired</strong>:</p>

            <table>
                <thead>
                    <tr>
                        <th>Contract Name</th>
                        <th>Vendor</th>
                        <th>Department</th>
                        <th>Value</th>
                        <th>Start Date</th>
                        <th>End Date</th>
                        <th>Added By</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($contracts as $contract)
                    <tr>
                        <td>{{ $contract->title }}</td>
                        <td>{{ $contract->vendor->name ?? '' }}</td>
                        <td>{{ $contract->department->dept_name ?? '' }}</td>
                        <td>{{ $contract->cost }}</td>
                        <td>{{ $contract->created_date }}</td>
                        <td>{{ $contract->end_date }}</td>
                        <td>{{ trim(($contract->creator->fname ?? '').' '.($contract->creator->lname ?? '')) }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>

            <p style="margin-top:20px;">
                You can log in to the 
                <a href="http://192.168.2.93/" style="color:#1fb85e;font-weight:bold;">e-Docs system</a> 
                to review the full contracts.
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
