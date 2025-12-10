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
                                @can('view oncall reports')
                                    <a href="{{ route('oncall_requests.report') }}" class="btn btn-outline-secondary btn-sm">
                                        <i class="fas fa-chart-bar me-1"></i> Reports
                                    </a>
                                @endcan
                            </div>
                            <h4 class="page-title mb-0 fs-6">Pending On-Call Requests</h4>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card shadow-sm mb-4">
                <div class="card-body">
                    <div class="table-responsive">
                        {{-- TABLE (self-contained; includes per-row modals + bulk reject modal) --}}
                        @include('oncall_requests.view_partials._table', ['requests' => $requests])
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- scripts --}}
    @include('oncall_requests.view_partials._scripts')
@endsection
