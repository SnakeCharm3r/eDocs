@extends('layouts.template')

@section('content')
    <div class="page-wrapper">
        <div class="content container-fluid">
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white">
                    <div class="d-flex flex-wrap justify-content-between align-items-center">
                        <h4 class="mb-0"><i class="fas fa-plus-circle me-2 text-primary"></i>Add Department Policy</h4>
                        <a href="{{ route('department-policies.index') }}" class="btn btn-outline-secondary btn-sm">
                            <i class="fas fa-arrow-left me-1"></i>Back
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <form action="{{ route('department-policies.store') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <div class="row">
                            <div class="col-md-8">
                                <div class="mb-3">
                                    <label for="title" class="form-label">Title <span class="text-danger">*</span></label>
                                    <input type="text" name="title" id="title" class="form-control @error('title') is-invalid @enderror"
                                        value="{{ old('title') }}" required maxlength="255">
                                    @error('title')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="mb-3">
                                    <label for="document_code" class="form-label">Document code (optional)</label>
                                    <input type="text" name="document_code" id="document_code" class="form-control"
                                        value="{{ old('document_code') }}" maxlength="50">
                                </div>
                                <div class="mb-3">
                                    <label for="description" class="form-label">Description (optional)</label>
                                    <textarea name="description" id="description" class="form-control" rows="3">{{ old('description') }}</textarea>
                                </div>
                                <div class="mb-3">
                                    <label for="department_id" class="form-label">Department <span class="text-danger">*</span></label>
                                    <select name="department_id" id="department_id" class="form-select @error('department_id') is-invalid @enderror" required>
                                        <option value="">Select department</option>
                                        @foreach ($departments as $dept)
                                            <option value="{{ $dept->id }}" {{ old('department_id') == $dept->id ? 'selected' : '' }}>
                                                {{ $dept->dept_name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('department_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="mb-3">
                                    <div class="form-check">
                                        <input type="hidden" name="visible_to_all_staff" value="0">
                                        <input type="checkbox" name="visible_to_all_staff" id="visible_to_all_staff" value="1"
                                            class="form-check-input" {{ old('visible_to_all_staff') ? 'checked' : '' }}>
                                        <label for="visible_to_all_staff" class="form-check-label">Visible to all staff (not only this department)</label>
                                    </div>
                                    <small class="text-muted">If checked, all staff can view this policy and will receive the notification email.</small>
                                </div>
                                <div class="mb-3">
                                    <label for="pdf" class="form-label">PDF document <span class="text-danger">*</span></label>
                                    <input type="file" name="pdf" id="pdf" class="form-control @error('pdf') is-invalid @enderror"
                                        accept=".pdf" required>
                                    <small class="text-muted">Only PDF allowed. Maximum size: 5 MB.</small>
                                    @error('pdf')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                        <hr>
                        <button type="submit" class="btn btn-primary"><i class="fas fa-save me-2"></i>Save &amp; Notify</button>
                        <a href="{{ route('department-policies.index') }}" class="btn btn-outline-secondary">Cancel</a>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
