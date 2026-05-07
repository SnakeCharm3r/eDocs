@extends('layouts.template')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">Create New Consultant</div>

                <div class="card-body">
                    <form method="POST" action="{{ route('consultants.store') }}">
                        @csrf

                        <!-- Application Date -->
                        <div class="form-group row">
                            <label for="application_date" class="col-md-4 col-form-label text-md-right">Application Date</label>
                            <div class="col-md-6">
                                <input id="application_date" type="date" class="form-control @error('application_date') is-invalid @enderror" name="application_date" value="{{ old('application_date') }}" required>
                                @error('application_date')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>
                        </div>

                        <!-- Full Name -->
                        <div class="form-group row">
                            <label for="consultant_full_name" class="col-md-4 col-form-label text-md-right">Full Name</label>
                            <div class="col-md-6">
                                <input id="consultant_full_name" type="text" class="form-control @error('consultant_full_name') is-invalid @enderror" name="consultant_full_name" value="{{ old('consultant_full_name') }}" required>
                                @error('consultant_full_name')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>
                        </div>

                        <!-- Address -->
                        <div class="form-group row">
                            <label for="consultant_address" class="col-md-4 col-form-label text-md-right">Address</label>
                            <div class="col-md-6">
                                <textarea id="consultant_address" class="form-control @error('consultant_address') is-invalid @enderror" name="consultant_address" required>{{ old('consultant_address') }}</textarea>
                                @error('consultant_address')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>
                        </div>

                        <!-- Mobile -->
                        <div class="form-group row">
                            <label for="consultant_mobile" class="col-md-4 col-form-label text-md-right">Mobile Number</label>
                            <div class="col-md-6">
                                <input id="consultant_mobile" type="text" class="form-control @error('consultant_mobile') is-invalid @enderror" name="consultant_mobile" value="{{ old('consultant_mobile') }}" required>
                                @error('consultant_mobile')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>
                        </div>

                        <!-- Qualification -->
                        <div class="form-group row">
                            <label for="qualification" class="col-md-4 col-form-label text-md-right">Qualification</label>
                            <div class="col-md-6">
                                <input id="qualification" type="text" class="form-control @error('qualification') is-invalid @enderror" name="qualification" value="{{ old('qualification') }}" required>
                                @error('qualification')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>
                        </div>

                        <!-- Years of Experience -->
                        <div class="form-group row">
                            <label for="year_of_experience" class="col-md-4 col-form-label text-md-right">Years of Experience</label>
                            <div class="col-md-6">
                                <input id="year_of_experience" type="number" class="form-control @error('year_of_experience') is-invalid @enderror" name="year_of_experience" value="{{ old('year_of_experience') }}" required>
                                @error('year_of_experience')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>
                        </div>

                        <!-- Hosted Department -->
                        <div class="form-group row">
                            <label for="hosted_dept" class="col-md-4 col-form-label text-md-right">Hosted Department</label>
                            <div class="col-md-6">
                                <select id="hosted_dept" class="form-control @error('hosted_dept') is-invalid @enderror" name="hosted_dept" required>
                                    <option value="">Select Department</option>
                                    @foreach($dept as $department)
                                        <option value="{{ $department->id }}" {{ old('hosted_dept') == $department->id ? 'selected' : '' }}>
                                            {{ $department->dept_name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('hosted_dept')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>
                        </div>

                        <!-- Start Date -->
                        <div class="form-group row">
                            <label for="startDate" class="col-md-4 col-form-label text-md-right">Start Date</label>
                            <div class="col-md-6">
                                <input id="startDate" type="date" class="form-control @error('startDate') is-invalid @enderror" name="startDate" value="{{ old('startDate') }}" required>
                                @error('startDate')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>
                        </div>

                        <!-- End Date -->
                        <div class="form-group row">
                            <label for="endDate" class="col-md-4 col-form-label text-md-right">End Date</label>
                            <div class="col-md-6">
                                <input id="endDate" type="date" class="form-control @error('endDate') is-invalid @enderror" name="endDate" value="{{ old('endDate') }}" required>
                                @error('endDate')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>
                        </div>

                        <div class="form-group row mb-0">
                            <div class="col-md-6 offset-md-4">
                                <button type="submit" class="btn btn-primary">
                                    Create Consultant
                                </button>
                                <a href="{{ route('consultants.index') }}" class="btn btn-secondary">
                                    Cancel
                                </a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
