@extends('layouts.template')

@section('breadcrumb')
    @include('sweetalert::alert')
@endsection

@section('content')
    @include('partials.modals.editVendor') @include('partials.scripts.vendorScripts')
    <div class="page-wrapper">
        <div class="content container-fluid">

            {{-- Page Header --}}
            <div class="row mb-4 align-items-center">
                <div class="col-md-12">
                    <h2 class="page-title text-muted mb-0"><strong>Vendors Management</strong></h2>
                </div>
            </div>

            {{-- Filters --}}
            <div class="card mb-4 shadow-sm rounded-3">
                <div class="card-header bg-white border-bottom">
                    <h5 class="mb-0 text-muted">Filters</h5>
                </div>
                <div class="card-body">
                    <div class="row g-3 align-items-center">
                        <div class="col-md-3">
                            <label for="monthFilter" class="form-label">Filter by Month</label>
                            <select id="monthFilter" class="form-select">
                                <option value="">All Months</option>
                                @foreach (range(1, 12) as $month)
                                    <option value="{{ str_pad($month, 2, '0', STR_PAD_LEFT) }}">
                                        {{ date('F', mktime(0, 0, 0, $month, 1)) }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-3">
                            <label for="statusFilter" class="form-label">Filter by Status</label>
                            <select id="statusFilter" class="form-select">
                                <option value="">All Statuses</option>
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>

                        <div class="col-md-3">
                            <label for="showEntries" class="form-label">Show Entries</label>
                            <select id="showEntries" class="form-select">
                                <option value="10">10 per page</option>
                                <option value="20">20 per page</option>
                                <option value="50">50 per page</option>
                                <option value="100">100 per page</option>
                                <option value="-1">All</option>
                            </select>
                        </div>

                        <div class="col-md-3 d-flex align-items-end">
                            <button onclick="clearFilters()" class="btn btn-outline-secondary me-2">
                                <i class="fas fa-times me-1"></i> Clear All
                            </button>
                            <div id="filterStatus" class="text-muted small">Showing 10 entries</div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Vendors Table --}}
            <div class="card shadow-sm rounded-3">
                <div class="card-header d-flex justify-content-between align-items-center bg-white border-bottom">
                    <h5 class="mb-0 text-muted"><i class="fas fa-list text-success"></i> Vendors List</h5>
                    <a href="{{ route('procurements.vendors.create') }}" class="btn btn-sm btn-success">
                        <i class="fas fa-plus me-1"></i> Create Vendor
                    </a>
                </div>

                <div class="card-body">
                    <div class="table-responsive">
                        <x-datatable-vendor id="vendorsTable" class="table table-hover table-striped align-middle w-100">
                            <x-slot name="thead">
                                <tr>
                                    <th>ID</th>
                                    <th>Name</th>
                                    <th>Owner</th>
                                    <th>Type</th>
                                    <th>Contact</th>
                                    <th>Phone</th>
                                    <th>Reg Number</th>
                                    <th>Tax Number</th>
                                    <th>Status</th>
                                    <th>Registered</th>
                                    <th>Attachments</th>
                                    <th class="text-center">Actions</th>
                                </tr>
                            </x-slot>
                            @foreach ($vendors as $vendor)
                                <tr>
                                    <td>{{ $vendor->id }}</td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="avatar-circle me-2">
                                                {{ substr($vendor->name, 0, 2) }}
                                            </div>
                                            <div>
                                                <p class="mb-0 fw-bold">{{ $vendor->name }}</p>
                                                <small class="text-muted">{{ $vendor->address ?? 'N/A' }}</small>
                                            </div>
                                        </div>
                                    </td>
                                    <td>{{ $vendor->owner_name ?? 'N/A' }}</td>
                                    <td>{{ $vendor->type }}</td>
                                    <td>
                                        <p class="mb-0">{{ $vendor->contact_person }}</p>
                                        <small class="text-muted">{{ $vendor->contact_email }}</small>
                                    </td>
                                    <td>{{ $vendor->contact_phone ?? 'N/A' }}</td>
                                    <td>{{ $vendor->registration_number ?? 'N/A' }}</td>
                                    <td>{{ $vendor->tax_number ?? 'N/A' }}</td>
                                    <td>
                                        @php
                                            $statusConfig = [
                                                'active' => ['class' => 'bg-success', 'icon' => 'fa-check-circle'],
                                                'inactive' => ['class' => 'bg-danger', 'icon' => 'fa-times-circle'],
                                            ];
                                            $status = $vendor->status ?? 'active';
                                            $config = $statusConfig[$status] ?? [
                                                'class' => 'bg-secondary',
                                                'icon' => 'fa-question-circle',
                                            ];
                                        @endphp
                                        <span class="badge rounded-pill {{ $config['class'] }}">
                                            <i class="fas {{ $config['icon'] }} me-1"></i>
                                            {{ ucfirst($vendor->status ?? 'N/A') }}
                                        </span>
                                    </td>
                                    <td>
                                        @if ($vendor->registered_at)
                                            {{ $vendor->registered_at->format('Y-m-d') }}
                                        @else
                                            {{ $vendor->created_at->format('Y-m-d') }}
                                        @endif
                                    </td>
                                    <td>
                                        @if ($vendor->attachments_count > 0)
                                            <span class="badge bg-info text-dark attachments-badge" data-bs-toggle="popover"
                                                data-bs-html="true" data-bs-trigger="hover focus"
                                                data-bs-title="<i class='fas fa-paperclip me-2'></i>Attachments ({{ $vendor->attachments_count }})"
                                                data-bs-content="
                                                <div class='attachments-list' style='min-width: 280px;'>
                                                @foreach ($vendor->attachments as $attachment)
<div class='attachment-item d-flex align-items-center justify-content-between mb-2 p-2 border rounded hoverable-file'>
                                                        <div class='d-flex align-items-center flex-grow-1'>
                                                            <i class='fas fa-file-{{ $attachment['file_type'] === 'pdf' ? 'pdf text-danger' : 'word text-primary' }} me-2 fa-lg'></i>
                                                            <div class='flex-grow-1'>
                                                                <div class='fw-medium text-dark' style='font-size: 13px;'>{{ $attachment['original_name'] }}</div>
                                                                <small class='text-muted' style='font-size: 11px;'>
                                                                    {{ number_format($attachment['file_size'] / 1024, 1) }} KB •
                                                                    {{ strtoupper($attachment['file_type']) }}
                                                                </small>
                                                            </div>
                                                        </div>
                                                        <a href='{{ route('vendor.download', ['vendor' => $vendor->id, 'fileIndex' => $loop->index]) }}'
   class='btn btn-sm btn-outline-primary ms-2 download-btn'
   data-bs-toggle='tooltip'
   data-bs-title='Download {{ $attachment['original_name'] }}'>
    <i class='fas fa-download'></i>
</a>
                                                    </div>
@endforeach
                                                </div>
                                              ">
                                                <i class="fas fa-paperclip me-1"></i>
                                                {{ $vendor->attachments_count }}
                                            </span>
                                        @else
                                            <span class="text-muted">No files</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <div class="d-flex gap-1 justify-content-center">
                                            <a href="{{ route('procurements.vendors.edit', $vendor->id) }}"
                                                class="btn btn-sm btn-outline-primary" title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="{{ route('procurements.vendors.show', $vendor->id) }}"
                                                class="btn btn-sm btn-outline-info" title="View">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </x-datatable-vendor>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('styles')
        <!-- DataTables CSS -->
        <link rel="stylesheet" href="https://cdn.datatables.net/2.1.2/css/dataTables.dataTables.css" />
        <style>
            /* Green icons styling */
            .fas,
            .fa,
            i[class*="fa-"] {
                color: #28a745 !important;
            }

            /* Override for specific cases where we want different colors */
            .badge .fas,
            .badge .fa {
                color: inherit !important;
            }

            .btn .fas,
            .btn .fa {
                color: inherit !important;
            }

            .text-warning .fas,
            .text-warning .fa {
                color: #ffc107 !important;
            }

            .text-danger .fas,
            .text-danger .fa {
                color: #dc3545 !important;
            }

            .text-success .fas,
            .text-success .fa {
                color: #28a745 !important;
            }

            .attachments-badge {
                cursor: pointer;
                transition: all 0.2s ease;
            }

            .attachments-badge:hover {
                transform: translateY(-1px);
                box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            }

            .attachment-item {
                background: #f8f9fa;
                transition: background-color 0.2s ease;
            }

            .attachment-item:hover {
                background: #e9ecef;
            }

            .popover {
                max-width: 400px;
            }

            .popover-header {
                background: #f8f9fa;
                border-bottom: 1px solid #dee2e6;
            }

            .modal-overlay {
                display: none;
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(0, 0, 0, 0.5);
                justify-content: center;
                align-items: center;
                z-index: 1050;
                padding: 20px;
            }

            .modal-content {
                background: white;
                border-radius: 10px;
                box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
                max-width: 800px;
                max-height: 90vh;
                overflow-y: auto;
                width: 100%;
                margin: 0 auto;
            }

            #deleteModal .modal-content {
                max-width: 500px;
            }

            .modal-header,
            .modal-footer {
                padding: 1rem 1.5rem;
                border-bottom: 1px solid #dee2e6;
            }

            .modal-footer {
                border-top: 1px solid #dee2e6;
                border-bottom: none;
            }

            .modal-body {
                padding: 1.5rem;
            }

            .avatar-circle {
                width: 40px;
                height: 40px;
                font-weight: bold;
                border: 1px solid #4e73df;
                background: white;
                color: #4e73df;
                border-radius: 50%;
                display: flex;
                align-items: center;
                justify-content: center;
            }

            .form-label.required::after {
                content: " *";
                color: #e74a3b;
            }

            .btn-close {
                border: none;
                background: transparent;
                font-size: 1.2rem;
                cursor: pointer;
                opacity: 0.7;
            }

            .btn-close:hover {
                opacity: 1;
            }

            .badge {
                font-size: 0.75rem;
                font-weight: 600;
                padding: 0.5em 0.8em;
            }

            /* Status badge styling */
            .badge.bg-success {
                background: linear-gradient(45deg, #28a745, #20c997) !important;
                border: 1px solid #1e7e34;
            }

            .badge.bg-danger {
                background: linear-gradient(45deg, #dc3545, #e83e8c) !important;
                border: 1px solid #c82333;
            }

            .badge.bg-secondary {
                background: linear-gradient(45deg, #6c757d, #a0a0a0) !important;
                border: 1px solid #545b62;
            }

            /* Filter status styling */
            #filterStatus.text-success {
                font-weight: 600;
            }

            #filterStatus.text-muted {
                font-style: italic;
            }

            /* Responsive adjustments */
            @media (max-width: 768px) {
                .modal-overlay {
                    padding: 10px;
                    align-items: flex-start;
                    padding-top: 50px;
                }

                .modal-content {
                    max-width: calc(100% - 20px) !important;
                }

                .badge {
                    font-size: 0.7rem;
                    padding: 0.4em 0.6em;
                }
            }
        </style>
    @endpush

    @push('scripts')
        <!-- DataTables JS -->
        <script src="https://cdn.datatables.net/2.1.2/js/dataTables.js"></script>
    @endpush
@endsection
