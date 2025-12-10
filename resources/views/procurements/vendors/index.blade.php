@extends('layouts.template')

@php
    use Illuminate\Support\Str;
@endphp

@section('breadcrumb')
    @include('sweetalert::alert')
@endsection

@section('content')
    @include('partials.modals.editVendor')
    @include('partials.scripts.vendorScripts')

    <div class="page-wrapper">
        <div class="content container-fluid">
            {{-- Page Header --}}
            <div class="card shadow-sm mb-4 border-0">
                <div class="card-header bg-white text-dark border-bottom d-flex justify-content-between align-items-center">
                    <div>
                        <h4 class="mb-1 text-dark">
                            </i>Vendors Management
                        </h4>
                        <small class="text-muted">
                            <i class="fas fa-list me-1"></i>Total Vendors: <strong>{{ $vendors->count() }}</strong>
                        </small>
                    </div>
                    <div class="d-flex gap-2">
                        <a href="{{ route('procurements.vendors.create') }}" class="btn btn-success btn-sm">
                            <i class="fas fa-plus me-1"></i> Add Vendor
                        </a>
                    </div>
                </div>

                <div class="card-body">
                    {{-- Filters --}}
                    <div class="row mb-3">
                        <div class="col-md-3">
                            <label for="statusFilter" class="form-label small text-muted">Filter by Status</label>
                            <select id="statusFilter" class="form-select form-select-sm">
                                <option value="">All Statuses</option>
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="typeFilter" class="form-label small text-muted">Filter by Type</label>
                            <select id="typeFilter" class="form-select form-select-sm">
                                <option value="">All Types</option>
                                @php
                                    $types = $vendors->pluck('type')->unique()->filter()->sort();
                                @endphp
                                @foreach ($types as $type)
                                    <option value="{{ $type }}">{{ ucfirst(str_replace('_', ' ', $type)) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="categoryFilter" class="form-label small text-muted">Filter by Category</label>
                            <select id="categoryFilter" class="form-select form-select-sm">
                                <option value="">All Categories</option>
                                @php
                                    $industries = $vendors->pluck('industry')->unique()->filter()->sort();
                                @endphp
                                @foreach ($industries as $industry)
                                    @if (!empty($industry))
                                        <option value="{{ $industry }}">
                                            {{ ucfirst(str_replace('_', ' ', $industry)) }}
                                        </option>
                                    @endif
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3 d-flex align-items-end">
                            <button onclick="clearFilters()" class="btn btn-outline-secondary btn-sm">
                                <i class="fas fa-times me-1"></i> Clear Filters
                            </button>
                        </div>
                    </div>

                    {{-- Vendors Table --}}
                    <div class="table-responsive">
                        <table id="vendorsTable" class="table table-hover table-striped align-middle w-100">
                            <thead class="table-light">
                                <tr>
                                    <th style="width: 60px;">ID</th>
                                    <th>Vendor Name</th>
                                    <th style="width: 120px;">Type</th>
                                    <th style="width: 150px;">Category</th>
                                    <th style="width: 100px;">Status</th>
                                    <th style="width: 120px;">Registered</th>
                                    <th style="width: 150px;" class="text-center">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($vendors as $vendor)
                                    <tr>
                                        <td>{{ $vendor->id }}</td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="avatar-circle me-2"
                                                    style="width: 35px; height: 35px; font-size: 0.85rem;">
                                                    {{ strtoupper(substr($vendor->name, 0, 2)) }}
                                                </div>
                                                <div>
                                                    <p class="mb-0 fw-semibold">{{ $vendor->name }}</p>
                                                    <small
                                                        class="text-muted">{{ Str::limit($vendor->address ?? 'N/A', 30) }}</small>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <span
                                                class="badge bg-secondary">{{ ucfirst(str_replace('_', ' ', $vendor->type ?? 'N/A')) }}</span>
                                        </td>
                                        <td>
                                            @if (!empty($vendor->industry))
                                                @php
                                                    $industryLabels = [
                                                        'construction' => 'Construction',
                                                        'building_materials' => 'Building Materials',
                                                        'it_equipment' => 'IT Equipment',
                                                        'office_supplies' => 'Office Supplies',
                                                        'furniture' => 'Furniture',
                                                        'medical_supplies' => 'Medical Supplies',
                                                        'consultancy' => 'Consultancy',
                                                        'legal' => 'Legal',
                                                        'transport' => 'Transport',
                                                        'security_services' => 'Security',
                                                        'cleaning_services' => 'Cleaning',
                                                        'catering' => 'Catering',
                                                        'training' => 'Training',
                                                    ];
                                                    $industryDisplay =
                                                        $industryLabels[$vendor->industry] ??
                                                        ucfirst(str_replace('_', ' ', $vendor->industry));
                                                @endphp
                                                <span class="badge bg-info text-white">
                                                    <i class="fas fa-industry me-1"></i>
                                                    {{ $industryDisplay }}
                                                </span>
                                            @else
                                                <span class="text-muted">N/A</span>
                                            @endif
                                        </td>
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
                                                {{ \Carbon\Carbon::parse($vendor->registered_at)->format('Y-m-d') }}
                                            @else
                                                {{ \Carbon\Carbon::parse($vendor->created_at)->format('Y-m-d') }}
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            <div class="d-flex gap-1 justify-content-center">
                                                <a href="{{ route('procurements.vendors.show', $vendor->id) }}"
                                                    class="btn btn-sm btn-outline-success" title="View Details">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <a href="{{ route('procurements.vendors.edit', $vendor->id) }}"
                                                    class="btn btn-sm btn-outline-primary" title="Edit">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                @php
                                                    $hasContracts = $vendor->contracts_count > 0;
                                                @endphp
                                                @if ($hasContracts)
                                                    <button type="button" class="btn btn-sm btn-outline-danger"
                                                        title="Cannot delete: Vendor has {{ $vendor->contracts_count }} contract(s)"
                                                        disabled
                                                        onclick="alert('Cannot delete vendor. This vendor has {{ $vendor->contracts_count }} contract(s) associated. Please remove or reassign the contracts first.');">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                @else
                                                    <form action="{{ route('procurements.vendors.destroy', $vendor->id) }}"
                                                        method="POST" class="d-inline"
                                                        onsubmit="return confirm('Are you sure you want to delete this vendor?');">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="btn btn-sm btn-outline-danger"
                                                            title="Delete">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </form>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
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
        <style>
            .avatar-circle {
                width: 35px;
                height: 35px;
                font-weight: bold;
                border: 1px solid #28a745;
                background: white;
                color: #28a745;
                border-radius: 50%;
                display: flex;
                align-items: center;
                justify-content: center;
                font-size: 0.85rem;
            }

            .badge {
                font-size: 0.75rem;
                font-weight: 600;
                padding: 0.5em 0.8em;
            }

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

            .badge.bg-info {
                background: linear-gradient(45deg, #17a2b8, #138496) !important;
                border: 1px solid #117a8b;
            }
        </style>
    @endpush

    @push('scripts')
        <script src="https://cdn.datatables.net/2.1.2/js/dataTables.js"></script>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                // Initialize DataTable
                const table = new DataTable('#vendorsTable', {
                    responsive: true,
                    pageLength: 25,
                    lengthMenu: [
                        [10, 25, 50, 100, -1],
                        [10, 25, 50, 100, 'All']
                    ],
                    order: [
                        [0, 'desc']
                    ],
                    columnDefs: [{
                            orderable: false,
                            targets: 6
                        } // Actions column
                    ],
                    language: {
                        searchPlaceholder: 'Search vendors...'
                    }
                });

                // Status filter
                $('#statusFilter').on('change', function() {
                    table.column(4).search(this.value).draw();
                });

                // Type filter
                $('#typeFilter').on('change', function() {
                    table.column(2).search(this.value).draw();
                });

                // Category filter
                $('#categoryFilter').on('change', function() {
                    table.column(3).search(this.value).draw();
                });

                // Clear filters function
                window.clearFilters = function() {
                    $('#statusFilter').val('').trigger('change');
                    $('#typeFilter').val('').trigger('change');
                    $('#categoryFilter').val('').trigger('change');
                    table.search('').draw();
                };
            });
        </script>
    @endpush
@endsection
