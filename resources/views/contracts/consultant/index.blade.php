@extends('layouts.template')
@section('breadcrumb')
    @include('sweetalert::alert')
@endsection

@section('content')
    <div class="page-wrapper">
        <div class="content container-fluid">
            <div class="row">
                <div class="col-md-12">
                    <div class="page-sub-header">
                        <h3 class="page-title">Consultant Contracts List</h3>
                    </div>
                    <div class="card">
                        <div class="card-body">
                            <a href="{{ route('consultants.create') }}" class="btn btn-primary btn-sm mb-3">
                                Create New Contract
                            </a>

                            <div class="table-responsive">
                                <table id="contractsTable" class="table table-hover table-bordered">
                                    <thead class="table-success">
                                        <tr>
                                            <th>#</th>
                                            <th>Application Date</th>
                                            <th>Consultant Name</th>
                                            <th>Consultant Address</th>
                                            <th>Consultant Mobile</th>
                                            <th>Qualification</th>
                                            <th>Experience (Years)</th>
                                            <th>Department</th>
                                            <th>Start Date</th>
                                            <th>End Date</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($consult as $contract)
                                            <tr>
                                                <td>{{ $loop->iteration }}</td>
                                                <td>{{ $contract->application_date }}</td>
                                                <td>{{ $contract->consultant_full_name ?? 'N/A' }}</td>
                                                <td>{{ $contract->consultant_address ?? 'N/A' }}</td>
                                                <td>{{ $contract->consultant_mobile ?? 'N/A' }}</td>
                                                <td>{{ $contract->qualification ?? 'N/A' }}</td>
                                                <td>{{ $contract->year_of_experience ?? 'N/A' }}</td>
                                                <td>{{ $contract->department->dept_name ?? 'N/A' }}</td>
                                                <td>{{ $contract->startDate ?? 'N/A' }}</td>
                                                <td>{{ $contract->endDate ?? 'N/A' }}</td>
                                                <td>
                                                    <span
                                                        class="badge {{ $contract->status == 'active' ? 'bg-success' : 'bg-danger' }}">
                                                        {{ ucfirst($contract->status ?? 'N/A') }}
                                                    </span>
                                                </td>
                                                <td>
                                                    <a href="{{ route('contracts.show', $contract->id) }}"
                                                        class="btn btn-sm btn-info" title="View">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    <a href="{{ route('contracts.edit', $contract->id) }}"
                                                        class="btn btn-sm btn-warning" title="Edit">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <form action="{{ route('contracts.destroy', $contract->id) }}"
                                                        method="POST" style="display: inline;"
                                                        onsubmit="return confirm('Are you sure you want to delete this contract?')">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="btn btn-sm btn-danger" title="Delete">
                                                            <i class="fas fa-trash-alt"></i>
                                                        </button>
                                                    </form>
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
        </div>
    </div>
@endsection
