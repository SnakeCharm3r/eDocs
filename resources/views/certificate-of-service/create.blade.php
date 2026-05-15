@extends('layouts.template')

@push('styles')
<style>
    .certificate-create-page .content.container-fluid { padding-top: 1rem; }
    .certificate-create-page .section-card { border: 0; border-radius: 10px; box-shadow: 0 1px 6px rgba(0,0,0,.07); margin-bottom: .75rem; }
    .certificate-create-page .section-card .card-header { background: #f8fdf9; border-bottom: 1px solid #d1e7dd; border-radius: 10px 10px 0 0 !important; padding: .55rem 1rem; }
    .certificate-create-page .section-card .card-header h6 { margin: 0; font-size: .82rem; font-weight: 600; color: #198754; }
    .certificate-create-page .section-card .card-body { padding: 1rem; }
    .certificate-create-page .cos-theme-icon { color: #007A33; }
    .certificate-create-page .cos-section-title { font-size: 1rem; font-weight: 600; color: #495057; margin-bottom: 1rem; padding-bottom: .5rem; border-bottom: 2px solid #007A33; }
    .certificate-create-page .cos-input-wrap { position: relative; }
    .certificate-create-page .cos-input-icon { position: absolute; left: .75rem; top: 50%; transform: translateY(-50%); color: #007A33; z-index: 2; font-size: .85rem; }
    .certificate-create-page .cos-textarea-icon { top: .9rem; transform: none; }
    .certificate-create-page .cos-input-wrap .form-control,
    .certificate-create-page .cos-input-wrap .form-select { padding-left: 2.1rem; }
    .certificate-create-page .cos-preview-card { border-radius: 8px; padding: .85rem; background: #f8f9fa; border: 1px solid rgba(21, 50, 67, 0.08); }
    .certificate-create-page .cos-preview-name,
    .certificate-create-page .cos-signature-name { color: #2f343b; }
    .certificate-create-page .cos-preview-meta,
    .certificate-create-page .cos-signature-name small,
    .certificate-create-page .cos-field-hint,
    .certificate-create-page .cos-actions-note { color: #6f7782; }
    .certificate-create-page .cos-preview-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: .85rem; margin-top: 1rem; }
    .certificate-create-page .cos-preview-grid span { display: block; font-size: .74rem; text-transform: uppercase; letter-spacing: .04em; color: #6f7782; margin-bottom: .25rem; }
    .certificate-create-page .cos-preview-grid strong { color: #2f343b; font-size: .88rem; }
    .certificate-create-page .cos-status-pill { display: inline-flex; align-items: center; justify-content: center; padding: .2rem .55rem; border-radius: 999px; font-size: .72rem; font-weight: 600; line-height: 1.2; border: 1px solid transparent; white-space: nowrap; }
    .certificate-create-page .cos-status-pill.is-pending { background: #f3f4f6; color: #4b5563; border-color: #d1d5db; }
    .certificate-create-page .cos-status-pill.is-selected { background: #d1fae5; color: #065f46; border-color: #a7f3d0; }
    .certificate-create-page .cos-signature-box { border: 0; border-radius: 10px; box-shadow: 0 1px 6px rgba(0,0,0,.07); background: #fff; overflow: hidden; margin-bottom: .75rem; }
    .certificate-create-page .cos-signature-header { background: #f8fdf9; border-bottom: 1px solid #d1e7dd; padding: .55rem 1rem; }
    .certificate-create-page .cos-signature-body { padding: 1rem; }
    .certificate-create-page .cos-signature-frame { min-height: 72px; border-radius: 8px; background: #fff; border: 1px dashed rgba(21, 50, 67, 0.16); display: flex; align-items: center; justify-content: center; padding: .75rem; }
    .certificate-create-page .cos-signature-frame img { max-height: 58px; max-width: 100%; object-fit: contain; }
    .certificate-create-page textarea.form-control { min-height: 96px; resize: vertical; }
    .certificate-create-page .cos-actions { display: flex; align-items: center; justify-content: space-between; gap: 1rem; border-top: 1px solid rgba(21, 50, 67, 0.08); padding-top: 1rem; margin-top: .75rem; }
    .certificate-create-page .cos-actions-note { font-size: .78rem; margin: 0; }
    .certificate-create-page .btn-cos-primary { background-color: #61ce70; border: 1px solid #61ce70; color: #fff; border-radius: .375rem; padding: .375rem .75rem; font-size: .875rem; }
    .certificate-create-page .btn-cos-primary:hover,
    .certificate-create-page .btn-cos-primary:focus { color: #fff; background-color: #00b374; border-color: #00b374; }
    .certificate-create-page .select2-container { width: 100% !important; }
    .certificate-create-page .select2-container--default .select2-selection--single { min-height: 31px; border-radius: .375rem; border: 1px solid rgba(21, 50, 67, 0.13); display: flex; align-items: center; padding-left: 2.1rem; }
    .certificate-create-page .select2-container--default .select2-selection--single .select2-selection__rendered { color: #2f343b; line-height: 1.5; padding-left: 0; padding-right: 2rem; font-size: .875rem; }
    .certificate-create-page .select2-container--default .select2-selection--single .select2-selection__placeholder { color: #6f7782; }
    .certificate-create-page .select2-container--default .select2-selection--single .select2-selection__arrow { height: 100%; right: .75rem; }
    .certificate-create-page .select2-container--default.select2-container--focus .select2-selection--single,
    .certificate-create-page .select2-container--default.select2-container--open .select2-selection--single,
    .certificate-create-page .form-control:focus,
    .certificate-create-page .form-select:focus { border-color: rgba(95, 102, 112, 0.55); box-shadow: 0 0 0 .22rem rgba(95, 102, 112, 0.12); }
    .certificate-create-page .select2-dropdown { border: 1px solid rgba(21, 50, 67, 0.13); border-radius: .375rem; box-shadow: 0 10px 24px rgba(47, 52, 59, 0.1); overflow: hidden; }
    .certificate-create-page .select2-search--dropdown { padding: .65rem; }
    .certificate-create-page .select2-search--dropdown .select2-search__field { border: 1px solid rgba(21, 50, 67, 0.13); border-radius: .375rem; padding: .45rem .65rem; }
    .certificate-create-page .select2-results__option { padding: .45rem .7rem; font-size: .875rem; }
    .certificate-create-page .select2-results__option--highlighted[aria-selected] { background: rgba(0, 122, 51, 0.08) !important; color: #2f343b !important; }
    @media (max-width: 575.98px) {
        .certificate-create-page .cos-preview-grid { grid-template-columns: 1fr; }
        .certificate-create-page .cos-actions { flex-direction: column; align-items: stretch; }
        .certificate-create-page .cos-actions .d-flex,
        .certificate-create-page .cos-actions .btn { width: 100%; }
    }
</style>
@endpush

@section('content')
<div class="page-wrapper certificate-create-page">
    <div class="content container-fluid">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h4 class="mb-1"><i class="fas fa-file-certificate text-primary me-2"></i>Issue Certificate of Service</h4>
            </div>
            <a href="{{ route('certificate-of-service.index') }}" class="btn btn-outline-secondary btn-sm">
                <i class="fas fa-arrow-left me-1"></i>Back to certificates
            </a>
        </div>

        @if($errors->any())
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <strong><i class="fas fa-exclamation-circle me-2"></i>Please fix the following errors:</strong>
                <ul class="mb-0 mt-2">
                    @foreach($errors->all() as $e)
                        <li>{{ $e }}</li>
                    @endforeach
                </ul>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        <form method="POST" action="{{ route('certificate-of-service.store') }}">
            @csrf
            <div class="row">
                <div class="col-lg-4">
                    <div class="card section-card mb-3">
                        <div class="card-header">
                            <h6><i class="fas fa-user me-2 cos-theme-icon"></i>Select Staff</h6>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label class="form-label small fw-semibold">Staff Member <span class="text-danger">*</span></label>
                                <div class="cos-input-wrap">
                                    <i class="fas fa-users cos-input-icon"></i>
                                    <select name="user_id" id="staffSelect" class="form-select form-select-sm staff-search-select @error('user_id') is-invalid @enderror" required>
                                        <option value="">-- Select staff --</option>
                                        @foreach($eligibleStaff as $staff)
                                            @php
                                                $staffContract = $latestContracts[$staff->id] ?? null;
                                                $joiningDate = $staff->starting_date
                                                    ? \Carbon\Carbon::parse($staff->starting_date)->format('Y-m-d')
                                                    : ($staffContract?->start_date
                                                        ? \Carbon\Carbon::parse($staffContract->start_date)->format('Y-m-d')
                                                        : ($clearanceJoinDates[$staff->id] ?? ''));
                                                $endingDate = $staff->ending_date
                                                    ? \Carbon\Carbon::parse($staff->ending_date)->format('Y-m-d')
                                                    : ($staffContract?->end_date
                                                        ? \Carbon\Carbon::parse($staffContract->end_date)->format('Y-m-d')
                                                        : ($clearanceEndDates[$staff->id] ?? ''));
                                            @endphp
                                            <option value="{{ $staff->id }}"
                                                data-name="{{ trim(($staff->fname ?? '') . ' ' . ($staff->lname ?? '')) }}"
                                                data-code="{{ $staff->ccbrt_code ?? '' }}"
                                                data-dept="{{ $staff->department?->dept_name }}"
                                                data-position="{{ $staff->jobTitle?->job_title }}"
                                                data-join="{{ $joiningDate }}"
                                                data-end="{{ $endingDate }}"
                                                data-employment="{{ $staff->employmentType?->employee_type ?? '' }}"
                                                data-status="{{ $staff->status ?? '' }}"
                                                data-personal-email="{{ $personalEmails[$staff->id] ?? '' }}"
                                                data-fallback-email="{{ $defaultRecipientEmail ?? 'HRTeam@ccbrt.org' }}"
                                                {{ (old('user_id', $preselectedUserId) == $staff->id) ? 'selected' : '' }}>
                                                {{ $staff->fname }} {{ $staff->lname }}@if($staff->ccbrt_code) ({{ $staff->ccbrt_code }}) @endif
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                @error('user_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                @if($eligibleStaff->isEmpty())
                                    <small class="text-warning"><i class="fas fa-exclamation-triangle me-1"></i>No eligible staff found. Staff must have an approved clearance form or a deactivated account.</small>
                                @endif
                            </div>

                            <div class="cos-preview-card" id="staffPreviewCard">
                                <div class="d-flex justify-content-between align-items-start gap-3">
                                    <div>
                                        <div class="cos-preview-name fw-bold" id="previewName">No staff selected</div>
                                        <div class="cos-preview-meta small" id="previewCode">Choose a staff member to preview their employment details.</div>
                                    </div>
                                    <span class="cos-status-pill is-pending" id="previewStatus">Pending</span>
                                </div>
                                <div class="cos-preview-grid">
                                    <div><span>Department</span><strong id="previewDept">--</strong></div>
                                    <div><span>Position</span><strong id="previewPosition">--</strong></div>
                                    <div><span>Joining Date</span><strong id="previewJoin">--</strong></div>
                                    <div><span>Last Working Day</span><strong id="previewEnd">--</strong></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    @php
                        $cooSignatureSrc = null;
                        if ($coo && $coo->signature) {
                            if (str_starts_with($coo->signature, 'data:image')) {
                                $cooSignatureSrc = $coo->signature;
                            } elseif (file_exists(storage_path('app/public/' . $coo->signature))) {
                                $cooSignatureSrc = asset('storage/' . $coo->signature);
                            } else {
                                $cooSignatureSrc = 'data:image/png;base64,' . $coo->signature;
                            }
                        }
                    @endphp
                    @if($coo && $cooSignatureSrc)
                        <div class="cos-signature-box">
                            <div class="cos-signature-header">
                                <h6 class="mb-0" style="font-size:.82rem;font-weight:600;color:#198754;"><i class="fas fa-signature me-2 cos-theme-icon"></i>COO Signature</h6>
                            </div>
                            <div class="cos-signature-body">
                                <div class="mb-3">
                                    <div class="cos-signature-name fw-semibold">{{ $coo->fname }} {{ $coo->lname }} <small class="d-block">Chief Operating Officer</small></div>
                                </div>
                                <div class="cos-signature-frame">
                                        <img src="{{ $cooSignatureSrc }}" alt="COO Signature">
                                </div>
                            </div>
                        </div>
                    @endif
                </div>

                <div class="col-lg-8">
                    <div class="card section-card mb-0">
                        <div class="card-header">
                            <h6><i class="fas fa-file-alt me-2 cos-theme-icon"></i>Certificate Details</h6>
                        </div>
                        <div class="card-body">
                            <div class="cos-section-title">Service Record</div>
                            <div class="row g-3 mb-4">
                                <div class="col-md-6">
                                    <label class="form-label small fw-semibold">Issue Date <span class="text-danger">*</span></label>
                                    <div class="cos-input-wrap">
                                        <i class="fas fa-calendar-day cos-input-icon"></i>
                                        <input type="date" name="issue_date" class="form-control form-control-sm @error('issue_date') is-invalid @enderror" value="{{ old('issue_date', now()->format('Y-m-d')) }}" required>
                                    </div>
                                    @error('issue_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    <div class="cos-field-hint mt-2">Defaults to today. Change it only if the certificate is being issued retrospectively.</div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-semibold">Position Held <span class="text-danger">*</span></label>
                                    <div class="cos-input-wrap">
                                        <i class="fas fa-briefcase cos-input-icon"></i>
                                        <input type="text" name="position_held" id="positionHeld" class="form-control form-control-sm @error('position_held') is-invalid @enderror" value="{{ old('position_held') }}" placeholder="e.g. Nurse" required>
                                    </div>
                                    @error('position_held')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-semibold">Department <span class="text-danger">*</span></label>
                                    <div class="cos-input-wrap">
                                        <i class="fas fa-building cos-input-icon"></i>
                                        <input type="text" name="department" id="deptField" class="form-control form-control-sm @error('department') is-invalid @enderror" value="{{ old('department') }}" placeholder="Department name" required>
                                    </div>
                                    @error('department')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label small fw-semibold">Date of Joining <span class="text-danger">*</span></label>
                                    <div class="cos-input-wrap">
                                        <i class="fas fa-door-open cos-input-icon"></i>
                                        <input type="date" name="date_of_joining" id="joinDate" class="form-control form-control-sm @error('date_of_joining') is-invalid @enderror" value="{{ old('date_of_joining') }}" required>
                                    </div>
                                    @error('date_of_joining')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label small fw-semibold">Last Working Day <span class="text-danger">*</span></label>
                                    <div class="cos-input-wrap">
                                        <i class="fas fa-calendar-check cos-input-icon"></i>
                                        <input type="date" name="last_working_day" id="lastDay" class="form-control form-control-sm @error('last_working_day') is-invalid @enderror" value="{{ old('last_working_day') }}" required>
                                    </div>
                                    @error('last_working_day')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                            </div>

                            <hr class="my-3">
                            <div class="cos-section-title">Send to Staff</div>
                            <div class="row g-3 mb-3">
                                <div class="col-12">
                                    <label class="form-label small fw-semibold">Staff Personal Email</label>
                                    <div class="cos-input-wrap">
                                        <i class="fas fa-envelope cos-input-icon"></i>
                                        <input type="email" name="send_email_to" id="sendEmailTo"
                                               class="form-control form-control-sm @error('send_email_to') is-invalid @enderror"
                                               value="{{ old('send_email_to') }}"
                                               placeholder="Personal email to send the certificate PDF to">
                                    </div>
                                    @error('send_email_to')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    <div class="form-text">Loaded from the clearance form. If no staff email is available, it auto-fills with {{ $defaultRecipientEmail ?? 'HRTeam@ccbrt.org' }}. Leave blank to skip sending.</div>
                                </div>
                            </div>

                            <div class="cos-actions">

                                <div class="d-flex gap-2 flex-wrap justify-content-end">
                                    <a href="{{ route('certificate-of-service.index') }}" class="btn btn-outline-secondary btn-sm">Cancel</a>
                                    <button type="submit" class="btn btn-cos-primary btn-sm"><i class="fas fa-file-certificate me-1"></i>Issue Certificate</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
(() => {
    const staffSelect = document.getElementById('staffSelect');
    const deptField = document.getElementById('deptField');
    const positionHeld = document.getElementById('positionHeld');
    const joinDate = document.getElementById('joinDate');
    const lastDay = document.getElementById('lastDay');
    const sendEmailTo = document.getElementById('sendEmailTo');

    const previewName = document.getElementById('previewName');
    const previewCode = document.getElementById('previewCode');
    const previewStatus = document.getElementById('previewStatus');
    const previewDept = document.getElementById('previewDept');
    const previewPosition = document.getElementById('previewPosition');
    const previewJoin = document.getElementById('previewJoin');
    const previewEnd = document.getElementById('previewEnd');
    const defaultRecipientEmail = @json($defaultRecipientEmail ?? 'HRTeam@ccbrt.org');
    let emailTouchedManually = Boolean(sendEmailTo && sendEmailTo.value);

    const formatDate = (value) => {
        if (!value) {
            return '--';
        }

        const date = new Date(value + 'T00:00:00');
        if (Number.isNaN(date.getTime())) {
            return value;
        }

        return date.toLocaleDateString('en-GB', {
            day: '2-digit',
            month: 'short',
            year: 'numeric'
        });
    };

    const resolveRecipientEmail = (opt) => {
        if (!opt || !opt.value) {
            return '';
        }

        return opt.dataset.personalEmail || opt.dataset.fallbackEmail || defaultRecipientEmail || '';
    };

    const updatePreview = () => {
        if (!staffSelect) {
            return;
        }

        const opt = staffSelect.options[staffSelect.selectedIndex];
        if (!opt || !opt.value) {
            previewName.textContent = 'No staff selected';
            previewCode.textContent = 'Choose a staff member to preview their employment details.';
            previewStatus.textContent = 'Pending';
            previewStatus.classList.remove('is-selected');
            previewStatus.classList.add('is-pending');
            previewDept.textContent = '--';
            previewPosition.textContent = '--';
            previewJoin.textContent = '--';
            previewEnd.textContent = '--';
            if (sendEmailTo && !emailTouchedManually) {
                sendEmailTo.value = '';
            }
            return;
        }

        deptField.value = opt.dataset.dept || '';
        positionHeld.value = opt.dataset.position || '';
        joinDate.value = opt.dataset.join || '';
        lastDay.value = opt.dataset.end || '';
        if (sendEmailTo && !emailTouchedManually) {
            sendEmailTo.value = resolveRecipientEmail(opt);
        }

        previewName.textContent = opt.dataset.name || opt.textContent.trim();
        previewCode.textContent = opt.dataset.code ? 'Staff code: ' + opt.dataset.code : 'No staff code available';
        previewStatus.textContent = 'Selected';
        previewStatus.classList.remove('is-pending');
        previewStatus.classList.add('is-selected');
        previewDept.textContent = opt.dataset.dept || '--';
        previewPosition.textContent = opt.dataset.position || '--';
        previewJoin.textContent = formatDate(opt.dataset.join);
        previewEnd.textContent = formatDate(opt.dataset.end);
    };

    if (sendEmailTo) {
        sendEmailTo.addEventListener('input', () => {
            emailTouchedManually = true;
        });
    }

    if (staffSelect && window.jQuery && typeof window.jQuery.fn.select2 !== 'undefined') {
        window.jQuery(staffSelect).select2({
            placeholder: '-- Select staff --',
            allowClear: true,
            width: '100%'
        });

        window.jQuery(staffSelect).on('change', updatePreview);
    }

    if (staffSelect) {
        staffSelect.addEventListener('change', updatePreview);
        updatePreview();
    }
})();
</script>
@endpush
@endsection
