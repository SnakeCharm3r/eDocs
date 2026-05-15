@extends('layouts.template')

@push('styles')
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" />
    <style>
        .content.container-fluid { padding-top: 1rem; }
        .no-sort { cursor: default !important; }
        .no-sort::after { display: none !important; }
        .section-card { border:0; border-radius:10px; box-shadow:0 1px 6px rgba(0,0,0,.07); margin-bottom:.75rem; }
        .section-card .card-header { background:#f8fdf9; border-bottom:1px solid #d1e7dd; border-radius:10px 10px 0 0!important; padding:.55rem 1rem; }
        .section-card .card-header h6 { margin:0; font-size:.82rem; font-weight:600; color:#198754; }
        #sopsTable { width: 100% !important; }
        #sopsTable thead th { font-weight: 600; font-size:.82rem; color: #495057; white-space: nowrap; }
        #sopsTable tbody td { vertical-align: middle; font-size:.82rem; }
        #sopsTable tbody tr { cursor: pointer; }
        #sopsTable tbody tr td:last-child { cursor: default; }
        .sop-actions-cell { display: flex; flex-wrap: nowrap; gap: 0.25rem; justify-content: flex-end; align-items: center; }
        .sop-actions-cell .btn { flex-shrink: 0; }
        .sop-actions-cell .btn .fas { color: inherit; }
        table.dataTable { border-collapse: collapse !important; }
        .sop-doc-modal-dialog { max-width: 900px; width: 100%; }
        .sop-doc-modal-body { min-height: 75vh; padding: 0; display: flex; align-items: flex-start; justify-content: center; }
        .sop-doc-iframe { width: 100%; height: 75vh; border: none; max-width: 100%; }
    </style>
@endpush

@section('content')
    @php
        $hasDeptFilter = !$isRequesterOnly && isset($departmentCards) && $departmentCards->isNotEmpty();
    @endphp
    <div class="page-wrapper">
        <div class="content container-fluid">
            <div class="card section-card">
                <div class="card-header">
                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                        <h6><i class="fas fa-book me-2"></i>Standard Operating Procedures (SOPs)</h6>
                        <div class="d-flex gap-2 align-items-center flex-wrap">
                            @if ($hasDeptFilter)
                                <form method="GET" action="{{ route('sops.index') }}" class="d-inline-flex align-items-center gap-2">
                                    <label class="form-label mb-0 small text-muted">Department</label>
                                    <select name="owner_department_id" class="form-select form-select-sm" style="width: auto; min-width: 180px;" onchange="this.form.submit()">
                                        <option value="">All departments</option>
                                        @foreach ($departmentCards as $card)
                                            <option value="{{ $card->department_id }}" {{ request('owner_department_id') == $card->department_id ? 'selected' : '' }}>
                                                {{ $card->department_name }} ({{ $card->sop_count }})
                                            </option>
                                        @endforeach
                                    </select>
                                </form>
                            @endif
                            @if ($isQA)
                                <a href="{{ route('sops.create') }}" class="btn btn-success btn-sm">
                                    <i class="fas fa-plus me-1"></i>Add SOP
                                </a>
                            @endif
                            @if ($canSeeAll || $isLineManager)
                                <a href="{{ route('sops.expiring') }}" class="btn btn-outline-warning btn-sm">
                                    <i class="fas fa-clock me-1"></i>Expiring Soon
                                    @if ($expiringSoonCount > 0)
                                        <span class="badge bg-danger ms-1">{{ $expiringSoonCount }}</span>
                                    @endif
                                </a>
                            @endif
                    </div>
                </div>
                <div class="card-body py-2 px-3">
                <div class="table-responsive">
                    <table class="table table-hover table-striped align-middle" id="sopsTable" style="width:100%">
                    <thead class="table-light">
                        <tr>
                            <th class="text-center" style="width: 2.5rem;">#</th>
                            <th>Title</th>
                            <th>Entity / Department</th>
                            <th>Effective</th>
                            <th>Next Review</th>
                            <th class="text-end no-sort">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($sops as $index => $sop)
                        <tr class="sop-row"
                            data-has-pdf="{{ $sop->pdf_path ? '1' : '0' }}"
                            data-pdf-url="{{ $sop->pdf_path ? asset('storage/' . $sop->pdf_path) : '' }}"
                            data-pdf-title="{{ $sop->title }}">
                            <td class="text-center text-muted">{{ $index + 1 }}</td>
                            <td>
                                {{ Str::limit($sop->title, 60) }}
                                @if ($sop->version || $sop->document_code)
                                    <small class="text-muted d-block">
                                        @if ($sop->version)v{{ $sop->version }}@endif
                                        @if ($sop->version && $sop->document_code) &middot; @endif
                                        @if ($sop->document_code){{ $sop->document_code }}@endif
                                    </small>
                                @endif
                            </td>
                            <td>
                                @if ($sop->division)
                                    <span class="fw-semibold">{{ $sop->division->name }}</span>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                                @if ($sop->ownerDepartment)
                                    <small class="text-muted d-block">{{ $sop->ownerDepartment->dept_name }}</small>
                                @endif
                            </td>
                            <td data-order="{{ $sop->effective_date ? $sop->effective_date->format('Y-m-d') : '' }}">{{ $sop->effective_date ? $sop->effective_date->format('d M Y') : '—' }}</td>
                            <td data-order="{{ $sop->current_review_date ? $sop->current_review_date->format('Y-m-d') : '' }}" title="Next Review Date">
                                @if ($sop->current_review_date)
                                    {{ $sop->current_review_date->format('d M Y') }}
                                    @if ($sop->isExpired())
                                        <span class="badge bg-danger ms-1">Expired</span>
                                    @elseif ($sop->isExpiringSoon())
                                        <span class="badge {{ $sop->getExpiryBadgeClass() }}">{{ $sop->getExpiryStatusLabel() }}</span>
                                    @endif
                                @else
                                    —
                                @endif
                            </td>
                            <td class="text-end" onclick="event.stopPropagation()">
                                <div class="sop-actions-cell">
                                    <a href="{{ route('sops.show', $sop->id) }}" class="btn btn-sm btn-outline-primary" title="View"><i class="fas fa-eye"></i></a>
                                    @if ($isQA)
                                        <a href="{{ route('sops.edit', $sop->id) }}" class="btn btn-sm btn-outline-warning" title="Edit"><i class="fas fa-edit"></i></a>
                                        @if ($sop->isActive())
                                            <button type="button" class="btn btn-sm btn-outline-secondary" title="Archive" data-bs-toggle="modal" data-bs-target="#archiveSopModal" data-sop-id="{{ $sop->id }}" data-sop-title="{{ $sop->title }}"><i class="fas fa-archive"></i></button>
                                        @elseif ($sop->isArchived())
                                            <button type="button" class="btn btn-sm btn-outline-info" title="Restore" data-bs-toggle="modal" data-bs-target="#restoreSopModal" data-sop-id="{{ $sop->id }}" data-sop-title="{{ $sop->title }}"><i class="fas fa-undo"></i></button>
                                        @endif
                                        <button type="button" class="btn btn-sm btn-outline-danger" title="Delete" data-bs-toggle="modal" data-bs-target="#deleteSopModal" data-sop-id="{{ $sop->id }}" data-sop-title="{{ $sop->title }}"><i class="fas fa-trash-alt"></i></button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4">No SOPs found.</td>
                        </tr>
                        @endforelse
                    </tbody>
                    </table>
                </div>
                </div>
            </div>
        </div>
    </div>

    {{-- View SOP Document Modal: document width (readable), not full screen --}}
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

    {{-- Archive SOP Modal --}}
    <div class="modal fade" id="archiveSopModal" tabindex="-1" aria-labelledby="archiveSopModalLabel"
        aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="archiveSopModalLabel">
                        <i class="fas fa-archive me-2"></i>Archive SOP
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to archive this SOP?</p>
                    <p class="mb-0">
                        <strong>SOP:</strong>
                        <span id="archiveSopTitle" class="text-primary"></span>
                    </p>
                    <small class="text-muted">Archived SOPs will not be visible to regular users but can be restored
                        later.</small>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <form id="archiveSopForm" method="POST" class="d-inline">
                        @csrf
                        <button type="submit" class="btn btn-warning">
                            <i class="fas fa-archive me-1"></i>Archive
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    {{-- Restore SOP Modal --}}
    <div class="modal fade" id="restoreSopModal" tabindex="-1" aria-labelledby="restoreSopModalLabel"
        aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="restoreSopModalLabel">
                        <i class="fas fa-undo me-2"></i>Restore SOP
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to restore this archived SOP?</p>
                    <p class="mb-0">
                        <strong>SOP:</strong>
                        <span id="restoreSopTitle" class="text-primary"></span>
                    </p>
                    <small class="text-muted">The SOP will become active and visible to all authorized users.</small>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <form id="restoreSopForm" method="POST" class="d-inline">
                        @csrf
                        <button type="submit" class="btn btn-info">
                            <i class="fas fa-undo me-1"></i>Restore
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    {{-- Delete SOP Modal --}}
    <div class="modal fade" id="deleteSopModal" tabindex="-1" aria-labelledby="deleteSopModalLabel"
        aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteSopModalLabel">
                        <i class="fas fa-exclamation-triangle me-2"></i>Delete SOP
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p><strong>Warning: This action cannot be undone!</strong></p>
                    <p>Are you sure you want to permanently delete this SOP?</p>
                    <p class="mb-0">
                        <strong>SOP:</strong>
                        <span id="deleteSopTitle"></span>
                    </p>
                    <small class="text-muted">This will permanently remove the SOP and its associated document from the
                        system.</small>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <form id="deleteSopForm" method="POST" class="d-inline">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-outline-secondary">
                            <i class="fas fa-trash-alt me-1"></i>Delete Permanently
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <script>
        $(document).ready(function() {
            // DataTable for SOPs list (search, sort, pagination)
            var $sopsTable = $('#sopsTable');
            var hasRows = $sopsTable.find('tbody tr').length > 0 && $sopsTable.find('tbody tr td[colspan]').length === 0;
            if (hasRows) {
                $sopsTable.DataTable({
                    pageLength: 25,
                    order: [[0, 'asc']],
                    columnDefs: [
                        { orderable: false, targets: [5] },
                        { targets: 0, render: function(data, type, row, meta) { return meta.row + meta.settings._iDisplayStart + 1; } }
                    ],
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
            }

            // Archive Modal
            const archiveModal = document.getElementById('archiveSopModal');
            if (archiveModal) {
                archiveModal.addEventListener('show.bs.modal', function(event) {
                    const button = event.relatedTarget;
                    const sopId = button.getAttribute('data-sop-id');
                    const sopTitle = button.getAttribute('data-sop-title');
                    const modalTitle = archiveModal.querySelector('#archiveSopTitle');
                    const form = archiveModal.querySelector('#archiveSopForm');

                    modalTitle.textContent = sopTitle;
                    form.action = `/sops/${sopId}/archive`;
                });
            }

            // Restore Modal
            const restoreModal = document.getElementById('restoreSopModal');
            if (restoreModal) {
                restoreModal.addEventListener('show.bs.modal', function(event) {
                    const button = event.relatedTarget;
                    const sopId = button.getAttribute('data-sop-id');
                    const sopTitle = button.getAttribute('data-sop-title');
                    const modalTitle = restoreModal.querySelector('#restoreSopTitle');
                    const form = restoreModal.querySelector('#restoreSopForm');

                    modalTitle.textContent = sopTitle;
                    form.action = `/sops/${sopId}/restore`;
                });
            }

            // View SOP Document Modal (same page, no new tab)
            const viewDocModal = document.getElementById('viewSopDocModal');
            const viewDocIframe = document.getElementById('viewSopDocIframe');
            const viewDocTitleEl = document.getElementById('viewSopDocTitle');
            if (viewDocModal && viewDocIframe) {
                // Clickable rows
                document.querySelectorAll('.sop-row').forEach(function(row) {
                    row.addEventListener('click', function() {
                        var hasPdf = this.getAttribute('data-has-pdf');
                        var url = this.getAttribute('data-pdf-url');
                        var title = this.getAttribute('data-pdf-title') || 'Document';
                        if (hasPdf === '1' && url) {
                            viewDocTitleEl.textContent = title;
                            viewDocIframe.src = url;
                            new bootstrap.Modal(viewDocModal).show();
                        }
                    });
                });
                viewDocModal.addEventListener('hidden.bs.modal', function() {
                    viewDocIframe.src = '';
                });
            }

            // Delete Modal
            const deleteModal = document.getElementById('deleteSopModal');
            if (deleteModal) {
                deleteModal.addEventListener('show.bs.modal', function(event) {
                    const button = event.relatedTarget;
                    const sopId = button.getAttribute('data-sop-id');
                    const sopTitle = button.getAttribute('data-sop-title');
                    const modalTitle = deleteModal.querySelector('#deleteSopTitle');
                    const form = deleteModal.querySelector('#deleteSopForm');

                    modalTitle.textContent = sopTitle;
                    form.action = `/sops/${sopId}`;
                });
            }
        });
    </script>
@endpush
