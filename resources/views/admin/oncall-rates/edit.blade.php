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
                            <h3 class="page-title mb-0">Edit On-Call Rate</h3>
                        </div>
                        <a href="{{ route('oncall-rates.index') }}" class="btn btn-secondary btn-sm">
                            <i class="fas fa-arrow-left me-1"></i> Back
                        </a>
                    </div>
                </div>
            </div>

            <div class="card shadow-sm">
                <div class="card-body">
                    <form action="{{ route('oncall-rates.update', $oncall_rate->id) }}" method="POST"
                        id="editOnCallRateForm">
                        @csrf
                        @method('PUT')

                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" id="is_active" name="is_active" value="1"
                                {{ old('is_active', $oncall_rate->is_active) ? 'checked' : '' }}>
                            <label class="form-check-label" for="is_active">Active</label>
                            <small class="form-text text-muted d-block">Check to set active; then set Active from / Active
                                until. Uncheck for Not active (dates not applicable).</small>
                        </div>

                        <div id="active-dates-row"
                            class="row {{ old('is_active', $oncall_rate->is_active) ? '' : 'd-none' }}">
                            <div class="col-md-6 mb-3">
                                <label for="start_date" class="form-label">Active from</label>
                                <input type="date" id="start_date" name="start_date"
                                    class="form-control @error('start_date') is-invalid @enderror"
                                    value="{{ old('start_date', $oncall_rate->start_date ? $oncall_rate->start_date->format('Y-m-d') : date('Y-m-d')) }}"
                                    required>
                                @error('start_date')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <small class="form-text text-muted">Date when this rate becomes effective</small>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="end_date" class="form-label">Active until</label>
                                <input type="date" id="end_date" name="end_date"
                                    class="form-control @error('end_date') is-invalid @enderror"
                                    value="{{ old('end_date', $oncall_rate->end_date ? $oncall_rate->end_date->format('Y-m-d') : date('Y-m-d', strtotime('+1 year'))) }}"
                                    required>
                                @error('end_date')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <small class="form-text text-muted">Last date this rate applies</small>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="education_level" class="form-label">Education Level</label>
                            <input type="text" id="education_level" name="education_level"
                                class="form-control @error('education_level') is-invalid @enderror"
                                value="{{ old('education_level', $oncall_rate->education_level) }}" required>
                            @error('education_level')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="rate" class="form-label">Rate (TZS)</label>
                            <input type="number" id="rate" name="rate"
                                class="form-control @error('rate') is-invalid @enderror"
                                value="{{ old('rate', $oncall_rate->rate) }}" min="0" step="1" required>
                            @error('rate')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="notes" class="form-label">Notes (optional)</label>
                            <textarea id="notes" name="notes" rows="3" class="form-control @error('notes') is-invalid @enderror">{{ old('notes', $oncall_rate->notes) }}</textarea>
                            @error('notes')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-1"></i> Update
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var isActive = document.getElementById('is_active');
            var activeDatesRow = document.getElementById('active-dates-row');
            var startDate = document.getElementById('start_date');
            var endDate = document.getElementById('end_date');
            var form = document.getElementById('editOnCallRateForm');

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
                form.addEventListener('submit', function() {
                    if (isActive && !isActive.checked && startDate && endDate) {
                        var d = new Date();
                        startDate.value = d.toISOString().slice(0, 10);
                        endDate.value = d.toISOString().slice(0, 10);
                    }
                });
            }
        });
    </script>
@endsection
