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
                            <h3 class="page-title mb-0">Manage On-Call Rates</h3>
                        </div>
                        <div class="d-flex gap-2 align-items-center">
                            <form method="GET" action="{{ route('oncall-rates.index') }}" class="d-inline">
                                <select name="year" id="yearFilter" class="form-select form-select-sm"
                                    onchange="this.form.submit()" style="width: auto; display: inline-block;">
                                    @foreach ($availableYears as $year)
                                        <option value="{{ $year }}" {{ $selectedYear == $year ? 'selected' : '' }}>
                                            {{ $year }}</option>
                                    @endforeach
                                </select>
                            </form>
                            <a href="{{ route('oncall-rates.create') }}?year={{ $selectedYear }}"
                                class="btn btn-primary btn-sm">
                                <i class="fas fa-plus me-1"></i> Add On-Call Rate for {{ $selectedYear }}
                            </a>
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

            <div class="card shadow-sm">
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="oncallRatesTable" class="table table-hover table-striped align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    <th>Education Level</th>
                                    <th>Rate (TZS)</th>
                                    <th>Status</th>
                                    <th>Notes</th>
                                    <th class="text-center">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($rates as $index => $rate)
                                    <tr>
                                        <td>{{ $index + 1 }}</td>
                                        <td>{{ $rate->education_level }}</td>
                                        <td>TZS {{ number_format($rate->rate, 0) }}</td>
                                        <td>
                                            @if ($rate->is_active)
                                                <span class="badge bg-success">Active until
                                                    {{ $rate->end_date ? $rate->end_date->format('d M Y') : '—' }}</span>
                                            @else
                                                <span class="badge bg-secondary">Not active</span>
                                            @endif
                                        </td>
                                        <td>{{ $rate->notes }}</td>
                                        <td class="text-center">
                                            <a href="{{ route('oncall-rates.edit', $rate->id) }}"
                                                class="btn btn-sm btn-outline-primary">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            @if (auth()->user()->hasAnyRole(['Admin', 'Super-Admin', 'super-admin']))
                                                <form action="{{ route('oncall-rates.destroy', $rate->id) }}"
                                                    method="POST" class="d-inline"
                                                    onsubmit="return confirm('Are you sure you want to delete this on-call rate?');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-outline-danger">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center text-muted py-4">
                                            <i class="fas fa-info-circle me-2"></i>
                                            No on-call rates found for {{ $selectedYear }}.
                                            <a href="{{ route('oncall-rates.create') }}?year={{ $selectedYear }}">Add
                                                rates for this year</a>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
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
            $(document).ready(function() {
                $('#oncallRatesTable').DataTable({
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
                        [1, 'asc'], // Education level ascending
                        [2, 'desc'] // Period descending
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
