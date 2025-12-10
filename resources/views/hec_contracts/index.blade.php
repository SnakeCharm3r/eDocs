@extends('layouts.template')

@push('styles')
    {{-- DataTables CSS & JS (same style as locums module) --}}
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" />
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
@endpush

@section('breadcrumb')
    @include('sweetalert::alert')
@endsection

@section('content')
    <div class="page-wrapper">
        <div class="content container-fluid">
            @if (session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            <div class="card shadow-sm mb-4 border-0">
                <div class="card-header bg-white text-dark border-bottom">
                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
                        <div>
                            <h4 class="mb-1 text-dark">
                                <i class="fas fa-file-contract me-2"></i>HEC Contracts
                            </h4>
                            <small class="text-muted">
                                <i class="fas fa-chart-line me-1"></i>Total: <strong>{{ $totalContracts ?? 0 }}</strong> |
                                Active: <strong>{{ $activeContracts ?? 0 }}</strong> |
                                Expired: <strong>{{ $expiredContracts ?? 0 }}</strong> |
                                Soon to Expire: <strong>{{ $soonToExpireContracts ?? 0 }}</strong>
                            </small>
                        </div>
                        <div>
                            <a href="{{ route('hec-contracts.create') }}" class="btn btn-success btn-sm">
                                <i class="fas fa-plus me-1"></i> Add HEC Contract
                            </a>
                        </div>
                    </div>
                </div>

                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-8 d-flex flex-wrap gap-2 align-items-center">
                            <div class="d-flex flex-wrap gap-2">
                                <div>
                                    <label for="hecStatusFilter" class="form-label form-label-sm mb-1">Status</label>
                                    <select id="hecStatusFilter" class="form-select form-select-sm">
                                        <option value="">All Status</option>
                                        <option value="active">Active</option>
                                        <option value="expired">Expired</option>
                                        <option value="soonToExpire">Soon To Expire</option>
                                        <option value="in_progress">In Progress</option>
                                        <option value="draft">Draft</option>
                                        <option value="renewed">Renewed</option>
                                        <option value="terminated">Terminated</option>
                                    </select>
                                </div>
                                <div>
                                    <label for="hecTypeFilter" class="form-label form-label-sm mb-1">Type</label>
                                    <select id="hecTypeFilter" class="form-select form-select-sm">
                                        <option value="">All Types</option>
                                        <option value="Services">Services</option>
                                        <option value="Goods">Goods</option>
                                        <option value="Services and Goods">Services and Goods</option>
                                        <option value="Consultants">Consultants</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-hover table-striped align-middle" id="hecContractsTable">
                            <thead class="table-light">
                                <tr>
                                    <th>Contract Number</th>
                                    <th>Title</th>
                                    <th>Type</th>
                                    <th>Entity</th>
                                    <th>Value</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($contracts as $contract)
                                    <tr>
                                        <td class="fw-semibold">{{ $contract->contract_number ?? 'N/A' }}</td>
                                        <td>{{ $contract->title }}</td>
                                        <td><span class="badge bg-info">{{ $contract->contract_type }}</span></td>
                                        <td>
                                            @if ($contract->division)
                                                {{ $contract->division->name }}
                                                @if ($contract->division->code)
                                                    <br><small class="text-muted">{{ $contract->division->code }}</small>
                                                @endif
                                            @else
                                                <span class="text-muted">N/A</span>
                                            @endif
                                        </td>
                                        <td class="text-end">
                                            {{ number_format($contract->cost ?? 0, 2) }}
                                            <small class="text-muted">{{ $contract->currency ?? 'TZS' }}</small>
                                        </td>
                                        <td>
                                            @php
                                                $statusColors = [
                                                    'active' => 'success',
                                                    'expired' => 'danger',
                                                    'soonToExpire' => 'warning',
                                                    'draft' => 'secondary',
                                                    'in_progress' => 'info',
                                                    'renewed' => 'primary',
                                                    'terminated' => 'dark',
                                                ];
                                                $color = $statusColors[$contract->status] ?? 'secondary';
                                            @endphp
                                            <span class="badge bg-{{ $color }}">
                                                {{ ucfirst(str_replace('_', ' ', $contract->status)) }}
                                            </span>
                                        </td>
                                        <td>
                                            <div class="btn-group" role="group">
                                                <a href="{{ route('hec-contracts.show', $contract->id) }}"
                                                    class="btn btn-sm btn-info" title="View">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <a href="{{ route('hec-contracts.edit', $contract->id) }}"
                                                    class="btn btn-sm btn-warning" title="Edit">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <button type="button" class="btn btn-sm btn-danger" data-bs-toggle="modal"
                                                    data-bs-target="#hecDeleteModal" data-contract-id="{{ $contract->id }}"
                                                    data-contract-number="{{ $contract->contract_number }}" title="Delete">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    @if ($contracts->isEmpty())
                        <div class="text-center py-4">
                            <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                            <p class="text-muted">No HEC contracts found.</p>
                            @role('Admin-Secretary|super-admin')
                                <a href="{{ route('hec-contracts.create') }}" class="btn btn-success">
                                    <i class="fas fa-plus me-1"></i> Create First Contract
                                </a>
                            @endrole
                        </div>
                    @endif

                    @if ($contracts->hasPages())
                        <div class="mt-3">
                            {{ $contracts->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="hecDeleteModal" tabindex="-1" aria-labelledby="hecDeleteModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="hecDeleteModalLabel">Delete HEC Contract</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>
                        Are you sure you want to delete this HEC contract?
                    </p>
                    <p class="mb-0">
                        <strong>Contract:</strong>
                        <span id="hecDeleteContractNumber" class="text-danger"></span>
                    </p>
                    <small class="text-muted">This action cannot be undone.</small>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <form id="hecDeleteForm" method="POST" class="d-inline">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger btn-sm">
                            <i class="fas fa-trash me-1"></i> Delete
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            $(document).ready(function() {
                // Initialise DataTable (search + pagination + sorting)
                const table = $('#hecContractsTable').DataTable({
                    pageLength: 20, // default show 20
                    lengthChange: true, // allow user to change page size
                    lengthMenu: [
                        [10, 20, 50, 100],
                        [10, 20, 50, 100]
                    ],
                    order: [
                        [0, 'desc']
                    ],
                    language: {
                        search: 'Search:',
                        lengthMenu: 'Show _MENU_ contracts per page',
                        info: 'Showing _START_ to _END_ of _TOTAL_ contracts',
                        infoEmpty: 'Showing 0 to 0 of 0 contracts',
                        paginate: {
                            previous: '&laquo;',
                            next: '&raquo;'
                        }
                    }
                });

                // Column filters (Status + Type)
                const statusFilter = $('#hecStatusFilter');
                const typeFilter = $('#hecTypeFilter');

                function applyFilters() {
                    const statusVal = statusFilter.val() || '';
                    const typeVal = typeFilter.val() || '';

                    // Status column index = 5
                    if (statusVal) {
                        table.column(5).search('^' + statusVal.replace(/[-\/\\^$*+?.()|[\]{}]/g, '\\$&') + '$', true,
                            false);
                    } else {
                        table.column(5).search('');
                    }

                    // Type column index = 2
                    if (typeVal) {
                        table.column(2).search('^' + typeVal.replace(/[-\/\\^$*+?.()|[\]{}]/g, '\\$&') + '$', true,
                            false);
                    } else {
                        table.column(2).search('');
                    }

                    table.draw();
                }

                statusFilter.on('change', applyFilters);
                typeFilter.on('change', applyFilters);

                // Delete modal handling
                const deleteModal = document.getElementById('hecDeleteModal');
                if (deleteModal) {
                    deleteModal.addEventListener('show.bs.modal', function(event) {
                        const button = event.relatedTarget;
                        const contractId = button.getAttribute('data-contract-id');
                        const contractNumber = button.getAttribute('data-contract-number') || '—';

                        const numberSpan = deleteModal.querySelector('#hecDeleteContractNumber');
                        const form = deleteModal.querySelector('#hecDeleteForm');

                        if (numberSpan) {
                            numberSpan.textContent = contractNumber;
                        }

                        if (form && contractId) {
                            form.action = "{{ url('hec-contracts') }}/" + contractId;
                        }
                    });
                }
            });
        </script>
    @endpush
@endsection
