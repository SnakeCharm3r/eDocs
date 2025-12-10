@extends('layouts.template')

@section('breadcrumb')
    @include('includes.loader')
    @include('sweetalert::alert')
@endsection

@section('content')
    <div class="page-wrapper">
        <div class="content container-fluid">
            <div class="page-header">
                <div class="row">
                    <div class="col-sm-12">
                        <div class="page-sub-header">
                            <h3 class="page-title">Punch History for
                                {{ $user->name ?? 'User ID: ' . ($user->userid ?? '') }}</h3>

                            <form method="GET" class="row g-3">
                                <div class="col-md-3">
                                    <label for="year" class="form-label">Year</label>
                                    <input type="number" name="year" id="year" value="{{ $year }}"
                                        class="form-control" min="2000" max="{{ date('Y') }}">
                                </div>
                                <div class="col-md-3">
                                    <label for="month" class="form-label">Month</label>
                                    <input type="number" name="month" id="month" value="{{ $month }}"
                                        class="form-control" min="1" max="12">
                                </div>
                                <div class="col-md-3 d-flex align-items-end">
                                    <button type="submit" class="btn btn-primary">Filter</button>
                                </div>

                                <div class="col-md-3 d-flex align-items-end justify-content-end">
                                    <div class="alert alert-info py-1 px-2 mb-0">
                                        <strong>Total Month Hours:</strong>
                                        <span class="text-mono">{{ $totalMonthHours ?? '00:00' }}</span>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            @if (session('error'))
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    {{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            <div class="row">
                <div class="col-md-12">
                    <div class="card shadow-sm">
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered table-hover">
                                    <thead class="table-light">
                                        <tr>
                                            <th>#</th>
                                            <th>Date</th>
                                            <th>Punch In</th>
                                            <th>Punch Out</th>
                                            <th>Total Hours</th>
                                            <th>Overtime Hours</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse ($records as $index => $record)
                                            <tr>
                                                <td>{{ $index + 1 }}</td>
                                                <td>{{ $record->punch_date }}</td>
                                                <td class="text-mono">{{ $record->punch_in ?? 'N/A' }}</td>
                                                <td class="text-mono">{{ $record->punch_out ?? 'N/A' }}</td>
                                                <td class="text-mono">{{ $record->total_hours ?? '00:00' }}</td>
                                                <td class="text-mono">{{ $record->overtime_hours ?? '00:00' }}</td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="6" class="text-center text-muted">
                                                    No paired cycles found for this user and month.
                                                </td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                    <tfoot>
                                        <tr>
                                            <th colspan="4" class="text-end">Total Month Hours:</th>
                                            <th class="text-mono">{{ $totalMonthHours ?? '00:00' }}</th>
                                            <th></th>
                                        </tr>
                                    </tfoot>
                                </table>
                                <div class="small text-muted">
                                    Only complete IN → OUT cycles are counted. Cross-day pairs allowed (within policy
                                    window).
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
