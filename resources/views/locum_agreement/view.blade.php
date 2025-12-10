@extends('layouts.template')

@section('breadcrumb')
    @include('sweetalert::alert')
@endsection

@section('content')
    <div class="page-wrapper">
        <div class="content container-fluid">
            <!-- Page Header -->
            <div class="page-header">
                <div class="row">
                    <div class="col-sm-12">
                        <div class="page-sub-header">
                            <h3 class="page-title">Locum Agreements</h3>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Locum Agreements Table -->
            <div class="row">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-body">
                            @if (session('success'))
                                <div class="alert alert-success alert-dismissible fade show" role="alert">
                                    {{ session('success') }}
                                    <button type="button" class="btn-close" data-bs-dismiss="alert"
                                        aria-label="Close"></button>
                                </div>
                            @endif
                            @if (session('error'))
                                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                    {{ session('error') }}
                                    <button type="button" class="btn-close" data-bs-dismiss="alert"
                                        aria-label="Close"></button>
                                </div>
                            @endif

                            <div class="table-responsive">
                                @if ($agreements->isEmpty())
                                    <p>No locum agreements found.</p>
                                @else
                                    <table class="table table-striped table-hover">
                                        <thead>
                                            <tr>
                                                <th>Staff Name</th>
                                                <th>CCBRT Code</th>
                                                <th>Start Date</th>
                                                <th>End Date</th>
                                                <th>Locum Rate</th>
                                                <th>Education Level</th>
                                                <th>Status</th>
                                                <th>Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($agreements as $index => $agreement)
                                                <tr>

                                                    <td>{{ ($agreement->user->fname ?? '') . ' ' . ($agreement->user->lname ?? '') }}
                                                    </td>
                                                    <td>{{ $agreement->user->ccbrt_code ?? 'N/A' }}</td>

                                                    <td>{{ $agreement->start_date ? \Carbon\Carbon::parse($agreement->start_date)->format('F j, Y') : 'N/A' }}
                                                    </td>
                                                    <td>{{ $agreement->end_date ? \Carbon\Carbon::parse($agreement->end_date)->format('F j, Y') : 'N/A' }}
                                                    </td>
                                                    <td>{{ $agreement->locum_rate ? 'TZS ' . number_format($agreement->locum_rate, 2) : 'N/A' }}
                                                    </td>
                                                    <td>{{ $agreement->education_level ?? ($agreement->user->education_level ?? 'N/A') }}
                                                    </td>
                                                    <td>
                                                        @php
                                                            $statusMap = [
                                                                0 => [
                                                                    'label' => 'Pending to Line Manager',
                                                                    'class' => 'bg-warning',
                                                                ],
                                                                1 => ['label' => 'Pending to HR', 'class' => 'bg-info'],
                                                                2 => ['label' => 'Approved', 'class' => 'bg-success'],
                                                                3 => [
                                                                    'label' => 'Rejected by Line Manager',
                                                                    'class' => 'bg-danger',
                                                                ],
                                                                4 => [
                                                                    'label' => 'Rejected by HR',
                                                                    'class' => 'bg-danger',
                                                                ],
                                                            ];

                                                            $statusInfo = $statusMap[$agreement->status] ?? [
                                                                'label' => 'N/A',
                                                                'class' => 'bg-secondary',
                                                            ];
                                                        @endphp

                                                        <span class="badge {{ $statusInfo['class'] }}">
                                                            {{ $statusInfo['label'] }}
                                                        </span>
                                                        @if (in_array($agreement->status, [3, 4]) && !empty($agreement->rejection_status))
                                                            <div class="mt-1  small">
                                                                <strong>Reason:</strong> {{ $agreement->rejection_status }}
                                                            </div>
                                                        @endif
                                                    </td>

                                                    <td>
                                                        <div class="d-flex gap-1">
                                                            <a href="{{ route('locum-agreements.show', $agreement->id) }}"
                                                                class="btn btn-sm btn-outline-primary" title="View">
                                                                <i class="fas fa-eye"></i>
                                                            </a>
                                                            @if (in_array((int) ($agreement->status ?? 0), [3, 4]))
                                                                @php
                                                                    $canEdit = true;
                                                                    $rejectedAt = null;
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
                                                                        $canEdit = $rejectedDate->gte($twoMonthsAgo);
                                                                    }
                                                                @endphp
                                                                @if ($canEdit)
                                                                    <a href="{{ route('locum-agreements.edit', $agreement->id) }}"
                                                                        class="btn btn-sm btn-outline-warning"
                                                                        title="Edit and resubmit">
                                                                        <i class="fas fa-edit"></i> Edit & Resubmit
                                                                    </a>
                                                                @else
                                                                    <span class="btn btn-sm btn-outline-secondary disabled"
                                                                        title="This agreement was rejected more than 2 months ago">
                                                                        <i class="fas fa-lock"></i> Expired
                                                                    </span>
                                                                @endif
                                                            @endif
                                                        </div>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="d-flex mb-3">
                <a href="{{ route('locum-requests.index') }}" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back
                </a>

            </div>
        </div>
    </div>
@endsection
