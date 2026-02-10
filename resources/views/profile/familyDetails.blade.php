@extends('layouts.template2')
@section('breadcrumb')
    <div class="content container-fluid" style="background-color: #eff8f3;">
        <div class="page-header">
            <div class="row">
                <div class="col-sm-12">
                    <div class="page-sub-header">
                        <h3 class="page-title">Family Details</h3>
                    </div>
                </div>
            </div>
        </div>

        <!-- Description Section -->
        <div class="container my-5">
            {{-- Progress Bar --}}
            <div class="container">
                <div class="d-flex align-items-center justify-content-between">
                    <!-- Step Title on Left -->
                    <h2 class="my-4" style="margin: 0; font-size: 18px;">Step {{ session('current_step') }}: Family
                        Details
                    </h2>

                    <!-- Progress Bar on Right -->
                    <div class="progress flex-grow-1 ml-3" style="max-width: 70%;">
                        <div class="progress-bar progress-bar-success progress-bar-striped" role="progressbar"
                            aria-valuenow="{{ (session('current_step') / 7) * 100 }}" aria-valuemin="0" aria-valuemax="100"
                            style="width: {{ (session('current_step') / 7) * 100 }}%;">
                            Step {{ session('current_step') }} of 7
                        </div>
                    </div>
                </div>
                <small class="form-text text-muted">Please enter details about your immediate family members (e.g.,
                    parents, siblings, spouse, children).</small>
            </div>

            <div class="container">
                <div class="row flex-lg-nowrap">
                    <div class="col-lg-4">
                        <!-- Column for adding/editing family data -->
                        <div class="row">
                            <div class="col mb-3">
                                <div class="card">
                                    <div class="card-body">
                                        @if (session('message'))
                                            <div class="alert alert-success">
                                                {{ session('message') }}
                                            </div>
                                        @elseif(session('info'))
                                            <div class="alert alert-info">
                                                {{ session('info') }}
                                            </div>
                                        @elseif(session('error'))
                                            <div class="alert alert-danger">
                                                {{ session('error') }}
                                            </div>
                                        @endif

                                        <form method="POST" action="{{ route('profile.saveFamilyDetails') }}">
                                            @csrf

                                            <!-- Hidden input for family member ID if editing -->
                                            @if (isset($familyMember))
                                                <input type="hidden" name="familyData[0][id]"
                                                    value="{{ $familyMember->id }}">
                                            @endif

                                            <div class="form-group">
                                                <label for="fullName">Full Name<span class="text-danger">*</span></label>
                                                <input type="text" class="form-control" id="fullName"
                                                    name="familyData[0][full_name]"
                                                    value="{{ old('familyData.0.full_name', $familyMember->full_name ?? '') }}"
                                                    placeholder="John Doe" required maxlength="50">
                                            </div>
                                            <script>
                                                document.addEventListener('DOMContentLoaded', function() {
                                                    const fullNameInput = document.getElementById('fullName');
                                                    if (fullNameInput) {
                                                        fullNameInput.addEventListener('input', function(e) {
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
                                                });
                                            </script>

                                            <div class="form-group">
                                                <label for="relationship">Relationship<span
                                                        class="text-danger">*</span></label>
                                                <select class="form-control" id="relationship"
                                                    name="familyData[0][relationship]" required
                                                    onchange="toggleOtherField()">
                                                    <option value="" disabled selected>Select Relationship</option>
                                                    <optgroup label="Immediate Family">
                                                        <option value="Parent"
                                                            {{ old('familyData.0.relationship', $familyMember->relationship ?? '') == 'Parent' ? 'selected' : '' }}>
                                                            Parent</option>
                                                        <option value="Sibling"
                                                            {{ old('familyData.0.relationship', $familyMember->relationship ?? '') == 'Sibling' ? 'selected' : '' }}>
                                                            Sibling</option>
                                                        <option value="Spouse"
                                                            {{ old('familyData.0.relationship', $familyMember->relationship ?? '') == 'Spouse' ? 'selected' : '' }}>
                                                            Spouse</option>
                                                        <option value="Child"
                                                            {{ old('familyData.0.relationship', $familyMember->relationship ?? '') == 'Child' ? 'selected' : '' }}>
                                                            Child</option>
                                                    </optgroup>
                                                    <optgroup label="Extended Family">
                                                        <option value="Relatives"
                                                            {{ old('familyData.0.relationship', $familyMember->relationship ?? '') == 'Relatives' ? 'selected' : '' }}>
                                                            Relatives</option>
                                                        <option value="Guardian"
                                                            {{ old('familyData.0.relationship', $familyMember->relationship ?? '') == 'Guardian' ? 'selected' : '' }}>
                                                            Guardian</option>
                                                    </optgroup>
                                                    <optgroup label="Other">
                                                        <option value="Other"
                                                            {{ old('familyData.0.relationship', $familyMember->relationship ?? '') == 'Other' ? 'selected' : '' }}>
                                                            Other</option>
                                                    </optgroup>
                                                </select>
                                            </div>

                                            <div class="form-group" id="other-relationship-field" style="display: none;">
                                                <label for="otherRelationship">Please specify</label>
                                                <input type="text" class="form-control" id="otherRelationship"
                                                    name="familyData[0][other_relationship]"
                                                    value="{{ old('familyData.0.other_relationship', $familyMember->other_relationship ?? '') }}"
                                                    placeholder="Enter Relationship" maxlength="30">
                                            </div>

                                            <div class="form-group">
                                                <label for="phoneNumber">Mobile</label>
                                                <input type="tel" id="phoneNumber" name="familyData[0][phone_number]"
                                                    class="form-control"
                                                    value="{{ old('familyData.0.phone_number', $familyMember->phone_number ?? '') }}"
                                                    placeholder="0699990002" maxlength="13"
                                                    oninput="if(this.value.length > 13) this.value = this.value.slice(0, 13);">
                                            </div>

                                            <div class="form-group">
                                                <label for="occupation">Occupation<span class="text-danger">*</span></label>
                                                <select class="form-control" id="occupation"
                                                    name="familyData[0][occupation]" required
                                                    onchange="toggleOtherOccupationField()">
                                                    <option value="" disabled selected>Select Occupation</option>
                                                    <option value="Businessperson"
                                                        {{ old('familyData.0.occupation', $familyMember->occupation ?? '') == 'Businessperson' ? 'selected' : '' }}>
                                                        Businessperson</option>
                                                    <option value="Employed"
                                                        {{ old('familyData.0.occupation', $familyMember->occupation ?? '') == 'Employed' ? 'selected' : '' }}>
                                                        Employed</option>
                                                    <option value="Self-employed"
                                                        {{ old('familyData.0.occupation', $familyMember->occupation ?? '') == 'Self-employed' ? 'selected' : '' }}>
                                                        Self-employed</option>
                                                    <option value="Unemployed"
                                                        {{ old('familyData.0.occupation', $familyMember->occupation ?? '') == 'Unemployed' ? 'selected' : '' }}>
                                                        Unemployed</option>
                                                    <option value="Student"
                                                        {{ old('familyData.0.occupation', $familyMember->occupation ?? '') == 'Student' ? 'selected' : '' }}>
                                                        Student</option>
                                                    <option value="Retired"
                                                        {{ old('familyData.0.occupation', $familyMember->occupation ?? '') == 'Retired' ? 'selected' : '' }}>
                                                        Retired</option>
                                                    <option value="Other"
                                                        {{ old('familyData.0.occupation', $familyMember->occupation ?? '') == 'Other' ? 'selected' : '' }}>
                                                        Other</option>
                                                </select>
                                            </div>

                                            <div class="form-group" id="other-occupation-field" style="display: none;">
                                                <label for="otherOccupation">Please specify your occupation<span
                                                        class="text-danger">*</span></label>
                                                <input type="text" class="form-control" id="otherOccupation"
                                                    name="familyData[0][other_occupation]"
                                                    value="{{ old('familyData.0.other_occupation', $familyMember->other_occupation ?? '') }}"
                                                    placeholder="Enter Occupation" maxlength="50">
                                            </div>

                                            @php
                                                $nextOfKinExists = $familyData->contains('next_of_kin', true);
                                            @endphp

                                            <div class="form-group">
                                                <label class="form-label">Next of Kin</label>

                                                @if (!$nextOfKinExists || (isset($familyMember) && $familyMember->next_of_kin))
                                                    <div class="form-check">
                                                        <input type="checkbox" id="nextOfKin"
                                                            name="familyData[0][next_of_kin]" value="1"
                                                            class="form-check-input"
                                                            {{ old('familyData.0.next_of_kin', $familyMember->next_of_kin ?? '') ? 'checked' : '' }}>
                                                        <label class="form-check-label fw-normal text-dark"
                                                            for="nextOfKin">
                                                            Check if this person is your Next of Kin.
                                                        </label>
                                                    </div>
                                                @else
                                                    <p class="form-text text-muted mb-0">A Next of Kin has already been
                                                        selected.</p>
                                                @endif
                                            </div>

                                            <!-- Submit Button -->
                                            <button type="submit"
                                                class="btn btn-primary mt-3">{{ isset($familyMember) ? 'Save' : 'Add' }}</button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-8">
                        <!-- Column for displaying family data and remaining slots -->
                        <div class="row">
                            <div class="col mb-3">
                                <div class="card">
                                    <div class="card-body">
                                        <h4 class="card-title">Current Family Members</h4>

                                        {{-- Success/Error alerts --}}
                                        @if (session('success'))
                                            <div class="alert alert-success">{{ session('success') }}</div>
                                        @endif
                                        @if (session('error'))
                                            <div class="alert alert-danger">{{ session('error') }}</div>
                                        @endif

                                        <div class="table-responsive">
                                            <table class="table table-striped">
                                                <thead>
                                                    <tr>
                                                        <th>#</th>
                                                        <th>Full Name</th>
                                                        <th>Relationship</th>
                                                        <th>Mobile</th>
                                                        <th>Occupation</th>
                                                        <th>Next of Kin</th>
                                                        <th>Actions</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <!-- Loop through family data -->
                                                    @foreach ($familyData as $detail)
                                                        <tr>
                                                            <td>{{ $loop->iteration }}</td>
                                                            <td>{{ $detail->full_name }}</td>
                                                            <td>{{ $detail->relationship }}</td>
                                                            <td>{{ $detail->phone_number }}</td>
                                                            <td>{{ $detail->occupation }}</td>
                                                            <td>{{ $detail->next_of_kin ? 'Yes' : 'No' }}</td>
                                                            <td class="d-flex gap-1">
                                                                <!-- Edit button -->
                                                                <a href="{{ route('profile.editFamilyDetails', $detail->id) }}"
                                                                    title="Edit">
                                                                    <i class="fas fa-edit"></i>
                                                                </a>

                                                                <!-- Delete button -->
                                                                <form
                                                                    action="{{ route('profile.deleteFamilyDetail', $detail->id) }}"
                                                                    method="POST"
                                                                    class="delete-family-form"
                                                                    data-name="{{ $detail->full_name }}">
                                                                    @csrf
                                                                    @method('DELETE')
                                                                    <button type="submit"
                                                                        class="btn btn-link p-0 m-0 text-danger"
                                                                        style="border: none; background: none;"
                                                                        title="Delete">
                                                                        <i class="fas fa-trash-alt"></i>
                                                                    </button>
                                                                </form>
                                                            </td>
                                                        </tr>
                                                    @endforeach

                                                    @if ($familyData->isEmpty())
                                                        <tr>
                                                            <td colspan="7" class="text-center">No family members added
                                                                yet.</td>
                                                        </tr>
                                                    @endif
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>

                            </div>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mt-3">
                            <a href="{{ route('profile.personalDetails') }}" class="btn btn-secondary">Previous</a>
                            @if ($existingCount >= 1 && $nextOfKinExists)
                                <a href="{{ route('profile.healthDetails') }}" class="btn btn-primary">Next</a>
                            @else
                                <a href="javascript:void(0);" class="btn btn-primary"
                                    onclick="alert('Please select a Next of Kin before proceeding.');">Next</a>
                            @endif
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>
@endsection
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.8/css/intlTelInput.min.css">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    // Function to toggle visibility of the input field based on selection
    function toggleOtherField() {
        var relationship = document.getElementById('relationship');
        var otherField = document.getElementById('other-relationship-field');
        
        if (relationship && otherField) {
            if (relationship.value === 'Other') {
                otherField.style.display = 'block';
                document.getElementById('otherRelationship').required = true;
            } else {
                otherField.style.display = 'none';
                document.getElementById('otherRelationship').required = false;
                document.getElementById('otherRelationship').value = '';
            }
        }
    }

    function toggleOtherOccupationField() {
        var occupation = document.getElementById('occupation');
        var otherField = document.getElementById('other-occupation-field');
        
        if (occupation && otherField) {
            if (occupation.value === 'Other') {
                otherField.style.display = 'block';
                document.getElementById('otherOccupation').required = true;
            } else {
                otherField.style.display = 'none';
                document.getElementById('otherOccupation').required = false;
                document.getElementById('otherOccupation').value = '';
            }
        }
    }

    // Call the functions on page load to ensure the correct visibility if a previous value is set
    document.addEventListener('DOMContentLoaded', function() {
        toggleOtherField();
        toggleOtherOccupationField();
        
        // Handle delete with SweetAlert
        document.querySelectorAll('.delete-family-form').forEach(form => {
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                const form = this;
                const name = form.getAttribute('data-name');
                
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        title: 'Delete Family Member?',
                        text: `Are you sure you want to delete ${name}?`,
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
                    if (confirm(`Are you sure you want to delete ${name}?`)) {
                        form.submit();
                    }
                }
            });
        });
    });
</script>
