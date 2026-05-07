<form action="{{ route('profile.storeFamily') }}" method="POST">
    @csrf

    <div class="row">
        <!-- Next of Kin 1 -->
        <div class="col-12 col-md-6">
            <div class="form-group">
                <label>Full Name (Next of Kin 1)</label>
                <input type="text" class="form-control" name="full_name_1" value="{{ old('full_name_1') }}" required>
            </div>
            <div class="form-group">
                <label>Relationship (Next of Kin 1)</label>
                <input type="text" class="form-control" name="relationship_1" value="{{ old('relationship_1') }}" required>
            </div>
        </div>
        <div class="col-12 col-md-6">
            <div class="form-group">
                <label>Mobile (Next of Kin 1)</label>
                <input type="text" class="form-control" name="phone_number_1" value="{{ old('phone_number_1') }}" required>
            </div>
            <div class="form-group">
                <label>Date of Birth (Next of Kin 1)</label>
                <input type="date" class="form-control" name="dob_1" value="{{ old('dob_1') }}" required>
            </div>
        </div>
        <div class="col-12 col-md-6">
            <div class="form-group">
                <label>Occupation (Next of Kin 1)</label>
                <input type="text" class="form-control" name="occupation_1" value="{{ old('occupation_1') }}" required>
            </div>
        </div>
    </div>
    <hr>
    <div class="row">
        <!-- Next of Kin 2 -->
        <div class="col-12 col-md-6">
            <div class="form-group">
                <label>Full Name (Next of Kin 2)</label>
                <input type="text" class="form-control" name="full_name_2" value="{{ old('full_name_2') }}" required>
            </div>
            <div class="form-group">
                <label>Relationship (Next of Kin 2)</label>
                <input type="text" class="form-control" name="relationship_2" value="{{ old('relationship_2') }}" required>
            </div>
        </div>
        <div class="col-12 col-md-6">
            <div class="form-group">
                <label>Mobile (Next of Kin 2)</label>
                <input type="text" class="form-control" name="phone_number_2" value="{{ old('phone_number_2') }}" required>
            </div>
            <div class="form-group">
                <label>Date of Birth (Next of Kin 2)</label>
                <input type="date" class="form-control" name="dob_2" value="{{ old('dob_2') }}" required>
            </div>
        </div>
        <div class="col-12 col-md-6">
            <div class="form-group">
                <label>Occupation (Next of Kin 2)</label>
                <input type="text" class="form-control" name="occupation_2" value="{{ old('occupation_2') }}" required>
            </div>
        </div>
    </div>
    <div class="row mt-3">
        <div class="col-md-12">
            <div class="form-group">
                <label>First Person to Contact in Case of Emergency:</label>
                <select class="form-control" name="emergency_contact" required>
                    <option value="" disabled {{ old('emergency_contact') == '' ? 'selected' : '' }}>Select Emergency Contact</option>
                    <option value="next_of_kin1" {{ old('emergency_contact') == 'next_of_kin1' ? 'selected' : '' }}>Next of Kin 1</option>
                    <option value="next_of_kin2" {{ old('emergency_contact') == 'next_of_kin2' ? 'selected' : '' }}>Next of Kin 2</option>
                </select>
            </div>
        </div>
    </div>
    <div class="row mt-3">
        <div class="col d-flex justify-content-end">
            <button class="btn btn-primary" type="submit">Add Family Details</button>
        </div>
    </div>
</form>