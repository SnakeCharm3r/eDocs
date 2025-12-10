@extends('layouts.template2')
@section('breadcrumb')
    <div class="content container-fluid" style="background-color: #eff8f3;">
        <div class="page-header">
            <div class="row">
                <div class="col-sm-12">
                    <div class="page-sub-header">
                        <h3 class="page-title">Edit Language Knowledge</h3>
                    </div>
                </div>
            </div>
        </div>

        <div class="container mt-5">
            <div class="card">
                <div class="card-body">
                    <form action="{{ route('profile.languageKnowledge.update', $languageKnowledge->id) }}" method="POST">
                        @csrf
                        @method('PUT')

                        @if ($errors->any())
                            <div class="alert alert-danger">
                                <ul>
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        @php
                            $standardLanguages = ['English', 'Swahili', 'Sign Language'];
                            $currentLanguage = old('language', $languageKnowledge->language ?? '');
                            $isOtherLanguage = !in_array($currentLanguage, $standardLanguages) && !empty($currentLanguage);
                        @endphp

                        <!-- Language Selection Form -->
                        <div class="form-group">
                            <label for="language">Language:<span class="text-danger">* </span></label>
                            <select id="language" name="language" class="form-control" required onchange="toggleOtherLanguageField()">
                                <option value="" disabled>Select Language</option>
                                <option value="English" {{ $currentLanguage == 'English' ? 'selected' : '' }}>English</option>
                                <option value="Swahili" {{ $currentLanguage == 'Swahili' ? 'selected' : '' }}>Swahili</option>
                                <option value="Sign Language" {{ $currentLanguage == 'Sign Language' ? 'selected' : '' }}>Sign Language</option>
                                <option value="Other" {{ $isOtherLanguage ? 'selected' : '' }}>Other</option>
                            </select>
                            @error('language')
                                <div class="text-danger">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Other Language Field -->
                        <div class="form-group" id="other-language-field" style="display: {{ $isOtherLanguage ? 'block' : 'none' }};">
                            <label for="other_language">Please specify language<span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="other_language" name="other_language"
                                value="{{ old('other_language', $isOtherLanguage ? $currentLanguage : '') }}"
                                placeholder="Enter language name" maxlength="100">
                            <small class="form-text text-muted">Enter the name of the language if not listed above.</small>
                        </div>

                        <!-- Speaking Level -->
                        <div class="form-group">
                            <label for="speaking">Speaking Level:<span class="text-danger">*</span></label>
                            <select id="speaking" name="speaking" class="form-control" required>
                                <option value="" disabled>Select Speaking Level</option>
                                <option value="Basic" {{ old('speaking', $languageKnowledge->speaking) == 'Basic' ? 'selected' : '' }}>Basic</option>
                                <option value="Good" {{ old('speaking', $languageKnowledge->speaking) == 'Good' ? 'selected' : '' }}>Good</option>
                                <option value="Very Good" {{ old('speaking', $languageKnowledge->speaking) == 'Very Good' ? 'selected' : '' }}>Very Good</option>
                                <option value="Excellent" {{ old('speaking', $languageKnowledge->speaking) == 'Excellent' ? 'selected' : '' }}>Excellent</option>
                            </select>
                            @error('speaking')
                                <div class="text-danger">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Reading Level -->
                        <div class="form-group">
                            <label for="reading">Reading Level:<span class="text-danger">*</span></label>
                            <select id="reading" name="reading" class="form-control" required>
                                <option value="" disabled>Select Reading Level</option>
                                <option value="Basic" {{ old('reading', $languageKnowledge->reading) == 'Basic' ? 'selected' : '' }}>Basic</option>
                                <option value="Good" {{ old('reading', $languageKnowledge->reading) == 'Good' ? 'selected' : '' }}>Good</option>
                                <option value="Very Good" {{ old('reading', $languageKnowledge->reading) == 'Very Good' ? 'selected' : '' }}>Very Good</option>
                                <option value="Excellent" {{ old('reading', $languageKnowledge->reading) == 'Excellent' ? 'selected' : '' }}>Excellent</option>
                            </select>
                            @error('reading')
                                <div class="text-danger">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Writing Level -->
                        <div class="form-group">
                            <label for="writing">Writing Level:<span class="text-danger">*</span></label>
                            <select id="writing" name="writing" class="form-control" required>
                                <option value="" disabled>Select Writing Level</option>
                                <option value="Basic" {{ old('writing', $languageKnowledge->writing) == 'Basic' ? 'selected' : '' }}>Basic</option>
                                <option value="Good" {{ old('writing', $languageKnowledge->writing) == 'Good' ? 'selected' : '' }}>Good</option>
                                <option value="Very Good" {{ old('writing', $languageKnowledge->writing) == 'Very Good' ? 'selected' : '' }}>Very Good</option>
                                <option value="Excellent" {{ old('writing', $languageKnowledge->writing) == 'Excellent' ? 'selected' : '' }}>Excellent</option>
                            </select>
                            @error('writing')
                                <div class="text-danger">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="d-flex justify-content-between align-items-center mt-3">
                            <a href="{{ route('profile.languageKnowledge') }}" class="btn btn-secondary">Cancel</a>
                            <button type="submit" class="btn btn-primary">Update Language Knowledge</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

<script>
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
                    if (language.value !== 'Other') {
                        otherInput.value = '';
                    }
                }
            }
        }
    }

    document.addEventListener('DOMContentLoaded', function() {
        toggleOtherLanguageField();
    });
</script>
