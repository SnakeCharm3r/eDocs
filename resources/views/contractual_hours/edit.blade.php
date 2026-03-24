@extends('layouts.template')

@section('content')
    <div class="page-wrapper">
        <div class="content container-fluid">
            @include('sweetalert::alert')

            <div class="row">
                <div class="col-md-8 offset-md-2">
                    <div class="card">
                        <div class="card-header bg-warning text-dark">
                            <h5 class="mb-0">
                                <i class="fas fa-edit me-2"></i>Edit Contractual Hours
                            </h5>
                        </div>
                        <div class="card-body">
                            <form action="{{ route('contractual-hours.update', $contractualHours->id) }}" method="POST">
                                @csrf
                                @method('PUT')

                                <div class="mb-3">
                                    <label for="year" class="form-label">Year <span class="text-danger">*</span></label>
                                    <select name="year" id="year" class="form-select @error('year') is-invalid @enderror" required>
                                        @foreach(range(date('Y') + 1, 2020, -1) as $y)
                                            <option value="{{ $y }}" {{ old('year', $contractualHours->year) == $y ? 'selected' : '' }}>{{ $y }}</option>
                                        @endforeach
                                    </select>
                                    @error('year')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="mb-3">
                                    <label for="month" class="form-label">Month <span class="text-danger">*</span></label>
                                    <select name="month" id="month" class="form-select @error('month') is-invalid @enderror" required>
                                        @foreach(range(1, 12) as $m)
                                            <option value="{{ $m }}" {{ old('month', $contractualHours->month) == $m ? 'selected' : '' }}>
                                                {{ \Carbon\Carbon::create(null, $m, 1)->format('F') }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('month')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="mb-3">
                                    <label for="working_days" class="form-label">Working Days <span class="text-danger">*</span></label>
                                    <input type="number" name="working_days" id="working_days" 
                                           class="form-control @error('working_days') is-invalid @enderror" 
                                           value="{{ old('working_days', $contractualHours->working_days) }}" 
                                           min="1" max="31" required
                                           onchange="calculateHours()">
                                    <small class="text-muted">Number of working days in the month (excluding public holidays)</small>
                                    @error('working_days')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="mb-3">
                                    <label for="public_holidays" class="form-label">Public Holidays</label>
                                    <input type="text" name="public_holidays" id="public_holidays" 
                                           class="form-control @error('public_holidays') is-invalid @enderror" 
                                           value="{{ old('public_holidays', is_array($contractualHours->public_holidays) ? implode(', ', $contractualHours->public_holidays) : $contractualHours->public_holidays) }}" 
                                           placeholder="e.g., 01 January, 12 January">
                                    <small class="text-muted">Enter dates separated by commas (e.g., 01 January, 12 January)</small>
                                    @error('public_holidays')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="mb-3">
                                    <label for="contractual_hours" class="form-label">Contractual Hours</label>
                                    <input type="number" name="contractual_hours" id="contractual_hours" 
                                           class="form-control bg-light" 
                                           value="{{ $contractualHours->contractual_hours }}"
                                           readonly>
                                    <small class="text-muted">Automatically calculated: Working Days × 9</small>
                                </div>

                                <div class="mb-3">
                                    <label for="notes" class="form-label">Notes</label>
                                    <textarea name="notes" id="notes" rows="3" 
                                              class="form-control @error('notes') is-invalid @enderror">{{ old('notes', $contractualHours->notes) }}</textarea>
                                    @error('notes')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="d-flex gap-2">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save me-2"></i>Update
                                    </button>
                                    <a href="{{ route('contractual-hours.index') }}" class="btn btn-secondary">
                                        <i class="fas fa-times me-2"></i>Cancel
                                    </a>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function calculateHours() {
            const workingDays = document.getElementById('working_days').value;
            if (workingDays) {
                document.getElementById('contractual_hours').value = workingDays * 9;
            } else {
                document.getElementById('contractual_hours').value = '';
            }
        }

        // Calculate on page load
        document.addEventListener('DOMContentLoaded', function() {
            calculateHours();
        });
    </script>
@endsection

