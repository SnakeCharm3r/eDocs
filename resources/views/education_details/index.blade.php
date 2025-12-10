@extends('layouts.template')

@section('breadcrumb')
    @include('sweetalert::alert')
@endsection

@section('content')
    <div class="page-wrapper">
        <div class="content container-fluid">
            <div class="row">
                <div class="col-md-12">
                    <div class="page-sub-header">
                        <h3 class="page-title">Education Details</h3>
                    </div>
                    <div class="card">
                        <div class="card-body">
                            @if (session('success'))
                                <div class="alert alert-success alert-dismissible fade show" role="alert">
                                    {{ session('success') }}
                                    <button type="button" class="btn-close" data-bs-dismiss="alert"
                                        aria-label="Close"></button>
                                </div>
                            @endif
                            @if ($errors->any())
                                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                    <ul>
                                        @foreach ($errors->all() as $error)
                                            <li>{{ $error }}</li>
                                        @endforeach
                                    </ul>
                                    <button type="button" class="btn-close" data-bs-dismiss="alert"
                                        aria-label="Close"></button>
                                </div>
                            @endif

                            <form action="{{ route('education.update') }}" method="POST" enctype="multipart/form-data">
                                @csrf
                                <div class="row">
                                    <div class="col-md-4">
                                        <label for="education_level" class="form-label">Select Education Level</label>
                                        <select id="education_level" class="form-control" name="education_level"
                                            onchange="showUploadFields(this.value)">
                                            <option value="">-- Select Level --</option>
                                            <option value="primary">Primary Education</option>
                                            <option value="o_level">O-Level (Form 4)</option>
                                            <option value="a_level">A-Level (Form 6)</option>
                                            <option value="certificate">Certificate</option>
                                            <option value="diploma">Diploma</option>
                                            <option value="degree">Degree</option>
                                            <option value="masters">Masters</option>
                                            <option value="phd">PhD</option>
                                        </select>
                                    </div>
                                </div>

                                @foreach ($education_levels as $level => $label)
                                    <div class="row education_upload" id="{{ $level }}" style="display: none">
                                        <div class="col-md-4">
                                            <label for="{{ $level }}_institution"
                                                class="form-label">{{ $label }} Institution</label>
                                            <input type="text" name="{{ $level }}_institution"
                                                class="form-control"
                                                value="{{ old("{$level}_institution", $user->{"{$level}_institution"} ?? '') }}">
                                        </div>
                                        <div class="col-md-4">
                                            <label for="{{ $level }}_country" class="form-label">{{ $label }}
                                                Country</label>
                                            <select name="{{ $level }}_country" class="form-control">
                                                <option value="">-- Select Country --</option>
                                                @foreach (['Tanzania', 'Kenya', 'Uganda', 'Rwanda', 'Burundi', 'South Sudan', 'Nigeria', 'South Africa', 'Ghana', 'Ethiopia', 'Algeria', 'Morocco', 'Egypt', 'United States', 'United Kingdom', 'Canada', 'Australia', 'India', 'China', 'Germany', 'France', 'Japan', 'Brazil', 'Other'] as $country)
                                                    <option value="{{ $country }}"
                                                        {{ old("{$level}_country", $user->{"{$level}_country"} ?? '') == $country ? 'selected' : '' }}>
                                                        {{ $country }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-md-4">
                                            <label for="{{ $level }}_start_year" class="form-label">Start
                                                Year</label>
                                            <select name="{{ $level }}_start_year" class="form-control">
                                                <option value="">-- Select Year --</option>
                                                @for ($year = date('Y'); $year >= 1900; $year--)
                                                    <option value="{{ $year }}"
                                                        {{ old("{$level}_start_year", $user->{"{$level}_start_year"} ?? '') == $year ? 'selected' : '' }}>
                                                        {{ $year }}</option>
                                                @endfor
                                            </select>
                                        </div>
                                        <div class="col-md-4">
                                            <label for="{{ $level }}_completion_year" class="form-label">Completion
                                                Year</label>
                                            <select name="{{ $level }}_completion_year" class="form-control">
                                                <option value="">-- Select Year --</option>
                                                @for ($year = date('Y'); $year >= 1900; $year--)
                                                    <option value="{{ $year }}"
                                                        {{ old("{$level}_completion_year", $user->{"{$level}_completion_year"} ?? '') == $year ? 'selected' : '' }}>
                                                        {{ $year }}</option>
                                                @endfor
                                            </select>
                                        </div>
                                        <div class="col-md-4">
                                            <label for="{{ $level }}_certificate" class="form-label">Upload
                                                {{ $label }} Certificate (PDF, Max: 2MB)</label>
                                            @if ($user->{"{$level}_certificate"})
                                                <a href="{{ asset('storage/' . $user->{"{$level}_certificate"}) }}"
                                                    target="_blank">View Certificate</a>
                                            @endif
                                            <input type="file" name="{{ $level }}_certificate"
                                                class="form-control" accept=".pdf">
                                        </div>
                                        @if (in_array($level, ['certificate', 'diploma', 'degree', 'masters', 'phd']))
                                            <div class="col-md-4">
                                                <label for="{{ $level }}_transcript" class="form-label">Upload
                                                    {{ $label }} Transcript (PDF, Max: 2MB)</label>
                                                @if ($user->{"{$level}_transcript"})
                                                    <a href="{{ asset('storage/' . $user->{"{$level}_transcript"}) }}"
                                                        target="_blank">View Transcript</a>
                                                @endif
                                                <input type="file" name="{{ $level }}_transcript"
                                                    class="form-control" accept=".pdf">
                                            </div>
                                        @endif
                                    </div>
                                @endforeach

                                <div class="d-flex justify-content-center">
                                    <button type="submit" class="btn btn-primary mt-3 w-50">Save and Continue</button>
                                </div>
                            </form>

                            <br>
                            <h6>Saved Academic Qualifications</h6>
                            <div class="table-responsive">
                                <table class="table table-bordered">
                                    <thead>
                                        <tr>
                                            <th>Level</th>
                                            <th>Name</th>
                                            <th>Institution</th>
                                            <th>Country</th>
                                            <th>Attachment</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($education_levels as $level => $label)
                                            @if ($user->{"{$level}_institution"})
                                                <tr>
                                                    <td>{{ $label }}</td>
                                                    <td>{{ $label }}</td>
                                                    <td>{{ $user->{"{$level}_institution"} }}</td>
                                                    <td>{{ $user->{"{$level}_country"} ?? 'N/A' }}</td>
                                                    <td>
                                                        @if ($user->{"{$level}_certificate"})
                                                            <a href="{{ asset('storage/' . $user->{"{$level}_certificate"}) }}"
                                                                target="_blank">Certificate</a>
                                                            @if (in_array($level, ['certificate', 'diploma', 'degree', 'masters', 'phd']) && $user->{"{$level}_transcript"})
                                                                | <a href="{{ asset('storage/' . $user->{"{$level}_transcript"}) }}"
                                                                    target="_blank">Transcript</a>
                                                            @endif
                                                        @else
                                                            None
                                                        @endif
                                                    </td>
                                                    <td>
                                                        <button onclick="showUploadFields('{{ $level }}')"
                                                            class="btn btn-warning btn-sm"><i
                                                                class="fas fa-pencil-alt me-1"></i>Edit</button>
                                                        @if (Auth::check() && Auth::user()->hasAnyRole('hr', 'super-admin', 'Admin'))
                                                            <form action="{{ route('education.update') }}" method="POST"
                                                                style="display: inline;"
                                                                onsubmit="return confirm('Are you sure you want to delete this qualification?');">
                                                                @csrf
                                                                <input type="hidden" name="delete_level"
                                                                    value="{{ $level }}">
                                                                <button type="submit" class="btn btn-danger btn-sm"><i
                                                                        class="fas fa-trash-alt me-1"></i>Delete</button>
                                                            </form>
                                                        @endif
                                                    </td>
                                                </tr>
                                            @endif
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function showUploadFields(level = '') {
            const sections = ['primary', 'o_level', 'a_level', 'certificate', 'diploma', 'degree', 'masters', 'phd'];
            sections.forEach(section => {
                document.getElementById(section).style.display = section === level ? 'block' : 'none';
            });
            document.getElementById('education_level').value = level;
        }

        // Call showUploadFields on page load if editing
        window.onload = function() {
            @if (session('edit_level'))
                showUploadFields('{{ session('edit_level') }}');
            @endif
        };
    </script>
@endsection
