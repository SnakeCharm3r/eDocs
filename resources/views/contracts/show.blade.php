@extends('layouts.template')
@section('breadcrumb')
    @include('sweetalert::alert')
@endsection

@section('content')
    <div class="page-wrapper">
        <div class="content container-fluid">
            <div class="row">
                <div class="col-md-12">
                    <div class="contract-container">
                        <!-- Logo at the Top Center -->
                        <div class="contract-logo">
                            <img src="{{ asset('assets/img/ccbrt.JPG') }}" alt="Logo" style="width: 105px; height: auto;">
                        </div>


                        <!-- Header -->
                        <header class="contract-header">
                            <div class="contract-header-position">
                                <h3 class="contract-header-subtitle">CONTRACT OF EMPLOYMENT</h3>
                                <h4 class="contract-header-subtitle">VERSION 2024</h4>
                                <h4 class="contract-header-subtitle">PART 1</h4>
                            </div>
                        </header>



                        <!-- Introduction -->
                        <section class="contract-section">
                            <p>
                                <strong>This ‘Contract of Employment’ made on {{ $contract->start_date ?? 'N/A' }} between
                                    CCBRT (Comprehensive Community Based Rehabilitation in Tanzania), hereinafter called the
                                    EMPLOYER of P. O. Box 23310, Dar es Salaam, Tanzania and {{ $contract->user->fname }}
                                    {{ $contract->user->mname }} {{ $contract->user->lname }}, of P.O. Box (NUMBER),
                                    (REGION), Tanzania hereinafter called the EMPLOYEE.</strong>
                            </p>
                            <p>
                                The Employer offers employment to the Employee and the Employee accepts this employment as
                                {{ optional($contract->jobTitle)->job_title ?? 'N/A' }} on the terms and conditions
                                specified hereinafter as well as Part 2 and signed by both the Employer and Employee.
                            </p>
                        </section>

                        <!-- 1. Name, Age and Gender of Employee -->
                        <section class="contract-section">
                            <h3 class="contract-section-header">1. Name, Age and Gender of Employee</h3>
                            <table class="contract-table">
                                <tbody>
                                    <tr>
                                        <td class="contract-label">Name</td>
                                        <td class="contract-value">{{ $contract->user->fname }} {{ $contract->user->mname }}
                                            {{ $contract->user->lname }}</td>
                                    </tr>
                                    <tr>
                                        <td class="contract-label">Gender</td>
                                        <td class="contract-value">{{ $contract->user->gender ?? 'N/A' }}</td>
                                    </tr>
                                    <tr>
                                        <td class="contract-label">Nationality</td>
                                        <td class="contract-value">{{ $contract->user->nationality ?? 'N/A' }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </section>

                        <!-- 2. Job Title, Place of Recruitment, Duty Station, Duration of Contract, Probation -->
                        <section class="contract-section">
                            <h3 class="contract-section-header">2. Job Title, Place of Recruitment, Duty Station, Duration
                                of Contract, Probation</h3>
                            <table class="contract-table">
                                <tbody>
                                    <tr>
                                        <td class="contract-label">Job Title</td>
                                        <td class="contract-value">{{ optional($contract->jobTitle)->job_title ?? 'N/A' }}
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="contract-label">Place of Recruitment and Work</td>
                                        <td class="contract-value">{{ $contract->place_of_recruitment ?? 'N/A' }}</td>
                                    </tr>
                                    <tr>
                                        <td class="contract-label">Duty Station</td>
                                        <td class="contract-value">{{ $contract->duty_station ?? 'N/A' }}</td>
                                    </tr>
                                    <tr>
                                        <td class="contract-label">Start Date</td>
                                        <td class="contract-value">{{ $contract->start_date ?? 'N/A' }}</td>
                                    </tr>
                                    <tr>
                                        <td class="contract-label">Duration of Contract</td>
                                        <td class="contract-value">{{ $contract->duration ?? 'N/A' }}</td>
                                    </tr>
                                    <tr>
                                        <td class="contract-label">Working Hours</td>
                                        <td class="contract-value">{{ $contract->working_hours ?? 'N/A' }}</td>
                                    </tr>
                                    <tr>
                                        <td class="contract-label">Probation Period</td>
                                        <td class="contract-value">{{ $contract->probation_period ?? 'N/A' }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </section>

                        <!-- 3. Conditional Precedents -->
                        <section class="contract-section">
                            <h3 class="contract-section-header">3. Conditional Precedents</h3>
                            <div class="contract-content">
                                <p>3.1. This position and contract is offered on condition of providing CCBRT with certified
                                    copies of all relevant documents as required at the start of the engagement. You are
                                    required to ensure your HR file at CCBRT is updated at any given time.</p>
                                <p>3.2. This position and contract is offered on condition of obtaining full registration
                                    and valid license from the professional body in Tanzania (if applicable for the job). If
                                    for any reason this registration is rejected or annual license is invalid, CCBRT shall
                                    be obliged to retract the contract.</p>
                                <p>3.3. The Employer is the Primary Employer for the Employee in Tanzania.</p>
                                <p>3.4. You will undergo medical check-up as per CCBRT regulations.</p>
                            </div>
                        </section>

                        <!-- 4. Duties, Regulations of Employment and Annual Leave -->
                        <section class="contract-section">
                            <h3 class="contract-section-header">4. Duties, Regulations of Employment and Annual Leave</h3>
                            <div class="contract-content">
                                <h4 class="contract-subsection-title">4.1 Duties</h4>
                                <p>Your job description is attached as addendum to the contract of employment. This Job
                                    description may be amended by CCBRT whenever it is necessary. At any given time, the
                                    latest job description signed by both parties is valid.</p>
                                <p>CCBRT reserves the right to change the work-allocation during the employment in relation
                                    to the needs of the business.</p>

                                <h4 class="contract-subsection-title">4.2 Regulations of Employment</h4>
                                <p>The employee shall serve the employer in accordance with regulations stated in Part 1 of
                                    this Contract, as well as in Part 2, and in accordance with any other management
                                    documents provided (management guide, finance rules) under separate cover, as an
                                    integral part of this Contract of Employment.</p>
                                <p>The employee is required to sign the employee code of conduct as well as other relevant
                                    policy documents upon the first day of employment.</p>

                                <h4 class="contract-subsection-title">4.3 Working Hours and Working Planning</h4>
                                <p>The max working hours per week is {{ $contract->working_hours ?? 'N/A' }}, whereby the
                                    expectation is that all daily tasks are completed.</p>
                                <p>The planning of working days/working hours and deliverables will be in consultation with
                                    the respective line manager. While the tasks are generally described in the job
                                    description, the specific workload will vary from day to day. This requires flexibility
                                    from the employee. On call duties apply.</p>

                                <h4 class="contract-subsection-title">4.4 Leave Days</h4>
                                <p>Upon completion of 11 months of service, an employee is entitled to annual leave of 28
                                    paid working days with full pay.</p>
                                <p>First leave during employment can only be taken after completion of the probation period.
                                    In case an employee works less in a calendar year, the accrued days which will be
                                    pro-rated towards the actual worked period.</p>

                                <h4 class="contract-subsection-title">4.5 Medical Insurance Cover</h4>
                                <p>Medical Insurance cover is compulsory to all CCBRT employees. NHIF is chosen as insurance
                                    and employees and their family are eligible for medical treatment Administered under
                                    National Health Insurance scheme where employee shall contribute
                                    {{ $contract->medical_insurance_employee ?? 'N/A' }} and employer
                                    {{ $contract->medical_insurance_employer ?? 'N/A' }}.</p>
                            </div>
                        </section>

                        <!-- 5. Remuneration -->
                        <section class="contract-section">
                            <h3 class="contract-section-header">5. Remuneration</h3>
                            <div class="contract-content">
                                <p>5.1. During this Agreement the employee’s Remuneration shall be:</p>
                                <table class="contract-table">
                                    <tbody>
                                        <tr>
                                            <td class="contract-label">Basic Pay</td>
                                            <td class="contract-value">Tshs. {{ $contract->basic_pay ?? 'N/A' }} per
                                                calendar month</td>
                                        </tr>
                                        <tr>
                                            <td class="contract-label">Total Gross Pay</td>
                                            <td class="contract-value">Tshs. {{ $contract->total_gross_pay ?? 'N/A' }} per
                                                calendar month</td>
                                        </tr>
                                    </tbody>
                                </table>
                                <p>5.2. On call duties (if applicable) are covered under a separate agreement as per the
                                    respective policy.</p>
                                <p>5.3. This compensation has been established based on the understanding that it includes
                                    all of the Employee’s cost as well as any tax obligation that may be imposed on the
                                    Employee.</p>
                                <p>5.4. CCBRT will be obliged to submit respective statutory deductions as per the
                                    regulations of Tanzania.</p>
                                <p>5.5. Payment verification will be done based upon biometric attendance registration
                                    system and/or the departmental registration book.</p>
                                <p>5.6. Every day 30 minutes are provided for lunch and not calculated for compensation.</p>
                                <p>5.7. Payments will be processed as per CCBRT’s payroll process and those regulations will
                                    apply.</p>
                            </div>
                        </section>

                        <!-- 6. Other Costs -->
                        <section class="contract-section">
                            <h3 class="contract-section-header">6. Other Costs</h3>
                            <div class="contract-content">
                                <p>6.1. The employee is responsible for all additional costs related to this employment such
                                    as local transport and meals.</p>
                            </div>
                        </section>

                        <!-- 7. Funeral Insurance -->
                        <section class="contract-section">
                            <h3 class="contract-section-header">7. Funeral Insurance</h3>
                            <div class="contract-content">
                                <p>7.1. Funeral Insurance is compulsory to all CCBRT staff. CCBRT will pay annual premium.
                                    Eligibility of claims will start
                                    {{ $contract->funeral_insurance_eligibility ?? 'N/A' }}.</p>
                            </div>
                        </section>

                        <!-- 8. Intellectual Property Rights -->
                        <section class="contract-section">
                            <h3 class="contract-section-header">8. Intellectual Property Rights</h3>
                            <div class="contract-content">
                                <p>8.1. Ownership of all copyright, patents, trademarks, design rights and other
                                    intellectual property rights in respect of any translations, data compilations,
                                    research, spreadsheets, graphs, reports, diagrams, designs, work products, software, or
                                    any other documents, developed in connection with this Agreement will exclusively vest
                                    in or remain with CCBRT, which shall have all proprietary rights therein,
                                    notwithstanding that you may be the author of the intellectual property.</p>
                                <p>8.2. You shall promptly disclose to CCBRT fully and completely any and all of the ideas,
                                    concepts, discoveries, inventions, improvements and developments, made or acquired by
                                    you in the discharge of your duties for CCBRT.</p>
                            </div>
                        </section>

                        <!-- 9. Behaviour and Ethics -->
                        <section class="contract-section">
                            <h3 class="contract-section-header">9. Behaviour and Ethics</h3>
                            <div class="contract-content">
                                <p>9.1. Every CCBRT employee is expected to act as per CCBRT values and code of conduct.</p>
                                <p>9.2. CCBRT will take action against misbehavior and misrepresentation of CCBRT.</p>
                                <p>9.3. If CCBRT is confronted with un-recoverable claims due to the negligence of an
                                    individual staff member, then CCBRT has the right to recover the loss from the
                                    respective staff member through deductions from salary payments.</p>
                            </div>
                        </section>

                        <!-- 10. Termination of Contract and Notice -->
                        <section class="contract-section">
                            <h3 class="contract-section-header">10. Termination of Contract and Notice</h3>
                            <div class="contract-content">
                                <p>10.1. This contract will automatically expire at the end of the defined period. There is
                                    no automatic renewal.</p>
                                <p>10.2. The employer shall have the right to terminate the employment of the employee prior
                                    to the indicated end date of the contract provided that the reason for the termination
                                    is related to the employees conduct, capacity or compatibility or is based on the
                                    operational requirements of the employer (see Article 14.11.) including any financial
                                    constraints that may necessitate the Employer to terminate this contract.</p>
                                <p>10.3. The employer shall give the employee one month notice. Provided that the employee
                                    will not receive a one month notice in the event that the termination by the employer
                                    was on disciplinary grounds and constitutes fair termination under the terms of the
                                    Labor Relations Act 2004.</p>
                                <p>10.4. The employee, likewise, may terminate the employment prior to the indicated end
                                    date of the contract by giving one month notice. If one months’ notice is not observed
                                    by the employee, then no pending payments will be honored.</p>
                                <p>10.5. This contract is not automatically renewed. The employer is not obliged to alert
                                    the employee on contract end date as it will automatically forfeit.</p>
                            </div>
                        </section>

                        <!-- 11. Jurisdiction and Copies of the Contract -->
                        <section class="contract-section">
                            <h3 class="contract-section-header">11. Jurisdiction and Copies of the Contract</h3>
                            <div class="contract-content">
                                <h4 class="contract-subsection-title">11.1. Jurisdiction</h4>
                                <p>The construction, performance and interpretation of this Agreement shall be governed by
                                    the laws of the United Republic of Tanzania in general, and the terms within the CCBRT
                                    Contract in particular.</p>

                                <h4 class="contract-subsection-title">11.2 Copies</h4>
                                <p>One English version of this contract will be kept on file at CCBRT headquarters and one
                                    copy is for the employee’s records.</p>
                                <p>11.3. Part 2 is an integral part of this Contract of Employment. They have been read and
                                    understood by all signatories to this contract. This contract will only become binding
                                    on CCBRT once signed by CCBRT and not before that.</p>
                            </div>
                        </section>

                        <!-- 12. Notices and Communication -->
                        <section class="contract-section">
                            <h3 class="contract-section-header">12. Notices and Communication</h3>
                            <div class="contract-content">
                                <p>Every notice demand or other communication under this Agreement shall be in writing and
                                    may be delivered personally or by letter, E-mail or dispatch as follows:</p>
                                <p>
                                    - if to the CCBRT to:<br>
                                    Comprehensive Community Based Rehabilitation in Tanzania<br>
                                    Attn: CEO<br>
                                    PO BOX 23310<br>
                                    Dar es Salaam
                                </p>
                            </div>
                        </section>

                        <!-- Signature Placeholder -->
                        <section class="contract-section">
                            <h3 class="contract-section-header">Signature</h3>
                            <div class="contract-content">
                                <p><strong>Signed by both parties on this {{ $contract->start_date ?? 'N/A' }} day of
                                        ................................ in the year
                                        ................................</strong></p>
                                <p>(NAME OF HEC MEMBER), (TITLE),</p>
                                <p>on behalf of the said Employer, CCBRT…………………………</p>
                                <p><strong>Signature of Employer</strong></p>
                                <p>in the presence of (witness):</p>
                                <p>Witness’s Name: …………………………………….. CCBRT Stamp</p>
                                <p>Signature: ……………………………………………</p>
                                <p>Postal Address: ………………………………………</p>
                                <hr>
                                <p>Voluntarily Signed by the said {{ $contract->user->fname }}
                                    {{ $contract->user->mname }} {{ $contract->user->lname }}
                                    .....................................</p>
                                <p><strong>Employee Signature</strong></p>
                                <p>Who is living in house number……………………………..on plot number………… in
                                    ………………………District………………………………Ward</p>
                                <p>The contract was signed in the presence of (Witness): …………………………………….........………………</p>
                                <p>Witness Name: ……………………………………… Signature of Witness</p>
                                <p>Postal Address: ……………………………………...</p>
                            </div>
                        </section>
                        <div class="back-button">
                            <a href="{{ route('requisitions.index') }}" class="btn btn-secondary">
                                <i class="fas fa-arrow-left"></i> Back
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <style>
        /* Container */
        .contract-container {
            font-family: Arial, sans-serif;
            font-size: 12pt;
            line-height: 1.4;
            color: #000;
            background: #fff;
            max-width: 1100px;
            margin: 20px auto;
            padding: 20px;
        }

        /* Logo */
        .contract-logo {
            text-align: center;
            margin-bottom: 20px;
        }

        .logo-img {
            max-width: 150px;
            height: auto;
        }

        /* Header */
        .contract-header {
            text-align: center;
            margin-bottom: 20px;
        }

        .contract-header-title {
            font-size: 18pt;
            font-weight: bold;
            margin-bottom: 10px;
        }

        .contract-header-subtitle {
            font-size: 16pt;
            font-weight: bold;
            margin-bottom: 15px;
        }

        .contract-header-position {
            font-size: 14pt;
            border: 1px solid #000;
            padding: 8px 25px;
            display: inline-block;
            margin-bottom: 15px;
        }

        /* Section */
        .contract-section {
            margin-bottom: 30px;
        }

        .contract-section-header {
            font-size: 14pt;
            font-weight: bold;
            margin-bottom: 15px;
        }

        /* Table */
        .contract-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }

        .contract-table td {
            border: 1px solid #000;
            padding: 10px;
            vertical-align: top;
        }

        .contract-label {
            width: 30%;
            font-weight: bold;
        }

        .contract-value {
            width: 70%;
        }

        /* Subsection */
        .contract-subsection-title {
            font-size: 12pt;
            font-weight: bold;
            margin: 10px 0;
        }

        .contract-content {
            padding: 10px 0;
        }

        .contract-content p {
            margin-bottom: 10px;
        }

        /* Print Styles */
        @media print {
            body {
                background-color: white;
                margin: 0;
                padding: 0;
            }

            .breadcrumb,
            .header,
            .sidebar,
            .footer,
            .text-right {
                display: none !important;
            }

            .page-wrapper,
            .content,
            .container-fluid,
            .row,
            .col-md-12 {
                padding: 0 !important;
                margin: 0 !important;
                width: 100% !important;
                max-width: none !important;
            }

            .contract-container {
                margin: 0;
                padding: 10px;
                max-width: none;
            }

            @page {
                size: A4;
                margin: 1.5cm;
            }
        }
    </style>
@endsection
