@extends('layouts.template')

@section('breadcrumb')
    @include('sweetalert::alert')
@endsection

@section('content')
    <div class="page-wrapper">
        <div class="content container-fluid">
            <div class="page-header mb-4">
                <div class="row align-items-center">
                    <div class="col">
                        <h3 class="page-title mb-0">Add Goal</h3>
                    </div>
                    <div class="col-auto">
                        <a href="{{ route('goal-setting.staff.my-goals') }}" class="btn btn-secondary btn-sm">
                            <i class="fas fa-arrow-left"></i> Back
                        </a>
                    </div>
                </div>
            </div>

            @if ($errors->any())
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="card shadow-sm">
                <div class="card-header bg-white border-bottom">
                    <h5 class="mb-0">Goal details</h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('goal-setting.staff-goals.store') }}">
                        @csrf
                        <input type="hidden" name="department_id" value="{{ $user->deptId }}">
                        <input type="hidden" name="owner_user_id" value="{{ $user->id }}">

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Cycle <span class="text-danger">*</span></label>
                                <select name="goal_cycle_id" class="form-select" required>
                                    <option value="">Select cycle</option>
                                    @foreach ($cycles as $cycle)
                                        <option value="{{ $cycle->id }}" {{ (string) old('goal_cycle_id', $selectedCycleId) === (string) $cycle->id ? 'selected' : '' }}>
                                            {{ $cycle->name }} ({{ ucfirst($cycle->status) }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label">Unit (optional)</label>
                                <select name="unit_id" class="form-select">
                                    <option value="">None</option>
                                    @foreach ($units as $unit)
                                        <option value="{{ $unit->id }}">{{ $unit->name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-12 mb-3">
                                <label class="form-label">Title <span class="text-danger">*</span></label>
                                <input type="text" name="title" class="form-control" value="{{ old('title') }}" required>
                            </div>

                            <div class="col-12 mb-3">
                                <label class="form-label">Description</label>
                                <textarea name="description" class="form-control" rows="3">{{ old('description') }}</textarea>
                            </div>
                        </div>

                        <div class="d-flex justify-content-end gap-2">
                            <a href="{{ route('goal-setting.staff.my-goals') }}" class="btn btn-secondary">Cancel</a>
                            <button type="submit" class="btn btn-success">Save</button>
                        </div>
                    </form>
                </div>
            </div>

        </div>
    </div>
@endsection
