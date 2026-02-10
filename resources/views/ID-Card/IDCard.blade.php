@extends('layouts.template')

@section('breadcrumb')
    @include('sweetalert::alert')
    {{-- @include('includes.loader') --}}

    <div class="page-wrapper">
        <div class="content container-fluid">
            <div class="page-header">
                <div class="row">
                    <div class="col-sm-12">
                        <div class="page-sub-header">
                            <h3 class="page-title">Staff ID Card Request Form</h3>
                        </div>
                    </div>
                </div>
            </div>

            <!-- SweetAlert Success Message -->
            @if (session('success'))
                <script>
                    Swal.fire({
                        title: 'Success!',
                        text: "{{ session('success') }}",
                        icon: 'success',
                        confirmButtonText: 'OK'
                    });
                </script>
            @endif

            <div class="row">
                <!-- Regulations Section -->
                <div class="col-md-12">
                    <div class="card shadow-sm">
                        <div class="card-body">
                            <table class="table regulation-table">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>CCBRT ID Card Regulations
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>1.</td>
                                        <td>All CCBRT Team members (employees/consultants) are required to identify
                                            themselves while on duty and for security reasons are required to wear their ID
                                            card (version 2018) visible at all times at CCBRT premises.
                                            <strong>Volunteers/Interns are required to wear name tags.</strong>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>2.</td>
                                        <td>CCBRT provides a standard CCBRT ID card (version 2018) once to the team members
                                            free of charge.</td>
                                    </tr>
                                    <tr>
                                        <td>3.</td>
                                        <td>For employees this is issued after completion of the probation period. Upon
                                            management approval a card might be issued during the probation period.
                                            Non-staff members will be issued as per contract.</td>
                                    </tr>
                                    <tr>
                                        <td>4.</td>
                                        <td>CCBRT management has the right to add/withdraw features assigned to the
                                            respective card (access rights, credits etc) at any time.</td>
                                    </tr>
                                    <tr>
                                        <td>5.</td>
                                        <td>Old CCBRT ID cards are no longer valid and should be returned to CCBRT HR
                                            office; these cards are no longer authorized by CCBRT as valid identification
                                            method.</td>
                                    </tr>
                                    <tr>
                                        <td>6.</td>
                                        <td>Staff will be required to wear their name tag (if provided) visible as well.
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>7.</td>
                                        <td>Loss or theft of the ID card needs to be reported within 12 hours to the CCBRT
                                            security department. Theft also needs to be reported to the police and a copy of
                                            the report to be provided to CCBRT Security office.</td>
                                    </tr>
                                    <tr>
                                        <td>8.</td>
                                        <td>Replacement costs need to be paid by the team member.</td>
                                    </tr>
                                    <tr>
                                        <td>9.</td>
                                        <td>Any wear & tear of the card or malfunctioning of its features should be reported
                                            to security immediately. Replacement costs due to wear & tear are not for cost
                                            of the team member. Replacement costs of the card due to negligence are for the
                                            team member.</td>
                                    </tr>
                                    <tr>
                                        <td>10.</td>
                                        <td>Negligence might also result in disciplinary action.</td>
                                    </tr>
                                    <tr>
                                        <td>11.</td>
                                        <td>The ID card remains property of CCBRT and should be returned to CCBRT at the end
                                            of the employment/contract.</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Card Application Form Section -->
                <div class="col-md-12">
                    <div class="card shadow-sm">
                        <div class="card-body">
                            <form method="POST" action="{{ route('IDCard.store') }}">
                                @csrf

                            <div class="form-group mt-4">
                                <label class="d-flex align-items-center">
                                    <input type="radio" name="regulationsConfirm" value="1" id="regulationsConfirm" class="toggle-switch" required>
                                    <span class="ml-2">I have read and agree to the above ID Card Regulations</span>
                                </label>
                                @error('regulationsConfirm')
                                    <div class="text-danger small">{{ $message }}</div>
                                @enderror
                            </div>

                                <!-- Form Actions -->
                                <div class="form-actions text-right">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save"></i> Request CCBRT ID card
                                    </button>
                                    <a href="javascript:history.back();" class="btn btn-secondary">
                                        <i class="fas fa-arrow-left"></i> Back
                                    </a>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <style>
        /* General Styling */
        .page-wrapper {
            background-color: #f8f9fa;
        }

        .card {
            border: none;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .toggle-switch {
        appearance: none;
        width: 50px;
        height: 25px;
        background-color: #ccc;
        border-radius: 25px;
        position: relative;
        outline: none;
        cursor: pointer;
        transition: background-color 0.3s;
    }
        .toggle-switch::before {
        content: "";
        width: 21px;
        height: 21px;
        border-radius: 50%;
        background-color: white;
        position: absolute;
        top: 2px;
        left: 2px;
        transition: 0.3s;
    }

        .toggle-switch:checked {
        background-color: #28a745;
    }

        .toggle-switch:checked::before {
        transform: translateX(25px);
    }
        /* Regulation Table */
        .regulation-table {
            width: 100%;
            border: 1px solid #ddd;
            border-collapse: collapse;
        }

        .regulation-table th,
        .regulation-table td {
            border: 1px solid #ddd;
            padding: 10px;
            vertical-align: top;
        }

        .regulation-table th {
            background-color: #f5f5f5;
            font-weight: bold;
            text-align: left;
            color: #2c3e50;
        }

        .regulation-table td {
            color: #34495e;
        }

        /* Form Table Styling */
        .form-table {
            width: 100%;
            margin-bottom: 20px;
        }

        .form-table td {
            padding: 12px 15px;
            vertical-align: top;
        }

        .form-label {
            font-weight: bold;
            color: #2c3e50;
            margin-bottom: 5px;
            display: block;
        }

        .form-value {
            color: #34495e;
            font-size: 14px;
        }

        /* Request Message */
        .request-message {
            color: #34495e;
            font-size: 14px;
            margin-bottom: 20px;
        }

        /* Checkbox */
        /* .form-check {
            margin-bottom: 20px;
        } */

        .form-check-label {
            color: #34495e;
            font-size: 14px;
            margin-left: 10px;
        }

        /* Form Actions */
        .form-actions {
            margin-top: 20px;
        }

        /* Version Info */
        .version-info {
            font-size: 12px;
            color: #7f8c8d;
            margin-top: 20px;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .form-table td {
                display: block;
                width: 100%;
                padding: 10px 0;
            }

            .form-actions {
                text-align: center;
            }

            .regulation-table th,
            .regulation-table td {
                display: block;
                width: 100%;
                text-align: left;
            }
        }
    </style>
@endsection
