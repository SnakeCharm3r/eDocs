@extends('layouts.template')

@section('breadcrumb')
    @include('sweetalert::alert')
@endsection

@section('content')
    <style>
        .view-agreements-page .page-header {
            padding-bottom: 1rem;
            margin-bottom: 1rem;
            border-bottom: 1px solid var(--bs-border-color-translucent, #dee2e6);
        }

        .view-agreements-page .card {
            border-radius: 0.5rem;
            border: 1px solid rgba(0, 0, 0, .08);
            overflow: hidden;
        }

        .view-agreements-page .card-header {
            border-radius: 0;
            font-weight: 600;
            padding: 0.875rem 1.25rem;
            border-bottom: 1px solid rgba(0, 0, 0, .06);
        }

        .view-agreements-page .card-body {
            padding: 1.25rem;
        }

        .view-agreements-page #agreementsTable thead th {
            font-weight: 600;
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.03em;
            color: #495057;
            border-bottom: 2px solid #dee2e6;
            padding: 0.75rem 1rem;
            white-space: nowrap;
        }

        .view-agreements-page #agreementsTable tbody td {
            padding: 0.75rem 1rem;
            vertical-align: middle;
        }

        .view-agreements-page #agreementsTable tbody tr:hover {
            background-color: rgba(0, 0, 0, .02);
        }

        .view-agreements-page .badge {
            font-size: 0.7rem;
            padding: 0.4em 0.65em;
            font-weight: 500;
        }

        .view-agreements-page .action-btns {
            flex-wrap: wrap;
            gap: 0.35rem;
            justify-content: center;
        }

        .view-agreements-page .action-btns .btn {
            min-width: 2rem;
        }

        .view-agreements-page .empty-state-icon {
            width: 4.5rem;
            height: 4.5rem;
            border-radius: 50%;
            background: linear-gradient(135deg, rgba(13, 110, 253, 0.12) 0%, rgba(13, 110, 253, 0.06) 100%);
            color: #0d6efd;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 1.25rem;
        }

        .view-agreements-page .dataTables_wrapper .row:first-child {
            margin-bottom: 1rem;
        }

        .view-agreements-page .dataTables_wrapper .dataTables_length label,
        .view-agreements-page .dataTables_wrapper .dataTables_filter label {
            margin-bottom: 0;
            font-size: 0.875rem;
        }

        .view-agreements-page .dataTables_wrapper .dataTables_paginate {
            margin-top: 1rem;
            padding-top: 0.75rem;
            border-top: 1px solid #dee2e6;
        }
    </style>
    <div class="page-wrapper view-agreements-page">
        <div class="content container-fluid">
            <!-- Page Header -->
            <div class="page-header">
                <div class="row align-items-center">
                    <div class="col">
                        <a href="{{ route('locum-requests.index') }}" class="btn btn-outline-secondary btn-sm mb-2 mb-md-0">
                            <i class="fas fa-arrow-left me-1"></i> Back to Locum Requests
                        </a>
                    </div>
                    <div class="col-auto mt-2 mt-md-0">
                        <a href="{{ route('locum-agreements.index') }}" class="btn btn-primary">
                            <i class="fas fa-plus me-1"></i>
                            {{ $agreements->where('is_active', true)->count() ? 'Request New Agreement' : 'Create New Agreement' }}
                        </a>
                    </div>
                </div>
            </div>

            @if (session('success'))
                <div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert">
                    <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif
            @if (session('error'))
                <div class="alert alert-danger alert-dismissible fade show shadow-sm" role="alert">
                    <i class="fas fa-exclamation-circle me-2"></i>{{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            <!-- Agreements Table Card -->
            <div class="row">
                <div class="col-12">
                    <div class="card shadow-sm">
                        <div class="card-header bg-light d-flex align-items-center justify-content-between flex-wrap gap-2">
                            <h5 class="mb-0"><i class="fas fa-file-contract me-2 text-primary"></i>Your agreements</h5>
                            @if (!$agreements->isEmpty())
                                <span class="badge bg-primary rounded-pill">{{ $agreements->count() }} total</span>
                            @endif
                        </div>
                        <div class="card-body">
                            @php
                                $hasActiveAgreement = $agreements->where('is_active', true)->count() > 0;
                            @endphp

                            @if ($agreements->isEmpty())
                                <div class="text-center py-5 px-4">
                                    <div class="empty-state-icon">
                                        <i class="fas fa-file-contract fa-2x"></i>
                                    </div>
                                    <h5 class="mb-2">No locum agreements yet</h5>
                                    <p class="text-muted mb-4 mx-auto" style="max-width: 360px;">Create your first agreement
                                        to start submitting locum claims. You can request a new agreement anytime from the
                                        button above.</p>
                                    <a href="{{ route('locum-agreements.index') }}" class="btn btn-primary">
                                        <i class="fas fa-plus me-1"></i> Create New Agreement
                                    </a>
                                </div>
                            @else
                                <div class="table-responsive">
                                    <table id="agreementsTable" class="table table-hover table-striped align-middle mb-0">
                                        <thead class="table-light">
                                            <tr>
                                                <th style="width:2.5rem;">#</th>
                                                <th>Staff Name</th>
                                                <th class="text-nowrap">CCBRT Code</th>
                                                <th>Education Level</th>
                                                <th class="text-end">Locum Rate</th>
                                                <th>Contract Status</th>
                                                <th>Approval Status</th>
                                                <th class="text-nowrap">Submitted</th>
                                                <th style="width:9rem;" class="text-center">Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($agreements as $index => $agreement)
                                                <tr>
                                                    <td>{{ $index + 1 }}</td>
                                                    <td>
                                                        <strong>{{ ($agreement->user->fname ?? '') . ' ' . ($agreement->user->lname ?? '') }}</strong>
                                                    </td>
                                                    <td class="text-nowrap">{{ $agreement->user->ccbrt_code ?? 'N/A' }}
                                                    </td>
                                                    <td>{{ $agreement->education_level ?? ($agreement->user->education_level ?? 'N/A') }}
                                                    </td>
                                                    <td class="text-end text-nowrap">
                                                        <strong>{{ $agreement->locum_rate ? 'TZS ' . number_format($agreement->locum_rate, 0) : 'N/A' }}</strong>
                                                    </td>
                                                    <td>
                                                        @php
                                                            // Determine contract status, ensuring EXPIRED always shows as 'Expired'
                                                            $contractStatus = 'N/A';
                                                            $contractClass = 'bg-secondary';
                                                            $icon = 'question-circle';
                                                            $contractValidUntil = null;

                                                            $today = \Carbon\Carbon::today();
                                                            $isApproved = (int) $agreement->status === 2;

                                                            // Use creation date -> creation date + 1 year (matching agreement document)
                                                            $agreementCreatedDate = \Carbon\Carbon::parse(
                                                                $agreement->created_at,
                                                            );
                                                            $validEndDate = $agreementCreatedDate->copy()->addYear();
                                                            $validStartDate = $agreementCreatedDate;

                                                            // Compute a unified "isExpired" flag that respects stored end_date override
                                                            if ($agreement->end_date) {
                                                                $endDate = \Carbon\Carbon::parse($agreement->end_date);
                                                                $isExpired = $endDate->lt($today);
                                                            } else {
                                                                $endDate = $validEndDate;
                                                                $isExpired = $validEndDate->lt($today);
                                                            }

                                                            if ($isApproved) {
                                                                // Calculate days until expiration
                                                                $daysUntilExpiration = null;
                                                                if ($agreement->end_date) {
                                                                    $endDate = \Carbon\Carbon::parse($agreement->end_date);
                                                                    $daysUntilExpiration = $today->diffInDays($endDate, false);
                                                                    $isExpired = $endDate->lt($today);
                                                                } else {
                                                                    $endDate = $validEndDate;
                                                                    $daysUntilExpiration = $today->diffInDays($endDate, false);
                                                                    $isExpired = $validEndDate->lt($today);
                                                                }

                                                                if ($isExpired) {
                                                                    // Expired agreements should only show "Expired" - no validity information
                                                                    $contractStatus = 'Expired';
                                                                    $contractClass = 'bg-danger';
                                                                    $icon = 'times-circle';
                                                                } elseif ($daysUntilExpiration <= 60 && $daysUntilExpiration > 0) {
                                                                    // Near expiration (within 60 days) - show "Valid for claiming"
                                                                    $contractStatus = 'Valid for claiming';
                                                                    $contractValidUntil = $endDate;
                                                                    $contractClass = 'bg-warning text-dark';
                                                                    $icon = 'clock';
                                                                } else {
                                                                    // Approved and not near expiration — show "Active until [date]"
                                                                    $contractStatus = 'Active';
                                                                    $contractValidUntil = $endDate;
                                                                    $contractClass = 'bg-success';
                                                                    $icon = 'check-circle';
                                                                }
                                                            } else {
                                                                // Not approved by HR - show pending status
                                                                $validStartDate = \Carbon\Carbon::parse($agreement->created_at);
                                                                if ($validStartDate->gt($today)) {
                                                                    $contractStatus = 'Not Started';
                                                                    $contractClass = 'bg-info';
                                                                    $icon = 'clock';
                                                                } else {
                                                                    $contractStatus = 'Pending HR Approval';
                                                                    $contractClass = 'bg-warning text-dark';
                                                                    $icon = 'clock';
                                                                }
                                                            }
                                                        @endphp
                                                        <span class="badge {{ $contractClass }}">
                                                            <i class="fas fa-{{ $icon }} me-1"></i>
                                                            {{ $contractStatus }}{{ $contractValidUntil ? ' until ' . $contractValidUntil->format('d M Y') : '' }}
                                                        </span>
                                                    </td>
                                                    <td>
                                                        @php
                                                            $statusMap = [
                                                                0 => [
                                                                    'label' => 'Pending to Line Manager',
                                                                    'class' => 'bg-warning text-dark',
                                                                    'icon' => 'clock',
                                                                ],
                                                                1 => [
                                                                    'label' => 'Pending to HR',
                                                                    'class' => 'bg-info',
                                                                    'icon' => 'clock',
                                                                ],
                                                                2 => [
                                                                    'label' => 'Approved',
                                                                    'class' => 'bg-success',
                                                                    'icon' => 'check-circle',
                                                                ],
                                                                3 => [
                                                                    'label' => 'Rejected by Line Manager',
                                                                    'class' => 'bg-danger',
                                                                    'icon' => 'times-circle',
                                                                ],
                                                                4 => [
                                                                    'label' => 'Rejected by HR',
                                                                    'class' => 'bg-danger',
                                                                    'icon' => 'times-circle',
                                                                ],
                                                            ];

                                                            $statusInfo = $statusMap[$agreement->status] ?? [
                                                                'label' => 'Unknown',
                                                                'class' => 'bg-secondary',
                                                                'icon' => 'question-circle',
                                                            ];
                                                        @endphp

                                                        <span class="badge {{ $statusInfo['class'] }}">
                                                            <i class="fas fa-{{ $statusInfo['icon'] }} me-1"></i>
                                                            {{ $statusInfo['label'] }}
                                                        </span>
                                                        <button type="button"
                                                            class="btn btn-link btn-sm p-0 ms-1 text-secondary align-baseline"
                                                            title="View status / reason" data-bs-toggle="modal"
                                                            data-bs-target="#statusReasonModal"
                                                            data-status-label="{{ $statusInfo['label'] }}"
                                                            data-rejection-reason="{{ in_array($agreement->status, [3, 4]) && !empty($agreement->rejection_status) ? e($agreement->rejection_status) : '' }}">
                                                            <i class="fas fa-eye"></i>
                                                        </button>
                                                    </td>
                                                    <td class="text-nowrap text-muted small">
                                                        {{ \Carbon\Carbon::parse($agreement->created_at)->format('d M Y') }}
                                                    </td>
                                                    <td>
                                                        <div class="d-flex gap-1 action-btns justify-content-center">
                                                            <a href="{{ route('locum-agreements.show', $agreement->id) }}"
                                                                class="btn btn-sm btn-outline-primary" title="View">
                                                                <i class="fas fa-eye"></i>
                                                            </a>
                                                            @php
                                                                // Check if agreement is expired (based on creation date + 1 year or stored end_date)
                                                                $agreementCreatedDate = \Carbon\Carbon::parse(
                                                                    $agreement->created_at,
                                                                );
                                                                $validEndDate = $agreementCreatedDate
                                                                    ->copy()
                                                                    ->addYear();
                                                                $today = \Carbon\Carbon::today();
                                                                $isExpired = false;

                                                                if ($agreement->end_date) {
                                                                    $endDate = \Carbon\Carbon::parse(
                                                                        $agreement->end_date,
                                                                    );
                                                                    $isExpired = $endDate->lt($today);
                                                                } else {
                                                                    $isExpired = $validEndDate->lt($today);
                                                                }

                                                                // Check if rejected and within 2 months
                                                                $canEditRejected = false;
                                                                $rejectedAt = null;
                                                                if (in_array((int) ($agreement->status ?? 0), [3, 4])) {
                                                                    if (
                                                                        $agreement->workflow &&
                                                                        $agreement->workflow->histories
                                                                    ) {
                                                                        $rejectedHistory = $agreement->workflow->histories
                                                                            ->where('status', 2)
                                                                            ->whereIn('locum_agreement_status', [3, 4])
                                                                            ->first();
                                                                        if ($rejectedHistory) {
                                                                            $rejectedAt =
                                                                                $rejectedHistory->attend_date ??
                                                                                ($rejectedHistory->updated_at ??
                                                                                    $rejectedHistory->created_at);
                                                                        }
                                                                    }
                                                                    if ($rejectedAt) {
                                                                        $rejectedDate = \Carbon\Carbon::parse(
                                                                            $rejectedAt,
                                                                        );
                                                                        $twoMonthsAgo = now()->subMonths(2);
                                                                        $canEditRejected = $rejectedDate->gte(
                                                                            $twoMonthsAgo,
                                                                        );
                                                                    } else {
                                                                        $canEditRejected = true; // Rejected but no date found
                                                                    }
                                                                }
                                                            @endphp
                                                            @if (in_array((int) ($agreement->status ?? 0), [3, 4]))
                                                                @if ($isExpired)
                                                                    <span class="btn btn-sm btn-outline-secondary disabled"
                                                                        title="This agreement has expired and cannot be edited. Please create a new agreement.">
                                                                        <i class="fas fa-ban"></i> Expired
                                                                    </span>
                                                                @elseif ($canEditRejected && !$hasActiveAgreement)
                                                                    <a href="{{ route('locum-agreements.edit', $agreement->id) }}"
                                                                        class="btn btn-sm btn-outline-warning"
                                                                        title="Edit and resubmit">
                                                                        <i class="fas fa-edit"></i> Edit & Resubmit
                                                                    </a>
                                                                @else
                                                                    <span class="btn btn-sm btn-outline-secondary disabled"
                                                                        title="This agreement was rejected more than 2 months ago">
                                                                        <i class="fas fa-lock"></i> Cannot Edit
                                                                    </span>
                                                                @endif
                                                            @elseif ($isExpired)
                                                                <span class="btn btn-sm btn-outline-secondary disabled"
                                                                    title="This agreement has expired and cannot be edited. Please create a new agreement.">
                                                                    <i class="fas fa-ban"></i> Expired
                                                                </span>
                                                            @elseif ($hasActiveAgreement)
                                                                <span class="btn btn-sm btn-outline-secondary disabled"
                                                                    title="You already have an active locum agreement. Rejected agreements cannot be edited while an active contract exists.">
                                                                    <i class="fas fa-lock"></i> Locked
                                                                </span>
                                                            @endif
                                                        </div>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Modal: View approval status / rejection reason --}}
    <div class="modal fade" id="statusReasonModal" tabindex="-1" aria-labelledby="statusReasonModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="statusReasonModalLabel">Approval status</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="mb-1"><strong>Status:</strong> <span id="statusReasonLabel">—</span></p>
                    <div id="statusReasonRejection" class="mt-2" style="display: none;">
                        <strong>Reason for rejection:</strong>
                        <p id="statusReasonText" class="mb-0 mt-1 p-2 bg-light rounded small"></p>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    @push('styles')
        <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" />
    @endpush

    @push('scripts')
        <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
        <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
        <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
        <script>
            document.getElementById('statusReasonModal') && document.getElementById('statusReasonModal').addEventListener(
                'show.bs.modal',
                function(e) {
                    var btn = e.relatedTarget;
                    if (!btn) return;
                    var label = btn.getAttribute('data-status-label') || '—';
                    var reason = btn.getAttribute('data-rejection-reason') || '';
                    document.getElementById('statusReasonLabel').textContent = label;
                    var block = document.getElementById('statusReasonRejection');
                    var text = document.getElementById('statusReasonText');
                    if (reason) {
                        block.style.display = 'block';
                        text.textContent = reason;
                    } else {
                        block.style.display = 'none';
                    }
                });
            $(document).ready(function() {
                if ($.fn.DataTable && $('#agreementsTable').length) {
                    $('#agreementsTable').DataTable({
                        paging: true,
                        pageLength: 10,
                        lengthMenu: [
                            [10, 25, 50, 100, -1],
                            [10, 25, 50, 100, "All"]
                        ],
                        searching: true,
                        ordering: true,
                        info: true,
                        autoWidth: false,
                        responsive: true,
                        order: [
                            [7, 'desc']
                        ], // Sort by Submitted (newest first)
                        columnDefs: [{
                            targets: -1,
                            orderable: false
                        }],
                        dom: '<"row align-items-center mb-3"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>rt<"row mt-2"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>',
                        language: {
                            search: "Search:",
                            searchPlaceholder: "agreements...",
                            lengthMenu: "Show _MENU_ agreements",
                            info: "Showing _START_ to _END_ of _TOTAL_ agreements",
                            infoEmpty: "No agreements",
                            infoFiltered: "(filtered from _MAX_ total)",
                            zeroRecords: "No matching agreements found",
                            paginate: {
                                first: "First",
                                last: "Last",
                                next: "Next",
                                previous: "Prev"
                            }
                        }
                    });
                }
            });
        </script>
    @endpush
@endsection
