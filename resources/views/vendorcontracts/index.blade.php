@extends('layouts.template')

@section('breadcrumb')
    @include('sweetalert::alert'), @include('partials.modals.deleteVendor')
@endsection

@section('content')

<div class="page-wrapper">
    <div class="content container-fluid">
        <div class="page-header">
            <div class="row align-items-center">
                <div class="col">
                    <h3 class="page-title">Contracts Management Portal</h3>
                </div>
            </div>
        </div>

        @php
            $today = \Carbon\Carbon::now();
            $soonToExpire = $today->copy()->addDays(30);

            $activeContracts = $contracts->where('end_date', '>', $today);
            $expiringSoonContracts = $contracts->whereBetween('end_date', [$today, $soonToExpire]);
            $expiredContracts = $contracts->where('end_date', '<', $today);

            function groupByCurrency($collection) {
                return $collection->groupBy('currency')->map(function($group){
                    return [
                        'contracts' => $group->count(),
                        'total_value' => $group->sum('cost')
                    ];
                });
            }

            $activeAnalytics = groupByCurrency($activeContracts);
            $expiringAnalytics = groupByCurrency($expiringSoonContracts);
            $expiredAnalytics = groupByCurrency($expiredContracts);
        @endphp

        <div class="card shadow-lg mb-4">
            <div class="card-body">
                <div class="d-flex gap-3 mb-3">
                    <button class="accordion-btn btn text-dark px-4 py-2 border border-success" data-bs-toggle="collapse" data-bs-target="#activePanel">
                        <i class="fas fa-check-circle me-1"></i> Active ({{ $activeContracts->count() }})
                    </button>

                    <button class="accordion-btn btn text-dark px-4 py-2 border border-warning" data-bs-toggle="collapse" data-bs-target="#expiringPanel">
                        <i class="fas fa-clock me-1"></i> Expiring Soon ({{ $expiringSoonContracts->count() }})
                    </button>

                    <button class="accordion-btn btn text-dark px-4 py-2 border border-danger" data-bs-toggle="collapse" data-bs-target="#expiredPanel">
                        <i class="fas fa-times-circle me-1"></i> Expired ({{ $expiredContracts->count() }})
                    </button>
                </div>

                <div class="accordion collapse" id="activePanel">
                    <table class="table table-bordered mb-0">
                        <thead class="table-success">
                            <tr>
                                <th>Currency</th>
                                <th>Contracts</th>
                                <th>Total Value</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($activeAnalytics as $currency => $data)
                            <tr>
                                <td>{{ $currency }}</td>
                                <td>{{ $data['contracts'] }}</td>
                                <td>{{ number_format($data['total_value'], 2) }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="accordion collapse" id="expiringPanel">
                    <table class="table table-bordered mb-0">
                        <thead class="table-warning">
                            <tr>
                                <th>Currency</th>
                                <th>Contracts</th>
                                <th>Total Value</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($expiringAnalytics as $currency => $data)
                            <tr>
                                <td>{{ $currency }}</td>
                                <td>{{ $data['contracts'] }}</td>
                                <td>{{ number_format($data['total_value'], 2) }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="accordion collapse" id="expiredPanel">
                    <table class="table table-bordered mb-0">
                        <thead class="table-danger">
                            <tr>
                                <th>Currency</th>
                                <th>Contracts</th>
                                <th>Total Value</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($expiredAnalytics as $currency => $data)
                            <tr>
                                <td>{{ $currency }}</td>
                                <td>{{ $data['contracts'] }}</td>
                                <td>{{ number_format($data['total_value'], 2) }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <style>
            .accordion-btn {
                background: white;
                font-weight: 600;
                border-radius: 6px;
            }
        </style>

        <div class="card shadow-lg">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center gap-3">
                    <a href="{{ route('vendorContract.upload') }}" class="btn text-dark px-4 py-2 border border-success accordion-btn d-flex align-items-center">
                        <i class="fas fa-folder-open me-2 text-success"></i>
                        Upload Existing Contracts
                    </a>

                    <a href="{{ route('vendorContract.makeContract') }}" class="btn text-dark px-4 py-2 border border-warning accordion-btn d-flex align-items-center">
                        <i class="fas fa-refresh me-2 text-warning"></i>
                        Renew Contract
                    </a>
                </div>
                <h5 class="mb-0 text-dark">Contract Records</h5>
            </div>

            <div class="card-body">
                <form id="filterForm" class="row g-3 align-items-end mb-4">
                    <div class="col-md-3">
                        <label class="form-label">Year</label>
                        <select id="filterYear" class="form-select">
                            @for($y = date('Y') - 5; $y <= date('Y'); $y++)
                                <option value="{{ $y }}">{{ $y }}</option>
                            @endfor
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Month</label>
                        <select id="filterMonth" class="form-select">
                            @for($m = 1; $m <= 12; $m++)
                                <option value="{{ $m }}">{{ date('F', mktime(0,0,0,$m,1)) }}</option>
                            @endfor
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Day</label>
                        <input type="number" id="filterDay" class="form-control" min="1" max="31">
                    </div>

                    <div class="col-md-3 d-flex gap-2 justify-content-end">
                        <button type="button" class="btn btn-primary" onclick="applyFilters()">Filter</button>
                        <button type="button" class="btn btn-success" onclick="exportTable()">Export</button>
                    </div>
                </form>

                <x-datatable id="contractsTable">
                    <x-slot name="thead">
                        <tr>
                            <th></th>
                            <th>Contract Type</th>
                            <th>Contract Title</th>
                            <th>Vendor</th>
                            <th>Currency</th>
                            <th>Department</th>
                            <th>Commencement of Contract</th>
                            <th>End of Contract</th>
                            <th>Contract Status</th>
                            <th>Contract Value</th>
                            <th>Actions</th>
                        </tr>
                    </x-slot>

                    @foreach ($contracts as $i => $c)
                        <tr>
                            <td>{{ $i + 1 }}</td>
                            <td>{{ $c->contract_type ?? 'N/A' }}</td>
                            <td>{{ $c->title }}</td>
                            <td>{{ $c->vendor->name ?? 'N/A' }}</td>
                            <td>{{ $c->currency ?? 'N/A' }}</td>
                            <td>{{ $c->department->dept_name ?? 'N/A' }}</td>
                            <td>{{ $c->created_at->format('Y-m-d') }}</td>
                            <td>{{ $c->updated_at->format('Y-m-d') }}</td>
                            <td>
                                @if ($c->end_date > $today)
                                    <span class="badge bg-success">Active</span>
                                @elseif ($c->end_date < $today)
                                    <span class="badge bg-danger">Expired</span>
                                @elseif ($c->end_date >= $today && $c->end_date <= $soonToExpire)
                                    <span class="badge bg-pending">Expiring Soon</span>
                                @endif
                            </td>
                            <td>{{ number_format($c->cost, 2) }}</td>
                            <td>
                                <a class="btn btn-sm btn-outline-primary btn-view-detail"
                                   href="#"
                                   title="View details"
                                   data-id="{{ $c->id }}"
                                   data-title="{{ e($c->title) }}"
                                   data-contract_type="{{ e($c->contract_type) }}"
                                   data-vendor="{{ e($c->vendor->name ?? '') }}"
                                   data-department="{{ e($c->department->dept_name ?? '') }}"
                                   data-status="{{ e($c->status) }}"
                                   data-renewal_status="{{ e($c->renewal_status) }}"
                                   data-likelihood="{{ e($c->likelihood_rating ?? '') }}"
                                   data-impact="{{ e($c->impact_if_not_requested ?? '') }}"
                                   data-overall_risk="{{ e($c->overall_risk ?? '') }}"
                                   data-cost="{{ $c->cost }}"
                                   data-currency="{{ e($c->currency ?? '') }}"
                                   data-creation_date="{{ optional($c->creation_date)->format('Y-m-d') }}"
                                   data-end_date="{{ optional($c->end_date)->format('Y-m-d') }}"
                                   data-duration_months="{{ $c->duration_months }}"
                                   data-creator="{{ e($c->creator->name ?? '') }}"
                                   data-updater="{{ e($c->updater->name ?? '') }}"
                                   data-file_url="{{ $c->file_path ? asset('storage/' . $c->file_path) : '' }}"
                                >
                                    <i class="fas fa-eye"></i>
                                </a>

                                <a href="#" 
                                   class="btn btn-sm btn-outline-success btn-edit" 
                                   title="Edit"
                                   data-id="{{ $c->id }}"
                                   data-title="{{ e($c->title) }}"
                                   data-contract_type="{{ e($c->contract_type) }}"
                                   data-vendor_id="{{ $c->vendor_id }}"
                                   data-department_id="{{ $c->department_id }}"
                                   data-cost="{{ $c->cost }}"
                                   data-creation_date="{{ optional($c->creation_date)->format('Y-m-d') }}"
                                   data-end_date="{{ optional($c->end_date)->format('Y-m-d') }}"
                                   data-duration_months="{{ $c->duration_months }}"
                                   data-currency="{{ $c->currency }}"
                                   data-status="{{ $c->status }}"
                                   data-file_url="{{ $c->file_path ? asset('storage/' . $c->file_path) : '' }}"
                                >
                                    <i class="fas fa-edit"></i>
                                </a>
                            </td>
                         </tr>
                     @endforeach
                </x-datatable>
            </div>
        </div>
    </div>
</div>

<!-- Edit Contract Modal - Bootstrap Structure -->
<div class="modal fade" id="editContractModal" tabindex="-1" aria-labelledby="editContractModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <form id="editContractForm" method="POST" enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="_method" value="PUT">
                <input type="hidden" name="id" id="modal_contract_id">
                <input type="hidden" name="remove_file" id="modal_remove_file" value="0">

                <div class="modal-header">
                    <h5 class="modal-title" id="editContractModalLabel">
                        <i class="fas fa-file-contract me-2"></i>Edit Contract — <span id="modal_title_label" class="fw-bold"></span>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body" style="max-height:75vh; overflow-y:auto;">
                    <div id="editContractFormErrors" class="alert alert-danger" style="display:none;">
                        <strong><i class="fas fa-exclamation-triangle me-2"></i>Please fix the following errors:</strong>
                        <ul class="mb-0 mt-2" id="editContractErrorsList"></ul>
                    </div>

                    {{-- Contract Details --}}
                    <div class="card mb-3 bg-light">
                        <div class="card-header fw-bold"><i class="fas fa-info-circle me-2"></i>Contract Details</div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-md-12">
                                    <label class="form-label">Title</label>
                                    <input type="text" id="modal_title" name="title" class="form-control">
                                </div>
                                <div class="col-md-12">
                                    <label class="form-label">Contract Type</label>
                                    <input type="text" id="modal_contract_type" name="contract_type" class="form-control">
                                </div>
                                <div class="col-md-12">
                                    <label class="form-label">Vendor</label>
                                    <select id="modal_vendor_id" name="vendor_id" class="form-select">
                                        <option value="">-- choose --</option>
                                        @foreach(\App\Models\CcbrtVendor::all() as $v)
                                            <option value="{{ $v->id }}">{{ $v->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-12">
                                    <label class="form-label">Department</label>
                                    <select id="modal_department_id" name="department_id" class="form-select">
                                        <option value="">-- choose --</option>
                                        @foreach(\App\Models\Departments::all() as $d)
                                            <option value="{{ $d->id }}">{{ $d->dept_name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-12">
                                    <label class="form-label">Status</label>
                                    <input type="text" id="modal_status" name="status" class="form-control">
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Financials --}}
                    <div class="card mb-3 bg-light">
                        <div class="card-header fw-bold"><i class="fas fa-dollar-sign me-2"></i>Financials</div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Cost</label>
                                    <input type="number" step="0.01" id="modal_cost" name="cost" class="form-control">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Currency</label>
                                    <input type="text" id="modal_currency" name="currency" class="form-control">
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Timeline --}}
                    <div class="card mb-3 bg-light">
                        <div class="card-header fw-bold"><i class="fas fa-calendar-alt me-2"></i>Timeline</div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Start Date</label>
                                    <input type="date" id="modal_creation_date" name="creation_date" class="form-control">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">End Date</label>
                                    <input type="date" id="modal_end_date" name="end_date" class="form-control">
                                </div>
                                <div class="col-md-12">
                                    <label class="form-label">Duration (months)</label>
                                    <input type="number" id="modal_duration_months" name="duration_months" class="form-control">
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Risk & Impact --}}
                    <div class="card mb-3 bg-light">
                        <div class="card-header fw-bold"><i class="fas fa-exclamation-triangle me-2"></i>Risk & Impact</div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Likelihood Rating</label>
                                    <input type="text" id="modal_likelihood" name="likelihood" class="form-control">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Overall Risk</label>
                                    <input type="text" id="modal_overall_risk" name="overall_risk" class="form-control">
                                </div>
                                <div class="col-md-12">
                                    <label class="form-label">Impact if Not Requested</label>
                                    <textarea id="modal_impact" name="impact" class="form-control" rows="3"></textarea>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- File --}}
                    <div class="card mb-3 bg-light">
                        <div class="card-header fw-bold"><i class="fas fa-paperclip me-2"></i>Documents</div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-md-12">
                                    <label class="form-label">Replace File (optional)</label>
                                    <input type="file" id="modal_file" name="file_path" class="form-control">
                                </div>
                                <div class="col-md-12 text-center">
                                    <div id="modal_file_icon" class="mb-2">
                                        <div class="text-muted small">No file</div>
                                    </div>
                                    <div id="modal_file_name" class="mb-2 small text-truncate"></div>
                                    <div class="d-flex gap-2 justify-content-center">
                                        <a id="modal_file_open" href="#" target="_blank" class="btn btn-sm btn-outline-primary" style="display:none;">Open</a>
                                        <button type="button" id="modal_delete_file_btn" class="btn btn-sm btn-danger" style="display:none;">Delete</button>
                                    </div>
                                    <div id="modal_delete_hint" class="text-muted small mt-2" style="display:none;">File will be removed on save</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i>Cancel
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-1"></i>Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    // helper: return icon html based on extension
    function fileIconHtml(filename) {
        if (!filename) return '<div class="text-muted small">No file</div>';
        const ext = (filename.split('.').pop() || '').toLowerCase();
        if (ext === 'pdf') return '<i class="fas fa-file-pdf fa-3x text-danger"></i>';
        if (['jpg','jpeg','png','gif','bmp','webp'].includes(ext)) return '<i class="fas fa-file-image fa-3x text-success"></i>';
        if (['doc','docx'].includes(ext)) return '<i class="fas fa-file-word fa-3x text-primary"></i>';
        if (['xls','xlsx','csv'].includes(ext)) return '<i class="fas fa-file-excel fa-3x text-success"></i>';
        return '<i class="fas fa-file-alt fa-3x text-secondary"></i>';
    }

    document.addEventListener('DOMContentLoaded', function () {
        const editButtons = document.querySelectorAll('.btn-edit');
        const editModalEl = document.getElementById('editContractModal');
        const bsModal = new bootstrap.Modal(editModalEl);
        const form = document.getElementById('editContractForm');

        const fileInput = document.getElementById('modal_file');
        const fileIcon = document.getElementById('modal_file_icon');
        const fileNameEl = document.getElementById('modal_file_name');
        const fileOpenBtn = document.getElementById('modal_file_open');
        const deleteBtn = document.getElementById('modal_delete_file_btn');
        const removeFileInput = document.getElementById('modal_remove_file');
        const deleteHint = document.getElementById('modal_delete_hint');

        // When user selects a local file, show icon + filename (no inline embed)
        fileInput.addEventListener('change', function () {
            const f = this.files && this.files[0];
            if (!f) {
                // restore existing preview if any
                const existing = form.dataset.originalFileName || '';
                fileIcon.innerHTML = fileIconHtml(existing);
                fileNameEl.textContent = existing ? existing : '';
                fileOpenBtn.style.display = form.dataset.originalFileUrl ? 'inline-block' : 'none';
                removeFileInput.value = '0';
                deleteHint.style.display = 'none';
                deleteBtn.style.display = form.dataset.originalFileUrl ? 'inline-flex' : 'none';
                return;
            }
            fileIcon.innerHTML = fileIconHtml(f.name);
            fileNameEl.textContent = f.name;
            fileOpenBtn.style.display = 'none'; // new file not yet uploaded
            removeFileInput.value = '0';
            deleteHint.style.display = 'none';
            deleteBtn.style.display = 'inline-flex';
        });

        // delete existing file button behaviour (marks removal)
        deleteBtn.addEventListener('click', function () {
            if (!confirm('Remove existing file from this contract? This will take effect on Save.')) return;
            removeFileInput.value = '1';
            fileInput.value = '';
            form.dataset.originalFileUrl = '';
            form.dataset.originalFileName = '';
            fileIcon.innerHTML = fileIconHtml('');
            fileNameEl.textContent = '';
            fileOpenBtn.style.display = 'none';
            this.style.display = 'none';
            deleteHint.style.display = 'inline';
        });

        // Edit button click handlers
        editButtons.forEach(btn => {
            btn.addEventListener('click', function (e) {
                e.preventDefault();
                const id = this.dataset.id;

                // Populate basic fields
                document.getElementById('modal_contract_id').value = id;
                document.getElementById('modal_title').value = this.dataset.title || '';
                document.getElementById('modal_title_label').textContent = this.dataset.title || '';
                document.getElementById('modal_contract_type').value = this.dataset.contract_type || '';
                document.getElementById('modal_vendor_id').value = this.dataset.vendor_id || '';
                document.getElementById('modal_department_id').value = this.dataset.department_id || '';
                document.getElementById('modal_status').value = this.dataset.status || '';
                document.getElementById('modal_cost').value = this.dataset.cost || '';
                document.getElementById('modal_currency').value = this.dataset.currency || '';
                document.getElementById('modal_creation_date').value = this.dataset.creation_date || '';
                document.getElementById('modal_end_date').value = this.dataset.end_date || '';
                document.getElementById('modal_duration_months').value = this.dataset.duration_months || '';
                document.getElementById('modal_likelihood').value = this.dataset.likelihood || '';
                document.getElementById('modal_overall_risk').value = this.dataset.overall_risk || '';
                document.getElementById('modal_impact').value = this.dataset.impact || '';

                // file info (use dataset.file_url if present)
                const fileUrl = this.dataset.file_url || '';
                let fileName = '';
                if (fileUrl) {
                    // try to extract filename from URL
                    try { fileName = decodeURIComponent(fileUrl.split('/').pop().split('?')[0]); } catch (err) { fileName = fileUrl; }
                }

                form.dataset.originalFileUrl = fileUrl;
                form.dataset.originalFileName = fileName;

                fileIcon.innerHTML = fileIconHtml(fileName);
                fileNameEl.textContent = fileName;
                fileOpenBtn.href = fileUrl;
                fileOpenBtn.style.display = fileUrl ? 'inline-block' : 'none';
                deleteBtn.style.display = fileUrl ? 'inline-flex' : 'none';
                deleteHint.style.display = 'none';
                removeFileInput.value = '0';

                // clear chosen file input
                fileInput.value = '';

                // set form action to update endpoint
                form.action = '/vendor-contracts/' + id;
                
                // Show Bootstrap modal
                bsModal.show();
            });
        });

        // Form submission handler - add AJAX submission if needed
        form.addEventListener('submit', function(e) {
            // If you want to handle form submission via AJAX, uncomment below:
            /*
            e.preventDefault();
            
            const formData = new FormData(this);
            
            fetch(this.action, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    bsModal.hide();
                    // Show success message
                    location.reload(); // or update table dynamically
                } else {
                    // Show errors
                    showFormErrors(data.errors);
                }
            })
            .catch(error => {
                console.error('Error:', error);
            });
            */
        });

        // view details button handler
        document.querySelectorAll('.btn-view-detail').forEach(btn => {
          btn.addEventListener('click', function (e) {
            e.preventDefault();
            const d = this.dataset;

            document.getElementById('detail_title').textContent = d.title || '';
            document.getElementById('detail_contract_type').textContent = d.contract_type || '';
            document.getElementById('detail_vendor').textContent = d.vendor || '';
            document.getElementById('detail_department').textContent = d.department || '';
            document.getElementById('detail_status').textContent = d.status || '';
            document.getElementById('detail_renewal_status').textContent = d.renewal_status || '';
            document.getElementById('detail_likelihood').textContent = d.likelihood || '';
            document.getElementById('detail_impact').textContent = d.impact || '';
            document.getElementById('detail_overall_risk').textContent = d.overall_risk || '';
            document.getElementById('detail_cost').textContent = d.cost ? Number(d.cost).toFixed(2) : '';
            document.getElementById('detail_currency').textContent = d.currency || '';
            document.getElementById('detail_start').textContent = d.creation_date || '';
            document.getElementById('detail_end').textContent = d.end_date || '';
            document.getElementById('detail_duration').textContent = d.duration_months || '';

            // file preview area (use file_url data-attr)
            const filePreview = document.getElementById('detail_file_preview');
            const fileLink = document.getElementById('detail_file_link');
            filePreview.innerHTML = '<div class="text-muted small">No file</div>';
            fileLink.style.display = 'none';
            if (d.file_url) {
              const url = d.file_url;
              const filename = decodeURIComponent(url.split('/').pop().split('?')[0] || '');
              const iconHtml = fileIconHtml(filename);
              filePreview.innerHTML = `<div class="d-flex align-items-center gap-3"><div class="text-center">${iconHtml}</div><div><div class="fw-semibold">${filename}</div><div class="small text-muted">Attached from database</div></div></div>`;
              fileLink.href = url;
              fileLink.style.display = 'inline-block';
            }

            new bootstrap.Modal(document.getElementById('viewContractModal')).show();
          });
        });

        // Filter functions (placeholder implementations)
        function applyFilters() {
            const year = document.getElementById('filterYear').value;
            const month = document.getElementById('filterMonth').value;
            const day = document.getElementById('filterDay').value;
            
            console.log('Applying filters:', { year, month, day });
            // Implement your filter logic here
            // This would typically reload the datatable with new parameters
            // or make an AJAX call to filter the results
        }

        function exportTable() {
            console.log('Exporting table data...');
            // Implement your export logic here
            // This could export to CSV, Excel, or PDF
        }

        // Error display function for form validation
        function showFormErrors(errors) {
            const errorDiv = document.getElementById('editContractFormErrors');
            const errorList = document.getElementById('editContractErrorsList');
            
            errorList.innerHTML = '';
            if (errors && Object.keys(errors).length > 0) {
                for (const [field, messages] of Object.entries(errors)) {
                    const li = document.createElement('li');
                    li.textContent = `${field}: ${messages.join(', ')}`;
                    errorList.appendChild(li);
                }
                errorDiv.style.display = 'block';
            } else {
                errorDiv.style.display = 'none';
            }
        }

        // Clear errors when modal is hidden
        editModalEl.addEventListener('hidden.bs.modal', function () {
            document.getElementById('editContractFormErrors').style.display = 'none';
        });
    });
</script>

@endsection
