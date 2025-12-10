{{-- resources/views/locum_requests/view_partials/_table.blade.php --}}
<style>
    /* Improved checkbox styling */
    #locumApproverTable input[type="checkbox"] {
        width: 18px;
        height: 18px;
        cursor: pointer;
        accent-color: #2fb565;
        transform: scale(1.1);
    }

    #locumApproverTable input[type="checkbox"]:checked {
        background-color: #2fb565;
        border-color: #2fb565;
    }

    #locumApproverTable tbody tr:hover {
        background-color: #f8f9fa;
    }

    #locumApproverTable tbody tr:has(input[type="checkbox"]:checked) {
        background-color: #e8f5e9 !important;
    }

    .selection-counter {
        font-size: 0.9rem;
    }

    .selection-counter .badge {
        padding: 0.5rem 0.75rem;
        font-size: 0.875rem;
    }
</style>

@if ($requests->isEmpty())
    <p>No pending locum requests.</p>
@else
    <form action="{{ route('locum-requests.bulk-approve') }}" method="POST" id="bulk-approve-form" novalidate>
        @csrf
        <input type="hidden" name="action" value="approve">

        <table id="locumApproverTable" class="table table-striped table-hover table-bordered align-middle request-table">
            <thead>
                <tr>
                    <th style="width:3rem;">
                        <input type="checkbox" class="select-all" id="select-all">
                        <label for="select-all" class="ms-1">All</label>
                    </th>
                    <th style="width:3rem;">#</th>
                    <th style="width:10rem;">CCBRT Code</th>
                    <th style="min-width:12rem;">Full Name</th>
                    <th style="min-width:9rem;">Amount (TZS)</th>
                    <th style="min-width:9rem;">Approval Status</th>
                    <th style="min-width:8rem;">Action</th>
                </tr>
            </thead>

            <tbody>
                @foreach ($requests as $i => $request)
                    @php
                        $rowIndex =
                            $requests instanceof \Illuminate\Pagination\LengthAwarePaginator
                                ? $requests->firstItem() + $i
                                : $i + 1;

                        $wf = $request->workflow ?? null;
                        $histories = $wf ? $wf->histories : collect();
                        $last = $histories->sortByDesc('updated_at')->first();

                        $pendingLbl = fn(?string $s) => match ($s) {
                            'Incharge Approval' => 'Pending In-Charge Approval',
                            'Platform Manager Approval' => 'Pending Platform Manager Approval',
                            'Line Manager Approval' => 'Pending Line Manager Approval',
                            'HEC Approval' => 'Pending HEC Approval',
                            'HR Approval' => 'Pending HR Approval',
                            default => 'Pending',
                        };

                        $label = 'Pending';
                        $class = 'bg-warning';
                        $tooltip = null;

                        // Check if there's a pending step FIRST (after resubmission, there may be pending steps even if old rejected entries exist)
$pendingHistory = $histories->firstWhere('status', 0);
if ($pendingHistory) {
    // Determine pending label based on step name
    $stepName = $pendingHistory->step_name ?? '';
    $label = $pendingLbl($stepName);
    $class = 'bg-warning';
} elseif ($wf && (int) $wf->work_flow_completed === 1 && (int) $wf->work_flow_status === 1) {
    // Workflow is completed AND approved (status === 1 means approved)
    $label =
        $last && $last->step_name === 'HR Approval' && (string) $last->status === '1'
            ? 'HR Approved'
            : 'Approved';
    $class = 'bg-success';
} elseif ($last && (string) $last->status === '1') {
    // Last step was approved but workflow not completed - still pending
    $stepName = $last->step_name ?? '';
    $label = match ($stepName) {
        'HR Approval' => 'Pending HR Approval',
        default => 'Pending',
    };
    $class = 'bg-warning';
} else {
    // Check for rejection (only if no pending steps)
    if ($last) {
        if ((string) $last->status === '2' || (int) ($last->locum_request_status ?? 0) === 5) {
            $label = 'Rejected';
            $class = 'bg-danger';
            if (!empty($last->rejection_reason)) {
                $tooltip = $last->rejection_reason;
            }
        } elseif ($wf && (int) $wf->work_flow_status === 2) {
            // Workflow marked as rejected
            $label = 'Rejected';
            $class = 'bg-danger';
            if ($last && (string) $last->status === '2') {
                                        $tooltip = $last->rejection_reason ?? null;
                                    }
                                }
                            }
                        }

                        $wid = $request->workflow->id;
                        $modalId = "workedDaysModal{$request->id}";
                    @endphp

                    <tr data-month="{{ $request->locum_month }}"
                        data-department="{{ $request->user->department->dept_name ?? 'N/A' }}">
                        <td><input type="checkbox" name="request_ids[]" value="{{ $wid }}"></td>
                        <td>{{ $rowIndex }}</td>
                        <td>{{ $request->user->ccbrt_code ?? 'N/A' }}</td>
                        <td>{{ trim(($request->user->fname ?? '') . ' ' . ($request->user->mname ?? '') . ' ' . ($request->user->lname ?? '')) }}
                        </td>
                        <td>{{ number_format((float) ($request->total_amount_payable ?? 0), 2, '.', ',') }}</td>
                        <td>
                            <span class="badge {{ $class }}"
                                @if ($tooltip) title="{{ $tooltip }}" @endif>{{ $label }}</span>
                        </td>

                        <td>
                            <button type="button"
                                class="btn btn-sm btn-outline-primary d-flex align-items-center gap-1 view-worked-days"
                                data-bs-toggle="modal" data-bs-target="#{{ $modalId }}"
                                data-id="{{ $request->id }}" title="View Details & BioTime">
                                <i class="fas fa-calendar-day"></i><span>View</span>
                            </button>

                            {{-- ==== MODAL (reduced width) ==== --}}
                            <div class="modal fade" id="{{ $modalId }}" tabindex="-1"
                                aria-labelledby="{{ $modalId }}Label" aria-hidden="true">
                                <div class="modal-dialog modal-xl"> {{-- was modal-xxl --}}
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title" id="{{ $modalId }}Label">
                                                Locum for {{ $request->locum_month }}
                                                {{ \Carbon\Carbon::parse($request->created_at)->format('Y') }}
                                            </h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"
                                                aria-label="Close"></button>
                                        </div>

                                        <div class="modal-body">
                                            {{-- Header info --}}
                                            <div class="row g-3 mb-3">
                                                <div class="col-md-6">
                                                    <div class="mini-kv"><span class="k"><strong>Staff
                                                                Name:</strong></span>
                                                        {{ trim(($request->user->fname ?? '') . ' ' . ($request->user->mname ?? '') . ' ' . ($request->user->lname ?? '')) ?: '—' }}
                                                    </div>
                                                    <div class="mini-kv"><span class="k"><strong>CCBRT
                                                                Code:</strong></span>
                                                        <span id="ccbrt-code-{{ $request->id }}" class="text-muted">
                                                            {{ $request->user->ccbrt_code ?? '—' }}
                                                        </span>
                                                    </div>
                                                    <div class="mini-kv"><span class="k"><strong>Locum
                                                                Month:</strong></span>
                                                        <span id="locum-month-{{ $request->id }}">
                                                            {{ $request->locum_month }}
                                                            {{ \Carbon\Carbon::parse($request->created_at)->year }}
                                                        </span>
                                                    </div>
                                                    <div class="mini-kv"><span class="k"><strong>Locum Rate
                                                                (TZS)
                                                                :</strong></span>
                                                        <span id="locum-rate-{{ $request->id }}"
                                                            data-rate="{{ (float) ($request->locumAgreement->locum_rate ?? 0) }}">
                                                            {{ number_format($request->locumAgreement->locum_rate ?? 0, 2, '.', ',') }}
                                                        </span>
                                                    </div>
                                                </div>
                                            </div>

                                            {{-- Department and Education Level Table --}}
                                            <div class="mb-3">
                                                <table class="table table-bordered table-sm align-middle mb-0"
                                                    style="background-color: #fff;">
                                                    <thead style="background-color: #f8f9fa;">
                                                        <tr>
                                                            <th
                                                                style="font-weight: 600; padding: 10px; border: 1px solid #dee2e6;">
                                                                Department</th>
                                                            <th
                                                                style="font-weight: 600; padding: 10px; border: 1px solid #dee2e6;">
                                                                Education Level</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <tr>
                                                            <td style="padding: 10px; border: 1px solid #dee2e6; background-color: #f8f9fa;"
                                                                id="department-name-{{ $request->id }}">
                                                                {{ $request->user->department->dept_name ?? '—' }}
                                                            </td>
                                                            <td style="padding: 10px; border: 1px solid #dee2e6; background-color: #f8f9fa;"
                                                                id="education-level-{{ $request->id }}"
                                                                data-edu-multiplier="{{ (float) ($request->locumAgreement->education_multiplier ?? 1) }}">
                                                                {{ $request->locumAgreement->education_level ?? '—' }}
                                                            </td>
                                                        </tr>
                                                    </tbody>
                                                </table>
                                            </div>

                                            @if ($request->description)
                                                <hr class="my-3">
                                                <p class="mb-2">
                                                    <strong>Description:</strong><br>{!! nl2br(e($request->description)) !!}
                                                </p>
                                            @endif

                                            {{-- ========== BioTime mini summary ========== --}}
                                            <div class="alert alert-secondary mt-3" role="alert">
                                                <div class="fw-semibold mb-2">BioTime Summary</div>

                                                <div class="mini-kv">
                                                    <span class="k">Worked Days (BioTime):</span>
                                                    <span id="biotime-worked-days-{{ $request->id }}">—</span>
                                                </div>

                                                <div class="row g-2 mb-2">
                                                    <div class="col-md-6">
                                                        <div class="mini-kv">
                                                            <span class="k">Claimed Hours (Form):</span>
                                                            <span id="claimed-total-hours-{{ $request->id }}">
                                                                {{ number_format((float) ($request->total_hours ?? 0), 2) }}
                                                            </span>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <div class="mini-kv text-md-end">
                                                            <span class="k">Claimed Days (Form):</span>
                                                            <span id="claimed-days-{{ $request->id }}">
                                                                {{ (int) ($request->number_of_days ?? 0) }}
                                                            </span>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="row g-2">
                                                    <div class="col-md-6">
                                                        <div class="mini-kv">
                                                            <span class="k">Total Hours (BioTime):</span>
                                                            <span id="bioTime-total-hours-{{ $request->id }}">—</span>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <div class="mini-kv text-md-end">
                                                            <span class="k">Claimed Amount:</span>
                                                            <span class="fw-bold">
                                                                {{ number_format((float) ($request->total_amount_payable ?? 0), 2, '.', ',') }}
                                                            </span>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            {{-- ========== /BioTime mini summary ========== --}}

                                            {{-- ===== Approval History ===== --}}
                                            <div class="mt-3">
                                                <div class="d-flex align-items-center justify-content-between">
                                                    <div class="fw-semibold">Approval History</div>
                                                    <small class="text-muted">From first step to last</small>
                                                </div>

                                                <div class="table-responsive mt-2">
                                                    <table class="table table-sm table-bordered align-middle">
                                                        <thead class="table-light">
                                                            <tr>
                                                                <th style="width: 12rem;">When</th>
                                                                <th style="width: 12rem;">Step</th>
                                                                <th style="width: 16rem;">Sender</th>
                                                                <th style="width: 16rem;">Approver (Assigned)</th>
                                                                <th style="width: 12rem;">Action</th>
                                                                <th style="width: 10rem;">Status</th>
                                                                <th>Remark / Reason</th>
                                                                <th style="width: 16rem;">Approved By</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            @php
                                                                $hItems =
                                                                    optional($request->workflow)->histories ??
                                                                    collect();
                                                                $hItems = $hItems->sortBy('created_at');
                                                            @endphp

                                                            @forelse ($hItems as $h)
                                                                @php
                                                                    $senderName = $h->forwardedBy?->display_name ?? '—';
                                                                    $assigneeName =
                                                                        $h->attendedBy?->display_name ?? '—';
                                                                    $approvedBy = $h->approver?->display_name ?? '—';

                                                                    $statusInt = (int) ($h->status ?? 0);
                                                                    $statusBadge = match ($statusInt) {
                                                                        1
                                                                            => '<span class="badge bg-success">Approved</span>',
                                                                        2
                                                                            => '<span class="badge bg-danger">Rejected</span>',
                                                                        default
                                                                            => '<span class="badge bg-warning text-dark">Pending</span>',
                                                                    };

                                                                    $actionTxt =
                                                                        $h->action_taken ?:
                                                                        ($statusInt === 1
                                                                            ? 'Approved'
                                                                            : ($statusInt === 2
                                                                                ? 'Rejected'
                                                                                : 'Forwarded'));

                                                                    $remark = $h->remark ?: '';
                                                                    if (
                                                                        $statusInt === 2 &&
                                                                        !empty($h->rejection_reason)
                                                                    ) {
                                                                        $remark =
                                                                            ($remark ? $remark . ' — ' : '') .
                                                                            'Reason: ' .
                                                                            $h->rejection_reason;
                                                                    }

                                                                    $when = $h->created_at
                                                                        ? \Carbon\Carbon::parse($h->created_at)->format(
                                                                            'd M Y, H:i',
                                                                        )
                                                                        : ($h->attend_date
                                                                            ? \Carbon\Carbon::parse(
                                                                                $h->attend_date,
                                                                            )->format('d M Y, H:i')
                                                                            : '—');
                                                                @endphp

                                                                <tr>
                                                                    <td>{!! $when !!}</td>
                                                                    <td>{{ $h->step_name ?? '—' }}</td>
                                                                    <td>{{ $senderName }}</td>
                                                                    <td>{{ $assigneeName }}</td>
                                                                    <td>{{ $actionTxt }}</td>
                                                                    <td>{!! $statusBadge !!}</td>
                                                                    <td>{!! $remark ? e($remark) : '—' !!}</td>
                                                                    <td>{{ $approvedBy }}</td>
                                                                </tr>
                                                            @empty
                                                                <tr>
                                                                    <td colspan="8" class="text-muted">No approval
                                                                        history recorded yet.</td>
                                                                </tr>
                                                            @endforelse
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>
                                            {{-- ===== /Approval History ===== --}}

                                            {{-- ONE DETAILS TABLE (Date roll-up with per-shift rows + daily total) --}}
                                            <div class="mt-4">
                                                <div class="fw-semibold mb-2">Daily Summary</div>
                                                <div class="table-responsive">
                                                    <table class="table table-bordered table-sm">
                                                        <thead>
                                                            <tr>
                                                                <th style="width:12rem">Date</th>
                                                                <th style="width:7rem">Worked</th>
                                                                <th style="width:16rem">Hours (Claimed)</th>
                                                                <th>Location / Unit / In-Charge</th>
                                                                <th style="width:15rem">Biotime</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody id="day-rollup-body-{{ $request->id }}">
                                                            {{-- Filled by _scripts --}}
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>

                                        </div> {{-- /modal-body --}}

                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary"
                                                data-bs-dismiss="modal">Close</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            {{-- ==== /MODAL ==== --}}

                            {{-- Approve Modal --}}
                            <div class="modal fade" id="approveModal{{ $wid }}" tabindex="-1"
                                aria-labelledby="approveModalLabel{{ $wid }}" aria-hidden="true">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title" id="approveModalLabel{{ $wid }}">Confirm
                                                Approval</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"
                                                aria-label="Close"></button>
                                        </div>
                                        <div class="modal-body">
                                            Approve the locum request for {{ $request->locum_month }}
                                            {{ \Carbon\Carbon::parse($request->created_at)->format('Y') }}
                                            by {{ $request->user->fname ?? '' }} {{ $request->user->lname ?? '' }}?
                                        </div>
                                        <div class="modal-footer">
                                            <form action="{{ route('locum-requests.approve', $wid) }}" method="POST"
                                                class="approve-form" novalidate>
                                                @csrf
                                                <input type="hidden" name="action" value="approve">
                                                <button type="button" class="btn btn-secondary"
                                                    data-bs-dismiss="modal">Cancel</button>
                                                <button type="submit" class="btn btn-success js-submit-btn"
                                                    data-loading-label="Approving...">
                                                    <span class="btn-text"><i class="fas fa-check-circle me-1"></i>
                                                        Approve</span>
                                                    <span
                                                        class="spinner-border spinner-border-sm ms-2 d-none js-spinner"
                                                        role="status" aria-hidden="true"></span>
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- Reject Modal --}}
                            <div class="modal fade" id="rejectModal{{ $wid }}" tabindex="-1"
                                aria-labelledby="rejectModalLabel{{ $wid }}" aria-hidden="true">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title" id="rejectModalLabel{{ $wid }}">Reject
                                                Request</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"
                                                aria-label="Close"></button>
                                        </div>
                                        <form action="{{ route('locum-requests.approve', $wid) }}" method="POST"
                                            class="reject-form" novalidate>
                                            @csrf
                                            <div class="modal-body">
                                                <div class="mb-3">
                                                    <label for="rejection_reason_{{ $wid }}"
                                                        class="form-label">Reason for Rejection</label>
                                                    <textarea class="form-control" id="rejection_reason_{{ $wid }}" name="rejection_reason" rows="4"
                                                        required></textarea>
                                                    @error('rejection_reason')
                                                        <div class="text-danger">{{ $message }}</div>
                                                    @enderror
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <input type="hidden" name="action" value="reject">
                                                <button type="button" class="btn btn-secondary"
                                                    data-bs-dismiss="modal">Cancel</button>
                                                <button type="submit" class="btn btn-danger js-submit-btn"
                                                    data-loading-label="Rejecting...">
                                                    <span class="btn-text"><i class="fas fa-times-circle me-1"></i>
                                                        Reject</span>
                                                    <span
                                                        class="spinner-border spinner-border-sm ms-2 d-none js-spinner"
                                                        role="status" aria-hidden="true"></span>
                                                </button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>

                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        {{-- Bulk actions --}}
        <div class="mb-3 d-flex align-items-center gap-3">
            <div class="selection-counter" id="selection-counter" style="display: none;">
                <span class="badge bg-info">
                    <span id="selected-count">0</span> selected
                </span>
            </div>
            <button type="submit" class="btn btn-success btn-sm me-2 js-submit-btn" name="action" value="approve"
                data-loading-label="Approving...">
                <span class="btn-text"><i class="fas fa-check-circle me-1"></i> Approve Selected</span>
                <span class="spinner-border spinner-border-sm ms-2 d-none js-spinner" role="status"
                    aria-hidden="true"></span>
            </button>
            <button type="button" class="btn btn-danger btn-sm" id="reject-btn">
                <i class="fas fa-times-circle me-1"></i> Reject Selected
            </button>
        </div>

        {{-- Bulk Reject Modal - Removed: Now using SweetAlert for better UX with loading animation --}}

    </form>
@endif
