@extends('layouts.template')

@section('content')
    @include('sweetalert::alert')

    <style>
        .doc-preview {
            border: 1px solid #e5e7eb;
            border-radius: .5rem;
            overflow: hidden;
            background: #fff;
        }

        .doc-preview .doc-embed {
            width: 100%;
            height: 75vh;
            display: block;
            border: 0;
        }

        .doc-preview .doc-image {
            max-width: 100%;
            height: auto;
            display: block;
            margin: 0 auto;
        }

        @media (max-width: 768px) {
            .doc-preview .doc-embed {
                height: 60vh;
            }
        }

        .badge-wrap {
            word-break: break-word;
        }
    </style>

    <div class="page-wrapper">
        <div class="content container-fluid">
            {{-- Header --}}
            <div class="page-header d-flex justify-content-between align-items-center mb-3">
                <h3 class="page-title mb-0">On-Call Request Details</h3>
                <div>
                    @if (auth()->id() === $request->user_id && $request->status === 'rejected')
                        <a href="{{ route('oncall_requests.edit', $request->id) }}" class="btn btn-warning btn-sm">
                            <i class="fas fa-edit me-1"></i> Update & Resubmit
                        </a>
                    @endif
                </div>
            </div>

            {{-- Main Card --}}
            <div class="card card-body shadow-sm">
                <div class="row g-3">
                    {{-- Contact / Employee Info --}}
                    <div class="col-md-6">
                        <h5 class="text-primary mb-2">Employee / Contact</h5>
                        <p><strong>Username:</strong> {{ $request->user->username ?? '—' }}</p>
                        <p><strong>Employee Code:</strong> {{ $request->user->ccbrt_code ?? '—' }}</p>
                        <p><strong>Description / Note:</strong>
                            <span
                                class="badge bg-warning text-dark badge-wrap">{{ $request->description ?? 'Routine' }}</span>
                        </p>
                        <p><strong>Education Level:</strong> {{ $request->education_level ?? '—' }}</p>
                    </div>

                    {{-- Request Info --}}
                    <div class="col-md-6">
                        <h5 class="text-primary mb-2">Request Details</h5>
                        <p><strong>Claim Period:</strong> {{ $request->locum_month }} {{ $request->locum_year }}</p>
                        <p><strong>Days Selected:</strong> {{ $request->number_of_days ?? 0 }}</p>
                        <p><strong>Total Hours:</strong> {{ number_format($request->total_hours ?? 0, 2) }}</p>
                        <p><strong>Total Amount (TZS):</strong> {{ number_format($request->total_amount_payable ?? 0, 2) }}
                        </p>
                    </div>
                </div>

                {{-- Support Document --}}
                @php
                    $docPath = $request->special_task_path ?? null;
                    $docUrl = $docPath ? asset('storage/' . ltrim($docPath, '/')) : null;
                    $docName = $request->special_task_original_name ?? ($docPath ? basename($docPath) : null);
                    $docExt = $docName ? strtolower(pathinfo($docName, PATHINFO_EXTENSION)) : null;
                    $collapseId = 'specialDocPreview-' . $request->id;
                @endphp

                @if ($request->has_special_task && $docUrl)
                    <div class="mt-3 p-3 border rounded bg-light">
                        <h6>Support Document</h6>
                        <div class="d-flex flex-wrap align-items-center gap-2">
                            <div class="me-auto">
                                <i class="fas fa-paperclip me-1"></i> {{ $docName }}
                            </div>
                            <button class="btn btn-sm btn-outline-secondary" type="button" data-bs-toggle="collapse"
                                data-bs-target="#{{ $collapseId }}">
                                Preview
                            </button>
                            <a href="{{ $docUrl }}" target="_blank" class="btn btn-sm btn-outline-primary">Open</a>
                        </div>
                        <div class="collapse mt-3" id="{{ $collapseId }}">
                            <div class="doc-preview card card-body p-0">
                                @if ($docExt === 'pdf')
                                    <embed class="doc-embed" src="{{ $docUrl }}" type="application/pdf">
                                @elseif(in_array($docExt, ['jpg', 'jpeg', 'png']))
                                    <img class="doc-image" src="{{ $docUrl }}" alt="Support Document">
                                @else
                                    <div class="p-3 small text-muted">Preview not available for
                                        <code>.{{ $docExt }}</code>.
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                @else
                    <p class="mt-3"><strong>Routine:</strong> <span class="badge bg-secondary">Yes</span></p>
                @endif

                <hr>

                {{-- Approval / Visits History --}}
                <h5 class="text-primary mb-2">Approval / Visits History</h5>
                <div class="table-responsive">
                    <table class="table table-bordered table-hover align-middle text-center mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>#</th>
                                <th>Step / Visit</th>
                                <th>Performed By / Host</th>
                                <th>Remarks</th>
                                <th>Status</th>
                                {{-- <th>Level</th> --}}
                            </tr>
                        </thead>
                        <tbody>
                            @php $stepNumber = 1; @endphp
                            @forelse ($request->workflow->histories->sortBy('created_at') as $h)
                                <tr>
                                    <td>{{ $stepNumber++ }}</td>
                                    <td>{{ $h->step_name }}</td>
                                    <td>{{ trim("{$h->attendedBy?->fname} {$h->attendedBy?->mname} {$h->attendedBy?->lname}") ?? '—' }}
                                    </td>
                                    <td>
                                        @if ($h->rejection_reason || $h->remark)
                                            <span role="button" data-bs-toggle="modal"
                                                data-bs-target="#remarkModal{{ $h->id }}">
                                                <i class="fas fa-eye text-info"></i>
                                            </span>




                                            {{-- Scrollable Modal --}}
                                            <div class="modal fade" id="remarkModal{{ $h->id }}" tabindex="-1"
                                                aria-hidden="true">
                                                <div class="modal-dialog modal-dialog-scrollable">
                                                    <div class="modal-content">
                                                        <div class="modal-header">
                                                            <h5 class="modal-title">Remark</h5>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal"
                                                                aria-label="Close"></button>
                                                        </div>
                                                        <div class="modal-body" style="white-space: pre-wrap;">
                                                            {!! nl2br(e($h->rejection_reason ?? $h->remark)) !!}
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-secondary btn-sm"
                                                                data-bs-dismiss="modal">Close</button>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        @else
                                            —
                                        @endif
                                    </td>
                                    <td>
                                        @if ($h->status == 0)
                                            <span class="badge bg-warning text-dark">Pending</span>
                                        @elseif ($h->status == 1)
                                            <span class="badge bg-success text-dark">Approved</span>
                                        @elseif ($h->status == 2)
                                            <span class="badge bg-danger">Closed</span>
                                        @else
                                            <span class="badge bg-secondary">Unknown</span>
                                        @endif
                                    </td>
                                    {{-- <td>
                                        @if ($h->status == 0)
                                            <span class="badge bg-info text-dark">Level {{ $h->level ?? '—' }}</span>
                                        @else
                                            —
                                        @endif
                                    </td> --}}
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center">No history recorded.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection
