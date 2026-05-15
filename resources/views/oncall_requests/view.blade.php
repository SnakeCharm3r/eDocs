{{-- resources/views/oncall_requests/view.blade.php --}}
@extends('layouts.template')
@include('sweetalert::alert')

@section('content')
    {{-- needed for AJAX in _scripts --}}
    <meta name="csrf-token" content="{{ csrf_token() }}">

    {{-- DataTables CSS & JS --}}
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" />
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>

    <div class="page-wrapper">
        <div class="content container-fluid">

            {{-- Header / Nav --}}
            <div class="page-header">
                <div class="row">
                    <div class="col-sm-12">
                        <div class="page-sub-header d-flex justify-content-between align-items-center">
                            <div class="btn-group" role="group" aria-label="On-Call Navigation">
                                <a href="{{ route('oncall_requests.index') }}" class="btn btn-outline-secondary btn-sm me-2">
                                    <i class="fas fa-list me-1"></i> My Claims
                                </a>
                                <a href="{{ route('oncall_requests.view') }}" class="btn btn-outline-primary btn-sm">
                                    <i class="fas fa-tasks me-1"></i> Review Requests
                                </a>
                                @canany(['view oncall reports', 'view oncall requests'])
                                    <a href="{{ route('oncall_requests.report') }}" class="btn btn-outline-secondary btn-sm">
                                        <i class="fas fa-chart-bar me-1"></i> Reports
                                    </a>
                                @endcanany
                            </div>
                            <h4 class="page-title mb-0 fs-6">
                                @if(isset($filter) && $filter === 'approved')
                                    Approved On-Call Requests
                                @else
                                    Pending On-Call Requests
                                @endif
                            </h4>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Filter Buttons --}}
            <div class="row mb-3">
                <div class="col-sm-12">
                    <div class="btn-group" role="group" aria-label="Filter Requests">
                        <a href="{{ route('oncall_requests.view', ['filter' => 'pending']) }}" 
                           class="btn {{ (!isset($filter) || $filter === 'pending') ? 'btn-primary' : 'btn-outline-primary' }}">
                            <i class="fas fa-clock me-1"></i> Pending
                        </a>
                        <a href="{{ route('oncall_requests.view', ['filter' => 'approved']) }}" 
                           class="btn {{ (isset($filter) && $filter === 'approved') ? 'btn-primary' : 'btn-outline-primary' }}">
                            <i class="fas fa-check-circle me-1"></i> Approved
                        </a>
                    </div>
                </div>
            </div>

            <div class="card shadow-sm mb-4">
                <div class="card-body">
                    <div class="table-responsive">
                        {{-- TABLE (self-contained; includes per-row modals + bulk reject modal) --}}
                        @include('oncall_requests.view_partials._table', ['requests' => $requests, 'filter' => $filter ?? 'pending'])
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- scripts --}}
    @include('oncall_requests.view_partials._scripts')
@endsection
