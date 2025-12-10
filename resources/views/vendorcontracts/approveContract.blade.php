@extends('layouts.template')

@section('breadcrumb')
    @include('sweetalert::alert')
@endsection

@section('content')
<div class="page-wrapper">
    <div class="container-fluid">

        <!-- Page Header -->
        <div class="mb-4">
            <h2 class="fw-bold">Contract Approval Matrix</h2>
            <p class="text-muted">Review and approve contract requests from all departments</p>
        </div>

        <!-- Approval Table Card -->
        <div class="card shadow-sm mb-4">
            <!-- Card Header -->
            <div class="card-header bg-primary text-white py-3">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Contracts Pending Approval</h5>
                    <a href="{{ route('contracts.index') }}" class="btn btn-light btn-sm">View All</a>
                </div>
            </div>

            <div class="card-body p-4">

                <!-- Filters & Search -->
                <div class="row mb-3">
                    <div class="col-md-6 mb-2">
                        <input type="text" class="form-control" placeholder="Search contracts..." id="contractSearch">
                    </div>
                    <div class="col-md-3 mb-2">
                        <select class="form-select" id="perPageSelect">
                            <option value="10" selected>Show 10</option>
                            <option value="30">Show 30</option>
                            <option value="50">Show 50</option>
                        </select>
                    </div>
                </div>

                <!-- Contracts Table -->
                <div class="table-responsive">
                    <table class="table table-striped table-hover align-middle">
                        <thead style="background-color: #d4edda;"> <!-- Light green -->
                            <tr>
                                <th>#</th>
                                <th>Contract Name</th>
                                <th>Contract Type</th>
                                <th>Requester</th>
                                <th>Status</th>
                                <th>Submitted On</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="contractsTable">
                            @forelse($contracts->take(10) as $index => $contract)
                                <tr>
                                    <td>{{ $index + 1 }}</td>
                                    <td>{{ $contract->title }}</td>
                                    <td>{{ $contract->contract_type}}</td>
                                    <td>{{ trim(($contract->creator->fname ?? 'N/A') . ' ' . ($contract->creator->lname ?? '')) }}</td>
                                    <td>
                                        <span class="badge bg-success">
                                           {{ $contract->status ?? 'No Status' }}
                                        </span>
                                   </td>
                                    <td>{{ $contract->created_at->format('d M Y') }}</td>
                                    <td>
                                        <a href="{{ route('contracts.show', $contract->id) }}" class="btn btn-sm btn-info">View</a>
                                        
                                            <a href="{{ route('vendorContract.approveContract', $contract->id) }}" class="btn btn-sm btn-success">Approve</a>
                                            <a href="{{ route('vendorContract.rejectContract', $contract->id) }}" class="btn btn-sm btn-danger">Reject</a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center text-muted">No contracts available</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

            </div>
        </div>

    </div>
</div>

<!-- Optional JS for search & per-page filter -->
<script>
    const searchInput = document.getElementById('contractSearch');
    const tableBody = document.getElementById('contractsTable');
    const perPageSelect = document.getElementById('perPageSelect');

    searchInput.addEventListener('keyup', function() {
        const query = this.value.toLowerCase();
        Array.from(tableBody.getElementsByTagName('tr')).forEach(row => {
            row.style.display = row.innerText.toLowerCase().includes(query) ? '' : 'none';
        });
    });

    perPageSelect.addEventListener('change', function() {
        const limit = parseInt(this.value);
        Array.from(tableBody.getElementsByTagName('tr')).forEach((row, index) => {
            row.style.display = index < limit ? '' : 'none';
        });
    });
</script>
@endsection
