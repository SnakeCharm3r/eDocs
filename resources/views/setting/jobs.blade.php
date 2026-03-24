@extends('layouts.template')

@push('styles')
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" />
@endpush

@section('breadcrumb')
    @include('sweetalert::alert')
    @include('includes.loader')
@endsection

@section('content')
    @include('sweetalert::alert')
    <div class="page-wrapper">
        <div class="content container-fluid">
            <div class="page-header">
                <div class="row">
                    <div class="col-sm-12">
                        <div class="page-sub-header">
                            <h3 class="page-title">Cron & Queue Jobs</h3>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Queue status --}}
            <div class="row mb-4">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0"><i class="fas fa-layer-group me-2 text-success"></i>Queue Status</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-4">
                                    <p class="mb-1 text-muted small">Queue driver</p>
                                    <p class="mb-0 fw-bold">{{ $queueDriver }}</p>
                                </div>
                                <div class="col-md-4">
                                    <p class="mb-1 text-muted small">Pending jobs</p>
                                    <p class="mb-0 fw-bold">{{ $pendingCount }}</p>
                                    @if($queueDriver === 'sync')
                                        <small class="text-muted">Jobs run immediately when driver is "sync".</small>
                                    @endif
                                </div>
                                <div class="col-md-4">
                                    <p class="mb-1 text-muted small">Failed jobs</p>
                                    <p class="mb-0 fw-bold">{{ $failedJobs->count() }}</p>
                                    <small class="text-muted">Last 100 shown below. Retry individually or all.</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Scheduled (cron) tasks --}}
            <div class="row mb-4">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0"><i class="fas fa-clock me-2 text-success"></i>Scheduled Tasks (Cron)</h5>
                        </div>
                        <div class="card-body p-0">
                            <pre class="mb-0 p-3 bg-light border-0 rounded-0" style="max-height: 320px; overflow: auto; font-size: 0.85rem;">{{ $scheduleListOutput ?: 'No schedule output.' }}</pre>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Failed jobs --}}
            <div class="row">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-header d-flex flex-wrap justify-content-between align-items-center">
                            <h5 class="card-title mb-0"><i class="fas fa-exclamation-circle me-2 text-success"></i>Failed Jobs</h5>
                            @if($failedJobs->isNotEmpty())
                                <form action="{{ route('settings.jobs.retry-all') }}" method="POST" class="d-inline" onsubmit="return confirm('Retry all {{ $failedJobs->count() }} failed jobs?');">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-outline-primary">Retry all</button>
                                </form>
                            @endif
                        </div>
                        <div class="card-body p-0">
                            @if($failedJobs->isEmpty())
                                <p class="mb-0 p-4 text-muted">No failed jobs.</p>
                            @else
                                <div class="table-responsive">
                                    <table class="table table-hover table-striped align-middle mb-0" id="failedJobsTable">
                                        <thead class="table-light">
                                            <tr>
                                                <th>#</th>
                                                <th>Job / Command</th>
                                                <th>Queue</th>
                                                <th>Failed at</th>
                                                <th width="120">Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($failedJobs as $index => $job)
                                                <tr>
                                                    <td>{{ $index + 1 }}</td>
                                                    <td>
                                                        <span class="text-break" title="{{ $job->display_name }}">{{ Str::limit($job->display_name, 50) }}</span>
                                                    </td>
                                                    <td><code>{{ $job->queue }}</code></td>
                                                    <td>{{ \Carbon\Carbon::parse($job->failed_at)->format('d M Y H:i') }}</td>
                                                    <td>
                                                        <form action="{{ route('settings.jobs.retry', $job->uuid) }}" method="POST" class="d-inline">
                                                            @csrf
                                                            <button type="submit" class="btn btn-sm btn-success">Retry</button>
                                                        </form>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @if(session('success') || session('error'))
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            @if(session('success'))
                if (typeof toastr !== 'undefined') {
                    toastr.success("{{ session('success') }}");
                } else {
                    alert("{{ session('success') }}");
                }
            @endif
            @if(session('error'))
                if (typeof toastr !== 'undefined') {
                    toastr.error("{{ session('error') }}");
                } else {
                    alert("{{ session('error') }}");
                }
            @endif
        });
    </script>
    @endif
@endsection

@push('scripts')
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    @if($failedJobs->isNotEmpty())
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            $('#failedJobsTable').DataTable({
                order: [[3, 'desc']],
                pageLength: 25,
                lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
                language: {
                    search: 'Search:',
                    lengthMenu: 'Show _MENU_ entries',
                    info: 'Showing _START_ to _END_ of _TOTAL_ failed jobs',
                    infoEmpty: 'No failed jobs',
                    infoFiltered: '(filtered from _MAX_ total)',
                    paginate: { first: 'First', last: 'Last', next: 'Next', previous: 'Previous' }
                },
                columnDefs: [
                    { orderable: false, targets: 0 },
                    { orderable: false, targets: 4 }
                ]
            });
        });
    </script>
    @endif
@endpush
