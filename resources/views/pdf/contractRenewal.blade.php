<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Contract Renewal Form</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; }
        .card { border: 1px solid #ccc; margin-bottom: 10px; border-radius: 5px; }
        .card-header { background-color: #72d37f; color: #fff; padding: 8px; font-weight: bold; }
        .table { width: 100%; border-collapse: collapse; }
        .table th, .table td { border: 1px solid #000; padding: 6px; text-align: left; }
        .header-row { border:2px solid black; padding:10px; background-color:#f8f9fa; border-radius:5px; display:flex; justify-content:space-between; align-items:center; }
        .logo { width:80px; height:80px; object-fit:contain; }
        h3 { text-align:center; margin:0; }
    </style>
</head>
<body>
    <div class="header-row">
        <img src="{{ public_path('assets/img/ccbrt.jpg') }}" class="logo" alt="Logo">
        <h3>Service | Goods Requisition Form</h3>
    </div>

    <div class="card">
        <div class="card-header">Renewal Contract Details</div>
        <div class="card-body">
            <table class="table">
                <tr><th>Vendor Name</th><td>{{ $contractRenewal->vendor->name ?? 'N/A' }}</td></tr>
                <tr><th>Contract Category</th><td>{{ $contractRenewal->category ?? 'N/A' }}</td></tr>
                <tr><th>Cost</th><td>{{ number_format($contractRenewal->cost, 2) }}</td></tr>
                <tr><th>Duration (Months)</th><td>{{ $contractRenewal->duration_months ?? 'N/A' }}</td></tr>
                <tr><th>Requested By</th><td>{{ $contractRenewal->user->fname ?? 'N/A' }}</td></tr>
                <tr><th>Requested On</th><td>{{ \Carbon\Carbon::parse($contractRenewal->created_at)->format('d F Y') }}</td></tr>
            </table>
        </div>
    </div>

    <div class="card">
        <div class="card-header">Vendor & Urgency Details</div>
        <div class="card-body">
            <table class="table">
                <tr><th>Contract requested status</th><td>{{ $contractRenewal->status ?? 'N/A' }}</td></tr>
                <tr><th>Contract Vendor Option</th><td>{{ $contractRenewal->vendor_option ?? 'N/A' }}</td></tr>
                <tr><th>Associated CCBRT Division</th><td>{{ $contractRenewal->division->name ?? 'N/A' }}</td></tr>
                <tr><th>Impact on Business of the Contract</th><td>{{ $contractRenewal->impact_if_not_requested ?? 'N/A' }}</td></tr>
                <tr><th>Overall Risk of Service/Goods not Renewed</th><td>{{ $contractRenewal->overall_risk ?? 'N/A' }}</td></tr>
            </table>
        </div>
    </div>

    <div class="card">
        <div class="card-header">Approval Workflow</div>
        <div class="card-body">
            <table class="table">
                <thead>
                    <tr><th>Forwarded By</th><th>Status</th><th>Approved / Rejected By</th><th>Signature</th></tr>
                </thead>
                <tbody>
                    @forelse($contractRenewal->histories ?? [] as $history)
                        <tr>
                            <td>{{ $history->forwarded_by ?? 'N/A' }}</td>
                            <td>{{ ucfirst($history->status ?? 'Pending') }}</td>
                            <td>{{ $history->approved_by ?? 'N/A' }}</td>
                            <td>
                                @if(!empty($history->signature))
                                    <img src="{{ public_path('storage/signatures/' . $history->signature) }}" width="80" height="40">
                                @else
                                    <em>No signature</em>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" style="text-align:center;">No approval history available</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>
