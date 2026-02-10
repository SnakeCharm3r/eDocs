{{-- resources/views/emails/final_approved.blade.php --}}
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Request Fully Approved</title>
</head>

<body>
    <p>Dear {{ $requester->name }},</p>
    <p>We are happy to inform you that your request (ID: {{ $changeRequest->id }}) has been fully approved by all necessary approvers.</p>

    <ul>
        <li><strong>Request Title:</strong> {{ $changeRequest->title }}</li>
        <li><strong>Requested By:</strong> {{ $changeRequest->requestedBy->name }}</li>
        <li><strong>Date of Request:</strong> {{ $changeRequest->created_at->format('d F Y') }}</li>
    </ul>

    <p>If you have any questions or concerns, please do not hesitate to contact the IT Support Desk.</p>

    <p>Regards,<br>CCBRT IT Support Desk</p>
</body>

</html>
