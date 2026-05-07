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
                        <h3 class="page-title">Fixed Flex Contract Details</h3>
                        <a href="{{ route('contracts.fixed_flex.index') }}" class="btn btn-secondary">Back to List</a>
                    </div>
                    <div class="card">
                        <div class="card-body">
                            <h4>Contract Information</h4>
                            <table class="table table-bordered">
                                <tr>
                                    <th>Employee Name</th>
                                    <td>{{ $contract->name }}</td>
                                </tr>
                                <tr>
                                    <th>Job Title</th>
                                    <td>{{ $contract->job_title ?? 'N/A' }}</td>
                                </tr>
                                <tr>
                                    <th>Duty Station</th>
                                    <td>{{ $contract->duty_station ?? 'N/A' }}</td>
                                </tr>
                                <tr>
                                    <th>Contract Duration</th>
                                    <td>{{ $contract->duration ?? 'N/A' }}</td>
                                </tr>
                                <tr>
                                    <th>Fixed Salary (TZS)</th>
                                    <td>{{ number_format($contract->salary_fixed ?? 0) }}</td>
                                </tr>
                                <tr>
                                    <th>Flexible Daily Rate (TZS)</th>
                                    <td>{{ number_format($contract->salary_flexible ?? 0) }}</td>
                                </tr>
                            </table>
                            <div class="mt-3">
                                <a href="{{ route('contracts.fixed_flex.edit', $contract->id) }}"
                                    class="btn btn-warning">Edit Contract</a>
                                <form action="{{ route('contracts.fixed_flex.destroy', $contract->id) }}" method="POST"
                                    style="display:inline;">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-danger"
                                        onclick="return confirm('Are you sure?')">Delete Contract</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
