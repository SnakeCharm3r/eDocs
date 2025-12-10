@extends('layouts.template')
@section('content')

    <div class="page-wrapper">
        <div class="content container-fluid">
            <div class="row">
                <div class="col-md-12">
                    <div class="page-sub-header">
                        <h3 class="page-title">Edit Contract</h3>
                    </div>
                    <div class="card">
                        <div class="card-body">
                            <form action="{{ route('contracts.update', $contract->id) }}" method="POST">
                                @csrf
                                @method('PUT')
                                <div class="row">
                                    <!-- Employee Selection -->
                                    <div class="col-md-6 mb-3">
                                        <label for="user_id" class="form-label">Employee</label>
                                        <select name="user_id" id="user_id"
                                            class="form-select @error('user_id') is-invalid @enderror">
                                            <option value="">Select Employee</option>
                                            @foreach ($users as $user)
                                                <option value="{{ $user->id }}"
                                                    {{ $contract->user_id == $user->id ? 'selected' : '' }}>
                                                    {{ $user->fname }} {{ $user->mname }} {{ $user->lname }}
                                                </option>
                                            @endforeach
                                        </select>
                                        @error('user_id')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <!-- Place of Recruitment -->
                                    <div class="col-md-6 mb-3">
                                        <label for="place_of_recruitment" class="form-label">Place of Recruitment</label>
                                        <input type="text" name="place_of_recruitment" id="place_of_recruitment"
                                            class="form-control @error('place_of_recruitment') is-invalid @enderror"
                                            value="{{ old('place_of_recruitment', $contract->place_of_recruitment) }}">
                                        @error('place_of_recruitment')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <!-- Duty Station -->
                                    <div class="col-md-6 mb-3">
                                        <label for="duty_station" class="form-label">Duty Station</label>
                                        <input type="text" name="duty_station" id="duty_station"
                                            class="form-control @error('duty_station') is-invalid @enderror"
                                            value="{{ old('duty_station', $contract->duty_station) }}">
                                        @error('duty_station')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <!-- Start Date -->
                                    <div class="col-md-6 mb-3">
                                        <label for="start_date" class="form-label">Start Date</label>
                                        <input type="date" name="start_date" id="start_date"
                                            class="form-control @error('start_date') is-invalid @enderror"
                                            value="{{ old('start_date', $contract->start_date) }}">
                                        @error('start_date')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <!-- Duration -->
                                    <div class="col-md-6 mb-3">
                                        <label for="duration" class="form-label">Duration</label>
                                        <input type="text" name="duration" id="duration"
                                            class="form-control @error('duration') is-invalid @enderror"
                                            value="{{ old('duration', $contract->duration) }}">
                                        @error('duration')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <!-- Working Hours -->
                                    <div class="col-md-6 mb-3">
                                        <label for="working_hours" class="form-label">Working Hours</label>
                                        <input type="text" name="working_hours" id="working_hours"
                                            class="form-control @error('working_hours') is-invalid @enderror"
                                            value="{{ old('working_hours', $contract->working_hours) }}">
                                        @error('working_hours')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <!-- Probation Period -->
                                    <div class="col-md-6 mb-3">
                                        <label for="probation_period" class="form-label">Probation Period</label>
                                        <input type="text" name="probation_period" id="probation_period"
                                            class="form-control @error('probation_period') is-invalid @enderror"
                                            value="{{ old('probation_period', $contract->probation_period) }}">
                                        @error('probation_period')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <!-- Basic Pay -->
                                    <div class="col-md-6 mb-3">
                                        <label for="basic_pay" class="form-label">Basic Pay (per month)</label>
                                        <input type="text" name="basic_pay" id="basic_pay"
                                            class="form-control @error('basic_pay') is-invalid @enderror"
                                            value="{{ old('basic_pay', $contract->basic_pay) }}">
                                        @error('basic_pay')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <!-- Total Gross Pay -->
                                    <div class="col-md-6 mb-3">
                                        <label for="total_gross_pay" class="form-label">Total Gross Pay (per
                                            month)</label>
                                        <input type="text" name="total_gross_pay" id="total_gross_pay"
                                            class="form-control @error('total_gross_pay') is-invalid @enderror"
                                            value="{{ old('total_gross_pay', $contract->total_gross_pay) }}">
                                        @error('total_gross_pay')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <!-- Medical Insurance Employee -->
                                    <div class="col-md-6 mb-3">
                                        <label for="medical_insurance_employee" class="form-label">Medical Insurance
                                            (Employee Contribution)</label>
                                        <input type="text" name="medical_insurance_employee"
                                            id="medical_insurance_employee"
                                            class="form-control @error('medical_insurance_employee') is-invalid @enderror"
                                            value="{{ old('medical_insurance_employee', $contract->medical_insurance_employee) }}">
                                        @error('medical_insurance_employee')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <!-- Medical Insurance Employer -->
                                    <div class="col-md-6 mb-3">
                                        <label for="medical_insurance_employer" class="form-label">Medical Insurance
                                            (Employer Contribution)</label>
                                        <input type="text" name="medical_insurance_employer"
                                            id="medical_insurance_employer"
                                            class="form-control @error('medical_insurance_employer') is-invalid @enderror"
                                            value="{{ old('medical_insurance_employer', $contract->medical_insurance_employer) }}">
                                        @error('medical_insurance_employer')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <!-- Funeral Insurance Eligibility -->
                                    <div class="col-md-6 mb-3">
                                        <label for="funeral_insurance_eligibility" class="form-label">Funeral Insurance
                                            Eligibility</label>
                                        <input type="text" name="funeral_insurance_eligibility"
                                            id="funeral_insurance_eligibility"
                                            class="form-control @error('funeral_insurance_eligibility') is-invalid @enderror"
                                            value="{{ old('funeral_insurance_eligibility', $contract->funeral_insurance_eligibility) }}">
                                        @error('funeral_insurance_eligibility')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>


                                </div>

                                <!-- Submit and Cancel Buttons -->
                                <div class="mt-4">
                                    <button type="submit" class="btn btn-primary">Update Contract</button>
                                    <a href="{{ route('contracts.index') }}" class="btn btn-secondary">Cancel</a>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
