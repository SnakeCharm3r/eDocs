@extends('layouts.template')

@section('content')
<div class="page-wrapper">
    <div class="content container-fluid">
        <div class="page-header mb-4">
            <div class="row align-items-center">
                <div class="col">
                    <h3 class="page-title mb-0">
                        <i class="fas fa-user-plus me-2 text-success"></i>Assign Asset
                    </h3>
                </div>
                <div class="col-auto">
                    <a href="{{ route('asset-management.assignments.index') }}" class="btn btn-secondary btn-sm">
                        <i class="fas fa-arrow-left"></i> Back
                    </a>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-8">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <!-- Asset Information -->
                        <div class="alert alert-info mb-4">
                            <h6 class="mb-2"><strong>Asset Information:</strong></h6>
                            <div class="row">
                                <div class="col-md-6">
                                    <strong>Asset Tag:</strong> {{ $asset->asset_code }}<br>
                                    <strong>Category:</strong> {{ $asset->category->name ?? 'N/A' }}<br>
                                    <strong>Brand/Model:</strong> {{ $asset->brand }} {{ $asset->model }}
                                </div>
                                <div class="col-md-6">
                                    <strong>Current Status:</strong> <span class="badge bg-success">{{ $asset->status }}</span><br>
                                    @if($asset->location)
                                        <strong>Current Location:</strong> {{ $asset->location->name }}<br>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <form action="{{ route('asset-management.assignments.store', $asset->id) }}" method="POST">
                            @csrf
                            
                            <h5 class="mb-3"><i class="fas fa-user me-2"></i>Assignment Details</h5>
                            
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label class="form-label">Assign To User <span class="text-danger">*</span></label>
                                    <select class="form-select @error('assigned_to_user_id') is-invalid @enderror" name="assigned_to_user_id" id="assigned_to_user_id" required>
                                        <option value="">Select User</option>
                                        @foreach($users as $user)
                                            <option value="{{ $user->id }}" {{ old('assigned_to_user_id') == $user->id ? 'selected' : '' }}>
                                                {{ $user->username }} ({{ $user->ccbrt_code ?? 'N/A' }})
                                                @if($user->department)
                                                    - {{ $user->department->dept_name }}
                                                @endif
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('assigned_to_user_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Assignment Date <span class="text-danger">*</span></label>
                                    <input type="date" class="form-control @error('assignment_date') is-invalid @enderror" name="assignment_date" value="{{ old('assignment_date', date('Y-m-d')) }}" required>
                                    @error('assignment_date')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <hr class="my-4">

                            <h5 class="mb-3"><i class="fas fa-map-marker-alt me-2"></i>Location Details</h5>
                            
                            <div class="row mb-3">
                                <div class="col-md-4">
                                    <label class="form-label">Entity</label>
                                    <select class="form-select" name="division_id" id="division_id">
                                        <option value="">Select Entity</option>
                                        @foreach($divisions as $div)
                                            <option value="{{ $div->id }}" {{ old('division_id') == $div->id ? 'selected' : '' }}>{{ $div->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Department</label>
                                    <select class="form-select" name="department_id" id="department_id">
                                        <option value="">Select Department</option>
                                        @foreach($departments as $dept)
                                            <option value="{{ $dept->id }}" {{ old('department_id') == $dept->id ? 'selected' : '' }}>{{ $dept->dept_name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Location/Branch</label>
                                    <select class="form-select" name="location_id" id="location_id">
                                        <option value="">Select Location</option>
                                        @foreach($locations as $loc)
                                            <option value="{{ $loc->id }}" {{ old('location_id') == $loc->id ? 'selected' : '' }}>{{ $loc->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label class="form-label">Line Manager</label>
                                    <select class="form-select" name="line_manager_id" id="line_manager_id">
                                        <option value="">Select Line Manager</option>
                                        @foreach($users->filter(fn($u) => $u->hasRole('line-manager')) as $lm)
                                            <option value="{{ $lm->id }}" {{ old('line_manager_id') == $lm->id ? 'selected' : '' }}>{{ $lm->username }} ({{ $lm->ccbrt_code ?? 'N/A' }})</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Custom Location</label>
                                    <input type="text" class="form-control" name="custom_location" value="{{ old('custom_location') }}" placeholder="e.g., Room 101, Building A">
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Notes</label>
                                <textarea class="form-control" name="notes" rows="3" placeholder="Assignment notes...">{{ old('notes') }}</textarea>
                            </div>

                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-success">
                                    <i class="fas fa-check"></i> Assign Asset
                                </button>
                                <a href="{{ route('asset-management.assignments.index') }}" class="btn btn-secondary">Cancel</a>
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
    const userSelect = document.getElementById('assigned_to_user_id');
    const departmentSelect = document.getElementById('department_id');
    
    // Auto-fill department when user is selected (if user has a department)
    userSelect.addEventListener('change', function() {
        const selectedOption = this.options[this.selectedIndex];
        const userText = selectedOption.text;
        
        // Try to extract department from user text (format: username (code) - dept_name)
        if (userText.includes(' - ')) {
            const parts = userText.split(' - ');
            if (parts.length > 1) {
                const deptName = parts[1].trim();
                // Find matching department in dropdown
                for (let option of departmentSelect.options) {
                    if (option.text === deptName) {
                        departmentSelect.value = option.value;
                        break;
                    }
                }
            }
        }
    });
});
</script>
@endsection

