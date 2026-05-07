@extends('layouts.template2')
@section('breadcrumb')
    <div class="content container-fluid" style="background-color: #eff8f3;">
        <div class="page-header">
            <div class="row">
                <div class="col-sm-12">
                    <div class="page-sub-header">
                        <h3 class="page-title">Health Details</h3>
                    </div>
                </div>
            </div>
        </div>

        <div class="container my-5">
            {{-- Progress Bar --}}
            <div class="container">
                <div class="d-flex align-items-center justify-content-between">
                    <h2 class="my-4" style="margin: 0; font-size: 18px;">Step {{ session('current_step') }}: Health
                        Details</h2>
                    <div class="progress flex-grow-1 ml-3" style="max-width: 70%;">
                        <div class="progress-bar progress-bar-success progress-bar-striped" role="progressbar"
                            aria-valuenow="{{ (session('current_step') / 7) * 100 }}" aria-valuemin="0" aria-valuemax="100"
                            style="width: {{ (session('current_step') / 7) * 100 }}%;">Step {{ session('current_step') }} of
                            7</div>
                    </div>
                </div>
                <small class="form-text text-muted">Please provide your health information. All fields marked with <span class="text-danger">*</span> are required.</small>
            </div>

            {{-- Flash Messages --}}
            @if (session('message'))
                <div class="alert alert-success alert-dismissible fade show">
                    {{ session('message') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @elseif(session('info'))
                <div class="alert alert-info alert-dismissible fade show">
                    {{ session('info') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @elseif(session('error'))
                <div class="alert alert-danger alert-dismissible fade show">
                    {{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            @if ($errors->any())
                <div class="alert alert-danger alert-dismissible fade show">
                    <strong>Please fix the following errors:</strong>
                    <ul class="mb-0 mt-2">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            {{-- Health Details Form --}}
            <form action="{{ route('profile.saveHealthDetails') }}" method="POST" id="healthDetailsForm">
                @csrf
                <input type="hidden" name="userId" value="{{ Auth::id() }}">

                <div class="row">
                    <!-- Left Column (Physical Disability, Blood Group, Illness History) -->
                    <div class="col-12 col-md-6">
                        {{-- Physical Disability --}}
                        <div class="form-group">
                            <label for="physical_disability">Physical Disability<span class="text-danger">*</span></label>
                            <select class="form-control @error('physical_disability') is-invalid @enderror"
                                name="physical_disability" id="physical_disability" required onchange="toggleOtherDisabilityField()">
                                <option value="" disabled selected>-- Select --</option>
                                <option value="None"
                                    {{ old('physical_disability', $healthDetail->physical_disability ?? '') == 'None' ? 'selected' : '' }}>
                                    None</option>
                                <option value="Wheelchair-bound"
                                    {{ old('physical_disability', $healthDetail->physical_disability ?? '') == 'Wheelchair-bound' ? 'selected' : '' }}>
                                    Wheelchair-bound</option>
                                <option value="Visually Impaired"
                                    {{ old('physical_disability', $healthDetail->physical_disability ?? '') == 'Visually Impaired' ? 'selected' : '' }}>
                                    Visually Impaired</option>
                                <option value="Hearing Impaired"
                                    {{ old('physical_disability', $healthDetail->physical_disability ?? '') == 'Hearing Impaired' ? 'selected' : '' }}>
                                    Hearing Impaired</option>
                                <option value="Other"
                                    {{ old('physical_disability', $healthDetail->physical_disability ?? '') == 'Other' ? 'selected' : '' }}>
                                    Other</option>
                            </select>
                            @error('physical_disability')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                        </div>

                        {{-- Other Physical Disability Field --}}
                        @php
                            $standardDisabilities = ['None', 'Wheelchair-bound', 'Visually Impaired', 'Hearing Impaired'];
                            $currentDisability = old('physical_disability', $healthDetail->physical_disability ?? '');
                            $isOtherDisability = !in_array($currentDisability, $standardDisabilities) && !empty($currentDisability);
                        @endphp
                        <div class="form-group" id="other-disability-field" style="display: {{ $isOtherDisability ? 'block' : 'none' }};">
                            <label for="other_disability">Please specify</label>
                            <input type="text" class="form-control" id="other_disability" name="other_disability"
                                value="{{ old('other_disability', $isOtherDisability ? $currentDisability : '') }}"
                                placeholder="Specify physical disability" maxlength="100">
                            <small class="form-text text-muted">Enter the specific physical disability if not listed above.</small>
                        </div>

                        {{-- Blood Group --}}
                        <div class="form-group">
                            <label for="blood_group">Blood Group</label>
                            <select class="form-control" name="blood_group" id="blood_group">
                                <option value="">Select Blood Group</option>
                                <option value="A+"
                                    {{ old('blood_group', $healthDetail->blood_group ?? '') == 'A+' ? 'selected' : '' }}>A+
                                </option>
                                <option value="A-"
                                    {{ old('blood_group', $healthDetail->blood_group ?? '') == 'A-' ? 'selected' : '' }}>A-
                                </option>
                                <option value="B+"
                                    {{ old('blood_group', $healthDetail->blood_group ?? '') == 'B+' ? 'selected' : '' }}>B+
                                </option>
                                <option value="B-"
                                    {{ old('blood_group', $healthDetail->blood_group ?? '') == 'B-' ? 'selected' : '' }}>B-
                                </option>
                                <option value="AB+"
                                    {{ old('blood_group', $healthDetail->blood_group ?? '') == 'AB+' ? 'selected' : '' }}>
                                    AB+</option>
                                <option value="AB-"
                                    {{ old('blood_group', $healthDetail->blood_group ?? '') == 'AB-' ? 'selected' : '' }}>
                                    AB-</option>
                                <option value="O+"
                                    {{ old('blood_group', $healthDetail->blood_group ?? '') == 'O+' ? 'selected' : '' }}>O+
                                </option>
                                <option value="O-"
                                    {{ old('blood_group', $healthDetail->blood_group ?? '') == 'O-' ? 'selected' : '' }}>O-
                                </option>
                                <option value="unknown"
                                    {{ old('blood_group', $healthDetail->blood_group ?? '') == 'unknown' ? 'selected' : '' }}>
                                    I don't know</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="illness_history">Illness History</label>
                            <textarea class="form-control" name="illness_history" id="illness_history" rows="4"
                                placeholder="E.g., Asthma, Diabetes, Hypertension">{{ old('illness_history', $healthDetail->illness_history ?? '') }}</textarea>
                            <small class="form-text text-muted">List any chronic illnesses or medical conditions you have.</small>
                        </div>
                    </div>

                    <div class="col-12 col-md-6">
                        <div class="form-group">
                            <label for="allergies">Allergies</label>
                            <textarea class="form-control" name="allergies" id="allergies" rows="4"
                                placeholder="E.g., Penicillin, Peanuts, Shellfish">{{ old('allergies', $healthDetail->allergies ?? '') }}</textarea>
                            <small class="form-text text-muted">List any known allergies you have.</small>
                        </div>

                        <div class="form-group">
                            <label for="health_insurance">Do you have health insurance coverage?<span
                                    class="text-danger">*</span></label>
                            <select name="health_insurance" class="form-control @error('health_insurance') is-invalid @enderror" id="health_insurance"
                                onchange="toggleInsuranceFields()" required>
                                <option value="" disabled selected>Select an option</option>
                                <option value="yes"
                                    {{ old('health_insurance', $healthInsurance) == 'yes' || old('health_insurance', $healthInsurance) == 'Yes' ? 'selected' : '' }}>Yes</option>
                                <option value="no"
                                    {{ old('health_insurance', $healthInsurance) == 'no' || old('health_insurance', $healthInsurance) == 'No' ? 'selected' : '' }}>No</option>
                            </select>
                            @error('health_insurance')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                        </div>

                        {{-- Insurance Name --}}
                        <div class="form-group" id="insuranceNameGroup" style="display: none;">
                            <label for="insur_name_select">Insurance Name <span class="text-danger">*</span></label>
                            <select class="form-control @error('insur_name') is-invalid @enderror" id="insur_name_select">
                                <option value="">-- Select Insurance Provider --</option>
                                <option value="NHIF">NHIF</option>
                                <option value="Jubilee Insurance">Jubilee Insurance</option>
                                <option value="AAR Insurance">AAR Insurance</option>
                                <option value="Britam">Britam</option>
                                <option value="Resolution Insurance">Resolution Insurance</option>
                                <option value="Other">Other (Please specify)</option>
                            </select>
                            @error('insur_name')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror

                            {{-- Hidden input that will always be submitted --}}
                            <input type="hidden" name="insur_name" id="insur_name"
                                value="{{ old('insur_name', $healthDetail->insur_name ?? '') }}">

                            {{-- Shown only when "Other" is selected --}}
                            <div id="customInsuranceInput" style="display: none; margin-top: 10px;">
                                <label for="custom_insur_name">Please specify</label>
                                <input type="text" class="form-control" id="custom_insur_name"
                                    placeholder="Enter insurance provider name" maxlength="100">
                            </div>
                        </div>

                        {{-- Insurance Number --}}
                        <div class="form-group" id="insuranceNumberGroup" style="display: none;">
                            <label for="insur_no">Insurance Number<span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('insur_no') is-invalid @enderror" name="insur_no" id="insur_no"
                                value="{{ old('insur_no', $healthDetail->insur_no ?? '') }}"
                                placeholder="E.g., ABC123456789" maxlength="50">
                            @error('insur_no')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                        </div>
                    </div>
                </div>

                {{-- Form Navigation --}}
                <div class="d-flex justify-content-between align-items-center mt-4">
                    <a href="{{ route('profile.familyDetails') }}" class="btn btn-secondary">Previous</a>
                    <button type="submit" class="btn btn-primary">Save and Continue</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Toggle the visibility of Insurance Name and Number fields based on Health Insurance selection
        function toggleInsuranceFields() {
            const healthInsurance = document.getElementById('health_insurance').value;
            const insuranceNameGroup = document.getElementById('insuranceNameGroup');
            const insuranceNumberGroup = document.getElementById('insuranceNumberGroup');
            const insurNameSelect = document.getElementById('insur_name_select');
            const insurNo = document.getElementById('insur_no');

            // If health insurance is 'yes', show the insurance name and number fields
            if (healthInsurance === 'yes') {
                insuranceNameGroup.style.display = 'block';
                insuranceNumberGroup.style.display = 'block';
                insurNameSelect.required = true;
                insurNo.required = true;
            } else {
                // Otherwise, hide them and clear values
                insuranceNameGroup.style.display = 'none';
                insuranceNumberGroup.style.display = 'none';
                insurNameSelect.required = false;
                insurNo.required = false;
                insurNameSelect.value = '';
                insurNo.value = '';
                document.getElementById('insur_name').value = '';
                document.getElementById('custom_insur_name').value = '';
                document.getElementById('customInsuranceInput').style.display = 'none';
            }
        }

        // Toggle Other Physical Disability field
        function toggleOtherDisabilityField() {
            const physicalDisability = document.getElementById('physical_disability');
            const otherField = document.getElementById('other-disability-field');
            
            if (physicalDisability && otherField) {
                if (physicalDisability.value === 'Other') {
                    otherField.style.display = 'block';
                    document.getElementById('other_disability').required = true;
                } else {
                    otherField.style.display = 'none';
                    document.getElementById('other_disability').required = false;
                    document.getElementById('other_disability').value = '';
                }
            }
        }

        // Handle insurance name selection
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize insurance fields visibility
            toggleInsuranceFields();
            toggleOtherDisabilityField();

            const select = document.getElementById('insur_name_select');
            const hiddenInput = document.getElementById('insur_name');
            const customInputGroup = document.getElementById('customInsuranceInput');
            const customInput = document.getElementById('custom_insur_name');

            function updateHiddenInput() {
                if (select.value === 'Other') {
                    customInputGroup.style.display = 'block';
                    customInput.required = true;
                    hiddenInput.value = customInput.value;
                } else {
                    customInputGroup.style.display = 'none';
                    customInput.required = false;
                    hiddenInput.value = select.value;
                }
            }

            select.addEventListener('change', updateHiddenInput);
            customInput.addEventListener('input', () => {
                hiddenInput.value = customInput.value;
            });

            // Initialize on load (to preserve old value)
            const existingValue = hiddenInput.value;
            if (
                existingValue &&
                !Array.from(select.options).some(opt => opt.value === existingValue)
            ) {
                select.value = 'Other';
                customInputGroup.style.display = 'block';
                customInput.value = existingValue;
            } else {
                select.value = existingValue;
            }

            updateHiddenInput();

            // Form validation
            const form = document.getElementById('healthDetailsForm');
            if (form) {
                form.addEventListener('submit', function(e) {
                    const healthInsurance = document.getElementById('health_insurance').value;
                    
                    if (healthInsurance === 'yes') {
                        const insurName = document.getElementById('insur_name').value;
                        const insurNo = document.getElementById('insur_no').value;
                        
                        if (!insurName || insurName.trim() === '') {
                            e.preventDefault();
                            alert('Please select or specify an insurance provider name.');
                            return false;
                        }
                        
                        if (!insurNo || insurNo.trim() === '') {
                            e.preventDefault();
                            alert('Please enter your insurance number.');
                            return false;
                        }
                    }
                });
            }
        });
    </script>
@endsection
