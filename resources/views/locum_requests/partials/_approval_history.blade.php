@php
    /** @var \App\Models\LocumRequest|null $requestModel */
    $req = $requestModel ?? null;
    $histories = optional($req?->workflow)->histories?->sortByDesc('created_at') ?? collect();

    // Submitter taken from locum_requests.submited_by_incharge (exact column name)
    $submitter = $req?->submittedByIncharge ?? null;
    $submitterFull = trim(
        collect([$submitter?->fname, $submitter?->mname, $submitter?->lname])
            ->filter()
            ->implode(' '),
    );

    // Check if submitter is an In-Charge
    $submitterIsIncharge = $submitter
        ? (method_exists($submitter, 'hasRole')
            ? $submitter->hasRole('incharge')
            : $submitter->roles && $submitter->roles->contains('name', 'incharge'))
        : false;

    // Only show the header if there *is* a submitter and they are incharge
    $showSubmitterHeader = $submitter && $submitterIsIncharge;
@endphp

<div class="mt-3">
    <h6 class="mb-2">Approval History</h6>

    {{-- Submitted-by header (shown only if the submitter is In-Charge) --}}
    @if ($showSubmitterHeader)
        <div class="alert alert-light border py-2 mb-2">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div>
                    <strong>Submitted by:</strong>
                    {{ $submitter->username ?? '—' }}
                    ({{ $submitterFull ?: '—' }}, {{ $submitter->ccbrt_code ?? '—' }})
                    <span class="badge bg-info text-dark ms-1">In-Charge</span>
                </div>
                <small class="text-muted">
                    {{ optional($req?->created_at)->format('d M Y H:i') ?? '—' }}
                </small>
            </div>
        </div>
    @endif

    <ul class="list-group">
        @forelse ($histories as $h)
            @php
                $toUser = $h->attendedBy ?? null;
                $fromUser = $h->forwardedBy ?? null;

                $toFull = trim(
                    collect([$toUser?->fname, $toUser?->mname, $toUser?->lname])
                        ->filter()
                        ->implode(' '),
                );
                $fromFull = trim(
                    collect([$fromUser?->fname, $fromUser?->mname, $fromUser?->lname])
                        ->filter()
                        ->implode(' '),
                );

                $fromIsIncharge = $fromUser
                    ? (method_exists($fromUser, 'hasRole')
                        ? $fromUser->hasRole('incharge')
                        : $fromUser->roles && $fromUser->roles->contains('name', 'incharge'))
                    : false;
            @endphp

            <li class="list-group-item d-flex justify-content-between align-items-center">
                <span>
                    <strong>{{ $h->step_name ?? '—' }}</strong> — {{ $h->action_taken ?? '—' }}
                    <small class="text-muted">
                        ({{ \Carbon\Carbon::parse($h->created_at ?? now())->format('d M Y H:i') }})
                    </small>

                    {{-- To (recipient) --}}
                    <br>
                    <small>
                        To: {{ $toUser?->username ?? '—' }}
                        ({{ $toFull ?: '—' }}, {{ $toUser?->ccbrt_code ?? '—' }})
                    </small>

                    {{-- From (sender) --}}
                    @if ($fromUser)
                        <br>
                        <small>
                            From: {{ $fromUser?->username ?? '—' }}
                            ({{ $fromFull ?: '—' }}, {{ $fromUser?->ccbrt_code ?? '—' }})
                            @if ($fromIsIncharge)
                                — <em>sent by In-Charge</em>
                            @endif
                        </small>
                    @endif

                    {{-- Optional note/remark --}}
                    @if ($h->rejection_reason || $h->remark || ($h->remarks ?? null))
                        <br><small>Note: {{ $h->rejection_reason ?? ($h->remark ?? ($h->remarks ?? '')) }}</small>
                    @endif
                </span>

                <span>
                    @if ($h->status == 0)
                        <span class="badge bg-warning text-dark">Pending</span>
                    @elseif ($h->status == 1)
                        <span class="badge bg-success text-dark">Approved</span>
                    @elseif ($h->status == 2)
                        <span class="badge bg-danger">Closed</span>
                    @else
                        <span class="badge bg-secondary">—</span>
                    @endif
                </span>
            </li>
        @empty
            <li class="list-group-item text-center">No history.</li>
        @endforelse
    </ul>
</div>
