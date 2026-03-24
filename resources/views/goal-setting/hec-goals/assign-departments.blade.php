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
                        <h3 class="page-title mb-0">Assign HEC Goal to Departments</h3>
                    </div>
                    <div class="col-auto">
                        <a href="{{ route('goal-setting.cycles.index') }}" class="btn btn-secondary btn-sm">
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

            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white border-bottom">
                    <h5 class="mb-0">Goal</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4 mb-2">
                            <div class="text-muted small">Cycle</div>
                            <div class="fw-semibold">{{ $goal->cycle->name ?? '—' }}</div>
                        </div>
                        <div class="col-md-8 mb-2">
                            <div class="text-muted small">Title</div>
                            <div class="fw-semibold">{{ $goal->title }}</div>
                        </div>
                        <div class="col-12">
                            <div class="text-muted small">Description</div>
                            <div>{{ $goal->description ?? '—' }}</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card shadow-sm">
                <div class="card-header bg-white border-bottom">
                    <h5 class="mb-0">Select departments</h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('goal-setting.hec-goals.assign-departments', $goal->id) }}">
                        @csrf

                        <div class="row">
                            <div class="col-12 mb-3">
                                <div class="table-responsive">
                                    <table class="table table-sm table-striped align-middle">
                                        <thead class="table-light">
                                            <tr>
                                                <th style="width: 5%;"></th>
                                                <th style="width: 45%;">Department</th>
                                                <th style="width: 50%;">Description</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($departments as $dept)
                                                <tr>
                                                    <td>
                                                        <input class="form-check-input" type="checkbox" name="department_ids[]" value="{{ $dept->id }}" id="dept_{{ $dept->id }}">
                                                    </td>
                                                    <td>
                                                        <label class="mb-0" for="dept_{{ $dept->id }}">{{ $dept->dept_name }}</label>
                                                    </td>
                                                    <td class="text-muted">{{ $dept->description ?? '—' }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex justify-content-end gap-2">
                            <a href="{{ route('goal-setting.cycles.index') }}" class="btn btn-secondary">Cancel</a>
                            <button type="submit" class="btn btn-success">Assign</button>
                        </div>
                    </form>
                </div>
            </div>

        </div>
    </div>
@endsection
