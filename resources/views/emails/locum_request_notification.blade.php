<!DOCTYPE html>
<html>
<head>
    <title>Locum Request Notification</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
    <h2>Locum Request Notification</h2>
    <p>Dear {{ $recipient->fname }} {{ $recipient->lname }},</p>

    @if($action === 'approve')
        <p>A locum request requires your approval for the following step: <strong>{{ $step }}</strong>.</p>
        <h3>Request Details:</h3>
        <ul>
            <li><strong>Request ID:</strong> {{ $locumRequest->id }}</li>
            <li><strong>Requestor:</strong> {{ $locumRequest->user->fname }} {{ $locumRequest->user->lname }}</li>
            <li><strong>Department:</strong> {{ $locumRequest->user->department->dept_name ?? 'N/A' }}</li>
            <li><strong>Locum Month:</strong> {{ $locumRequest->locum_month }} {{ $locumRequest->locum_year }}</li>
            <li><strong>Number of Days:</strong> {{ $locumRequest->number_of_days }}</li>
            <li><strong>Total Hours:</strong> {{ $locumRequest->total_hours }}</li>
            <li><strong>Total Amount:</strong> {{ number_format($locumRequest->total_amount, 2) }} TZS</li>
        </ul>
        <p>Please review and take action on this request:</p>
        <a href="{{ route('locum-requests.view') }}" style="display: inline-block; padding: 10px 20px; background-color: #007bff; color: #fff; text-decoration: none; border-radius: 5px;">View Request</a>
    @elseif($action === 'final_approve')
        <p>Your locum request has been fully approved.</p>
        <h3>Request Details:</h3>
        <ul>
            <li><strong>Request ID:</strong> {{ $locumRequest->id }}</li>
            <li><strong>Locum Month:</strong> {{ $locumRequest->locum_month }} {{ $locumRequest->locum_year }}</li>
            <li><strong>Number of Days:</strong> {{ $locumRequest->number_of_days }}</li>
            <li><strong>Total Hours:</strong> {{ $locumRequest->total_hours }}</li>
            <li><strong>Total Amount:</strong> {{ number_format($locumRequest->total_amount, 2) }} TZS</li>
        </ul>
    @elseif($action === 'reject')
        <p>Your locum request has been rejected.</p>
        <h3>Request Details:</h3>
        <ul>
            <li><strong>Request ID:</strong> {{ $locumRequest->id }}</li>
            <li><strong>Locum Month:</strong> {{ $locumRequest->locum_month }} {{ $locumRequest->locum_year }}</li>
            <li><strong>Rejection Reason:</strong> {{ $rejectionReason }}</li>
        </ul>
    @endif

    <p>Thank you,<br>CCBRT Team</p>
</body>
</html>
