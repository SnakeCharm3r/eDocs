@extends('layouts.template')

@section('breadcrumb')
    @include('includes.loader')
    @include('sweetalert::alert')
@endsection

@section('content')
    <div class="page-wrapper">
        <div class="content container-fluid">
            <div class="page-header">
                <div class="row">
                    <div class="col-sm-12">
                        <div class="page-sub-header">
                            <h3 class="page-title">NHIF Registration</h3>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-12">
                    <div class="card shadow-sm">
                        <div class="card-body">
                            <form action="{{ route('nhif_registration.store') }}" method="POST"
                                enctype="multipart/form-data">
                                @csrf

                                {{-- Success and Error messages --}}
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

                                {{-- Terms and Conditions with Numbering and Description --}}
                                <div class="card mb-4 border-light">
                                    <div class="card-header bg-light">
                                        <h5 class="mb-0">Terms and Conditions</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="terms-list">
                                            <div class="term-item">
                                                <div class="term-number">1.</div>
                                                <div class="term-description">
                                                    <strong>Monthly Contribution:</strong> 3% from the employer and 3% from
                                                    the employee's basic salary.
                                                </div>
                                            </div>
                                            <div class="term-item">
                                                <div class="term-number">2.</div>
                                                <div class="term-description">
                                                    <strong>No Refund Policy:</strong> NHIF contributions are
                                                    non-refundable, even in cases of termination, resignation, retrenchment,
                                                    or retirement.
                                                </div>
                                            </div>
                                            <div class="term-item">
                                                <div class="term-number">3.</div>
                                                <div class="term-description">
                                                    <strong>Review of Services:</strong> The latest NHIF health service
                                                    terms are available at the HR office or the NHIF website for review
                                                    prior to registration.
                                                </div>
                                            </div>
                                            <div class="term-item">
                                                <div class="term-number">4.</div>
                                                <div class="term-description">
                                                    <strong>Effective Date:</strong> This policy has been in effect since
                                                    January 1, 2011. However, continuation of employer contributions depends
                                                    on fund availability.
                                                </div>
                                            </div>
                                            <div class="term-item">
                                                <div class="term-number">5.</div>
                                                <div class="term-description">
                                                    <strong>Funding Notice:</strong> If CCBRT is unable to finance NHIF
                                                    contributions, employees will be given a four-week notice via programme
                                                    information boards. Health services will cease one month after the
                                                    notice period ends.
                                                </div>
                                            </div>
                                            <div class="term-item">
                                                <div class="term-number">6.</div>
                                                <div class="term-description">
                                                    <strong>Application Process:</strong> Upon signing this form, employees
                                                    must complete the NHIF application form and ensure accurate data
                                                    submission. (Forms are available in HR.)
                                                </div>
                                            </div>
                                            <div class="term-item">
                                                <div class="term-number">7.</div>
                                                <div class="term-description">
                                                    <strong>Data Verification:</strong> Ensure all data is verified with an
                                                    HR staff member before submission.
                                                </div>
                                            </div>
                                            <div class="term-item">
                                                <div class="term-number">8.</div>
                                                <div class="term-description">
                                                    <strong>Processing Timeline:</strong> NHIF takes approximately 3–4 weeks
                                                    to process the application. User cards will be issued through the CCBRT
                                                    HR office.
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                {{-- Employee Declaration --}}
                                <h5 class="mb-3">Employee Declaration and Voluntary Registration</h5>

                                {{-- Acknowledgment Checkbox --}}
                                <div class="form-check mb-4">
                                    <input class="form-check-input" type="checkbox" id="agreeTerms" name="agreeTerms"
                                        required>
                                    <label class="form-check-label" for="agreeTerms">
                                        I agree to the terms and conditions and request CCBRT to register me and my eligible
                                        dependents for NHIF health insurance.
                                    </label>
                                    <div class="invalid-feedback">
                                        You must agree to the terms before submitting the registration.
                                    </div>
                                </div>

                                {{-- Submit and Back Buttons --}}
                                <div class="form-group">
                                    <button type="submit" class="btn btn-primary mr-3">
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

    {{-- Loader Script --}}
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            const nhifForm = document.querySelector('form[action="{{ route('nhif_registration.store') }}"]');
            if (nhifForm) {
                nhifForm.addEventListener("submit", function() {
                    document.getElementById("loader").style.display = "block";
                });
            }
        });
    </script>
@endsection

<style>
    .terms-list {
        display: flex;
        flex-direction: column;
        gap: 1rem;
    }

    .term-item {
        display: flex;
        align-items: flex-start;
    }


    .term-description {
        font-size: 14px;
        line-height: 1.6;
    }

    .form-check-label {
        font-size: 14px;
    }
</style>
