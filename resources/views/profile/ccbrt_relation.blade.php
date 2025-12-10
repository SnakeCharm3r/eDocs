@extends('layouts.template2')
@section('breadcrumb')
    <div class="content container-fluid" style="background-color: #eff8f3;">
        <div class="page-header">
            <div class="row">
                <div class="col-sm-12">
                    <div class="page-sub-header">
                        <h3 class="page-title">CCBRT Relation</h3>
                    </div>
                </div>
            </div>
        </div>

        <div class="container mt-5">
            {{-- Progress Bar --}}
            <div class="container">
                <div class="d-flex align-items-center justify-content-between">
                    <h2 class="my-4" style="margin: 0; font-size: 18px;">Step {{ session('current_step') }}: CCBRT
                        Relation</h2>
                    <div class="progress flex-grow-1 ml-3" style="max-width: 70%;">
                        <div class="progress-bar progress-bar-success progress-bar-striped" role="progressbar"
                            aria-valuenow="{{ (session('current_step') / 7) * 100 }}" aria-valuemin="0" aria-valuemax="100"
                            style="width: {{ (session('current_step') / 7) * 100 }}%;">
                            Step {{ session('current_step') }} of 7
                        </div>
                    </div>
                </div>
                <small class="form-text text-muted">Please enter the name of any person you have a relation with from CCBRT.
                    If you don't have any relation with a CCBRT member, you can skip this step and proceed to the next one.</small>
            </div>

            <div class="row">
                <!-- Left Side: Add or Edit Relation Form -->
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-body">
                            <!-- Check for success or error messages -->
                            @if (session('success'))
                                <div class="alert alert-success alert-dismissible fade show">
                                    {{ session('success') }}
                                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                                </div>
                            @endif

                            @if (session('error'))
                                <div class="alert alert-danger alert-dismissible fade show">
                                    {{ session('error') }}
                                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                                </div>
                            @endif

                            <!-- Display validation errors -->
                            @if ($errors->any())
                                <div class="alert alert-danger alert-dismissible fade show">
                                    <strong>Please fix the following errors:</strong>
                                    <ul class="mb-0 mt-2">
                                        @foreach ($errors->all() as $error)
                                            <li>{{ $error }}</li>
                                        @endforeach
                                    </ul>
                                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                                </div>
                            @endif

                            <form method="POST" id="relationForm"
                                action="{{ isset($relationToEdit) ? route('profile.updateRelation', $relationToEdit->id) : route('profile.addRelationData') }}">
                                @csrf
                                @if (isset($relationToEdit))
                                    @method('PUT')
                                @endif
                                <input type="hidden" name="userId" value="{{ Auth::id() }}">

                                <div class="form-group">
                                    <label>Names <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" name="names" id="names"
                                        value="{{ old('names', $relationToEdit->names ?? '') }}" placeholder="John Doe"
                                        required maxlength="100">
                                </div>

                                @php
                                    $standardRelations = ['Colleague', 'Brother', 'Sister', 'Father', 'Mother', 'Spouse', 'Friend', 'Relative'];
                                    $currentRelation = old('relation', $relationToEdit->relation ?? '');
                                    $isOtherRelation = !in_array($currentRelation, $standardRelations) && !empty($currentRelation);
                                @endphp

                                <div class="form-group">
                                    <label>Relation <span class="text-danger">*</span></label>
                                    <select class="form-control" name="relation" id="relation" required onchange="toggleOtherRelationField()">
                                        <option value="" disabled selected>Select Relation</option>
                                        <option value="Colleague" {{ $currentRelation == 'Colleague' ? 'selected' : '' }}>Colleague</option>
                                        <option value="Brother" {{ $currentRelation == 'Brother' ? 'selected' : '' }}>Brother</option>
                                        <option value="Sister" {{ $currentRelation == 'Sister' ? 'selected' : '' }}>Sister</option>
                                        <option value="Father" {{ $currentRelation == 'Father' ? 'selected' : '' }}>Father</option>
                                        <option value="Mother" {{ $currentRelation == 'Mother' ? 'selected' : '' }}>Mother</option>
                                        <option value="Spouse" {{ $currentRelation == 'Spouse' ? 'selected' : '' }}>Spouse</option>
                                        <option value="Friend" {{ $currentRelation == 'Friend' ? 'selected' : '' }}>Friend</option>
                                        <option value="Relative" {{ $currentRelation == 'Relative' ? 'selected' : '' }}>Relative</option>
                                        <option value="Other" {{ $isOtherRelation ? 'selected' : '' }}>Other</option>
                                    </select>
                                </div>

                                <div class="form-group" id="other-relation-field" style="display: {{ $isOtherRelation ? 'block' : 'none' }};">
                                    <label>Please specify relation <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" name="other_relation" id="other_relation"
                                        value="{{ old('other_relation', $isOtherRelation ? $currentRelation : '') }}"
                                        placeholder="Enter relation" maxlength="50">
                                </div>

                                <div class="form-group">
                                    <label>Department <span class="text-danger">*</span></label>
                                    <select class="form-control" name="department" id="department" required>
                                        <option value="" disabled selected>Select Department</option>
                                        @foreach ($departments as $department)
                                            <option value="{{ $department->id }}"
                                                {{ old('department', $relationToEdit->department ?? '') == $department->id ? 'selected' : '' }}>
                                                {{ $department->dept_name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="form-group">
                                    <label>Position <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" name="position" id="position"
                                        value="{{ old('position', $relationToEdit->position ?? '') }}"
                                        placeholder="e.g., Manager, Doctor" required maxlength="100">
                                </div>

                                <div class="d-flex gap-2 mt-3">
                                    @if (isset($relationToEdit))
                                        <a href="{{ route('profile.ccbrt_relation') }}" class="btn btn-secondary">Cancel</a>
                                    @endif
                                    <button type="submit" class="btn btn-primary {{ isset($relationToEdit) ? '' : 'w-100' }}">
                                        {{ isset($relationToEdit) ? 'Update Relation' : 'Save' }}
                                    </button>
                                </div>
                            </form>

                        </div>
                    </div>
                </div>

                <!-- Right Side: View Relations Table -->
                <div class="col-md-8">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">View Relations</h5>
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>Names</th>
                                            <th>Relation</th>
                                            <th>Department</th>
                                            <th>Position</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse ($relations as $relation)
                                            <tr>
                                                <td>{{ $loop->iteration }}</td>
                                                <td>{{ $relation->names }}</td>
                                                <td>{{ $relation->relation }}</td>
                                                <td>{{ $relation->department_name ?? 'N/A' }}</td>
                                                <td>{{ $relation->position }}</td>
                                                <td>
                                                    <div class="d-flex gap-1">
                                                        <a href="{{ route('profile.editRelation', $relation->id) }}"
                                                            class="btn btn-warning btn-sm" title="Edit">
                                                            <i class="fas fa-edit"></i>
                                                        </a>
                                                        <form action="{{ route('profile.deleteRelation', $relation->id) }}" 
                                                              method="POST" 
                                                              class="d-inline delete-relation-form"
                                                              data-name="{{ $relation->names }}">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit" class="btn btn-danger btn-sm" title="Delete">
                                                                <i class="fas fa-trash-alt"></i>
                                                            </button>
                                                        </form>
                                                    </div>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="6" class="text-center text-muted">No relations added yet.</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                            <div class="d-flex justify-content-between align-items-center mt-3">
                                <a href="{{ route('profile.languageKnowledge') }}" class="btn btn-secondary">Previous</a>
                                <form action="{{ route('conflict-interest.viewit') }}" method="GET">
                                    <button type="submit" class="btn btn-primary">Next</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        // Toggle Other Relation Field
        function toggleOtherRelationField() {
            const relation = document.getElementById('relation');
            const otherField = document.getElementById('other-relation-field');
            const otherInput = document.getElementById('other_relation');
            
            if (relation && otherField) {
                if (relation.value === 'Other') {
                    otherField.style.display = 'block';
                    if (otherInput) {
                        otherInput.required = true;
                    }
                } else {
                    otherField.style.display = 'none';
                    if (otherInput) {
                        otherInput.required = false;
                        otherInput.value = '';
                    }
                }
            }
        }

        // Initialize on page load
        document.addEventListener('DOMContentLoaded', function() {
            toggleOtherRelationField();

            // Auto-capitalize names
            const namesInput = document.getElementById('names');
            if (namesInput) {
                namesInput.addEventListener('input', function(e) {
                    let value = e.target.value;
                    value = value.replace(/[^A-Za-z\s]/g, '');
                    const endsWithSpace = /\s$/.test(e.target.value);
                    value = value
                        .split(' ')
                        .filter(word => word.length > 0)
                        .map(word => word.charAt(0).toUpperCase() + word.slice(1).toLowerCase())
                        .join(' ');
                    if (endsWithSpace) {
                        value += ' ';
                    }
                    e.target.value = value;
                });
            }

            // Handle delete with SweetAlert
            document.querySelectorAll('.delete-relation-form').forEach(form => {
                form.addEventListener('submit', function(e) {
                    e.preventDefault();
                    const form = this;
                    const name = form.getAttribute('data-name');
                    
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            title: 'Delete Relation?',
                            text: `Are you sure you want to delete the relation with ${name}?`,
                            icon: 'warning',
                            showCancelButton: true,
                            confirmButtonText: 'Yes, Delete',
                            cancelButtonText: 'Cancel',
                            confirmButtonColor: '#dc3545',
                            cancelButtonColor: '#6c757d',
                            reverseButtons: true
                        }).then((result) => {
                            if (result.isConfirmed) {
                                form.submit();
                            }
                        });
                    } else {
                        if (confirm(`Are you sure you want to delete the relation with ${name}?`)) {
                            form.submit();
                        }
                    }
                });
            });

            // Form validation
            const form = document.getElementById('relationForm');
            if (form) {
                form.addEventListener('submit', function(e) {
                    const relation = document.getElementById('relation').value;
                    
                    if (relation === 'Other') {
                        const otherRelation = document.getElementById('other_relation').value;
                        if (!otherRelation || otherRelation.trim() === '') {
                            e.preventDefault();
                            alert('Please specify the relation.');
                            return false;
                        }
                    }
                });
            }
        });
    </script>
@endsection
