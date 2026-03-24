@extends('layouts.template')

@push('styles')
    <style>
        .policy-doc-modal-dialog { max-width: 900px; width: 100%; }
        .policy-doc-modal-body { min-height: 75vh; padding: 0; }
        .policy-doc-iframe { width: 100%; height: 75vh; border: none; }
    </style>
@endpush

@section('content')
    <div class="page-wrapper">
        <div class="content container-fluid">
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white">
                    <div class="d-flex flex-wrap justify-content-between align-items-center">
                        <h4 class="mb-0">{{ $policy->title }}</h4>
                        <a href="{{ route('department-policies.index') }}" class="btn btn-outline-secondary btn-sm">
                            <i class="fas fa-arrow-left me-1"></i>Back to list
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-8">
                            @if ($policy->document_code)
                                <p><strong>Document code:</strong> {{ $policy->document_code }}</p>
                            @endif
                            @if ($policy->description)
                                <p><strong>Description:</strong><br>{{ $policy->description }}</p>
                            @endif
                            <p><strong>Department:</strong> {{ $policy->department->dept_name ?? 'N/A' }}</p>
                            <p><strong>Visibility:</strong>
                                @if ($policy->visible_to_all_staff)
                                    <span class="badge bg-success">All staff</span>
                                @else
                                    <span class="badge bg-secondary">Department only</span>
                                @endif
                            </p>
                            <p><strong>Uploaded by:</strong> {{ $policy->creator ? $policy->creator->display_name : 'Unknown' }} on {{ $policy->created_at->format('d F Y, H:i') }}</p>
                            <p><strong>Views:</strong> {{ number_format($policy->view_count ?? 0) }}</p>
                            @if ($policy->pdf_path)
                                <div class="d-flex gap-2">
                                    <button type="button" class="btn btn-primary btn-view-policy-pdf" data-doc-url="{{ asset('storage/' . $policy->pdf_path) }}" data-doc-title="{{ $policy->title }}">
                                        <i class="fas fa-file-pdf me-1"></i>View PDF
                                    </button>
                                    <a href="{{ asset('storage/' . $policy->pdf_path) }}" target="_blank" class="btn btn-outline-secondary">
                                        <i class="fas fa-download me-1"></i>Download
                                    </a>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @if ($policy->pdf_path)
    <div class="modal fade" id="viewPolicyDocModal" tabindex="-1" aria-labelledby="viewPolicyDocModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered policy-doc-modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="viewPolicyDocModalLabel">
                        <i class="fas fa-file-pdf me-2"></i><span id="viewPolicyDocTitle">Document</span>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-0 text-center policy-doc-modal-body">
                    <iframe id="viewPolicyDocIframe" src="" class="policy-doc-iframe" title="Policy PDF"></iframe>
                </div>
            </div>
        </div>
    </div>
    @endif
@endsection

@push('scripts')
    @if ($policy->pdf_path ?? false)
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var btn = document.querySelector('.btn-view-policy-pdf');
            var modal = document.getElementById('viewPolicyDocModal');
            var iframe = document.getElementById('viewPolicyDocIframe');
            var titleEl = document.getElementById('viewPolicyDocTitle');
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
    @endif
@endpush
