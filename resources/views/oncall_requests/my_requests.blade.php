{{-- resources/views/oncall_requests/my_requests.blade.php --}}
@extends('layouts.template')

@push('styles')
    <style>
        :root {
            --brand: #198754
        }

        .status-pills .btn-filter {
            border: 1px solid var(--brand);
            color: var(--brand);
            background: #fff;
            padding: .25rem .5rem;
            font-size: .85rem;
            line-height: 1.1;
            border-radius: .4rem
        }

        .status-pills .btn-filter:hover,
        .status-pills .btn-filter.active {
            background: var(--brand);
            color: #fff
        }

        .status-pills .badge {
            font-size: .7rem;
            margin-left: .25rem
        }

        /* Loading button styles */
        .btn-loading {
            position: relative;
            pointer-events: none;
            opacity: 0.7;
        }

        .btn-loading::after {
            content: "";
            position: absolute;
            width: 16px;
            height: 16px;
            top: 50%;
            left: 50%;
            margin-left: -8px;
            margin-top: -8px;
            border: 2px solid #ffffff;
            border-radius: 50%;
            border-top-color: transparent;
            animation: spinner 0.6s linear infinite;
        }

        @keyframes spinner {
            to {
                transform: rotate(360deg);
            }
        }

        .btn-loading .btn-text {
            opacity: 0;
        }
    </style>
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" />
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
@endpush

@section('content')
    @include('sweetalert::alert')

    <div class="page-wrapper">
        <div class="content container-fluid">
            <div class="page-header">
                <div class="d-flex align-items-end justify-content-between gap-2 flex-wrap">
                    <div>
                        <h3 class="page-title mb-1">My On-Call Requests</h3>
                        <div class="text-muted small">Review and track your on-call claim requests.</div>
                    </div>
                    <div class="page-tools d-flex gap-2">
                        <a href="{{ route('oncall_requests.index') }}" class="btn btn-outline-secondary btn-sm">
                            <i class="fas fa-arrow-left me-1"></i> Back
                        </a>
                        <a href="{{ route('oncall_requests.create') }}" class="btn btn-primary btn-sm">
                            <i class="fas fa-plus me-1"></i> New Claim
                        </a>
                    </div>
                </div>
            </div>

            @if (session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    {{ session('success') }} <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif
            @if (session('error'))
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    {!! session('error') !!} <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            <div class="card">
                <div class="card-body">
                    {{-- Filter Pills --}}
                    <div class="d-flex flex-wrap align-items-center gap-2 mb-3 status-pills" id="statusPills">
                        <button type="button" class="btn btn-filter active" data-filter="all">
                            All <span class="badge bg-secondary">{{ $statusCounts['All'] ?? 0 }}</span>
                        </button>
                        <button type="button" class="btn btn-filter" data-filter="Pending">
                            Pending <span class="badge bg-warning text-dark">{{ $statusCounts['Pending'] ?? 0 }}</span>
                        </button>
                        <button type="button" class="btn btn-filter" data-filter="Approved">
                            Approved <span class="badge bg-success">{{ $statusCounts['Approved'] ?? 0 }}</span>
                        </button>
                        <button type="button" class="btn btn-filter" data-filter="Rejected">
                            Rejected <span class="badge bg-danger">{{ $statusCounts['Rejected'] ?? 0 }}</span>
                        </button>
                    </div>

                    <div class="table-responsive">
                        <table id="myRequestsTable" class="table table-striped table-hover table-bordered align-middle" style="width:100%">
                            <thead class="table-success">
                                <tr>
                                    <th>Month</th>
                                    <th>Days</th>
                                    <th>Amount (TZS)</th>
                                    <th>Status</th>
                                    <th>Created</th>
                                    <th>View</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($requests as $request)
                                    @php
                                        $period = trim(collect([$request->locum_month ?? null, $request->locum_year ?? null])->filter()->implode(' '));
                                        if ($period === '') $period = '—';

                                        $wf = $request->workflow ?? null;
                                        $histories = $wf ? $wf->histories : collect();
                                        $lastHistory = $histories->sortByDesc('updated_at')->first();

                                        $pendingLabel = function (?string $step) {
                                            return match ($step) {
                                                'Line Manager Approval' => 'Pending Line Manager Approval',
                                                'HEC Approval' => 'Pending HEC Approval',
                                                'HR Approval' => 'Pending HR Approval',
                                                default => 'Pending',
                                            };
                                        };

                                        $label = 'Pending';
                                        $class = 'bg-warning';
                                        $tooltip = null;

                                        // First check if workflow is completed (approved) - this takes priority
                                        if ($wf && (int) $wf->work_flow_completed === 1) {
                                            $hrApproved = $histories->where('step_name', 'HR Approval')->where('status', 1)->sortByDesc('id')->first();
                                            if ($hrApproved) {
                                                $label = 'HR Approved';
                                                $class = 'bg-success';
                                            } else {
                                                $lastApproved = $histories->where('status', 1)->sortByDesc('id')->first();
                                                if ($lastApproved) {
                                                    $label = 'Approved by ' . $lastApproved->step_name;
                                                    $class = 'bg-success';
                                                } else {
                                                    $label = 'Approved';
                                                    $class = 'bg-success';
                                                }
                                            }
                                        } else {
                                            // Check for rejection, but also check if it was resubmitted
                                            $rejectedHistory = $histories->where('status', 2)->sortByDesc('id')->first();
                                            $hasPending = $histories->where('status', 0)->isNotEmpty();
                                            
                                            // If there are pending histories, check if they were created after rejection
                                            $isResubmitted = false;
                                            if ($rejectedHistory) {
                                                $historiesAfterRejection = $histories->where('id', '>', $rejectedHistory->id);
                                                // If there are any histories after rejection, it was resubmitted
                                                $isResubmitted = $historiesAfterRejection->isNotEmpty();
                                            }
                                            
                                            // Only show as rejected if not resubmitted
                                            if (($rejectedHistory || $request->status === 'rejected') && !$isResubmitted) {
                                                $label = 'Rejected';
                                                $class = 'bg-danger';
                                                $lastRejection = $rejectedHistory ?? $histories->where('action_taken', 'Rejected')->sortByDesc('created_at')->first();
                                                if (!empty($lastRejection?->rejection_reason)) {
                                                    $tooltip = $lastRejection->rejection_reason;
                                                }
                                            } else {
                                                $nextPending = $histories->firstWhere('status', 0);
                                                if ($nextPending) {
                                                    $label = $pendingLabel($nextPending->step_name);
                                                    $class = 'bg-warning';
                                                } else {
                                                    // Check last step to determine status
                                                    $lastStep = $histories->sortByDesc('id')->first();
                                                    if ($lastStep) {
                                                        if ((string) $lastStep->status === '0') {
                                                            $label = $pendingLabel($lastStep->step_name);
                                                            $class = 'bg-warning';
                                                        } elseif ((string) $lastStep->status === '1') {
                                                            $label = 'Approved by ' . $lastStep->step_name;
                                                            $class = 'bg-success';
                                                        }
                                                    }
                                                }
                                            }
                                        }
                                    @endphp

                                    <tr data-status="{{ $label }}">
                                        <td>{{ $period }}</td>
                                        <td>{{ (int) ($request->number_of_days ?? 0) }}</td>
                                        <td>{{ number_format((float) ($request->total_amount_payable ?? 0), 2) }}</td>
                                        <td>
                                            <span class="badge {{ $class }}" @if ($tooltip) data-bs-toggle="tooltip" data-bs-title="{{ $tooltip }}" @endif>
                                                {{ $label }}
                                            </span>
                                        </td>
                                        <td>{{ optional(\Carbon\Carbon::parse($request->created_at))->format('d M Y H:i') }}</td>
                                        <td>
                                            <button class="btn btn-outline-primary btn-sm view-details"
                                                data-id="{{ $request->id }}"
                                                data-url-template="{{ route('oncall_requests.status', ['id' => '__ID__']) }}"
                                                data-monthlabel="{{ $period }}">
                                                <i class="fas fa-eye"></i> View
                                            </button>
                                            @if ($label === 'Rejected' && $request->status === 'rejected')
                                                <a href="{{ route('oncall_requests.edit', $request->id) }}" 
                                                    class="btn btn-outline-warning btn-sm ms-1"
                                                    title="Edit and resubmit">
                                                    <i class="fas fa-edit"></i> Edit
                                                </a>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Details Modal (Workflow History) --}}
    <div class="modal fade" id="detailsModal" tabindex="-1" aria-labelledby="detailsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        On-Call Request Details
                        <small class="text-muted ms-2" id="detailsMonth">—</small>
                    </h5>
                    <button type="button" class="btn btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="p-2 border rounded mb-4">
                        <div class="d-flex align-items-center gap-2 mb-1">
                            <span id="wf-badge" class="badge bg-secondary">Loading…</span>
                            <small id="wf-tooltip" class="text-muted"></small>
                        </div>
                        <div class="small">
                            <span class="text-muted">Pending with:</span> <span id="wf-pending-with">—</span>
                            <span class="text-muted ms-3">Step:</span> <span id="wf-pending-step">—</span>
                        </div>
                    </div>

                    <h6 class="section-title mb-2">Workflow Timeline</h6>
                    <div class="card shadow-sm mb-4">
                        <div class="card-body p-0">
                            <div class="table-responsive modal-scroll">
                                <table class="table table-sm table-hover table-nowrap align-middle mb-0" id="wf-steps-table">
                                    <thead class="table-light sticky-top">
                                        <tr>
                                            <th style="width:32%">Step</th>
                                            <th style="width:20%">Attended By</th>
                                            <th style="width:20%">Forwarded By</th>
                                            <th style="width:12%">Status</th>
                                            <th style="width:16%">Acted At</th>
                                        </tr>
                                    </thead>
                                    <tbody></tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        window.setButtonLoading = function(button, isLoading) {
            if (!button) return;
            const btnText = button.querySelector('.btn-text');
            const spinner = button.querySelector('.js-spinner');
            const loadingLabel = button.getAttribute('data-loading-label') || 'Processing...';

            if (isLoading) {
                button.disabled = true;
                button.classList.add('btn-loading');
                if (btnText) {
                    if (!button.hasAttribute('data-original-html')) {
                        button.setAttribute('data-original-html', btnText.innerHTML);
                    }
                    btnText.innerHTML = loadingLabel;
                }
                if (spinner) {
                    spinner.classList.remove('d-none');
                }
            } else {
                button.disabled = false;
                button.classList.remove('btn-loading');
                if (btnText) {
                    const originalHtml = button.getAttribute('data-original-html');
                    if (originalHtml) {
                        btnText.innerHTML = originalHtml;
                        button.removeAttribute('data-original-html');
                    }
                }
                if (spinner) {
                    spinner.classList.add('d-none');
                }
            }
        };

        document.addEventListener('DOMContentLoaded', function() {
            var table = $('#myRequestsTable').DataTable({
                order: [[4, 'desc']],
                pageLength: 10,
                pagingType: 'simple_numbers',
                language: {
                    search: "Search:",
                    lengthMenu: "Show _MENU_ entries",
                    info: "Showing _START_ to _END_ of _TOTAL_ entries",
                    paginate: {
                        previous: "Previous",
                        next: "Next"
                    }
                },
                columnDefs: [
                    { orderable: true, targets: [0, 1, 2, 3, 4] },
                    { orderable: false, targets: [5] }
                ]
            });

            $('#statusPills button').on('click', function() {
                var filter = $(this).data('filter');
                var pills = $('#statusPills button');
                pills.removeClass('active');
                $(this).addClass('active');

                if (filter === 'all') {
                    table.column(3).search('').draw();
                } else {
                    $.fn.dataTable.ext.search = [];
                    $.fn.dataTable.ext.search.push(
                        function(settings, data, dataIndex) {
                            var row = table.row(dataIndex).node();
                            var status = $(row).attr('data-status') || '';
                            return status === filter;
                        }
                    );
                    table.draw();
                }
            });

            $(document).on('click', '.view-details', function() {
                var id = $(this).attr('data-id');
                var tpl = $(this).attr('data-url-template');
                var url = (tpl || '').replace('__ID__', id);
                var monthLabel = $(this).attr('data-monthlabel') || '—';

                $('#detailsMonth').text(monthLabel);
                $('#wf-badge').removeClass('bg-success bg-warning bg-danger bg-secondary')
                    .addClass('bg-secondary').text('Loading…');
                $('#wf-tooltip').text('');
                $('#wf-pending-with').text('—');
                $('#wf-pending-step').text('—');
                $('#wf-steps-table tbody').empty();

                var modal = new bootstrap.Modal(document.getElementById('detailsModal'));
                modal.show();

                $.ajax({
                    url: url,
                    method: 'GET',
                    success: function(data) {
                        var wf = (data && data.workflow) ? data.workflow : {};
                        var kind = (wf.current_class ? wf.current_class : 'secondary').replace('bg-', '');
                        var map = {
                            success: 'bg-success',
                            warning: 'bg-warning',
                            danger: 'bg-danger',
                            secondary: 'bg-secondary'
                        };
                        var klass = map[kind] ? map[kind] : 'bg-secondary';
                        $('#wf-badge').removeClass('bg-success bg-warning bg-danger bg-secondary')
                            .addClass(klass).text(wf.current_label ? wf.current_label : '—');
                        $('#wf-tooltip').text(wf.tooltip ? wf.tooltip : '');
                        $('#wf-pending-with').text(wf.pending_with ? wf.pending_with : (wf.completed ? '—' : 'Unknown'));
                        $('#wf-pending-step').text(wf.pending_step ? wf.pending_step : (wf.completed ? '—' : 'Pending'));

                        var $tb = $('#wf-steps-table tbody').empty();
                        var steps = (wf.steps && Array.isArray(wf.steps)) ? wf.steps : [];
                        steps.forEach(function(step) {
                            var sClass = (step.status === 'Approved') ? 'bg-success' : 
                                        (step.status === 'Rejected') ? 'bg-danger' : 'bg-warning';
                            var extra = '';
                            if (step.rejection) {
                                extra = '<div class="small text-danger mt-1">' + step.rejection + '</div>';
                            } else if (step.status === 'Pending' && step.remark) {
                                extra = '<div class="small text-muted mt-1">' + step.remark + '</div>';
                            }
                            $tb.append(
                                '<tr>' +
                                '<td><div class="fw-semibold">' + (step.step ? step.step : '—') + '</div>' + extra + '</td>' +
                                '<td>' + (step.attended_by ? step.attended_by : '—') + '</td>' +
                                '<td>' + (step.forwarded_by ? step.forwarded_by : '—') + '</td>' +
                                '<td><span class="badge ' + sClass + '">' + step.status + '</span></td>' +
                                '<td>' + (step.acted_at ? step.acted_at : '—') + '</td>' +
                                '</tr>'
                            );
                        });
                    },
                    error: function() {
                        alert('Failed to load workflow details.');
                    }
                });
            });

            const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            tooltipTriggerList.map(function(tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });
        });
    </script>
@endsection

