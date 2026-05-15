@extends('layouts.template')
@include('includes.loader')

@section('breadcrumb')
<div class="page-header">
    <div class="row">
        <div class="col-sm-12">
            <div class="page-sub-header d-flex justify-content-between align-items-center">
                <h3 class="page-title mb-0">
                    <i class="fas fa-id-card me-2" style="color:#007A33;"></i>HR Details Form
                </h3>
                <a href="{{ route('requestapprove.index') }}" class="btn btn-sm btn-outline-secondary">
                    <i class="fas fa-arrow-left me-1"></i> Back
                </a>
            </div>
        </div>
    </div>
</div>
@endsection

@section('content')
<style>
    /* ── Page shell ─────────────────────────────────────────── */
    .hrform-shell {
        background: #dde1e7;
        min-height: calc(100vh - 60px);
        padding: 1.5rem;
        display: flex;
        justify-content: center;
        align-items: flex-start;
    }

    /* ── A4 document ────────────────────────────────────────── */
    .hrform-a4 {
        background: #fff;
        width: 100%;
        max-width: 860px;
        flex-shrink: 0;
        box-shadow: 0 2px 20px rgba(0,0,0,.18);
        border-radius: 3px;
        padding: 2.2rem 2.4rem;
    }

    /* ── Form header ────────────────────────────────────────── */
    .hrf-doc-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        border-bottom: 3px solid #007A33;
        padding-bottom: .85rem;
        margin-bottom: 1.2rem;
    }
    .hrf-doc-header .doc-logo {
        height: 56px; width: auto;
    }
    .hrf-doc-header .doc-title {
        text-align: center;
        flex: 1;
        padding: 0 1rem;
    }
    .hrf-doc-header .doc-title .main-title {
        font-size: 1.25rem;
        font-weight: 700;
        color: #007A33;
        letter-spacing: .04em;
        text-transform: uppercase;
    }
    .hrf-doc-header .doc-title .sub-title {
        font-size: .78rem;
        color: #6c757d;
        margin-top: .1rem;
    }
    .hrf-doc-header .doc-ref {
        font-size: .75rem;
        color: #6c757d;
        text-align: right;
        white-space: nowrap;
    }
    .hrf-doc-header .doc-ref strong { color: #495057; }

    /* ── Employee banner (photo + core details) ─────────────── */
    .hrf-emp-banner {
        display: flex;
        gap: 1.2rem;
        align-items: stretch;
        border: 1px solid #dee2e6;
        border-radius: 4px;
        padding: .85rem 1rem;
        margin-bottom: 1.4rem;
        background: #fafafa;
    }
    .hrf-emp-banner .emp-photo {
        width: 100px; height: 120px;
        object-fit: cover; object-position: center;
        border: 1px solid #ced4da;
        border-radius: 3px;
        flex-shrink: 0;
    }
    .hrf-emp-banner .emp-photo-placeholder {
        width: 100px; height: 120px;
        border: 1px dashed #ced4da;
        border-radius: 3px;
        background: #f8f9fa;
        display: flex; align-items: center; justify-content: center;
        color: #adb5bd; font-size: 2rem;
        flex-shrink: 0;
    }
    .hrf-emp-banner .emp-details { flex: 1; display: grid; grid-template-columns: 1fr 1fr 1fr; gap: .5rem .75rem; }
    .hrf-emp-banner .emp-field .ef-label { font-size: .68rem; font-weight: 700; color: #868e96; text-transform: uppercase; letter-spacing: .04em; }
    .hrf-emp-banner .emp-field .ef-value { font-size: .88rem; color: #212529; font-weight: 500; border-bottom: 1px solid #e9ecef; padding-bottom: .2rem; }
    .hrf-emp-banner .emp-field.span2 { grid-column: span 2; }
    .hrf-emp-banner .emp-field.span3 { grid-column: span 3; }

    /* ── Status badge line ──────────────────────────────────── */
    .hrf-status-line {
        display: flex; align-items: center; gap: .6rem;
        font-size: .82rem; margin-bottom: 1.2rem; padding: .5rem .75rem;
        border-radius: 4px;
    }
    .hrf-status-line.approved { background: #d4edda; color: #155724; border-left: 4px solid #28a745; }
    .hrf-status-line.rejected { background: #f8d7da; color: #721c24; border-left: 4px solid #dc3545; }
    .hrf-status-line.pending  { background: #fff3cd; color: #856404; border-left: 4px solid #ffc107; }

    /* ── Section heading ────────────────────────────────────── */
    .hrf-section-heading {
        display: flex; align-items: center; gap: .5rem;
        font-size: .82rem; font-weight: 700;
        text-transform: uppercase; letter-spacing: .06em;
        color: #fff;
        background: #007A33;
        padding: .38rem .85rem;
        margin: 1.4rem -2.4rem .85rem;
        padding-left: 2.4rem;
    }
    .hrf-section-heading i { opacity: .85; }

    /* ── Info grid (personal details) ──────────────────────── */
    .info-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 0; }
    .info-cell {
        padding: .45rem .7rem;
        border: 1px solid #e9ecef;
        margin: -1px 0 0 -1px;
    }
    .info-cell .ic-label { font-size: .67rem; font-weight: 700; color: #868e96; text-transform: uppercase; letter-spacing: .04em; }
    .info-cell .ic-value { font-size: .87rem; color: #212529; }
    .info-cell .ic-value.na { color: #ced4da; font-style: italic; }
    @media (max-width: 600px) { .info-grid { grid-template-columns: 1fr 1fr; } .hrf-emp-banner .emp-details { grid-template-columns: 1fr 1fr; } }

    /* ── Tables ─────────────────────────────────────────────── */
    .hrf-tbl { width: 100%; border-collapse: collapse; font-size: .85rem; }
    .hrf-tbl th { background: #f1f3f5; font-size: .75rem; font-weight: 700; text-transform: uppercase; letter-spacing: .04em; color: #495057; padding: .5rem .7rem; border: 1px solid #dee2e6; }
    .hrf-tbl td { padding: .48rem .7rem; border: 1px solid #dee2e6; vertical-align: middle; }
    .hrf-tbl tbody tr:nth-child(even) td { background: #fafafa; }
    .num-col { text-align: center; width: 40px; color: #868e96; }

    /* ── Conflict of interest answer styling ─────────────────── */
    .ans-yes { color: #dc3545; font-weight: 600; }
    .ans-no  { color: #007A33; font-weight: 600; }

    /* ── Declaration ─────────────────────────────────────────── */
    .decl-text {
        font-size: .82rem; color: #495057; line-height: 1.7;
        border: 1px solid #e9ecef; border-radius: 3px;
        background: #fafafa; padding: .75rem 1rem; margin-bottom: 1rem;
    }
    .sig-row { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: .75rem; margin-top: .75rem; }
    .sig-cell { border: 1px solid #dee2e6; padding: .5rem .75rem; border-radius: 3px; }
    .sig-cell .sc-label { font-size: .67rem; font-weight: 700; color: #868e96; text-transform: uppercase; letter-spacing: .04em; }
    .sig-cell .sc-value { font-size: .88rem; color: #212529; margin-top: .2rem; }
    .sig-img { max-width: 130px; height: auto; display: block; border: 1px solid #dee2e6; padding: 3px; background: #fff; }

    /* ── Attachments ─────────────────────────────────────────── */
    .att-chip {
        display: inline-flex; align-items: center; gap: .35rem;
        border: 1px solid #c8e6c9; background: #f1f8e9; color: #33691e;
        border-radius: 3px; padding: .28rem .65rem; font-size: .8rem; font-weight: 500;
        text-decoration: none;
    }
    .att-chip:hover { background: #dcedc8; color: #1b5e20; }

    /* ── Professional reg box ────────────────────────────────── */
    .verify-box { border: 1px solid #ffe082; border-radius: 4px; background: #fffde7; padding: 1rem; margin-top: .75rem; }

    /* ── Action bar (in-flow, inside A4 card) ───────────────── */
    .hrf-action-bar {
        display: flex; align-items: center; justify-content: space-between;
        border-top: 2px solid #e9ecef;
        padding: .85rem 0 0;
        margin-top: 1.5rem;
    }
    .hrf-action-bar .ai-info { font-size: .82rem; color: #6c757d; }

    /* ── Print ───────────────────────────────────────────────── */
    @media print {
        .no-print, .hrf-action-bar { display: none !important; }
        .hrform-shell { background: #fff !important; padding: 0 !important; }
        .hrform-a4 { box-shadow: none !important; max-width: 100% !important; padding: 0 !important; margin: 0 !important; }
        .hrf-section-heading { margin-left: 0 !important; margin-right: 0 !important; padding-left: .85rem !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        .content-wrapper, .page-wrapper { margin: 0 !important; padding: 0 !important; }
        @page { size: A4 portrait; margin: 12mm; }
        .hrf-card { page-break-inside: avoid; }
    }
</style>

{{-- ══ Page shell ══════════════════════════════════════════════════════════ --}}
<div class="hrform-shell">

    {{-- ══ A4 document card ══════════════════════════════════════════════ --}}
    <div class="hrform-a4">

        {{-- ── Document header (logo + title + ref) ── --}}
        <div class="hrf-doc-header">
            <img src="{{ asset('assets/img/ccbrt.jpg') }}" alt="CCBRT Logo" class="doc-logo">
            <div class="doc-title">
                <div class="main-title">HR Details Form</div>
                <div class="sub-title">Staff Detail &amp; Declaration Form</div>
            </div>
            <div class="doc-ref">
                <strong>HR.8 v2022</strong><br>
                Submitted: {{ \Carbon\Carbon::parse($user->created_at)->format('d M Y') }}<br>
                @if ($isApproved)
                    {{-- Status shown in banner below --}}
                @elseif ($isRejected)
                    <span style="color:#dc3545;font-weight:700;"><i class="fas fa-times-circle"></i> Rejected</span>
                @else
                    <span style="color:#856404;font-weight:700;"><i class="fas fa-clock"></i> Pending</span>
                @endif
            </div>
        </div>

        {{-- ── Status banner ── --}}
        @if ($isApproved)
            <div class="hrf-status-line approved">
                <i class="fas fa-check-circle"></i>
                <div>
                    <strong>Form Approved.</strong>
                    @if ($approver)
                        Verified by <strong>{{ $approver->fname }} {{ $approver->lname }}</strong>
                        @if ($approvalDate) on {{ \Carbon\Carbon::parse($approvalDate)->format('d F Y, h:i A') }}. @endif
                    @endif
                </div>
            </div>
        @elseif ($isRejected)
            @php
                $rejectedHistory = \App\Models\WorkFlowHistory::where('work_flow_id', $workflow->id)
                    ->where('attended_by', Auth::id())->where('status', 2)->first();
            @endphp
            <div class="hrf-status-line rejected">
                <i class="fas fa-times-circle"></i>
                <div>
                    <strong>Form Rejected.</strong>
                    @if ($rejectedHistory && $rejectedHistory->rejection_reason)
                        Reason: {{ $rejectedHistory->rejection_reason }}
                    @endif
                </div>
            </div>
        @else
            <div class="hrf-status-line pending">
                <i class="fas fa-clock"></i>
                <strong>Awaiting HR review.</strong>
            </div>
        @endif

        {{-- ── Employee identity banner (photo + key fields) ── --}}
        <div class="hrf-emp-banner">
            @if ($user->profile_picture)
                <img src="{{ asset('storage/' . $user->profile_picture) }}" alt="Photo" class="emp-photo">
            @else
                <div class="emp-photo-placeholder"><i class="fas fa-user"></i></div>
            @endif
            <div class="emp-details">
                <div class="emp-field span3">
                    <div class="ef-label">Full Name</div>
                    <div class="ef-value fw-bold" style="font-size:1rem;">{{ strtoupper($user->fname . ' ' . $user->mname . ' ' . $user->lname) }}</div>
                </div>
                <div class="emp-field">
                    <div class="ef-label">Job Title</div>
                    <div class="ef-value">{{ $user->jobTitle->job_title ?? 'N/A' }}</div>
                </div>
                <div class="emp-field">
                    <div class="ef-label">Department</div>
                    <div class="ef-value">{{ $user->department->dept_name ?? 'N/A' }}</div>
                </div>
                <div class="emp-field">
                    <div class="ef-label">Employment Type</div>
                    <div class="ef-value">{{ $user->employmentType->employment_type ?? 'N/A' }}</div>
                </div>
                <div class="emp-field span2">
                    <div class="ef-label">Email</div>
                    <div class="ef-value">{{ $user->email ?? 'N/A' }}</div>
                </div>
                <div class="emp-field">
                    <div class="ef-label">Mobile</div>
                    <div class="ef-value">{{ $user->mobile ?? 'N/A' }}</div>
                </div>
            </div>
        </div>

        {{-- ════════════════════════════════════════════════════════════════
             SECTION 1 — Personal Information
        ════════════════════════════════════════════════════════════════ --}}
        <div class="hrf-section-heading"><i class="fas fa-user"></i>&nbsp; Personal Information</div>
        @php
            $pInfo = [
                ['First Name',         $user->fname],
                ['Middle Name',        $user->mname],
                ['Surname',            $user->lname],
                ['Gender',             $user->gender],
                ['Date of Birth',      $user->DOB],
                ['Place of Birth',     $user->place_of_birth],
                ['Marital Status',     $user->marital_status],
                ['Nationality',        $user->nationality],
                ['Religion',           $user->religion],
                ['Domicile',           $user->domicile],
                ['Passport No',        $user->passport_no],
                ['TIN Number',         $user->tin_no],
                ['NIDA (NIN)',          $user->NIN],
                ['NSSF No',            $user->nssf_no],
                ['Prof. Reg. No',      $user->professional_reg_number],
                ['Region',             $user->region],
                ['District',           $user->district],
                ['Street / Address',   $user->street],
                ['House No',           $user->house_no],
                ['Popular Landmark',   $user->popular_landmark],
            ];
        @endphp
        <div class="info-grid">
            @foreach ($pInfo as [$lbl, $val])
                <div class="info-cell">
                    <div class="ic-label">{{ $lbl }}</div>
                    <div class="ic-value {{ empty($val) ? 'na' : '' }}">{{ $val ?: 'N/A' }}</div>
                </div>
            @endforeach
        </div>

        {{-- ════════════════════════════════════════════════════════════════
             SECTION 2 — Family Data
        ════════════════════════════════════════════════════════════════ --}}
        <div class="hrf-section-heading"><i class="fas fa-users"></i>&nbsp; Family Data</div>
        <table class="hrf-tbl">
            <thead>
                <tr>
                    <th class="num-col">#</th>
                    <th>Full Name</th>
                    <th>Relationship</th>
                    <th>Occupation</th>
                    <th>Phone Number</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($familyDetails as $i => $f)
                    <tr>
                        <td class="num-col">{{ $i + 1 }}</td>
                        <td>{{ $f->full_name }}</td>
                        <td>{{ $f->relationship }}</td>
                        <td>{{ $f->occupation ?? '—' }}</td>
                        <td>{{ $f->phone_number }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-center text-muted py-2" style="font-size:.82rem;">No family details recorded.</td></tr>
                @endforelse
            </tbody>
        </table>

        {{-- ════════════════════════════════════════════════════════════════
             SECTION 3 — Next of Kin / Emergency Contact
        ════════════════════════════════════════════════════════════════ --}}
        <div class="hrf-section-heading"><i class="fas fa-phone-alt"></i>&nbsp; Next of Kin &amp; Emergency Contact</div>
        <table class="hrf-tbl">
            <thead>
                <tr>
                    <th>Full Name</th>
                    <th>Relationship</th>
                    <th>Phone Number</th>
                </tr>
            </thead>
            <tbody>
                @php $hasNok = false; @endphp
                @foreach ($familyDetails as $f)
                    @if ($f->next_of_kin)
                        @php $hasNok = true; @endphp
                        <tr>
                            <td>{{ $f->full_name }}</td>
                            <td>{{ $f->relationship }}</td>
                            <td>{{ $f->phone_number }}</td>
                        </tr>
                    @endif
                @endforeach
                @if (!$hasNok)
                    <tr><td colspan="3" class="text-center text-muted py-2" style="font-size:.82rem;">No next of kin recorded.</td></tr>
                @endif
            </tbody>
        </table>

        {{-- ════════════════════════════════════════════════════════════════
             SECTION 4 — Health Data
        ════════════════════════════════════════════════════════════════ --}}
        <div class="hrf-section-heading"><i class="fas fa-heartbeat"></i>&nbsp; Health Data</div>
        <div class="info-grid">
            @php
                $hInfo = [
                    ['Physical Disability',    $healthDetails->physical_disability ?? null, 'None'],
                    ['Blood Group',             $healthDetails->blood_group ?? null, 'Unknown'],
                    ['Insurance Name',          $healthDetails->insur_name ?? null],
                    ['Insurance Number',        $healthDetails->insur_no ?? null],
                    ['Major Illness / Surgery', $healthDetails->illness_history ?? null, 'None'],
                    ['Allergies',               $healthDetails->allergies ?? null, 'None'],
                ];
            @endphp
            @foreach ($hInfo as $hf)
                <div class="info-cell">
                    <div class="ic-label">{{ $hf[0] }}</div>
                    <div class="ic-value {{ empty($hf[1]) ? 'na' : '' }}">{{ $hf[1] ?: ($hf[2] ?? 'N/A') }}</div>
                </div>
            @endforeach
        </div>

        {{-- ════════════════════════════════════════════════════════════════
             SECTION 5 — Knowledge of Languages
        ════════════════════════════════════════════════════════════════ --}}
        <div class="hrf-section-heading"><i class="fas fa-language"></i>&nbsp; Knowledge of Languages</div>
        <table class="hrf-tbl">
            <thead>
                <tr>
                    <th class="num-col">#</th>
                    <th>Language</th>
                    <th>Speaking</th>
                    <th>Reading</th>
                    <th>Writing</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($languageKnowledge as $lang)
                    <tr>
                        <td class="num-col">{{ $loop->iteration }}</td>
                        <td>{{ $lang->language }}</td>
                        <td>{{ $lang->speaking }}</td>
                        <td>{{ $lang->reading }}</td>
                        <td>{{ $lang->writing }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-center text-muted py-2" style="font-size:.82rem;">No languages recorded.</td></tr>
                @endforelse
            </tbody>
        </table>

        {{-- ════════════════════════════════════════════════════════════════
             SECTION 6 — CCBRT Relationship (conditional)
        ════════════════════════════════════════════════════════════════ --}}
        @if ($relations->isNotEmpty())
            <div class="hrf-section-heading"><i class="fas fa-link"></i>&nbsp; CCBRT Relationship</div>
            <table class="hrf-tbl">
                <thead>
                    <tr>
                        <th class="num-col">#</th>
                        <th>Name</th>
                        <th>Relation</th>
                        <th>Department</th>
                        <th>Position</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($relations as $rel)
                        <tr>
                            <td class="num-col">{{ $loop->iteration }}</td>
                            <td>{{ $rel->names }}</td>
                            <td>{{ $rel->relation }}</td>
                            <td>{{ $rel->dept_name ?? '—' }}</td>
                            <td>{{ $rel->position }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif

        {{-- ════════════════════════════════════════════════════════════════
             SECTION 7 — Disclosure of Conflict of Interest
        ════════════════════════════════════════════════════════════════ --}}
        <div class="hrf-section-heading"><i class="fas fa-balance-scale"></i>&nbsp; Disclosure of Conflict of Interest</div>
        <table class="hrf-tbl">
            <thead>
                <tr>
                    <th class="num-col">#</th>
                    <th>Question</th>
                    <th style="width:75px; text-align:center;">Answer</th>
                </tr>
            </thead>
            <tbody>
                @php
                    $coiRows = [
                        [1, 'Are you or a member of your immediate family an officer, director, trustee, partner, employee, or regularly retained consultant of any company that presently has business dealings with CCBRT?',
                            $user->conflict_officer_role, $user->conflict_officer_role === 'yes' ? 'Company name, position held, and nature of the business: ' . ($user->officer_details ?? '') : null],
                        [2, 'Do you or a member of your family have a material financial interest in a company with business dealings with CCBRT?',
                            $user->financial_interest, $user->financial_interest === 'Yes' ? 'Details: ' . ($user->financial_details ?? '') : null],
                        [3, 'Do you or a member of your family have any other interests that might create a conflict of interest?',
                            $user->other_interests, $user->other_interests === 'Yes' ? 'Details: ' . ($user->interest_details ?? '') : null],
                        [4, 'Please declare CCBRT as your primary employer:',
                            $user->primary_employer_ccbrt, $user->primary_employer_ccbrt === 'No' ? 'Explanation: ' . ($user->primary_employer_details ?? '') : null],
                        [5, 'Have you ever been involved in any court proceedings?',
                            $user->court_proceedings, $user->court_proceedings === 'Yes' ? 'Details: ' . ($user->court_details ?? '') : null],
                    ];
                @endphp
                @foreach ($coiRows as [$num, $question, $answer, $detail])
                    <tr>
                        <td class="num-col">{{ $num }}</td>
                        <td style="font-size:.83rem;">{{ $question }}</td>
                        <td class="text-center {{ in_array(strtolower($answer ?? ''), ['yes', 'no']) ? (strtolower($answer) === 'yes' ? 'ans-yes' : 'ans-no') : '' }}">
                            {{ ucfirst($answer ?? 'N/A') }}
                        </td>
                    </tr>
                    @if ($detail)
                        <tr style="background:#fffbe6;">
                            <td></td>
                            <td colspan="2" style="font-size:.8rem; color:#555; font-style:italic;">
                                <span style="text-decoration:underline;">{{ $detail }}</span>
                            </td>
                        </tr>
                    @endif
                @endforeach
            </tbody>
        </table>

        {{-- ════════════════════════════════════════════════════════════════
             SECTION 8 — Attachments
        ════════════════════════════════════════════════════════════════ --}}
        @php
            $docs = array_filter([
                'NIDA'                => $user->nida ?? null,
                'Driving License'     => $user->driving_license ?? null,
                'Transport ID'        => $user->transport_id ?? null,
                'Voting ID'           => $user->voting_id ?? null,
                'Marriage Certificate'=> $user->marriage_certificate ?? null,
                'Divorce Certificate' => $user->divorced_certificate ?? $user->divorce_certificate ?? null,
                'Employee CV'         => $user->employee_cv ?? null,
            ]);
        @endphp
        @if (count($docs))
            <div class="hrf-section-heading"><i class="fas fa-paperclip"></i>&nbsp; Attachments</div>
            <div class="d-flex flex-wrap gap-2 pb-1">
                @foreach ($docs as $label => $path)
                    <a href="{{ asset('storage/' . $path) }}" target="_blank" class="att-chip">
                        <i class="fas fa-file-alt"></i> {{ $label }}
                    </a>
                @endforeach
            </div>
        @endif

        {{-- ════════════════════════════════════════════════════════════════
             SECTION 9 — Declaration
        ════════════════════════════════════════════════════════════════ --}}
        <div class="hrf-section-heading"><i class="fas fa-file-signature"></i>&nbsp; Declaration</div>
        <p class="decl-text">
            I declare that the information provided in this form is true and correct to the best of my knowledge,
            and acknowledge that I will be liable to action against me as per the rules of the organization if,
            at any point of time during my employment with the organization, any of the above details are found to
            be untrue. I also undertake to periodically inform the organization and update the HR department in case
            of any relevant changes in the details mentioned above or on other relevant matters (e.g., completed
            courses, training).
        </p>
        <div class="sig-row">
            <div class="sig-cell">
                <div class="sc-label">Name of Employee</div>
                <div class="sc-value fw-bold" style="font-size:.95rem; color:#212529;">
                    {{ trim($user->fname . ' ' . $user->mname . ' ' . $user->lname) }}
                </div>
                @if ($user->jobTitle)
                    <div style="font-size:.75rem; color:#007A33; margin-top:.2rem; font-style:italic;">
                        {{ $user->jobTitle->name ?? '' }}
                    </div>
                @endif
                @if ($user->department)
                    <div style="font-size:.72rem; color:#868e96; margin-top:.1rem;">
                        {{ $user->department->dept_name ?? '' }}
                    </div>
                @endif
            </div>
            <div class="sig-cell">
                <div class="sc-label">Signature</div>
                <div class="sc-value mt-1">
                    @if ($user->signature)
                        <img src="data:image/png;base64,{{ $user->signature }}" alt="Signature" class="sig-img">
                    @else
                        <span class="text-muted fst-italic" style="font-size:.8rem;">No signature on file</span>
                    @endif
                </div>
            </div>
            <div class="sig-cell">
                <div class="sc-label">Date</div>
                <div class="sc-value">
                    {{ $workflow && $workflow->created_at ? \Carbon\Carbon::parse($workflow->created_at)->format('d F Y') : \Carbon\Carbon::now()->format('d F Y') }}
                </div>
            </div>
        </div>

        {{-- Approver signature row (if approved) --}}
        @if ($isApproved && $approver)
            <div class="sig-row mt-2">
                <div class="sig-cell" style="border-color:#c3e6cb;">
                    <div class="sc-label" style="color:#007A33;">Approved By</div>
                    <div class="sc-value fw-bold" style="font-size:.95rem; color:#212529;">
                        {{ trim($approver->fname . ' ' . $approver->mname . ' ' . $approver->lname) }}
                    </div>
                    @if ($approver->jobTitle)
                        <div style="font-size:.75rem; color:#007A33; margin-top:.2rem; font-style:italic;">
                            {{ $approver->jobTitle->name ?? '' }}
                        </div>
                    @elseif ($approver->roles->isNotEmpty())
                        <div style="font-size:.75rem; color:#007A33; margin-top:.2rem; font-style:italic;">
                            {{ $approver->roles->first()->name }}
                        </div>
                    @endif
                </div>
                <div class="sig-cell" style="border-color:#c3e6cb;">
                    <div class="sc-label" style="color:#007A33;">HR Signature</div>
                    <div class="sc-value mt-1">
                        @if ($approver->signature)
                            <img src="data:image/png;base64,{{ $approver->signature }}" alt="HR Signature" class="sig-img">
                        @else
                            <span class="text-muted fst-italic" style="font-size:.8rem;">No signature on file</span>
                        @endif
                    </div>
                </div>
                <div class="sig-cell" style="border-color:#c3e6cb;">
                    <div class="sc-label" style="color:#007A33;">Date Approved</div>
                    <div class="sc-value">{{ $approvalDate ? \Carbon\Carbon::parse($approvalDate)->format('d F Y') : 'N/A' }}</div>
                </div>
            </div>
        @endif

        {{-- ════════════════════════════════════════════════════════════════
             Professional Registration Verification (clinical, pending only)
        ════════════════════════════════════════════════════════════════ --}}
        @if ($isClinicalDepartment && $user->professional_reg_number && !$isApproved && !$isRejected)
            <div class="hrf-section-heading" style="background:#856404;"><i class="fas fa-certificate"></i>&nbsp; Professional Registration Verification <small style="font-weight:400;opacity:.8;">(Clinical — Required)</small></div>

            @if ($currentHrHistory && $currentHrHistory->professional_reg_verified)
                <div class="alert alert-success d-flex align-items-center gap-2 py-2" style="font-size:.85rem;">
                    <i class="fas fa-check-circle fs-5"></i>
                    <div><strong>Verified.</strong> Professional registration confirmed.
                        @if ($currentHrHistory->license_provider) &nbsp;Provider: {{ $currentHrHistory->license_provider }}. @endif
                        @if ($currentHrHistory->license_valid_until) &nbsp;Valid until {{ \Carbon\Carbon::parse($currentHrHistory->license_valid_until)->format('d F Y') }}. @endif
                    </div>
                </div>
            @else
                @php
                    $regNumber = $user->professional_reg_number ?? '';
                    $licenseProviderName = '';
                    if (preg_match('/^([A-Z]+):\s*(.+)$/', $regNumber, $m)) {
                        $providers = ['MCT'=>'Medical Council of Tanzania','TNMC'=>'Tanzania Nursing and Midwifery Council',
                                      'TPB'=>'Tanzania Pharmacy Board','TPC'=>'Tanzania Physiotherapy Council','TMDC'=>'Tanzania Medical and Dental Council'];
                        $licenseProviderName = $providers[$m[1]] ?? $m[1];
                    }
                @endphp
                <div class="verify-box">
                    <div class="mb-2" style="font-size:.83rem;color:#6c757d;">Registration No: <strong style="color:#007A33;">{{ $user->professional_reg_number }}</strong></div>
                    <div class="row g-2 mb-2">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold mb-1">License Valid Until <span class="text-danger">*</span></label>
                            <input type="date" class="form-control form-control-sm" id="license_valid_until"
                                value="{{ $currentHrHistory->license_valid_until ?? '' }}" min="{{ date('Y-m-d') }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold mb-1">License Provider</label>
                            <input type="text" class="form-control form-control-sm" id="license_provider"
                                value="{{ $currentHrHistory->license_provider ?? $licenseProviderName }}"
                                readonly style="background:#f8f9fa;">
                        </div>
                    </div>
                    <div class="form-check mb-2">
                        <input class="form-check-input" type="checkbox" id="professional_reg_verified"
                            {{ $currentHrHistory && $currentHrHistory->professional_reg_verified ? 'checked' : '' }}>
                        <label class="form-check-label small" for="professional_reg_verified">
                            <strong>I confirm the registration is active and the employee is working under a licensed provider.</strong>
                        </label>
                    </div>
                    <button type="button" class="btn btn-sm btn-success" id="saveVerificationBtn">
                        <i class="fas fa-save me-1"></i> Save Verification
                    </button>
                    <span id="verificationStatus" class="ms-2"></span>
                </div>
            @endif
        @elseif ($isClinicalDepartment && $user->professional_reg_number && ($isApproved || $isRejected) && $currentHrHistory && $currentHrHistory->professional_reg_verified)
            <div class="mt-2 mb-1">
                <span style="font-size:.8rem;background:#d4edda;color:#155724;border-radius:3px;padding:.3rem .7rem;">
                    <i class="fas fa-certificate me-1"></i><strong>Professional Reg. Verified:</strong>
                    {{ $user->professional_reg_number }}
                    @if ($currentHrHistory->license_valid_until) — Valid until {{ \Carbon\Carbon::parse($currentHrHistory->license_valid_until)->format('d F Y') }} @endif
                </span>
            </div>
        @endif

        {{-- ── Action buttons (inside A4 card) ── --}}
        <div class="hrf-action-bar no-print">
            <div class="ai-info">
                @if (auth()->user()->can('view signatures') || auth()->user()->can('approve signatures'))
                    <strong>{{ $user->fname }} {{ $user->lname }}</strong>
                    &nbsp;&mdash;&nbsp;{{ $user->jobTitle->name ?? ($user->jobTitle->job_title ?? 'N/A') }}
                    @if ($isApproved)
                        <span class="badge bg-success ms-1" style="font-size:.72rem;">Approved</span>
                    @elseif ($isRejected)
                        <span class="badge bg-danger ms-1" style="font-size:.72rem;">Rejected</span>
                    @else
                        <span class="badge ms-1" style="font-size:.72rem;background:#007A33;">Pending</span>
                    @endif
                @endif
            </div>
            <div class="d-flex gap-2">
                <button class="btn btn-sm btn-outline-secondary" id="backButton">
                    <i class="fas fa-arrow-left me-1"></i> Back
                </button>
                @if ($isApproved)
                    <button class="btn btn-sm btn-outline-primary" onclick="window.print()">
                        <i class="fas fa-print me-1"></i> Print
                    </button>
                @endif
                @php
                    $hasPendingAction = !$isApproved && !$isRejected && \App\Models\WorkFlowHistory::where('work_flow_id', $workflow->id)
                        ->where('attended_by', Auth::id())
                        ->where('status', 0)
                        ->exists();
                @endphp
                @if ($hasPendingAction)
                    <button class="btn btn-sm btn-success" id="approveButton">
                        <i class="fas fa-check me-1"></i> Approve
                    </button>
                    <button class="btn btn-sm btn-danger" id="rejectButton">
                        <i class="fas fa-times me-1"></i> Reject
                    </button>
                @endif
            </div>
        </div>

    </div>{{-- end hrform-a4 --}}
</div>{{-- end hrform-shell --}}

{{-- ══ Scripts ════════════════════════════════════════════════════════════ --}}
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.4.12/dist/sweetalert2.all.min.js"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
    document.getElementById('backButton').addEventListener('click', () => window.history.back());

    @if ($isClinicalDepartment && $user->professional_reg_number && !$isApproved && !$isRejected && (!$currentHrHistory || !$currentHrHistory->professional_reg_verified))
    (function() {
        const btn = document.getElementById('saveVerificationBtn');
        if (!btn) return;
        btn.addEventListener('click', function() {
            const chk      = document.getElementById('professional_reg_verified');
            const until    = document.getElementById('license_valid_until').value;
            const provider = document.getElementById('license_provider').value;
            const verified = chk.checked;
            const statusEl = document.getElementById('verificationStatus');

            if (verified && !until) {
                Swal.fire({ icon:'warning', title:'Date Required', text:'Enter the license expiration date before verifying.', confirmButtonColor:'#007A33' });
                return;
            }
            if (!verified && chk.hasAttribute('data-verified')) {
                Swal.fire({ icon:'warning', title:'Unverify?', text:'Are you sure?', showCancelButton:true,
                    confirmButtonColor:'#007A33', cancelButtonColor:'#6c757d',
                    confirmButtonText:'Yes, unverify'
                }).then(r => { if (r.isConfirmed) doSave(false); else chk.checked = true; });
                return;
            }
            doSave(verified);

            function doSave(v) {
                const orig = btn.innerHTML;
                btn.disabled = true; btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Saving…';
                $.ajax({
                    url: '{{ route('hr_form.verify_professional_reg') }}', method:'POST', dataType:'json',
                    headers:{'X-Requested-With':'XMLHttpRequest','Accept':'application/json'},
                    data:{ user_id:{{ $user->id }}, verified:v?1:0, license_valid_until:until, license_provider:provider, _token:'{{ csrf_token() }}' },
                    success(res) {
                        btn.disabled=false; btn.innerHTML=orig;
                        if (res.success) {
                            statusEl.innerHTML = v
                                ? '<span class="badge bg-success"><i class="fas fa-check-circle"></i> Verified</span>'
                                : '<span class="badge bg-secondary">Not Verified</span>';
                            v ? chk.setAttribute('data-verified','true') : chk.removeAttribute('data-verified');
                            Swal.fire({ icon:'success', title:'Saved', text:res.message, confirmButtonColor:'#007A33', timer:2000, timerProgressBar:true });
                        }
                    },
                    error(xhr) {
                        btn.disabled=false; btn.innerHTML=orig;
                        let msg='Failed to save.';
                        try { msg=xhr.responseJSON?.message||JSON.parse(xhr.responseText)?.message||msg; } catch(e){}
                        Swal.fire({ icon:'error', title:'Error', text:msg, confirmButtonColor:'#007A33' });
                    }
                });
            }
        });
    })();
    @endif

    @if (!$isApproved && !$isRejected)
    // ── Approve ──
    document.getElementById('approveButton').addEventListener('click', function() {
        @if ($isClinicalDepartment && $user->professional_reg_number)
        const chk = document.getElementById('professional_reg_verified');
        const verified = chk ? chk.checked : {{ $currentHrHistory && $currentHrHistory->professional_reg_verified ? 'true' : 'false' }};
        if (!verified) {
            Swal.fire({ icon:'warning', title:'Verification Required',
                text:'Verify the professional registration number before approving.', confirmButtonColor:'#007A33' });
            return;
        }
        @endif
        Swal.fire({ title:'Approve this form?', text:'This will mark the HR form as approved and activate the employee record.',
            icon:'question', showCancelButton:true, confirmButtonColor:'#007A33',
            confirmButtonText:'Yes, Approve', cancelButtonText:'Cancel', reverseButtons:true
        }).then(r => {
            if (!r.isConfirmed) return;
            Swal.fire({ title:'Processing…', allowOutsideClick:false, allowEscapeKey:false, didOpen:()=>Swal.showLoading() });
            $.ajax({
                url:'/hr_form_approve/{{ $user->id }}', method:'POST', dataType:'json',
                headers:{'X-Requested-With':'XMLHttpRequest','Accept':'application/json'},
                data:{ status:'approved', comment:'', _token:'{{ csrf_token() }}' },
                success(res) {
                    if (res.success) {
                        Swal.fire({ icon:'success', title:'Approved!', text:res.message||'HR form approved.', confirmButtonColor:'#007A33' })
                            .then(()=>window.location.href='/requestapprove');
                    } else {
                        Swal.fire({ icon:'error', title:'Error', text:res.message||'Could not approve.', confirmButtonColor:'#007A33' });
                    }
                },
                error(xhr) {
                    let msg='There was an error approving the request.';
                    try { msg=xhr.responseJSON?.message||JSON.parse(xhr.responseText)?.message||msg; } catch(e){}
                    Swal.fire({ icon:'error', title:'Error', text:msg, confirmButtonColor:'#007A33' });
                }
            });
        });
    });

    // ── Reject ──
    document.getElementById('rejectButton').addEventListener('click', function() {
        Swal.fire({ title:'Reject this form?', icon:'warning', showCancelButton:true,
            confirmButtonColor:'#dc3545', confirmButtonText:'Reject', cancelButtonText:'Cancel',
            input:'textarea', inputPlaceholder:'Please provide a rejection reason…',
            showLoaderOnConfirm:true,
            preConfirm(comment) {
                if (!comment) { Swal.showValidationMessage('A rejection reason is required'); return false; }
                return comment;
            }
        }).then(r => {
            if (!r.isConfirmed) return;
            Swal.fire({ title:'Processing…', allowOutsideClick:false, allowEscapeKey:false, didOpen:()=>Swal.showLoading() });
            $.ajax({
                url:'/hr_form_reject', method:'POST',
                data:{ id:{{ $user->id }}, status:'rejected', comment:r.value, _token:'{{ csrf_token() }}' },
                success() { window.location.href='/requestapprove'; },
                error(xhr) {
                    if (xhr.status===302||xhr.getResponseHeader('Location')) { window.location.reload(true); }
                    else { Swal.fire({ icon:'error', title:'Error', text:'There was an error rejecting the submission.' }); }
                }
            });
        });
    });
    @endif
</script>
@endsection
