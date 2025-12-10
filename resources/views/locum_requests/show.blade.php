@extends('layouts.template')

@section('breadcrumb')
    @include('sweetalert::alert')
@endsection

@section('content')
    <div class="page-wrapper">
        <div class="content container-fluid">
            <div class="page-header">
                <div class="row">
                    <div class="col-sm-6">
                        <h3 class="page-title">Locum Request Details</h3>
                    </div>
                    <div class="col-sm-6 d-flex justify-content-end">
                        <a href="{{ route('locum-requests.index') }}" class="btn btn-secondary mt-2">
                            <i class="fas fa-arrow-left"></i> Back to List
                        </a>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-body">
                            <!-- Alerts -->
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

                            <!-- Employee Details -->
                            <h4 class="card-title">Employee Details</h4>
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>Employee Name</label>
                                        <input type="text" class="form-control"
                                            value="{{ auth()->user()->full_name ?? 'N/A' }}" readonly>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>CCBRT Code</label>
                                        <input type="text" class="form-control"
                                            value="{{ auth()->user()->ccbrt_code ?? 'N/A' }}" readonly>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>Department</label>
                                        <input type="text" class="form-control"
                                            value="{{ auth()->user()->department ? auth()->user()->department->dept_name : 'N/A' }}"
                                            readonly>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>Education Level</label>
                                        <input type="text" class="form-control"
                                            value="{{ $agreement->education_level ?? 'N/A' }}" readonly>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>Locum Rate</label>
                                        <input type="text" class="form-control"
                                            value="TZS {{ $agreement->locum_rate ? number_format($agreement->locum_rate, 2, '.', ',') : 'N/A' }}"
                                            readonly>
                                    </div>
                                </div>
                            </div>

                            <!-- Request Details -->
                            <h4 class="card-title mt-4">Request Details</h4>
                            <div class="table-responsive">
                                <table class="table table-bordered">
                                    <tr>
                                        <th>Locum Month</th>
                                        <td>{{ $request->locum_month }}</td>
                                    </tr>
                                    {{-- <tr>
                                        <th>Locum Year</th>
                                        <td>{{ $request->locum_year }}</td>
                                    </tr> --}}
                                    <tr>
                                        <th>Number of Days</th>
                                        <td>{{ $request->number_of_days }}</td>
                                    </tr>
                                    <tr>
                                        <th>Total Hours</th>
                                        <td>{{ number_format($request->total_hours, 2) }}</td>
                                    </tr>
                                    <tr>
                                        <th>Total Amount Payable</th>
                                        <td>TZS {{ number_format($request->total_amount_payable, 2, '.', ',') }}</td>
                                    </tr>
                                    <tr>
                                        <th>Status</th>
                                        <td>
                                            <span
                                                class="badge bg-{{ $request->status == 'approved' ? 'success' : ($request->status == 'pending' ? 'warning' : 'danger') }}">
                                                {{ ucfirst($request->status) }}
                                            </span>
                                        </td>
                                    </tr>
                                </table>
                            </div>

                            <!-- Worked Days -->
                            {{-- <h4 class="card-title mt-4">Worked Days</h4>
                            @php
                                $workedDays = json_decode($request->worked_days, true) ?? [];
                                $workedDetails = collect($workedDays)
                                    ->filter(function ($data, $date) {
                                        return isset($data['worked']) && $data['worked'] == 1;
                                    })
                                    ->map(function ($data, $date) {
                                        return ['date' => $date, 'hours' => $data['hours']];
                                    });
                            @endphp --}}
                            {{-- @if ($workedDetails->isEmpty())
                                <p>No days worked recorded.</p>
                            @else --}}
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        {{-- <tr>
                                            <th>Date</th>
                                            <th>Hours</th>
                                        </tr> --}}
                                    </thead>
                                    <tbody>
                                        {{-- @foreach ($workedDetails as $detail)
                                            <tr>
                                                <td>{{ $detail['date'] }}</td>
                                                <td>{{ number_format($detail['hours'], 2) }}</td>
                                            </tr>
                                        @endforeach --}}
                                    </tbody>
                                </table>
                            </div>
                            {{-- @endif --}}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
