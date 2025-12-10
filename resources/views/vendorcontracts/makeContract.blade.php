@extends('layouts.template')

@section('breadcrumb')
    @include('sweetalert::alert')
@endsection

@section('content')
<div class="page-wrapper">
    <div class="content container-fluid">

        <!-- Main Container -->
        <div class="border shadow-lg p-3" style="background-color:#f9f9f9; border-radius:8px; border:2px solid #000;">

            <!-- Header Row -->
            <div class="d-flex align-items-center justify-content-between mb-4" 
                 style="border:2px solid black; padding:15px; background-color:#f8f9fa; border-radius:5px;">
                <div class="d-flex align-items-center">
                    <img src="{{ asset('assets/img/ccbrt.jpg') }}" alt="Logo" style="width:100px; height:100px; object-fit:contain;">
                </div>
                <div class="text-center" style="flex:1;">
                    <h3 class="fw-bold mb-0">Service | Goods Requisition Form</h3>
                </div>
                <div>
                    <span class="badge rounded-pill bg-success">Version 1 (V1)</span>
                </div>
            </div>

            <form action="{{ route('vendorContract.approveContract') }}" method="POST" enctype="multipart/form-data">
                @csrf

                <!-- Contract + Impact Card -->
                <div class="card mb-4 border">
                    <div class="card-body">

                        <!-- Contract Information -->
                        <h5 class="mb-3 border-bottom pb-2">Contract Information</h5>
                        <div class="row g-3 mb-4">
                            <div class="col-md-4">
                                <label for="contract_id" class="form-label">Select Contract to Renew</label>
                                <select id="contract_id" name="contract_id" class="form-select" required>
                                    <option value="">-- Select Contract --</option>
                                    @foreach($contracts as $contract)
                                        <option value="{{ $contract->id }}"
                                            data-vendor="{{ $contract->vendor_id }}"
                                            data-end-date="{{ $contract->end_date }}"
                                            data-notice="{{ $contract->notice_period_months }}">
                                            {{ $contract->title }} (Ends: {{ $contract->end_date }})
                                        </option>
                                    @endforeach
                                </select>
                                <small class="text-muted">Provide a clear Service/Goods title.</small>
                                @error('title')
                                    <div class="invalid-feedback d-block">
                                        {{ $message }}
                                    </div>
                                @enderror
                            </div>
                            <div class="col-md-4">
                                <label for="contract_type" class="form-label">Contract Type</label>
                                <select class="form-select professional-input" id="contract_type" name="contract_type" required>
                                    <option value="">-- Select Contract Type --</option>
                                    <option value="Supply">Goods</option>
                                    <option value="Service">Services</option>
                                    <option value="Maintenance">Goods & Services</option>
                                </select>
                                <small class="text-muted">Select the type of contract.</small>
                                @error('contract_type')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-4">
                                <label for="category" class="form-label">Category</label>
                                <select class="form-select professional-input" id="category" name="category" required>
                                    <option value="">-- Select Category --</option>
                                    <option value="One-Off">One-Off</option>
                                    <option value="OnGoing">OnGoing</option>
                                </select>
                                <small class="text-muted">Select the contract category.</small>
                                @error('category')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <h5 class="mb-3 border-bottom pb-2">Cost and Duration of Service</h5>
                        <div class="row g-3 mb-4">
                            <div class="col-md-4">
                                <label for="cost" class="form-label">Estimated Cost (TZS)</label>
                                <input type="number" class="form-control professional-input" id="cost" name="cost" placeholder="Example: 1,000,000" required>
                                <small class="text-muted">Provide the estimated cost in Tanzanian Shillings (TZS).</small>
                                @error('cost')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-4">
                                <label for="duration_months" class="form-label">Duration of Services in Months</label>
                                <input type="number" class="form-control professional-input" id="duration_months" name="duration_months" placeholder="1 month, 2 months, 12 months" required>
                                <small class="text-muted">Enter the number of Month.</small>
                                @error('duration_months')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-4">
                                <label for="status" class="form-label">Current Status of the Service or Goods</label>
                                <select class="form-select professional-input" id="status" name="status" required>
                                    <option value="">-- Select Status --</option>
                                    <option value="New">New</option>
                                    <option value="Renewal">Renewal</option>
                                    <option value="Extension">Extension</option>
                                </select>
                                <small class="text-muted">Is the Service under Renewal</small>
                                @error('status')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <h5 class="mb-3 border-bottom pb-2">Impact of Service/Goods</h5>
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label d-block mb-2">Likelihood Rating</label>
                                <input type="hidden" name="likelihood_rating" id="likelihood_rating_input" required>
                                <div class="dropdown w-100">
                                    <button class="btn btn-light dropdown-toggle w-100 text-start" type="button" id="likelihoodDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                                        Select Likelihood
                                    </button>
                                    <ul class="dropdown-menu w-100" aria-labelledby="likelihoodDropdown">
                                        <li><a class="dropdown-item likelihood-pill" href="#" data-value="Unlikely" data-class="badge bg-info text-dark">Unlikely</a></li>
                                        <li><a class="dropdown-item likelihood-pill" href="#" data-value="Possible" data-class="badge bg-success">Possible</a></li>
                                        <li><a class="dropdown-item likelihood-pill" href="#" data-value="Likely" data-class="badge bg-warning text-dark">Likely</a></li>
                                        <li><a class="dropdown-item likelihood-pill" href="#" data-value="Highly Likely" data-class="badge bg-danger">Highly Likely</a></li>
                                    </ul>
                                    @error('likelihood_rating')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label d-block mb-2">Impact if Service/Goods Not Requested</label>
                                <input type="hidden" name="impact_if_not_requested" id="impact_input" required>
                                <div class="dropdown w-100">
                                    <button class="btn btn-light dropdown-toggle w-100 text-start" type="button" id="impactDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                                        Select Impact
                                    </button>
                                    <ul class="dropdown-menu w-100" aria-labelledby="impactDropdown">
                                        <li><a class="dropdown-item impact-pill" href="#" data-value="Very Low" data-class="badge bg-primary">Very Low</a></li>
                                        <li><a class="dropdown-item impact-pill" href="#" data-value="Low" data-class="badge bg-success">Low</a></li>
                                        <li><a class="dropdown-item impact-pill" href="#" data-value="Moderate" data-class="badge bg-warning text-dark">Moderate</a></li>
                                        <li><a class="dropdown-item impact-pill" href="#" data-value="Significant" data-class="badge bg-orange text-dark">Significant</a></li>
                                        <li><a class="dropdown-item impact-pill" href="#" data-value="Severe" data-class="badge bg-danger">Severe</a></li>
                                    </ul>
                                    @error('impact_if_not_requested')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label d-block mb-2">Overall Risk Score</label>
                                <input type="hidden" name="overall_risk" id="risk_input" required>
                                <div class="dropdown w-100">
                                    <button class="btn btn-light dropdown-toggle w-100 text-start" type="button" id="riskDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                                        Select Risk
                                    </button>
                                    <ul class="dropdown-menu w-100" aria-labelledby="riskDropdown">
                                        <li><a class="dropdown-item risk-pill" href="#" data-value="Low" data-class="badge bg-success">Low</a></li>
                                        <li><a class="dropdown-item risk-pill" href="#" data-value="Medium" data-class="badge bg-warning text-dark">Medium</a></li>
                                        <li><a class="dropdown-item risk-pill" href="#" data-value="High" data-class="badge bg-danger">High</a></li>
                                    </ul>
                                    @error('overall_risk')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                    </div>
                </div>

                <!-- Vendor / Department Card -->
                <div class="card mb-4 border">
                    <div class="card-header">
                        <h5 class="mb-0">Vendor / Department</h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <!-- Vendor Option -->
                            <div class="col-md-6">
                                <label for="vendor_option" class="form-label">Option</label>
                                <select class="form-select professional-input" id="vendor_option" name="vendor_option" required>
                                    <option value="existing">Continue with Service Provider</option>
                                    <option value="new">New Service Provider</option>
                                </select>
                                @error('vendor_option')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Existing Vendor -->
                            <div class="col-md-6" id="existing_vendor_field">
                                <label for="vendor_id" class="form-label">Select Existing Vendor</label>
                                <select class="form-select professional-input" id="vendor_id" name="vendor_id">
                                    <option value="">-- Select Vendor --</option>
                                    @foreach($vendors as $vendor)
                                        <option value="{{ $vendor->id }}">{{ $vendor->name }}</option>
                                    @endforeach
                                </select>
                                @error('vendor_id')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- New Vendor: Review Score -->
                            <div class="col-md-6 d-none" id="vendor_review_field">
                                <label for="vendor_review" class="form-label">Rate Previous Vendor (1-10)</label>
                                <input type="number" class="form-control" id="vendor_review" name="vendor_review" min="1" max="10" placeholder="Enter score from 1 to 10">
                                <small class="text-muted">Provide a score for the previous vendor's services if applicable.</small>
                                @error('vendor_review')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <!-- Department -->
                        <div class="row g-3 mt-3">
                            <div class="col-md-6">
                                <label for="department_id" class="form-label">Department</label>
                                <select name="department_id" id="department_id" class="form-control" required>
                                    <option value="">-- Select Department --</option>
                                    @foreach($departments as $department)
                                        <option value="{{ $department->id }}" {{ old('department_id') == $department->id ? 'selected' : '' }}>
                                            {{ $department->dept_name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('department_id')
                                    <div class="text-danger small">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Division -->
                            <div class="col-md-6">
                                <label for="division_id" class="form-label">Division</label>
                                <select name="division_id" id="division_id" class="form-control" required>
                                    <option value="">-- Select Division --</option>
                                    @foreach($divisions as $division)
                                        <option value="{{ $division->id }}" {{ old('division_id') == $division->id ? 'selected' : '' }}>
                                            {{ $division->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('division_id')
                                    <div class="text-danger small">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Service Requirements Card -->
                <div class="card mb-4 border">
                    <div class="card-header">
                        <h5 class="mb-0">Service Requirements</h5>
                    </div>
                    <div class="card-body">

                        <div class="row g-3 align-items-center mb-3">
                            <div class="col-md-12">
                                <label class="form-label fw-bold">Input Type:</label>
                                <small class="text-muted d-block mb-2">
                                    Choose whether to enter requirements manually or upload a PDF file.
                                </small>
                                <div class="form-check form-switch form-switch-md">
                                    <input class="form-check-input" type="checkbox" role="switch" id="requirement_toggle">
                                    <label class="form-check-label" for="requirement_toggle">Upload PDF</label>
                                </div>
                                @error('requirement_toggle')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6" id="text_requirement_field">
                                <label for="service_requirements" class="form-label">Service Requirements (Text)</label>
                                <textarea class="form-control professional-input" name="service_requirements_text" id="service_requirements" rows="3" placeholder="Enter requirements manually"></textarea>
                            </div>

                            <div class="col-md-6 d-none" id="file_requirement_field">
                                <label for="service_requirements_file" class="form-label">Upload Service Requirements (PDF)</label>
                                <input type="file" class="form-control" name="contract_file" id="contract_file" accept=".pdf,.doc,.docx">
                                @error('service_requirements_file')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-12">
                                <input type="hidden" name="service_requirements_final" id="service_requirements_final">
                            </div>
                        </div>

                    </div>
                </div>

                <!-- Submit Button -->
                <div class="text-end mb-4">
                    <button type="submit" class="btn btn-success btn-lg">
                        <i class="fas fa-book"></i> Submit your Request
                    </button>
                </div>

            </form>
        </div>
    </div>
</div>

<script>
    // Vendor toggle (only existingVendor and review field now)
    const vendorOption = document.getElementById('vendor_option');
    const existingVendor = document.getElementById('existing_vendor_field');
    const vendorReview = document.getElementById('vendor_review_field');

    vendorOption.addEventListener('change', function() {
        if (this.value === 'new') {
            existingVendor.classList.add('d-none');
            vendorReview.classList.remove('d-none');
        } else {
            existingVendor.classList.remove('d-none');
            vendorReview.classList.add('d-none');
        }
    });

    // Service requirement toggle
    const requirementToggle = document.getElementById('requirement_toggle');
    const textField = document.getElementById('text_requirement_field');
    const fileField = document.getElementById('file_requirement_field');

    requirementToggle.addEventListener('change', function() {
        if(this.checked){
            textField.classList.add('d-none');
            fileField.classList.remove('d-none');
        } else {
            textField.classList.remove('d-none');
            fileField.classList.add('d-none');
        }
    });

    // Pill dropdown handlers (Likelihood, Impact, Risk)
    function handlePillDropdown(selector, dropdownId, inputId){
        document.querySelectorAll(selector).forEach(function(el){
            el.addEventListener('click', function(e){
                e.preventDefault();
                const val = this.dataset.value;
                const badgeClass = this.dataset.class;
                const btn = document.getElementById(dropdownId);
                btn.innerHTML = `<span class="${badgeClass} px-4 py-2 fs-5 rounded-pill">${val}</span>`;
                document.getElementById(inputId).value = val;
            });
        });
    }
    handlePillDropdown('.likelihood-pill','likelihoodDropdown','likelihood_rating_input');
    handlePillDropdown('.impact-pill','impactDropdown','impact_input');
    handlePillDropdown('.risk-pill','riskDropdown','risk_input');
</script>
@endsection
