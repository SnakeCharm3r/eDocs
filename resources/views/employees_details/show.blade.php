@extends('layouts.template')

@section('breadcrumb')
    @include('sweetalert::alert')
    <link rel="stylesheet" href="{{ asset('assets/plugins/sweetalert/sweetalert2.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/plugins/fontawesome/css/all.min.css') }}">
    <style>
        .profile-hero { background: #fff; border-radius: 10px; border: 1px solid #e9ecef; }
        .profile-avatar-wrap { position: relative; display: inline-block; }
        .profile-avatar { width: 110px; height: 110px; object-fit: cover; border-radius: 50%; border: 3px solid #e9ecef; }
        .avatar-placeholder { width: 110px; height: 110px; border-radius: 50%; background: #e9ecef; display: flex; align-items: center; justify-content: center; border: 3px solid #dee2e6; }
        .avatar-placeholder i { font-size: 2.5rem; color: #adb5bd; }
        .avatar-download { position: absolute; bottom: 2px; right: 2px; background: #0d6efd; color: #fff; border-radius: 50%; width: 26px; height: 26px; display: flex; align-items: center; justify-content: center; font-size: .7rem; text-decoration: none; }
        .status-badge { font-size: .75rem; padding: .3em .75em; border-radius: 20px; font-weight: 600; text-transform: capitalize; }
        .info-label { color: #6c757d; font-size: .78rem; margin-bottom: 1px; }
        .info-value { font-weight: 500; font-size: .92rem; color: #212529; }
        .section-card { border-radius: 8px; border: 1px solid #e9ecef; margin-bottom: 1.25rem; }
        .section-card .card-header { background: #f8f9fa; border-bottom: 1px solid #e9ecef; padding: .65rem 1rem; border-radius: 8px 8px 0 0; }
        .section-card .card-header h6 { font-size: .85rem; font-weight: 600; color: #495057; margin: 0; }
        .doc-item { display: flex; align-items: center; gap: .6rem; padding: .5rem 0; border-bottom: 1px solid #f0f0f0; }
        .doc-item:last-child { border-bottom: none; }
        .doc-item i { color: #6c757d; font-size: .9rem; width: 18px; text-align: center; }
        .doc-item a { font-size: .85rem; }
        .kv-row { display: flex; gap: .5rem; margin-bottom: .75rem; }
        .kv-row:last-child { margin-bottom: 0; }
        .edu-badge { display: inline-flex; align-items: center; gap: .4rem; font-size: .82rem; padding: .35rem .7rem; border-radius: 6px; background: #f8f9fa; border: 1px solid #dee2e6; color: #212529; text-decoration: none; }
        .edu-badge:hover { background: #e9ecef; color: #212529; }
        .edu-badge i { color: #6c757d; }
        .policy-card { border: 1px solid #dee2e6; border-radius: 6px; transition: border-color .15s; }
        .policy-card:hover { border-color: #adb5bd; }
        .completion-item { padding: .5rem 0; border-bottom: 1px solid #f0f0f0; display: flex; justify-content: space-between; align-items: center; }
        .completion-item:last-child { border-bottom: none; }
    </style>
@endsection

@section('content')
<div class="page-wrapper">
    <div class="content container-fluid">

        {{-- Flash message --}}
        @if (session('success'))
            <div class="alert alert-success alert-dismissible fade show" id="successMsg" role="alert">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <script>setTimeout(()=>document.getElementById('successMsg')?.remove(), 3000);</script>
        @endif

        {{-- ===== PROFILE HERO ===== --}}
        <div class="profile-hero p-4 mb-4 shadow-sm">
            <div class="d-flex flex-wrap gap-4 align-items-center">
                {{-- Avatar --}}
                <div class="profile-avatar-wrap flex-shrink-0">
                    @if ($user->profile_picture)
                        <img src="{{ asset('storage/' . $user->profile_picture) }}" alt="Profile" class="profile-avatar">
                        <a href="{{ asset('storage/' . $user->profile_picture) }}" download class="avatar-download" title="Download photo">
                            <i class="fas fa-download"></i>
                        </a>
                    @else
                        <div class="avatar-placeholder">
                            <i class="fas fa-user"></i>
                        </div>
                    @endif
                </div>

                {{-- Name + key info --}}
                <div class="flex-grow-1">
                    <div class="d-flex flex-wrap align-items-start gap-2 mb-1">
                        <h4 class="mb-0 fw-semibold">{{ trim($user->fname . ' ' . $user->mname . ' ' . $user->lname) }}</h4>
                        @php
                            $statusClass = match(strtolower($user->status ?? '')) {
                                'active'      => 'bg-success',
                                'inactive'    => 'bg-secondary',
                                'deactivated' => 'bg-danger',
                                'pending'     => 'bg-warning text-dark',
                                default       => 'bg-light text-dark border',
                            };
                        @endphp
                        <span class="badge status-badge {{ $statusClass }}">{{ ucfirst($user->status ?? 'Unknown') }}</span>
                    </div>

                    <div class="d-flex flex-wrap gap-3 text-muted small mb-3">
                        @if (optional($user->jobTitle)->job_title)
                            <span><i class="fas fa-briefcase me-1"></i>{{ $user->jobTitle->job_title }}</span>
                        @endif
                        @if (optional($user->department)->dept_name)
                            <span><i class="fas fa-building me-1"></i>{{ $user->department->dept_name }}</span>
                        @endif
                        @if ($user->ccbrt_code)
                            <span><i class="fas fa-id-badge me-1"></i>{{ $user->ccbrt_code }}</span>
                        @endif
                        @if ($user->emp_id)
                            <span><i class="fas fa-hashtag me-1"></i>{{ $user->emp_id }}</span>
                        @endif
                    </div>

                    <div class="d-flex flex-wrap gap-2">
                        <a href="{{ route('user.edit', $user->id) }}" class="btn btn-sm btn-primary">
                            <i class="fas fa-edit me-1"></i>Edit Profile
                        </a>
                        <a href="{{ route('employee.index') }}" class="btn btn-sm btn-outline-secondary">
                            <i class="fas fa-arrow-left me-1"></i>Back to Staff List
                        </a>
                    </div>
                </div>
            </div>
        </div>

        {{-- ===== MAIN CONTENT ===== --}}
        <div class="row g-4">

            {{-- LEFT COLUMN --}}
            <div class="col-lg-8">

                {{-- Employee Details --}}
                <div class="section-card card">
                    <div class="card-header">
                        <h6><i class="fas fa-info-circle me-2 text-primary"></i>Employee Details</h6>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-sm-6">
                                <div class="info-label">Department</div>
                                <div class="info-value">{{ optional($user->department)->dept_name ?? '—' }}</div>
                            </div>
                            <div class="col-sm-6">
                                <div class="info-label">Job Title</div>
                                <div class="info-value">{{ optional($user->jobTitle)->job_title ?? '—' }}</div>
                            </div>
                            <div class="col-sm-6">
                                <div class="info-label">CCBRT Code</div>
                                <div class="info-value">{{ $user->ccbrt_code ?? '—' }}</div>
                            </div>
                            <div class="col-sm-6">
                                <div class="info-label">Employee ID</div>
                                <div class="info-value">{{ $user->emp_id ?? '—' }}</div>
                            </div>
                            <div class="col-sm-6">
                                <div class="info-label">Professional Reg. Number</div>
                                <div class="info-value">{{ $user->professional_reg_number ?? '—' }}</div>
                            </div>
                            <div class="col-sm-6">
                                <div class="info-label">NSSF No.</div>
                                <div class="info-value">{{ $user->nssf_no ?? '—' }}</div>
                            </div>
                            <div class="col-sm-6">
                                <div class="info-label">Employment Contract Type</div>
                                <div class="info-value">{{ optional($user->employmentType)->employment_type ?? '—' }}</div>
                            </div>
                            <div class="col-sm-6">
                                <div class="info-label">Starting Date</div>
                                <div class="info-value">
                                    {{ $user->starting_date ? \Carbon\Carbon::parse($user->starting_date)->format('d M Y') : '—' }}
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="info-label">Joined System</div>
                                <div class="info-value">{{ \Carbon\Carbon::parse($user->created_at)->format('d M Y') }}</div>
                            </div>
                            @if ($user->ending_date)
                            <div class="col-sm-6">
                                <div class="info-label">Ending Date</div>
                                <div class="info-value">{{ \Carbon\Carbon::parse($user->ending_date)->format('d M Y') }}</div>
                            </div>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- Documents & Attachments --}}
                @php
                    $attachments = [
                        ['label'=>'Curriculum Vitae (CV)',   'file'=>$user->employee_cv],
                        ['label'=>'Marriage Certificate',    'file'=>($user->marital_status=='married' ? ($user->marriage_certificate ?? null) : null)],
                        ['label'=>'Divorce Certificate',     'file'=>($user->marital_status=='divorced' ? ($user->divorced_certificate ?? $user->divorce_certificate ?? null) : null)],
                        ['label'=>'NIDA',                    'file'=>$user->nida],
                        ['label'=>'Driving License',         'file'=>$user->driving_license],
                        ['label'=>'Transport ID',            'file'=>$user->transport_id],
                        ['label'=>'Voting ID',               'file'=>$user->voting_id],
                        ['label'=>'Other Documents',         'file'=>$user->other_document],
                        ['label'=>'Signature',               'file'=>$user->signature],
                    ];
                    $hasAttachments = collect($attachments)->filter(fn($a) => !empty($a['file']))->isNotEmpty();
                @endphp
                @if ($hasAttachments)
                <div class="section-card card">
                    <div class="card-header">
                        <h6><i class="fas fa-paperclip me-2 text-secondary"></i>Documents & Attachments</h6>
                    </div>
                    <div class="card-body">
                        <div class="row g-2">
                            @foreach ($attachments as $att)
                                @if (!empty($att['file']))
                                <div class="col-sm-6">
                                    <div class="doc-item">
                                        <i class="fas fa-file-alt"></i>
                                        <div class="flex-grow-1 small">{{ $att['label'] }}</div>
                                        <a href="{{ asset('storage/' . $att['file']) }}" target="_blank" class="btn btn-xs btn-outline-secondary btn-sm py-0 px-2">
                                            <i class="fas fa-eye me-1"></i>View
                                        </a>
                                    </div>
                                </div>
                                @endif
                            @endforeach
                        </div>
                    </div>
                </div>
                @endif

                {{-- Education Documents --}}
                @php
                    $eduDocs = [
                        'O-Level (Form 4)'   => ['cert'=>$user->o_level_certificate??null,  'trans'=>null, 'alt'=>$user->form_4_certificate??null],
                        'A-Level (Form 6)'   => ['cert'=>$user->a_level_certificate??null,  'trans'=>null, 'alt'=>$user->form_6_certificate??null],
                        'Certificate'        => ['cert'=>$user->certificate_certificate??null,'trans'=>$user->certificate_transcript??null],
                        'Diploma'            => ['cert'=>$user->diploma_certificate??null,   'trans'=>$user->diploma_transcript??null],
                        'Degree'             => ['cert'=>$user->degree_certificate??null,    'trans'=>$user->degree_transcript??null],
                        'Masters'            => ['cert'=>$user->masters_certificate??null,   'trans'=>$user->masters_transcript??null],
                        'PhD'                => ['cert'=>$user->phd_certificate??null,       'trans'=>$user->phd_transcript??null],
                    ];
                    $hasEdu = collect($eduDocs)->filter(fn($d) => !empty($d['cert']) || !empty($d['trans']) || !empty($d['alt']??null))->isNotEmpty();
                @endphp
                @if ($hasEdu)
                <div class="section-card card">
                    <div class="card-header">
                        <h6><i class="fas fa-graduation-cap me-2 text-secondary"></i>Education Documents</h6>
                    </div>
                    <div class="card-body">
                        @foreach ($eduDocs as $level => $docs)
                            @php
                                $cert  = $docs['cert'] ?? null;
                                $trans = $docs['trans'] ?? null;
                                $alt   = $docs['alt'] ?? null;
                                if (empty($cert) && empty($trans) && empty($alt)) continue;
                            @endphp
                            <div class="mb-3">
                                <div class="info-label mb-1">{{ $level }}</div>
                                <div class="d-flex flex-wrap gap-2">
                                    @if (!empty($cert))
                                        <a href="{{ asset('storage/' . $cert) }}" target="_blank" class="edu-badge">
                                            <i class="fas fa-file-pdf"></i> Certificate
                                        </a>
                                    @elseif (!empty($alt))
                                        <a href="{{ asset('storage/' . $alt) }}" target="_blank" class="edu-badge">
                                            <i class="fas fa-file-pdf"></i> Certificate
                                        </a>
                                    @endif
                                    @if (!empty($trans))
                                        <a href="{{ asset('storage/' . $trans) }}" target="_blank" class="edu-badge">
                                            <i class="fas fa-file-alt"></i> Transcript
                                        </a>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
                @endif

                {{-- CCBRT Policies --}}
                <div class="section-card card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h6 class="mb-0"><i class="fas fa-file-contract me-2 text-secondary"></i>CCBRT Policies</h6>
                        @if (!$policies->isEmpty())
                        <div class="d-flex gap-1">
                            <button type="button" class="btn btn-xs btn-outline-secondary btn-sm py-0 px-2" onclick="selectAllPolicies()">
                                <i class="fas fa-check-square me-1"></i>All
                            </button>
                            <button type="button" class="btn btn-xs btn-outline-secondary btn-sm py-0 px-2" onclick="deselectAllPolicies()">
                                <i class="fas fa-square me-1"></i>None
                            </button>
                        </div>
                        @endif
                    </div>
                    <div class="card-body">
                        @if ($policies->isEmpty())
                            <p class="text-muted small mb-0"><i class="fas fa-info-circle me-1"></i>No policies available.</p>
                        @else
                            <div class="row g-2 mb-3" id="policy-selection">
                                @foreach ($policies as $policy)
                                <div class="col-sm-6">
                                    <div class="policy-card p-2">
                                        <div class="form-check mb-0">
                                            <input class="form-check-input policy-checkbox" type="checkbox"
                                                value="{{ $policy->id }}" id="policy-{{ $policy->id }}"
                                                data-title="{{ $policy->title }}">
                                            <label class="form-check-label" for="policy-{{ $policy->id }}">
                                                <span class="d-block fw-medium" style="font-size:.85rem;">{{ $policy->title }}</span>
                                                <span class="text-muted" style="font-size:.75rem;">
                                                    <i class="fas fa-calendar me-1"></i>{{ \Carbon\Carbon::parse($policy->created_at)->format('d M Y') }}
                                                </span>
                                            </label>
                                        </div>
                                    </div>
                                </div>
                                @endforeach
                            </div>
                            <div class="d-flex align-items-center gap-2 pt-2 border-top">
                                <span id="selected-count" class="badge bg-secondary">
                                    <span id="count-text">0</span> selected
                                </span>
                                <button type="button" class="btn btn-sm btn-success ms-auto" id="download-selected-btn" onclick="downloadSelectedPolicies()" disabled>
                                    <i class="fas fa-download me-1"></i>Download Selected
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-primary" onclick="previewSelectedPolicies()" id="preview-btn" disabled>
                                    <i class="fas fa-eye me-1"></i>Preview
                                </button>
                            </div>

                            {{-- Policy Preview Modal --}}
                            <div class="modal fade" id="policyPreviewModal" tabindex="-1">
                                <div class="modal-dialog modal-lg">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title"><i class="fas fa-file-alt me-2"></i>Policy Preview</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body" id="policy-preview-content" style="max-height:70vh;overflow-y:auto;">
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                            <button type="button" class="btn btn-success" onclick="downloadFromPreview()">
                                                <i class="fas fa-download me-1"></i>Download
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>

            </div>{{-- /LEFT COLUMN --}}

            {{-- RIGHT COLUMN --}}
            <div class="col-lg-4">

                {{-- Account Management --}}
                <div class="section-card card mb-4">
                    <div class="card-header">
                        <h6><i class="fas fa-user-cog me-2 text-secondary"></i>Account Management</h6>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <div class="info-label">Status</div>
                            <span class="badge status-badge {{ $statusClass }} mt-1">{{ ucfirst($user->status ?? 'Unknown') }}</span>
                        </div>
                        <div class="mb-3">
                            <div class="info-label">Joined System</div>
                            <div class="info-value">{{ \Carbon\Carbon::parse($user->created_at)->format('d M Y') }}</div>
                        </div>
                        @if ($user->starting_date)
                        <div class="mb-3">
                            <div class="info-label">Employment Start</div>
                            <div class="info-value">{{ \Carbon\Carbon::parse($user->starting_date)->format('d M Y') }}</div>
                        </div>
                        @endif
                        <div class="d-flex flex-wrap gap-2 pt-2 border-top">
                            @if ($user->status === 'active')
                                <form action="{{ route('auth.deactivate', $user->id) }}" method="POST" id="deactivateForm{{ $user->id }}">
                                    @csrf @method('PUT')
                                    <button type="button" class="btn btn-sm btn-warning" onclick="confirmDeactivate({{ $user->id }})">
                                        <i class="fas fa-user-slash me-1"></i>Deactivate
                                    </button>
                                </form>
                            @elseif (in_array($user->status, ['inactive', 'deactivated']))
                                <form action="{{ route('auth.activate', $user->id) }}" method="POST" onsubmit="return confirm('Activate this user?');">
                                    @csrf @method('PUT')
                                    <button type="submit" class="btn btn-sm btn-success">
                                        <i class="fas fa-user-check me-1"></i>Activate
                                    </button>
                                </form>
                            @endif
                            @if ($user->status === 'inactive')
                                <form action="{{ route('auth.destroy', $user->id) }}" method="POST" onsubmit="return confirm('Delete this user? This cannot be undone.');">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-danger">
                                        <i class="fas fa-trash me-1"></i>Delete
                                    </button>
                                </form>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- Profile Completion (HR/Admin — inactive/pending only) --}}
                @role('super-admin|admin|hr')
                    @if ($user->status === 'inactive' || ($user->status === 'pending' && $existingWorkflow))
                    <div class="section-card card mb-4">
                        <div class="card-header">
                            <h6><i class="fas fa-clipboard-check me-2 text-secondary"></i>Profile Completion</h6>
                        </div>
                        <div class="card-body p-0">
                            @php
                                $completionItems = [
                                    ['label'=>'Personal Details',   'done'=>$hasPersonalDetails,   'route'=>route('hr.employee.personal-details', $user->id),   'required'=>true],
                                    ['label'=>'Family Details',     'done'=>$hasFamilyDetails,     'route'=>route('hr.employee.family-details', $user->id),     'required'=>true],
                                    ['label'=>'Health Details',     'done'=>$hasHealthDetails,     'route'=>route('hr.employee.health-details', $user->id),     'required'=>true],
                                    ['label'=>'Language Knowledge', 'done'=>$hasLanguageKnowledge, 'route'=>route('hr.employee.language-knowledge', $user->id), 'required'=>true],
                                    ['label'=>'Conflict of Interest','done'=>$hasConflictInterest, 'route'=>route('hr.employee.conflict-interest', $user->id),  'required'=>true],
                                    ['label'=>'CCBRT Relation',     'done'=>$hasCcbrtRelation,     'route'=>route('hr.employee.ccbrt-relation', $user->id),     'required'=>false],
                                ];
                            @endphp
                            <div class="px-3 pt-2 pb-1">
                                @foreach ($completionItems as $item)
                                <div class="completion-item">
                                    <div class="d-flex align-items-center gap-2" style="font-size:.85rem;">
                                        <i class="fas {{ $item['done'] ? 'fa-check-circle text-success' : ($item['required'] ? 'fa-times-circle text-danger' : 'fa-circle text-muted') }}"></i>
                                        <span>{{ $item['label'] }}@if(!$item['required']) <small class="text-muted">(Optional)</small>@endif</span>
                                    </div>
                                    @if ($item['done'])
                                        <span class="badge bg-success" style="font-size:.7rem;">Done</span>
                                    @else
                                        <a href="{{ $item['route'] }}" class="btn btn-xs btn-outline-primary btn-sm py-0 px-2" style="font-size:.75rem;">Fill</a>
                                    @endif
                                </div>
                                @endforeach
                            </div>
                            <div class="px-3 py-2 border-top">
                                @if ($profileComplete)
                                    <div class="alert alert-success py-2 mb-0 small">
                                        <i class="fas fa-check-circle me-1"></i>All required details completed.
                                    </div>
                                @elseif ($existingWorkflow)
                                    <span class="badge bg-info"><i class="fas fa-clock me-1"></i>Pending Approval</span>
                                @else
                                    <div class="alert alert-warning py-2 mb-0 small">
                                        <i class="fas fa-info-circle me-1"></i>Staff must login and sign to submit for approval.
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                    @endif
                @endrole

                {{-- On-Call Rate Management (HR/Admin) --}}
                @role('super-admin|admin|hr')
                <div class="section-card card">
                    <div class="card-header">
                        <h6><i class="fas fa-dollar-sign me-2 text-secondary"></i>On-Call Rates</h6>
                    </div>
                    <div class="card-body">
                        <p class="text-muted small mb-3">Assign which on-call rates this staff member can use. If none selected, all active rates apply.</p>
                        <form id="oncallRatesForm" method="POST" action="{{ route('employee.update-oncall-rates', $user->id) }}">
                            @csrf @method('PUT')
                            @if ($allOnCallRates->isEmpty())
                                <div class="alert alert-warning small py-2">
                                    <i class="fas fa-exclamation-triangle me-1"></i>No active on-call rates found.
                                </div>
                            @else
                                <div class="mb-3">
                                    @foreach ($allOnCallRates as $rate)
                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="checkbox" name="oncall_rates[]"
                                            value="{{ $rate->id }}" id="rate_{{ $rate->id }}"
                                            {{ $user->onCallRates->contains($rate->id) ? 'checked' : '' }}>
                                        <label class="form-check-label" for="rate_{{ $rate->id }}" style="font-size:.85rem;">
                                            <strong>{{ $rate->education_level }}</strong>
                                            <span class="text-muted"> — TZS {{ number_format($rate->rate, 0) }}</span>
                                        </label>
                                    </div>
                                    @endforeach
                                </div>
                                <div class="d-flex gap-2 flex-wrap border-top pt-2">
                                    <button type="submit" class="btn btn-sm btn-primary">
                                        <i class="fas fa-save me-1"></i>Save
                                    </button>
                                    <button type="button" class="btn btn-sm btn-outline-secondary" id="selectAllRates">All</button>
                                    <button type="button" class="btn btn-sm btn-outline-secondary" id="deselectAllRates">None</button>
                                </div>
                            @endif
                        </form>
                    </div>
                </div>
                @endrole

            </div>{{-- /RIGHT COLUMN --}}
        </div>

    </div>
</div>

<script src="{{ asset('assets/plugins/sweetalert/sweetalert2.all.min.js') }}"></script>
<script>
    // Deactivate confirmation
    function confirmDeactivate(userId) {
        Swal.fire({
            title: 'Deactivate User?',
            html: '<div class="text-start"><p class="mb-3">Are you sure you want to deactivate this user?</p><div class="alert alert-warning mb-0"><i class="fas fa-exclamation-triangle me-2"></i><strong>Warning:</strong> This user will <strong>NOT</strong> be able to login after deactivation.</div></div>',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ffc107',
            cancelButtonColor: '#6c757d',
            confirmButtonText: '<i class="fas fa-user-slash me-2"></i>Yes, Deactivate',
            cancelButtonText: 'Cancel',
            reverseButtons: true,
            focusCancel: true
        }).then(r => { if (r.isConfirmed) document.getElementById('deactivateForm' + userId).submit(); });
    }

    // On-call rate select/deselect all
    document.addEventListener('DOMContentLoaded', function () {
        const cbs = document.querySelectorAll('input[name="oncall_rates[]"]');
        document.getElementById('selectAllRates')?.addEventListener('click', () => cbs.forEach(c => c.checked = true));
        document.getElementById('deselectAllRates')?.addEventListener('click', () => cbs.forEach(c => c.checked = false));

        // Policy checkboxes
        document.querySelectorAll('.policy-checkbox').forEach(cb => cb.addEventListener('change', updateSelectedCount));
        updateSelectedCount();
    });

    // Policies
    const policies = @json($policies);
    const userId = {{ $user->id }};
    let selectedPolicies = [];

    function updateSelectedCount() {
        const checked = document.querySelectorAll('.policy-checkbox:checked');
        selectedPolicies = Array.from(checked).map(cb => ({ id: cb.value, title: cb.getAttribute('data-title') }));
        const n = selectedPolicies.length;
        document.getElementById('count-text').textContent = n;
        const dl = document.getElementById('download-selected-btn');
        const pv = document.getElementById('preview-btn');
        if (dl) dl.disabled = n === 0;
        if (pv) pv.disabled = n === 0;
        const badge = document.getElementById('selected-count');
        if (badge) { badge.classList.toggle('bg-success', n > 0); badge.classList.toggle('bg-secondary', n === 0); }
    }

    function selectAllPolicies() {
        document.querySelectorAll('.policy-checkbox').forEach(cb => cb.checked = true);
        updateSelectedCount();
    }

    function deselectAllPolicies() {
        document.querySelectorAll('.policy-checkbox').forEach(cb => cb.checked = false);
        updateSelectedCount();
    }

    function downloadSelectedPolicies() {
        if (!selectedPolicies.length) { Swal.fire('Error', 'Please select at least one policy.', 'error'); return; }
        const ids = selectedPolicies.map(p => p.id).join(',');
        window.location.href = `{{ route('user.policies.download', ['id' => $user->id]) }}?policy_ids=${ids}`;
    }

    function previewSelectedPolicies() {
        if (!selectedPolicies.length) { Swal.fire('Error', 'Please select at least one policy.', 'error'); return; }
        const modal = new bootstrap.Modal(document.getElementById('policyPreviewModal'));
        const content = document.getElementById('policy-preview-content');
        content.innerHTML = '<div class="text-center py-4"><div class="spinner-border text-primary"></div></div>';
        modal.show();
        fetch(`{{ route('user.policies.preview', ['id' => $user->id]) }}?policy_ids=${selectedPolicies.map(p=>p.id).join(',')}`)
            .then(r => r.text()).then(html => content.innerHTML = html)
            .catch(() => content.innerHTML = '<div class="alert alert-danger">Failed to load preview.</div>');
    }

    function downloadFromPreview() {
        downloadSelectedPolicies();
        bootstrap.Modal.getInstance(document.getElementById('policyPreviewModal'))?.hide();
    }
</script>
@endsection
