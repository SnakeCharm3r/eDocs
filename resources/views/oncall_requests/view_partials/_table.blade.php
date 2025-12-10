{{-- resources/views/oncall_requests/view_partials/_table.blade.php --}}
<style>
    #oncallApproverTable input[type="checkbox"] {
        width: 18px;
        height: 18px;
        cursor: pointer;
        accent-color: #198754;
        transform: scale(1.1);
    }

    #oncallApproverTable input[type="checkbox"]:checked {
        background-color: #198754;
        border-color: #198754;
    }

    #oncallApproverTable tbody tr:hover {
        background-color: #f8f9fa;
    }

    #oncallApproverTable tbody tr:has(input[type="checkbox"]:checked) {
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
    <p class="text-muted">No pending on-call requests for your approval.</p>
@else
    <form action="{{ route('oncall_requests.bulk-approve') }}" method="POST" id="bulk-approve-form" novalidate>
        @csrf

        <table id="oncallApproverTable" class="table table-striped table-hover table-bordered align-middle">
            <thead class="table-success">
                <tr>
                    <th style="width:3rem;">
                        <input type="checkbox" class="select-all" id="select-all">
                        <label for="select-all" class="ms-1">All</label>
                    </th>
                    <th style="width:3rem;">#</th>
                    <th style="width:10rem;">CCBRT Code</th>
                    <th style="min-width:12rem;">Full Name</th>
                    <th style="min-width:8rem;">Month</th>
                    <th style="min-width:6rem;">Days</th>
                    <th style="min-width:9rem;">Amount (TZS)</th>
                    <th style="min-width:9rem;">Status</th>
                    <th style="min-width:8rem;">Action</th>
                </tr>
            </thead>

            <tbody>
                @foreach ($requests as $i => $request)
                    @php
                        $wf = $request->workflow ?? null;
                        $histories = $wf ? $wf->histories : collect();
                        $pendingHistory = $histories->firstWhere('status', 0);

                        $pendingLbl = fn(?string $s) => match ($s) {
                            'Line Manager Approval' => 'Pending Line Manager Approval',
                            'HEC Approval' => 'Pending HEC Approval',
                            'HR Approval' => 'Pending HR Approval',
                            default => 'Pending',
                        };

                        $label = 'Pending';
                        $class = 'bg-warning';
                        $tooltip = null;

                        if ($pendingHistory) {
                            $stepName = $pendingHistory->step_name ?? '';
                            $label = $pendingLbl($stepName);
                            $class = 'bg-warning';
                        } elseif ($wf && (int) $wf->work_flow_completed === 1) {
                            $label = 'Approved';
                            $class = 'bg-success';
                        }

                        $wid = $request->workflow->id ?? null;
                        $period = trim(collect([$request->locum_month ?? null, $request->locum_year ?? null])->filter()->implode(' '));
                        if ($period === '') $period = '—';
                    @endphp

                    <tr>
                        <td><input type="checkbox" name="request_ids[]" value="{{ $wid }}"></td>
                        <td>{{ $i + 1 }}</td>
                        <td>{{ $request->user->ccbrt_code ?? 'N/A' }}</td>
                        <td>{{ trim(($request->user->fname ?? '') . ' ' . ($request->user->mname ?? '') . ' ' . ($request->user->lname ?? '')) }}</td>
                        <td>{{ $period }}</td>
                        <td>{{ (int) ($request->number_of_days ?? 0) }}</td>
                        <td>{{ number_format((float) ($request->total_amount_payable ?? 0), 2) }}</td>
                        <td>
                            <span class="badge {{ $class }}" @if ($tooltip) title="{{ $tooltip }}" @endif>{{ $label }}</span>
                        </td>
                        <td>
                            <a href="{{ route('oncall_requests.show', $request->id) }}" class="btn btn-sm btn-outline-primary">
                                <i class="fas fa-eye"></i> View
                            </a>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        {{-- Bulk actions --}}
        <div class="mt-3 d-flex align-items-center gap-3">
            <div class="selection-counter" id="selection-counter" style="display: none;">
                <span class="badge bg-info">
                    <span id="selected-count">0</span> selected
                </span>
            </div>
            <button type="submit" class="btn btn-success btn-sm js-submit-btn" name="action" value="approve" data-loading-label="Approving...">
                <span class="btn-text">
                    <i class="fas fa-check-circle me-1"></i> Approve Selected
                </span>
                <span class="spinner-border spinner-border-sm ms-2 d-none js-spinner" role="status" aria-hidden="true"></span>
            </button>
            <button type="button" class="btn btn-danger btn-sm" id="reject-btn" data-loading-label="Rejecting...">
                <i class="fas fa-times-circle me-1"></i> Reject Selected
            </button>
        </div>
    </form>
@endif

