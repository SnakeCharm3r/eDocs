{{-- Common Fields (for all user types) --}}
{{-- Responsibility Centre --}}
<div class="col-md-6">
    <label for="responsibility_centre" class="form-label fw-semibold">CCBRT Responsibility Centre <span class="text-danger">*</span></label>
    <input type="text" name="responsibility_centre" id="responsibility_centre" class="form-control" 
        required maxlength="100" value="{{ old('responsibility_centre') }}" placeholder="Enter responsibility centre">
    @error('responsibility_centre')
        <small class="text-danger">{{ $message }}</small>
    @enderror
</div>

{{-- Reporting Line --}}
<div class="col-md-6">
    <label for="reporting_line" class="form-label fw-semibold">Reporting Line (Reports to Position) <span class="text-danger">*</span></label>
    <input type="text" name="reporting_line" id="reporting_line" class="form-control" required maxlength="100" 
        value="{{ old('reporting_line') }}" placeholder="Enter reporting position">
    @error('reporting_line')
        <small class="text-danger">{{ $message }}</small>
    @enderror
</div>

{{-- Contract Type --}}
<div class="col-md-6">
    <fieldset>
        <legend class="form-label fw-semibold">Contract Type <span class="text-danger">*</span></legend>
        <div class="border rounded p-3">
            <div class="form-check mb-2">
                <input type="radio" name="contract_type" id="minimal_1_year" value="minimal_1_year" 
                    class="form-check-input" required {{ old('contract_type') == 'minimal_1_year' ? 'checked' : '' }}>
                <label class="form-check-label" for="minimal_1_year">Minimal 1 Year (Employment)</label>
            </div>
            <div class="form-check mb-2">
                <input type="radio" name="contract_type" id="termed_less_1_year" value="termed_less_1_year" 
                    class="form-check-input" {{ old('contract_type') == 'termed_less_1_year' ? 'checked' : '' }}>
                <label class="form-check-label" for="termed_less_1_year">Termed &lt; 1 Year (Consultant/Specific Task)</label>
            </div>
            <div class="form-check mb-2">
                <input type="radio" name="contract_type" id="health_volunteer" value="health_volunteer" 
                    class="form-check-input" {{ old('contract_type') == 'health_volunteer' ? 'checked' : '' }}>
                <label class="form-check-label" for="health_volunteer">Health Volunteer (50% Basic, Minimal 1 Year)</label>
            </div>
            <div class="form-check">
                <input type="radio" name="contract_type" id="work_exposure" value="work_exposure" 
                    class="form-check-input" {{ old('contract_type') == 'work_exposure' ? 'checked' : '' }}>
                <label class="form-check-label" for="work_exposure">Work Exposure Placement (No Pay, Max 2x3 Months)</label>
            </div>
        </div>
        @error('contract_type')
            <small class="text-danger">{{ $message }}</small>
        @enderror
    </fieldset>
</div>

{{-- Required Starting Date --}}
<div class="col-md-6">
    <label for="required_start_date" class="form-label fw-semibold">Required Starting Date <span class="text-danger">*</span></label>
    <input type="date" name="required_start_date" id="required_start_date" class="form-control" 
        value="{{ old('required_start_date') }}" required>
    <small class="text-muted"><i class="fas fa-info-circle me-1"></i>Expected start date for the position</small>
    @error('required_start_date')
        <small class="text-danger d-block">{{ $message }}</small>
    @enderror
</div>

{{-- Job Description File --}}
<div class="col-12">
    <label for="job_description_file" class="form-label fw-semibold">The following documents to be attached: <span class="text-danger">*</span></label>
    <div class="form-check mb-2">
        <input type="checkbox" class="form-check-input" id="job_description_check" checked disabled>
        <label class="form-check-label" for="job_description_check">Updated Job/Task Description</label>
    </div>
    <input type="file" name="job_description_file" id="job_description_file" class="form-control mt-2" 
        accept="application/pdf" required>
    <small class="text-muted"><i class="fas fa-info-circle me-1"></i>Max size: 2MB. PDF format only.</small>
    <div id="file-error-message" class="text-danger mt-1" style="display: none;"></div>
    <div id="file-success-message" class="text-success mt-1" style="display: none;"></div>
    @error('job_description_file')
        <small class="text-danger d-block">{{ $message }}</small>
    @enderror
</div>

