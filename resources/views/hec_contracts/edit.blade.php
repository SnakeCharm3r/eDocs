@extends('layouts.template')

@push('styles')
<style>
    .edit-section { border:0; border-radius:10px; box-shadow:0 1px 6px rgba(0,0,0,.07); margin-bottom:1rem; }
    .edit-section .card-header { background:#f8fdf9; border-bottom:1px solid #d1e7dd; border-radius:10px 10px 0 0 !important; padding:.65rem 1rem; }
    .edit-section .card-header h6 { margin:0; font-size:.85rem; font-weight:700; color:#198754; }
    .form-label { font-size:.82rem; font-weight:600; color:#495057; }
    .form-label .text-danger { font-weight:400; }
    .current-doc-badge { display:inline-flex; align-items:center; gap:5px; padding:4px 10px; border:1px solid #d1e7dd; border-radius:6px; font-size:.78rem; color:#198754; background:#f8fdf9; text-decoration:none; transition:.15s; }
    .current-doc-badge:hover { background:#198754; color:#fff; border-color:#198754; }
    .current-doc-badge i { font-size:.75rem; }
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
                <h5 class="mb-0 text-dark fw-bold">
                    <i class="fas fa-edit me-2 text-primary"></i>Edit HEC Contract
                </h5>
                <small class="text-muted">{{ $contract->contract_number ?? 'No Number' }} — {{ $contract->title }}</small>
            </div>
            <div class="d-flex gap-2">
                <a href="{{ route('hec-contracts.show', $contract->id) }}" class="btn btn-sm btn-outline-secondary">
                    <i class="fas fa-eye me-1"></i> View
                </a>
                <a href="{{ route('hec-contracts.index') }}" class="btn btn-sm btn-outline-secondary">
                    <i class="fas fa-arrow-left me-1"></i> Back to List
                </a>
            </div>
        </div>

        <form action="{{ route('hec-contracts.update', $contract->id) }}" method="POST" enctype="multipart/form-data" id="contractForm">
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
                                        id="title" name="title" value="{{ old('title', $contract->title) }}" required>
                                    @error('title')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                                <div class="col-md-4">
                                    <label for="contract_number" class="form-label">Contract Number</label>
                                    <input type="text" class="form-control form-control-sm @error('contract_number') is-invalid @enderror"
                                        id="contract_number" name="contract_number"
                                        value="{{ old('contract_number', $contract->contract_number) }}"
                                        placeholder="Auto-generated">
                                    @error('contract_number')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                                <div class="col-md-4">
                                    <label for="contract_source" class="form-label">Source <span class="text-danger">*</span></label>
                                    <select class="form-select form-select-sm @error('contract_source') is-invalid @enderror"
                                        id="contract_source" name="contract_source" required>
                                        <option value="new" {{ old('contract_source', $contract->contract_source ?? 'new') == 'new' ? 'selected' : '' }}>New Contract</option>
                                        <option value="existing" {{ old('contract_source', $contract->contract_source) == 'existing' ? 'selected' : '' }}>Existing Contract</option>
                                    </select>
                                    @error('contract_source')<div class="invalid-feedback">{{ $message }}</div>@enderror
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
                                        @foreach(['draft'=>'Draft','active'=>'Active','in_progress'=>'In Progress','renewed'=>'Renewed','expired'=>'Expired','terminated'=>'Terminated','soonToExpire'=>'Soon To Expire'] as $val => $lbl)
                                        <option value="{{ $val }}" {{ old('status', $contract->status) == $val ? 'selected' : '' }}>{{ $lbl }}</option>
                                        @endforeach
                                    </select>
                                    @error('status')<div class="invalid-feedback">{{ $message }}</div>@enderror
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
                                    <label for="start_date" class="form-label">Start Date</label>
                                    <input type="date" class="form-control form-control-sm @error('start_date') is-invalid @enderror"
                                        id="start_date" name="start_date"
                                        value="{{ old('start_date', $contract->start_date?->format('Y-m-d') ?? '') }}">
                                    @error('start_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                                <div class="col-md-4">
                                    <label for="end_date" class="form-label">End Date</label>
                                    <input type="date" class="form-control form-control-sm @error('end_date') is-invalid @enderror"
                                        id="end_date" name="end_date"
                                        value="{{ old('end_date', $contract->end_date?->format('Y-m-d') ?? '') }}">
                                    @error('end_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                                <div class="col-md-4">
                                    <label for="duration_months" class="form-label">Duration (Months) <span class="text-danger">*</span></label>
                                    <input type="number" class="form-control form-control-sm @error('duration_months') is-invalid @enderror"
                                        id="duration_months" name="duration_months"
                                        value="{{ old('duration_months', $contract->duration_months) }}" required readonly
                                        style="background:#f8f9fa;">
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
                                        value="{{ old('cost', $contract->cost ? number_format($contract->cost, 2, '.', ',') : '') }}" required>
                                    <input type="hidden" id="cost" name="cost" value="{{ old('cost', $contract->cost) }}" required>
                                    @error('cost')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                                <div class="col-md-3">
                                    <label for="currency" class="form-label">Currency <span class="text-danger">*</span></label>
                                    <select class="form-select form-select-sm @error('currency') is-invalid @enderror"
                                        id="currency" name="currency" required>
                                        @foreach(['TZS','USD','EUR','GBP','KES','UGX','ZAR','INR','CNY','CAD','AUD'] as $cur)
                                        <option value="{{ $cur }}" {{ old('currency', $contract->currency ?? 'TZS') == $cur ? 'selected' : '' }}>{{ $cur }}</option>
                                        @endforeach
                                    </select>
                                    @error('currency')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                                <div class="col-md-3">&nbsp;</div>
                                <div class="col-md-6">
                                    <label for="impact_if_not_requested" class="form-label">Impact if Not Requested <span class="text-danger">*</span></label>
                                    <select class="form-select form-select-sm @error('impact_if_not_requested') is-invalid @enderror"
                                        id="impact_if_not_requested" name="impact_if_not_requested" required>
                                        <option value="">Select Impact</option>
                                        @foreach(['Low','Medium','High'] as $opt)
                                        <option value="{{ $opt }}" {{ old('impact_if_not_requested', $contract->impact_if_not_requested) == $opt ? 'selected' : '' }}>{{ $opt }}</option>
                                        @endforeach
                                    </select>
                                    @error('impact_if_not_requested')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                                <div class="col-md-6">
                                    <label for="likelihood_rating" class="form-label">Likelihood of Renewal <span class="text-danger">*</span></label>
                                    <select class="form-select form-select-sm @error('likelihood_rating') is-invalid @enderror"
                                        id="likelihood_rating" name="likelihood_rating" required>
                                        <option value="">Select Likelihood</option>
                                        @foreach(['Low','Medium','High'] as $opt)
                                        <option value="{{ $opt }}" {{ old('likelihood_rating', $contract->likelihood_rating) == $opt ? 'selected' : '' }}>{{ $opt }}</option>
                                        @endforeach
                                    </select>
                                    @error('likelihood_rating')<div class="invalid-feedback">{{ $message }}</div>@enderror
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
                                @foreach($docFields as [$field, $label, $current])
                                <div class="col-md-6">
                                    <label for="{{ $field }}" class="form-label">{{ $label }} (PDF)</label>
                                    @if($current)
                                    <div class="mb-2">
                                        <a href="{{ asset('storage/'.$current) }}" target="_blank" class="current-doc-badge">
                                            <i class="fas fa-file-pdf text-danger"></i> View Current
                                        </a>
                                    </div>
                                    @endif
                                    <input type="file" class="form-control form-control-sm @error($field) is-invalid @enderror"
                                        id="{{ $field }}" name="{{ $field }}" accept=".pdf">
                                    <small class="form-text text-muted">Max 10MB{{ $current ? ' — leave empty to keep current' : '' }}</small>
                                    @error($field)<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                                @endforeach
                            </div>
                        </div>
                    </div>

                </div>

                {{-- Right column --}}
                <div class="col-lg-4">

                    {{-- Entity & Vendor --}}
                    <div class="card edit-section">
                        <div class="card-header"><h6><i class="fas fa-building me-2"></i>Entity &amp; Vendor</h6></div>
                        <div class="card-body py-3 px-4">
                            <div class="mb-3">
                                <label for="division_id" class="form-label">CCBRT Entity</label>
                                <select class="form-select form-select-sm @error('division_id') is-invalid @enderror"
                                    id="division_id" name="division_id">
                                    <option value="">Select Entity (Optional)</option>
                                    @foreach($divisions ?? [] as $division)
                                    <option value="{{ $division->id }}" {{ old('division_id', $contract->division_id) == $division->id ? 'selected' : '' }}>
                                        {{ $division->name }}@if($division->code) ({{ $division->code }})@endif
                                    </option>
                                    @endforeach
                                </select>
                                @error('division_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div>
                                <label for="vendor_id" class="form-label">Vendor</label>
                                <select class="form-select form-select-sm @error('vendor_id') is-invalid @enderror"
                                    id="vendor_id" name="vendor_id">
                                    <option value="">Select Vendor (Optional)</option>
                                    @foreach($vendors ?? [] as $vendor)
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
                            <div class="mb-3">
                                <label for="contract_owner_id" class="form-label">HEC Member</label>
                                <select class="form-select form-select-sm @error('contract_owner_id') is-invalid @enderror"
                                    id="contract_owner_id" name="contract_owner_id">
                                    <option value="">None / External (use email)</option>
                                    @forelse($hecMembers ?? [] as $member)
                                    <option value="{{ $member->id }}" {{ old('contract_owner_id', $contract->contract_owner_id) == $member->id ? 'selected' : '' }}>
                                        {{ $member->fname }} {{ $member->mname }} {{ $member->lname }} ({{ $member->email }})
                                    </option>
                                    @empty
                                    <option value="" disabled>No HEC members found</option>
                                    @endforelse
                                </select>
                                <small class="form-text text-muted">COO, CFO, CMS, CCDRO</small>
                                @error('contract_owner_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div id="owner_email_container" style="display:none;">
                                <label for="owner_email" class="form-label">Owner Email <span class="text-danger">*</span></label>
                                <input type="email" class="form-control form-control-sm @error('owner_email') is-invalid @enderror"
                                    id="owner_email" name="owner_email"
                                    value="{{ old('owner_email', $contract->owner_email) }}"
                                    placeholder="email@example.com">
                                <small class="form-text text-muted">Required when no HEC member is selected</small>
                                @error('owner_email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>
                    </div>

                    {{-- Actions --}}
                    <div class="card edit-section">
                        <div class="card-body py-3 px-4">
                            <button type="submit" class="btn btn-success btn-sm w-100 mb-2" id="submitBtn">
                                <i class="fas fa-save me-1"></i> Update Contract
                            </button>
                            <a href="{{ route('hec-contracts.show', $contract->id) }}" class="btn btn-outline-secondary btn-sm w-100">
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
    // === Owner email toggle ===
    const ownerSelect = document.getElementById('contract_owner_id');
    const emailContainer = document.getElementById('owner_email_container');
    const emailInput = document.getElementById('owner_email');

    function toggleOwnerEmail() {
        if (ownerSelect.value === '') {
            emailContainer.style.display = 'block';
            emailInput.setAttribute('required', 'required');
        } else {
            emailContainer.style.display = 'none';
            emailInput.removeAttribute('required');
        }
    }

    if (ownerSelect.value === '' && emailInput.value) {
        emailContainer.style.display = 'block';
        emailInput.setAttribute('required', 'required');
    } else {
        toggleOwnerEmail();
    }
    ownerSelect.addEventListener('change', toggleOwnerEmail);

    // === Auto-calculate duration ===
    const startInput = document.getElementById('start_date');
    const endInput   = document.getElementById('end_date');
    const durInput   = document.getElementById('duration_months');

    function calculateDuration() {
        if (!startInput.value || !endInput.value) { durInput.value = ''; return; }
        const start = new Date(startInput.value);
        const end   = new Date(endInput.value);
        if (end <= start) { durInput.value = ''; return; }
        let months = (end.getFullYear() - start.getFullYear()) * 12 + end.getMonth() - start.getMonth();
        if (end.getDate() < start.getDate()) months--;
        durInput.value = months || 1;
    }
    startInput.addEventListener('change', calculateDuration);
    endInput.addEventListener('change', calculateDuration);

    // === Cost comma formatting ===
    const costDisplay = document.getElementById('cost_display');
    const costHidden  = document.getElementById('cost');

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
        btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Saving…';
    });
});
</script>
@endpush

@endsection
