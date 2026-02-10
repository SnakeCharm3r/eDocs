@extends('layouts.template')

@php
    use Illuminate\Support\Str;
@endphp

@section('breadcrumb')
    @include('sweetalert::alert')
@endsection

@section('content')
    <div class="page-wrapper">
        <div class="content container-fluid">
            <div class="page-header">
                <div class="row align-items-center">
                    <div class="col">
                        <h3 class="page-title">
                            <i class="fas fa-bell me-2"></i>Contract Notification Management
                        </h3>
                        {{-- <p class="text-muted">Manage automatic expiration notifications for contracts</p> --}}
                    </div>
                    <div class="col-auto">
                        <a href="{{ route('procurements.contracts.index') }}" class="btn btn-secondary">
                            <i class="fas fa-arrow-left me-1"></i> Back to Contracts
                        </a>
                    </div>
                </div>
            </div>

            @if (session('success'))
                <div class="alert alert-success alert-dismissible fade show">
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            @if (session('error'))
                <div class="alert alert-danger alert-dismissible fade show">
                    {{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            <!-- Admin Area: Email Testing & Queue Management (Super Admin Only) -->
            @if (auth()->user()->hasRole('super-admin'))
                <div class="row mb-4">
                    <div class="col-md-6">
                        <div class="card shadow-sm border-0">
                            <div class="card-header bg-white border-bottom">
                                <h5 class="mb-0 text-dark">
                                    <i class="fas fa-envelope me-2 text-muted"></i>Test Reminder Email
                                </h5>
                            </div>
                            <div class="card-body">
                                <form action="{{ route('procurements.contracts.test-reminder-email') }}" method="POST">
                                    @csrf
                                    <div class="mb-3">
                                        <label for="test_email" class="form-label fw-semibold">Recipient Email</label>
                                        <input type="email" name="test_email" class="form-control"
                                            placeholder="example@domain.com" required>
                                        <small class="text-muted">Enter an email address to test reminder email
                                            delivery</small>
                                    </div>
                                    <div class="mb-3">
                                        <label for="contract_id" class="form-label fw-semibold">Select Contract
                                            (Optional)</label>
                                        <select name="contract_id" class="form-select">
                                            <option value="">-- No specific contract --</option>
                                            @foreach ($contracts->take(20) as $contract)
                                                <option value="{{ $contract->id }}">{{ $contract->title }}</option>
                                            @endforeach
                                        </select>
                                        <small class="text-muted">If selected, contract details will be included in the test
                                            email</small>
                                    </div>
                                    <button type="submit" class="btn btn-success">
                                        <i class="fas fa-paper-plane me-1"></i> Send Test Email
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card shadow-sm border-0">
                            <div class="card-header bg-white border-bottom">
                                <h5 class="mb-0 text-dark">
                                    <i class="fas fa-cog me-2 text-muted"></i>System Status
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <strong>Queue Connection:</strong>
                                    <span class="badge bg-{{ $queueConnection === 'sync' ? 'success' : 'warning' }}">
                                        {{ strtoupper($queueConnection) }}
                                    </span>
                                    @if ($queueConnection !== 'sync')
                                        <small class="text-muted d-block mt-1">
                                            <i class="fas fa-info-circle"></i> Queue worker must be running: <code>php
                                                artisan queue:work</code>
                                        </small>
                                    @endif
                                </div>
                                <div class="mb-3">
                                    <strong>Pending Jobs:</strong>
                                    <span class="badge bg-secondary">{{ $pendingJobs }}</span>
                                </div>
                                <div class="mb-3">
                                    <strong>Failed Jobs:</strong>
                                    <span
                                        class="badge bg-{{ $failedJobs > 0 ? 'danger' : 'success' }}">{{ $failedJobs }}</span>
                                </div>
                                <div class="mb-3">
                                    <strong>Mail Host:</strong> {{ $mailConfig['host'] ?? 'Not configured' }}
                                </div>
                                <div class="mb-3">
                                    <strong>Mail Port:</strong> {{ $mailConfig['port'] ?? 'Not configured' }}
                                </div>
                                <div>
                                    <a href="{{ route('settings.email') }}" class="btn btn-sm btn-outline-secondary">
                                        <i class="fas fa-cog me-1"></i> Configure Email Settings
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            <div class="card shadow-sm border-0">
                <div class="card-header bg-white border-bottom">
                    <h5 class="mb-0 text-dark">
                        <i class="fas fa-cog me-2 text-muted"></i>Notification Settings
                    </h5>
                </div>
                <div class="card-body">

                    <div class="table-responsive">
                        <table id="notificationTable" class="table table-striped table-bordered">
                            <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    <th>Contract Name</th>
                                    <th>Vendor</th>
                                    <th>Department</th>
                                    <th>End Date</th>
                                    <th>30 Days Alert</th>
                                    <th>60 Days Alert</th>
                                    <th>90 Days Alert</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($contracts as $index => $contract)
                                    <tr>
                                        <td>{{ $index + 1 }}</td>
                                        <td>
                                            <strong>{{ Str::limit($contract->title, 40) }}</strong>
                                            @if ($contract->contract_number)
                                                <br><small class="text-muted">{{ $contract->contract_number }}</small>
                                            @endif
                                        </td>
                                        <td>
                                            @if ($contract->vendor)
                                                {{ Str::limit($contract->vendor->name, 30) }}
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if ($contract->department)
                                                {{ $contract->department->dept_name }}
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if ($contract->end_date)
                                                {{ \Carbon\Carbon::parse($contract->end_date)->format('Y-m-d') }}
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            <span
                                                class="badge bg-{{ $contract->alert_30_days ? 'success' : 'secondary' }}">
                                                {{ $contract->alert_30_days ? 'Enabled' : 'Disabled' }}
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <span
                                                class="badge bg-{{ $contract->alert_60_days ? 'success' : 'secondary' }}">
                                                {{ $contract->alert_60_days ? 'Enabled' : 'Disabled' }}
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <span
                                                class="badge bg-{{ $contract->alert_90_days ? 'success' : 'secondary' }}">
                                                {{ $contract->alert_90_days ? 'Enabled' : 'Disabled' }}
                                            </span>
                                        </td>
                                        <td>
                                            <button type="button" class="btn btn-sm btn-outline-success"
                                                data-bs-toggle="modal"
                                                data-bs-target="#editNotificationModal{{ $contract->id }}">
                                                <i class="fas fa-edit"></i> Edit
                                            </button>
                                        </td>
                                    </tr>

                                    <!-- Edit Notification Modal -->
                                    <div class="modal fade" id="editNotificationModal{{ $contract->id }}" tabindex="-1">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <div class="modal-header bg-white border-bottom">
                                                    <h5 class="modal-title text-dark">
                                                        <i class="fas fa-bell me-2 text-muted"></i>Edit Notification
                                                        Settings
                                                    </h5>
                                                    <button type="button" class="btn-close"
                                                        data-bs-dismiss="modal"></button>
                                                </div>
                                                <form
                                                    action="{{ route('procurements.contracts.update-notification', $contract->id) }}"
                                                    method="POST">
                                                    @csrf
                                                    @method('PUT')
                                                    <div class="modal-body">
                                                        <h6 class="mb-3">{{ $contract->title }}</h6>
                                                        <div class="mb-3">
                                                            <div class="form-check form-switch">
                                                                <input class="form-check-input" type="checkbox"
                                                                    id="alert_30_days{{ $contract->id }}"
                                                                    name="alert_30_days" value="1"
                                                                    {{ $contract->alert_30_days ? 'checked' : '' }}>
                                                                <label class="form-check-label"
                                                                    for="alert_30_days{{ $contract->id }}">
                                                                    30 Days Before Expiry
                                                                </label>
                                                            </div>
                                                        </div>
                                                        <div class="mb-3">
                                                            <div class="form-check form-switch">
                                                                <input class="form-check-input" type="checkbox"
                                                                    id="alert_60_days{{ $contract->id }}"
                                                                    name="alert_60_days" value="1"
                                                                    {{ $contract->alert_60_days ? 'checked' : '' }}>
                                                                <label class="form-check-label"
                                                                    for="alert_60_days{{ $contract->id }}">
                                                                    60 Days Before Expiry
                                                                </label>
                                                            </div>
                                                        </div>
                                                        <div class="mb-3">
                                                            <div class="form-check form-switch">
                                                                <input class="form-check-input" type="checkbox"
                                                                    id="alert_90_days{{ $contract->id }}"
                                                                    name="alert_90_days" value="1"
                                                                    {{ $contract->alert_90_days ? 'checked' : '' }}>
                                                                <label class="form-check-label"
                                                                    for="alert_90_days{{ $contract->id }}">
                                                                    90 Days Before Expiry
                                                                </label>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary"
                                                            data-bs-dismiss="modal">Cancel</button>
                                                        <button type="submit" class="btn btn-success">
                                                            <i class="fas fa-save me-1"></i> Save Changes
                                                        </button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('styles')
        <link rel="stylesheet" href="https://cdn.datatables.net/2.1.2/css/dataTables.dataTables.css" />
    @endpush

    @push('scripts')
        <script src="https://cdn.datatables.net/2.1.2/js/dataTables.js"></script>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                new DataTable('#notificationTable', {
                    pageLength: 25,
                    lengthMenu: [
                        [10, 25, 50, 100, -1],
                        [10, 25, 50, 100, "All"]
                    ],
                    order: [
                        [0, 'asc']
                    ],
                    columnDefs: [{
                            orderable: false,
                            targets: 8
                        } // Actions column
                    ]
                });
            });
        </script>
    @endpush
@endsection
