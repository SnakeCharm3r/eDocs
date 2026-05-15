@extends('layouts.template')

@push('styles')
<style>
    .edit-section { border:0; border-radius:10px; box-shadow:0 1px 6px rgba(0,0,0,.07); margin-bottom:1rem; }
    .edit-section .card-header { background:#f8fdf9; border-bottom:1px solid #d1e7dd; border-radius:10px 10px 0 0 !important; padding:.55rem 1rem; }
    .edit-section .card-header h6 { margin:0; font-size:.82rem; font-weight:600; color:#198754; }
    .form-label { font-size:.82rem; font-weight:600; color:#495057; }
    .form-label .text-danger { font-weight:400; }
    .current-doc { font-size:.78rem; padding:.35rem .6rem; background:#f8f9fa; border:1px solid #e9ecef; border-radius:6px; display:inline-flex; align-items:center; gap:.35rem; }
</style>
@endpush

@section('breadcrumb')
    @include('sweetalert::alert')
@endsection

@section('content')
<div class="page-wrapper">
    <div class="content container-fluid">

        {{-- Page header --}}
        <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
            <div>
                <h6 class="mb-0 text-dark fw-semibold" style="font-size:.92rem;">
                    <i class="fas fa-edit me-1 text-success"></i>Edit Contract
                </h6>
                <small class="text-muted" style="font-size:.76rem;">{{ $contract->title }} &mdash; #{{ $contract->contract_number ?? 'No Number' }}</small>
            </div>
            <div class="d-flex gap-2">
                <a href="{{ route('procurements.contracts.show', $contract->id) }}" class="btn btn-sm btn-outline-secondary" style="font-size:.78rem;">
                    <i class="fas fa-eye me-1"></i> View
                </a>
                <a href="{{ route('procurements.contracts.index') }}" class="btn btn-sm btn-outline-secondary" style="font-size:.78rem;">
                    <i class="fas fa-arrow-left me-1"></i> Back
                </a>
            </div>
        </div>

        <form action="{{ route('procurements.contracts.update', $contract->id) }}" method="POST" enctype="multipart/form-data" id="contractForm">
            @csrf
            @method('PUT')

            <div class="row g-3">
                {{-- Left column --}}
                <div class="col-lg-8">

                    {{-- Contract Information --}}
                    <div class="card edit-section">
                        <div class="card-header"><h6><i class="fas fa-file-contract me-2"></i>Contract Information</h6></div>
                        <div class="card-body py-3 px-4">
                            <div class="row g-3">
                                <div class="col-md-8">
                                    <label for="title" class="form-label">Contract Name <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control form-control-sm @error('title') is-invalid @enderror"
                                        id="title" name="title" value="{{ old('title', $contract->title) }}" maxlength="100" required
                                        oninput="document.getElementById('titleCount').textContent = this.value.length">
                                    <div class="d-flex justify-content-end">
                                        <small class="text-muted"><span id="titleCount">{{ strlen(old('title', $contract->title ?? '')) }}</span>/100</small>
                                    </div>
                                    @error('title')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                                <div class="col-md-4">
                                    <label for="contract_number" class="form-label">Contract Number</label>
                                    <input type="text" class="form-control form-control-sm @error('contract_number') is-invalid @enderror"
                                        id="contract_number" name="contract_number" value="{{ old('contract_number', $contract->contract_number) }}"
                                        placeholder="Auto-generated">
                                    @error('contract_number')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                                <div class="col-md-4">
                                    <label for="contract_type" class="form-label">Type <span class="text-danger">*</span></label>
                                    <select class="form-select form-select-sm @error('contract_type') is-invalid @enderror"
                                        id="contract_type" name="contract_type" required>
                                        <option value="">Select Type</option>
                                        @foreach(['Services','Goods','Services and Goods','Consultants'] as $type)
                                        <option value="{{ $type }}" {{ old('contract_type', $contract->contract_type) == $type ? 'selected' : '' }}>{{ $type }}</option>
                                        @endforeach
                                    </select>
                                    @error('contract_type')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                                <div class="col-md-4">
                                    <label for="status" class="form-label">Status <span class="text-danger">*</span></label>
                                    <select class="form-select form-select-sm @error('status') is-invalid @enderror"
                                        id="status" name="status" required>
                                        @foreach(['draft'=>'Draft','active'=>'Active','in_progress'=>'In Progress','expired'=>'Expired','terminated'=>'Terminated','soonToExpire'=>'Soon To Expire'] as $val => $lbl)
                                        <option value="{{ $val }}" {{ old('status', $contract->status ?? 'draft') == $val ? 'selected' : '' }}>{{ $lbl }}</option>
                                        @endforeach
                                    </select>
                                    @error('status')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                                <div class="col-md-4">
                                    <label for="renewal_status" class="form-label">Renewal Status</label>
                                    <select class="form-select form-select-sm @error('renewal_status') is-invalid @enderror"
                                        id="renewal_status" name="renewal_status">
                                        <option value="">Select</option>
                                        @foreach(['renewed'=>'Renewed','not_renewed'=>'Not Renewed','pending'=>'Pending'] as $val => $lbl)
                                        <option value="{{ $val }}" {{ old('renewal_status', $contract->renewal_status) == $val ? 'selected' : '' }}>{{ $lbl }}</option>
                                        @endforeach
                                    </select>
                                    @error('renewal_status')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                                <div class="col-12">
                                    <label for="description" class="form-label">Description</label>
                                    <textarea class="form-control form-control-sm @error('description') is-invalid @enderror"
                                        id="description" name="description" rows="3">{{ old('description', $contract->description) }}</textarea>
                                    @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Period --}}
                    <div class="card edit-section">
                        <div class="card-header"><h6><i class="fas fa-calendar-alt me-2"></i>Contract Period</h6></div>
                        <div class="card-body py-3 px-4">
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label for="start_date" class="form-label">Start Date <span class="text-danger">*</span></label>
                                    <input type="date" class="form-control form-control-sm @error('start_date') is-invalid @enderror"
                                        id="start_date" name="start_date"
                                        value="{{ old('start_date', $contract->start_date ? \Carbon\Carbon::parse($contract->start_date)->format('Y-m-d') : '') }}" required>
                                    @error('start_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                                <div class="col-md-4">
                                    <label for="end_date" class="form-label">End Date <span class="text-danger">*</span></label>
                                    <input type="date" class="form-control form-control-sm @error('end_date') is-invalid @enderror"
                                        id="end_date" name="end_date"
                                        value="{{ old('end_date', $contract->end_date ? \Carbon\Carbon::parse($contract->end_date)->format('Y-m-d') : '') }}" required>
                                    @error('end_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                                <div class="col-md-4">
                                    <label for="duration_months" class="form-label">Duration (Months) <span class="text-danger">*</span></label>
                                    <input type="number" class="form-control form-control-sm @error('duration_months') is-invalid @enderror"
                                        id="duration_months" name="duration_months"
                                        value="{{ old('duration_months', $contract->duration_months) }}"
                                        required readonly style="background:#f8f9fa;">
                                    <small class="form-text text-muted">Auto-calculated from dates</small>
                                    @error('duration_months')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Financial Details --}}
                    <div class="card edit-section">
                        <div class="card-header"><h6><i class="fas fa-coins me-2"></i>Financial Details</h6></div>
                        <div class="card-body py-3 px-4">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label for="cost_display" class="form-label">Contract Value <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control form-control-sm @error('cost') is-invalid @enderror"
                                        id="cost_display" placeholder="e.g. 1,000,000"
                                        value="{{ old('cost', $contract->cost) ? number_format(old('cost', $contract->cost), 2, '.', ',') : '' }}" required>
                                    <input type="hidden" id="cost" name="cost" value="{{ old('cost', $contract->cost) }}" required>
                                    @error('cost')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                                <div class="col-md-3">
                                    <label for="currency" class="form-label">Currency <span class="text-danger">*</span></label>
                                    <select class="form-select form-select-sm @error('currency') is-invalid @enderror"
                                        id="currency" name="currency" required>
                                        @foreach(['TZS','USD','EUR','GBP','KES','UGX'] as $cur)
                                        <option value="{{ $cur }}" {{ old('currency', $contract->currency ?? 'TZS') == $cur ? 'selected' : '' }}>{{ $cur }}</option>
                                        @endforeach
                                    </select>
                                    @error('currency')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Documents --}}
                    <div class="card edit-section">
                        <div class="card-header"><h6><i class="fas fa-paperclip me-2"></i>Documents &amp; Attachments</h6></div>
                        <div class="card-body py-3 px-4">
                            <div class="row g-3">
                                @php
                                    $docFields = [
                                        ['file_path', 'Contract Document', $contract->file_path],
                                        ['signed_contract_path', 'Signed Contract', $contract->signed_contract_path],
                                        ['terms_conditions_path', 'Terms & Conditions', $contract->terms_conditions_path],
                                        ['sla_document_path', 'SLA Document', $contract->sla_document_path],
                                    ];
                                @endphp
                                @foreach($docFields as [$field, $label, $currentPath])
                                <div class="col-md-6">
                                    <label for="{{ $field }}" class="form-label">{{ $label }} (PDF)</label>
                                    <input type="file" class="form-control form-control-sm @error($field) is-invalid @enderror"
                                        id="{{ $field }}" name="{{ $field }}" accept=".pdf">
                                    <small class="form-text text-muted">Max 10MB. Leave empty to keep current.</small>
                                    @if($currentPath)
                                    <div class="mt-1">
                                        <a href="{{ route('procurements.contracts.document', $contract->id) }}?type={{ $field }}"
                                            target="_blank" class="current-doc text-decoration-none text-success">
                                            <i class="fas fa-file-pdf"></i> View Current
                                        </a>
                                    </div>
                                    @endif
                                    @error($field)<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                                @endforeach
                            </div>
                        </div>
                    </div>

                </div>

                {{-- Right column --}}
                <div class="col-lg-4">

                    {{-- Entity & Department --}}
                    <div class="card edit-section">
                        <div class="card-header"><h6><i class="fas fa-building me-2"></i>Entity &amp; Department</h6></div>
                        <div class="card-body py-3 px-4">
                            <div class="mb-3">
                                <label for="division_id" class="form-label">CCBRT Entity <span class="text-danger">*</span></label>
                                <select class="form-select form-select-sm @error('division_id') is-invalid @enderror"
                                    id="division_id" name="division_id" required>
                                    <option value="">Select Entity</option>
                                    @foreach($divisions as $division)
                                    <option value="{{ $division->id }}" {{ old('division_id', $contract->division_id) == $division->id ? 'selected' : '' }}>
                                        {{ $division->name }}
                                    </option>
                                    @endforeach
                                </select>
                                @error('division_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="mb-3">
                                <label for="department_id" class="form-label">Department <span class="text-danger">*</span></label>
                                <select class="form-select form-select-sm @error('department_id') is-invalid @enderror"
                                    id="department_id" name="department_id" required>
                                    <option value="">Select Entity first</option>
                                </select>
                                <small class="form-text text-muted" id="department-help">Select an Entity first</small>
                                @error('department_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div>
                                <label for="vendor_id" class="form-label">Vendor / Contractor</label>
                                <select class="form-select form-select-sm @error('vendor_id') is-invalid @enderror"
                                    id="vendor_id" name="vendor_id">
                                    <option value="">Select Vendor</option>
                                    @foreach($vendors as $vendor)
                                    <option value="{{ $vendor->id }}" {{ old('vendor_id', $contract->vendor_id) == $vendor->id ? 'selected' : '' }}>
                                        {{ $vendor->name }}
                                    </option>
                                    @endforeach
                                </select>
                                @error('vendor_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>
                    </div>

                    {{-- Contract Owner --}}
                    <div class="card edit-section">
                        <div class="card-header"><h6><i class="fas fa-user-tie me-2"></i>Contract Owner</h6></div>
                        <div class="card-body py-3 px-4">
                            <label class="form-label">Line Manager</label>
                            <input type="text" class="form-control form-control-sm" id="line_manager_display" readonly
                                placeholder="Auto-detected from department" style="background:#f8f9fa;">
                            <input type="hidden" id="line_manager_id" name="line_manager_id">
                            <input type="hidden" id="contract_manager_id" name="contract_manager_id"
                                value="{{ old('contract_manager_id', $contract->contract_manager_id) }}">
                            <small class="form-text text-muted">Auto-set from selected department</small>
                        </div>
                    </div>

                    {{-- Actions --}}
                    <div class="card edit-section">
                        <div class="card-body py-3 px-4">
                            <button type="submit" class="btn btn-success btn-sm w-100 mb-2" id="submitBtn">
                                <i class="fas fa-save me-1"></i> Update Contract
                            </button>
                            <a href="{{ route('procurements.contracts.show', $contract->id) }}" class="btn btn-outline-secondary btn-sm w-100">
                                <i class="fas fa-times me-1"></i> Cancel
                            </a>
                        </div>
                    </div>

                </div>
            </div>

        </form>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const divisionSelect = document.getElementById('division_id');
    const departmentSelect = document.getElementById('department_id');
    const departmentHelp = document.getElementById('department-help');
    const lineManagerDisplay = document.getElementById('line_manager_display');
    const lineManagerId = document.getElementById('line_manager_id');
    const contractManagerId = document.getElementById('contract_manager_id');
    const startInput = document.getElementById('start_date');
    const endInput = document.getElementById('end_date');
    const durInput = document.getElementById('duration_months');

    const currentDivisionId = '{{ $contract->division_id }}';
    const currentDepartmentId = '{{ $contract->department_id }}';

    // Load departments for current division on page load
    if (currentDivisionId) {
        loadDepartments(currentDivisionId, currentDepartmentId);
    }

    divisionSelect.addEventListener('change', function() {
        const divisionId = this.value;
        departmentSelect.innerHTML = '<option value="">Select Department</option>';
        lineManagerDisplay.value = '';
        lineManagerId.value = '';
        contractManagerId.value = '';
        if (divisionId) {
            loadDepartments(divisionId);
        } else {
            departmentHelp.textContent = 'Select an Entity first';
        }
    });

    function loadDepartments(divisionId, selectedDeptId = null) {
        fetch(`{{ url('/procurements/contracts/departments') }}/${divisionId}`)
            .then(r => r.json())
            .then(departments => {
                departmentSelect.innerHTML = '<option value="">Select Department</option>';
                departments.forEach(dept => {
                    const opt = document.createElement('option');
                    opt.value = dept.id;
                    opt.textContent = dept.dept_name;
                    if (selectedDeptId && dept.id == selectedDeptId) opt.selected = true;
                    departmentSelect.appendChild(opt);
                });
                departmentHelp.textContent = departments.length ? 'Select a department' : 'No departments found';
                if (selectedDeptId) loadLineManager(selectedDeptId);
            })
            .catch(() => { departmentHelp.textContent = 'Error loading departments'; });
    }

    departmentSelect.addEventListener('change', function() {
        if (this.value) {
            loadLineManager(this.value);
        } else {
            lineManagerDisplay.value = '';
            lineManagerId.value = '';
            contractManagerId.value = '';
        }
    });

    function loadLineManager(deptId) {
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
        fetch(`{{ url('/procurements/contracts/departments') }}/${deptId}/line-manager`, {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': csrfToken }
        })
        .then(r => r.json())
        .then(data => {
            if (data.success && data.line_manager) {
                lineManagerDisplay.value = data.line_manager.name;
                lineManagerId.value = data.line_manager.id;
                contractManagerId.value = data.line_manager.id;
            } else {
                lineManagerDisplay.value = data.message || 'No line manager assigned';
                lineManagerId.value = '';
                contractManagerId.value = '';
            }
        })
        .catch(() => {
            lineManagerDisplay.value = 'Error loading line manager';
            lineManagerId.value = '';
            contractManagerId.value = '';
        });
    }

    // === Auto-calculate duration ===
    function calculateDuration() {
        if (!startInput.value || !endInput.value) { durInput.value = ''; return; }
        const start = new Date(startInput.value);
        const end = new Date(endInput.value);
        if (end <= start) { durInput.value = ''; return; }
        let months = (end.getFullYear() - start.getFullYear()) * 12 + end.getMonth() - start.getMonth();
        if (end.getDate() < start.getDate()) months--;
        durInput.value = months || 1;
    }
    startInput.addEventListener('change', calculateDuration);
    endInput.addEventListener('change', calculateDuration);

    // === Cost comma formatting ===
    const costDisplay = document.getElementById('cost_display');
    const costHidden = document.getElementById('cost');
    if (costDisplay && costHidden) {
        costDisplay.addEventListener('input', function(e) {
            let value = e.target.value.replace(/[^\d.]/g, '');
            const parts = value.split('.');
            if (parts.length > 2) value = parts[0] + '.' + parts.slice(1).join('');
            if (parts.length === 2 && parts[1].length > 2) value = parts[0] + '.' + parts[1].substring(0, 2);
            costHidden.value = value;
            if (value) {
                const num = parseFloat(value);
                if (!isNaN(num)) {
                    e.target.value = num.toLocaleString('en-US', {
                        minimumFractionDigits: value.includes('.') ? 2 : 0,
                        maximumFractionDigits: 2
                    });
                }
            }
        });
        costDisplay.addEventListener('blur', function(e) {
            if (costHidden.value) {
                const num = parseFloat(costHidden.value);
                if (!isNaN(num)) {
                    e.target.value = num.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                }
            }
        });
    }

    // === Prevent double-submit ===
    document.getElementById('contractForm').addEventListener('submit', function() {
        var btn = document.getElementById('submitBtn');
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Updating…';
    });
});
</script>
@endpush

@endsection
