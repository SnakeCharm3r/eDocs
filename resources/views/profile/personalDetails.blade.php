@extends('layouts.template2')
@section('breadcrumb')
    <div class="content container-fluid" style="background-color: #eff8f3;">
        <div class="page-header">
            <div class="row">
                <div class="col-sm-12">
                    <div class="page-sub-header">
                        <h3 class="page-title">Personal Details</h3>
                    </div>
                </div>
            </div>
        </div>

        <div class="container-fluid my-5" style="max-width: 95%;">
            {{-- Progress Bar --}}
            <div class="container-fluid">
                <div class="d-flex align-items-center justify-content-between">
                    <!-- Step Title on Left -->
                    <h2 class="my-4" style="margin: 0; font-size: 18px;">Step {{ session('current_step') }}: Personal
                        Details</h2>

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

            <form action="{{ route('profile.personalDetails') }}" method="POST" enctype="multipart/form-data"
                class="p-4">
                @csrf
                @if(isset($isHrEditing) && $isHrEditing)
                    <input type="hidden" name="user_id" value="{{ $user->id }}">
                @endif
                @if ($errors->any())
                    <div class="alert alert-danger">
                        <ul>
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <div class="row mb-3">
                    <!-- Input field for marital status -->
                    <div class="col-md-4">
                        <label for="marital_status" class="form-label">
                            Marital Status <span class="text-danger">*</span>
                        </label>
                        <select class="form-select" name="marital_status" id="marital_status" required
                            aria-describedby="marital_status_error">
                            <option value="" disabled @selected(strtolower(old('marital_status', $user->marital_status ?? '')) == '')>
                                Select Marital Status
                            </option>
                            <option value="single" @selected(strtolower(old('marital_status', $user->marital_status ?? '')) == 'single')>
                                Single
                            </option>
                            <option value="married" @selected(strtolower(old('marital_status', $user->marital_status ?? '')) == 'married')>
                                Married
                            </option>
                            <option value="divorced" @selected(strtolower(old('marital_status', $user->marital_status ?? '')) == 'divorced')>
                                Divorced
                            </option>
                            <option value="Widower" @selected(strtolower(old('marital_status', $user->marital_status ?? '')) == 'Widower')>
                                Widower
                            </option>
                        </select>
                        <small id="marital_status_error" class="text-danger" style="display: none;">
                            Please select a marital status.
                        </small>
                    </div>


                    <!-- Input field for Gender -->
                    <div class="col-md-4">
                        <label for="gender" class="form-label">Gender<span class="text-danger">*</span></label>
                        <select class="form-select" name="gender" id="gender" required aria-describedby="gender_error">
                            <option value="" disabled
                                {{ old('gender', $user->gender ?? '') == '' ? 'selected' : '' }}>Select Gender</option>
                            <option value="male" {{ old('gender', $user->gender ?? '') == 'male' ? 'selected' : '' }}>
                                Male</option>
                            <option value="female" {{ old('gender', $user->gender ?? '') == 'female' ? 'selected' : '' }}>
                                Female</option>
                        </select>
                        <small id="gender_error" class="text-danger" style="display: none;">Please select a gender.</small>
                    </div>

                    <div class="col-md-4">
                        <label for="religion" class="form-label">Religion<span class="text-danger">*</span></label>
                        <select class="form-control" name="religion" id="religion-select" required
                            aria-describedby="religion_error">
                            <option value="" disabled
                                {{ old('religion', $user->religion ?? '') == '' ? 'selected' : '' }}>Select Religion
                            </option>
                            <option value="Christian"
                                {{ old('religion', $user->religion ?? '') == 'Christian' ? 'selected' : '' }}>Christian
                            </option>
                            <option value="Islam"
                                {{ old('religion', $user->religion ?? '') == 'Islam' ? 'selected' : '' }}>Muslim</option>
                            <option value="Hindu"
                                {{ old('religion', $user->religion ?? '') == 'Hindu' ? 'selected' : '' }}>Hindu</option>
                            <option value="Buddhist"
                                {{ old('religion', $user->religion ?? '') == 'Buddhist' ? 'selected' : '' }}>Buddhist
                            </option>
                            <option value="Other"
                                {{ old('religion', $user->religion ?? '') == 'Other' ? 'selected' : '' }}>Other</option>
                        </select>
                        <input class="form-control mt-2" type="text" name="religion_other" id="religion-other"
                            placeholder="Please specify your religion" style="display: none;"
                            value="{{ old('religion_other', $user->religion_other ?? '') }}" maxlength="20"
                            aria-describedby="religion_other_error">
                        <small id="religion_error" class="text-danger" style="display: none;">Please select a
                            religion.</small>
                        <small id="religion_other_error" class="text-danger" style="display: none;">Please specify your
                            religion.</small>
                    </div>
                </div>

                <!-- Input field for Region and other location -->
                <div class="row mb-3">
                    <div class="col-md-4">
                        <label for="region" class="form-label">Region<span class="text-danger">* </span><small
                                class="form-text text-muted">Please select your current region</small></label>
                        <select class="form-control" name="region" id="region" required aria-describedby="region_error">
                            <option value="" disabled
                                {{ old('region', $user->region ?? '') == '' ? 'selected' : '' }}>Select Region</option>
                            @foreach (['Arusha', 'Dar es Salaam', 'Dodoma', 'Geita', 'Iringa', 'Kagera', 'Katavi', 'Kigoma', 'Kilimanjaro', 'Lindi', 'Manyara', 'Mara', 'Mbeya', 'Morogoro', 'Mtwara', 'Mwanza', 'Njombe', 'Pemba North', 'Pemba South', 'Pwani', 'Rukwa', 'Ruvuma', 'Shinyanga', 'Simiyu', 'Singida', 'Songwe', 'Tabora', 'Tanga', 'Zanzibar Central/South', 'Zanzibar North', 'Zanzibar Urban/West'] as $region)
                                <option value="{{ $region }}"
                                    {{ old('region', $user->region ?? '') == $region ? 'selected' : '' }}>
                                    {{ $region }}
                                </option>
                            @endforeach
                        </select>
                        <small id="region_error" class="text-danger" style="display: none;">Please select a region.</small>
                    </div>
                    <div class="col-md-4">
                        <label for="district" class="form-label">District<span class="text-danger">*</span></label>
                        <select class="form-control" name="district" id="district" required
                            aria-describedby="district_error">
                            <option value="" disabled>Select District</option>
                        </select>
                        <small id="district_error" class="text-danger" style="display: none;">Please select a
                            district.</small>
                    </div>
                    <div class="col-md-4">
                        <label for="street" class="form-label">Street<span class="text-danger">*</span></label>
                        <input class="form-control" type="text" name="street" id="street"
                            placeholder="Enter Street" value="{{ old('street', $user->street ?? '') }}" required
                            maxlength="20" aria-describedby="street_error">
                        <small id="street_error" class="text-danger" style="display: none;">Please enter a valid street
                            name (max 20 characters).</small>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-4">
                        <label for="box_no" class="form-label">Box No</label>
                        <input class="form-control" type="number" name="box_no" id="box_no" placeholder="Box No"
                            value="{{ old('box_no', $user->box_no ?? '') }}" min="0" max="999999"
                            aria-describedby="box_no_error">
                        <small id="box_no_error" class="text-danger" style="display: none;">Please enter a valid box
                            number (0-999999).</small>
                    </div>
                    <div class="col-md-4">
                        <label for="plot_no" class="form-label">Plot No</label>
                        <input class="form-control" type="number" name="plot_no" id="plot_no" placeholder="Plot No"
                            value="{{ old('plot_no', $user->plot_no ?? '') }}" min="0" max="999999"
                            aria-describedby="plot_no_error">
                        <small id="plot_no_error" class="text-danger" style="display: none;">Please enter a valid plot
                            number (0-999999).</small>
                    </div>
                    <div class="col-md-4">
                        <label for="popular_landmark" class="form-label">Popular Landmark<span
                                class="text-danger">*</span></label>
                        <input class="form-control" type="text" name="popular_landmark" id="popular_landmark"
                            placeholder="Near well-known area"
                            value="{{ old('popular_landmark', $user->popular_landmark ?? '') }}" required maxlength="100"
                            aria-describedby="popular_landmark_error">
                        <small id="popular_landmark_error" class="text-danger" style="display: none;">Please enter a
                            valid landmark (max 100 characters).</small>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-4">
                        <label for="house_no" class="form-label">House Number</label>
                        <input class="form-control" type="text" name="house_no" id="house_no"
                            placeholder="House Number" pattern="^[A-Za-z0-9\s]+$" maxlength="20"
                            value="{{ old('house_no', $user->house_no ?? '') }}" aria-describedby="house_no_error">
                        <small id="house_no_error" class="text-danger" style="display: none;">Please enter a valid house
                            number (alphanumeric, max 20 characters).</small>
                    </div>

                    <div class="col-md-4">
                        <label for="professional_reg_number" class="form-label">
                            Professional Registration Number
                            @if (isset($isClinicalDepartment) && $isClinicalDepartment)
                                <span class="text-danger">*</span>
                            @else
                                <small class="text-muted">(Optional)</small>
                            @endif
                        </label>
                        @php
                            $existingRegNumber = old('professional_reg_number', $user->professional_reg_number ?? '');
                            $existingNumber = $existingRegNumber;

                            // Parse existing value if it's in format "TYPE: NUMBER" and extract just the number
if (preg_match('/^([A-Z]+):\s*(.+)$/', $existingRegNumber, $matches)) {
                                $existingNumber = $matches[2];
                            }
                        @endphp
                        <input class="form-control @error('professional_reg_number') is-invalid @enderror" type="text"
                            name="professional_reg_number" id="professional_reg_number"
                            placeholder="e.g. PH123456 or 12345" value="{{ $existingNumber }}" maxlength="50"
                            @if (isset($isClinicalDepartment) && $isClinicalDepartment) required @endif
                            aria-describedby="professional_reg_number_error">

                        @error('professional_reg_number')
                            <small id="professional_reg_number_error" class="text-danger">{{ $message }}</small>
                        @enderror
                        @if (isset($isClinicalDepartment) && $isClinicalDepartment)
                            <small class="form-text text-muted">Required for users in clinical job titles.</small>
                        @else
                            <small class="form-text text-muted">Optional: If you have one (e.g.,
                                professional licenses, etc.).</small>
                        @endif
                    </div>


                    <div class="col-md-4">
                        <label for="nationality" class="form-label">Nationality<span class="text-danger">*</span></label>
                        <select class="form-control" name="nationality" id="nationality" required
                            aria-describedby="nationality_error">
                            <option value="" disabled
                                {{ old('nationality', $user->nationality ?? '') == '' ? 'selected' : '' }}>
                                Select Nationality</option>
                            @foreach ([
            'Tanzania',
            'Afghanistan',
            'Albania',
            'Algeria',
            'Andorra',
            'Angola',
            'Argentina',
            'Armenia',
            'Australia',
            'Austria',
            'Azerbaijan',
            'Bahamas',
            'Bahrain',
            'Bangladesh',
            'Barbados',
            'Belarus',
            'Belgium',
            'Belize',
            'Benin',
            'Bhutan',
            'Bolivia',
            'Bosnia and Herzegovina',
            'Botswana',
            'Brazil',
            'Brunei',
            'Bulgaria',
            'Burkina Faso',
            'Burundi',
            'Cambodia',
            'Cameroon',
            'Canada',
            'Cape Verde',
            'Central African Republic',
            'Chad',
            'Chile',
            'China',
            'Colombia',
            'Comoros',
            'Congo',
            'Costa Rica',
            'Croatia',
            'Cuba',
            'Cyprus',
            'Czech Republic',
            'Denmark',
            'Djibouti',
            'Dominica',
            'Dominican Republic',
            'DR Congo',
            'Ecuador',
            'Egypt',
            'El Salvador',
            'Equatorial Guinea',
            'Eritrea',
            'Estonia',
            'Eswatini',
            'Ethiopia',
            'Fiji',
            'Finland',
            'France',
            'Gabon',
            'Gambia',
            'Georgia',
            'Germany',
            'Ghana',
            'Greece',
            'Grenada',
            'Guatemala',
            'Guinea',
            'Guinea-Bissau',
            'Guyana',
            'Haiti',
            'Honduras',
            'Hungary',
            'Iceland',
            'India',
            'Indonesia',
            'Iran',
            'Iraq',
            'Ireland',
            'Israel',
            'Italy',
            'Ivory Coast',
            'Jamaica',
            'Japan',
            'Jordan',
            'Kazakhstan',
            'Kenya',
            'Kiribati',
            'Kuwait',
            'Kyrgyzstan',
            'Laos',
            'Latvia',
            'Lebanon',
            'Lesotho',
            'Liberia',
            'Libya',
            'Liechtenstein',
            'Lithuania',
            'Luxembourg',
            'Madagascar',
            'Malawi',
            'Malaysia',
            'Maldives',
            'Mali',
            'Malta',
            'Marshall Islands',
            'Mauritania',
            'Mauritius',
            'Mexico',
            'Micronesia',
            'Moldova',
            'Monaco',
            'Mongolia',
            'Montenegro',
            'Morocco',
            'Mozambique',
            'Myanmar',
            'Namibia',
            'Nauru',
            'Nepal',
            'Netherlands',
            'New Zealand',
            'Nicaragua',
            'Niger',
            'Nigeria',
            'North Korea',
            'North Macedonia',
            'Norway',
            'Oman',
            'Pakistan',
            'Palau',
            'Palestine',
            'Panama',
            'Papua New Guinea',
            'Paraguay',
            'Peru',
            'Philippines',
            'Poland',
            'Portugal',
            'Qatar',
            'Romania',
            'Russia',
            'Rwanda',
            'Saint Kitts and Nevis',
            'Saint Lucia',
            'Saint Vincent and the Grenadines',
            'Samoa',
            'San Marino',
            'Sao Tome and Principe',
            'Saudi Arabia',
            'Senegal',
            'Serbia',
            'Seychelles',
            'Sierra Leone',
            'Singapore',
            'Slovakia',
            'Slovenia',
            'Solomon Islands',
            'Somalia',
            'South Africa',
            'South Korea',
            'South Sudan',
            'Spain',
            'Sri Lanka',
            'Sudan',
            'Suriname',
            'Sweden',
            'Switzerland',
            'Syria',
            'Taiwan',
            'Tajikistan',
            'Thailand',
            'Timor-Leste',
            'Togo',
            'Tonga',
            'Trinidad and Tobago',
            'Tunisia',
            'Turkey',
            'Turkmenistan',
            'Tuvalu',
            'Uganda',
            'Ukraine',
            'United Arab Emirates',
            'United Kingdom',
            'United States',
            'Uruguay',
            'Uzbekistan',
            'Vanuatu',
            'Vatican City',
            'Venezuela',
            'Vietnam',
            'Yemen',
            'Zambia',
            'Zimbabwe',
        ] as $country)
                                <option value="{{ $country }}"
                                    {{ old('nationality', $user->nationality ?? 'Tanzania') == $country ? 'selected' : '' }}>
                                    {{ $country }}
                                </option>
                            @endforeach
                        </select>
                        <small id="nationality_error" class="text-danger" style="display: none;">Please select a
                            nationality.</small>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-4">
                        <label for="place_of_birth" class="form-label">Place of Birth <span
                                class="text-danger">*</span></label>
                        <select class="form-control" name="place_of_birth" id="place_of_birth" required
                            aria-describedby="place_of_birth_error">
                            <option value="" disabled
                                {{ old('place_of_birth', $user->place_of_birth ?? '') == '' ? 'selected' : '' }}>
                                Select Country</option>
                            @foreach ([
            'Tanzania',
            'Afghanistan',
            'Albania',
            'Algeria',
            'Andorra',
            'Angola',
            'Argentina',
            'Armenia',
            'Australia',
            'Austria',
            'Azerbaijan',
            'Bahamas',
            'Bahrain',
            'Bangladesh',
            'Barbados',
            'Belarus',
            'Belgium',
            'Belize',
            'Benin',
            'Bhutan',
            'Bolivia',
            'Bosnia and Herzegovina',
            'Botswana',
            'Brazil',
            'Brunei',
            'Bulgaria',
            'Burkina Faso',
            'Burundi',
            'Cambodia',
            'Cameroon',
            'Canada',
            'Cape Verde',
            'Central African Republic',
            'Chad',
            'Chile',
            'China',
            'Colombia',
            'Comoros',
            'Congo',
            'Costa Rica',
            'Croatia',
            'Cuba',
            'Cyprus',
            'Czech Republic',
            'Denmark',
            'Djibouti',
            'Dominica',
            'Dominican Republic',
            'DR Congo',
            'Ecuador',
            'Egypt',
            'El Salvador',
            'Equatorial Guinea',
            'Eritrea',
            'Estonia',
            'Eswatini',
            'Ethiopia',
            'Fiji',
            'Finland',
            'France',
            'Gabon',
            'Gambia',
            'Georgia',
            'Germany',
            'Ghana',
            'Greece',
            'Grenada',
            'Guatemala',
            'Guinea',
            'Guinea-Bissau',
            'Guyana',
            'Haiti',
            'Honduras',
            'Hungary',
            'Iceland',
            'India',
            'Indonesia',
            'Iran',
            'Iraq',
            'Ireland',
            'Israel',
            'Italy',
            'Ivory Coast',
            'Jamaica',
            'Japan',
            'Jordan',
            'Kazakhstan',
            'Kenya',
            'Kiribati',
            'Kuwait',
            'Kyrgyzstan',
            'Laos',
            'Latvia',
            'Lebanon',
            'Lesotho',
            'Liberia',
            'Libya',
            'Liechtenstein',
            'Lithuania',
            'Luxembourg',
            'Madagascar',
            'Malawi',
            'Malaysia',
            'Maldives',
            'Mali',
            'Malta',
            'Marshall Islands',
            'Mauritania',
            'Mauritius',
            'Mexico',
            'Micronesia',
            'Moldova',
            'Monaco',
            'Mongolia',
            'Montenegro',
            'Morocco',
            'Mozambique',
            'Myanmar',
            'Namibia',
            'Nauru',
            'Nepal',
            'Netherlands',
            'New Zealand',
            'Nicaragua',
            'Niger',
            'Nigeria',
            'North Korea',
            'North Macedonia',
            'Norway',
            'Oman',
            'Pakistan',
            'Palau',
            'Palestine',
            'Panama',
            'Papua New Guinea',
            'Paraguay',
            'Peru',
            'Philippines',
            'Poland',
            'Portugal',
            'Qatar',
            'Romania',
            'Russia',
            'Rwanda',
            'Saint Kitts and Nevis',
            'Saint Lucia',
            'Saint Vincent and the Grenadines',
            'Samoa',
            'San Marino',
            'Sao Tome and Principe',
            'Saudi Arabia',
            'Senegal',
            'Serbia',
            'Seychelles',
            'Sierra Leone',
            'Singapore',
            'Slovakia',
            'Slovenia',
            'Solomon Islands',
            'Somalia',
            'South Africa',
            'South Korea',
            'South Sudan',
            'Spain',
            'Sri Lanka',
            'Sudan',
            'Suriname',
            'Sweden',
            'Switzerland',
            'Syria',
            'Taiwan',
            'Tajikistan',
            'Thailand',
            'Timor-Leste',
            'Togo',
            'Tonga',
            'Trinidad and Tobago',
            'Tunisia',
            'Turkey',
            'Turkmenistan',
            'Tuvalu',
            'Uganda',
            'Ukraine',
            'United Arab Emirates',
            'United Kingdom',
            'United States',
            'Uruguay',
            'Uzbekistan',
            'Vanuatu',
            'Vatican City',
            'Venezuela',
            'Vietnam',
            'Yemen',
            'Zambia',
            'Zimbabwe',
        ] as $country)
                                <option value="{{ $country }}"
                                    {{ old('place_of_birth', $user->place_of_birth ?? '') == $country ? 'selected' : '' }}>
                                    {{ $country }}
                                </option>
                            @endforeach
                        </select>
                        <small id="place_of_birth_error" class="text-danger" style="display: none;">Please select a place
                            of birth.</small>
                    </div>

                    <div class="col-md-4">
                        <label for="domicile" class="form-label">Domicile<span class="text-danger">*</span> <small
                                id="domicile_help" class="form-text text-muted">
                                Please enter your permanent place of residence.
                            </small></label>
                        <input class="form-control" type="text" name="domicile" id="domicile"
                            placeholder="Enter your permanent residence"
                            value="{{ old('domicile', $user->domicile ?? '') }}" required maxlength="20"
                            aria-describedby="domicile_error">

                        <small id="domicile_error" class="text-danger" style="display: none;">Please enter a valid
                            domicile (max 20 characters).</small>
                    </div>

                    <div class="col-md-4">
                        <label for="nida" class="form-label">National Identification Number (NIDA)<span
                                class="text-danger" id="nida_required">*</span></label>
                        <input class="form-control" type="text" name="NIN" id="nida"
                            placeholder="12345678-12345-12345-12" maxlength="23" oninput="formatNida(this)"
                            value="{{ old('NIN', $user->NIN ?? '') }}" aria-describedby="nida_error" autocomplete="off">
                        <small id="nida_error" class="text-danger" style="display: none;">
                            Please enter a valid NIDA number (format: 12345678-12345-12345-12).
                        </small>
                        <small id="nida_help" class="form-text text-muted" style="display: none;">
                            For non-Tanzanian place of birth, enter your identification number.
                        </small>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-4">
                        <label for="nssf_no" class="form-label">NSSF Number<span class="text-danger">*</span></label>
                        <input class="form-control" type="text" name="nssf_no" id="nssf_no"
                            placeholder="e.g. 12345678" value="{{ old('nssf_no', $user->nssf_no ?? '') }}"
                            maxlength="20" required aria-describedby="nssf_no_error">
                        <small id="nssf_no_error" class="text-danger" style="display: none;">Please enter a valid NSSF
                            number (max 20 characters).</small>
                    </div>

                    <div class="col-md-4">
                        <label for="tin_no" class="form-label">TIN Number<span class="text-danger">*</span></label>
                        <input class="form-control" type="text" name="tin_no" id="tin_no"
                            placeholder="e.g. 123-456-789" value="{{ old('tin_no', $user->tin_no ?? '') }}"
                            maxlength="20" required aria-describedby="tin_no_error">
                        <small id="tin_no_error" class="text-danger" style="display: none;">Please enter a valid TIN
                            number (max 20 characters).</small>
                    </div>

                    <div class="col-md-4">
                        <label for="passport_no" class="form-label">Passport Number</label>
                        <input class="form-control" type="text" name="passport_no" id="passport_no"
                            placeholder="e.g. ABC-456-789" value="{{ old('passport_no', $user->passport_no ?? '') }}"
                            maxlength="20" aria-describedby="passport_no_error">
                        <small id="passport_no_error" class="text-danger" style="display: none;">Please enter a valid
                            passport number (max 20 characters).</small>
                    </div>
                </div>

                @php
                    // Check if user is existing (has starting_date in the past)
                    $isExistingUser = $user->starting_date && \Carbon\Carbon::parse($user->starting_date)->isPast();
                @endphp

                <!-- CCBRT Code Section -->
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="ccbrt_code" class="form-label">
                            <strong>CCBRT Code</strong>
                        </label>
                        <div class="input-group">
                            <span class="input-group-text"
                                style="background-color: #e9ecef; font-weight: 600; color: #495057;">CCBRT</span>
                            <input class="form-control" type="text" name="ccbrt_code_number" id="ccbrt_code_number"
                                placeholder="1234"
                                value="{{ old('ccbrt_code_number', $user->ccbrt_code ? str_replace('CCBRT', '', $user->ccbrt_code) : '') }}"
                                maxlength="4" pattern="[0-9]{1,4}" aria-describedby="ccbrt_code_error">
                            <input type="hidden" name="ccbrt_code" id="ccbrt_code"
                                value="{{ old('ccbrt_code', $user->ccbrt_code ?? '') }}">
                        </div>
                        <div class="form-check mt-2">
                            <input class="form-check-input" type="checkbox" id="skip_ccbrt_code" name="skip_ccbrt_code"
                                value="1">
                            <label class="form-check-label" for="skip_ccbrt_code">
                                <small class="text-muted">I don't have a CCBRT code</small>
                            </label>
                        </div>
                        <small id="ccbrt_code_error" class="text-danger" style="display: none;"></small>
                        <small class="form-text text-muted mt-1"></small>
                        <i class="fas fa-info-circle"></i> Enter 1-4 digits (e.g., 1234 becomes CCBRT1234).
                        </small>
                    </div>
                </div>

                <!-- Document Upload Section -->
                <div class="row mb-3">
                    <div class="col-12">
                        <hr class="my-4">
                        <h5 class="mb-3" style="color: #007A33;">
                            <i class="fas fa-file-upload"></i> Document Uploads
                        </h5>
                        <div class="alert alert-info mb-3"
                            style="background-color: #e3f2fd; border-left: 4px solid #007A33;">
                            <i class="fas fa-info-circle"></i> <strong>Note:</strong> All uploaded documents must be in PDF
                            format (max 2MB). Profile pictures must be JPG, PNG, or JPEG (max 2MB, 300px–500px width and
                            height).
                        </div>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="document_type" class="form-label">
                            <strong>Select Document Type</strong> <small class="text-muted">(Optional)</small>
                        </label>
                        <div class="border rounded p-3" style="background-color: #f8f9fa;">
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="radio" id="nida_radio" name="document_type"
                                    value="nida">
                                <label class="form-check-label" for="nida_radio">NIDA</label>
                            </div>
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="radio" id="driving_license_radio"
                                    name="document_type" value="driving_license">
                                <label class="form-check-label" for="driving_license_radio">Driving License</label>
                            </div>
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="radio" id="transport_id_radio"
                                    name="document_type" value="transport_id">
                                <label class="form-check-label" for="transport_id_radio">Transport ID</label>
                            </div>
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="radio" id="voting_id_radio" name="document_type"
                                    value="voting_id">
                                <label class="form-check-label" for="voting_id_radio">Voting ID</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" id="other_document_radio"
                                    name="document_type" value="other_document">
                                <label class="form-check-label" for="other_document_radio">Other Documents</label>
                            </div>
                        </div>
                        <small class="form-text text-muted mt-2">
                            <i class="fas fa-info-circle"></i> This field is optional, but you can add document details if
                            available.
                        </small>
                        <small id="document_type_error" class="text-danger" style="display: none;"></small>
                    </div>

                    <div id="nida_section" class="col-md-6" style="display: none;">
                        <label for="nida_doc" class="form-label"><strong>Upload NIDA</strong></label>
                        <input type="file" name="nida" id="nida_doc" class="form-control" accept=".pdf"
                            aria-describedby="nida_doc_error">
                        <small id="nida_doc_error" class="text-danger" style="display: none;">Please upload a valid PDF
                            file (max 2MB).</small>
                        @if ($user->nida)
                            <a href="{{ asset('storage/' . $user->nida) }}" target="_blank"
                                class="btn btn-sm btn-link mt-2">
                                <i class="fas fa-eye"></i> View Current NIDA
                            </a>
                        @endif
                    </div>

                    <div id="driving_license_section" class="col-md-6" style="display: none;">
                        <label for="driving_license" class="form-label"><strong>Upload Driving License</strong></label>
                        <input type="file" name="driving_license" id="driving_license" class="form-control"
                            accept=".pdf" aria-describedby="driving_license_error">
                        <small id="driving_license_error" class="text-danger" style="display: none;">Please upload a
                            valid PDF file (max 2MB).</small>
                        @if ($user->driving_license)
                            <a href="{{ asset('storage/' . $user->driving_license) }}" target="_blank"
                                class="btn btn-sm btn-link mt-2">
                                <i class="fas fa-eye"></i> View Current Driving License
                            </a>
                        @endif
                    </div>

                    <div id="transport_id_section" class="col-md-6" style="display: none;">
                        <label for="transport_id" class="form-label"><strong>Upload Transport ID</strong></label>
                        <input type="file" name="transport_id" id="transport_id" class="form-control" accept=".pdf"
                            aria-describedby="transport_id_error">
                        <small id="transport_id_error" class="text-danger" style="display: none;">Please upload a valid
                            PDF file (max 2MB).</small>
                        @if ($user->transport_id)
                            <a href="{{ asset('storage/' . $user->transport_id) }}" target="_blank"
                                class="btn btn-sm btn-link mt-2">
                                <i class="fas fa-eye"></i> View Current Transport ID
                            </a>
                        @endif
                    </div>

                    <div id="voting_id_section" class="col-md-6" style="display: none;">
                        <label for="voting_id" class="form-label"><strong>Upload Voting ID</strong></label>
                        <input type="file" name="voting_id" id="voting_id" class="form-control" accept=".pdf"
                            aria-describedby="voting_id_error">
                        <small id="voting_id_error" class="text-danger" style="display: none;">Please upload a valid PDF
                            file (max 2MB).</small>
                        @if ($user->voting_id)
                            <a href="{{ asset('storage/' . $user->voting_id) }}" target="_blank"
                                class="btn btn-sm btn-link mt-2">
                                <i class="fas fa-eye"></i> View Current Voting ID
                            </a>
                        @endif
                    </div>

                    <div id="other_document_section" class="col-md-6" style="display: none;">
                        <label for="other_document" class="form-label"><strong>Upload Other Document</strong></label>
                        <input type="file" name="other_document" id="other_document" class="form-control"
                            accept=".pdf" aria-describedby="other_document_error">
                        <small id="other_document_error" class="text-danger" style="display: none;">Please upload a valid
                            PDF file (max 2MB).</small>
                        @if ($user->other_document)
                            <a href="{{ asset('storage/' . $user->other_document) }}" target="_blank"
                                class="btn btn-sm btn-link mt-2">
                                <i class="fas fa-eye"></i> View Current Document
                            </a>
                        @endif
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="profile_picture" class="form-label">
                            <strong>Upload Profile Picture</strong>
                        </label>
                        <input type="file" name="profile_picture" id="profile_picture" class="form-control"
                            accept="image/jpeg, image/png" aria-describedby="profile_picture_error">
                        <small id="profile_picture_error" class="text-danger" style="display: none;">
                            Please upload a JPG or PNG image (max 4MB).
                        </small>
                        @if ($user->profile_picture)
                            <div class="mt-2">
                                <a href="{{ asset('storage/' . $user->profile_picture) }}" target="_blank"
                                    class="btn btn-sm btn-link">
                                    <i class="fas fa-eye"></i> View Current Profile Picture
                                </a>
                            </div>
                        @endif
                    </div>

                    <script>
                        document.addEventListener('DOMContentLoaded', function() {
                            const fileInput = document.getElementById('profile_picture');
                            const errorMsg = document.getElementById('profile_picture_error');

                            fileInput.addEventListener('change', function() {
                                const file = fileInput.files[0];

                                if (!file) {
                                    errorMsg.style.display = 'none';
                                    fileInput.setCustomValidity('');
                                    return;
                                }

                                const validTypes = ['image/jpeg', 'image/png'];
                                const maxSize = 4 * 1024 * 1024; // 4 MB

                                if (!validTypes.includes(file.type) || file.size > maxSize) {
                                    errorMsg.style.display = 'block';
                                    fileInput.setCustomValidity('Invalid file type or size.');
                                } else {
                                    errorMsg.style.display = 'none';
                                    fileInput.setCustomValidity('');
                                }
                            });
                        });
                    </script>


                    <div class="col-md-4" id="marriage_certificate" style="display: none">
                        <label for="marriage_certificate" class="form-label">Upload Marriage Certificate
                            (Optional)</label>
                        <input type="file" name="marriage_certificate" id="marriage_certificate" class="form-control"
                            accept=".pdf" aria-describedby="marriage_certificate_error">
                        <small id="marriage_certificate_error" class="text-danger" style="display: none;">Please upload a
                            valid PDF file (max 2MB).</small>
                        @if ($user->marriage_certificate)
                            <a href="{{ asset('storage/' . $user->marriage_certificate) }}" target="_blank">Marriage
                                Certificate</a>
                        @endif
                    </div>

                    <div class="col-md-4" id="divorce_certificate" style="display: none">
                        <label for="divorce_certificate" class="form-label">Upload Divorce Certificate
                            (Optional)</label>
                        <input type="file" name="divorce_certificate" id="divorce_certificate" class="form-control"
                            accept=".pdf" aria-describedby="divorce_certificate_error">
                        <small id="divorce_certificate_error" class="text-danger" style="display: none;">Please upload a
                            valid PDF file (max 2MB).</small>
                        @if ($user->divorce_certificate)
                            <a href="{{ asset('storage/' . $user->divorce_certificate) }}" target="_blank">Divorce
                                Certificate</a>
                        @endif
                    </div>
                </div>

                <div class="d-flex justify-content-center">
                    <button type="submit" class="btn btn-primary mt-3 w-50">Save and Continue</button>
                </div>
            </form>
        </div>
    </div>
@endsection

<script>
    // Move functions outside DOMContentLoaded to make them globally accessible
    function formatNida(input) {
        const placeOfBirthSelect = document.getElementById('place_of_birth');
        if (!placeOfBirthSelect) {
            validateNida();
            return;
        }

        const placeOfBirth = placeOfBirthSelect.value;

        // Only format if place of birth is Tanzania
        if (placeOfBirth === 'Tanzania') {
            let value = input.value.replace(/[^0-9]/g, ''); // Allow only digits
            let formattedValue = '';
            if (value.length > 0) {
                formattedValue = value.slice(0, 8);
                if (value.length > 8) formattedValue += '-' + value.slice(8, 13);
                if (value.length > 13) formattedValue += '-' + value.slice(13, 18);
                if (value.length > 18) formattedValue += '-' + value.slice(18, 20);
            }
            input.value = formattedValue;
        }
        // If not Tanzania, allow any input (no formatting, preserve all characters)
        validateNida();
    }

    function validateNida() {
        const nidaInput = document.getElementById('nida');
        const nidaError = document.getElementById('nida_error');
        const placeOfBirth = document.getElementById('place_of_birth').value;

        if (!nidaInput.required) {
            nidaError.style.display = 'none';
            nidaInput.style.borderColor = '#ced4da';
            return true;
        }

        // Only validate format if place of birth is Tanzania
        if (placeOfBirth === 'Tanzania') {
            const nidaPattern = /^\d{8}-\d{5}-\d{5}-\d{2}$/;
            if (nidaInput.value && nidaPattern.test(nidaInput.value)) {
                nidaError.style.display = 'none';
                nidaInput.style.borderColor = '#28a745'; // Green for valid
                return true;
            } else if (nidaInput.value) {
                nidaError.style.display = 'block';
                nidaInput.style.borderColor = '#dc3545'; // Red for invalid
                return false;
            }
        } else {
            // For non-Tanzania, just check if it's not empty (if required)
            if (nidaInput.value || !nidaInput.required) {
                nidaError.style.display = 'none';
                nidaInput.style.borderColor = '#ced4da';
                return true;
            }
        }

        nidaError.style.display = 'none';
        nidaInput.style.borderColor = '#ced4da';
        return true;
    }

    document.addEventListener('DOMContentLoaded', function() {
        // Region and District handling
        const regionSelect = document.getElementById('region');
        const districtSelect = document.getElementById('district');
        const nationalitySelect = document.getElementById('nationality');
        const nidaInput = document.getElementById('nida');
        const nidaRequired = document.getElementById('nida_required');

        const districts = {
            'Mwanza': ['Ilemela', 'Nyamagana', 'Magu', 'Ukerewe', 'Sengerema', 'Misungwi', 'Kwimba'],
            'Dar es Salaam': ['Ilala', 'Kinondoni', 'Temeke', 'Kigamboni', 'Ubungo'],
            'Arusha': ['Arusha City', 'Arusha Rural', 'Meru', 'Karatu', 'Longido', 'Monduli', 'Ngorongoro'],
            'Dodoma': ['Bahi', 'Chamwino', 'Chemba', 'Dodoma City', 'Kondoa', 'Kongwa', 'Mpwapwa'],
            'Geita': ['Bukombe', 'Chato', 'Geita', 'Mbogwe', 'Nyang\'hwale'],
            'Iringa': ['Iringa Urban', 'Iringa Rural', 'Kilolo', 'Mufindi'],
            'Kagera': ['Biharamulo', 'Bukoba', 'Karagwe', 'Kyerwa', 'Missenyi', 'Muleba', 'Ngara'],
            'Katavi': ['Mlele', 'Mpanda', 'Nsimbo'],
            'Kigoma': ['Buhigwe', 'Kakonko', 'Kasulu', 'Kigoma', 'Uvinza'],
            'Kilimanjaro': ['Hai', 'Moshi Urban', 'Moshi Rural', 'Mwanga', 'Rombo', 'Same', 'Siha'],
            'Lindi': ['Kilwa', 'Lindi Urban', 'Lindi Rural', 'Liwale', 'Nachingwea', 'Ruangwa'],
            'Manyara': ['Babati', 'Hanang', 'Kiteto', 'Mbulu', 'Simanjiro'],
            'Mara': ['Bunda', 'Butiama', 'Musoma', 'Rorya', 'Serengeti', 'Tarime'],
            'Mbeya': ['Busokelo', 'Chunya', 'Kyela', 'Mbarali', 'Mbeya City', 'Mbeya Rural', 'Rungwe'],
            'Morogoro': ['Gairo', 'Kilombero', 'Kilosa', 'Morogoro Urban', 'Morogoro Rural', 'Mvomero',
                'Ulanga'
            ],
            'Mtwara': ['Masasi', 'Mtwara Urban', 'Mtwara Rural', 'Nanyumbu', 'Newala', 'Tandahimba'],
            'Njombe': ['Ludewa', 'Makambako', 'Makete', 'Njombe Urban', 'Njombe Rural', 'Wanging\'ombe'],
            'Pemba North': ['Micheweni', 'Wete'],
            'Pemba South': ['Chake Chake', 'Mkoani'],
            'Pwani': ['Bagamoyo', 'Kibaha', 'Kisarawe', 'Mafia', 'Mkuranga', 'Rufiji'],
            'Rukwa': ['Kalambo', 'Nkasi', 'Sumbawanga'],
            'Ruvuma': ['Mbinga', 'Songea Urban', 'Songea Rural', 'Tunduru', 'Namtumbo'],
            'Shinyanga': ['Kahama', 'Kishapu', 'Shinyanga Urban', 'Shinyanga Rural'],
            'Simiyu': ['Bariadi', 'Busega', 'Itilima', 'Maswa', 'Meatu'],
            'Singida': ['Ikungi', 'Iramba', 'Manyoni', 'Mkalama', 'Singida Urban', 'Singida Rural'],
            'Songwe': ['Ileje', 'Mbozi', 'Momba', 'Songwe'],
            'Tabora': ['Igunga', 'Kaliua', 'Nzega', 'Sikonge', 'Tabora Urban', 'Urambo'],
            'Tanga': ['Handeni', 'Kilindi', 'Korogwe', 'Lushoto', 'Muheza', 'Pangani', 'Tanga City'],
            'Zanzibar Central/South': ['Central', 'South'],
            'Zanzibar North': ['North A', 'North B'],
            'Zanzibar Urban/West': ['Urban', 'West']
        };

        function updateDistricts() {
            const selectedRegion = regionSelect.value;
            districtSelect.innerHTML = '<option value="" disabled selected>Select District</option>';

            if (districts[selectedRegion]) {
                districts[selectedRegion].forEach(district => {
                    const option = document.createElement('option');
                    option.value = district;
                    option.textContent = district;
                    districtSelect.appendChild(option);
                });
            }

            // Restore previously selected district if available
            const oldDistrict = "{{ old('district', $user->district ?? '') }}";
            if (oldDistrict && districts[selectedRegion] && districts[selectedRegion].includes(oldDistrict)) {
                districtSelect.value = oldDistrict;
            }
        }

        function toggleNidaField() {
            const isTanzanian = nationalitySelect.value === 'Tanzania';
            const placeOfBirth = document.getElementById('place_of_birth').value;
            const isTanzaniaPlaceOfBirth = placeOfBirth === 'Tanzania';
            const nidaError = document.getElementById('nida_error');
            const nidaHelp = document.getElementById('nida_help');

            // NIDA is required if nationality is Tanzania
            nidaInput.disabled = !isTanzanian;
            nidaInput.required = isTanzanian;
            nidaRequired.style.display = isTanzanian ? 'inline' : 'none';

            // Update placeholder, maxlength, and help text based on place of birth
            if (isTanzaniaPlaceOfBirth) {
                nidaInput.placeholder = '12345678-12345-12345-12';
                nidaInput.maxLength = 23;
                nidaError.textContent = 'Please enter a valid NIDA number (format: 12345678-12345-12345-12).';
                nidaHelp.style.display = 'none';
            } else {
                nidaInput.placeholder = 'Enter identification number';
                nidaInput.maxLength = 50;
                nidaError.textContent = 'Please enter your identification number.';
                if (isTanzanian) {
                    nidaHelp.style.display = 'block';
                } else {
                    nidaHelp.style.display = 'none';
                }
            }

            if (!isTanzanian) {
                nidaInput.value = '';
                nidaError.style.display = 'none';
            }
            validateNida();
        }

        // Also update NIDA field when place of birth changes
        const placeOfBirthSelect = document.getElementById('place_of_birth');
        if (placeOfBirthSelect) {
            placeOfBirthSelect.addEventListener('change', function() {
                toggleNidaField();
            });
        }

        function validateForm() {
            let isValid = true;

            // Validate required select fields
            ['marital_status', 'gender', 'religion', 'region', 'district', 'place_of_birth', 'nationality']
            .forEach(id => {
                const select = document.getElementById(id);
                const error = document.getElementById(`${id}_error`);
                if (!select.value || select.value === '') {
                    error.style.display = 'block';
                    select.style.borderColor = '#dc3545';
                    isValid = false;
                } else {
                    error.style.display = 'none';
                    select.style.borderColor = '#ced4da';
                }
            });

            // Validate religion_other if Other is selected
            const religionSelect = document.getElementById('religion-select');
            const religionOtherInput = document.getElementById('religion-other');
            const religionOtherError = document.getElementById('religion_other_error');
            if (religionSelect.value === 'Other' && !religionOtherInput.value.trim()) {
                religionOtherError.style.display = 'block';
                religionOtherInput.style.borderColor = '#dc3545';
                isValid = false;
            } else {
                religionOtherError.style.display = 'none';
                religionOtherInput.style.borderColor = '#ced4da';
            }

            // Validate text fields
            ['street', 'popular_landmark', 'domicile', 'nssf_no', 'tin_no'].forEach(id => {
                const input = document.getElementById(id);
                const error = document.getElementById(`${id}_error`);
                if (input.required && !input.value.trim()) {
                    error.style.display = 'block';
                    input.style.borderColor = '#dc3545';
                    isValid = false;
                } else {
                    error.style.display = 'none';
                    input.style.borderColor = '#ced4da';
                }
            });

            // Validate optional text fields
            ['house_no', 'professional_reg_number', 'passport_no'].forEach(id => {
                const input = document.getElementById(id);
                const error = document.getElementById(`${id}_error`);
                if (input.value && input.value.length > 20) {
                    error.style.display = 'block';
                    input.style.borderColor = '#dc3545';
                    isValid = false;
                } else {
                    error.style.display = 'none';
                    input.style.borderColor = '#ced4da';
                }
            });

            // Validate NIDA
            if (!validateNida()) {
                isValid = false;
            }

            // Validate file inputs
            ['employee_cv', 'nida_doc', 'driving_license', 'transport_id', 'voting_id', 'other_document',
                'profile_picture', 'marriage_certificate', 'divorce_certificate'
            ].forEach(id => {
                const input = document.getElementById(id);
                if (input && input.files.length > 0) {
                    const file = input.files[0];
                    const error = document.getElementById(`${id}_error`);
                    if (file.size > 2 * 1024 * 1024) { // 2MB limit
                        error.style.display = 'block';
                        input.style.borderColor = '#dc3545';
                        isValid = false;
                    } else {
                        error.style.display = 'none';
                        input.style.borderColor = '#ced4da';
                    }
                }
            });

            return isValid;
        }

        // Event listeners
        regionSelect.addEventListener('change', updateDistricts);
        nationalitySelect.addEventListener('change', toggleNidaField);
        nidaInput.addEventListener('input', function() {
            formatNida(this);
        });

        // Only restrict to digits if place of birth is Tanzania
        nidaInput.addEventListener('keypress', function(event) {
            const placeOfBirth = document.getElementById('place_of_birth').value;
            if (placeOfBirth === 'Tanzania' && !/[0-9]/.test(event.key)) {
                event.preventDefault();
            }
            // For non-Tanzania, allow any character
        });

        const statusSelect = document.getElementById('marital_status');
        const marriageDiv = document.getElementById('marriage_certificate');
        const divorceDiv = document.getElementById('divorce_certificate');

        function toggleCertificateFields() {
            const selectedValue = statusSelect.value;
            marriageDiv.style.display = selectedValue === 'married' ? 'block' : 'none';
            divorceDiv.style.display = selectedValue === 'divorced' ? 'block' : 'none';
        }

        statusSelect.addEventListener('change', toggleCertificateFields);

        const radioButtons = document.querySelectorAll('input[name="document_type"]');
        radioButtons.forEach(radio => {
            radio.addEventListener('change', toggleDocumentSection);
        });

        function toggleDocumentSection() {
            const sections = ['nida_section', 'driving_license_section', 'transport_id_section',
                'voting_id_section', 'other_document_section'
            ];
            sections.forEach(section => {
                document.getElementById(section).style.display = 'none';
            });

            const selectedRadio = document.querySelector('input[name="document_type"]:checked');
            if (selectedRadio) {
                document.getElementById(selectedRadio.value + '_section').style.display = 'block';
            }
        }

        document.getElementById('religion-select').addEventListener('change', function() {
            const religionOtherInput = document.getElementById('religion-other');
            religionOtherInput.style.display = this.value === 'Other' ? 'block' : 'none';
        });

        // CCBRT Code handling
        const ccbrtCodeNumber = document.getElementById('ccbrt_code_number');
        const ccbrtCode = document.getElementById('ccbrt_code');
        const skipCcbrtCode = document.getElementById('skip_ccbrt_code');
        const ccbrtCodeError = document.getElementById('ccbrt_code_error');
        const isExistingUser = @json($isExistingUser ?? false);

        function formatCcbrtCode() {
            if (!ccbrtCodeNumber) return;

            // Only allow digits
            let value = ccbrtCodeNumber.value.replace(/[^0-9]/g, '');

            // Limit to 4 digits
            if (value.length > 4) {
                value = value.substring(0, 4);
            }

            ccbrtCodeNumber.value = value;

            // Update hidden field with full code
            if (value) {
                // Pad with leading zeros to make it 4 digits
                const paddedValue = value.padStart(4, '0');
                ccbrtCode.value = 'CCBRT' + paddedValue;
            } else {
                ccbrtCode.value = '';
            }

            // Clear error
            ccbrtCodeError.style.display = 'none';
            ccbrtCodeNumber.setCustomValidity('');
        }

        if (ccbrtCodeNumber) {
            ccbrtCodeNumber.addEventListener('input', formatCcbrtCode);
            ccbrtCodeNumber.addEventListener('paste', function(e) {
                e.preventDefault();
                const pastedText = (e.clipboardData || window.clipboardData).getData('text');
                const numbers = pastedText.replace(/[^0-9]/g, '').substring(0, 4);
                ccbrtCodeNumber.value = numbers;
                formatCcbrtCode();
            });
        }

        // Handle skip checkbox
        if (skipCcbrtCode) {
            skipCcbrtCode.addEventListener('change', function() {
                if (this.checked) {
                    ccbrtCodeNumber.disabled = true;
                    ccbrtCodeNumber.removeAttribute('required');
                    ccbrtCodeNumber.value = '';
                    ccbrtCode.value = '';
                    ccbrtCodeNumber.style.backgroundColor = '#e9ecef';
                } else {
                    ccbrtCodeNumber.disabled = false;
                    ccbrtCodeNumber.removeAttribute('required');
                    ccbrtCodeNumber.style.backgroundColor = '';
                }
            });
        }

        // Validate CCBRT code uniqueness on blur
        if (ccbrtCodeNumber) {
            ccbrtCodeNumber.addEventListener('blur', function() {
                const codeValue = ccbrtCode.value;
                if (codeValue && codeValue.length > 5) {
                    // Check uniqueness via AJAX
                    fetch('{{ route('profile.checkCcbrtCode') }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                'Accept': 'application/json'
                            },
                            body: JSON.stringify({
                                ccbrt_code: codeValue,
                                user_id: @json(Auth::id() ?? 0)
                            })
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (!data.available) {
                                ccbrtCodeError.textContent =
                                    'This CCBRT code is already in use. Please enter a different code.';
                                ccbrtCodeError.style.display = 'block';
                                ccbrtCodeNumber.setCustomValidity('CCBRT code must be unique.');
                                ccbrtCodeNumber.style.borderColor = '#dc3545';
                            } else {
                                ccbrtCodeError.style.display = 'none';
                                ccbrtCodeNumber.setCustomValidity('');
                                ccbrtCodeNumber.style.borderColor = '#ced4da';
                            }
                        })
                        .catch(error => {
                            console.error('Error checking CCBRT code:', error);
                        });
                }
            });
        }

        document.querySelector('form').addEventListener('submit', function(event) {
            // Format CCBRT code before submit
            if (ccbrtCodeNumber && (!skipCcbrtCode || !skipCcbrtCode.checked)) {
                formatCcbrtCode();
            }

            if (!validateForm()) {
                event.preventDefault();
                alert('Please correct the errors in the form.');
            }
        });

        // Initialize form state
        updateDistricts();
        toggleNidaField();
        toggleCertificateFields();
        toggleDocumentSection();
        validateNida();
    });

    // Backend validation suggestion for Laravel (add to your validation rules):
    // 'NIN' => ['nullable', 'regex:/^\d{8}-\d{5}-\d{5}-\d{2}$/']
</script>
