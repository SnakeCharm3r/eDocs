@extends('layouts.template2')
@section('breadcrumb')
    <div class="content container-fluid" style="background-color: #eff8f3;">
        <div class="page-header">
            <div class="row">
                <div class="col-sm-12">
                    <div class="page-sub-header">
                        <h3 class="page-title">Language Knowledge Details</h3>
                    </div>
                </div>
            </div>
        </div>

        <div class="container mt-5">
            {{-- Progress Bar --}}
            <div class="container">
                <div class="d-flex align-items-center justify-content-between">
                    <!-- Step Title on Left -->
                    <h2 class="my-4" style="margin: 0; font-size: 18px;">Step {{ session('current_step') }}: Language
                        Knowledge Details</h2>

                    <!-- Progress Bar on Right -->
                    <div class="progress flex-grow-1 ml-3" style="max-width: 70%;">
                        <div class="progress-bar progress-bar-success progress-bar-striped" role="progressbar"
                            aria-valuenow="{{ (session('current_step') / 7) * 100 }}" aria-valuemin="0" aria-valuemax="100"
                            style="width: {{ (session('current_step') / 7) * 100 }}%;">
                            Step {{ session('current_step') }} of 7
                        </div>
                    </div>
                </div>
                <small class="form-text text-muted">Please add at least 2 languages. All fields marked with <span class="text-danger">*</span> are required.</small>
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
                            <form action="{{ route('languageKnowledgeStore') }}" method="POST" id="languageForm">
                                @csrf
                                <div class="form-group">
                                    <label for="language">Language:<span class="text-danger">* </span></label>
                                    <select id="language" name="language" class="form-control" required onchange="toggleOtherLanguageField()">
                                        <option value="" disabled selected>Select Language</option>
                                        @php
                                            // Define available languages
                                            $availableLanguages = ['English', 'Swahili', 'Sign Language'];
                                            // Get languages already selected by the user
                                            $selectedLanguages = $user->languageKnowledge->pluck('language')->toArray();
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
                                    <button type="submit" class="btn btn-primary mt-3 w-5">Save</button>
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
                                    @foreach ($user->languageKnowledge as $language)
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
                            @if ($user->languageKnowledge->isEmpty())
                                <p class="text-muted">No languages added yet.</p>
                            @endif
                            <div class="d-flex justify-content-between align-items-center mt-3">
                                <a href="{{ route('profile.healthDetails') }}" class="btn btn-secondary">Previous</a>

                                <!-- Check if the user has exactly 2 languages -->
                                @if ($userLanguageCount >= 2)
                                    <!-- If the user has two or more languages, show the button to proceed -->
                                    <a href="{{ route('profile.ccbrt_relation') }}" class="btn btn-primary mt-3 w-5">Next</a>
                                @else
                                    <!-- If the user has less than 2 languages, disable the button or show a message -->
                                    <button type="button" class="btn btn-primary mt-3 w-5" disabled>Next</button>
                                @endif
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

                                // Change the form action to the update route using the language ID
                                var form = document.getElementById('languageForm');
                                form.action = '/profile/languageKnowledge/' + languageId;

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
