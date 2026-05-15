{{-- resources/views/locum_requests/view.blade.php --}}
@extends('layouts.template')
@include('sweetalert::alert')

@section('content')
    {{-- needed for AJAX in _scripts --}}
    <meta name="csrf-token" content="{{ csrf_token() }}">
    {{-- styles --}}
    @include('locum_requests.view_partials.styles')

    {{-- DataTables CSS & JS --}}
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" />
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    {{-- SweetAlert2 --}}
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <div class="page-wrapper">
        <div class="content container-fluid">

            {{-- Header / Nav (inline to avoid extra partials) --}}
            <div class="page-header">
                <div class="row">
                    <div class="col-sm-12">
                        <div class="page-sub-header d-flex justify-content-between align-items-center">
                            <div class="btn-group" role="group" aria-label="Locum Navigation">
                                <a href="/locum-agreement-show" class="btn btn-outline-secondary btn-sm me-2">
                                    Locum Agreements
                                    @if (!empty($pendingLocumAgreementCount) && $pendingLocumAgreementCount > 0)
                                        <span class="badge bg-danger ms-1">{{ $pendingLocumAgreementCount }}</span>
                                    @endif
                                </a>
                                <a href="{{ route('locum-requests.view') }}" class="btn btn-outline-primary btn-sm">
                                    Locum Requests
                                    @if ($requests->count() > 0)
                                        <span class="badge bg-warning text-dark ms-1">{{ $requests->count() }}</span>
                                    @endif
                                </a>
                                <a href="{{ route('night-shift.approve.index') }}" class="btn btn-outline-secondary btn-sm">
                                    Night Allowances
                                    @if (!empty($pendingNightShiftCount) && $pendingNightShiftCount > 0)
                                        <span class="badge bg-danger ms-1">{{ $pendingNightShiftCount }}</span>
                                    @endif
                                </a>
                                @can('view_locum_reports')
                                    <a href="{{ route('locum-requests.report') }}" class="btn btn-outline-secondary btn-sm">
                                        Reports
                                    </a>
                                @endcan
                                </div>
                                <h4 class="page-title mb-0 fs-6">Pending Locum Requests</h4>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card shadow-sm mb-4">
                    <div class="card-body">
                        <div class="table-responsive">
                            {{-- TABLE (self-contained; includes per-row modals + bulk reject modal) --}}
                            @include('locum_requests.view_partials._table', ['requests' => $requests])
                        </div>
                    </div>


                </div>
            </div>
        </div>

        {{-- scripts --}}
        @include('locum_requests.view_partials._scripts')
    @endsection
