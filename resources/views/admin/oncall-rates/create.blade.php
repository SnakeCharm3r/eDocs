@extends('layouts.template')

@section('breadcrumb')
    @include('sweetalert::alert')
@endsection

@section('content')
    <div class="page-wrapper">
        <div class="content container-fluid">
            <div class="page-header">
                <div class="row">
                    <div class="col-sm-12 d-flex justify-content-between align-items-center">
                        <div class="page-sub-header">
                            <h3 class="page-title mb-0">Add On-Call Rate</h3>
                        </div>
                        <a href="{{ route('oncall-rates.index') }}" class="btn btn-secondary btn-sm">
                            <i class="fas fa-arrow-left me-1"></i> Back
                        </a>
                    </div>
                </div>
            </div>

            @if ($errors->any())
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <strong>Oops! There were some problems:</strong>
                    <ul class="mb-0">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            <div class="card shadow-sm">
                <div class="card-body">
                    <form action="{{ route('oncall-rates.store') }}" method="POST" id="createOnCallRateForm">
                        @csrf
                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" id="is_active" name="is_active" value="1"
                                {{ old('is_active', true) ? 'checked' : '' }}>
                            <label class="form-check-label" for="is_active">Active</label>
                            <small class="form-text text-muted d-block">Check to set active; then set Active from / Active
                                until. Uncheck for Not active (dates not applicable).</small>
                        </div>

                        <div id="active-dates-row" class="row {{ old('is_active', true) ? '' : 'd-none' }}">
                            <div class="col-md-6 mb-3">
                                <label for="start_date" class="form-label">Active from</label>
                                <input type="date" id="start_date" name="start_date"
                                    class="form-control @error('start_date') is-invalid @enderror"
                                    value="{{ old('start_date', request('start_date', date('Y-m-d'))) }}" required>
                                @error('start_date')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <small class="form-text text-muted">Date when this rate becomes effective</small>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="end_date" class="form-label">Active until</label>
                                <input type="date" id="end_date" name="end_date"
                                    class="form-control @error('end_date') is-invalid @enderror"
                                    value="{{ old('end_date', request('end_date', date('Y-m-d', strtotime('+1 year')))) }}"
                                    required>
                                @error('end_date')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <small class="form-text text-muted">Last date this rate applies (e.g. one year
                                    later)</small>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="education_level" class="form-label">Education Level</label>
                            <input type="text" id="education_level" name="education_level"
                                class="form-control @error('education_level') is-invalid @enderror"
                                value="{{ old('education_level') }}" placeholder="e.g. Certificate, Diploma, Degree"
                                required>
                            @error('education_level')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="rate" class="form-label">Rate (TZS)</label>
                            <input type="number" id="rate" name="rate"
                                class="form-control @error('rate') is-invalid @enderror" value="{{ old('rate') }}"
                                min="0" step="1" required>
                            @error('rate')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="notes" class="form-label">Notes (optional)</label>
                            <textarea id="notes" name="notes" rows="3" class="form-control @error('notes') is-invalid @enderror"
                                placeholder="Any additional information about this rate">{{ old('notes') }}</textarea>
                            @error('notes')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <button type="submit" class="btn btn-primary" id="submitBtn">
                            <span class="btn-text">
                                <i class="fas fa-save me-1"></i> Save
                            </span>
                            <span class="btn-loading d-none">
                                <span class="spinner-border spinner-border-sm me-1" role="status"
                                    aria-hidden="true"></span>
                                Saving...
                            </span>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('createOnCallRateForm');
            const isActive = document.getElementById('is_active');
            const activeDatesRow = document.getElementById('active-dates-row');
            const startDate = document.getElementById('start_date');
            const endDate = document.getElementById('end_date');

            function toggleActiveDates() {
                if (isActive && activeDatesRow) {
                    if (isActive.checked) {
                        activeDatesRow.classList.remove('d-none');
                        if (startDate) startDate.setAttribute('required', 'required');
                        if (endDate) endDate.setAttribute('required', 'required');
                    } else {
                        activeDatesRow.classList.add('d-none');
                        if (startDate) startDate.removeAttribute('required');
                        if (endDate) endDate.removeAttribute('required');
                    }
                }
            }
            if (isActive) {
                isActive.addEventListener('change', toggleActiveDates);
                toggleActiveDates();
            }

            if (form) {
                form.addEventListener('submit', function(e) {
                    if (isActive && !isActive.checked && startDate && endDate) {
                        var d = new Date();
                        startDate.value = d.toISOString().slice(0, 10);
                        endDate.value = d.toISOString().slice(0, 10);
                    }
                    const submitBtn = document.getElementById('submitBtn');
                    if (submitBtn) {
                        const btnText = submitBtn.querySelector('.btn-text');
                        const btnLoading = submitBtn.querySelector('.btn-loading');
                        submitBtn.disabled = true;
                        if (btnText) btnText.classList.add('d-none');
                        if (btnLoading) btnLoading.classList.remove('d-none');
                    }
                });
            }
        });
    </script>
@endsection
