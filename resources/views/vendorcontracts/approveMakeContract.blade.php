@extends('layouts.template')

@section('breadcrumb')
    @include('sweetalert::alert')
@endsection

@section('content')
<div class="page-wrapper">
    <div class="container content-fluid">
        <div class="row justify-content-center">
            <div class="col-md-10">

                {{-- Outer Card --}}
                <div class="card shadow-lg mb-4">
                    <div class="card-header bg-61ce70 text-white d-flex justify-content-between align-items-center">
                        <h4 class="mb-0">Contract Renewal Form</h4>
                        <span class="badge rounded-pill bg-success">Version (V1)</span>
                    </div>

                    <div class="card-body">

                        {{-- Header Row --}}
                        <div class="d-flex align-items-center justify-content-between mb-4"
                            style="border:2px solid black; padding:15px; background-color:#f8f9fa; border-radius:5px;">
                            <img src="{{ asset('assets/img/ccbrt.jpg') }}" alt="Logo"
                                style="width:100px; height:100px; object-fit:contain;">
                            <h3 class="fw-bold mb-0 text-center flex-grow-1">Service | Goods Requisition Form</h3>
                        </div>

                        {{-- ============================= --}}
                        {{-- Section 1: Renewal Contract Details --}}
                        {{-- ============================= --}}
                        <div class="card mb-4 shadow-sm">
                            <div class="card-header text-white" style="background-color: #72d37f;">
                                <h5 class="mb-0">Renewal Contract Details</h5>
                            </div>
                            <div class="card-body">
                                <dl class="row mb-0">
                                    <dt class="col-sm-4">Vendor Name</dt>
                                    <dd class="col-sm-8">{{ $contractRenewal->vendor->name ?? 'N/A' }}</dd>

                                    <dt class="col-sm-4">Contract Category</dt>
                                    <dd class="col-sm-8">{{ $contractRenewal->category ?? 'N/A' }}</dd>

                                    <dt class="col-sm-4">Cost</dt>
                                    <dd class="col-sm-8">{{ number_format($contractRenewal->cost, 2) }}</dd>

                                    <dt class="col-sm-4">Duration (Months)</dt>
                                    <dd class="col-sm-8">{{ $contractRenewal->duration_months ?? 'N/A' }}</dd>

                                    <dt class="col-sm-4">Requested By</dt>
                                    <dd class="col-sm-8">{{ $contractRenewal->user->fname ?? 'N/A' }}</dd>

                                    <dt class="col-sm-4">Requested On</dt>
                                    <dd class="col-sm-8">{{ \Carbon\Carbon::parse($contractRenewal->created_at)->format('d F Y') }}</dd>
                                </dl>
                            </div>
                        </div>

                        {{-- ============================= --}}
                        {{-- Section 2: Vendor & Urgency Details --}}
                        {{-- ============================= --}}
                        <div class="card mb-4 shadow-sm">
                            <div class="card-header text-white" style="background-color: #72d37f;">
                                <h5 class="mb-0">Vendor & Urgency Details</h5>
                            </div>
                            <div class="card-body">
                                <dl class="row mb-0">
                                    <dt class="col-sm-4">Contract requested status</dt>
                                    <dd class="col-sm-8">{{ $contractRenewal->status ?? 'N/A' }}</dd>

                                    <dt class="col-sm-4">Contract Vendor Option</dt>
                                    <dd class="col-sm-8">{{ $contractRenewal->vendor_option ?? 'N/A' }}</dd>

                                    <dt class="col-sm-4">Associated CCBRT Division</dt>
                                    <dd class="col-sm-8">{{ $contractRenewal->Division->name ?? 'N/A' }}</dd>

                                    <dt class="col-sm-4">Impact on Business of the Contract</dt>
                                    <dd class="col-sm-8">
                                        {{ $contractRenewal->impact_if_not_requested ?? 'Not specified' }}
                                    </dd>

                                    <dt class="col-sm-4">Overall Risk of Service/Goods not Renewed</dt>
                                    <dd class="col-sm-8">
                                        {{ $contractRenewal->overall_risk ?? 'Not specified' }}
                                    </dd>
                                </dl>
                            </div>
                        </div>

                        {{-- ============================= --}}
                        {{-- Section 3: Approval Workflow --}}
                        {{-- ============================= --}}
                        <div class="card shadow-sm mb-4">
                            <div class="card-header text-white" style="background-color: #72d37f;">
                                <h5 class="mb-0">Approval Workflow</h5>
                            </div>
                            <div class="card-body">
                                <table class="table table-bordered align-middle">
                                    <thead class="table-white text-center">
                                        <tr>
                                            <th>Forwarded By</th>
                                            <th>Status</th>
                                            <th>Approved / Rejected By</th>
                                            <th>Signature</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($contractRenewal->histories ?? [] as $history)
                                            <tr>
                                                <td>{{ $history->forwarded_by ?? 'N/A' }}</td>
                                                <td>
                                                    <span class="badge 
                                                        @if($history->status === 'approved') bg-success
                                                        @elseif($history->status === 'rejected') bg-danger
                                                        @else bg-warning text-dark
                                                        @endif">
                                                        {{ ucfirst($history->status ?? 'pending') }}
                                                    </span>
                                                </td>
                                                <td>{{ $history->approved_by ?? 'N/A' }}</td>
                                                <td>
                                                    @if(!empty($history->signature))
                                                        <img src="{{ asset('storage/signatures/' . $history->signature) }}" alt="Signature" width="80" height="40">
                                                    @else
                                                        <em>No signature</em>
                                                    @endif
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="4" class="text-center text-muted">No approval history available</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        {{-- ============================= --}}
                        {{-- Section 4: Approval Actions --}}
                        {{-- ============================= --}}
                        <div class="card shadow-sm">
                            <div class="card-header text-white" style="background-color: #72d37f;">
                                <h5 class="mb-0">Approval Actions</h5>
                            </div>
                            <div class="card-body text-center">
                                <form id="approvalForm" method="POST">
                                    @csrf
                                    <input type="hidden" name="decision" id="decisionInput">
                                    <input type="hidden" name="rejection_reason" id="rejectionReasonInput">

                                    <div class="d-flex justify-content-center gap-3">
                                        <button type="button" id="approveBtn" class="btn btn-success px-4 py-2">
                                            <i class="fas fa-check-circle me-1"></i> Approve
                                        </button>
                                        <button type="button" id="rejectBtn" class="btn btn-danger px-4 py-2">
                                            <i class="fas fa-times-circle me-1"></i> Reject
                                        </button>
                                    </div>
                                </form>
                            </div>
                            <div class="d-flex align-items-center gap-3">
                               <a href="{{ route('contract.renewal.download', $contractRenewal->id) }}"class="btn btn-light btn-sm text-success fw-bold">
                                <i class="fas fa-download"></i> Download PDF
                               </a>
                            </div>
                        </div>
                    </div> {{-- end card-body --}}
                </div> {{-- end outer card --}}
            </div>
        </div>
    </div>
</div>

{{-- ============================= --}}
{{-- SweetAlert2 Script for Rejection --}}
{{-- ============================= --}}
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
document.getElementById('approveBtn').addEventListener('click', function() {
    document.getElementById('decisionInput').value = 'approved';
    document.getElementById('approvalForm').submit();
});

document.getElementById('rejectBtn').addEventListener('click', function() {
    Swal.fire({
        title: 'Reject Contract',
        input: 'textarea',
        inputLabel: 'Provide a reason for rejection',
        inputPlaceholder: 'Type your reason here...',
        inputAttributes: { 'aria-label': 'Rejection reason' },
        showCancelButton: true,
        confirmButtonText: 'Reject',
        confirmButtonColor: '#d33',
        preConfirm: (reason) => {
            if (!reason) {
                Swal.showValidationMessage('Please enter a reason for rejection');
            }
            return reason;
        }
    }).then((result) => {
        if (result.isConfirmed) {
            document.getElementById('decisionInput').value = 'rejected';
            document.getElementById('rejectionReasonInput').value = result.value;
            document.getElementById('approvalForm').submit();
        }
    });
});
</script>
<script>
    document.getElementById('approveBtn').addEventListener('click', function() {
        const form = document.getElementById('approvalForm');
        form.action = "{{ route('contract.renewal.approve', $contractRenewal->id) }}";
        form.submit();
    });
    document.getElementById('rejectBtn').addEventListener('click', function() {
        swal.fire({
            title: 'Reject Contract',
            input: 'textarea',
            inputLabel: 'Provide a reason for rejection',
            inputPlaceholder: 'Type your reason here...',
            inputAttributes: { 'aria-label': 'Rejection reason' },
            showCancelButton: true,
            confirmButtonText: 'Reject',
            confirmButtonColor: '#d33',
            preConfirm: (reason) => {
                if (!reason) {
                    Swal.showValidationMessage('Please enter a reason for rejection');
                }
                return reason;
            }
        }).then((result) => {
            if (result.isConfirmed) {
                const form = document.getElementById('approvalForm');
                form.action = "{{ route('contract.renewal.reject', $contractRenewal->id) }}";
                document.getElementById('decisionInput').value = 'rejected';
                document.getElementById('rejectionReasonInput').value = result.value;
                form.submit();
            }
        });
    });
</script>
@endsection
