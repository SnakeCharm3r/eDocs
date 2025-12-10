{{-- Section 2: Conditions --}}
<div class="card shadow-sm mb-4" style="border-left: 4px solid #007A33;">
    <div class="card-header bg-light border-bottom">
        <h5 class="card-title mb-0" style="color: #333;">
            <i class="fas fa-check-square me-2" style="color: #007A33;"></i>Conditions
        </h5>
    </div>
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-6">
                <div class="form-check p-3 border rounded h-100">
                    <input type="checkbox" name="conditions[]" value="medical_operational" id="medical_operational" 
                        class="form-check-input" {{ in_array('medical_operational', old('conditions', [])) ? 'checked' : '' }}>
                    <label class="form-check-label fw-semibold" for="medical_operational">
                        <i class="fas fa-hospital me-2 text-danger"></i>There are overwhelming medical or operational imperatives to fill the post
                    </label>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-check p-3 border rounded h-100">
                    <input type="checkbox" name="conditions[]" value="safety_reputational" id="safety_reputational" 
                        class="form-check-input" {{ in_array('safety_reputational', old('conditions', [])) ? 'checked' : '' }}>
                    <label class="form-check-label fw-semibold" for="safety_reputational">
                        <i class="fas fa-shield-alt me-2 text-warning"></i>There are safety or reputational risks to the organization if the post is not filled
                    </label>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-check p-3 border rounded h-100">
                    <input type="checkbox" name="conditions[]" value="legal" id="legal" class="form-check-input"
                        {{ in_array('legal', old('conditions', [])) ? 'checked' : '' }}>
                    <label class="form-check-label fw-semibold" for="legal">
                        <i class="fas fa-gavel me-2 text-info"></i>There are legal requirements to fill the post
                    </label>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-check p-3 border rounded h-100">
                    <input type="checkbox" name="conditions[]" value="financial_loss" id="financial_loss" 
                        class="form-check-input" {{ in_array('financial_loss', old('conditions', [])) ? 'checked' : '' }}>
                    <label class="form-check-label fw-semibold" for="financial_loss">
                        <i class="fas fa-dollar-sign me-2 text-danger"></i>There is evidence that not filling the post will result in demonstrable financial loss to the organisation
                    </label>
                </div>
            </div>
            <div class="col-md-12">
                <div class="form-check p-3 border rounded">
                    <input type="checkbox" name="conditions[]" value="increase_income" id="increase_income" 
                        class="form-check-input" {{ in_array('increase_income', old('conditions', [])) ? 'checked' : '' }}>
                    <label class="form-check-label fw-semibold" for="increase_income">
                        <i class="fas fa-chart-line me-2 text-success"></i>The post is necessary to increase income significantly
                    </label>
                </div>
            </div>
        </div>
        @error('conditions')
            <small class="text-danger">{{ $message }}</small>
        @enderror
    </div>
</div>

