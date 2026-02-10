{{-- Section 3: Reasoning --}}
<div class="card shadow-sm mb-4" style="border-left: 4px solid #007A33;">
    <div class="card-header bg-light border-bottom">
        <h5 class="card-title mb-0" style="color: #333;">
            <i class="fas fa-comment-alt me-2" style="color: #007A33;"></i>Reasoning & Business Impact
        </h5>
    </div>
    <div class="card-body">
        <div class="mb-3">
            <label for="reasoning" class="form-label fw-semibold">
                Elaborate Brief Your Reasoning, the Business Impact and Why Actions Cannot be Absorbed by Existing Staff or Through Work Exposure Placement or Health Volunteer
                <span class="text-danger">*</span>
            </label>
            <textarea name="reasoning" id="reasoning" class="form-control" required maxlength="1000" rows="6"
                placeholder="Please provide detailed reasoning...">{{ old('reasoning') }}</textarea>
            <small class="text-muted">Maximum 1000 characters</small>
            @error('reasoning')
                <small class="text-danger d-block">{{ $message }}</small>
            @enderror
        </div>
    </div>
</div>

