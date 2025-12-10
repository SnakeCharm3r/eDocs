@extends('layouts.template')
@section('breadcrumb')
    @include('sweetalert::alert')
    @include('includes.loader')

    <div class="page-wrapper">
        <div class="content container-fluid">
            <div class="page-header">
                <div class="row">
                    <div class="col-sm-12">
                        <div class="page-sub-header">
                            <h3 class="page-title">Bank Details Form</h3>
                        </div>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-body">
                            <p>Please fill out the following bank details.</p>
                            @if (session('success'))
                                <div class="alert alert-success">
                                    {{ session('success') }}
                                </div>
                            @endif


                            @if (session('error'))
                                <div class="alert alert-danger">
                                    {{ session('error') }}
                                </div>
                            @endif

                            <form action="{{ route('bank-details.store') }}" method="POST">
                                @csrf
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="bank_name" class="form-label">Bank Name <span
                                                    class="text-danger">*</span></label>
                                            <input type="text" id="bank_name" name="bank_name"
                                                placeholder="e.g., NMB, CRDB, NBC, Exim Bank, Stanbic Bank"
                                                class="form-control" required maxlength="20" pattern="[A-Za-z\s,.'-]+"
                                                title="Only letters, spaces, commas, periods, apostrophes, and hyphens are allowed">
                                        </div>

                                        <div class="mb-3">
                                            <label for="branch_name" class="form-label">Branch Name <span
                                                    class="text-danger">*</span></label>
                                            <input type="text" id="branch_name" name="branch_name"
                                                placeholder="e.g., Kariakoo, Arusha, Mwenge, Dodoma" class="form-control"
                                                required maxlength="20" pattern="[A-Za-z\s,.'-]+"
                                                title="Only letters, spaces, commas, periods, apostrophes, and hyphens are allowed">
                                        </div>

                                        <div class="mb-3">
                                            <label for="account_name" class="form-label">Bank Account Name <span
                                                    class="text-danger">*</span></label>
                                            <input type="text" id="account_name" name="account_name"
                                                placeholder="e.g., John Doe, ABC Limited" class="form-control" required
                                                maxlength="20" pattern="[A-Za-z\s,.'-]+"
                                                title="Only letters, spaces, commas, periods, apostrophes, and hyphens are allowed">
                                        </div>

                                        <div class="mb-3">
                                            <label for="swift_code" class="form-label">Swift Code</label>
                                            <input type="text" id="swift_code" name="swift_code"
                                                placeholder="e.g., NMBTZTZP, CRDBTZTZ, NBCBTZTX" class="form-control">
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="branch_address" class="form-label">Bank Branch Address <span
                                                    class="text-danger">*</span></label>
                                            <input type="text" id="branch_address" name="branch_address"
                                                placeholder="e.g., Plot 123, Lumumba Street, Dar es Salaam"
                                                class="form-control" required>
                                        </div>

                                        <div class="mb-3">
                                            <label for="account_number" class="form-label">
                                                Bank Account Number <span class="text-danger">*</span>
                                            </label>
                                            <input type="text" id="account_number" name="account_number"
                                                placeholder="Enter bank account number" class="form-control" required
                                                maxlength="34" title="Enter a valid bank account number">
                                        </div>
                                        <div class="mb-3">
                                            <label for="bank_mobile_number" class="form-label">
                                                Mobile Phone <span class="text-danger">*</span>
                                            </label>
                                            <input type="text" id="bank_mobile_number" name="bank_mobile_number"
                                                placeholder="Enter mobile number" class="form-control" required
                                                maxlength="34" title="Enter a valid mobile number">
                                        </div>


                                    </div>
                                </div>

                                <!-- Employee Signature -->
                                <h6>Employee Confirmation</h6>
                                <p>
                                    {{-- I, <strong>{{ Auth::user()->fname }} {{ Auth::user()->mname }}
                                        {{ Auth::user()->lname }}</strong>, with my own will, do hereby --}}
<div class="form-check mb-4">
    <input class="form-check-input" type="checkbox" id="agreeTerms" name="agreeTerms" required>
    <label class="form-check-label" for="agreeTerms">
        I agree to submit the request to CCBRT to pay my eligible payments to the Bank Account details as provided above. 
        I also acknowledge and accept that any delays due to bank transfer processes, associated costs, or other issues will be my responsibility.
    </label>
    <div class="invalid-feedback">
        You must agree to the terms before submitting the request.
    </div>
</div>

                                </p>
                                {{-- <table class="table">
                                    <tr>
                                        <td>
                                            <!-- Signature Label -->
                                            <strong>Employee Signature:</strong>
                                            @if ($user->signature)
                                                <!-- Display Signature Image -->
                                                <img src="data:image/png;base64,{{ $user->signature }}" alt="User Signature"
                                                    style="max-width: 100px; height: auto; vertical-align: middle;">
                                            @else
                                                <!-- Display 'No Signature' if not available -->
                                                <span>No Signature</span>
                                            @endif

                                            <!-- Add space between signature and date -->
                                            <span style="margin-left: 20px;"></span>

                                            <!-- Date Label -->
                                            <strong>Date:</strong>
                                            <span>{{ \Carbon\Carbon::now()->format('d, F Y') }}</span>
                                        </td>
                                    </tr>
                                </table> --}}


                                <br>
                                <div class="form-group">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save"></i> Submit
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
    </div>
@endsection
