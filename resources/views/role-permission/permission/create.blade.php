@extends('layouts.template')

@section('breadcrumb')
    @include('sweetalert::alert')
    <div class="page-wrapper">
        <div class="content container-fluid">
            <div class="page-header mb-4">
                <div class="row align-items-center">
                    <div class="col">
                        <h3 class="page-title mb-1">Create New Permission</h3>
                        <p class="text-muted mb-0">Add a new permission to the system</p>
                    </div>
                    <div class="col-auto">
                        <a href="{{ route('permission.index') }}" class="btn btn-outline-secondary">
                            <i class="fas fa-arrow-left me-1"></i> Back to Permissions
                        </a>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-8 offset-md-2">
                    <div class="card shadow-sm border-0">
                        <div class="card-body">
                            <form method="POST" action="{{ route('permission.store') }}" id="create-permission-form">
                                @csrf

                                <div class="mb-4">
                                    <label for="name" class="form-label fw-semibold">
                                        Permission Name <span class="text-danger">*</span>
                                    </label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-light">
                                            <i class="fas fa-key text-muted"></i>
                                        </span>
                                        <input type="text" 
                                            class="form-control form-control-lg @error('name') is-invalid @enderror" 
                                            id="name" 
                                            name="name" 
                                            value="{{ old('name') }}" 
                                            placeholder="e.g., view oncall requests, create locum requests"
                                            required
                                            autofocus>
                                    </div>
                                    @error('name')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                    <small class="text-muted">
                                        <i class="fas fa-info-circle me-1"></i>
                                        Use lowercase letters and spaces. Examples: "view oncall requests", "approve locum requests"
                                    </small>
                                </div>

                                <div class="alert alert-info border-0">
                                    <h6 class="alert-heading mb-2">
                                        <i class="fas fa-lightbulb me-2"></i>Permission Naming Convention
                                    </h6>
                                    <ul class="mb-0 ps-3">
                                        <li>Use lowercase letters</li>
                                        <li>Use spaces to separate words</li>
                                        <li>Follow the pattern: <code>action resource</code> (e.g., "view oncall requests")</li>
                                        <li>Common actions: view, create, edit, delete, approve, reject, manage</li>
                                    </ul>
                                </div>

                                <div class="d-flex justify-content-end gap-2 mt-4">
                                    <a href="{{ route('permission.index') }}" class="btn btn-outline-secondary btn-lg">
                                        Cancel
                                    </a>
                                    <button type="submit" class="btn btn-primary btn-lg" id="submit-btn">
                                        <i class="fas fa-save me-2"></i> Create Permission
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('create-permission-form');
            const submitBtn = document.getElementById('submit-btn');
            const nameInput = document.getElementById('name');

            // Format permission name on input
            nameInput.addEventListener('input', function() {
                let value = this.value.toLowerCase().trim();
                // Replace multiple spaces with single space
                value = value.replace(/\s+/g, ' ');
                this.value = value;
            });

            // Form submission handler
            form.addEventListener('submit', function(e) {
                const name = nameInput.value.trim();
                
                if (name.length < 3) {
                    e.preventDefault();
                    alert('Permission name must be at least 3 characters long.');
                    nameInput.focus();
                    return false;
                }

                submitBtn.disabled = true;
                submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Creating...';
            });
        });
    </script>
@endsection
