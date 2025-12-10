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
                            <h3 class="page-title">HESLB Loan Declaration Form</h3>
                        </div>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-body">
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

                            <div class="mb-4">
                                <h6>GUIDELINES</h6>
                                <ul class="custom-bullets">
                                    <li>CCBRT as Employer is required by Law to ensure that outstanding HESLB loans are
                                        recovered from its employees.</li>
                                    <li>CCBRT employees are required to declare whether they have an outstanding HESLB loan.
                                    </li>
                                    <li>CCBRT will also seek confirmation with HESLB on outstanding obligations from its
                                        employees.</li>
                                    <li>As per law, monthly deductions are done as per the percentage indicated in the HESLB
                                        Act through the payroll, and CCBRT will submit to HESLB on behalf of the employee.
                                    </li>
                                    <li>HESLB deductions get priority over all other loan deductions applicable to an
                                        employee.</li>
                                </ul>
                            </div>



                            <form action="{{ route('loan-declarations.store') }}" method="POST" novalidate>
                                @csrf
                                <!-- Form IV Index Number -->
                                <div class="row mb-4">
                                    <div class="col-md-6">
                                        <label for="form_iv_index" class="form-label fw-bold">Form IV Index No.<span
                                                class="text-danger">*</span></label>
                                        <input type="text" id="form_iv_index" name="form_iv_index" class="form-control"
                                            placeholder="S0001/0000/0000 or P0123" required
                                            pattern="^(S|P)\d{5,}(?:[/.]?\d{4}){0,2}$"
                                            aria-describedby="form_iv_index_help">
                                        <small id="form_iv_index_help" class="form-text text-muted">
                                            Enter your Form IV Index Number. Examples: S0123, S0709/0019/2001,
                                        </small>
                                        <div class="invalid-feedback">
                                            Please provide a valid Form IV Index Number.
                                        </div>
                                    </div>

                                    <!-- Loan Declaration -->
                                    <div class="col-md-6">
                                        <label for="has_loan" class="form-label fw-bold">Outstanding Loan Declaration<span
                                                class="text-danger">*</span></label>
                                        <select id="has_loan" name="has_loan" class="form-control" required
                                            aria-describedby="has_loan_help">
                                            <option value="" disabled selected>Select an option</option>
                                            <option value="Yes">Yes</option>
                                            <option value="No">No</option>
                                        </select>
                                        <small id="has_loan_help" class="form-text text-muted">
                                            Select "Yes" if you have an outstanding HESLB loan; otherwise, choose "No."
                                        </small>
                                        <div class="invalid-feedback">
                                            Please select an option.
                                        </div>
                                    </div>
                                </div>
                                <!-- Checkbox for guidelines acknowledgment -->
                                <div class="form-check mb-4">
                                    <input class="form-check-input" type="checkbox" id="acknowledge" name="acknowledge"
                                        required>
                                    <label class="form-check-label" for="acknowledge">
                                        I hereby have read and understood the above guidelines.
                                    </label>
                                    <div class="invalid-feedback">
                                        You must acknowledge that you have read and understood the guidelines.
                                    </div>
                                </div>
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

            <script>
                document.querySelector('form').addEventListener('submit', function(event) {
                    if (!this.checkValidity()) {
                        event.preventDefault();
                        event.stopPropagation();
                    }
                    this.classList.add('was-validated');
                });
            </script>
        </div>
    </div>
    </div>
@endsection

<style>
    .custom-bullets {
        list-style-type: disc;
        margin-left: 20px;
        padding-left: 0;
        font-family: Arial, sans-serif;
        font-size: 14px;
        color: #000;
    }

    .custom-bullets li {
        line-height: 1.5;
        margin-bottom: 8px;
    }
</style>
