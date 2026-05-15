@extends('layouts.template')

@push('styles')
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" />
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
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>

{{-- Archive Comment Modal --}}
<div class="modal fade" id="hecArchiveCommentModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Archive Comment</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="small text-muted mb-2">Contract: <span class="fw-semibold" id="hecArchiveCommentContractNumber">—</span></div>
                <div class="border rounded p-2" style="background:#f8f9fa;white-space:pre-wrap;" id="hecArchiveCommentText">—</div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
        @endif

        {{-- Main Table Card --}}
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white border-bottom">
                <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
                    <div>
                        <h5 class="mb-1 text-dark"><i class="fas fa-file-contract me-2 text-success"></i>HEC Contracts</h5>
                        <div class="text-muted small">
                            <i class="fas fa-chart-bar me-1 text-success"></i>
                            Total: <strong>{{ $totalContracts }}</strong>
                            &nbsp;|&nbsp; Active: <strong class="text-success">{{ $activeContracts }}</strong>
                            &nbsp;|&nbsp; Soon to Expire: <strong class="text-warning">{{ $soonToExpireContracts }}</strong>
                            &nbsp;|&nbsp; Expired: <strong class="text-danger">{{ $expiredContracts }}</strong>
                        </div>
                    </div>
                    @if($isManager)
                    <div class="d-flex gap-2">
                        <button class="btn btn-outline-success btn-sm" data-bs-toggle="modal" data-bs-target="#hecExportModal">
                            <i class="fas fa-file-excel me-1"></i> Export
                        </button>
                        <a href="{{ route('hec-contracts.create') }}" class="btn btn-success btn-sm">
                            <i class="fas fa-plus me-1"></i> Add Contract
                        </a>
                    </div>
                    @endif
                </div>
            </div>
            <div class="card-body">
                {{-- Total value summary --}}
                @php
                    $draftContracts = \App\Models\HecContract::where('status','draft')->count();
                @endphp
                {{-- Single row: value summary + filter buttons --}}
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3 px-3 py-2 rounded" style="background:#f8f9fa;border:1px solid #e9ecef">
                    <div class="d-flex gap-3 small">
                        <span><span class="text-muted">Total Portfolio:</span> <strong>{{ number_format($totalValue ?? 0, 2) }} TZS</strong></span>
                        <span><span class="text-muted">Active Value:</span> <strong class="text-success">{{ number_format($activeValue ?? 0, 2) }} TZS</strong></span>
                    </div>
                    <div class="d-flex flex-wrap gap-2">
                        <button class="btn btn-sm btn-outline-secondary status-filter active-filter" data-status="">All (excl. Renewed)</button>
                        <button class="btn btn-sm btn-outline-success status-filter" data-status="active">Active <span class="badge bg-success ms-1">{{ $activeContracts }}</span></button>
                        <button class="btn btn-sm btn-outline-warning status-filter" data-status="soonToExpire">Soon to Expire <span class="badge bg-warning text-dark ms-1">{{ $soonToExpireContracts }}</span></button>
                        <button class="btn btn-sm btn-outline-danger status-filter" data-status="expired">Expired <span class="badge bg-danger ms-1">{{ $expiredContracts }}</span></button>
                        <button class="btn btn-sm btn-outline-secondary status-filter" data-status="draft">Draft <span class="badge bg-secondary ms-1">{{ $draftContracts }}</span></button>
                        @php
                            $archivedContracts = \App\Models\HecContract::where('status','archived')->count();
                            $renewedContracts  = \App\Models\HecContract::where('status','renewed')->count();
                        @endphp
                        @if($archivedContracts > 0)
                        <button class="btn btn-sm btn-outline-dark status-filter" data-status="archived">Archived <span class="badge bg-dark ms-1">{{ $archivedContracts }}</span></button>
                        @endif
                        @if($renewedContracts > 0)
                        <button class="btn btn-sm status-filter" data-status="renewed"
                            style="border:1px solid #6f42c1;color:#6f42c1;background:#fff;">
                            <i class="fas fa-redo me-1" style="font-size:.75rem;"></i>Renewed
                            <span class="badge ms-1" style="background:#6f42c1;color:#fff;">{{ $renewedContracts }}</span>
                        </button>
                        @endif
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover table-striped align-middle" id="hecContractsTable"
                           style="table-layout:fixed;width:100%">
                        <colgroup>
                            <col style="width:4%">
                            <col style="width:28%">
                            <col style="width:11%">
                            <col style="width:13%">
                            <col style="width:14%">
                            <col style="width:12%">
                            <col style="width:10%">
                            <col style="width:8%">
                        </colgroup>
                        <thead class="table-light">
                            <tr>
                                <th>#</th>
                                <th>Title</th>
                                <th>Type</th>
                                <th>Entity</th>
                                <th>Period</th>
                                <th>Cost</th>
                                <th>Status</th>
                                <th class="text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($contracts as $contract)
                                @php
                                    $today = \Carbon\Carbon::today();
                                    $endDate = $contract->end_date ? \Carbon\Carbon::parse($contract->end_date) : null;
                                    $isExpired   = $endDate && $endDate->lt($today);
                                    $isSoon      = $endDate && !$isExpired && $endDate->diffInDays($today) <= 30;
                                    $canRenew    = $contract->renewals->isEmpty()
                                                   && in_array($contract->status, ['expired','soonToExpire'])
                                                      || ($contract->renewals->isEmpty() && $isSoon);
                                    // Human-readable time helper
                                    $timeLabel = function(\Carbon\Carbon $from, \Carbon\Carbon $to) {
                                        $days   = abs($from->diffInDays($to));
                                        $months = abs($from->diffInMonths($to));
                                        $years  = abs($from->diffInYears($to));
                                        if ($years >= 1)  return $years  . ' yr'  . ($years  > 1 ? 's' : '');
                                        if ($months >= 1) return $months . ' mo'  . ($months > 1 ? 's' : '');
                                        return $days . 'd';
                                    };
                                    // Compute real status from dates (overrides stale DB value)
                                    if (in_array($contract->status, ['renewed','terminated','archived','draft'])) {
                                        $displayStatus = $contract->status; // never override these
                                    } elseif ($isExpired) {
                                        $displayStatus = 'expired';
                                    } elseif ($isSoon) {
                                        $displayStatus = 'soonToExpire';
                                    } else {
                                        $displayStatus = $contract->status;
                                    }
                                    $statusColors = [
                                        'active'      => 'success',
                                        'expired'     => 'danger',
                                        'soonToExpire'=> 'warning',
                                        'draft'       => 'secondary',
                                        'in_progress' => 'info',
                                        'renewed'     => 'primary',
                                        'terminated'  => 'dark',
                                        'archived'    => 'secondary',
                                    ];
                                    $badgeColor = $statusColors[$displayStatus] ?? 'secondary';
                                @endphp
                                <tr data-url="{{ route('hec-contracts.show', $contract->id) }}" style="cursor:pointer">
                                    <td></td>
                                    <td>
                                        {{ $contract->title }}
                                        @if($contract->contract_number)
                                            <br><small class="text-muted">{{ $contract->contract_number }}</small>
                                        @endif
                                        @if($contract->vendor)
                                            <br><small class="text-muted"><i class="fas fa-building me-1"></i>{{ Str::limit($contract->vendor->name, 30) }}</small>
                                        @endif
                                    </td>
                                    <td><span class="badge bg-secondary">{{ Str::limit($contract->contract_type, 12) }}</span></td>
                                    <td style="overflow:hidden;text-overflow:ellipsis;white-space:nowrap">
                                        {{ $contract->division?->name ?? '—' }}
                                    </td>
                                    <td class="small">
                                        {{ $contract->start_date ? $contract->start_date->format('d M Y') : '—' }}
                                        <br>
                                        <span class="{{ $isExpired ? 'text-danger fw-semibold' : ($isSoon ? 'text-warning fw-semibold' : '') }}">
                                            {{ $endDate ? $endDate->format('d M Y') : '—' }}
                                        </span>
                                    </td>
                                    <td class="small">
                                        <strong>{{ number_format($contract->cost ?? 0, 2) }}</strong>
                                        <br><small class="text-muted">{{ $contract->currency ?? 'TZS' }}</small>
                                    </td>
                                    <td>
                                        {{-- Hidden key used by DataTables column search --}}
                                        <span class="d-none hec-status-key">{{ $displayStatus }}</span>
                                        <span class="badge bg-{{ $badgeColor }}">
                                            {{ $displayStatus === 'soonToExpire' ? 'Soon to Expire' : ucfirst(str_replace('_', ' ', $displayStatus)) }}
                                        </span>
                                        @if($isExpired)
                                            <br><small class="text-danger">{{ $timeLabel($today, $endDate) }} ago</small>
                                        @elseif($isSoon)
                                            <br><small class="text-warning">{{ $timeLabel($endDate, $today) }} left</small>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <div class="d-flex gap-1 justify-content-center flex-wrap">
                                            <a href="{{ route('hec-contracts.show', $contract->id) }}"
                                               class="btn btn-sm btn-success" title="View" onclick="event.stopPropagation()">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            @if($canRenew)
                                            <a href="{{ route('hec-contracts.show', $contract->id) }}#renewModal"
                                               class="btn btn-sm btn-outline-success renew-btn"
                                               title="Renew"
                                               data-contract-id="{{ $contract->id }}"
                                               data-contract-number="{{ $contract->contract_number }}"
                                               data-end-date="{{ $contract->end_date?->format('Y-m-d') }}"
                                               data-duration="{{ $contract->duration_months ?? 12 }}"
                                               data-cost="{{ $contract->cost ?? 0 }}"
                                               data-currency="{{ $contract->currency ?? 'TZS' }}"
                                               onclick="event.stopPropagation()">
                                                <i class="fas fa-redo"></i>
                                            </a>
                                            @endif
                                            @if($isManager)
                                            <a href="{{ route('hec-contracts.edit', $contract->id) }}"
                                               class="btn btn-sm btn-outline-secondary" title="Edit" onclick="event.stopPropagation()">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <button type="button" class="btn btn-sm btn-outline-danger"
                                                data-bs-toggle="modal" data-bs-target="#hecDeleteModal"
                                                data-contract-id="{{ $contract->id }}"
                                                data-contract-number="{{ $contract->contract_number }}"
                                                title="Delete" onclick="event.stopPropagation()">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center py-4 text-muted">
                                        <i class="fas fa-inbox fa-2x mb-2 d-block"></i>No HEC contracts found.
                                        @if($isManager)
                                            <a href="{{ route('hec-contracts.create') }}" class="btn btn-success btn-sm ms-2">
                                                <i class="fas fa-plus me-1"></i> Create First
                                            </a>
                                        @endif
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>
</div>

{{-- Export Modal --}}
<div class="modal fade" id="hecExportModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="GET" action="{{ route('hec-contracts.export') }}" id="hecExportForm">
                <div class="modal-header bg-light border-bottom">
                    <h5 class="modal-title text-dark fw-semibold">
                        <i class="fas fa-file-excel me-2 text-success"></i>Export HEC Contracts
                    </h5>
                    <button type="button" class="btn-close" id="hecExportCloseBtn" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted small mb-3">
                        <i class="fas fa-info-circle me-1"></i>Select filters to narrow down the export. Leave blank for all contracts.
                    </p>
                    <div class="row g-3" id="hecExportFields">
                        <div class="col-6">
                            <label class="form-label small fw-semibold">
                                <i class="fas fa-filter me-1 text-success"></i>Status
                            </label>
                            <select name="export_status" id="hecExpStatus" class="form-select form-select-sm">
                                <option value="all">All Statuses</option>
                                <option value="active">Active</option>
                                <option value="soonToExpire">Soon to Expire</option>
                                <option value="expired">Expired</option>
                                <option value="draft">Draft</option>
                                <option value="renewed">Renewed</option>
                                <option value="archived">Archived</option>
                            </select>
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-semibold">
                                <i class="fas fa-building me-1 text-success"></i>Entity
                            </label>
                            <select name="entity" id="hecExpEntity" class="form-select form-select-sm">
                                <option value="">All Entities</option>
                                @php $entities = \App\Models\Division::orderBy('name')->pluck('name'); @endphp
                                @foreach($entities as $ent)
                                    <option value="{{ $ent }}">{{ $ent }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-semibold">
                                <i class="fas fa-file-contract me-1 text-success"></i>Contract Type
                            </label>
                            <select name="contract_type" id="hecExpType" class="form-select form-select-sm">
                                <option value="">All Types</option>
                                @php $types = \App\Models\HecContract::distinct()->pluck('contract_type')->filter()->sort(); @endphp
                                @foreach($types as $type)
                                    <option value="{{ $type }}">{{ $type }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    {{-- Progress area --}}
                    <div id="hecExportProgress" style="display:none;" class="mt-3">
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <div class="spinner-border spinner-border-sm text-success" role="status"></div>
                            <span class="small fw-semibold text-muted" id="hecExportProgressLabel">Preparing your report…</span>
                        </div>
                        <div class="progress" style="height:6px; border-radius:3px;">
                            <div id="hecExportProgressBar" class="progress-bar progress-bar-striped progress-bar-animated bg-success" role="progressbar" style="width:0%"></div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary btn-sm" id="hecExportCancelBtn" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success btn-sm" id="hecExportSubmitBtn">
                        <i class="fas fa-download me-1"></i> Download Excel
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Delete Modal --}}
<div class="modal fade" id="hecDeleteModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Delete HEC Contract</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete contract <strong><span id="hecDeleteContractNumber" class="text-danger"></span></strong>?</p>
                <small class="text-muted">This action cannot be undone.</small>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                <form id="hecDeleteForm" method="POST" class="d-inline">
                    @csrf @method('DELETE')
                    <button type="submit" class="btn btn-danger btn-sm"><i class="fas fa-trash me-1"></i>Delete</button>
                </form>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
<script>
// Filter state
window.hecStatusFilter = ''; // '' = all except renewed

// Custom search: filter by .hec-status-key span content
$.fn.dataTable.ext.search.push(function(settings, data, dataIndex, row) {
    if (settings.nTable.id !== 'hecContractsTable') return true;
    var rowNode = $(settings.nTable).DataTable().row(dataIndex).node();
    var key = $(rowNode).find('.hec-status-key').text().trim();
    var filter = window.hecStatusFilter;
    if (filter === '') {
        // Default: hide renewed
        return key !== 'renewed';
    }
    return key === filter;
});

$(document).ready(function () {
    const dt = $('#hecContractsTable').DataTable({
        pageLength: 25,
        lengthChange: false,
        order: [[1,'asc']],
        autoWidth: false,
        columnDefs: [{ orderable: false, targets: [0, 7] }],
        language: { search: 'Search contracts:' },
        drawCallback: function(settings) {
            var api = this.api();
            api.column(0, { search: 'applied', order: 'applied' }).nodes().each(function(cell, i) {
                cell.innerHTML = (api.page() * api.page.len()) + i + 1;
            });
        }
    });

    // Status filter buttons
    $('.status-filter').on('click', function () {
        $('.status-filter').removeClass('active-filter');
        $(this).addClass('active-filter');
        window.hecStatusFilter = $(this).data('status');
        dt.draw();
    });

    // Clickable rows
    $('#hecContractsTable tbody').on('click', 'tr[data-url]', function () {
        window.location.href = $(this).data('url');
    });

    // Delete modal
    document.getElementById('hecDeleteModal').addEventListener('show.bs.modal', function (e) {
        const btn = e.relatedTarget;
        document.getElementById('hecDeleteContractNumber').textContent = btn.dataset.contractNumber || '—';
        document.getElementById('hecDeleteForm').action = "{{ url('hec-contracts') }}/" + btn.dataset.contractId;
    });

    // Archive comment modal
    document.getElementById('hecArchiveCommentModal').addEventListener('show.bs.modal', function (e) {
        const btn = e.relatedTarget;
        document.getElementById('hecArchiveCommentContractNumber').textContent = btn.dataset.contractNumber || '—';
        document.getElementById('hecArchiveCommentText').textContent = btn.dataset.comment || '—';
    });

    // ===== Export Modal =====
    $('#hecExportModal').on('show.bs.modal', function() {
        // Sync active filter into modal status dropdown
        var activeFilter = window.hecStatusFilter || '';
        if (activeFilter) {
            $('#hecExpStatus').val(activeFilter);
        } else {
            $('#hecExpStatus').val('all');
        }
        $('#hecExpEntity').val('');
        $('#hecExpType').val('');
        // Reset progress
        $('#hecExportFields').show();
        $('#hecExportProgress').hide();
        $('#hecExportSubmitBtn').prop('disabled', false).html('<i class="fas fa-download me-1"></i> Download Excel');
        $('#hecExportCloseBtn, #hecExportCancelBtn').prop('disabled', false);
    });

    $('#hecExportSubmitBtn').on('click', function(e) {
        e.preventDefault();
        var params = $('#hecExportForm').serialize();
        var url = $('#hecExportForm').attr('action') + '?' + params;

        $('#hecExportProgress').show();
        $(this).prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i> Generating…');
        $('#hecExportCloseBtn, #hecExportCancelBtn').prop('disabled', true);

        var bar = $('#hecExportProgressBar');
        var progress = 0;
        var interval = setInterval(function() {
            progress += Math.random() * 15;
            if (progress > 90) progress = 90;
            bar.css('width', progress + '%');
        }, 300);

        fetch(url, { credentials: 'same-origin' }).then(function(response) {
            return response.blob();
        }).then(function(blob) {
            clearInterval(interval);
            bar.css('width', '100%');
            $('#hecExportProgressLabel').text('Download complete!');
            var a = document.createElement('a');
            a.href = window.URL.createObjectURL(blob);
            a.download = 'HEC_Contracts_Report_' + new Date().toISOString().slice(0,10) + '.xlsx';
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            window.URL.revokeObjectURL(a.href);
            setTimeout(function() {
                var modal = bootstrap.Modal.getInstance(document.getElementById('hecExportModal'));
                if (modal) modal.hide();
            }, 800);
        }).catch(function() {
            clearInterval(interval);
            bar.css('width', '100%');
            $('#hecExportProgressLabel').text('Error generating report.');
            $('#hecExportCloseBtn, #hecExportCancelBtn').prop('disabled', false);
            $('#hecExportSubmitBtn').prop('disabled', false).html('<i class="fas fa-download me-1"></i> Download Excel');
        });
    });
});
</script>
@endpush
@endsection
