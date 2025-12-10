@extends('layouts.template')
@include('sweetalert::alert')

@section('content')
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <div class="page-wrapper">
        <div class="content container-fluid">
            <div class="page-header">
                <div class="row">
                    <div class="col-sm-12">
                        <div class="page-sub-header d-flex justify-content-between align-items-center">
                            <div class="btn-group" role="group" aria-label="Requisition Navigation">
                                <a href="{{ route('requisitions.index') }}" class="btn btn-outline-secondary btn-sm me-2">
                                    All Requisitions
                                </a>
                                <a href="{{ route('requisitions.view') }}" class="btn btn-outline-primary btn-sm">
                                    Pending Approvals
                                </a>
                            </div>
                            <h4 class="page-title mb-0 fs-6">Pending Requisition Approvals</h4>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card shadow-sm mb-4">
                <div class="card-body">
                    @if ($requisitions->isEmpty())
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>No pending requisitions requiring your approval.
                        </div>
                    @else
                        <div class="table-responsive">
                            <table id="requisitionsApproveTable" class="table table-hover table-striped align-middle w-100">
                                <thead class="table-light">
                                    <tr>
                                        <th class="text-center" style="width: 50px;">#</th>
                                        <th style="width: 180px;"><i class="fas fa-user me-1 text-muted"></i>Requester</th>
                                        <th style="width: 180px;"><i class="fas fa-building me-1 text-muted"></i>Department
                                        </th>
                                        <th style="width: 120px;"><i class="fas fa-circle-notch me-1 text-muted"></i>Status
                                        </th>
                                        <th style="width: 120px;"><i
                                                class="fas fa-calendar-alt me-1 text-muted"></i>Submitted</th>
                                        <th class="text-center" style="width: 110px;"><i
                                                class="fas fa-cog me-1 text-muted"></i>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($requisitions as $index => $requisition)
                                        @php
                                            $workflow = $requisition->workflow;

                                            // Check if workflow is completed
                                            $isCompleted = $workflow && $workflow->work_flow_completed == 1;

                                            // Check for HR completion/rejection status
                                            $hrCompleted = false;
                                            $hrRejected = false;
                                            if ($workflow && $workflow->histories->count() > 0) {
                                                $hrHistory = $workflow
                                                    ->histories()
                                                    ->whereHas('attendedBy.roles', function ($q) {
                                                        $q->where('name', 'hr');
                                                    })
                                                    ->whereIn('requisition_status', [3, 2])
                                                    ->latest()
                                                    ->first();

                                                if ($hrHistory) {
                                                    if ($hrHistory->requisition_status == 3) {
                                                        $hrCompleted = true;
                                                    } elseif ($hrHistory->requisition_status == 2) {
                                                        $hrRejected = true;
                                                    }
                                                }
                                            }

                                            // Determine status based on completion (matching index page logic)
                                            if ($isCompleted) {
                                                // If workflow is completed, show "Completed" status
                                                $statusInfo = ['label' => 'Completed', 'class' => 'success'];
                                            } elseif ($hrRejected) {
                                                $statusInfo = ['label' => 'Rejected', 'class' => 'danger'];
                                            } else {
                                                // Check for pending items for current user
                                                $currentHistory = $workflow
                                                    ? $workflow->histories
                                                        ->where('attended_by', Auth::id())
                                                        ->whereIn('requisition_status', [0, 1])
                                                        ->whereNull('decision_date')
                                                        ->first()
                                                    : null;

                                                // Check if HR is pending (for HR users)
                                                $hrPending = false;
                                                if ($workflow && !$isCompleted && Auth::user()->hasRole('hr')) {
                                                    $hrPendingHistory = $workflow
                                                        ->histories()
                                                        ->whereHas('attendedBy.roles', function ($q) {
                                                            $q->where('name', 'hr');
                                                        })
                                                        ->where('requisition_status', 1)
                                                        ->whereNull('decision_date')
                                                        ->where('attended_by', Auth::id())
                                                        ->latest()
                                                        ->first();
                                                    $hrPending = $hrPendingHistory !== null;
                                                }

                                                if ($hrPending) {
                                                    $statusInfo = ['label' => 'Pending HR Review', 'class' => 'info'];
                                                } else {
                                                    $status = $currentHistory ? $currentHistory->requisition_status : 0;
                                                    $statusLabels = [
                                                        0 => ['label' => 'Pending', 'class' => 'warning'],
                                                        1 => ['label' => 'Under Review', 'class' => 'info'],
                                                        2 => ['label' => 'Rejected', 'class' => 'danger'],
                                                    ];
                                                    $statusInfo = $statusLabels[$status] ?? $statusLabels[0];
                                                }
                                            }

                                            // Derive current step label from workflow history
                                            $currentStep = $currentHistory?->step_name ?? '—';

                                            // Derive high‑level route summary based on initiator, background and funding
                                            $initiator = $requisition->user;
                                            $initiatorRole = $initiator?->roles?->first()?->name ?? '';
                                            $isNewPosition =
                                                isset($requisition->background) &&
                                                $requisition->background === 'new_position';
                                            $budgetApproved = (bool) $requisition->budget_approved;
                                            $fundingAvailable = (bool) $requisition->funding_available;
                                            $hasBudgetAndFunds = $budgetApproved && $fundingAvailable;

                                            if (in_array($initiatorRole, ['coo', 'cms'])) {
                                                // HEC‑initiated (COO / CMS) flow
                                                if ($isNewPosition) {
                                                    // New position: always goes through CFO/CEO
                                                    $routeSummary =
                                                        'HEC (COO/CMS) → Payroll Accountant → CFO → CEO → HR';
                                                } else {
                                                    // Replacement/Renewal: check budget
                                                    if ($budgetApproved && $fundingAvailable) {
                                                        // With budget: skip CFO/CEO
                                                        $routeSummary = 'HEC (COO/CMS) → Payroll Accountant → HR';
                                                    } else {
                                                        // Without budget: go through CFO/CEO
                                                        $routeSummary =
                                                            'HEC (COO/CMS) → Payroll Accountant → CFO → CEO → HR';
                                                    }
                                                }
                                            } else {
                                                // Line Manager initiated
                                                if ($budgetApproved && !$fundingAvailable) {
                                                    // Budget approved but funds NOT available → skip CFO/CEO
                                                    $routeSummary = 'Line Manager → Payroll Accountant → HEC → HR';
                                                } elseif (!$budgetApproved && $fundingAvailable) {
                                                    // Budget NOT approved but funds available → goes to CFO/CEO when no objection
                                                    $routeSummary =
                                                        'Line Manager → Payroll Accountant → HEC → CFO → CEO → HR';
                                                } elseif ($budgetApproved && $fundingAvailable) {
                                                    if ($isNewPosition) {
                                                        // New position with budget & funds → goes to CFO/CEO when no objection
                                                        $routeSummary =
                                                            'Line Manager → Payroll Accountant → HEC → CFO → CEO → HR';
                                                    } else {
                                                        // Replacement/Renewal with budget & funds → goes directly to HR
                                                        $routeSummary = 'Line Manager → Payroll Accountant → HEC → HR';
                                                    }
                                                } else {
                                                    // Budget NOT approved AND funds NOT available → goes to CFO/CEO when no objection
                                                    $routeSummary =
                                                        'Line Manager → Payroll Accountant → HEC → CFO → CEO → HR';
                                                }
                                            }
                                        @endphp
                                        @php
                                            // Check if initiated by HEC member
                                            $initiatedByHEC = false;
                                            $hecInitiator = null;
                                            $lineManagerFor = null;

                                            if ($workflow && $workflow->histories->count() > 0) {
                                                $firstHistory = $workflow->histories->sortBy('created_at')->first();
                                                if ($firstHistory && $firstHistory->forwardedBy) {
                                                    $initiator = $firstHistory->forwardedBy;
                                                    if ($initiator->hasAnyRole(['coo', 'cms'])) {
                                                        $initiatedByHEC = true;
                                                        $hecInitiator = $initiator;
                                                        $lineManagerFor = $requisition->user;
                                                    }
                                                }
                                            }

                                            // Progress indicator logic
                                            $currentStage = 'Not Started';
                                            $currentStageIndex = -1;
                                            $payrollDone = false;
                                            $hecDone = false;
                                            $cfoDone = false;
                                            $ceoDone = false;
                                            $hrDone = false;
                                            $payrollPending = false;
                                            $hecPending = false;
                                            $cfoPending = false;
                                            $ceoPending = false;
                                            $hrPending = false;

                                            if ($workflow && $workflow->histories->count() > 0) {
                                                $histories = $workflow->histories->sortBy('created_at');

                                                foreach ($histories as $history) {
                                                    if (!$history->attendedBy) {
                                                        continue;
                                                    }

                                                    $role = $history->attendedBy->getRoleNames()->first() ?? '';
                                                    $status = $history->requisition_status;
                                                    $hasDecision = !is_null($history->decision_date);

                                                    if ($role === 'payroll_accountant') {
                                                        if ($hasDecision && $status != 2) {
                                                            $payrollDone = true;
                                                        } elseif (!$hasDecision && $status == 0) {
                                                            $payrollPending = true;
                                                        }
                                                    } elseif (in_array($role, ['coo', 'cms', 'chief_accountant'])) {
                                                        if ($hasDecision && $status != 2) {
                                                            $hecDone = true;
                                                        } elseif (!$hasDecision && $status == 1) {
                                                            $hecPending = true;
                                                        }
                                                    } elseif ($role === 'cfo') {
                                                        if ($hasDecision && $status != 2) {
                                                            $cfoDone = true;
                                                        } elseif (!$hasDecision && $status == 1) {
                                                            $cfoPending = true;
                                                        }
                                                    } elseif ($role === 'ceo') {
                                                        if ($hasDecision && $status != 2) {
                                                            $ceoDone = true;
                                                        } elseif (!$hasDecision && $status == 1) {
                                                            $ceoPending = true;
                                                        }
                                                    } elseif ($role === 'hr') {
                                                        if ($hasDecision && ($status == 3 || $status == -1)) {
                                                            $hrDone = true;
                                                        } elseif (!$hasDecision && $status == 1) {
                                                            $hrPending = true;
                                                        }
                                                    }
                                                }

                                                if ($hrPending) {
                                                    $currentStage = 'HR Review';
                                                    $currentStageIndex = 4;
                                                } elseif ($hrDone || $workflow->work_flow_completed == 1) {
                                                    $currentStage = 'Completed';
                                                    $currentStageIndex = 5;
                                                } elseif ($ceoPending) {
                                                    $currentStage = 'CEO Review';
                                                    $currentStageIndex = 3;
                                                } elseif ($ceoDone) {
                                                    $currentStage = 'CEO Approved';
                                                    $currentStageIndex = 3;
                                                } elseif ($cfoPending) {
                                                    $currentStage = 'CFO Review';
                                                    $currentStageIndex = 2;
                                                } elseif ($cfoDone) {
                                                    $currentStage = 'CFO Approved';
                                                    $currentStageIndex = 2;
                                                } elseif ($hecPending) {
                                                    $currentStage = 'HEC Review';
                                                    $currentStageIndex = 1;
                                                } elseif ($hecDone) {
                                                    $currentStage = 'HEC Approved';
                                                    $currentStageIndex = 1;
                                                } elseif ($payrollPending) {
                                                    $currentStage = 'Payroll Review';
                                                    $currentStageIndex = 0;
                                                } elseif ($payrollDone) {
                                                    $currentStage = 'Payroll Approved';
                                                    $currentStageIndex = 0;
                                                }
                                            }
                                        @endphp
                                        <tr>
                                            <td class="text-center">{{ $index + 1 }}</td>
                                            <td>
                                                @if ($initiatedByHEC && $hecInitiator)
                                                    {{-- Show HEC Member as Requester --}}
                                                    <div class="d-flex flex-column">
                                                        <div class="d-flex align-items-center gap-1 mb-1">
                                                            <span class="fw-semibold">
                                                                {{ $hecInitiator->fname ?? '' }}
                                                                {{ $hecInitiator->mname ?? '' }}
                                                                {{ $hecInitiator->lname ?? '' }}
                                                            </span>
                                                            <span class="badge bg-info text-white"
                                                                style="font-size: 0.6rem;" title="HEC Member (Requester)">
                                                                <i class="fas fa-user-shield"></i>
                                                            </span>
                                                        </div>
                                                        @if (!empty($hecInitiator->employee_id))
                                                            <small class="text-muted">
                                                                <i
                                                                    class="fas fa-id-badge me-1"></i>{{ $hecInitiator->employee_id }}
                                                            </small>
                                                        @endif
                                                    </div>
                                                @elseif ($requisition->user)
                                                    {{-- Show Line Manager as Requester --}}
                                                    <div class="d-flex flex-column">
                                                        <div class="d-flex align-items-center gap-1 mb-1">
                                                            <span class="fw-semibold">
                                                                {{ $requisition->user->fname ?? '' }}
                                                                {{ $requisition->user->mname ?? '' }}
                                                                {{ $requisition->user->lname ?? '' }}
                                                            </span>
                                                            @if ($requisition->user->hasRole('line-manager'))
                                                                <span class="badge bg-primary text-white"
                                                                    style="font-size: 0.6rem;" title="Line Manager">
                                                                    <i class="fas fa-user-tie"></i>
                                                                </span>
                                                            @endif
                                                        </div>
                                                        @if (!empty($requisition->user->employee_id))
                                                            <small class="text-muted">
                                                                <i
                                                                    class="fas fa-id-badge me-1"></i>{{ $requisition->user->employee_id }}
                                                            </small>
                                                        @endif
                                                    </div>
                                                @else
                                                    <span class="text-muted">N/A</span>
                                                @endif
                                            </td>
                                            <td>
                                                <span class="text-dark">
                                                    <i class="fas fa-building me-1 text-muted"></i>
                                                    {{ $requisition->user->department->dept_name ?? 'N/A' }}
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge bg-{{ $statusInfo['class'] }}">
                                                    {{ $statusInfo['label'] }}
                                                </span>
                                            </td>
                                            <td>
                                                <small class="text-muted">
                                                    <i class="fas fa-calendar-alt me-1 text-muted"></i>
                                                    {{ $requisition->created_at->format('Y-m-d') }}
                                                </small>
                                            </td>
                                            <td class="text-center">
                                                <a href="{{ route('requisitions.show', $requisition->id) }}"
                                                    class="btn btn-sm btn-outline-success" title="Review">
                                                    <i class="fas fa-eye"></i>
                                                </a>
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

@endsection

@push('styles')
    <link rel="stylesheet" href="https://cdn.datatables.net/2.1.2/css/dataTables.dataTables.css" />
    <style>
        .table-hover tbody tr:hover {
            background-color: #f8f9fa;
            transition: background-color 0.2s;
        }
    </style>
@endpush

@push('scripts')
    <script src="https://cdn.datatables.net/2.1.2/js/dataTables.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize DataTable
            const table = new DataTable('#requisitionsApproveTable', {
                responsive: true,
                pageLength: 25,
                lengthMenu: [
                    [10, 25, 50, 100, -1],
                    [10, 25, 50, 100, 'All']
                ],
                order: [
                    [0, 'desc']
                ],
                columnDefs: [{
                    orderable: false,
                    targets: [5] // Actions column
                }],
                language: {
                    searchPlaceholder: 'Search requisitions...',
                    emptyTable: 'No requisitions found',
                    info: 'Showing _START_ to _END_ of _TOTAL_ entries',
                    search: 'Search:',
                    lengthMenu: 'Show _MENU_ entries'
                }
            });
        });
    </script>
@endpush
