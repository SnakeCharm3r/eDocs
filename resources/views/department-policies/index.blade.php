@extends('layouts.template')

@push('styles')
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" />
    <style>
        #departmentPoliciesTable thead th { white-space: nowrap; }
        #departmentPoliciesTable .btn-group .form { display: inline; }
        #departmentPoliciesTable tbody tr { cursor: pointer; }
        #departmentPoliciesTable tbody tr td:last-child { cursor: default; }
        /* Single dropdown arrow for "Show X per page" - remove duplicate from native + Bootstrap + DataTables */
        .dataTables_wrapper .dataTables_length select,
        .dataTables_wrapper .dataTables_length select.form-select,
        #departmentPoliciesTable_wrapper .dataTables_length select {
            -webkit-appearance: none !important;
            -moz-appearance: none !important;
            appearance: none !important;
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16'%3e%3cpath fill='none' stroke='%23343a40' stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M2 5l6 6 6-6'/%3e%3c/svg%3e") !important;
            background-repeat: no-repeat !important;
            background-position: right 0.5rem center !important;
            background-size: 16px 12px !important;
            padding-right: 2rem !important;
        }
        #departmentPoliciesTable_wrapper .dataTables_length .form-select {
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16'%3e%3cpath fill='none' stroke='%23343a40' stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M2 5l6 6 6-6'/%3e%3c/svg%3e") !important;
        }
        /* PDF modal: document width for readable viewing */
        .policy-doc-modal-dialog { max-width: 900px; width: 100%; }
        .policy-doc-modal-body { min-height: 75vh; padding: 0; }
        .policy-doc-iframe { width: 100%; height: 75vh; border: none; }
    </style>
@endpush

@section('content')
    <div class="page-wrapper">
        <div class="content container-fluid">
            <div class="card shadow-sm mb-4 border-0">
                <div class="card-header bg-white border-bottom">
                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
                        <div>
                            <h4 class="mb-0 text-dark">
                                <i class="fas fa-folder-open me-2 text-success"></i>Department Policies
                            </h4>
                        </div>
                        @if ($canManage)
                        <div>
                            <a href="{{ route('department-policies.create') }}" class="btn btn-success btn-sm">
                                <i class="fas fa-plus me-1"></i>Add Department Policy
                            </a>
                        </div>
                        @endif
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover table-striped align-middle" id="departmentPoliciesTable">
                            <thead class="table-light">
                                <tr>
                                    <th style="width: 50px;">#</th>
                                    <th>Title</th>
                                    <th>Department</th>
                                    <th>Visibility</th>
                                    <th>Created</th>
                                    <th class="text-center" style="width: 150px;">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($policies as $policy)
                                    <tr class="policy-row"
                                        data-show-url="{{ route('department-policies.show', $policy->id) }}"
                                        data-has-pdf="{{ $policy->pdf_path ? '1' : '0' }}"
                                        data-pdf-url="{{ $policy->pdf_path ? asset('storage/' . $policy->pdf_path) : '' }}"
                                        data-pdf-title="{{ $policy->title }}">
                                        <td class="text-muted">{{ $loop->iteration }}</td>
                                        <td>
                                            {{ Str::limit($policy->title, 60) }}
                                            @if ($policy->document_code)
                                                <br><small class="text-muted">{{ $policy->document_code }}</small>
                                            @endif
                                        </td>
                                        <td>
                                            <span class="badge bg-info">{{ $policy->department->dept_name ?? 'N/A' }}</span>
                                        </td>
                                        <td>
                                            @if ($policy->visible_to_all_staff)
                                                <span class="badge bg-success">All staff</span>
                                            @else
                                                <span class="badge bg-secondary">Department only</span>
                                            @endif
                                        </td>
                                        <td data-order="{{ $policy->created_at->format('Y-m-d H:i:s') }}">
                                            {{ $policy->created_at->format('d M Y') }}
                                            <br><small class="text-muted">{{ $policy->created_at->diffForHumans() }}</small>
                                        </td>
                                        <td class="text-end" onclick="event.stopPropagation()">
                                            <div class="d-flex gap-1 justify-content-end flex-wrap">
                                                @if ($canManage && $departments->contains('id', $policy->department_id))
                                                    <a href="{{ route('department-policies.edit', $policy->id) }}"
                                                        class="btn btn-sm btn-outline-warning" title="Edit">
                                                        <i class="fas fa-edit text-success"></i>
                                                    </a>
                                                    <form action="{{ route('department-policies.destroy', $policy->id) }}"
                                                        method="POST" class="d-inline" onsubmit="return confirm('Delete this policy?');">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete">
                                                            <i class="fas fa-trash-alt text-success"></i>
                                                        </button>
                                                    </form>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center py-5 text-muted">
                                            <i class="fas fa-folder-open fa-3x mb-3 d-block"></i>
                                            No department policies found.
                                            @if ($canManage)
                                                <br><a href="{{ route('department-policies.create') }}">Add the first policy</a>
                                            @endif
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- View PDF Modal (same style as SOPs) --}}
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
@endsection

@push('scripts')
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <script>
        $(document).ready(function() {
            var $table = $('#departmentPoliciesTable');
            var hasData = $table.find('tbody tr').length > 0 && $table.find('tbody tr td[colspan]').length === 0;
            if (hasData) {
                $table.DataTable({
                    pageLength: 25,
                    order: [[4, 'desc']],
                    columnDefs: [
                        { orderable: false, targets: [0, 5] }
                    ],

                    language: {
                        search: 'Search:',
                        lengthMenu: 'Show _MENU_ policies per page',
                        info: 'Showing _START_ to _END_ of _TOTAL_ policies',
                        infoEmpty: 'No policies to show',
                        infoFiltered: '(filtered from _MAX_ total)',
                        zeroRecords: 'No matching policies found',
                        paginate: {
                            first: 'First',
                            last: 'Last',
                            next: 'Next',
                            previous: 'Previous'
                        }
                    }
                });
            }

            // Clickable rows
            var viewModal = document.getElementById('viewPolicyDocModal');
            var viewIframe = document.getElementById('viewPolicyDocIframe');
            var viewTitleEl = document.getElementById('viewPolicyDocTitle');
            $(document).on('click', '.policy-row', function() {
                var hasPdf = $(this).data('has-pdf');
                var pdfUrl = $(this).data('pdf-url');
                var pdfTitle = $(this).data('pdf-title');
                var showUrl = $(this).data('show-url');
                if (hasPdf == '1' && viewModal) {
                    viewTitleEl.textContent = pdfTitle;
                    viewIframe.src = pdfUrl;
                    new bootstrap.Modal(viewModal).show();
                } else if (showUrl) {
                    window.location.href = showUrl;
                }
            });
            if (viewModal) {
                viewModal.addEventListener('hidden.bs.modal', function() { viewIframe.src = ''; });
            }
        });
    </script>
@endpush
