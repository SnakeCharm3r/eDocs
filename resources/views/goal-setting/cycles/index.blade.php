@extends('layouts.template')

@section('breadcrumb')
    @include('sweetalert::alert')
@endsection

@section('content')
    <div class="page-wrapper">
        <div class="content container-fluid">
            <div class="page-header mb-4">
                <div class="row align-items-center">
                    <div class="col">
                        <h3 class="page-title mb-0">Goal Setting Cycles</h3>
                    </div>
                    <div class="col-auto">
                        @can('manage goal cycles')
                            <a href="{{ route('goal-setting.cycles.create') }}" class="btn btn-success btn-sm">
                                <i class="fas fa-plus"></i> Create Cycle
                            </a>
                        @endcan
                    </div>
                </div>
            </div>

            @if (session('success') || session('error'))
                <div class="mb-4">
                    @if (session('success'))
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="fas fa-check-circle me-2"></i> {{ session('success') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif
                    @if (session('error'))
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="fas fa-exclamation-circle me-2"></i> {{ session('error') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif
                </div>
            @endif

            <div class="row">
                <div class="col-12">
                    <div class="card shadow-sm">
                        <div class="card-header bg-white border-bottom">
                            <h5 class="mb-0">Cycles</h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover table-striped align-middle">
                                    <thead class="table-light">
                                        <tr>
                                            <th style="width: 5%;">#</th>
                                            <th style="width: 30%;">Name</th>
                                            <th style="width: 15%;">Status</th>
                                            <th style="width: 20%;">Start Date</th>
                                            <th style="width: 20%;">End Date</th>
                                            <th style="width: 10%;">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse ($cycles as $index => $cycle)
                                            <tr>
                                                <td>{{ $index + 1 }}</td>
                                                <td><strong>{{ $cycle->name }}</strong></td>
                                                <td>
                                                    @php
                                                        $status = $cycle->status ?? 'draft';
                                                        $badge = 'bg-secondary';
                                                        if ($status === 'active') {
                                                            $badge = 'bg-success';
                                                        }
                                                        if ($status === 'closed') {
                                                            $badge = 'bg-dark';
                                                        }
                                                    @endphp
                                                    <span class="badge {{ $badge }}">{{ ucfirst($status) }}</span>
                                                </td>
                                                <td>{{ $cycle->start_date ? \Carbon\Carbon::parse($cycle->start_date)->format('d M Y') : '—' }}</td>
                                                <td>{{ $cycle->end_date ? \Carbon\Carbon::parse($cycle->end_date)->format('d M Y') : '—' }}</td>
                                                <td>
                                                    <a href="{{ route('goal-setting.hec-goals.create', ['cycle' => $cycle->id]) }}" class="btn btn-sm btn-outline-success">
                                                        HEC Goal
                                                    </a>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="6" class="text-center text-muted py-4">No cycles found.</td>
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
