@extends('layouts.template')

@section('breadcrumb')
    @include('sweetalert::alert')
    @include('includes.loader')
@endsection

@section('content')
    <style>
        :root {
            --theme-color: #007A33;
        }

        #errorsTable thead th {
            background-color: var(--theme-color) !important;
            color: white !important;
            font-weight: 600;
            border: none;
            padding: 12px 10px;
        }

        #errorsTable thead th.sorting:before,
        #errorsTable thead th.sorting:after,
        #errorsTable thead th.sorting_asc:before,
        #errorsTable thead th.sorting_asc:after,
        #errorsTable thead th.sorting_desc:before,
        #errorsTable thead th.sorting_desc:after {
            color: white !important;
            opacity: 1 !important;
        }

        .workflow-flow {
            max-height: 300px;
            overflow-y: auto;
        }

        .bg-light-success {
            background-color: #d1e7dd !important;
        }

        .bg-light-danger {
            background-color: #f8d7da !important;
        }

        .bg-light-warning {
            background-color: #fff3cd !important;
        }
    </style>
    <div class="page-wrapper">
        <div class="content container-fluid">
            <br>
            <div class="page-header">
                <div class="row">
                    <div class="col-sm-12">
                        <div class="page-sub-header d-flex justify-content-between align-items-center">
                            <h3 class="page-title mb-0"><i class="fas fa-exclamation-triangle me-2 text-danger"></i>Workflows
                                with Errors</h3>
                            <a href="{{ route('workflow-management.index') }}" class="btn btn-secondary btn-sm">
                                <i class="fas fa-arrow-left me-1"></i> Back to All Workflows
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <div class="alert alert-danger">
                <i class="fas fa-exclamation-triangle me-2"></i>
                <strong>Warning:</strong> These workflows have errors such as assigned users that don't exist or are
                inactive. Please reassign them to active users to resolve the issues.
            </div>

            <!-- All Workflows with Errors -->
            <div class="card shadow-sm">
                <div class="card-header bg-white border-bottom">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0"><i class="fas fa-bug me-2"></i>All Workflows with Errors</h5>
                        <span class="badge bg-danger badge-status">{{ $allErrorWorkflows->count() }} Error(s)</span>
                    </div>
                </div>
                <div class="card-body">
                    <form id="bulkDeleteForm" action="{{ route('workflow-management.bulk-destroy') }}" method="POST">
                        @csrf
                        @method('DELETE')
                        <div class="d-flex mb-3 gap-2 align-items-center">
                            <button type="submit" id="bulkDeleteBtn" class="btn btn-danger btn-sm" disabled
                                onclick="return confirm('Delete selected workflows and their history? This cannot be undone.')">
                                <i class="fas fa-trash me-1"></i>Delete selected
                            </button>
                            <small class="text-muted">Clearance workflows cannot be bulk deleted.</small>
                        </div>
                        <div class="table-responsive">
                            <table id="errorsTable" class="display nowrap" style="width:100%">
                                <thead>
                                    <tr>
                                        <th data-orderable="false"><input type="checkbox" id="selectAll"></th>
                                        <th data-type="num">ID</th>
                                        <th>User</th>
                                        <th>Form Type</th>
                                        <th>Error</th>
                                        <th>Created</th>
                                        <th data-orderable="false" data-priority="2">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($allErrorWorkflows as $workflow)
                                        @php
                                            $isClearance =
                                                isset($workflow->workflow_type) &&
                                                $workflow->workflow_type == 'clearance';

                                            // Get form type
                                            $formType = 'Unknown';
                                            $formTypeIcon = 'fa-question-circle';
                                            if ($isClearance) {
                                                $formType = 'Clearance';
                                                $formTypeIcon = 'fa-clipboard-check';
                                            } elseif ($workflow->ict_request_resource_id) {
                                                $formType = 'ICT Access';
                                                $formTypeIcon = 'fa-server';
                                            } elseif ($workflow->hr_form) {
                                                $formType = 'HR Form';
                                                $formTypeIcon = 'fa-file-alt';
                                            } elseif ($workflow->requisition_id) {
                                                $formType = 'Requisition';
                                                $formTypeIcon = 'fa-shopping-cart';
                                            } elseif ($workflow->locum_request_id) {
                                                $formType = 'Locum Request';
                                                $formTypeIcon = 'fa-user-md';
                                            } elseif ($workflow->on_call_request_id) {
                                                $formType = 'On-Call Request';
                                                $formTypeIcon = 'fa-phone';
                                            }

                                            $allHistories = $isClearance
                                                ? $workflow->histories ?? collect()
                                                : $workflow->workflowHistory ?? collect();
                                            $errorHistories = $allHistories
                                                ->where('status', 0)
                                                ->filter(function ($history) {
                                                    return !$history->attendedBy ||
                                                        ($history->attendedBy &&
                                                            $history->attendedBy->status != 'active');
                                                });
                                            $currentErrorHistory = $errorHistories->first();
                                            $errorSummary = '';
                                            if ($currentErrorHistory) {
                                                if (!$currentErrorHistory->attendedBy) {
                                                    $errorSummary =
                                                        'User Not Found (ID: ' .
                                                        $currentErrorHistory->attended_by .
                                                        ')';
                                                } elseif ($currentErrorHistory->attendedBy->status != 'active') {
                                                    $errorSummary =
                                                        'Inactive User: ' . $currentErrorHistory->attendedBy->username;
                                                }
                                            } elseif ($allHistories->count() == 0) {
                                                $errorSummary = 'No workflow history found';
                                            } else {
                                                $errorSummary = 'Pending/Unknown error';
                                            }
                                            $errorCount = $errorHistories->count();
                                        @endphp
                                        <tr class="table-danger">
                                            <td>
                                                @if (!$isClearance)
                                                    <input type="checkbox" name="workflow_ids[]" value="{{ $workflow->id }}"
                                                        class="workflow-checkbox">
                                                @endif
                                            </td>
                                            <td><strong>#{{ $workflow->id }}</strong></td>
                                            <td>
                                                <strong>{{ $workflow->user->fname ?? '' }}
                                                    {{ $workflow->user->lname ?? '' }}</strong>
                                                <br><small class="text-muted"><i
                                                        class="fas fa-user me-1"></i>{{ $workflow->user->username ?? 'N/A' }}</small>
                                                @if ($workflow->user && $workflow->user->department)
                                                    <br><small class="text-info"><i
                                                            class="fas fa-building me-1"></i>{{ $workflow->user->department->dept_name ?? 'N/A' }}</small>
                                                @endif
                                            </td>
                                            <td>
                                                <span class="badge bg-secondary">
                                                    <i class="fas {{ $formTypeIcon }} me-1"></i>{{ $formType }}
                                                </span>
                                            </td>
                                            <td>
                                                <div class="text-danger">
                                                    <small><i
                                                            class="fas fa-exclamation-circle me-1"></i>{{ $errorSummary }}</small>
                                                    @if ($errorCount > 1)
                                                        <br><small class="text-muted">{{ $errorCount - 1 }} more
                                                            error(s)</small>
                                                    @endif
                                                    @if ($currentErrorHistory)
                                                        <br><small class="text-muted"><i
                                                                class="fas fa-calendar me-1"></i>Since:
                                                            {{ \Carbon\Carbon::parse($currentErrorHistory->created_at)->format('d M Y') }}</small>
                                                    @endif
                                                </div>
                                            </td>
                                            <td>
                                                <i
                                                    class="fas fa-calendar me-1 text-muted"></i>{{ \Carbon\Carbon::parse($workflow->created_at)->format('d M Y') }}
                                                <br><small
                                                    class="text-muted">{{ \Carbon\Carbon::parse($workflow->created_at)->format('H:i') }}</small>
                                            </td>
                                            <td>
                                                <div class="d-flex justify-content-center gap-1">
                                                    @if ($isClearance)
                                                        <a href="{{ route('workflow-management.show-clearance', $workflow->id) }}"
                                                            class="btn btn-sm" title="View Details"
                                                            style="background-color: var(--theme-color); border-color: var(--theme-color); color: white;">
                                                            <i class="fas fa-eye"></i>
                                                        </a>
                                                    @else
                                                        <a href="{{ route('workflow-management.show', $workflow->id) }}"
                                                            class="btn btn-sm" title="View Details"
                                                            style="background-color: var(--theme-color); border-color: var(--theme-color); color: white;">
                                                            <i class="fas fa-eye"></i>
                                                        </a>
                                                    @endif
                                                    @if ($currentErrorHistory)
                                                        @if ($isClearance)
                                                            <a href="{{ route('workflow-management.edit-clearance-history', $currentErrorHistory->id) }}"
                                                                class="btn btn-sm btn-warning" title="Fix Error">
                                                                <i class="fas fa-wrench"></i>
                                                            </a>
                                                        @else
                                                            <a href="{{ route('workflow-management.edit-history', $currentErrorHistory->id) }}"
                                                                class="btn btn-sm btn-warning" title="Fix Error">
                                                                <i class="fas fa-wrench"></i>
                                                            </a>
                                                        @endif
                                                    @endif
                                                    @if (Auth::user()->can('manage workflows') && !$isClearance)
                                                        <form
                                                            action="{{ route('workflow-management.destroy', $workflow->id) }}"
                                                            method="POST" class="d-inline"
                                                            onsubmit="return confirm('Are you sure you want to delete workflow #{{ $workflow->id }}? This will also delete all associated workflow history. This action cannot be undone.');">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit" class="btn btn-sm btn-danger"
                                                                title="Delete Workflow">
                                                                <i class="fas fa-trash"></i>
                                                            </button>
                                                        </form>
                                                    @endif
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="7" class="text-center py-4">
                                                <i class="fas fa-check-circle fa-2x text-success mb-2"></i>
                                                <p class="text-success mb-0"><strong>Great!</strong> No workflows with
                                                    errors found.</p>
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    {{-- Scripts --}}
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.datatables.net/2.1.2/js/dataTables.js"></script>
    <script src="https://cdn.datatables.net/buttons/3.1.0/js/dataTables.buttons.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/3.1.0/js/buttons.html5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/3.1.0/js/buttons.print.min.js"></script>

    <script>
        // ====== DataTable ======
        $(document).ready(function() {
            // Initialize DataTable with explicit column definitions
            const table = $('#errorsTable').DataTable({
                responsive: true,
                order: [
                    [5, 'desc']
                ], // Created column
                pageLength: 25,
                lengthMenu: [
                    [10, 25, 50, 100, -1],
                    [10, 25, 50, 100, "All"]
                ],
                columnDefs: [{
                        targets: [0, 6],
                        orderable: false
                    },
                    {
                        targets: 1,
                        type: 'num'
                    }
                ],
                language: {
                    search: "Search errors:",
                    lengthMenu: "Show _MENU_ workflows per page",
                    info: "Showing _START_ to _END_ of _TOTAL_ workflows with errors",
                    infoEmpty: "Showing 0 to 0 of 0 workflows",
                    infoFiltered: "(filtered from _MAX_ total workflows)",
                    zeroRecords: "No matching workflows with errors found",
                    emptyTable: "No workflows with errors found"
                }
            });

            // Bulk select handling
            const selectAll = $('#selectAll');
            const checkboxes = $('.workflow-checkbox');
            const bulkBtn = $('#bulkDeleteBtn');

            function updateBulkButton() {
                const anyChecked = $('.workflow-checkbox:checked').length > 0;
                bulkBtn.prop('disabled', !anyChecked);
            }

            selectAll.on('change', function() {
                const checked = $(this).is(':checked');
                checkboxes.prop('checked', checked);
                updateBulkButton();
            });

            $(document).on('change', '.workflow-checkbox', function() {
                const total = checkboxes.length;
                const checkedCount = $('.workflow-checkbox:checked').length;
                selectAll.prop('checked', total > 0 && checkedCount === total);
                updateBulkButton();
            });

            updateBulkButton();
        });
    </script>
@endsection
