@extends('layouts.template')
@section('breadcrumb')
    @include('sweetalert::alert')

    <div class="page-wrapper">
        <div class="content container-fluid">
            <div class="page-header">
                <div class="row">
                    <div class="col-sm-12">
                        <div class="page-sub-header">
                            <h3 class="page-title">Edit ICT Access Form</h3>
                        </div>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-body">
                            <p>Please update the following form.</p>

                            <form method="POST" action="{{ route('form.update', $ictForm->id) }}">
                                @csrf
                                @method('PUT') <!-- Use PUT for updating -->

                                <div class="row">
                                    <!-- Column 1 -->
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="aruti">Aruti HR MIS<span class="text-danger">*</span></label>
                                            <select class="form-control" id="aruti" name="aruti" required>
                                                <option value="" disabled>Select an option</option>
                                                @foreach ($privileges as $privilege)
                                                    @if (in_array($privilege->prv_name, ['User', 'Administrator', 'Super Administrator', 'HR Officer', 'HR Manager']))
                                                        <option value="{{ $privilege->id }}"
                                                            {{ $ictForm->aruti == $privilege->id ? 'selected' : '' }}>
                                                            {{ $privilege->prv_name }}</option>
                                                    @endif
                                                @endforeach
                                            </select>
                                        </div>

                                        <div class="form-group">
                                            <label for="sap">SAP ERP<span class="text-danger">*</span></label>
                                            <select class="form-control" id="sap" name="sap" required>
                                                <option value="" disabled>Select an option</option>
                                                @foreach ($privileges as $privilege)
                                                    @if (in_array($privilege->prv_name, [
                                                            'User',
                                                            'Administrator',
                                                            'Finance',
                                                            'Payroll Accountant',
                                                            'CFO',
                                                            'HR',
                                                            'HR Manager',
                                                            'HR Biodata',
                                                            'Director of HR COO',
                                                        ]))
                                                        <option value="{{ $privilege->id }}"
                                                            {{ $ictForm->sap == $privilege->id ? 'selected' : '' }}>
                                                            {{ $privilege->prv_name }}</option>
                                                    @endif
                                                @endforeach
                                            </select>
                                        </div>

                                        <div class="form-group">
                                            <label for="pbax">PABX<span class="text-danger">*</span></label>
                                            <select class="form-control" id="pbax" name="pbax" required>
                                                <option value="" disabled>Select an option</option>
                                                @foreach ($privileges as $privilege)
                                                    @if (in_array($privilege->prv_name, ['User', 'Administrator']))
                                                        <option value="{{ $privilege->id }}"
                                                            {{ $ictForm->pbax == $privilege->id ? 'selected' : '' }}>
                                                            {{ $privilege->prv_name }}</option>
                                                    @endif
                                                @endforeach
                                            </select>
                                        </div>

                                        <div class="form-group">
                                            <label for="hmisId">OpenClinic HMIS Access</label>
                                            <select class="form-control" id="hmisId" name="hmisId" required>
                                                <option value="" disabled>Select an option</option>
                                                @foreach ($hmis as $hmi)
                                                    <option value="{{ $hmi->id }}"
                                                        {{ $ictForm->hmisId == $hmi->id ? 'selected' : '' }}>
                                                        {{ $hmi->names }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>

                                    <!-- Column 2 -->
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="active_drt">Active Directory<span
                                                    class="text-danger">*</span></label>
                                            <select class="form-control" id="active_drt" name="active_drt" required>
                                                <option value="" disabled>Select an option</option>
                                                @foreach ($privileges as $privilege)
                                                    @if (in_array($privilege->prv_name, ['User', 'Administrator']))
                                                        <option value="{{ $privilege->id }}"
                                                            {{ $ictForm->active_drt == $privilege->id ? 'selected' : '' }}>
                                                            {{ $privilege->prv_name }}</option>
                                                    @endif
                                                @endforeach
                                            </select>
                                        </div>

                                        <div class="form-group">
                                            <label>Hardware</label>
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <div class="checkbox">
                                                        <label><input type="checkbox" class="hardware-checkbox"
                                                                name="hardware_request[]" value="Laptop computer"
                                                                {{ in_array('Laptop computer', $ictForm->hardware_request) ? 'checked' : '' }}>
                                                            Laptop computer</label>
                                                    </div>
                                                    <div class="checkbox">
                                                        <label><input type="checkbox" class="hardware-checkbox"
                                                                name="hardware_request[]" value="Desktop computer"
                                                                {{ in_array('Desktop computer', $ictForm->hardware_request) ? 'checked' : '' }}>
                                                            Desktop computer</label>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="checkbox">
                                                        <label><input type="checkbox" class="hardware-checkbox"
                                                                name="hardware_request[]" value="Telephone"
                                                                {{ in_array('Telephone', $ictForm->hardware_request) ? 'checked' : '' }}>
                                                            Telephone</label>
                                                    </div>
                                                    <div class="checkbox">
                                                        <label><input type="checkbox" class="hardware-checkbox"
                                                                name="hardware_request[]" value="Drive"
                                                                {{ in_array('External Drive', $ictForm->hardware_request) ? 'checked' : '' }}>
                                                            External Drive</label>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="form-group">
                                                <label for="network_folder">Network Folder</label>
                                                <input type="text" class="form-control" name="network_folder"
                                                    value="{{ $ictForm->network_folder }}" placeholder="e.g., HR_Documents"
                                                    required>
                                            </div>

                                            <div class="form-group">
                                                <label>Network Folder Access</label>
                                                <div class="d-flex">
                                                    @foreach ($privileges as $privilege)
                                                        @if (in_array($privilege->prv_name, ['Read', 'Write', 'Full Access']))
                                                            <div class="form-check form-check-inline">
                                                                <input class="form-check-input" type="radio"
                                                                    name="folder_privilege"
                                                                    id="privilege_{{ $privilege->id }}"
                                                                    value="{{ $privilege->id }}"
                                                                    {{ $ictForm->folder_privilege == $privilege->id ? 'checked' : '' }}
                                                                    required>
                                                                <label class="form-check-label"
                                                                    for="privilege_{{ $privilege->id }}">{{ $privilege->prv_name }}</label>
                                                            </div>
                                                        @endif
                                                    @endforeach
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Column 3 -->
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="nhifId">NHIF Qualification</label>
                                            <select class="form-control" id="nhifId" name="nhifId" required>
                                                <option value="" disabled>Select an option</option>
                                                @foreach ($qualifications as $qualification)
                                                    <option value="{{ $qualification->id }}"
                                                        {{ $ictForm->nhifId == $qualification->id ? 'selected' : '' }}>
                                                        {{ $qualification->name }}</option>
                                                @endforeach
                                            </select>
                                        </div>

                                        <div class="form-group">
                                            <label for="VPN">Network Access VPN</label>
                                            <select class="form-control" id="VPN" name="VPN" required>
                                                <option value="" disabled>Select an option</option>
                                                @foreach ($privileges as $privilege)
                                                    @if (in_array($privilege->prv_name, ['User', 'Administrator']))
                                                        <option value="{{ $privilege->id }}"
                                                            {{ $ictForm->VPN == $privilege->id ? 'selected' : '' }}>
                                                            {{ $privilege->prv_name }}</option>
                                                    @endif
                                                @endforeach
                                            </select>
                                        </div>

                                        <div class="form-group">
                                            <label for="privilegeId">CCBRT Email</label>
                                            <select class="form-control" id="privilegeId" name="privilegeId" required>
                                                <option value="" disabled>Select an option</option>
                                                @foreach ($privileges as $privilege)
                                                    @if (in_array($privilege->prv_name, ['User', 'Administrator']))
                                                        <option value="{{ $privilege->id }}"
                                                            {{ $ictForm->privilegeId == $privilege->id ? 'selected' : '' }}>
                                                            {{ $privilege->prv_name }}</option>
                                                    @endif
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                </div>

                                <button type="submit" class="btn btn-primary mt-3" style="width: 100%;">Update</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
