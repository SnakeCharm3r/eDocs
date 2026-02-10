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
                            <i class="fas fa-edit me-2"></i>Edit Contract
                        </h3>

                    </div>
                    <div class="col-auto">
                        <a href="{{ route('procurements.contracts.show', $contract->id) }}" class="btn btn-secondary">
                            <i class="fas fa-arrow-left me-1"></i> Back to Details
                        </a>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-lg-12">
                    <div class="card">
                        <div class="card-header bg-light">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-file-contract me-2"></i>Edit Contract: {{ $contract->title }}
                            </h5>
                        </div>
                        <div class="card-body">
                            <form action="{{ route('procurements.contracts.update', $contract->id) }}" method="POST"
                                enctype="multipart/form-data">
                                @csrf
                                @method('PUT')

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
                                                id="title" name="title" value="{{ old('title', $contract->title) }}"
                                                required>
                                            @error('title')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>

                                        <div class="col-md-6 mb-3">
                                            <label for="contract_number" class="form-label">Contract Number</label>
                                            <input type="text"
                                                class="form-control @error('contract_number') is-invalid @enderror"
                                                id="contract_number" name="contract_number"
                                                value="{{ old('contract_number', $contract->contract_number) }}"
                                                placeholder="Auto-generated if left empty">
                                            <small class="form-text text-muted">Leave empty for auto-generation</small>
                                            @error('contract_number')
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
                                                    {{ old('contract_type', $contract->contract_type) == 'Services' ? 'selected' : '' }}>
                                                    Services</option>
                                                <option value="Goods"
                                                    {{ old('contract_type', $contract->contract_type) == 'Goods' ? 'selected' : '' }}>
                                                    Goods</option>
                                                <option value="Services and Goods"
                                                    {{ old('contract_type', $contract->contract_type) == 'Services and Goods' ? 'selected' : '' }}>
                                                    Services and Goods</option>
                                                <option value="Consultants"
                                                    {{ old('contract_type', $contract->contract_type) == 'Consultants' ? 'selected' : '' }}>
                                                    Consultants</option>
                                            </select>
                                            @error('contract_type')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>

                                        <div class="col-md-6 mb-3">
                                            <label for="status" class="form-label">Status <span
                                                    class="text-danger">*</span></label>
                                            <select class="form-select @error('status') is-invalid @enderror" id="status"
                                                name="status" required>
                                                <option value="draft"
                                                    {{ old('status', $contract->status ?? 'draft') == 'draft' ? 'selected' : '' }}>
                                                    Draft</option>
                                                <option value="active"
                                                    {{ old('status', $contract->status ?? 'draft') == 'active' ? 'selected' : '' }}>
                                                    Active</option>
                                                <option value="expired"
                                                    {{ old('status', $contract->status ?? 'draft') == 'expired' ? 'selected' : '' }}>
                                                    Expired</option>
                                                <option value="terminated"
                                                    {{ old('status', $contract->status ?? 'draft') == 'terminated' ? 'selected' : '' }}>
                                                    Terminated</option>
                                                <option value="soonToExpire"
                                                    {{ old('status', $contract->status ?? 'draft') == 'soonToExpire' ? 'selected' : '' }}>
                                                    Soon To Expire</option>
                                            </select>
                                            @error('status')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>

                                        <div class="col-md-6 mb-3">
                                            <label for="renewal_status" class="form-label">Renewal Status</label>
                                            <select class="form-select @error('renewal_status') is-invalid @enderror"
                                                id="renewal_status" name="renewal_status">
                                                <option value="">Select Renewal Status</option>
                                                <option value="renewed"
                                                    {{ old('renewal_status', $contract->renewal_status) == 'renewed' ? 'selected' : '' }}>
                                                    Renewed</option>
                                                <option value="not_renewed"
                                                    {{ old('renewal_status', $contract->renewal_status) == 'not_renewed' ? 'selected' : '' }}>
                                                    Not Renewed</option>
                                                <option value="pending"
                                                    {{ old('renewal_status', $contract->renewal_status) == 'pending' ? 'selected' : '' }}>
                                                    Pending</option>
                                            </select>
                                            @error('renewal_status')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>

                                        <div class="col-md-6 mb-3">
                                            <label for="start_date" class="form-label">Start Date <span
                                                    class="text-danger">*</span></label>
                                            <input type="date"
                                                class="form-control @error('start_date') is-invalid @enderror"
                                                id="start_date" name="start_date"
                                                value="{{ old('start_date', $contract->start_date ? \Carbon\Carbon::parse($contract->start_date)->format('Y-m-d') : '') }}"
                                                required>
                                            @error('start_date')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>

                                        <div class="col-md-6 mb-3">
                                            <label for="end_date" class="form-label">End Date <span
                                                    class="text-danger">*</span></label>
                                            <input type="date"
                                                class="form-control @error('end_date') is-invalid @enderror" id="end_date"
                                                name="end_date"
                                                value="{{ old('end_date', $contract->end_date ? \Carbon\Carbon::parse($contract->end_date)->format('Y-m-d') : '') }}"
                                                required>
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
                                                value="{{ old('duration_months', $contract->duration_months) }}" required
                                                readonly>
                                            <small class="form-text text-muted">Auto-calculated from Start and End
                                                dates</small>
                                            @error('duration_months')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>

                                <!-- Contract Parties Section -->
                                <div class="mb-4">
                                    <h6 class="text-primary border-bottom pb-2 mb-3">
                                        <i class="fas fa-users me-2"></i>Contract Parties
                                    </h6>
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="vendor_id" class="form-label">Vendor / Contractor</label>
                                            <select class="form-select @error('vendor_id') is-invalid @enderror"
                                                id="vendor_id" name="vendor_id">
                                                <option value="">Select Vendor</option>
                                                @foreach ($vendors as $vendor)
                                                    <option value="{{ $vendor->id }}"
                                                        {{ old('vendor_id', $contract->vendor_id) == $vendor->id ? 'selected' : '' }}>
                                                        {{ $vendor->name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            @error('vendor_id')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>

                                        <div class="col-md-6 mb-3">
                                            <label for="division_id" class="form-label">Entity <span
                                                    class="text-danger">*</span></label>
                                            <select class="form-select @error('division_id') is-invalid @enderror"
                                                id="division_id" name="division_id" required>
                                                <option value="">Select Entity</option>
                                                @foreach ($divisions as $division)
                                                    <option value="{{ $division->id }}"
                                                        {{ old('division_id', $contract->division_id) == $division->id ? 'selected' : '' }}>
                                                        {{ $division->name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            @error('division_id')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>

                                        <div class="col-md-6 mb-3">
                                            <label for="department_id" class="form-label">Department <span
                                                    class="text-danger">*</span></label>
                                            <select class="form-select @error('department_id') is-invalid @enderror"
                                                id="department_id" name="department_id" required>
                                                <option value="">Select Entity first</option>
                                            </select>
                                            <small class="form-text text-muted" id="department-help">Please select an
                                                Entity first</small>
                                            @error('department_id')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>

                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Contract Owner (Line Manager) <span
                                                    class="text-danger">*</span></label>
                                            <input type="text" class="form-control" id="line_manager_display" readonly
                                                placeholder="Will be shown after selecting department">
                                            <input type="hidden" id="line_manager_id" name="line_manager_id">
                                            <input type="hidden" id="contract_manager_id" name="contract_manager_id"
                                                value="{{ old('contract_manager_id', $contract->contract_manager_id) }}">
                                            <small class="form-text text-muted">The line manager of the selected department
                                                is automatically set as the contract owner</small>
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
                                                value="{{ old('cost', $contract->cost) ? number_format(old('cost', $contract->cost), 2, '.', ',') : '' }}"
                                                required>
                                            <input type="hidden" id="cost" name="cost"
                                                value="{{ old('cost', $contract->cost) }}" required>
                                            <small class="form-text text-muted">Amount will be formatted with commas
                                                automatically</small>
                                            @error('cost')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>

                                        <div class="col-md-6 mb-3">
                                            <label for="currency" class="form-label">Currency <span
                                                    class="text-danger">*</span></label>
                                            <select class="form-select @error('currency') is-invalid @enderror"
                                                id="currency" name="currency" required>
                                                <option value="TZS"
                                                    {{ old('currency', $contract->currency ?? 'TZS') == 'TZS' ? 'selected' : '' }}>
                                                    TZS</option>
                                                <option value="USD"
                                                    {{ old('currency', $contract->currency ?? 'TZS') == 'USD' ? 'selected' : '' }}>
                                                    USD</option>
                                                <option value="EUR"
                                                    {{ old('currency', $contract->currency ?? 'TZS') == 'EUR' ? 'selected' : '' }}>
                                                    EUR</option>
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
                                        <div class="col-md-12 mb-3">
                                            <label for="file_path" class="form-label">Contract Document (PDF)</label>
                                            <input type="file"
                                                class="form-control @error('file_path') is-invalid @enderror"
                                                id="file_path" name="file_path" accept=".pdf">
                                            <div class="form-text">Upload a new PDF document to replace the existing one
                                                (Max:
                                                10MB). Leave empty to keep current document.</div>
                                            @if ($contract->file_path)
                                                <div class="mt-2">
                                                    <small class="text-muted">Current document: </small>
                                                    <a href="{{ route('procurements.contracts.document', $contract->id) }}?type=file_path"
                                                        target="_blank" class="text-primary">
                                                        <i class="fas fa-file-pdf me-1"></i>View Current Document
                                                    </a>
                                                </div>
                                            @endif
                                            @error('file_path')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>

                                <div class="mt-4">
                                    <button type="submit" class="btn btn-success">
                                        <i class="fas fa-save me-1"></i> Update Contract
                                    </button>
                                    <a href="{{ route('procurements.contracts.show', $contract->id) }}"
                                        class="btn btn-secondary">
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

    @push('styles')
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
        </style>
    @endpush

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const divisionSelect = document.getElementById('division_id');
                const departmentSelect = document.getElementById('department_id');
                const departmentHelp = document.getElementById('department-help');
                const lineManagerDisplay = document.getElementById('line_manager_display');
                const lineManagerId = document.getElementById('line_manager_id');
                const startDateInput = document.getElementById('start_date');
                const endDateInput = document.getElementById('end_date');
                const durationInput = document.getElementById('duration_months');

                const currentDivisionId = '{{ $contract->division_id }}';
                const currentDepartmentId = '{{ $contract->department_id }}';

                // Load departments for current division
                if (currentDivisionId) {
                    loadDepartments(currentDivisionId, currentDepartmentId);
                }

                // Filter departments when entity is selected
                divisionSelect.addEventListener('change', function() {
                    const divisionId = this.value;
                    departmentSelect.innerHTML = '<option value="">Select Department</option>';
                    lineManagerDisplay.value = '';
                    lineManagerId.value = '';

                    if (divisionId) {
                        loadDepartments(divisionId);
                    } else {
                        departmentHelp.textContent = 'Please select an Entity first';
                    }
                });

                function loadDepartments(divisionId, selectedDeptId = null) {
                    fetch(`{{ url('/procurements/contracts/departments') }}/${divisionId}`)
                        .then(response => response.json())
                        .then(departments => {
                            if (departments.length > 0) {
                                departments.forEach(dept => {
                                    const option = document.createElement('option');
                                    option.value = dept.id;
                                    option.textContent = dept.dept_name;
                                    if (selectedDeptId && dept.id == selectedDeptId) {
                                        option.selected = true;
                                    }
                                    departmentSelect.appendChild(option);
                                });
                                departmentHelp.textContent = 'Select a department';

                                // If department was pre-selected, load its line manager
                                if (selectedDeptId) {
                                    loadLineManager(selectedDeptId);
                                }
                            } else {
                                departmentHelp.textContent = 'No departments found for this entity';
                            }
                        })
                        .catch(error => {
                            console.error('Error fetching departments:', error);
                            departmentHelp.textContent = 'Error loading departments';
                        });
                }

                // Load line manager when department is selected
                departmentSelect.addEventListener('change', function() {
                    const departmentId = this.value;
                    if (departmentId) {
                        loadLineManager(departmentId);
                    } else {
                        lineManagerDisplay.value = '';
                        lineManagerId.value = '';
                    }
                });

                function loadLineManager(departmentId) {
                    const contractManagerId = document.getElementById('contract_manager_id');
                    const url = `{{ url('/procurements/contracts/departments') }}/${departmentId}/line-manager`;
                    console.log('Fetching line manager from:', url);

                    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

                    fetch(url, {
                            method: 'GET',
                            headers: {
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest',
                                'X-CSRF-TOKEN': csrfToken
                            }
                        })
                        .then(response => {
                            console.log('Response status:', response.status);
                            if (!response.ok) {
                                throw new Error(`HTTP error! status: ${response.status}`);
                            }
                            return response.json();
                        })
                        .then(data => {
                            console.log('Line manager data:', data);
                            if (data.success && data.line_manager) {
                                lineManagerDisplay.value = data.line_manager.name;
                                lineManagerId.value = data.line_manager.id;
                                contractManagerId.value = data.line_manager.id; // Set as contract manager
                            } else {
                                lineManagerDisplay.value = data.message || 'No line manager assigned';
                                lineManagerId.value = '';
                                contractManagerId.value = '';
                            }
                        })
                        .catch(error => {
                            console.error('Error fetching line manager:', error);
                            lineManagerDisplay.value = 'Error loading line manager: ' + (error.message ||
                                'Unknown error');
                            lineManagerId.value = '';
                            contractManagerId.value = '';
                        });
                }

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

                // Format Contract Value with commas (Tanzanian Shillings format)
                const costDisplay = document.getElementById('cost_display');
                const costHidden = document.getElementById('cost');

                if (costDisplay && costHidden) {
                    // Format number with commas on input
                    costDisplay.addEventListener('input', function(e) {
                        // Remove all non-digit characters except decimal point
                        let value = e.target.value.replace(/[^\d.]/g, '');

                        // Ensure only one decimal point
                        const parts = value.split('.');
                        if (parts.length > 2) {
                            value = parts[0] + '.' + parts.slice(1).join('');
                        }

                        // Limit to 2 decimal places
                        if (parts.length === 2 && parts[1].length > 2) {
                            value = parts[0] + '.' + parts[1].substring(0, 2);
                        }

                        // Store the numeric value (without commas) in hidden field
                        costHidden.value = value;

                        // Format with commas for display
                        if (value) {
                            const numValue = parseFloat(value);
                            if (!isNaN(numValue)) {
                                // Format with commas, keeping decimals
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

                    // On blur, ensure proper formatting
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
