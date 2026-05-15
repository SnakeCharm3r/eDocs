@extends('layouts.template')

@push('styles')
    <style>
        .sop-doc-modal-dialog { max-width: 900px; width: 100%; }
        .sop-doc-modal-body { min-height: 75vh; padding: 0; display: flex; align-items: flex-start; justify-content: center; }
        .sop-doc-iframe { width: 100%; height: 75vh; border: none; max-width: 100%; }
    </style>
@endpush

@section('content')
    <div class="page-wrapper">
        <div class="content container-fluid">
            <div class="d-flex flex-wrap justify-content-between align-items-center mb-3">
                <div>
                    <h4 class="mb-1">
                        <i class="fas fa-book text-primary me-2"></i>Standard Operating Procedure
                    </h4>
                    <p class="text-muted small mb-0">View SOP details and document</p>
                </div>
                <a href="{{ route('sops.index') }}" class="btn btn-outline-secondary btn-sm">
                    <i class="fas fa-arrow-left me-1"></i>Back to SOPs
                </a>
            </div>

            <div class="card shadow-sm border-0">
                <div class="card-header bg-light py-2">
                    <h6 class="mb-0"><i class="fas fa-file-alt me-2"></i>{{ $sop->title }}</h6>
                </div>
                <div class="card-body">
                    <div class="row g-3 mb-3">
                        @if ($sop->document_code)
                            <div class="col-md-6 col-lg-3">
                                <small class="text-muted d-block mb-1">Document Code</small>
                                <span class="fw-medium">{{ $sop->document_code }}</span>
                            </div>
                        @endif
                        @if ($sop->version)
                            <div class="col-md-6 col-lg-3">
                                <small class="text-muted d-block mb-1">Version</small>
                                <span class="badge bg-secondary">v{{ $sop->version }}</span>
                            </div>
                        @endif
                        <div class="col-md-6 col-lg-3">
                            <small class="text-muted d-block mb-1">Status</small>
                            @if ($sop->isExpired())
                                <span class="badge bg-danger">Expired</span>
                            @elseif ($sop->isExpiringSoon())
                                <span class="badge {{ $sop->getExpiryBadgeClass() }}">{{ $sop->getExpiryStatusLabel() }}</span>
                            @elseif ($sop->isActive())
                                <span class="badge bg-success">Active</span>
                            @else
                                <span class="badge bg-secondary">{{ ucfirst($sop->status ?? 'N/A') }}</span>
                            @endif
                        </div>
                        <div class="col-md-6 col-lg-3">
                            <small class="text-muted d-block mb-1">Views</small>
                            <span>{{ $sop->view_count ?? 0 }}</span>
                        </div>
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <small class="text-muted d-block mb-1">Entity / Division</small>
                            <span>{{ $sop->division->name ?? '—' }}</span>
                        </div>
                        <div class="col-md-6">
                            <small class="text-muted d-block mb-1">Owner Department</small>
                            <span>{{ $sop->ownerDepartment->dept_name ?? '—' }}</span>
                        </div>
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <small class="text-muted d-block mb-1">Effective Date</small>
                            <span>{{ $sop->effective_date ? $sop->effective_date->format('d M Y') : '—' }}</span>
                        </div>
                        <div class="col-md-6">
                            <small class="text-muted d-block mb-1">Next Review Date</small>
                            <span>{{ $sop->current_review_date ? $sop->current_review_date->format('d M Y') : '—' }}</span>
                        </div>
                    </div>
                    @if ($sop->description)
                        <div class="mb-3">
                            <small class="text-muted d-block mb-1">Description</small>
                            <p class="mb-0">{{ $sop->description }}</p>
                        </div>
                    @endif
                    @if ($sop->pdf_path)
                        <div class="pt-2 border-top">
                            <button type="button" class="btn btn-primary btn-view-sop-doc" data-doc-url="{{ asset('storage/' . $sop->pdf_path) }}" data-doc-title="{{ $sop->title }}">
                                <i class="fas fa-file-pdf me-1"></i>View Document
                            </button>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- View SOP Document Modal (same as index: document width) --}}
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
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var btn = document.querySelector('.btn-view-sop-doc');
            var modal = document.getElementById('viewSopDocModal');
            var iframe = document.getElementById('viewSopDocIframe');
            var titleEl = document.getElementById('viewSopDocTitle');
            if (btn && modal && iframe) {
                btn.addEventListener('click', function() {
                    titleEl.textContent = btn.getAttribute('data-doc-title') || 'Document';
                    iframe.src = btn.getAttribute('data-doc-url') || '';
                    (new bootstrap.Modal(modal)).show();
                });
                modal.addEventListener('hidden.bs.modal', function() { iframe.src = ''; });
            }
        });
    </script>
@endpush
