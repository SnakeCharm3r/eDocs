@extends('layouts.template')

@push('styles')
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" />
    <style>
        .content.container-fluid { padding-top: 1rem; }
        .section-card { border:0; border-radius:10px; box-shadow:0 1px 6px rgba(0,0,0,.07); margin-bottom:.75rem; }
        .section-card .card-header { background:#f8fdf9; border-bottom:1px solid #d1e7dd; border-radius:10px 10px 0 0!important; padding:.55rem 1rem; }
        .section-card .card-header h6 { margin:0; font-size:.82rem; font-weight:600; color:#198754; }
        #certificatesTable { width: 100% !important; }
        #certificatesTable thead th { font-weight:600; font-size:.82rem; color:#495057; white-space:nowrap; }
        #certificatesTable tbody td { vertical-align:middle; font-size:.82rem; }
        table.dataTable { border-collapse: collapse !important; }
        .certificate-number-pill { display:inline-flex; align-items:center; padding:2px 8px; border-radius:999px; background:#e9f7eb; color:#166534; font-weight:600; font-size:.74rem; }
        .certificate-staff-meta { display:block; margin-top:.1rem; color:#6c757d; font-size:.74rem; }
        .certificate-actions-cell { display:flex; flex-wrap:nowrap; gap:.25rem; justify-content:flex-end; align-items:center; }
        .certificate-actions-cell .btn { flex-shrink:0; }
        .certificate-actions-cell .btn .fas { color: inherit; }
    </style>
@endpush

@section('content')
<div class="page-wrapper">
    <div class="content container-fluid">
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        {{-- ── Pending COS Requests (HR-initialized, no certificate yet) ── --}}
        @if($pendingCosRequests->count() > 0)
        <div class="card section-card mb-3" style="border-left:4px solid #ffc107!important;">
            <div class="card-header" style="background:#fffdf0;border-bottom:1px solid #ffe699;">
                <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                    <h6 style="color:#856404;margin:0;">
                        <i class="fas fa-clock me-2"></i>Pending Certificate Requests
                        <span class="badge ms-1" style="background:#ffc107;color:#333;font-size:.72rem;">{{ $pendingCosRequests->count() }}</span>
                    </h6>
                    <span class="text-muted small">Initialized by HR — awaiting certificate creation</span>
                </div>
            </div>
            <div class="card-body py-2 px-3">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0" style="font-size:.83rem;">
                        <thead class="table-light">
                            <tr>
                                <th style="width:2rem;">#</th>
                                <th>Staff Member</th>
                                <th>Department</th>
                                <th>Last Working Day</th>
                                <th>Notified By HR</th>
                                <th class="text-end">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($pendingCosRequests as $req)
                            @php
                                $reqStaff = $req->user;
                                $reqName  = trim(($reqStaff->fname ?? '') . ' ' . ($reqStaff->lname ?? ''));
                            @endphp
                            <tr>
                                <td class="text-muted">{{ $loop->iteration }}</td>
                                <td>
                                    <strong>{{ $reqName }}</strong>
                                    @if($reqStaff?->ccbrt_code)
                                        <span class="d-block text-muted" style="font-size:.74rem;">{{ $reqStaff->ccbrt_code }}</span>
                                    @endif
                                    @if($reqStaff?->email)
                                        <span class="d-block text-muted" style="font-size:.74rem;">{{ $reqStaff->email }}</span>
                                    @endif
                                </td>
                                <td>{{ $reqStaff?->department?->dept_name ?? '—' }}</td>
                                <td>
                                    {{ $req->last_working_day ? \Carbon\Carbon::parse($req->last_working_day)->format('d M Y') : '—' }}
                                </td>
                                <td>
                                    {{ \Carbon\Carbon::parse($req->cos_notified_at)->format('d M Y, H:i') }}
                                </td>
                                <td class="text-end">
                                    <a href="{{ route('certificate-of-service.create', ['user_id' => $reqStaff?->id]) }}"
                                       class="btn btn-sm btn-warning fw-semibold" style="white-space:nowrap;">
                                        <i class="fas fa-certificate me-1"></i>Create Certificate
                                    </a>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        @endif

        <div class="card section-card">
            <div class="card-header">
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                    <h6><i class="fas fa-file-signature me-2"></i>Certificates of Service</h6>
                    <div class="d-flex gap-2 align-items-center flex-wrap">
                        <span class="text-muted small">{{ $certificates->count() }} record{{ $certificates->count() === 1 ? '' : 's' }}</span>
                        <a href="{{ route('certificate-of-service.template') }}" class="btn btn-outline-success btn-sm">
                            <i class="fas fa-pen-ruler me-1"></i>Manage Template
                        </a>
                        <a href="{{ route('certificate-of-service.create') }}" class="btn btn-success btn-sm">
                            <i class="fas fa-plus me-1"></i>Issue Certificate
                        </a>
                    </div>
                </div>
            </div>
            <div class="card-body py-2 px-3">
                <div class="table-responsive">
                    <table class="table table-hover table-striped align-middle" id="certificatesTable" style="width:100%">
                        <thead class="table-light">
                            <tr>
                                <th class="text-center" style="width:2.5rem;">#</th>
                                <th>Certificate No.</th>
                                <th>Staff Name</th>
                                <th>Position</th>
                                <th>Department</th>
                                <th>Issue Date</th>
                                <th>Created By</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($certificates as $cert)
                            <tr>
                                <td class="text-center text-muted">{{ $loop->iteration }}</td>
                                <td>
                                    <a href="{{ route('certificate-of-service.show', $cert) }}" class="text-decoration-none">
                                        <span class="certificate-number-pill">{{ $cert->certificate_number }}</span>
                                    </a>
                                </td>
                                <td>
                                    <a href="{{ route('certificate-of-service.show', $cert) }}" class="text-decoration-none fw-semibold text-dark">
                                        {{ $cert->staff?->fname }} {{ $cert->staff?->lname }}
                                    </a>
                                    @if($cert->staff?->ccbrt_code)
                                        <span class="certificate-staff-meta">{{ $cert->staff->ccbrt_code }}</span>
                                    @endif
                                </td>
                                <td>{{ $cert->position_held }}</td>
                                <td>{{ $cert->department }}</td>
                                <td data-order="{{ $cert->issue_date?->timestamp ?? '' }}">{{ $cert->issue_date?->format('d M Y') }}</td>
                                <td>{{ $cert->creator?->fname }} {{ $cert->creator?->lname }}</td>
                                <td class="text-end">
                                    <div class="certificate-actions-cell">
                                        <a href="{{ route('certificate-of-service.show', $cert) }}" class="btn btn-sm btn-outline-primary" title="View">
                                            <i class="fas fa-eye fa-xs"></i>
                                        </a>
                                        <a href="{{ route('certificate-of-service.download', $cert) }}" class="btn btn-sm btn-outline-success" title="Download PDF">
                                            <i class="fas fa-file-pdf fa-xs"></i>
                                        </a>
                                        <form method="POST" action="{{ route('certificate-of-service.destroy', $cert) }}" onsubmit="return confirm('Delete this certificate?')">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete">
                                                <i class="fas fa-trash fa-xs"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="8" class="text-center text-muted py-4">No certificates issued yet.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
<script>
$(document).ready(function(){
    @if($certificates->count())
    $('#certificatesTable').DataTable({
        pageLength: 25,
        language: { search: '', searchPlaceholder: 'Search...', emptyTable: 'No records found' },
        columnDefs: [
            { orderable:false, searchable:false, targets:0 },
            { orderable:false, targets:7 }
        ],
        drawCallback: function(){
            var api = this.api(), start = api.page.info().start;
            api.column(0,{page:'current'}).nodes().each(function(c,i){ c.innerHTML = start+i+1; });
        }
    });
    @endif
});
</script>
@endpush
