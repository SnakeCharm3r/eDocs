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
                    <!-- Progress Bar on Right -->
                    <div class="progress flex-grow-1 ml-3" style="max-width: 70%;">
                        <div class="progress-bar progress-bar-success progress-bar-striped" role="progressbar"
                            aria-valuenow="{{ (session('current_step') / 7) * 100 }}" aria-valuemin="0" aria-valuemax="100"
                            style="width: {{ (session('current_step') / 7) * 100 }}%;">
                            Step {{ session('current_step') }} of 7
                        </div>
                    </div>
                </div>
            </div>

            <div class="container">
                <div class="row flex-lg-nowrap">
                    <div class="col-lg-4">
                        <!-- Column for saving family data -->
                        <div class="row">
                            <div class="col mb-3">
                                <div class="card">
                                    <div class="card-body">
                                        <form method="POST"
                                            action="{{ route('profile.updateFamilyDetails', $familyMember->id) }}">
                                            @csrf
                                            @method('PUT')
                                            <input type="hidden" name="id" value="{{ $familyMember->id }}">

                                            @if ($errors->any())
                                                <div class="alert alert-danger">
                                                    <ul>
                                                        @foreach ($errors->all() as $error)
                                                            <li>{{ $error }}</li>
                                                        @endforeach
                                                    </ul>
                                                </div>
                                            @endif

                                            <div class="form-group">
                                                <label for="fullName">Full Name <span class="text-danger">*</span></label>
                                                <input type="text" class="form-control" id="fullName" name="full_name"
                                                    value="{{ old('full_name', $familyMember->full_name ?? '') }}"
                                                    placeholder="John Doe" required maxlength="50">
                                            </div>

                                            @php
                                                $standardRelationships = ['Parent', 'Sibling', 'Spouse', 'Child', 'Relatives', 'Guardian'];
                                                $currentRelationship = old('relationship', $familyMember->relationship ?? '');
                                                $isOtherRelationship = !in_array($currentRelationship, $standardRelationships) && !empty($currentRelationship);
                                            @endphp
                                            <div class="form-group">
                                                <label for="relationship">Relationship<span
                                                        class="text-danger">*</span></label>
                                                <select class="form-control" id="relationship" name="relationship" required
                                                    onchange="toggleOtherField()">
                                                    <option value="" disabled>Select Relationship</option>
                                                    <optgroup label="Immediate Family">
                                                        <option value="Parent"
                                                            {{ $currentRelationship == 'Parent' ? 'selected' : '' }}>
                                                            Parent</option>
                                                        <option value="Sibling"
                                                            {{ $currentRelationship == 'Sibling' ? 'selected' : '' }}>
                                                            Sibling</option>
                                                        <option value="Spouse"
                                                            {{ $currentRelationship == 'Spouse' ? 'selected' : '' }}>
                                                            Spouse</option>
                                                        <option value="Child"
                                                            {{ $currentRelationship == 'Child' ? 'selected' : '' }}>
                                                            Child</option>
                                                    </optgroup>
                                                    <optgroup label="Extended Family">
                                                        <option value="Relatives"
                                                            {{ $currentRelationship == 'Relatives' ? 'selected' : '' }}>
                                                            Relatives</option>
                                                        <option value="Guardian"
                                                            {{ $currentRelationship == 'Guardian' ? 'selected' : '' }}>
                                                            Guardian</option>
                                                    </optgroup>
                                                    <optgroup label="Other">
                                                        <option value="Other"
                                                            {{ $isOtherRelationship ? 'selected' : '' }}>
                                                            Other</option>
                                                    </optgroup>
                                                </select>
                                            </div>

                                            <div class="form-group" id="other-relationship-field" style="display: {{ $isOtherRelationship ? 'block' : 'none' }};">
                                                <label for="other_relationship">Please specify</label>
                                                <input type="text" class="form-control" name="other_relationship" 
                                                    id="other_relationship"
                                                    value="{{ old('other_relationship', $isOtherRelationship ? $currentRelationship : ($familyMember->other_relationship ?? '')) }}"
                                                    placeholder="Specify relationship" maxlength="30">
                                            </div>

                                            <div class="form-group">
                                                <label for="phoneNumber">Mobile</label>
                                                <input type="tel" class="form-control" id="phoneNumber"
                                                    name="phone_number"
                                                    value="{{ old('phone_number', $familyMember->phone_number ?? '') }}"
                                                    placeholder="0699 990 002" maxlength="13"
                                                    oninput="if(this.value.length > 13) this.value = this.value.slice(0, 13);">
                                            </div>

                                            @php
                                                $standardOccupations = ['Businessperson', 'Employed', 'Self-employed', 'Unemployed', 'Student', 'Retired'];
                                                $currentOccupation = old('occupation', $familyMember->occupation ?? '');
                                                $isOtherOccupation = !in_array($currentOccupation, $standardOccupations) && !empty($currentOccupation);
                                            @endphp
                                            <div class="form-group">
                                                <label for="occupation">Occupation <span class="text-danger">*</span></label>
                                                <select class="form-control" id="occupation" name="occupation" required
                                                    onchange="toggleOtherOccupationField()">
                                                    <option value="" disabled>Select Occupation</option>
                                                    <option value="Businessperson"
                                                        {{ $currentOccupation == 'Businessperson' ? 'selected' : '' }}>
                                                        Businessperson</option>
                                                    <option value="Employed"
                                                        {{ $currentOccupation == 'Employed' ? 'selected' : '' }}>
                                                        Employed</option>
                                                    <option value="Self-employed"
                                                        {{ $currentOccupation == 'Self-employed' ? 'selected' : '' }}>
                                                        Self-employed</option>
                                                    <option value="Unemployed"
                                                        {{ $currentOccupation == 'Unemployed' ? 'selected' : '' }}>
                                                        Unemployed</option>
                                                    <option value="Student"
                                                        {{ $currentOccupation == 'Student' ? 'selected' : '' }}>
                                                        Student</option>
                                                    <option value="Retired"
                                                        {{ $currentOccupation == 'Retired' ? 'selected' : '' }}>
                                                        Retired</option>
                                                    <option value="Other"
                                                        {{ $isOtherOccupation ? 'selected' : '' }}>
                                                        Other</option>
                                                </select>
                                            </div>

                                            <div class="form-group" id="other-occupation-field" style="display: {{ $isOtherOccupation ? 'block' : 'none' }};">
                                                <label for="otherOccupation">Please specify your occupation</label>
                                                <input type="text" class="form-control" id="otherOccupation"
                                                    name="other_occupation"
                                                    value="{{ old('other_occupation', $isOtherOccupation ? $currentOccupation : ($familyMember->other_occupation ?? '')) }}"
                                                    placeholder="Enter Occupation" maxlength="50">
                                            </div>
                                            
                                            @php
                                                $nextOfKinExists = $familyData->contains('next_of_kin', true);
                                            @endphp

                                            <div class="form-group">
                                                <label for="nextOfKin">Next of Kin</label>
                                                @if (!$nextOfKinExists || $familyMember->next_of_kin)
                                                    <div class="form-check">
                                                        <input type="checkbox" id="nextOfKin" name="next_of_kin" value="1"
                                                            class="form-check-input"
                                                            {{ old('next_of_kin', $familyMember->next_of_kin ?? '') ? 'checked' : '' }}>
                                                        <label class="form-check-label" for="nextOfKin">
                                                            Check if this person is your Next of Kin.
                                                        </label>
                                                    </div>
                                                @else
                                                    <p class="form-text text-muted">A Next of Kin has already been
                                                        selected.</p>
                                                @endif
                                            </div>

                                            <div class="d-flex gap-2">
                                                <a href="{{ route('profile.familyDetails') }}" 
                                                   class="btn btn-secondary">Cancel</a>
                                                <button type="submit" class="btn btn-primary">Update</button>
                                            </div>
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
                                                            <td>
                                                                <a href="{{ route('profile.editFamilyDetails', $detail->id) }}"
                                                                    class="btn btn-link p-0 m-0 text-warning"
                                                                    style="border: none; background: none;"
                                                                    title="Edit">
                                                                    <i class="fas fa-edit"></i>
                                                                </a>
                                                            </td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mt-3">
                            <a href="{{ route('profile.familyDetails') }}" class="btn btn-secondary">Back</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

<script>
    // Function to toggle visibility of the input field based on selection
    function toggleOtherField() {
        var relationship = document.getElementById('relationship');
        var otherField = document.getElementById('other-relationship-field');
        
        if (relationship && otherField) {
            if (relationship.value === 'Other') {
                otherField.style.display = 'block';
                var otherInput = document.getElementById('other_relationship');
                if (otherInput) {
                    otherInput.required = true;
                }
            } else {
                otherField.style.display = 'none';
                var otherInput = document.getElementById('other_relationship');
                if (otherInput) {
                    otherInput.required = false;
                    if (relationship.value !== 'Other') {
                        otherInput.value = '';
                    }
                }
            }
        }
    }

    function toggleOtherOccupationField() {
        var occupation = document.getElementById('occupation');
        var otherField = document.getElementById('other-occupation-field');
        
        if (occupation && otherField) {
            if (occupation.value === 'Other') {
                otherField.style.display = 'block';
                var otherInput = document.getElementById('otherOccupation');
                if (otherInput) {
                    otherInput.required = true;
                }
            } else {
                otherField.style.display = 'none';
                var otherInput = document.getElementById('otherOccupation');
                if (otherInput) {
                    otherInput.required = false;
                    if (occupation.value !== 'Other') {
                        otherInput.value = '';
                    }
                }
            }
        }
    }

    // Call the functions on page load
    document.addEventListener('DOMContentLoaded', function() {
        toggleOtherField();
        toggleOtherOccupationField();
        
        // Auto-capitalize full name
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
