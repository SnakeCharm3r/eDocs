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
            {{-- Page Header --}}
            <div class="card shadow-sm mb-4 border-0">
                <div class="card-header bg-white text-dark border-bottom d-flex justify-content-between align-items-center">
                    <div>
                        <h4 class="mb-1 text-dark">
                            <i class="fas fa-file-alt me-2"></i>Recruitment Requisitions
                        </h4>
                        <small class="text-muted">
                            <i class="fas fa-list me-1"></i>Total Requisitions:
                            <strong>{{ $requisitions->count() }}</strong>
                            @if (isset($isHecMember) && $isHecMember)
                                <span class="badge bg-info text-white ms-2">
                                    <i class="fas fa-user-shield me-1"></i>HEC Member View
                                </span>
                            @else
                                <span class="badge bg-primary text-white ms-2">
                                    <i class="fas fa-user-tie me-1"></i>Line Manager View
                                </span>
                            @endif
                        </small>
                    </div>
                    <div class="d-flex gap-2">
                        @php
                            $user = auth()->user();
                            $canCreate = $user->can('create new requisition') || 
                                         $user->hasRole('line-manager') || 
                                         $user->hasAnyRole(['coo', 'cms', 'cfo', 'crhdo', 'chief_accountant']);
                        @endphp
                        @if($canCreate)
                            <a href="{{ route('requisitions.create') }}" class="btn btn-success btn-sm">
                                <i class="fas fa-plus me-1"></i> Create New Requisition
                            </a>
                        @endif
                    </div>
                </div>

                <div class="card-body">
                    {{-- Requisitions Table --}}
                    <div class="table-responsive">
                        <table id="requisitionsTable" class="table table-hover table-striped align-middle w-100">
                            <thead class="table-light">
                                <tr>
                                    <th style="width: 50px;" class="text-center">#</th>
                                    <th style="width: 180px;">Requester</th>
                                    <th style="width: 180px;">Department</th>
                                    <th style="width: 120px;">Status</th>
                                    <th style="width: 120px;">Submitted</th>
                                    <th style="width: 110px;" class="text-center">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($requisitions as $requisition)
                                    <tr>
                                        <td class="text-center">{{ $loop->iteration }}</td>
                                        <td>
                                            @php
                                                $requester = $requisition->user ?? null;
                                                $initiatedByHEC = false;
                                                $hecInitiator = null;
                                                $lineManagerFor = null; // Line manager for HEC-initiated requisitions

                                                // Check if this requisition was initiated by an HEC member
                                                if (
                                                    $requisition->workflow &&
                                                    $requisition->workflow->histories->count() > 0
                                                ) {
                                                    $firstHistory = $requisition->workflow->histories
                                                        ->sortBy('created_at')
                                                        ->first();
                                                    if ($firstHistory) {
                                                        // Try to get initiator from forwardedBy relationship first
                                                        $initiator = $firstHistory->forwardedBy;

                                                        // Fallback to finding by forwarded_by if relationship not loaded
                                                        if (!$initiator && $firstHistory->forwarded_by) {
                                                            $initiator = \App\Models\User::with('roles')->find(
                                                                $firstHistory->forwarded_by,
                                                            );
                                                        }

                                                        if ($initiator && $initiator->hasAnyRole(['coo', 'cms'])) {
                                                            $initiatedByHEC = true;
                                                            $hecInitiator = $initiator;
                                                            // For HEC-initiated, the line manager is the requisition user
                                                            $lineManagerFor = $requester;
                                                        }
                                                    }
                                                }
                                            @endphp
                                            @if ($initiatedByHEC && $hecInitiator)
                                                {{-- Show HEC Member as Requester --}}
                                                <div class="d-flex flex-column">
                                                    <div class="d-flex align-items-center gap-1 mb-1">
                                                        <span class="fw-semibold">
                                                            {{ $hecInitiator->fname ?? '' }}
                                                            {{ $hecInitiator->mname ?? '' }}
                                                            {{ $hecInitiator->lname ?? '' }}
                                                        </span>
                                                        <span class="badge bg-info text-white" style="font-size: 0.6rem;"
                                                            title="HEC Member (Requester)">
                                                            <i class="fas fa-user-shield"></i>
                                                        </span>
                                                    </div>
                                                    @if (!empty($hecInitiator->employee_id))
                                                        <small class="text-muted">
                                                            <i
                                                                class="fas fa-id-badge me-1"></i>{{ $hecInitiator->employee_id }}
                                                        </small>
                                                    @endif
                                                </div>
                                            @elseif ($requester)
                                                {{-- Show Line Manager as Requester --}}
                                                <div class="d-flex flex-column">
                                                    <div class="d-flex align-items-center gap-1 mb-1">
                                                        <span class="fw-semibold">
                                                            {{ $requester->fname ?? '' }} {{ $requester->mname ?? '' }}
                                                            {{ $requester->lname ?? '' }}
                                                        </span>
                                                        @if ($requester->hasRole('line-manager'))
                                                            <span class="badge bg-primary text-white"
                                                                style="font-size: 0.6rem;" title="Line Manager">
                                                                <i class="fas fa-user-tie"></i>
                                                            </span>
                                                        @endif
                                                    </div>
                                                    @if (!empty($requester->employee_id))
                                                        <small class="text-muted">
                                                            <i
                                                                class="fas fa-id-badge me-1"></i>{{ $requester->employee_id }}
                                                        </small>
                                                    @endif
                                                </div>
                                            @else
                                                <span class="text-muted">N/A</span>
                                            @endif
                                        </td>
                                        <td>
                                            <span class="text-dark">
                                                <i class="fas fa-building me-1 text-muted"></i>
                                                {{ Str::limit($requisition->department->dept_name ?? 'N/A', 20) }}
                                            </span>
                                        </td>
                                        <td>
                                            @php
                                                $latestHistory = null;
                                                $statusInfo = null;
                                                $showStatus = false;
                                                $isHRProcessing = false;
                                                $isCompleted = false;

                                                if ($requisition->workflow) {
                                                    // Check if workflow is completed
                                                    $isCompleted = $requisition->workflow->work_flow_completed == 1;

                                                    if ($requisition->workflow->histories->count()) {
                                                        $latestHistory = $requisition->workflow->histories
                                                            ->sortByDesc('created_at')
                                                            ->first();

                                                        // If completed, show "Completed" status
                                                        if ($isCompleted) {
                                                            $statusInfo = [
                                                                'label' => 'Completed',
                                                                'class' => 'bg-success text-white',
                                                            ];
                                                            $showStatus = true;
                                                        } else {
                                                            // Check if HR is currently assigned (pending) on this workflow
                                                            $hrAssigned = $requisition->workflow
                                                                ->histories()
                                                                ->whereHas('attendedBy.roles', function ($q) {
                                                                    $q->where('name', 'hr');
                                                                })
                                                                ->whereIn('requisition_status', [1, 3])
                                                                ->whereNull('decision_date')
                                                                ->exists();

                                                            if ($hrAssigned && !$isCompleted) {
                                                                // Override label when HR is the current pending step
                                                                $isHRProcessing = true;
                                                                $statusInfo = [
                                                                    'label' => 'Pending HR Review',
                                                                    'class' => 'bg-info text-white',
                                                                ];
                                                                $showStatus = true;
                                                            } else {
                                                                $statusInfo = getRequisitionStatusLabel(
                                                                    $latestHistory->requisition_status,
                                                                );

                                                                // Only show status if it's Pending or Approved/Completed
                                                                $statusLabel = strtolower($statusInfo['label']);
                                                                if (
                                                                    strpos($statusLabel, 'pending') !== false ||
                                                                    strpos($statusLabel, 'approved') !== false ||
                                                                    strpos($statusLabel, 'completed') !== false
                                                                ) {
                                                                    $showStatus = true;
                                                                }
                                                            }
                                                        }
                                                    }
                                                }
                                            @endphp
                                            @if ($showStatus && $statusInfo)
                                                <span
                                                    class="badge {{ $isCompleted ? 'bg-success text-white' : ($isHRProcessing ? 'bg-info text-white' : (strpos(strtolower($statusInfo['label']), 'pending') !== false ? 'bg-warning text-dark' : 'bg-success text-white')) }}">
                                                    {{ $statusInfo['label'] }}
                                                </span>
                                            @else
                                                <span class="text-muted">—</span>
                                            @endif
                                        </td>
                                        <td>
                                            <small class="text-muted">
                                                <i class="fas fa-calendar-alt me-1 text-muted"></i>
                                                {{ $requisition->created_at?->format('Y-m-d') ?? 'N/A' }}
                                            </small>
                                        </td>
                                        <td class="text-center">
                                            <div class="d-flex gap-1 justify-content-center">
                                                <a href="{{ route('requisitions.show', $requisition->id) }}"
                                                    class="btn btn-sm btn-outline-success" title="View Details">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    {{-- Empty state is handled by DataTables drawCallback --}}
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('styles')
    <link rel="stylesheet" href="https://cdn.datatables.net/2.1.2/css/dataTables.dataTables.css" />
    <style>
        .avatar-circle {
            width: 35px;
            height: 35px;
            font-weight: bold;
            border: 1px solid #dee2e6;
            background: #f8f9fa;
            color: #6c757d;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.85rem;
        }

        .badge {
            font-size: 0.75rem;
            font-weight: 500;
            padding: 0.35em 0.65em;
        }

        .table-hover tbody tr:hover {
            background-color: #f8f9fa;
            transition: background-color 0.2s;
        }

        .card {
            border: none;
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
        }

        .progress-indicator {
            min-width: 180px;
        }

        .progress {
            background-color: #e9ecef;
            border-radius: 4px;
        }

        .progress-bar {
            transition: width 0.6s ease;
        }
    </style>
@endpush

@push('scripts')
    <script src="https://cdn.datatables.net/2.1.2/js/dataTables.js"></script>
    <script>
        @php
            $user = auth()->user();
            $canCreate = $user->can('create new requisition') || 
                         $user->hasRole('line-manager') || 
                         $user->hasAnyRole(['coo', 'cms', 'cfo', 'crhdo', 'chief_accountant']);
            $createButtonHtml = $canCreate ? '<a href="' . route('requisitions.create') . '" class="btn btn-success mt-2"><i class="fas fa-plus me-1"></i> Create Requisition</a>' : '';
        @endphp
        
        document.addEventListener('DOMContentLoaded', function() {
            // Store create button HTML in JavaScript variable
            var createButtonHtml = @json($createButtonHtml);
            
            // Initialize DataTable
            const table = new DataTable('#requisitionsTable', {
                responsive: true,
                pageLength: 25,
                lengthMenu: [
                    [10, 25, 50, 100, -1],
                    [10, 25, 50, 100, 'All']
                ],
                order: [
                    [4, 'desc'] // Order by Submitted date (column 4)
                ],
                columnDefs: [{
                    orderable: false,
                    targets: [0, 5] // # (column 0) and Actions (column 5) columns are not sortable
                }],
                language: {
                    searchPlaceholder: 'Search requisitions...',
                    emptyTable: 'No requisitions found',
                    info: 'Showing _START_ to _END_ of _TOTAL_ entries',
                    search: 'Search:',
                    lengthMenu: 'Show _MENU_ entries'
                },
                drawCallback: function(settings) {
                    // Custom empty state message
                    var api = this.api();
                    if (api.data().length === 0) {
                        var emptyMessage = '<div class="text-center py-5">' +
                            '<i class="fas fa-file-alt fa-3x text-muted mb-3 d-block"></i>' +
                            '<h5 class="text-muted">No Requisitions Found</h5>' +
                            '<p class="text-muted">Create your first requisition to get started.</p>' +
                            createButtonHtml +
                            '</div>';
                        
                        $(api.table().body()).html('<tr><td colspan="6" class="text-center">' + emptyMessage + '</td></tr>');
                    }
                }
            });
        });
    </script>
@endpush
