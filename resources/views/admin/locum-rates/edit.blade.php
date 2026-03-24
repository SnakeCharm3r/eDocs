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
                            <h3 class="page-title mb-0">Edit Locum Rate</h3>
                        </div>
                        <a href="{{ route('locum-rates.index') }}" class="btn btn-secondary btn-sm">
                            <i class="fas fa-arrow-left me-1"></i> Back
                        </a>
                    </div>
                </div>
            </div>

            <div class="card shadow-sm">
                <div class="card-body">
                    <form action="{{ route('locum-rates.update', $locumRate->id) }}" method="POST">
                        @csrf
                        @method('PUT')

                        <div class="mb-3">
                            <label for="education_level" class="form-label">Education Level</label>
                            <input type="text" id="education_level" name="education_level"
                                class="form-control @error('education_level') is-invalid @enderror"
                                value="{{ old('education_level', $locumRate->education_level) }}"
                                placeholder="e.g. Certificate, Diploma, Degree" required>
                            @error('education_level')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="year" class="form-label">Year <span class="text-danger">*</span></label>
                            <select id="year" name="year" class="form-select @error('year') is-invalid @enderror"
                                required>
                                @php
                                    $currentYear = (int) date('Y');
                                    $rateYear = $locumRate->start_date
                                        ? (int) $locumRate->start_date->format('Y')
                                        : $currentYear;
                                @endphp
                                @for ($y = 2025; $y <= $currentYear + 5; $y++)
                                    <option value="{{ $y }}"
                                        {{ old('year', $rateYear) == $y ? 'selected' : '' }}>{{ $y }}</option>
                                @endfor
                            </select>
                            @error('year')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="text-muted">Rate applies for this year (1 Jan – 31 Dec).</small>
                        </div>

                        <div class="mb-3">
                            <label for="rate" class="form-label">Rate (TZS)</label>
                            <input type="number" id="rate" name="rate"
                                class="form-control @error('rate') is-invalid @enderror"
                                value="{{ old('rate', $locumRate->rate) }}" min="0" step="1" required>
                            @error('rate')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" id="is_active" name="is_active"
                                {{ old('is_active', $locumRate->is_active) ? 'checked' : '' }}>
                            <label class="form-check-label" for="is_active">Active</label>
                        </div>

                        <div class="mb-3">
                            <label for="notes" class="form-label">Notes (optional)</label>
                            <textarea id="notes" name="notes" rows="3" class="form-control @error('notes') is-invalid @enderror">{{ old('notes', $locumRate->notes) }}</textarea>
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
@endsection
