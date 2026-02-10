@extends('layouts.template')

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
                            <i class="fas fa-plus-circle me-2"></i>Add New HEC Contract
                        </h3>
                    </div>
                    <div class="col-auto">
                        <a href="{{ route('hec-contracts.index') }}" class="btn btn-secondary">
                            <i class="fas fa-arrow-left me-1"></i> Back to List
                        </a>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-lg-12">
                    <div class="card">
                        <div class="card-header bg-light">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-file-contract me-2"></i>Create New HEC Contract
                            </h5>
                        </div>
                        <div class="card-body">
                            <form action="{{ route('hec-contracts.store') }}" method="POST" enctype="multipart/form-data"
                                id="contractForm">
                                @csrf

                                <!-- Contract Information Section -->
                                <div class="mb-4">
                                    <h6 class="text-primary border-bottom pb-2 mb-3">
                                        <i class="fas fa-info-circle me-2"></i>Contract Information
                                    </h6>
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="title" class="form-label">Contract Name <span
                                                    class="text-danger">*</span></label>
                                            <input type="text" class="form-control @error('title') is-invalid @enderror"
                                                id="title" name="title" value="{{ old('title') }}" required>
                                            @error('title')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>

                                        <div class="col-md-6 mb-3">
                                            <label for="contract_number" class="form-label">Contract Number</label>
                                            <input type="text"
                                                class="form-control @error('contract_number') is-invalid @enderror"
                                                id="contract_number" name="contract_number"
                                                value="{{ old('contract_number') }}"
                                                placeholder="Auto-generated if left empty">
                                            <small class="form-text text-muted">Leave empty for auto-generation
                                                (HEC-CNT-YYYY-####)</small>
                                            @error('contract_number')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>

                                        <div class="col-md-6 mb-3">
                                            <label for="contract_source" class="form-label">Contract Source <span
                                                    class="text-danger">*</span></label>
                                            <select class="form-select @error('contract_source') is-invalid @enderror"
                                                id="contract_source" name="contract_source" required>
                                                <option value="new"
                                                    {{ old('contract_source', 'new') == 'new' ? 'selected' : '' }}>New
                                                    Contract</option>
                                                <option value="existing"
                                                    {{ old('contract_source') == 'existing' ? 'selected' : '' }}>Existing
                                                    Contract</option>
                                            </select>
                                            @error('contract_source')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>

                                        <div class="col-md-6 mb-3">
                                            <label for="contract_type" class="form-label">Contract Type <span
                                                    class="text-danger">*</span></label>
                                            <select class="form-select @error('contract_type') is-invalid @enderror"
                                                id="contract_type" name="contract_type" required>
                                                <option value="">Select Contract Type</option>
                                                <option value="Services"
                                                    {{ old('contract_type') == 'Services' ? 'selected' : '' }}>Services
                                                </option>
                                                <option value="Goods"
                                                    {{ old('contract_type') == 'Goods' ? 'selected' : '' }}>Goods</option>
                                                <option value="Services and Goods"
                                                    {{ old('contract_type') == 'Services and Goods' ? 'selected' : '' }}>
                                                    Services and Goods</option>
                                                <option value="Consultants"
                                                    {{ old('contract_type') == 'Consultants' ? 'selected' : '' }}>
                                                    Consultants</option>
                                            </select>
                                            @error('contract_type')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>

                                        <div class="col-md-6 mb-3">
                                            <label for="division_id" class="form-label">CCBRT Entity</label>
                                            <select class="form-select @error('division_id') is-invalid @enderror"
                                                id="division_id" name="division_id">
                                                <option value="">Select CCBRT Entity (Optional)</option>
                                                @foreach ($divisions ?? [] as $division)
                                                    <option value="{{ $division->id }}"
                                                        {{ old('division_id') == $division->id ? 'selected' : '' }}>
                                                        {{ $division->name }} @if ($division->code)
                                                            ({{ $division->code }})
                                                        @endif
                                                    </option>
                                                @endforeach
                                            </select>
                                            @error('division_id')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>

                                        <div class="col-md-6 mb-3">
                                            <label for="status" class="form-label">Status <span
                                                    class="text-danger">*</span></label>
                                            <select class="form-select @error('status') is-invalid @enderror" id="status"
                                                name="status" required>
                                                <option value="draft"
                                                    {{ old('status', 'draft') == 'draft' ? 'selected' : '' }}>
                                                    Draft</option>
                                                <option value="active" {{ old('status') == 'active' ? 'selected' : '' }}>
                                                    Active</option>
                                                <option value="in_progress"
                                                    {{ old('status') == 'in_progress' ? 'selected' : '' }}>
                                                    In Progress</option>
                                                <option value="renewed" {{ old('status') == 'renewed' ? 'selected' : '' }}>
                                                    Renewed</option>
                                                <option value="expired" {{ old('status') == 'expired' ? 'selected' : '' }}>
                                                    Expired</option>
                                                <option value="terminated"
                                                    {{ old('status') == 'terminated' ? 'selected' : '' }}>Terminated
                                                </option>
                                                <option value="soonToExpire"
                                                    {{ old('status') == 'soonToExpire' ? 'selected' : '' }}>Soon To Expire
                                                </option>
                                            </select>
                                            @error('status')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>

                                        <div class="col-md-12 mb-3">
                                            <label for="description" class="form-label">Description</label>
                                            <textarea class="form-control @error('description') is-invalid @enderror" id="description" name="description"
                                                rows="3">{{ old('description') }}</textarea>
                                            @error('description')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>

                                        <div class="col-md-6 mb-3">
                                            <label for="start_date" class="form-label">Start Date</label>
                                            <input type="date"
                                                class="form-control @error('start_date') is-invalid @enderror"
                                                id="start_date" name="start_date" value="{{ old('start_date') }}">
                                            @error('start_date')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>

                                        <div class="col-md-6 mb-3">
                                            <label for="end_date" class="form-label">End Date</label>
                                            <input type="date"
                                                class="form-control @error('end_date') is-invalid @enderror"
                                                id="end_date" name="end_date" value="{{ old('end_date') }}">
                                            @error('end_date')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>

                                        <div class="col-md-6 mb-3">
                                            <label for="duration_months" class="form-label">Duration (Months) <span
                                                    class="text-danger">*</span></label>
                                            <input type="number"
                                                class="form-control @error('duration_months') is-invalid @enderror"
                                                id="duration_months" name="duration_months"
                                                value="{{ old('duration_months') }}" required readonly>
                                            <small class="form-text text-muted">Auto-calculated from Start and End
                                                dates</small>
                                            @error('duration_months')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>

                                <!-- Contract Owner Section -->
                                <div class="mb-4">
                                    <h6 class="text-primary border-bottom pb-2 mb-3">
                                        <i class="fas fa-user-tie me-2"></i>Contract Owner
                                    </h6>
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="contract_owner_id" class="form-label">Contract Owner (HEC
                                                Member)</label>
                                            <select class="form-select @error('contract_owner_id') is-invalid @enderror"
                                                id="contract_owner_id" name="contract_owner_id">
                                                <option value="">None / External (use email only)</option>
                                                @forelse ($hecMembers ?? [] as $member)
                                                    <option value="{{ $member->id }}"
                                                        {{ old('contract_owner_id') == $member->id ? 'selected' : '' }}>
                                                        {{ $member->fname }} {{ $member->mname }} {{ $member->lname }}
                                                        ({{ $member->email }})
                                                    </option>
                                                @empty
                                                    <option value="" disabled>No HEC members found</option>
                                                @endforelse
                                            </select>

                                            @error('contract_owner_id')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                        <div class="col-md-6 mb-3" id="owner_email_container" style="display: none;">
                                            <label for="owner_email" class="form-label">Owner Email (for notifications)
                                                <span class="text-danger">*</span></label>
                                            <input type="email"
                                                class="form-control @error('owner_email') is-invalid @enderror"
                                                id="owner_email" name="owner_email" value="{{ old('owner_email') }}">

                                            @error('owner_email')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>

                                <!-- Financial Details Section -->
                                <div class="mb-4">
                                    <h6 class="text-primary border-bottom pb-2 mb-3">
                                        <i class="fas fa-dollar-sign me-2"></i>Financial Details
                                    </h6>
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="cost" class="form-label">Contract Value (Total Amount) <span
                                                    class="text-danger">*</span></label>
                                            <input type="text"
                                                class="form-control @error('cost') is-invalid @enderror"
                                                id="cost_display" placeholder="Enter amount (e.g., 1,000,000)"
                                                value="{{ old('cost') ? number_format(old('cost'), 2, '.', ',') : '' }}"
                                                required>
                                            <input type="hidden" id="cost" name="cost"
                                                value="{{ old('cost') }}" required>
                                            @error('cost')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>

                                        <div class="col-md-6 mb-3">
                                            <label for="currency" class="form-label">Currency <span
                                                    class="text-danger">*</span></label>
                                            <select class="form-select @error('currency') is-invalid @enderror"
                                                id="currency" name="currency" required>
                                                @php
                                                    $currencyOptions = [
                                                        'TZS',
                                                        'USD',
                                                        'EUR',
                                                        'GBP',
                                                        'KES',
                                                        'UGX',
                                                        'ZAR',
                                                        'INR',
                                                        'CNY',
                                                        'CAD',
                                                        'AUD',
                                                    ];
                                                @endphp
                                                @foreach ($currencyOptions as $currencyOption)
                                                    <option value="{{ $currencyOption }}"
                                                        {{ old('currency', 'TZS') == $currencyOption ? 'selected' : '' }}>
                                                        {{ $currencyOption }}</option>
                                                @endforeach
                                            </select>
                                            @error('currency')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>

                                <!-- Documents & Attachments Section -->
                                <div class="mb-4">
                                    <h6 class="text-primary border-bottom pb-2 mb-3">
                                        <i class="fas fa-paperclip me-2"></i>Documents & Attachments
                                    </h6>
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="file_path" class="form-label">Contract Document (PDF)</label>
                                            <input type="file"
                                                class="form-control @error('file_path') is-invalid @enderror"
                                                id="file_path" name="file_path" accept=".pdf">
                                            <small class="form-text text-muted">Max: 10MB</small>
                                            @error('file_path')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>

                                        <div class="col-md-6 mb-3">
                                            <label for="signed_contract_path" class="form-label">Signed Contract
                                                (PDF)</label>
                                            <input type="file"
                                                class="form-control @error('signed_contract_path') is-invalid @enderror"
                                                id="signed_contract_path" name="signed_contract_path" accept=".pdf">
                                            <small class="form-text text-muted">Max: 10MB</small>
                                            @error('signed_contract_path')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>

                                        <div class="col-md-6 mb-3">
                                            <label for="terms_conditions_path" class="form-label">Terms & Conditions
                                                (PDF)</label>
                                            <input type="file"
                                                class="form-control @error('terms_conditions_path') is-invalid @enderror"
                                                id="terms_conditions_path" name="terms_conditions_path" accept=".pdf">
                                            <small class="form-text text-muted">Max: 10MB</small>
                                            @error('terms_conditions_path')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>

                                        <div class="col-md-6 mb-3">
                                            <label for="sla_document_path" class="form-label">SLA Document (PDF)</label>
                                            <input type="file"
                                                class="form-control @error('sla_document_path') is-invalid @enderror"
                                                id="sla_document_path" name="sla_document_path" accept=".pdf">
                                            <small class="form-text text-muted">Max: 10MB</small>
                                            @error('sla_document_path')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>

                                <!-- Impact & Likelihood Section -->
                                <div class="mb-4">
                                    <h6 class="text-primary border-bottom pb-2 mb-3">
                                        <i class="fas fa-exclamation-triangle me-2"></i>Impact & Likelihood of Contract
                                    </h6>
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="impact_if_not_requested" class="form-label">Impact if Contract Not
                                                Requested <span class="text-danger">*</span></label>
                                            <select
                                                class="form-select @error('impact_if_not_requested') is-invalid @enderror"
                                                id="impact_if_not_requested" name="impact_if_not_requested" required>
                                                <option value="">Select Impact</option>
                                                <option value="Low"
                                                    {{ old('impact_if_not_requested') == 'Low' ? 'selected' : '' }}>Low
                                                </option>
                                                <option value="Medium"
                                                    {{ old('impact_if_not_requested') == 'Medium' ? 'selected' : '' }}>
                                                    Medium</option>
                                                <option value="High"
                                                    {{ old('impact_if_not_requested') == 'High' ? 'selected' : '' }}>High
                                                </option>
                                            </select>
                                            @error('impact_if_not_requested')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label for="likelihood_rating" class="form-label">Likelihood of Contract
                                                Renewal <span class="text-danger">*</span></label>
                                            <select class="form-select @error('likelihood_rating') is-invalid @enderror"
                                                id="likelihood_rating" name="likelihood_rating" required>
                                                <option value="">Select Likelihood</option>
                                                <option value="Low"
                                                    {{ old('likelihood_rating') == 'Low' ? 'selected' : '' }}>Low</option>
                                                <option value="Medium"
                                                    {{ old('likelihood_rating') == 'Medium' ? 'selected' : '' }}>Medium
                                                </option>
                                                <option value="High"
                                                    {{ old('likelihood_rating') == 'High' ? 'selected' : '' }}>High
                                                </option>
                                            </select>
                                            @error('likelihood_rating')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>

                                <!-- Single owner only (HEC member or email) – no extra HEC members list -->

                                <div class="mt-4">
                                    <button type="submit" class="btn btn-success">
                                        <i class="fas fa-save me-1"></i> Create HEC Contract
                                    </button>
                                    <a href="{{ route('hec-contracts.index') }}" class="btn btn-secondary">
                                        <i class="fas fa-times me-1"></i> Cancel
                                    </a>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const contractOwnerSelect = document.getElementById('contract_owner_id');
                const ownerEmailContainer = document.getElementById('owner_email_container');
                const ownerEmailInput = document.getElementById('owner_email');

                // Function to toggle owner email field visibility
                function toggleOwnerEmail() {
                    if (contractOwnerSelect.value === '') {
                        // No HEC member selected - show email field
                        ownerEmailContainer.style.display = 'block';
                        ownerEmailInput.setAttribute('required', 'required');
                    } else {
                        // HEC member selected - hide email field
                        ownerEmailContainer.style.display = 'none';
                        ownerEmailInput.removeAttribute('required');
                        ownerEmailInput.value = ''; // Clear the email when HEC member is selected
                    }
                }

                // Initial check on page load
                toggleOwnerEmail();

                // Listen for changes to contract owner dropdown
                contractOwnerSelect.addEventListener('change', toggleOwnerEmail);

                const startDateInput = document.getElementById('start_date');
                const endDateInput = document.getElementById('end_date');
                const durationInput = document.getElementById('duration_months');

                // Auto-calculate duration from start and end dates
                function calculateDuration() {
                    const startDate = startDateInput.value;
                    const endDate = endDateInput.value;

                    if (startDate && endDate) {
                        const start = new Date(startDate);
                        const end = new Date(endDate);

                        if (end > start) {
                            const diffTime = Math.abs(end - start);
                            const diffMonths = Math.ceil(diffTime / (1000 * 60 * 60 * 24 * 30));
                            durationInput.value = diffMonths;
                        } else {
                            durationInput.value = '';
                        }
                    } else {
                        durationInput.value = '';
                    }
                }

                startDateInput.addEventListener('change', calculateDuration);
                endDateInput.addEventListener('change', calculateDuration);

                // Format Contract Value with commas
                const costDisplay = document.getElementById('cost_display');
                const costHidden = document.getElementById('cost');

                if (costDisplay && costHidden) {
                    costDisplay.addEventListener('input', function(e) {
                        let value = e.target.value.replace(/[^\d.]/g, '');
                        const parts = value.split('.');
                        if (parts.length > 2) {
                            value = parts[0] + '.' + parts.slice(1).join('');
                        }
                        if (parts.length === 2 && parts[1].length > 2) {
                            value = parts[0] + '.' + parts[1].substring(0, 2);
                        }
                        costHidden.value = value;
                        if (value) {
                            const numValue = parseFloat(value);
                            if (!isNaN(numValue)) {
                                const formatted = numValue.toLocaleString('en-US', {
                                    minimumFractionDigits: value.includes('.') ? 2 : 0,
                                    maximumFractionDigits: 2
                                });
                                e.target.value = formatted;
                            }
                        } else {
                            e.target.value = '';
                        }
                    });

                    costDisplay.addEventListener('blur', function(e) {
                        const value = costHidden.value;
                        if (value) {
                            const numValue = parseFloat(value);
                            if (!isNaN(numValue)) {
                                e.target.value = numValue.toLocaleString('en-US', {
                                    minimumFractionDigits: 2,
                                    maximumFractionDigits: 2
                                });
                            }
                        }
                    });
                }
            });
        </script>
    @endpush
@endsection
