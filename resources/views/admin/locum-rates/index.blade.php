@extends('layouts.template')

@section('breadcrumb')
    @include('sweetalert::alert')
@endsection

@section('content')
    <div class="page-wrapper">
        <div class="content container-fluid">
            <div class="page-header">
                <div class="row">
                    <div class="col-sm-12 d-flex justify-content-between align-items-center">
                        <div class="page-sub-header">
                            <h3 class="page-title mb-0">Manage Locum Rates</h3>
                        </div>
                        <div class="d-flex gap-2">
                            <a href="{{ route('locum-rates.create') }}" class="btn btn-primary btn-sm">
                                <i class="fas fa-plus me-1"></i> Add Locum Rate
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Agreement statistics and Expire All --}}
            <div class="row mb-3">
                <div class="col-md-12">
                    <div class="card border border-secondary border-opacity-25 shadow-sm">
                        <div class="card-header bg-light border-bottom border-secondary border-opacity-25 py-2">
                            <h5 class="mb-0 text-dark fw-normal">
                                <i class="fas fa-file-contract me-2 text-secondary"></i>Locum Agreement Management
                            </h5>
                        </div>
                        <div class="card-body py-3">
                            <div class="row g-2 g-md-3 align-items-center">
                                <div class="col-6 col-md">
                                    <div class="text-center p-2 rounded bg-light">
                                        <h4 class="mb-0 text-dark">{{ $totalAgreements ?? 0 }}</h4>
                                        <small class="text-muted">Total</small>
                                    </div>
                                </div>
                                <div class="col-6 col-md">
                                    <div class="text-center p-2 rounded" style="background-color: #e8f5e9;">
                                        <h4 class="mb-0" style="color: #2e7d32;">{{ $activeAgreements ?? 0 }}</h4>
                                        <small class="text-muted">Active</small>
                                    </div>
                                </div>
                                <div class="col-6 col-md">
                                    <div class="text-center p-2 rounded" style="background-color: #e3f2fd;">
                                        <h4 class="mb-0" style="color: #1565c0;">{{ $validForClaimingAgreements ?? 0 }}
                                        </h4>
                                        <small class="text-muted">Valid for claiming
                                            @if (!empty($validUntilDateFormatted))
                                                (until {{ $validUntilDateFormatted }})
                                            @else
                                                ({{ $graceDays }} days)
                                            @endif
                                        </small>
                                    </div>
                                </div>
                                <div class="col-6 col-md">
                                    <div class="text-center p-2 rounded bg-light">
                                        <h4 class="mb-0 text-secondary">{{ $expiredAgreements ?? 0 }}</h4>
                                        <small class="text-muted">Expired</small>
                                    </div>
                                </div>
                                <div class="col-6 col-md">
                                    <button type="button" class="btn btn-outline-secondary btn-sm w-100"
                                        data-bs-toggle="modal" data-bs-target="#expireAllAgreementsModal">
                                        <i class="fas fa-calendar-times me-1"></i> Expire All
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            @if (session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            @if ($errors->any())
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <ul class="mb-0">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            <div class="card shadow-sm">
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="locumRatesTable" class="table table-hover table-striped align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    <th>Education Level</th>
                                    <th>Year</th>
                                    <th>Rate (TZS)</th>
                                    <th>Period</th>
                                    <th>Status</th>
                                    <th>Notes</th>
                                    <th class="text-center">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($rates as $index => $rate)
                                    @php
                                        $start = $rate->start_date ? $rate->start_date->copy()->startOfDay() : null;
                                        $end = $rate->end_date ? $rate->end_date->copy()->endOfDay() : null;
                                        $isCurrent = $start && $end && $today->between($start, $end);
                                        $isPrevious = $end && $today->gt($end);
                                        $isUpcoming = $start && $today->lt($start);
                                        $periodStatus = $isCurrent
                                            ? 'current'
                                            : ($isPrevious
                                                ? 'previous'
                                                : ($isUpcoming
                                                    ? 'upcoming'
                                                    : 'other'));
                                    @endphp
                                    <tr
                                        class="{{ $periodStatus === 'current' ? 'table-success' : ($periodStatus === 'previous' ? 'table-light' : '') }}">
                                        <td>{{ $index + 1 }}</td>
                                        <td>{{ $rate->education_level }}</td>
                                        <td>{{ $rate->start_date ? $rate->start_date->format('Y') : '—' }}</td>
                                        <td>TZS {{ number_format($rate->rate, 0) }}</td>
                                        <td>
                                            @if ($periodStatus === 'current')
                                                <span class="badge bg-success">Current</span>
                                            @elseif ($periodStatus === 'previous')
                                                <span class="badge bg-secondary">Previous</span>
                                            @elseif ($periodStatus === 'upcoming')
                                                <span class="badge bg-info">Upcoming</span>
                                            @else
                                                <span class="badge bg-light text-dark">—</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if ($rate->is_active)
                                                <span class="badge bg-success">Active</span>
                                            @else
                                                <span class="badge bg-secondary">Not active</span>
                                            @endif
                                        </td>
                                        <td>{{ $rate->notes }}</td>
                                        <td class="text-center">
                                            <a href="{{ route('locum-rates.edit', $rate->id) }}"
                                                class="btn btn-sm btn-outline-primary">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            @if (auth()->user()->hasAnyRole(['Admin', 'Super-Admin', 'super-admin']))
                                                <form action="{{ route('locum-rates.destroy', $rate->id) }}" method="POST"
                                                    class="d-inline"
                                                    onsubmit="return confirm('Are you sure you want to delete this locum rate?');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-outline-danger">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
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

    {{-- Modal: Set valid-until date then expire all agreements --}}
    <div class="modal fade" id="expireAllAgreementsModal" tabindex="-1" aria-labelledby="expireAllAgreementsModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form action="{{ route('locum-rates.expire-all') }}" method="POST" id="expireAllAgreementsForm">
                    @csrf
                    <div class="modal-header bg-light border-secondary border-opacity-25">
                        <h5 class="modal-title" id="expireAllAgreementsModalLabel">
                            <i class="fas fa-calendar-times me-2 text-secondary"></i>Expire All Locum Agreements
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <p class="text-muted mb-3">
                            Contracts expired on <strong>29 January</strong> but are <strong>valid to use until</strong> the
                            date you choose below. After that date users must create new agreements.
                        </p>
                        <div class="mb-3">
                            <label for="valid_until" class="form-label">Valid to use until <span
                                    class="text-danger">*</span></label>
                            <input type="date" name="valid_until" id="valid_until"
                                class="form-control @error('valid_until') is-invalid @enderror"
                                value="{{ old('valid_until', date('Y-m-d', strtotime('+10 days'))) }}" required
                                min="{{ date('Y-m-d') }}">
                            @error('valid_until')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="form-text text-muted">Choose the date until which users may continue using their
                                expired agreement for claims.</small>
                        </div>
                    </div>
                    <div class="modal-footer border-secondary border-opacity-25">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-outline-secondary">
                            <i class="fas fa-calendar-times me-1"></i> Set end date & expire after
                        </button>
                    </div>
                </form>
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
            $(document).ready(function() {
                @if ($errors->has('valid_until'))
                    var modal = new bootstrap.Modal(document.getElementById('expireAllAgreementsModal'));
                    modal.show();
                @endif
                $('#locumRatesTable').DataTable({
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
                        [1, 'asc']
                    ],
                    columnDefs: [{
                        targets: -1,
                        orderable: false
                    }]
                });
            });
        </script>
    @endpush
@endsection
