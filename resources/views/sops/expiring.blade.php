@extends('layouts.template')

@push('styles')
    <link rel="stylesheet" href="{{ asset('vendor/dataTables.dataTables.min.css') }}" />
    <style>
        .countdown-badge { font-size: 0.85rem; padding: 0.35rem 0.65rem; white-space: nowrap; }
        .sop-doc-modal-dialog { max-width: 900px; width: 100%; }
        .sop-doc-modal-body { min-height: 75vh; padding: 0; }
        .sop-doc-iframe { width: 100%; height: 75vh; border: none; }
    </style>
@endpush

@section('content')
    <div class="page-wrapper">
        <div class="content container-fluid">
            <div class="d-flex flex-wrap justify-content-between align-items-center mb-3">
                <div>
                    <h4 class="mb-1">
                        <i class="fas fa-clock text-warning me-2"></i>SOPs Expiring Soon
                    </h4>
                    <p class="text-muted small mb-0">SOPs expiring within 30 days — review and update as needed</p>
                </div>
                <a href="{{ route('sops.index') }}" class="btn btn-outline-secondary btn-sm">
                    <i class="fas fa-arrow-left me-1"></i>Back to SOPs
                </a>
            </div>

            @if ($sops->isEmpty())
                <div class="card shadow-sm border-0">
                    <div class="card-body text-center py-5">
                        <i class="fas fa-check-circle fa-4x text-success mb-3"></i>
                        <h5>No SOPs Expiring Soon</h5>
                        <p class="text-muted mb-0">All SOPs are up to date. Great job!</p>
                    </div>
                </div>
            @else
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-light py-2">
                        <h6 class="mb-0"><i class="fas fa-table me-2"></i>Expiring SOPs ({{ $sops->count() }})</h6>
                    </div>
                    <div class="card-body p-3">
                        <div class="table-responsive sop-table-wrap">
                            <table class="table table-hover table-striped align-middle mb-0" id="sopsExpiringTable" style="width:100%">
                                <thead class="table-success">
                                <tr>
                                    <th class="text-center" style="width: 2.5rem;">#</th>
                                    <th>Title</th>
                                    <th>Document Code</th>
                                    <th>Entity / Division</th>
                                    <th>Owner</th>
                                    <th>Effective</th>
                                    <th>Expiry</th>
                                    <th class="text-center">Days Left</th>
                                    <th class="text-end no-sort" style="min-width: 10rem;">Actions</th>
                                </tr>
                                </thead>
                                <tbody>
                                @foreach ($sops as $index => $sop)
                                    @php
                                        $daysLeft = $sop->daysUntilExpiry();
                                    @endphp
                                    <tr>
                                        <td class="text-center text-muted">{{ $index + 1 }}</td>
                                        <td>
                                            <strong class="text-dark d-block">{{ $sop->title }}</strong>
                                            @if ($sop->version)
                                                <small class="text-muted">v{{ $sop->version }}</small>
                                            @endif
                                        </td>
                                        <td><span class="text-secondary">{{ $sop->document_code ?? '—' }}</span></td>
                                        <td>
                                            @if ($sop->division)
                                                <span class="badge bg-info">{{ $sop->division->name }}</span>
                                            @else
                                                —
                                            @endif
                                        </td>
                                        <td>{{ $sop->ownerDepartment->dept_name ?? '—' }}</td>
                                        <td>{{ $sop->effective_date ? $sop->effective_date->format('d M Y') : '—' }}</td>
                                        <td>
                                            @if ($sop->expiry_date)
                                                <span class="{{ $daysLeft <= 14 ? 'text-danger fw-bold' : '' }}">{{ $sop->expiry_date->format('d M Y') }}</span>
                                            @else
                                                —
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            @if ($daysLeft <= 0)
                                                <span class="badge bg-danger countdown-badge">Expired</span>
                                            @elseif ($daysLeft <= 14)
                                                <span class="badge bg-danger countdown-badge">{{ $daysLeft }} day{{ $daysLeft !== 1 ? 's' : '' }}</span>
                                            @else
                                                <span class="badge bg-warning text-dark countdown-badge">{{ $daysLeft }} days</span>
                                            @endif
                                        </td>
                                        <td class="text-end">
                                            <div class="d-flex flex-wrap gap-1 justify-content-end">
                                                @if ($sop->pdf_path)
                                                    <button type="button" class="btn btn-outline-primary btn-sm view-sop-doc" title="View document"
                                                            data-doc-url="{{ asset('storage/' . $sop->pdf_path) }}"
                                                            data-doc-title="{{ $sop->title }}">
                                                        <i class="fas fa-file-pdf me-1"></i>View
                                                    </button>
                                                @endif
                                                @if ($isQA ?? false)
                                                    <a href="{{ route('sops.edit', $sop->id) }}" class="btn btn-outline-secondary btn-sm" title="Update SOP">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>

    {{-- View SOP Document Modal (same as index) --}}
    <div class="modal fade" id="viewSopDocModal" tabindex="-1" aria-labelledby="viewSopDocModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered sop-doc-modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="viewSopDocModalLabel">
                        <i class="fas fa-file-pdf me-2"></i><span id="viewSopDocTitle">Document</span>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-0 text-center sop-doc-modal-body">
                    <iframe id="viewSopDocIframe" src="" class="sop-doc-iframe" title="SOP Document"></iframe>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    @if ($sops->isNotEmpty())
        <script src="{{ asset('vendor/dataTables.min.js') }}"></script>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                if (typeof DataTable === 'undefined') {
                    console.warn('DataTables failed to load. Table will not be sortable/searchable.');
                    return;
                }
                new DataTable('#sopsExpiringTable', {
                    pageLength: 25,
                    lengthChange: true,
                    order: [[6, 'asc']],
                    columnDefs: [ { orderable: false, targets: [0, 8] } ],
                    language: {
                        search: 'Search:',
                        info: 'Showing _START_ to _END_ of _TOTAL_ SOPs',
                        infoEmpty: 'No SOPs to show',
                        infoFiltered: '(filtered from _MAX_ total)',
                        zeroRecords: 'No matching SOPs found',
                        lengthMenu: 'Show _MENU_ entries',
                        paginate: { first: 'First', last: 'Last', next: 'Next', previous: 'Previous' }
                    }
                });

                var viewDocModal = document.getElementById('viewSopDocModal');
                var viewDocIframe = document.getElementById('viewSopDocIframe');
                var viewDocTitleEl = document.getElementById('viewSopDocTitle');
                document.querySelectorAll('.view-sop-doc').forEach(function(btn) {
                    btn.addEventListener('click', function() {
                        viewDocTitleEl.textContent = this.getAttribute('data-doc-title') || 'Document';
                        viewDocIframe.src = this.getAttribute('data-doc-url') || '';
                        (new bootstrap.Modal(viewDocModal)).show();
                    });
                });
                viewDocModal.addEventListener('hidden.bs.modal', function() { viewDocIframe.src = ''; });
            });
        </script>
    @endif
@endpush
