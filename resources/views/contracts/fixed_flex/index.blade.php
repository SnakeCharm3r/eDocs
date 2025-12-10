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
                        <h3 class="page-title">Fixed Flex Contracts</h3>
                        <a href="{{ route('contracts.fixed_flex.create') }}" class="btn btn-primary">Create New Contract</a>
                    </div>
                    <div class="card">
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Name</th>
                                            <th>Job Title</th>
                                            <th>Duty Station</th>
                                            <th>Contract Duration</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse ($contracts as $contract)
                                            <tr>
                                                <td>{{ $contract->name }}</td>
                                                <td>{{ $contract->job_title ?? 'N/A' }}</td>
                                                <td>{{ $contract->duty_station ?? 'N/A' }}</td>
                                                <td>{{ $contract->duration ?? 'N/A' }}</td>
                                                <td>
                                                    <a href="{{ route('contracts.fixed_flex.show', $contract->id) }}" class="btn btn-sm btn-info">View</a>
                                                    <a href="{{ route('contracts.fixed_flex.edit', $contract->id) }}" class="btn btn-sm btn-warning">Edit</a>
                                                    <form action="{{ route('contracts.fixed_flex.destroy', $contract->id) }}" method="POST" style="display:inline;">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure?')">Delete</button>
                                                    </form>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="5" class="text-center">No contracts found.</td>
                                            </tr>
                                        @endforelse
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