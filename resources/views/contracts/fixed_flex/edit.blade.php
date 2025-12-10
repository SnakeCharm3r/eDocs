@extends('layouts.template')
@section('breadcrumb')
    @include('sweetalert::alert')
@endsection
@section('content')
    <div class="page-wrapper">
        <div class="content container-fluid">
            <div class="row">
                <div class="col-md-12">
                    <div class="page-sub-header">
                        <h3 class="page-title">Edit Fixed Flex Contract</h3>
                    </div>
                    <div class="card">
                        <div class="card-body">
                            <form action="{{ route('contracts.fixed_flex.update', $contract->id) }}" method="POST">
                                @csrf
                                @method('PUT')
                                <div class="form-group">
                                    <label for="name">Employee Name</label>
                                    <input type="text" class="form-control" id="name" name="name"
                                        value="{{ old('name', $contract->name) }}" required>
                                    @error('name')
                                        <span class="text-danger">{{ $message }}</span>
                                    @enderror
                                </div>
                                <div class="form-group">
                                    <label for="job_title">Job Title</label>
                                    <input type="text" class="form-control" id="job_title" name="job_title"
                                        value="{{ old('job_title', $contract->job_title) }}">
                                </div>
                                <div class="form-group">
                                    <label for="duty_station">Duty Station</label>
                                    <input type="text" class="form-control" id="duty_station" name="duty_station"
                                        value="{{ old('duty_station', $contract->duty_station) }}">
                                </div>
                                <div class="form-group">
                                    <label for="duration">Contract Duration</label>
                                    <input type="text" class="form-control" id="duration" name="duration"
                                        value="{{ old('duration', $contract->duration) }}">
                                </div>
                                <div class="form-group">
                                    <label for="salary_fixed">Fixed Salary (TZS)</label>
                                    <input type="number" class="form-control" id="salary_fixed" name="salary_fixed"
                                        value="{{ old('salary_fixed', $contract->salary_fixed) }}">
                                </div>
                                <div class="form-group">
                                    <label for="salary_flexible">Flexible Daily Rate (TZS)</label>
                                    <input type="number" class="form-control" id="salary_flexible" name="salary_flexible"
                                        value="{{ old('salary_flexible', $contract->salary_flexible) }}">
                                </div>
                                <button type="submit" class="btn btn-primary">Update Contract</button>
                                <a href="{{ route('contracts.fixed_flex.index') }}" class="btn btn-secondary">Cancel</a>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
