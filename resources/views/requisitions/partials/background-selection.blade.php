{{-- Background Selection (Common for all user types) --}}
<div class="col-12">
    <fieldset>
        <legend class="form-label fw-semibold">Background <span class="text-danger">*</span></legend>
        @php
            $currentUser = auth()->user();
            $isHecMember = $isHecMember ?? ($currentUser?->hasAnyRole(['coo', 'cms', 'cfo', 'crhdo', 'chief_accountant']) ?? false);
            $isLineManager = $isLineManager ?? ($currentUser?->hasRole('line-manager') ?? false);
        @endphp
        <div class="row g-3">
            <div class="col-md-4">
                <div class="form-check p-3 border rounded">
                    <input type="radio" name="background" id="new_position" value="new_position"
                        class="form-check-input" required
                        {{ old('background') == 'new_position' ? 'checked' : '' }}>
                    <label class="form-check-label fw-semibold" for="new_position">
                        <i class="fas fa-plus-circle me-2 text-success"></i>New Position
                    </label>
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-check p-3 border rounded">
                    <input type="radio" name="background" id="replacement" value="replacement"
                        class="form-check-input" {{ old('background') == 'replacement' ? 'checked' : '' }}>
                    <label class="form-check-label fw-semibold" for="replacement">
                        <i class="fas fa-user-friends me-2 text-primary"></i>Replacement
                    </label>
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-check p-3 border rounded">
                    <input type="radio" name="background" id="contract_renewal" value="contract_renewal"
                        class="form-check-input" {{ old('background') == 'contract_renewal' ? 'checked' : '' }}>
                    <label class="form-check-label fw-semibold" for="contract_renewal">
                        <i class="fas fa-sync-alt me-2 text-info"></i>Contract Renewal/Extension
                    </label>
                </div>
            </div>
        </div>
        @error('background')
            <small class="text-danger">{{ $message }}</small>
        @enderror
    </fieldset>
</div>

