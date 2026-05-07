@extends('layouts.template')

@section('breadcrumb')
    @include('sweetalert::alert')
@endsection

@section('content')
    <div class="page-wrapper">
        <div class="content container-fluid">
            <div class="page-header mb-4">
                <div class="row align-items-center">
                    <div class="col">
                        <h6 class="page-title mb-2 text-muted">Requisition Approval Flow</h6>
                        <p class="mb-0 small text-muted">
                            Overview of how HR.01 requisitions move between Line Manager, Payroll Accountant, HEC members,
                            CFO, CEO and HR – depending on funds, budget and type of request.
                        </p>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-12">
                    <div class="card shadow-sm">
                        <div class="card-body">
                            <h6 class="fw-semibold mb-3">1. Replacement / Renewal initiated by Line Manager</h6>
                            <ul class="mb-4 ps-3">
                                <li><span class="fw-semibold">Budget approved & funds available:</span> Line Manager → Payroll
                                    Accountant → HEC (COO/CMS/Chief Accountant) → <span class="text-success">HR for
                                        processing</span>.
                                </li>
                                <li><span class="fw-semibold">Budget approved & funds NOT available:</span> Line Manager → Payroll
                                    Accountant → HEC → <span class="text-success">HR for processing</span> (skips CFO/CEO).
                                </li>
                                <li><span class="fw-semibold">Budget NOT approved & funds available, HEC has no objection
                                        (3c):</span> Line Manager → Payroll Accountant → HEC →
                                    <span class="fw-semibold">CFO</span> → <span class="fw-semibold">CEO</span> →
                                    <span class="text-success">HR for processing</span>.
                                </li>
                                <li><span class="fw-semibold">Budget NOT approved & funds available, HEC objects (3b):</span> Line
                                    Manager → Payroll Accountant → HEC →
                                    <span class="text-danger">Returned to Line Manager (Rejected)</span>.
                                </li>
                                <li><span class="fw-semibold">Budget NOT approved & funds NOT available, HEC has no objection
                                        (3c):</span> Line Manager → Payroll Accountant → HEC →
                                    <span class="fw-semibold">CFO</span> → <span class="fw-semibold">CEO</span> →
                                    <span class="text-success">HR for processing</span>.
                                </li>
                                <li><span class="fw-semibold">Budget NOT approved & funds NOT available, HEC objects (3b):</span> Line
                                    Manager → Payroll Accountant → HEC →
                                    <span class="text-danger">Returned to Line Manager (Rejected)</span>.
                                </li>
                            </ul>

                            <h6 class="fw-semibold mb-3">2. New Position initiated by Line Manager</h6>
                            <ul class="mb-4 ps-3">
                                <li><span class="fw-semibold">Budget approved & funds available, HEC has no objection:</span> Line Manager → Payroll
                                    Accountant → HEC →
                                    <span class="fw-semibold">CFO</span> → <span class="fw-semibold">CEO</span> →
                                    <span class="text-success">HR for processing</span>.
                                </li>
                                <li><span class="fw-semibold">Budget approved & funds available, HEC objects:</span> Line Manager → Payroll
                                    Accountant → HEC →
                                    <span class="text-danger">Returned to Line Manager (Rejected)</span>.
                                </li>
                                <li><span class="fw-semibold">Budget approved & funds NOT available:</span> Line Manager → Payroll
                                    Accountant → HEC → <span class="text-success">HR for processing</span> (skips CFO/CEO).
                                </li>
                                <li><span class="fw-semibold">Budget NOT approved & funds available, HEC has no objection
                                        (3c):</span> Line Manager → Payroll Accountant → HEC →
                                    <span class="fw-semibold">CFO</span> → <span class="fw-semibold">CEO</span> →
                                    <span class="text-success">HR for processing</span>.
                                </li>
                                <li><span class="fw-semibold">Budget NOT approved & funds available, HEC objects (3b):</span> Line
                                    Manager → Payroll Accountant → HEC →
                                    <span class="text-danger">Returned to Line Manager (Rejected)</span>.
                                </li>
                                <li><span class="fw-semibold">Budget NOT approved & funds NOT available, HEC has no objection
                                        (3c):</span> Line Manager → Payroll Accountant → HEC →
                                    <span class="fw-semibold">CFO</span> → <span class="fw-semibold">CEO</span> →
                                    <span class="text-success">HR for processing</span>.
                                </li>
                                <li><span class="fw-semibold">Budget NOT approved & funds NOT available, HEC objects (3b):</span> Line
                                    Manager → Payroll Accountant → HEC →
                                    <span class="text-danger">Returned to Line Manager (Rejected)</span>.
                                </li>
                            </ul>

                            <h6 class="fw-semibold mb-3">3. Replacement / Renewal initiated directly by HEC (COO/CMS)</h6>
                            <ul class="mb-4 ps-3">
                                <li><span class="fw-semibold">With budget & funds:</span> HEC (COO/CMS) →
                                    Payroll Accountant →
                                    <span class="text-success">HR for processing</span>.
                                </li>
                                <li><span class="fw-semibold">Without budget / no funds:</span> HEC (COO/CMS) →
                                    Payroll Accountant →
                                    <span class="fw-semibold">CFO</span> → <span class="fw-semibold">CEO</span> →
                                    <span class="text-success">HR for processing</span>.
                                </li>
                            </ul>

                            <h6 class="fw-semibold mb-3">4. New Position initiated directly by HEC (COO/CMS)</h6>
                            <ul class="mb-4 ps-3">
                                <li><span class="fw-semibold">Any funding condition:</span> HEC (COO/CMS) →
                                    Payroll Accountant →
                                    <span class="fw-semibold">CFO</span> → <span class="fw-semibold">CEO</span> →
                                    <span class="text-success">HR for processing</span>.
                                </li>
                            </ul>

                            <div class="alert alert-info mb-0 small">
                                <i class="fas fa-info-circle me-1"></i>
                                The actual routing is enforced inside the requisition workflow logic based on
                                <span class="fw-semibold">Budget Approved</span>, <span class="fw-semibold">Funding
                                    Available</span>, the
                                <span class="fw-semibold">HEC decision (3a/3b/3c)</span> and whether the background is a
                                <span class="fw-semibold">New Position</span> or a <span class="fw-semibold">Replacement /
                                    Renewal</span>.
                                <br><br>
                                <strong>Key Rule:</strong> If budget is approved but funds are NOT available, the requisition goes directly to HR (skips CFO/CEO). 
                                If budget is NOT approved but funds ARE available, and HEC approves with no objection, it goes to CFO → CEO → HR.
                                If HEC objects, the requisition is returned to the Line Manager as rejected.
                                This page is an overview to help HR and management understand the flow.
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection



