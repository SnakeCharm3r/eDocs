@extends('layouts.template')

@section('breadcrumb')
    @include('sweetalert::alert')
    @include('includes.loader')
@endsection

@section('content')
    @php
        $isITDepartment =
            auth()->user()->department && auth()->user()->department->dept_name === 'IT & Business Applications';
        // Auto-populate dates from user table
        $startDate = $user->starting_date ? \Carbon\Carbon::parse($user->starting_date)->format('Y-m-d') : '';
        $endDate = $user->ending_date ? \Carbon\Carbon::parse($user->ending_date)->format('Y-m-d') : '';
    @endphp

    <div class="page-wrapper">
        <div class="content container-fluid">
            <br>
            <!-- Page Header -->
            <div class="page-header">
                <div class="row">
                    <div class="col-sm-12">
                        <div class="page-sub-header">
                            <h3 class="page-title">IT Access Form</h3>
                        </div>
                    </div>
                </div>
            </div>
            @if ($errors->any())
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
                    <strong class="font-bold">Oops!</strong> There were some problems with your submission:
                    <ul class="mt-2 list-disc list-inside text-sm">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <!-- Form Card -->
            <div class="row">
                <div class="col-md-12">
                    <div class="card shadow-sm">
                        <div class="card-body">
                            <form method="POST" action="{{ route('form.store') }}" id="ictAccessForm">
                                @csrf
                                <input type="hidden" name="userId" value="{{ $user->id }}">
                                <input type="hidden" name="is_it_department" id="is_it_department"
                                    value="{{ $isITDepartment ? '1' : '0' }}">

                                <!-- Primary Information Section -->
                                <div class="row mb-4">
                                    <div class="col-md-12">
                                        <h5 class="section-title mb-3 d-flex align-items-center">
                                            <i class="fas fa-user-circle me-2"></i>Primary Information

                                        </h5>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="user_type" class="d-flex align-items-center">
                                                User Type <span class="text-danger">*</span>
                                                <i class="fas fa-question-circle text-primary ms-2" data-bs-toggle="tooltip"
                                                    data-bs-placement="top" data-bs-html="true"
                                                    title="Choose 'New User' for first-time employees, or 'Existing User' for people who already have an account"></i>
                                            </label>
                                            <select
                                                class="form-control {{ $errors->has('user_type') ? 'border-red-500' : '' }}"
                                                id="user_type" name="user_type" required>
                                                <option value="" disabled selected>--- Select User Type ---</option>
                                                <option value="New" {{ old('user_type') == 'New' ? 'selected' : '' }}>New
                                                    User</option>
                                                <option value="Existing"
                                                    {{ old('user_type') == 'Existing' ? 'selected' : '' }}>Existing User
                                                </option>
                                            </select>
                                            @if ($errors->has('user_type'))
                                                <span class="text-red-500 text-sm">{{ $errors->first('user_type') }}</span>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="access_required" class="d-flex align-items-center">
                                                Access Action <span class="text-danger">*</span>
                                                <i class="fas fa-question-circle text-primary ms-2" data-bs-toggle="tooltip"
                                                    data-bs-placement="top" data-bs-html="true"
                                                    title="Choose 'Grant Access' to add permissions, or 'Remove Access' to take them away."></i>
                                            </label>
                                            <select
                                                class="form-control {{ $errors->has('access_required') ? 'border-red-500' : '' }}"
                                                id="access_required" name="access_required" required>
                                                <option value="" disabled selected>--- Select Action ---</option>
                                                <option value="Grant"
                                                    {{ old('access_required') == 'Grant' ? 'selected' : '' }}>Grant Access
                                                </option>
                                                <option value="Revoke"
                                                    {{ old('access_required') == 'Revoke' ? 'selected' : '' }}>Remove
                                                    Access</option>
                                            </select>
                                            @if ($errors->has('access_required'))
                                                <span
                                                    class="text-red-500 text-sm">{{ $errors->first('access_required') }}</span>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="action_required" class="d-flex align-items-center">
                                                Action Type <span class="text-danger">*</span>
                                                <i class="fas fa-question-circle text-primary ms-2" data-bs-toggle="tooltip"
                                                    data-bs-placement="top" data-bs-html="true"
                                                    title="Choose 'Create' for new access credentials, or 'Update' to modify existing ones."></i>
                                            </label>
                                            <select
                                                class="form-control {{ $errors->has('action_required') ? 'border-red-500' : '' }}"
                                                id="action_required" name="action_required" required>
                                                <option value="" disabled selected>--- Select Action Type ---</option>
                                                <option value="Create"
                                                    {{ old('action_required') == 'Create' ? 'selected' : '' }}>Create
                                                </option>
                                                <option value="Update"
                                                    {{ old('action_required') == 'Update' ? 'selected' : '' }}>Update
                                                </option>
                                            </select>
                                            @if ($errors->has('action_required'))
                                                <span
                                                    class="text-red-500 text-sm">{{ $errors->first('action_required') }}</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>

                                <!-- Access Dates Section -->
                                <div class="row mb-4">
                                    <div class="col-md-12">
                                        <h5 class="section-title mb-3 d-flex align-items-center">
                                            <i class="fas fa-calendar-alt me-2"></i>Access Period
                                            <i class="fas fa-question-circle text-primary ms-2" data-bs-toggle="tooltip"
                                                data-bs-placement="top" data-bs-html="true"
                                                title="Access Period defines when the requested access should start and end."></i>
                                        </h5>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="start_date" class="d-flex align-items-center">
                                                Start Date <span class="text-danger">*</span>

                                            </label>
                                            <input type="date" name="start_date" id="start_date"
                                                class="form-control {{ $errors->has('start_date') ? 'border-red-500' : '' }}"
                                                value="{{ old('start_date', $startDate) }}" required>
                                            @if ($errors->has('start_date'))
                                                <span
                                                    class="text-red-500 text-sm">{{ $errors->first('start_date') }}</span>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="end_date" class="d-flex align-items-center">
                                                End Date <span class="text-danger">*</span>

                                            </label>
                                            <input type="date" name="end_date" id="end_date"
                                                class="form-control {{ $errors->has('end_date') ? 'border-red-500' : '' }}"
                                                value="{{ old('end_date', $endDate) }}" required>
                                            @if ($errors->has('end_date'))
                                                <span class="text-red-500 text-sm">{{ $errors->first('end_date') }}</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>

                                <!-- Access Types Section -->
                                <div class="row mb-4">
                                    <div class="col-md-12">
                                        <h5 class="section-title mb-3 d-flex align-items-center">
                                            <i class="fas fa-key me-2" style="color: #007A33;"></i>Access Types
                                            <i class="fas fa-question-circle text-primary ms-2" data-bs-toggle="tooltip"
                                                data-bs-placement="top" data-bs-html="true"
                                                title="Select the types of access required by ticking the checkboxes below."></i>
                                        </h5>
                                    </div>
                                </div>

                                <!-- Domain Access Section -->
                                <div class="card mb-3" style="border-left: 4px solid #007A33;">
                                    <div class="card-header bg-light">
                                        <div class="d-flex align-items-center justify-content-between">
                                            <div class="d-flex align-items-center">
                                                <h5 class="fw-normal mb-0 me-3" style="color: #333;">
                                                    <i class="fas fa-network-wired me-2"
                                                        style="color: #007A33;"></i>Domain Access
                                                </h5>
                                                <i class="fas fa-question-circle text-primary me-2"
                                                    data-bs-toggle="tooltip" data-bs-placement="top" data-bs-html="true"
                                                    title="lets users log into company computers and access network resources like shared drives."></i>
                                            </div>
                                            <div class="radio-toggle-group">
                                                <input type="radio" name="enable_domain_radio" id="enable_domain_yes"
                                                    value="1" {{ old('enable_domain') ? 'checked' : '' }}
                                                    data-target="enable_domain" data-section="domain_section">
                                                <label for="enable_domain_yes"
                                                    class="radio-label radio-label-yes">Yes</label>
                                                <input type="radio" name="enable_domain_radio" id="enable_domain_no"
                                                    value="0" {{ !old('enable_domain') ? 'checked' : '' }}
                                                    data-target="enable_domain" data-section="domain_section">
                                                <label for="enable_domain_no"
                                                    class="radio-label radio-label-no">No</label>
                                            </div>
                                            <input type="hidden" name="enable_domain" id="enable_domain"
                                                value="{{ old('enable_domain') ? '1' : '0' }}">
                                        </div>
                                    </div>
                                    <div class="card-body" id="domain_section">
                                        <div class="form-group">
                                            <label for="active_drt">Domain Access Level <span
                                                    class="text-danger">*</span></label>
                                            <select
                                                class="form-control {{ $errors->has('active_drt') ? 'border-red-500' : '' }}"
                                                id="active_drt" name="active_drt" required>
                                                <option value="" disabled>--- Select Access Level ---
                                                </option>
                                                @php
                                                    $defaultUserPrivilege = $privileges->firstWhere('prv_name', 'User');
                                                    $defaultUserPrivilegeId = $defaultUserPrivilege && $defaultUserPrivilege->prv_status == 'active' && $defaultUserPrivilege->can_use_for_domain_access ? $defaultUserPrivilege->id : null;
                                                @endphp
                                                @foreach ($privileges as $privilege)
                                                    @if ($privilege->prv_status == 'active' && $privilege->can_use_for_domain_access)
                                                        <option value="{{ $privilege->id }}"
                                                            {{ old('active_drt', $defaultUserPrivilegeId) == $privilege->id ? 'selected' : '' }}
                                                            data-privilege-name="{{ $privilege->prv_name }}">
                                                            {{ $privilege->prv_name }}
                                                        </option>
                                                    @endif
                                                @endforeach
                                            </select>
                                            @if ($errors->has('active_drt'))
                                                <span
                                                    class="text-red-500 text-sm">{{ $errors->first('active_drt') }}</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>

                                <!-- Email Access Section -->
                                <div class="card mb-3" style="border-left: 4px solid #007A33;">
                                    <div class="card-header bg-light">
                                        <div class="d-flex align-items-center justify-content-between">
                                            <div class="d-flex align-items-center">
                                                <h5 class="fw-normal mb-0 me-3" style="color: #333;">
                                                    <i class="fas fa-envelope me-2" style="color: #007A33;"></i>Email
                                                    Access (CCBRT Email Account)
                                                </h5>
                                                <i class="fas fa-question-circle text-primary me-2"
                                                    data-bs-toggle="tooltip" data-bs-placement="top" data-bs-html="true"
                                                    title="Email Access provides a CCBRT email account for official communication."></i>
                                            </div>
                                            <div class="radio-toggle-group">
                                                <input type="radio" name="enable_email_radio" id="enable_email_yes"
                                                    value="1" {{ old('enable_email') ? 'checked' : '' }}
                                                    data-target="enable_email" data-section="email_section">
                                                <label for="enable_email_yes"
                                                    class="radio-label radio-label-yes">Yes</label>
                                                <input type="radio" name="enable_email_radio" id="enable_email_no"
                                                    value="0" {{ !old('enable_email') ? 'checked' : '' }}
                                                    data-target="enable_email" data-section="email_section">
                                                <label for="enable_email_no" class="radio-label radio-label-no">No</label>
                                            </div>
                                            <input type="hidden" name="enable_email" id="enable_email"
                                                value="{{ old('enable_email') ? '1' : '0' }}">
                                        </div>
                                    </div>
                                    <div class="card-body" id="email_section">
                                        <div class="form-group">
                                            <label for="email">Email Access Level <span
                                                    class="text-danger">*</span></label>
                                            <select
                                                class="form-control {{ $errors->has('email') ? 'border-red-500' : '' }}"
                                                id="email" name="email">
                                                <option value="" disabled>--- Select Access Level ---
                                                </option>
                                                @php
                                                    $defaultUserPrivilegeEmail = $privileges->firstWhere('prv_name', 'User');
                                                    $defaultUserPrivilegeEmailId = $defaultUserPrivilegeEmail && $defaultUserPrivilegeEmail->prv_status == 'active' && $defaultUserPrivilegeEmail->can_use_for_email_access ? $defaultUserPrivilegeEmail->id : null;
                                                @endphp
                                                @foreach ($privileges as $privilege)
                                                    @if ($privilege->prv_status == 'active' && $privilege->can_use_for_email_access)
                                                        <option value="{{ $privilege->id }}"
                                                            {{ old('email', $defaultUserPrivilegeEmailId) == $privilege->id ? 'selected' : '' }}>
                                                            {{ $privilege->prv_name }}
                                                        </option>
                                                    @endif
                                                @endforeach
                                            </select>
                                            @if ($errors->has('email'))
                                                <span class="text-red-500 text-sm">{{ $errors->first('email') }}</span>
                                            @endif
                                            <small class="form-text text-muted">Access level for CCBRT email
                                                account.</small>
                                        </div>
                                        <div class="form-group mt-3">
                                            <label for="requested_email_address">Requested Email Address</label>
                                            <input type="email" id="requested_email_address"
                                                name="requested_email_address"
                                                class="form-control {{ $errors->has('requested_email_address') ? 'border-red-500' : '' }}"
                                                value="{{ old('requested_email_address') }}"
                                                placeholder="e.g. firstname.lastname@ccbrt.org">
                                            @if ($errors->has('requested_email_address'))
                                                <span
                                                    class="text-red-500 text-sm">{{ $errors->first('requested_email_address') }}</span>
                                            @endif
                                            <small class="form-text text-muted">Optional: specify the exact email address to create.</small>
                                        </div>
                                    </div>
                                </div>

                                <!-- Network Folder Access Section -->
                                <div class="card mb-3" style="border-left: 4px solid #007A33;">
                                    <div class="card-header bg-light">
                                        <div class="d-flex align-items-center justify-content-between">
                                            <div class="d-flex align-items-center">
                                                <h5 class="fw-normal mb-0 me-3" style="color: #333;">
                                                    <i class="fas fa-folder-open me-2" style="color: #007A33;"></i>Network
                                                    Folder Access
                                                </h5>
                                                <i class="fas fa-question-circle text-primary me-2"
                                                    data-bs-toggle="tooltip" data-bs-placement="top" data-bs-html="true"
                                                    title="Provides access to shared network drives and folders on the company network."></i>
                                            </div>
                                            <div class="radio-toggle-group">
                                                <input type="radio" name="enable_network_folder_radio"
                                                    id="enable_network_folder_yes" value="1"
                                                    {{ old('enable_network_folder') ? 'checked' : '' }}
                                                    data-target="enable_network_folder"
                                                    data-section="network_folder_section">
                                                <label for="enable_network_folder_yes"
                                                    class="radio-label radio-label-yes">Yes</label>
                                                <input type="radio" name="enable_network_folder_radio"
                                                    id="enable_network_folder_no" value="0"
                                                    {{ !old('enable_network_folder') ? 'checked' : '' }}
                                                    data-target="enable_network_folder"
                                                    data-section="network_folder_section">
                                                <label for="enable_network_folder_no"
                                                    class="radio-label radio-label-no">No</label>
                                            </div>
                                            <input type="hidden" name="enable_network_folder" id="enable_network_folder"
                                                value="{{ old('enable_network_folder') ? '1' : '0' }}">
                                        </div>
                                    </div>
                                    <div class="card-body" id="network_folder_section">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="network_folder">Network Folder <span
                                                            class="text-danger">*</span></label>
                                                    <select
                                                        class="form-control {{ $errors->has('network_folder') ? 'border-red-500' : '' }}"
                                                        id="network_folder" name="network_folder">
                                                        <option value="" disabled selected>--- Select Network Folder
                                                            ---</option>
                                                        @foreach ($networkFolders as $folder)
                                                            <option value="{{ $folder->folder_name }}"
                                                                {{ old('network_folder') == $folder->folder_name ? 'selected' : '' }}>
                                                                {{ $folder->folder_name }}
                                                            </option>
                                                        @endforeach
                                                        <option value="Other" {{ old('network_folder') == 'Other' ? 'selected' : '' }}>
                                                            Other
                                                        </option>
                                                    </select>
                                                    @if ($errors->has('network_folder'))
                                                        <span
                                                            class="text-red-500 text-sm">{{ $errors->first('network_folder') }}</span>
                                                    @endif
                                                </div>

                                                <div class="form-group mt-3" id="other_folder_container" style="display: none;">
                                                    <label for="other_network_folder">Other folder</label>
                                                    <input type="text" id="other_network_folder" name="other_network_folder"
                                                        class="form-control {{ $errors->has('other_network_folder') ? 'border-red-500' : '' }}"
                                                        value="{{ old('other_network_folder') }}" placeholder="Enter folder">
                                                    @if ($errors->has('other_network_folder'))
                                                        <span class="text-red-500 text-sm">{{ $errors->first('other_network_folder') }}</span>
                                                    @endif
                                                    <small class="form-text text-muted">Optional: specify the exact shared folder you want access to.</small>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label>Network Folder Access Level <span
                                                            class="text-danger">*</span></label>
                                                    <div
                                                        class="d-flex flex-wrap {{ $errors->has('folder_privilege') ? 'border border-red-500 p-2 rounded' : '' }}">
                                                        @foreach ($privileges as $privilege)
                                                            @if (in_array($privilege->prv_name, ['Read', 'Write', 'Full Access']))
                                                                <div class="form-check form-check-inline me-3">
                                                                    <input class="form-check-input folder-privilege-radio"
                                                                        type="radio" name="folder_privilege"
                                                                        id="privilege_{{ $privilege->id }}"
                                                                        value="{{ $privilege->id }}"
                                                                        {{ old('folder_privilege') == $privilege->id ? 'checked' : '' }}
                                                                        {{ $privilege->prv_name === 'Read' && !old('folder_privilege') ? 'checked' : '' }}>
                                                                    <label class="form-check-label"
                                                                        for="privilege_{{ $privilege->id }}">{{ $privilege->prv_name }}</label>
                                                                </div>
                                                            @endif
                                                        @endforeach
                                                    </div>
                                                    @if ($errors->has('folder_privilege'))
                                                        <span
                                                            class="text-red-500 text-sm">{{ $errors->first('folder_privilege') }}</span>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Additional System Access Section -->
                                <div class="row mb-4">
                                    <div class="col-md-12">
                                        <h5 class="section-title mb-3 d-flex align-items-center">
                                            <i class="fas fa-desktop me-2"></i>Additional System Access
                                            <i class="fas fa-question-circle text-primary ms-2" data-bs-toggle="tooltip"
                                                data-bs-placement="top" data-bs-html="true"
                                                title="Select the systems you need access to by ticking the checkboxes."></i>
                                        </h5>
                                    </div>
                                </div>

                                <!-- HealthAI HMIS Access -->
                                <div class="card mb-3" style="border-left: 4px solid #007A33;">
                                    <div class="card-header bg-light">
                                        <div class="d-flex align-items-center justify-content-between">
                                            <div class="d-flex align-items-center">
                                                <h5 class="fw-normal mb-0 me-3" style="color: #333;">
                                                    <i class="fas fa-hospital me-2" style="color: #007A33;"></i>HealthAI
                                                    HMIS Access
                                                </h5>
                                                <i class="fas fa-question-circle text-primary me-2"
                                                    data-bs-toggle="tooltip" data-bs-placement="top" data-bs-html="true"
                                                    title="HealthAI HMIS Access provides access to the Health Management Information System. This system is used for managing patient records, medical data, and healthcare operations. Select this if you need access to patient information and medical records."></i>
                                            </div>
                                            <div class="radio-toggle-group">
                                                <input type="radio" name="enable_hmis_radio" id="enable_hmis_yes"
                                                    value="1" {{ old('enable_hmis') ? 'checked' : '' }}
                                                    data-target="enable_hmis" data-section="hmis_section">
                                                <label for="enable_hmis_yes"
                                                    class="radio-label radio-label-yes">Yes</label>
                                                <input type="radio" name="enable_hmis_radio" id="enable_hmis_no"
                                                    value="0" {{ !old('enable_hmis') ? 'checked' : '' }}
                                                    data-target="enable_hmis" data-section="hmis_section">
                                                <label for="enable_hmis_no" class="radio-label radio-label-no">No</label>
                                            </div>
                                            <input type="hidden" name="enable_hmis" id="enable_hmis"
                                                value="{{ old('enable_hmis') ? '1' : '0' }}">
                                        </div>
                                    </div>
                                    <div class="card-body" id="hmis_section">
                                        <div class="form-group">
                                            <label for="hmisId">HealthAI HMIS Access <span
                                                    class="text-danger">*</span></label>
                                            <select
                                                class="form-select hmis-multiselect {{ $errors->has('hmisId') ? 'border-red-500' : '' }}"
                                                id="hmisId" name="hmisId[]" multiple required>
                                                @foreach ($hmis as $hmi)
                                                    <option value="{{ $hmi->id }}"
                                                        {{ is_array(old('hmisId')) && in_array($hmi->id, old('hmisId')) ? 'selected' : '' }}>
                                                        {{ $hmi->names }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            @if ($errors->has('hmisId'))
                                                <span class="text-red-500 text-sm">{{ $errors->first('hmisId') }}</span>
                                            @endif
                                            <small class="form-text text-muted">Select one or more roles from Health AI
                                                system. Use search to find roles quickly.</small>
                                        </div>
                                    </div>
                                </div>

                                <!-- Aruti HR MIS -->
                                <div class="card mb-3" style="border-left: 4px solid #007A33;">
                                    <div class="card-header bg-light">
                                        <div class="d-flex align-items-center justify-content-between">
                                            <div class="d-flex align-items-center">
                                                <h5 class="fw-normal mb-0 me-3" style="color: #333;">
                                                    <i class="fas fa-user-tie me-2" style="color: #007A33;"></i>Aruti HR
                                                    MIS
                                                </h5>
                                                <i class="fas fa-question-circle text-primary me-2"
                                                    data-bs-toggle="tooltip" data-bs-placement="top" data-bs-html="true"
                                                    title="Aruti HR MIS provides access to the Human Resources Management Information System. This system is used for managing employee data, payroll, attendance, leave management, and other HR-related functions."></i>
                                            </div>
                                            <div class="radio-toggle-group">
                                                <input type="radio" name="enable_aruti_radio" id="enable_aruti_yes"
                                                    value="1" {{ old('enable_aruti') ? 'checked' : '' }}
                                                    data-target="enable_aruti" data-section="aruti_section">
                                                <label for="enable_aruti_yes"
                                                    class="radio-label radio-label-yes">Yes</label>
                                                <input type="radio" name="enable_aruti_radio" id="enable_aruti_no"
                                                    value="0" {{ !old('enable_aruti') ? 'checked' : '' }}
                                                    data-target="enable_aruti" data-section="aruti_section">
                                                <label for="enable_aruti_no" class="radio-label radio-label-no">No</label>
                                            </div>
                                            <input type="hidden" name="enable_aruti" id="enable_aruti"
                                                value="{{ old('enable_aruti') ? '1' : '0' }}">
                                        </div>
                                    </div>
                                    <div class="card-body" id="aruti_section" style="display: none;">
                                        <div class="form-group">
                                            <label for="aruti">Aruti HR MIS <span class="text-danger">*</span></label>
                                            <select
                                                class="form-control {{ $errors->has('aruti') ? 'border-red-500' : '' }}"
                                                id="aruti" name="aruti" required>
                                                <option value="" disabled>--- Select an option ---</option>
                                                @php
                                                    $defaultUserAruti = isset($arutiLevels) ? $arutiLevels->firstWhere('aruti_name', 'User') : null;
                                                    $defaultUserArutiId = $defaultUserAruti && $defaultUserAruti->aruti_status == 'active' ? $defaultUserAruti->id : null;
                                                @endphp
                                                @foreach ($arutiLevels as $arutiLevel)
                                                    @if ($arutiLevel->aruti_status == 'active')
                                                        <option value="{{ $arutiLevel->id }}"
                                                            {{ old('aruti', $defaultUserArutiId) == $arutiLevel->id ? 'selected' : '' }}>
                                                            {{ $arutiLevel->aruti_name }}
                                                        </option>
                                                    @endif
                                                @endforeach
                                            </select>
                                            @if ($errors->has('aruti'))
                                                <span class="text-red-500 text-sm">{{ $errors->first('aruti') }}</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>

                                <!-- eDocs System -->
                                <div class="card mb-3" style="border-left: 4px solid #007A33;">
                                    <div class="card-header bg-light">
                                        <div class="d-flex align-items-center justify-content-between">
                                            <div class="d-flex align-items-center">
                                                <h5 class="fw-normal mb-0 me-3" style="color: #333;">
                                                    <i class="fas fa-file-alt me-2" style="color: #007A33;"></i>eDocs
                                                    System
                                                </h5>
                                                <i class="fas fa-question-circle text-primary me-2"
                                                    data-bs-toggle="tooltip" data-bs-placement="top" data-bs-html="true"
                                                    title="eDocs System provides access to the electronic document management system."></i>
                                            </div>
                                            <div class="radio-toggle-group">
                                                <input type="radio" name="enable_edocs_radio" id="enable_edocs_yes"
                                                    value="1" {{ old('enable_edocs') ? 'checked' : '' }}
                                                    data-target="enable_edocs" data-section="edocs_section">
                                                <label for="enable_edocs_yes"
                                                    class="radio-label radio-label-yes">Yes</label>
                                                <input type="radio" name="enable_edocs_radio" id="enable_edocs_no"
                                                    value="0" {{ !old('enable_edocs') ? 'checked' : '' }}
                                                    data-target="enable_edocs" data-section="edocs_section">
                                                <label for="enable_edocs_no" class="radio-label radio-label-no">No</label>
                                            </div>
                                            <input type="hidden" name="enable_edocs" id="enable_edocs"
                                                value="{{ old('enable_edocs') ? '1' : '0' }}">
                                        </div>
                                    </div>
                                    <div class="card-body" id="edocs_section" style="display: none;">
                                        <div class="form-group">
                                            <label for="edocs">eDocs System Access <span
                                                    class="text-danger">*</span></label>
                                            <div class="form-check-group">
                                                @if (isset($edocsLevels) && $edocsLevels->count() > 0)
                                                    @foreach ($edocsLevels as $edocsLevel)
                                                        <div class="form-check mb-2">
                                                            <input class="form-check-input" type="checkbox"
                                                                name="edocs[]" id="edocs_{{ $edocsLevel->id }}"
                                                                value="{{ $edocsLevel->id }}"
                                                                {{ is_array(old('edocs')) && in_array($edocsLevel->id, old('edocs')) ? 'checked' : '' }}>
                                                            <label class="form-check-label"
                                                                for="edocs_{{ $edocsLevel->id }}">
                                                                {{ $edocsLevel->edocs_name }}
                                                            </label>
                                                        </div>
                                                    @endforeach
                                                @else
                                                    <p class="text-muted">No eDocs access levels available. Please contact
                                                        administrator.</p>
                                                @endif
                                            </div>
                                            @if ($errors->has('edocs'))
                                                <span class="text-red-500 text-sm">{{ $errors->first('edocs') }}</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>

                                <!-- Network Access VPN -->
                                <div class="card mb-3" style="border-left: 4px solid #007A33;">
                                    <div class="card-header bg-light">
                                        <div class="d-flex align-items-center justify-content-between">
                                            <div class="d-flex align-items-center">
                                                <h5 class="fw-normal mb-0 me-3" style="color: #333;">
                                                    <i class="fas fa-network-wired me-2"
                                                        style="color: #007A33;"></i>Network Access VPN
                                                </h5>
                                                <i class="fas fa-question-circle text-primary me-2"
                                                    data-bs-toggle="tooltip" data-bs-placement="top" data-bs-html="true"
                                                    title="VPN (Virtual Private Network) Access allows you to securely connect to the company network from remote locations. This is required for accessing internal systems and resources when working from home or other remote locations."></i>
                                            </div>
                                            <div class="radio-toggle-group">
                                                <input type="radio" name="enable_vpn_radio" id="enable_vpn_yes"
                                                    value="1" {{ old('enable_vpn') ? 'checked' : '' }}
                                                    data-target="enable_vpn" data-section="vpn_section">
                                                <label for="enable_vpn_yes"
                                                    class="radio-label radio-label-yes">Yes</label>
                                                <input type="radio" name="enable_vpn_radio" id="enable_vpn_no"
                                                    value="0" {{ !old('enable_vpn') ? 'checked' : '' }}
                                                    data-target="enable_vpn" data-section="vpn_section">
                                                <label for="enable_vpn_no" class="radio-label radio-label-no">No</label>
                                            </div>
                                            <input type="hidden" name="enable_vpn" id="enable_vpn"
                                                value="{{ old('enable_vpn') ? '1' : '0' }}">
                                        </div>
                                    </div>
                                    <div class="card-body" id="vpn_section">
                                        <div class="form-group">
                                            <label for="VPN">Network Access VPN</label>
                                            <select class="form-control {{ $errors->has('VPN') ? 'border-red-500' : '' }}"
                                                id="VPN" name="VPN">
                                                <option value="" disabled>--- Select an option ---</option>
                                                @php
                                                    $defaultUserPrivilegeVPN = $privileges->firstWhere('prv_name', 'User');
                                                    $defaultUserPrivilegeVPNId = $defaultUserPrivilegeVPN && $defaultUserPrivilegeVPN->prv_status == 'active' && $defaultUserPrivilegeVPN->can_use_for_vpn_access ? $defaultUserPrivilegeVPN->id : null;
                                                @endphp
                                                @foreach ($privileges as $privilege)
                                                    @if ($privilege->prv_status == 'active' && $privilege->can_use_for_vpn_access)
                                                        <option value="{{ $privilege->id }}"
                                                            {{ old('VPN', $defaultUserPrivilegeVPNId) == $privilege->id ? 'selected' : '' }}>
                                                            {{ $privilege->prv_name }}
                                                        </option>
                                                    @endif
                                                @endforeach
                                            </select>
                                            @if ($errors->has('VPN'))
                                                <span class="text-red-500 text-sm">{{ $errors->first('VPN') }}</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>

                                <!-- SAP ERP Access -->
                                <div class="card mb-3" style="border-left: 4px solid #007A33;">
                                    <div class="card-header bg-light">
                                        <div class="d-flex align-items-center justify-content-between">
                                            <div class="d-flex align-items-center">
                                                <h5 class="fw-normal mb-0 me-3" style="color: #333;">
                                                    <i class="fas fa-database me-2" style="color: #007A33;"></i>SAP ERP
                                                    Access
                                                </h5>
                                                <i class="fas fa-question-circle text-primary me-2"
                                                    data-bs-toggle="tooltip" data-bs-placement="top" data-bs-html="true"
                                                    title="SAP ERP Access provides access to the Enterprise Resource Planning system. This system is used for managing financial transactions, procurement, inventory, and other business processes. Select this if you need to process financial transactions or manage business operations."></i>
                                            </div>
                                            <div class="radio-toggle-group">
                                                <input type="radio" name="enable_sap_radio" id="enable_sap_yes"
                                                    value="1" {{ old('enable_sap') ? 'checked' : '' }}
                                                    data-target="enable_sap" data-section="sap_section">
                                                <label for="enable_sap_yes"
                                                    class="radio-label radio-label-yes">Yes</label>
                                                <input type="radio" name="enable_sap_radio" id="enable_sap_no"
                                                    value="0" {{ !old('enable_sap') ? 'checked' : '' }}
                                                    data-target="enable_sap" data-section="sap_section">
                                                <label for="enable_sap_no" class="radio-label radio-label-no">No</label>
                                            </div>
                                            <input type="hidden" name="enable_sap" id="enable_sap"
                                                value="{{ old('enable_sap') ? '1' : '0' }}">
                                        </div>
                                    </div>
                                    <div class="card-body" id="sap_section">
                                        <div class="form-group">
                                            <label for="ASPId">SAP ERP Access</label>
                                            <select
                                                class="form-control {{ $errors->has('ASPId') ? 'border-red-500' : '' }}"
                                                id="ASPId" name="ASPId">
                                                <option value="" disabled selected>--- Select an option ---</option>
                                                @foreach ($acc as $access)
                                                    <option value="{{ $access->id }}"
                                                        {{ old('ASPId') == $access->id ? 'selected' : '' }}
                                                        {{ $access->access_name === 'N/A' && !old('ASPId') ? 'selected' : '' }}>
                                                        {{ $access->access_name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            @if ($errors->has('ASPId'))
                                                <span class="text-red-500 text-sm">{{ $errors->first('ASPId') }}</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>

                                <!-- Call Manager-PABX -->
                                <div class="card mb-3" style="border-left: 4px solid #007A33;">
                                    <div class="card-header bg-light">
                                        <div class="d-flex align-items-center justify-content-between">
                                            <div class="d-flex align-items-center">
                                                <h5 class="fw-normal mb-0 me-3" style="color: #333;">
                                                    <i class="fas fa-phone me-2" style="color: #007A33;"></i>Call
                                                    Manager-PABX
                                                </h5>
                                                <i class="fas fa-question-circle text-primary me-2"
                                                    data-bs-toggle="tooltip" data-bs-placement="top" data-bs-html="true"
                                                    title="This access is used when staff need access to use the CCBRT phone, especially for making external calls and managing communication outside the Organization."></i>
                                            </div>
                                            <div class="radio-toggle-group">
                                                <input type="radio" name="enable_pbax_radio" id="enable_pbax_yes"
                                                    value="1" {{ old('enable_pbax') ? 'checked' : '' }}
                                                    data-target="enable_pbax" data-section="pbax_section">
                                                <label for="enable_pbax_yes"
                                                    class="radio-label radio-label-yes">Yes</label>
                                                <input type="radio" name="enable_pbax_radio" id="enable_pbax_no"
                                                    value="0" {{ !old('enable_pbax') ? 'checked' : '' }}
                                                    data-target="enable_pbax" data-section="pbax_section">
                                                <label for="enable_pbax_no" class="radio-label radio-label-no">No</label>
                                            </div>
                                            <input type="hidden" name="enable_pbax" id="enable_pbax"
                                                value="{{ old('enable_pbax') ? '1' : '0' }}">
                                        </div>
                                    </div>
                                    <div class="card-body" id="pbax_section">
                                        <div class="form-group">
                                            <label for="pbax">Call Manager-PABX</label>
                                            <select
                                                class="form-control {{ $errors->has('pbax') ? 'border-red-500' : '' }}"
                                                id="pbax" name="pbax">
                                                <option value="" disabled>--- Select an option ---</option>
                                                @php
                                                    $defaultUserPrivilegePBAX = $privileges->firstWhere('prv_name', 'User');
                                                    $defaultUserPrivilegePBAXId = $defaultUserPrivilegePBAX && $defaultUserPrivilegePBAX->prv_status == 'active' && $defaultUserPrivilegePBAX->can_use_for_pbax_access ? $defaultUserPrivilegePBAX->id : null;
                                                @endphp
                                                @foreach ($privileges as $privilege)
                                                    @if ($privilege->prv_status == 'active' && $privilege->can_use_for_pbax_access)
                                                        <option value="{{ $privilege->id }}"
                                                            {{ old('pbax', $defaultUserPrivilegePBAXId) == $privilege->id ? 'selected' : '' }}>
                                                            {{ $privilege->prv_name }}
                                                        </option>
                                                    @endif
                                                @endforeach
                                            </select>
                                            @if ($errors->has('pbax'))
                                                <span class="text-red-500 text-sm">{{ $errors->first('pbax') }}</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>

                                <!-- Hardware Request Section -->
                                <div class="card mb-3" style="border-left: 4px solid #007A33;">
                                    <div class="card-header bg-light">
                                        <div class="d-flex align-items-center justify-content-between">
                                            <div class="d-flex align-items-center">
                                                <h5 class="fw-normal mb-0 me-3" style="color: #333;">
                                                    <i class="fas fa-laptop me-2" style="color: #007A33;"></i>Hardware
                                                    Request
                                                </h5>
                                                <i class="fas fa-question-circle text-primary me-2"
                                                    data-bs-toggle="tooltip" data-bs-placement="top" data-bs-html="true"
                                                    title="Hardware Request allows you to request physical equipment such as laptops, desktop computers, telephones, or external drives. Select this if you need new hardware or replacement equipment for your work."></i>
                                            </div>
                                            <div class="radio-toggle-group">
                                                <input type="radio" name="enable_hardware_radio"
                                                    id="enable_hardware_yes" value="1"
                                                    {{ old('enable_hardware') ? 'checked' : '' }}
                                                    data-target="enable_hardware" data-section="hardware_section">
                                                <label for="enable_hardware_yes"
                                                    class="radio-label radio-label-yes">Yes</label>
                                                <input type="radio" name="enable_hardware_radio"
                                                    id="enable_hardware_no" value="0"
                                                    {{ !old('enable_hardware') ? 'checked' : '' }}
                                                    data-target="enable_hardware" data-section="hardware_section">
                                                <label for="enable_hardware_no"
                                                    class="radio-label radio-label-no">No</label>
                                            </div>
                                            <input type="hidden" name="enable_hardware" id="enable_hardware"
                                                value="{{ old('enable_hardware') ? '1' : '0' }}">
                                        </div>
                                    </div>
                                    <div class="card-body" id="hardware_section">
                                        <div class="form-group">
                                            <label>Hardware Request</label>
                                            <div
                                                class="row {{ $errors->has('hardware_request') ? 'border border-red-500 p-2 rounded' : '' }}">
                                                <div class="col-md-3">
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="checkbox"
                                                            name="hardware_request[]" value="Laptop computer"
                                                            id="laptop"
                                                            {{ is_array(old('hardware_request')) && in_array('Laptop computer', old('hardware_request')) ? 'checked' : '' }}>
                                                        <label class="form-check-label" for="laptop">Laptop
                                                            Computer</label>
                                                    </div>
                                                </div>
                                                <div class="col-md-3">
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="checkbox"
                                                            name="hardware_request[]" value="Desktop computer"
                                                            id="desktop"
                                                            {{ is_array(old('hardware_request')) && in_array('Desktop computer', old('hardware_request')) ? 'checked' : '' }}>
                                                        <label class="form-check-label" for="desktop">Desktop
                                                            Computer</label>
                                                    </div>
                                                </div>
                                                <div class="col-md-3">
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="checkbox"
                                                            name="hardware_request[]" value="Telephone" id="telephone"
                                                            {{ is_array(old('hardware_request')) && in_array('Telephone', old('hardware_request')) ? 'checked' : '' }}>
                                                        <label class="form-check-label" for="telephone">Telephone</label>
                                                    </div>
                                                </div>
                                                <div class="col-md-3">
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="checkbox"
                                                            name="hardware_request[]" value="Drive" id="drive"
                                                            {{ is_array(old('hardware_request')) && in_array('Drive', old('hardware_request')) ? 'checked' : '' }}>
                                                        <label class="form-check-label" for="drive">External
                                                            Drive</label>
                                                    </div>
                                                </div>
                                            </div>
                                            @if ($errors->has('hardware_request'))
                                                <span
                                                    class="text-red-500 text-sm">{{ $errors->first('hardware_request') }}</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>

                                <!-- Access Key Cards Section -->
                                <div class="card mb-3" style="border-left: 4px solid #007A33;">
                                    <div class="card-header bg-light">
                                        <div class="d-flex align-items-center justify-content-between">
                                            <div class="d-flex align-items-center">
                                                <h5 class="fw-normal mb-0 me-3" style="color: #333;">
                                                    <i class="fas fa-key me-2" style="color: #007A33;"></i>Access Key
                                                    Cards
                                                </h5>
                                                <i class="fas fa-question-circle text-primary me-2"
                                                    data-bs-toggle="tooltip" data-bs-placement="top" data-bs-html="true"
                                                    title="Access Key Cards provide physical access to secured areas of the building. This card is used to unlock doors and access restricted areas. Select this if you need a key card for building access."></i>
                                            </div>
                                            <div class="radio-toggle-group">
                                                <input type="radio" name="enable_keycard_radio" id="enable_keycard_yes"
                                                    value="1" {{ old('enable_keycard') ? 'checked' : '' }}
                                                    data-target="enable_keycard" data-section="keycard_section">
                                                <label for="enable_keycard_yes"
                                                    class="radio-label radio-label-yes">Yes</label>
                                                <input type="radio" name="enable_keycard_radio" id="enable_keycard_no"
                                                    value="0" {{ !old('enable_keycard') ? 'checked' : '' }}
                                                    data-target="enable_keycard" data-section="keycard_section">
                                                <label for="enable_keycard_no"
                                                    class="radio-label radio-label-no">No</label>
                                            </div>
                                            <input type="hidden" name="enable_keycard" id="enable_keycard"
                                                value="{{ old('enable_keycard') ? '1' : '0' }}">
                                        </div>
                                    </div>
                                    <div class="card-body" id="keycard_section">
                                        <div class="form-group">
                                            <label for="access_key_card_id">Select Access Key Card(s) <span
                                                    class="text-danger">*</span></label>
                                            <select
                                                class="form-select keycard-multiselect {{ $errors->has('access_key_card_id') ? 'border-red-500' : '' }}"
                                                id="access_key_card_id" name="access_key_card_id[]" multiple>
                                                @foreach ($accessKeyCards as $card)
                                                    <option value="{{ $card->id }}"
                                                        {{ is_array(old('access_key_card_id')) && in_array($card->id, old('access_key_card_id')) ? 'selected' : '' }}>
                                                        {{ $card->card_number }} @if ($card->notes)
                                                            - {{ $card->notes }}
                                                        @endif
                                                    </option>
                                                @endforeach
                                            </select>
                                            @if ($errors->has('access_key_card_id'))
                                                <span
                                                    class="text-red-500 text-sm">{{ $errors->first('access_key_card_id') }}</span>
                                            @endif
                                            <small class="form-text text-muted">Select one or more available access key
                                                cards to request. Use search to find cards quickly.</small>
                                        </div>
                                    </div>
                                </div>

                                <!-- User Type Detection Alert -->
                                <div id="userTypeAlert" class="alert alert-info alert-dismissible fade" role="alert"
                                    style="display: none;">
                                    <i class="fas fa-info-circle me-2"></i>
                                    <span id="userTypeAlertText"></span>
                                    <button type="button" class="btn-close" data-bs-dismiss="alert"
                                        aria-label="Close"></button>
                                </div>

                                <!-- Form Actions -->
                                <div class="form-group mt-4">
                                    <button type="submit" class="btn btn-primary" id="submitBtn"
                                        style="background-color: #007A33; border-color: #007A33;">
                                        <span class="btn-text">
                                            <i class="fas fa-paper-plane"></i> Submit Request
                                        </span>
                                        <span class="btn-loading" style="display: none;">
                                            <span class="spinner-border spinner-border-sm" role="status"
                                                aria-hidden="true"></span>
                                            Submitting...
                                        </span>
                                    </button>
                                    <a href="javascript:history.back();" class="btn btn-secondary">
                                        <i class="fas fa-arrow-left"></i> Cancel
                                    </a>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Select2 CSS -->
        <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />

        <!-- Inline CSS for Professional Styling -->
        <style>
            .page-wrapper {
                padding: 20px;
            }

            .text-muted {
                font-size: 0.95rem;
                color: #6c757d;
            }

            .card {
                border: none;
                border-radius: 8px;
                background-color: #ffffff;
            }

            .card-body {
                padding: 2rem;
            }

            .section-title {
                font-size: 1.15rem;
                font-weight: 600;
                color: #007A33;
                margin-bottom: 1.25rem;
                border-bottom: 2px solid #007A33;
                padding-bottom: 0.5rem;
            }

            .form-group {
                margin-bottom: 1.5rem;
            }

            .form-group label {
                font-weight: 500;
                color: #333;
                margin-bottom: 0.5rem;
                display: block;
            }

            .form-control,
            .form-select {
                border: 1px solid #ced4da;
                border-radius: 5px;
                padding: 0.5rem 0.75rem;
                font-size: 0.95rem;
                font-weight: 400;
            }

            .form-control:focus,
            .form-select:focus {
                border-color: #007A33;
                box-shadow: 0 0 0 0.2rem rgba(0, 122, 51, 0.25);
            }

            .form-control:disabled {
                background-color: #e9ecef;
                cursor: not-allowed;
            }

            .form-check {
                margin-bottom: 0.75rem;
            }

            .form-check-input {
                margin-top: 0.25rem;
            }

            .form-check-label {
                font-size: 0.95rem;
                color: #333;
                font-weight: 400;
            }

            .text-danger {
                font-weight: 400;
            }

            /* Red border for invalid inputs */
            .border-red-500 {
                border-color: #dc3545 !important;
            }

            .border-red-500:focus {
                border-color: #dc3545 !important;
                box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.25) !important;
            }

            /* Smooth scroll to first error */
            .scroll-to-error {
                scroll-margin-top: 100px;
            }

            select[multiple] {
                height: 120px;
                width: 100%;
                min-width: 300px;
            }

            .card-header.bg-light {
                background-color: #f8f9fa !important;
            }

            /* Enhanced Select2 Styling for HMIS Access and Access Key Cards */
            .hmis-multiselect+.select2-container,
            .keycard-multiselect+.select2-container {
                width: 100% !important;
            }

            .hmis-multiselect+.select2-container .select2-selection--multiple,
            .keycard-multiselect+.select2-container .select2-selection--multiple {
                min-height: 50px !important;
                max-height: 120px !important;
                overflow-y: auto;
                border: 1.5px solid #ced4da !important;
                border-radius: 6px;
                padding: 4px 8px;
                background-color: #ffffff;
                transition: all 0.3s ease;
            }

            .hmis-multiselect+.select2-container .select2-selection--multiple:focus-within,
            .keycard-multiselect+.select2-container .select2-selection--multiple:focus-within {
                border-color: #007A33 !important;
                box-shadow: 0 0 0 3px rgba(0, 122, 51, 0.1);
            }

            .hmis-multiselect+.select2-container .select2-selection__choice,
            .keycard-multiselect+.select2-container .select2-selection__choice {
                background-color: #007A33 !important;
                color: #ffffff !important;
                border: 1px solid #007A33 !important;
                margin: 3px 3px 3px 0;
                padding: 4px 8px;
                border-radius: 5px;
                display: inline-flex;
                align-items: center;
                font-size: 0.875rem;
                font-weight: 500;
            }

            .hmis-multiselect+.select2-container .select2-selection__choice__remove,
            .keycard-multiselect+.select2-container .select2-selection__choice__remove {
                color: #ffffff !important;
                cursor: pointer;
                margin-right: 6px;
                font-weight: bold;
                opacity: 0.8;
                transition: opacity 0.2s;
            }

            .hmis-multiselect+.select2-container .select2-selection__choice__remove:hover,
            .keycard-multiselect+.select2-container .select2-selection__choice__remove:hover {
                color: #ffffff !important;
                opacity: 1;
            }

            .hmis-multiselect+.select2-container .select2-search--inline,
            .keycard-multiselect+.select2-container .select2-search--inline {
                margin-top: 4px;
            }

            .hmis-multiselect+.select2-container .select2-search--inline .select2-search__field,
            .keycard-multiselect+.select2-container .select2-search--inline .select2-search__field {
                margin-top: 0 !important;
                height: 28px !important;
                padding: 4px 8px;
                font-size: 0.875rem;
                border: none !important;
                outline: none !important;
            }

            .hmis-multiselect+.select2-container .select2-search--inline .select2-search__field:focus,
            .keycard-multiselect+.select2-container .select2-search--inline .select2-search__field:focus {
                border: none !important;
                outline: none !important;
                box-shadow: none !important;
            }

            /* Select2 Dropdown Styling */
            .select2-dropdown {
                border: 1.5px solid #ced4da !important;
                border-radius: 6px !important;
                box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15) !important;
            }

            .select2-results__option {
                padding: 8px 12px;
                font-size: 0.875rem;
            }

            .select2-results__option--highlighted {
                background-color: #007A33 !important;
                color: #ffffff !important;
            }

            .select2-results__option[aria-selected="true"] {
                background-color: #e8f5e9 !important;
                color: #007A33 !important;
                font-weight: 600;
            }

            .select2-results__option[aria-selected="true"]:before {
                content: "✓ ";
                color: #007A33;
                font-weight: bold;
                margin-right: 4px;
            }

            /* Radio Toggle Group Styles */
            .radio-toggle-group {
                display: inline-flex;
                align-items: center;
                background: #f8f9fa;
                border: 1.5px solid #007A33;
                border-radius: 6px;
                padding: 1px;
                gap: 0;
            }

            .radio-toggle-group input[type="radio"] {
                display: none;
            }

            .radio-label {
                padding: 0.35rem 0.85rem;
                font-size: 0.75rem;
                font-weight: 600;
                cursor: pointer;
                transition: all 0.3s ease;
                border-radius: 5px;
                margin: 0;
                user-select: none;
                text-align: center;
                min-width: 50px;
            }

            .radio-label-yes {
                color: #007A33;
                background: transparent;
            }

            .radio-label-no {
                color: #6c757d;
                background: transparent;
            }

            .radio-toggle-group input[type="radio"]:checked+.radio-label-yes {
                background-color: #007A33;
                color: #ffffff;
            }

            .radio-toggle-group input[type="radio"]:checked+.radio-label-no {
                background-color: #6c757d;
                color: #ffffff;
            }

            .radio-label:hover {
                background-color: rgba(0, 122, 51, 0.1);
            }

            .radio-toggle-group input[type="radio"]:checked+.radio-label:hover {
                opacity: 0.9;
            }
        </style>

        <!-- JavaScript for form interactions -->
        @if ($errors->any())
            <script>
                document.addEventListener('DOMContentLoaded', function() {
                    const firstError = document.querySelector('.border-red-500');
                    if (firstError) {
                        firstError.scrollIntoView({
                            behavior: 'smooth',
                            block: 'center'
                        });
                        firstError.classList.add('scroll-to-error');
                    }
                });
            </script>
        @endif

        @push('scripts')
            <!-- Load Select2 -->
            <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
            <script>
                // Wait for jQuery and DOM to be ready
                (function() {
                    function initSelect2() {
                        // Check if jQuery is available
                        if (typeof jQuery === 'undefined' || typeof $ === 'undefined') {
                            console.warn('jQuery not yet loaded, retrying...');
                            setTimeout(initSelect2, 100);
                            return;
                        }

                        // Check if Select2 is available
                        if (typeof jQuery.fn.select2 === 'undefined') {
                            console.warn('Select2 not yet loaded, retrying...');
                            setTimeout(initSelect2, 100);
                            return;
                        }

                        // Initialize Select2 for HMIS multi-select with enhanced features
                        try {
                            jQuery(document).ready(function($) {
                                // Toggle "Other folder" input for Network Folder section
                                function toggleOtherFolderInput() {
                                    var enabled = $('#enable_network_folder').val() === '1';
                                    var isOther = $('#network_folder').val() === 'Other';

                                    if (enabled && isOther) {
                                        $('#other_folder_container').show();
                                    } else {
                                        $('#other_folder_container').hide();
                                        if (!isOther) {
                                            $('#other_network_folder').val('');
                                        }
                                    }
                                }

                                if ($('#network_folder').length && $('#other_folder_container').length) {
                                    toggleOtherFolderInput();
                                    $('#network_folder').on('change', toggleOtherFolderInput);
                                    $('#enable_network_folder_yes, #enable_network_folder_no').on('change', function() {
                                        setTimeout(toggleOtherFolderInput, 0);
                                    });
                                }

                                if ($('#hmisId').length) {
                                    // First, ensure no options are selected by default (unless from old values)
                                    var hasOldValues =
                                        {{ is_array(old('hmisId')) && count(old('hmisId')) > 0 ? 'true' : 'false' }};
                                    if (!hasOldValues) {
                                        // Clear any accidental selections
                                        $('#hmisId option').prop('selected', false);
                                    }

                                    $('#hmisId').select2({
                                        placeholder: "Search and select HMIS roles...",
                                        allowClear: true,
                                        width: '100%',
                                        closeOnSelect: false,
                                        tags: false,
                                        multiple: true,
                                        minimumResultsForSearch: 0,
                                        language: {
                                            noResults: function() {
                                                return "No roles found";
                                            },
                                            searching: function() {
                                                return "Searching...";
                                            },
                                            removeAllItems: function() {
                                                return "Remove all";
                                            }
                                        },
                                        templateResult: function(data) {
                                            if (!data.id) {
                                                return data.text;
                                            }
                                            var $result = $(
                                                '<span><i class="fas fa-check-circle me-2" style="color: #007A33;"></i>' +
                                                data.text + '</span>'
                                            );
                                            return $result;
                                        },
                                        templateSelection: function(data) {
                                            return data.text;
                                        }
                                    });

                                    // After initialization, ensure no auto-selection occurred
                                    if (!hasOldValues) {
                                        $('#hmisId').val(null).trigger('change');
                                    }

                                    // Add custom styling for selected items
                                    $('#hmisId').on('select2:select', function(e) {
                                        var selectedData = e.params.data;
                                        $(this).next('.select2-container').find('.select2-selection__choice')
                                            .each(function() {
                                                $(this).css({
                                                    'background-color': '#007A33',
                                                    'color': '#ffffff',
                                                    'border-color': '#007A33'
                                                });
                                            });
                                    });

                                    // Style existing selections on load
                                    $('#hmisId').on('select2:open', function() {
                                        setTimeout(function() {
                                            $('.select2-selection__choice').css({
                                                'background-color': '#007A33',
                                                'color': '#ffffff',
                                                'border-color': '#007A33'
                                            });
                                        }, 100);
                                    });
                                }

                                // Initialize Select2 for Access Key Cards multi-select
                                if ($('#access_key_card_id').length) {
                                    var hasOldKeycardValues =
                                        {{ is_array(old('access_key_card_id')) && count(old('access_key_card_id')) > 0 ? 'true' : 'false' }};
                                    if (!hasOldKeycardValues) {
                                        $('#access_key_card_id option').prop('selected', false);
                                    }

                                    $('#access_key_card_id').select2({
                                        placeholder: "Search and select access key cards...",
                                        allowClear: true,
                                        width: '100%',
                                        closeOnSelect: false,
                                        tags: false,
                                        multiple: true,
                                        minimumResultsForSearch: 0,
                                        language: {
                                            noResults: function() {
                                                return "No cards found";
                                            },
                                            searching: function() {
                                                return "Searching...";
                                            }
                                        },
                                        templateResult: function(data) {
                                            if (!data.id) {
                                                return data.text;
                                            }
                                            var $result = $(
                                                '<span><i class="fas fa-key me-2" style="color: #007A33;"></i>' +
                                                data.text + '</span>'
                                            );
                                            return $result;
                                        },
                                        templateSelection: function(data) {
                                            return data.text;
                                        }
                                    });

                                    if (!hasOldKeycardValues) {
                                        $('#access_key_card_id').val(null).trigger('change');
                                    }

                                    // Add custom styling for selected items
                                    $('#access_key_card_id').on('select2:select', function(e) {
                                        $(this).next('.select2-container').find('.select2-selection__choice')
                                            .each(function() {
                                                $(this).css({
                                                    'background-color': '#007A33',
                                                    'color': '#ffffff',
                                                    'border-color': '#007A33'
                                                });
                                            });
                                    });

                                    // Style existing selections on load
                                    $('#access_key_card_id').on('select2:open', function() {
                                        setTimeout(function() {
                                            $('#access_key_card_id').next('.select2-container').find(
                                                '.select2-selection__choice').css({
                                                'background-color': '#007A33',
                                                'color': '#ffffff',
                                                'border-color': '#007A33'
                                            });
                                        }, 100);
                                    });
                                }
                            });
                        } catch (e) {
                            console.error('Select2 initialization failed:', e);
                        }
                    }

                    // Start initialization
                    if (document.readyState === 'loading') {
                        document.addEventListener('DOMContentLoaded', initSelect2);
                    } else {
                        initSelect2();
                    }
                })();

                // Handle radio button toggles for optional sections
                document.addEventListener('DOMContentLoaded', function() {
                    // Helper function to set default "User" value for select fields
                    function setDefaultUserValue(selectElement) {
                        if (!selectElement || selectElement.tagName !== 'SELECT') return;
                        
                        // Check if select already has a value (from old input or previous selection)
                        if (selectElement.value && selectElement.value !== '') return;
                        
                        // Find the "User" option
                        const userOption = Array.from(selectElement.options).find(option => {
                            const optionText = option.textContent.trim();
                            return optionText === 'User' || optionText.toLowerCase() === 'user';
                        });
                        
                        if (userOption && userOption.value) {
                            selectElement.value = userOption.value;
                            // Trigger change event to ensure any listeners are notified
                            selectElement.dispatchEvent(new Event('change', { bubbles: true }));
                        }
                    }

                    function updateSectionState(hiddenInputId, sectionId, isEnabled) {
                        const hiddenInput = document.getElementById(hiddenInputId);
                        const section = document.getElementById(sectionId);

                        if (hiddenInput && section) {
                            if (isEnabled) {
                                section.style.display = 'block';
                                const inputs = section.querySelectorAll('input, select, textarea');
                                inputs.forEach(input => {
                                    input.removeAttribute('disabled');
                                    
                                    // Set default "User" value for specific select fields
                                    if (input.tagName === 'SELECT' && !input.hasAttribute('multiple')) {
                                        const selectId = input.id;
                                        // Fields that should default to "User"
                                        if (['active_drt', 'email', 'aruti', 'VPN', 'pbax'].includes(selectId)) {
                                            setDefaultUserValue(input);
                                        }
                                    }
                                    
                                    const label = section.querySelector(`label[for="${input.id}"]`);
                                    if (label && label.innerHTML.includes('*')) {
                                        // For radio buttons with the same name, only require one
                                        if (input.type === 'radio') {
                                            const radioName = input.name;
                                            const radioGroup = section.querySelectorAll(
                                                `input[type="radio"][name="${radioName}"]`);
                                            radioGroup.forEach(radio => {
                                                radio.setAttribute('required', 'required');
                                            });
                                        } else {
                                            input.setAttribute('required', 'required');
                                        }
                                    }
                                });
                            } else {
                                section.style.display = 'none';
                                const inputs = section.querySelectorAll('input, select, textarea');
                                inputs.forEach(input => {
                                    input.setAttribute('disabled', 'disabled');
                                    // Remove required from all inputs, including radio buttons
                                    if (input.type === 'radio') {
                                        const radioName = input.name;
                                        const radioGroup = document.querySelectorAll(
                                            `input[type="radio"][name="${radioName}"]`);
                                        radioGroup.forEach(radio => {
                                            radio.removeAttribute('required');
                                        });
                                    } else {
                                        input.removeAttribute('required');
                                    }
                                    if (input.type === 'checkbox') {
                                        input.checked = false;
                                    } else if (input.tagName === 'SELECT') {
                                        // For multi-select, clear all selections
                                        if (input.hasAttribute('multiple')) {
                                            // Clear Select2 if it's initialized
                                            if (jQuery && jQuery(input).hasClass('select2-hidden-accessible')) {
                                                jQuery(input).val(null).trigger('change');
                                            } else {
                                                // Clear native select
                                                Array.from(input.options).forEach(option => {
                                                    option.selected = false;
                                                });
                                            }
                                        } else {
                                            // For single select, set to first empty option or unselect
                                            input.selectedIndex = -1;
                                        }
                                    } else if (input.type === 'radio') {
                                        // Don't uncheck radio buttons, just remove required
                                    } else {
                                        input.value = '';
                                    }
                                });
                            }
                        }
                    }

                    // Initialize all radio button groups
                    const radioGroups = {};
                    document.querySelectorAll('.radio-toggle-group input[type="radio"]').forEach(radio => {
                        const targetId = radio.getAttribute('data-target');
                        const sectionId = radio.getAttribute('data-section');
                        const hiddenInput = document.getElementById(targetId);

                        if (!radioGroups[targetId]) {
                            radioGroups[targetId] = {
                                hiddenInput: hiddenInput,
                                sectionId: sectionId,
                                radios: []
                            };
                        }
                        radioGroups[targetId].radios.push(radio);

                        // Handle radio button change
                        radio.addEventListener('change', function() {
                            if (this.checked && hiddenInput) {
                                const isEnabled = this.value === '1';
                                hiddenInput.value = this.value;
                                updateSectionState(targetId, sectionId, isEnabled);
                            }
                        });
                    });

                    // Set initial state for all sections
                    Object.keys(radioGroups).forEach(targetId => {
                        const group = radioGroups[targetId];
                        const checkedRadio = group.radios.find(r => r.checked);
                        if (checkedRadio && group.hiddenInput) {
                            const isEnabled = checkedRadio.value === '1';
                            group.hiddenInput.value = checkedRadio.value;
                            updateSectionState(targetId, group.sectionId, isEnabled);
                            
                            // If section is enabled on page load, set default "User" values
                            if (isEnabled) {
                                setTimeout(() => {
                                    const section = document.getElementById(group.sectionId);
                                    if (section) {
                                        const selectFields = section.querySelectorAll('select:not([multiple])');
                                        selectFields.forEach(select => {
                                            if (['active_drt', 'email', 'aruti', 'VPN', 'pbax'].includes(select.id)) {
                                                setDefaultUserValue(select);
                                            }
                                        });
                                    }
                                }, 100);
                            }
                        } else if (group.hiddenInput) {
                            // If no radio is checked, default to disabled
                            const noRadio = group.radios.find(r => r.value === '0');
                            if (noRadio) {
                                noRadio.checked = true;
                                group.hiddenInput.value = '0';
                                updateSectionState(targetId, group.sectionId, false);
                            }
                        }
                    });

                    // Initialize Bootstrap tooltips
                    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
                    var tooltipList = tooltipTriggerList.map(function(tooltipTriggerEl) {
                        return new bootstrap.Tooltip(tooltipTriggerEl);
                    });
                });

                // Auto-set action_required based on user_type
                document.addEventListener('DOMContentLoaded', function() {
                    const userType = document.getElementById('user_type');
                    const actionRequired = document.getElementById('action_required');

                    if (userType && actionRequired) {
                        userType.addEventListener('change', function() {
                            const selectedValue = this.value;
                            if (selectedValue === 'New') {
                                actionRequired.value = 'Create';
                            } else if (selectedValue === 'Existing') {
                                actionRequired.value = 'Update';
                            } else {
                                actionRequired.value = '';
                            }
                        });
                    }

                    // Detect if user is requesting super admin access or is from IT department
                    const activeDrtSelect = document.getElementById('active_drt');
                    const emailSelect = document.getElementById('email');
                    const isITDepartment = document.getElementById('is_it_department').value === '1';
                    const userTypeAlert = document.getElementById('userTypeAlert');
                    const userTypeAlertText = document.getElementById('userTypeAlertText');

                    function checkUserType() {
                        let showAlert = false;
                        let alertMessage = '';

                        // Check if requesting Super Administrator access
                        const activeDrtOption = activeDrtSelect.options[activeDrtSelect.selectedIndex];
                        const emailOption = emailSelect.options[emailSelect.selectedIndex];

                        const isRequestingSuperAdmin =
                            (activeDrtOption && activeDrtOption.getAttribute('data-privilege-name') ===
                                'Super Administrator') ||
                            (emailOption && emailOption.text === 'Super Administrator');

                        if (isRequestingSuperAdmin && !isITDepartment) {
                            showAlert = true;
                            alertMessage =
                                'You are requesting Super Administrator access. This request will require additional approval as you are not from the IT department.';
                        } else if (isITDepartment) {
                            showAlert = true;
                            alertMessage =
                                'You are from the IT department. This request will be processed with IT privileges.';
                        }

                        if (showAlert) {
                            userTypeAlertText.textContent = alertMessage;
                            userTypeAlert.classList.add('show');
                            userTypeAlert.style.display = 'block';
                        } else {
                            userTypeAlert.classList.remove('show');
                            userTypeAlert.style.display = 'none';
                        }
                    }

                    if (activeDrtSelect) {
                        activeDrtSelect.addEventListener('change', checkUserType);
                    }
                    if (emailSelect) {
                        emailSelect.addEventListener('change', checkUserType);
                    }

                    // Initial check
                    checkUserType();

                    // Form submission with loading animation
                    const ictAccessForm = document.getElementById('ictAccessForm');
                    if (ictAccessForm) {
                        ictAccessForm.addEventListener('submit', function(e) {
                            const submitBtn = document.getElementById('submitBtn');
                            const btnText = submitBtn.querySelector('.btn-text');
                            const btnLoading = submitBtn.querySelector('.btn-loading');
                            const form = this;

                            // Custom validation for Select2 and enabled sections
                            let isValid = true;
                            const errors = [];

                            // Check all enabled sections for required fields
                            document.querySelectorAll('.radio-toggle-group input[type="radio"]:checked').forEach(
                                radio => {
                                    if (radio.value === '1') {
                                        const sectionId = radio.getAttribute('data-section');
                                        const section = document.getElementById(sectionId);

                                        if (section) {
                                            const requiredFields = section.querySelectorAll('[required]');
                                            requiredFields.forEach(field => {
                                                if (field.disabled) {
                                                    return; // Skip disabled fields
                                                }

                                                // Special handling for Select2 multi-select (HMIS)
                                                if (field.id === 'hmisId' && jQuery && jQuery(field)
                                                    .hasClass('select2-hidden-accessible')) {
                                                    const selectedValues = jQuery(field).val();
                                                    if (!selectedValues || selectedValues.length ===
                                                        0) {
                                                        isValid = false;
                                                        errors.push(
                                                            'Please select at least one HealthAI HMIS Access role.'
                                                        );
                                                        jQuery(field).next('.select2-container')
                                                            .addClass('border border-danger');
                                                    } else {
                                                        jQuery(field).next('.select2-container')
                                                            .removeClass('border border-danger');
                                                    }
                                                } else if (field.type === 'radio') {
                                                    // For radio buttons, check if any in the group is checked
                                                    const radioName = field.name;
                                                    const radioGroup = section.querySelectorAll(
                                                        `input[type="radio"][name="${radioName}"]`);
                                                    const isChecked = Array.from(radioGroup).some(
                                                        radio => radio.checked && !radio.disabled);
                                                    if (!isChecked) {
                                                        isValid = false;
                                                        const label = section.querySelector(
                                                            `label:has(input[name="${radioName}"])`
                                                        ) || section.querySelector(
                                                            `label[for="${field.id}"]`);
                                                        const fieldName = label ? label.textContent
                                                            .replace('*', '').trim() : 'Field';
                                                        errors.push(`${fieldName} is required.`);
                                                        radioGroup.forEach(radio => {
                                                            if (!radio.disabled) {
                                                                radio.closest('.form-check')
                                                                    ?.classList.add(
                                                                        'border-danger');
                                                            }
                                                        });
                                                    } else {
                                                        radioGroup.forEach(radio => {
                                                            radio.closest('.form-check')
                                                                ?.classList.remove(
                                                                    'border-danger');
                                                        });
                                                    }
                                                } else if (field.value === '' || field.value === null ||
                                                    (field.tagName === 'SELECT' && field
                                                        .selectedIndex <= 0 && !field.hasAttribute(
                                                            'multiple'))) {
                                                    isValid = false;
                                                    const label = section.querySelector(
                                                        `label[for="${field.id}"]`);
                                                    const fieldName = label ? label.textContent.replace(
                                                        '*', '').trim() : 'Field';
                                                    errors.push(`${fieldName} is required.`);
                                                    field.classList.add('border-danger');
                                                } else {
                                                    field.classList.remove('border-danger');
                                                }
                                            });
                                        }
                                    }
                                });

                            if (!isValid) {
                                e.preventDefault();
                                e.stopPropagation();

                                // Show error message
                                const errorMessage = errors.length > 0 ? errors[0] :
                                    'Please fill in all required fields.';
                                alert(errorMessage);

                                // Scroll to first error
                                const firstError = form.querySelector(
                                    '.border-danger, .select2-container.border-danger');
                                if (firstError) {
                                    firstError.scrollIntoView({
                                        behavior: 'smooth',
                                        block: 'center'
                                    });
                                    if (firstError.tagName === 'SELECT' || firstError.tagName === 'INPUT') {
                                        firstError.focus();
                                    }
                                }

                                return false;
                            }

                            // Check HTML5 validation
                            if (!form.checkValidity()) {
                                form.reportValidity();
                                return false;
                            }

                            // Show loading animation
                            submitBtn.disabled = true;
                            btnText.style.display = 'none';
                            btnLoading.style.display = 'inline-block';

                            // Form will submit normally after this
                        });
                    }
                });
            </script>
        @endpush
    @endsection
