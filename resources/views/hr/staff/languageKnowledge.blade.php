@extends('layouts.template2')
@section('breadcrumb')
    <div class="content container-fluid" style="background-color: #eff8f3;">
        <div class="page-header">
            <div class="row">
                <div class="col-sm-12">
                    <div class="page-sub-header d-flex justify-content-between align-items-center">
                        <h3 class="page-title mb-0">
                            <i class="fas fa-language me-2"></i>Language Knowledge - {{ $user->fname }} {{ $user->lname }}
                        </h3>
                        <a href="{{ route('employees_details.show', $user->id) }}" class="btn btn-secondary">
                            <i class="fas fa-arrow-left me-2"></i>Back to Employee Details
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <div class="container mt-5">
            <div class="alert alert-info mb-4">
                <i class="fas fa-info-circle me-2"></i>
                <strong>HR Mode:</strong> You are adding language knowledge for <strong>{{ $user->fname }} {{ $user->lname }}</strong>.
                <br><small>You can add multiple languages. All fields marked with <span class="text-danger">*</span> are required.</small>
            </div>

            <div class="row">
                <!-- Left Area: Add Language Form -->
                <div class="col-md-4">
                    <div class="card">
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

                        <div class="card-body">
                            <form action="{{ route('hr.employee.save-language-knowledge', $user->id) }}" method="POST" id="languageForm">
                                @csrf
                                <div class="form-group">
                                    <label for="language">Language:<span class="text-danger">* </span></label>
                                    <select id="language" name="language" class="form-control" required onchange="toggleOtherLanguageField()">
                                        <option value="" disabled selected>Select Language</option>
                                        @php
                                            // Define available languages
                                            $availableLanguages = ['English', 'Swahili', 'Sign Language'];
                                            // Get languages already selected by the user
                                            $selectedLanguages = $languageKnowledge->pluck('language')->toArray();
                                            // Filter out selected languages
                                            $filteredLanguages = array_diff($availableLanguages, $selectedLanguages);
                                        @endphp
                                        @foreach ($filteredLanguages as $lang)
                                            <option value="{{ $lang }}" {{ old('language') == $lang ? 'selected' : '' }}>{{ $lang }}</option>
                                        @endforeach
                                        <option value="Other" {{ old('language') == 'Other' ? 'selected' : '' }}>Other</option>
                                    </select>
                                    @error('language')
                                        <div class="text-danger">{{ $message }}</div>
                                    @enderror
                                </div>

                                {{-- Other Language Field --}}
                                <div class="form-group" id="other-language-field" style="display: none;">
                                    <label for="other_language">Please specify language<span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="other_language" name="other_language"
                                        value="{{ old('other_language') }}"
                                        placeholder="Enter language name" maxlength="100">
                                    <small class="form-text text-muted">Enter the name of the language if not listed above.</small>
                                </div>

                                <div class="form-group">
                                    <label for="speaking">Speaking Level:<span class="text-danger">*</span></label>
                                    <select id="speaking" name="speaking" class="form-control" required>
                                        <option value="" disabled selected>Select Speaking Level</option>
                                        <option value="Basic" {{ old('speaking') == 'Basic' ? 'selected' : '' }}>Basic
                                        </option>
                                        <option value="Good" {{ old('speaking') == 'Good' ? 'selected' : '' }}>Good
                                        </option>
                                        <option value="Very Good" {{ old('speaking') == 'Very Good' ? 'selected' : '' }}>
                                            Very Good</option>
                                        <option value="Excellent" {{ old('speaking') == 'Excellent' ? 'selected' : '' }}>
                                            Excellent</option>
                                    </select>
                                    @error('speaking')
                                        <div class="text-danger">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="form-group">
                                    <label for="reading">Reading Level:<span class="text-danger">*</span></label>
                                    <select id="reading" name="reading" class="form-control" required>
                                        <option value="" disabled selected>Select Reading Level</option>
                                        <option value="Basic" {{ old('reading') == 'Basic' ? 'selected' : '' }}>Basic
                                        </option>
                                        <option value="Good" {{ old('reading') == 'Good' ? 'selected' : '' }}>Good
                                        </option>
                                        <option value="Very Good" {{ old('reading') == 'Very Good' ? 'selected' : '' }}>
                                            Very Good</option>
                                        <option value="Excellent" {{ old('reading') == 'Excellent' ? 'selected' : '' }}>
                                            Excellent</option>
                                    </select>
                                    @error('reading')
                                        <div class="text-danger">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="form-group">
                                    <label for="writing">Writing Level:<span class="text-danger">*</span></label>
                                    <select id="writing" name="writing" class="form-control" required>
                                        <option value="" disabled selected>Select Writing Level</option>
                                        <option value="Basic" {{ old('writing') == 'Basic' ? 'selected' : '' }}>Basic
                                        </option>
                                        <option value="Good" {{ old('writing') == 'Good' ? 'selected' : '' }}>Good
                                        </option>
                                        <option value="Very Good" {{ old('writing') == 'Very Good' ? 'selected' : '' }}>
                                            Very Good</option>
                                        <option value="Excellent" {{ old('writing') == 'Excellent' ? 'selected' : '' }}>
                                            Excellent</option>
                                    </select>
                                    @error('writing')
                                        <div class="text-danger">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="d-flex justify-content-between align-items-center mt-3">
                                    <div class="d-flex gap-2 mt-3">
                                        <button type="submit" class="btn btn-primary">Add Language</button>
                                        <a href="{{ route('employees_details.show', $user->id) }}" class="btn btn-success">
                                            <i class="fas fa-check me-2"></i>Done
                                        </a>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Right Area: Display Selected Languages -->
                <div class="col-md-8">
                    <div class="card">
                        <div class="card-header">
                            <h5>Selected Language Knowledge</h5>
                        </div>
                        <div class="card-body">
                            <!-- Table to display languages -->
                            <table class="table table-bordered">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Language</th>
                                        <th>Speaking Level</th>
                                        <th>Reading Level</th>
                                        <th>Writing Level</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($languageKnowledge as $language)
                                        <tr>
                                            <td>{{ $loop->iteration }}</td>
                                            <td>{{ $language->language }}</td>
                                            <td>{{ $language->speaking }}</td>
                                            <td>{{ $language->reading }}</td>
                                            <td>{{ $language->writing }}</td>
                                            <td>
                                                <!-- Edit Button for each language -->
                                                <button class="btn btn-warning btn-sm edit-btn"
                                                    data-id="{{ $language->id }}"
                                                    data-language="{{ $language->language }}"
                                                    data-speaking="{{ $language->speaking }}"
                                                    data-reading="{{ $language->reading }}"
                                                    data-writing="{{ $language->writing }}">Edit</button>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>

                            <!-- Display message if no languages are added -->
                            @if ($languageKnowledge->isEmpty())
                                <p class="text-muted">No languages added yet.</p>
                            @endif
                            <div class="d-flex justify-content-end align-items-center mt-3">
                                <a href="{{ route('employees_details.show', $user->id) }}" class="btn btn-success">
                                    <i class="fas fa-check me-2"></i>Done
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- JavaScript for handling inline edit -->
                <script>
                    // Toggle Other Language Field
                    function toggleOtherLanguageField() {
                        const language = document.getElementById('language');
                        const otherField = document.getElementById('other-language-field');
                        const otherInput = document.getElementById('other_language');
                        
                        if (language && otherField) {
                            if (language.value === 'Other') {
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
                        toggleOtherLanguageField();

                        // Handle edit button clicks
                        document.querySelectorAll('.edit-btn').forEach(function(button) {
                            button.addEventListener('click', function() {
                                var language = button.getAttribute('data-language');
                                var speaking = button.getAttribute('data-speaking');
                                var reading = button.getAttribute('data-reading');
                                var writing = button.getAttribute('data-writing');
                                var languageId = button.getAttribute('data-id');

                                // Check if language is in the standard list
                                const standardLanguages = ['English', 'Swahili', 'Sign Language'];
                                const isOtherLanguage = !standardLanguages.includes(language);

                                // Populate the form with the selected language's data
                                const languageSelect = document.getElementById('language');
                                
                                if (isOtherLanguage) {
                                    // If it's an "Other" language, set to "Other" and show the field
                                    languageSelect.value = 'Other';
                                    toggleOtherLanguageField();
                                    document.getElementById('other_language').value = language;
                                } else {
                                    // If it's a standard language, just set the value
                                    languageSelect.value = language;
                                    toggleOtherLanguageField();
                                }

                                document.getElementById('speaking').value = speaking;
                                document.getElementById('reading').value = reading;
                                document.getElementById('writing').value = writing;

                                // For HR, we'll just add new entries (edit can be done by deleting and re-adding)
                                // var form = document.getElementById('languageForm');
                                // form.action = '/profile/languageKnowledge/' + languageId;

                                // Check if _method input already exists
                                var existingMethodInput = form.querySelector('input[name="_method"]');
                                if (existingMethodInput) {
                                    existingMethodInput.remove();
                                }

                                // Add the hidden _method input with value 'PUT'
                                var methodInput = document.createElement('input');
                                methodInput.setAttribute('type', 'hidden');
                                methodInput.setAttribute('name', '_method');
                                methodInput.setAttribute('value', 'PUT');
                                form.appendChild(methodInput);

                                // Temporarily add the selected language back to the dropdown for editing if it's not "Other"
                                if (!isOtherLanguage) {
                                    var optionExists = Array.from(languageSelect.options).some(opt => opt.value === language);
                                    if (!optionExists) {
                                        var option = document.createElement('option');
                                        option.value = language;
                                        option.text = language;
                                        languageSelect.appendChild(option);
                                    }
                                }
                            });
                        });

                        // Form validation
                        const form = document.getElementById('languageForm');
                        if (form) {
                            form.addEventListener('submit', function(e) {
                                const language = document.getElementById('language').value;
                                
                                if (language === 'Other') {
                                    const otherLanguage = document.getElementById('other_language').value;
                                    if (!otherLanguage || otherLanguage.trim() === '') {
                                        e.preventDefault();
                                        alert('Please specify the language name.');
                                        return false;
                                    }
                                }
                            });
                        }
                    });
                </script>
            </div>
        </div>
    </div>
@endsection
