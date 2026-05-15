@extends('layouts.template')

@section('breadcrumb')
    @include('sweetalert::alert')
@endsection

@section('content')
    @php
        $statusClass = 'warning';
        $statusText = ucfirst(str_replace('_', ' ', $requisition->status));
        if ($requisition->status === 'approved') {
            $statusClass = 'success';
            $statusText = 'Approved';
        } elseif (in_array($requisition->status, ['rejected', 'rejected_for_editing', 'filed'])) {
            $statusClass = 'danger';
            if ($requisition->status === 'rejected_for_editing') $statusText = 'Rejected for Editing';
            elseif ($requisition->status === 'filed') $statusText = 'Filed';
            else $statusText = 'Rejected';
        } elseif (str_starts_with($requisition->status, 'pending_')) {
            $statusClass = 'warning';
        }

        $wfSteps = [
            ['label'=>'Submitted',  'done'=> true, 'icon'=>'fa-paper-plane'],
            ['label'=>'Payroll',    'done'=> (bool)$requisition->payroll_accountant_id, 'icon'=>'fa-calculator'],
            ['label'=>'HEC',        'done'=> (bool)$requisition->hec_reviewer_id || $requisition->isInitiatedByHEC(), 'icon'=>'fa-users'],
            ['label'=>'CFO',        'done'=> (bool)$requisition->cfo_id, 'icon'=>'fa-chart-line'],
            ['label'=>'CEO',        'done'=> (bool)$requisition->ceo_id, 'icon'=>'fa-crown'],
            ['label'=>'HR Final',   'done'=> (bool)$requisition->hr_id, 'icon'=>'fa-user-check'],
        ];
        $activeFound = false;
        foreach($wfSteps as &$s) {
            if(!$s['done'] && !$activeFound) { $s['state']='active'; $activeFound=true; }
            elseif($s['done'])              { $s['state']='done'; }
            else                            { $s['state']='pending'; }
        }
        unset($s);

        $skipHecSection = in_array($requisition->status, ['approved','rejected','filed','pending_cfo','pending_ceo','pending_hr'])
            || ($requisition->isInitiatedByHEC() && $requisition->hasBudget());
        $showHecSection = ($requisition->status === 'pending_hec' || $canReviewHEC || $requisition->hec_reviewer_id || $requisition->isInitiatedByHEC()) && !$skipHecSection;
    @endphp

    <div class="page-wrapper">
        <div class="content container-fluid">
            <br>
            <!-- Page Header -->
            <div class="page-header">
                <div class="row">
                    <div class="col-sm-12">
                        <div class="page-sub-header">
                            <h3 class="page-title">Recruitment Requisition Form</h3>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ── ALERTS ── --}}
            @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show mb-3" style="font-size:0.88rem;">
                <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            @endif
            @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show mb-3" style="font-size:0.88rem;">
                <i class="fas fa-exclamation-circle me-2"></i>{{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            @endif
            @if($requisition->status === 'rejected_for_editing')
            <div class="alert alert-warning alert-dismissible fade show mb-3" style="font-size:0.88rem;">
                @if($requisition->isExpired())
                    <i class="fas fa-clock me-2"></i><strong>Expired:</strong> The one-month editing period has passed.
                @else
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <strong>Rejected</strong> at <strong>{{ ucfirst($requisition->rejection_stage) }}</strong> stage.
                    @if($requisition->getDaysUntilExpiration() !== null)
                        <strong>{{ $requisition->getDaysUntilExpiration() }} days</strong> left to edit.
                    @endif
                    @if($requisition->rejection_reason) — {{ $requisition->rejection_reason }} @endif
                @endif
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            @endif

            <div class="row">
                <div class="col-md-12">

                    <!-- Request Summary Card -->
                    <div class="card shadow-sm mb-4">
                        <div class="card-header bg-white border-bottom">
                            <h5 class="card-title mb-0 section-title">
                                <i class="fas fa-info-circle me-2"></i>Request Summary
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-3">
                                    <div class="mb-3">
                                        <label class="text-muted small">Reference</label>
                                        <p class="mb-0"><strong style="color: #007A33; font-size: 1.05rem;">{{ $requisition->access_id }}</strong></p>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="mb-3">
                                        <label class="text-muted small">Status</label>
                                        <p class="mb-0">
                                            <span class="badge badge-{{ $statusClass }}" style="font-size: 0.9rem; padding: 0.5em 0.75em; font-weight: 600;">{{ $statusText }}</span>
                                        </p>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="mb-3">
                                        <label class="text-muted small">Submitted Date</label>
                                        <p class="mb-0"><strong>{{ $requisition->created_at->format('d F Y, H:i') }}</strong></p>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="mb-3">
                                        <label class="text-muted small">Position Type</label>
                                        <p class="mb-0">
                                            <span class="badge badge-info" style="font-size: 0.85rem;">{{ $requisition->getPositionTypeLabel() }}</span>
                                        </p>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="mb-3">
                                        <label class="text-muted small">Department</label>
                                        <p class="mb-0"><strong>{{ $requisition->dept_name ?? 'N/A' }}</strong></p>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="mb-3">
                                        <label class="text-muted small">Initiated By</label>
                                        <p class="mb-0"><strong>{{ $requisition->initiator_name }}</strong></p>
                                        <small class="text-muted">{{ ucfirst(str_replace('_',' ',$requisition->initiator_type)) }}</small>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="mb-3">
                                        <label class="text-muted small">Signature</label>
                                        <p class="mb-0">
                                            @php $initSig = $requisition->user?->signature; @endphp
                                            @if(!empty($initSig))
                                            <img src="data:image/png;base64,{{ $initSig }}" alt="Signature" style="max-height:50px; max-width:130px; border:1px solid #e9ecef; border-radius:4px; padding:3px;">
                                            @else
                                            <span class="text-muted">—</span>
                                            @endif
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Workflow Progress Card -->
                    <div class="card shadow-sm mb-4">
                        <div class="card-header bg-white border-bottom">
                            <h5 class="card-title mb-0 section-title">
                                <i class="fas fa-tasks me-2"></i>Approval Workflow
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="wf-stepper-inner">
                                @foreach($wfSteps as $si => $step)
                                <div class="wf-step-item {{ $step['state'] }}">
                                    <div class="wf-step-dot {{ $step['state'] }}">
                                        @if($step['state']==='done')
                                            <i class="fas fa-check" style="font-size:0.7rem;"></i>
                                        @elseif($step['state']==='active')
                                            <i class="fas {{ $step['icon'] }}" style="font-size:0.7rem;"></i>
                                        @else
                                            <span>{{ $si+1 }}</span>
                                        @endif
                                    </div>
                                    <span class="wf-step-lbl {{ $step['state'] }}">{{ $step['label'] }}</span>
                                </div>
                                @endforeach
                            </div>
                        </div>
                    </div>

                    <!-- Position Details Card -->
                    <div class="card shadow-sm mb-4">
                        <div class="card-header bg-white border-bottom">
                            <h5 class="card-title mb-0 section-title">
                                <i class="fas fa-briefcase me-2"></i>Position Details
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label class="text-muted small">Department</label>
                                        <p class="mb-0"><strong>{{ $requisition->dept_name ?? 'N/A' }}</strong></p>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label class="text-muted small">Responsibility Centre</label>
                                        <p class="mb-0"><strong>{{ $requisition->responsibility_centre ?? '—' }}</strong></p>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label class="text-muted small">Reporting Line</label>
                                        <p class="mb-0"><strong>{{ $requisition->reporting_line ?? '—' }}</strong></p>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label class="text-muted small">Contract Type</label>
                                        <p class="mb-0"><strong>{{ $requisition->getContractTypeLabel() }}</strong></p>
                                    </div>
                                </div>

                                @if($requisition->position_type !== 'new_position')
                                @php
                                    $displayNames = null;
                                    if(!empty($requisition->employee_names) && is_array($requisition->employee_names)) {
                                        $displayNames = $requisition->employee_names;
                                    } elseif(!empty($requisition->employee_ids) && is_array($requisition->employee_ids)) {
                                        $displayNames = \App\Models\User::whereIn('id',$requisition->employee_ids)->get()
                                            ->map(fn($u) => trim($u->fname.' '.($u->mname??'').' '.$u->lname))->toArray();
                                    } elseif($requisition->employee_id) {
                                        $emp = \App\Models\User::find($requisition->employee_id);
                                        $displayNames = $emp ? [trim($emp->fname.' '.($emp->mname??'').' '.$emp->lname)] : ($requisition->employee_name ? [$requisition->employee_name] : null);
                                    } elseif($requisition->employee_name) {
                                        $displayNames = [$requisition->employee_name];
                                    }
                                    if(empty($displayNames) && $requisition->isInitiatedByHEC() && $requisition->lineManager) {
                                        $displayNames = [trim($requisition->lineManager->fname.' '.($requisition->lineManager->mname??'').' '.$requisition->lineManager->lname)];
                                    }
                                @endphp
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label class="text-muted small">Employee(s)</label>
                                        <p class="mb-0">
                                            @if(!empty($displayNames))
                                                @foreach($displayNames as $dn)
                                                <strong><i class="fas fa-user-circle text-muted me-1" style="font-size:0.75rem;"></i>{{ $dn }}</strong><br>
                                                @endforeach
                                            @else
                                            <span class="text-muted">—</span>
                                            @endif
                                        </p>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label class="text-muted small">Contract End Date</label>
                                        <p class="mb-0"><strong>{{ $requisition->current_contract_end_date ? $requisition->current_contract_end_date->format('d M Y') : '—' }}</strong></p>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label class="text-muted small">Job Title</label>
                                        <p class="mb-0"><strong>{{ $requisition->jobTitle?->job_title ?? '—' }}</strong></p>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label class="text-muted small">Required Start Date</label>
                                        <p class="mb-0"><strong>{{ $requisition->required_start_date ? $requisition->required_start_date->format('d M Y') : '—' }}</strong></p>
                                    </div>
                                </div>
                                @else
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label class="text-muted small">Proposed Position</label>
                                        <p class="mb-0"><strong>{{ $requisition->new_job_title ?? '—' }}</strong></p>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label class="text-muted small">Required Start Date</label>
                                        <p class="mb-0"><strong>{{ $requisition->required_start_date ? $requisition->required_start_date->format('d M Y') : '—' }}</strong></p>
                                    </div>
                                </div>
                                @endif
                            </div>

                            @if($requisition->job_description_path)
                            <div class="mt-2 p-3" style="border-left: 4px solid #007A33; background-color: #f8f9fa; border-radius: 4px;">
                                <strong><i class="fas fa-file-pdf me-2" style="color: #007A33;"></i>Job Description</strong>
                                <a href="{{ route('requisitions.download-jd', $requisition->access_id) }}"
                                   class="btn btn-sm btn-outline-success ms-3" style="font-size:0.82rem;">
                                    <i class="fas fa-download me-1"></i> Download PDF
                                </a>
                            </div>
                            @endif
                        </div>
                    </div>

                    <!-- Reasoning & Justification Card -->
                    <div class="card shadow-sm mb-4">
                        <div class="card-header bg-white border-bottom">
                            <h5 class="card-title mb-0 section-title">
                                <i class="fas fa-clipboard-list me-2"></i>Reasoning & Justification
                            </h5>
                        </div>
                        <div class="card-body">
                            <h6 class="mb-3" style="font-weight: 600; color: #444;">
                                <i class="fas fa-tags me-2" style="color: #007A33;"></i>Conditions
                            </h6>
                            <div class="d-flex flex-wrap gap-2 mb-4">
                                @if($requisition->condition_medical_operational)
                                <span class="badge" style="background: #f0fdf4; color: #166534; border: 1px solid #bbf7d0; padding: 8px 14px; font-size: 0.85rem;">
                                    <i class="fas fa-medkit text-danger me-1"></i> Medical/Operational needs
                                </span>
                                @endif
                                @if($requisition->condition_safety_reputational)
                                <span class="badge" style="background: #f0fdf4; color: #166534; border: 1px solid #bbf7d0; padding: 8px 14px; font-size: 0.85rem;">
                                    <i class="fas fa-shield-alt text-warning me-1"></i> Safety/Reputational risks
                                </span>
                                @endif
                                @if($requisition->condition_legal_requirement)
                                <span class="badge" style="background: #f0fdf4; color: #166534; border: 1px solid #bbf7d0; padding: 8px 14px; font-size: 0.85rem;">
                                    <i class="fas fa-gavel me-1" style="color:#007A33;"></i> Legal requirement
                                </span>
                                @endif
                                @if($requisition->condition_financial_loss)
                                <span class="badge" style="background: #f0fdf4; color: #166534; border: 1px solid #bbf7d0; padding: 8px 14px; font-size: 0.85rem;">
                                    <i class="fas fa-chart-line text-danger me-1"></i> Financial loss
                                </span>
                                @endif
                                @if($requisition->condition_increase_income)
                                <span class="badge" style="background: #f0fdf4; color: #166534; border: 1px solid #bbf7d0; padding: 8px 14px; font-size: 0.85rem;">
                                    <i class="fas fa-money-bill-wave text-success me-1"></i> Increase income
                                </span>
                                @endif
                                @if(!$requisition->condition_medical_operational && !$requisition->condition_safety_reputational &&
                                    !$requisition->condition_legal_requirement && !$requisition->condition_financial_loss &&
                                    !$requisition->condition_increase_income)
                                <span class="text-muted">No conditions selected</span>
                                @endif
                            </div>

                            <h6 class="mb-3" style="font-weight: 600; color: #444;">
                                <i class="fas fa-align-left me-2" style="color: #007A33;"></i>Elaborate Justification
                            </h6>
                            <div class="p-3" style="background-color: #f8f9fa; border-left: 4px solid #007A33; border-radius: 4px; line-height: 1.7;">
                                {{ $requisition->elaborate_reason ?? 'No reason provided' }}
                            </div>
                        </div>
                    </div>

                    <!-- Payroll Accountant Review Card -->
                    <div class="card shadow-sm mb-4">
                        <div class="card-header bg-white border-bottom d-flex align-items-center justify-content-between">
                            <h5 class="card-title mb-0 section-title">
                                <i class="fas fa-calculator me-2"></i>Payroll Accountant Review
                            </h5>
                            <div>
                                @if($requisition->payroll_accountant_id)
                                <span class="badge badge-success" style="font-size:0.8rem;"><i class="fas fa-check me-1"></i>Reviewed</span>
                                @elseif($canReviewPayroll)
                                <span class="badge badge-primary" style="font-size:0.8rem;"><i class="fas fa-pen me-1"></i>Your Turn</span>
                                @else
                                <span class="badge badge-warning" style="font-size:0.8rem;"><i class="fas fa-clock me-1"></i>Pending</span>
                                @endif
                            </div>
                        </div>
                        <div class="card-body">
                            @if($canReviewPayroll)
                            <form action="{{ route('requisitions.payroll-review', $requisition->access_id) }}" method="POST">
                                @csrf
                                <div class="row g-3">
                                    <div class="col-md-4">
                                        <label class="form-label" style="font-weight: 600;">Position Approved in Budget <span class="text-danger">*</span></label>
                                        <div class="d-flex gap-3 mt-1">
                                            <div class="form-check">
                                                <input type="radio" name="budget_approved" id="budget_yes" value="1" class="form-check-input" required>
                                                <label class="form-check-label" for="budget_yes">Yes</label>
                                            </div>
                                            <div class="form-check">
                                                <input type="radio" name="budget_approved" id="budget_no" value="0" class="form-check-input">
                                                <label class="form-check-label" for="budget_no">No</label>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label" style="font-weight: 600;">Funding Available</label>
                                        <div class="d-flex gap-3 mt-1">
                                            <div class="form-check">
                                                <input type="radio" name="funding_available" id="funding_yes" value="1" class="form-check-input">
                                                <label class="form-check-label" for="funding_yes">Yes</label>
                                            </div>
                                            <div class="form-check">
                                                <input type="radio" name="funding_available" id="funding_no" value="0" class="form-check-input">
                                                <label class="form-check-label" for="funding_no">No</label>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label" style="font-weight: 600;">Max Monthly Budget (TZS)</label>
                                        <input type="number" name="max_monthly_budget" class="form-control" step="0.01" min="0">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label" style="font-weight: 600;">Donor Code</label>
                                        <input type="text" name="donor_code" class="form-control">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label" style="font-weight: 600;">Activity Code</label>
                                        <input type="text" name="activity_code" class="form-control">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label" style="font-weight: 600;">Comment</label>
                                        <textarea name="payroll_comment" class="form-control" rows="2"></textarea>
                                    </div>
                                    <div class="col-12">
                                        <button type="submit" class="btn btn-success js-submit-review">
                                            <i class="fas fa-check me-1"></i> Submit Review
                                        </button>
                                    </div>
                                </div>
                            </form>
                            @elseif($requisition->payroll_accountant_id)
                            <div class="row">
                                <div class="col-md-3">
                                    <div class="mb-3">
                                        <label class="text-muted small">Budget Approved</label>
                                        <p class="mb-0"><span class="badge badge-{{ $requisition->budget_approved ? 'success' : 'danger' }}">{{ $requisition->budget_approved ? 'Yes' : 'No' }}</span></p>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="mb-3">
                                        <label class="text-muted small">Funding Available</label>
                                        <p class="mb-0">
                                            @if($requisition->funding_available !== null)
                                            <span class="badge badge-{{ $requisition->funding_available ? 'success' : 'danger' }}">{{ $requisition->funding_available ? 'Yes' : 'No' }}</span>
                                            @else <span class="text-muted">—</span>
                                            @endif
                                        </p>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="mb-3">
                                        <label class="text-muted small">Max Monthly Budget</label>
                                        <p class="mb-0"><strong>{{ $requisition->max_monthly_budget ? number_format($requisition->max_monthly_budget,2).' TZS' : '—' }}</strong></p>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="mb-3">
                                        <label class="text-muted small">Donor / Activity Code</label>
                                        <p class="mb-0"><strong>{{ $requisition->donor_code ?? '—' }} / {{ $requisition->activity_code ?? '—' }}</strong></p>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label class="text-muted small">Reviewed By</label>
                                        <p class="mb-0"><strong>{{ $requisition->payrollAccountant?->fname }} {{ $requisition->payrollAccountant?->lname }}</strong></p>
                                        <small class="text-muted">{{ $requisition->payroll_reviewed_at?->format('d M Y, H:i') }}</small>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label class="text-muted small">Signature</label>
                                        <p class="mb-0">
                                            @php $payrollSig = $requisition->payrollAccountant?->signature; @endphp
                                            @if(!empty($payrollSig))
                                            <img src="data:image/png;base64,{{ $payrollSig }}" alt="Signature" style="max-height:50px; max-width:130px; border:1px solid #e9ecef; border-radius:4px; padding:3px;">
                                            @else <span class="text-muted">—</span>
                                            @endif
                                        </p>
                                    </div>
                                </div>
                                @if($requisition->payroll_comment)
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label class="text-muted small">Comment</label>
                                        <p class="mb-0" style="font-style:italic; color:#555;">{{ $requisition->payroll_comment }}</p>
                                    </div>
                                </div>
                                @endif
                            </div>
                            @else
                            <div class="p-3 text-center" style="background: #fafafa; border: 1px dashed #dee2e6; border-radius: 8px;">
                                <i class="fas fa-hourglass-half text-muted me-2"></i>
                                <span class="text-muted">Awaiting Payroll Accountant review.</span>
                            </div>
                            @endif
                        </div>
                    </div>

                    <!-- HEC Member Review Card -->
                    @if($showHecSection || $requisition->hec_reviewer_id || $requisition->isInitiatedByHEC())
                    <div class="card shadow-sm mb-4">
                        <div class="card-header bg-white border-bottom d-flex align-items-center justify-content-between">
                            <h5 class="card-title mb-0 section-title">
                                <i class="fas fa-users me-2"></i>HEC Member Review
                            </h5>
                            <div>
                                @if($requisition->hec_reviewer_id)
                                <span class="badge badge-success" style="font-size:0.8rem;"><i class="fas fa-check me-1"></i>Reviewed</span>
                                @elseif($canReviewHEC)
                                <span class="badge badge-primary" style="font-size:0.8rem;"><i class="fas fa-pen me-1"></i>Your Turn</span>
                                @elseif($requisition->status === 'pending_hec')
                                <span class="badge badge-warning" style="font-size:0.8rem;"><i class="fas fa-clock me-1"></i>Pending</span>
                                @endif
                            </div>
                        </div>
                        <div class="card-body">
                            @if($canReviewHEC)
                            <form action="{{ route('requisitions.hec-review', $requisition->access_id) }}" method="POST">
                                @csrf
                                <label class="form-label" style="font-weight: 600;">
                                    @if($requisition->isInitiatedByLineManager()) Decision @else Financial Decision @endif
                                    <span class="text-danger">*</span>
                                </label>
                                <div class="row g-2 mb-3">
                                    @if($requisition->isInitiatedByLineManager())
                                    <div class="col-md-6">
                                        <label class="dec-card w-100" for="hec_no_objection">
                                            <input type="radio" name="hec_financial_decision" id="hec_no_objection" value="no_objection">
                                            <div>
                                                <div class="dec-label"><i class="fas fa-check-circle text-success me-1"></i>No Objection</div>
                                                <div class="dec-sub">Send directly to HR</div>
                                            </div>
                                        </label>
                                    </div>
                                    @if(!$requisition->hasBudget() || $requisition->position_type === 'new_position')
                                    <div class="col-md-6">
                                        <label class="dec-card w-100" for="hec_no_objection_cfo_ceo">
                                            <input type="radio" name="hec_financial_decision" id="hec_no_objection_cfo_ceo" value="no_objection_cfo_ceo">
                                            <div>
                                                <div class="dec-label"><i class="fas fa-level-up-alt me-1" style="color:#007A33;"></i>No Objection — Escalate</div>
                                                <div class="dec-sub">Send to CFO &amp; CEO for approval</div>
                                            </div>
                                        </label>
                                    </div>
                                    @endif
                                    <div class="col-md-6">
                                        <label class="dec-card danger w-100" for="hec_objection">
                                            <input type="radio" name="hec_financial_decision" id="hec_objection" value="objection">
                                            <div>
                                                <div class="dec-label"><i class="fas fa-times-circle text-danger me-1"></i>Objection</div>
                                                <div class="dec-sub">File &amp; end process</div>
                                            </div>
                                        </label>
                                    </div>
                                    @endif
                                    <div class="col-md-6">
                                        <label class="dec-card danger w-100" for="hec_reject_for_editing">
                                            <input type="radio" name="action" id="hec_reject_for_editing" value="reject">
                                            <div>
                                                <div class="dec-label"><i class="fas fa-undo text-warning me-1"></i>Reject for Editing</div>
                                                <div class="dec-sub">Send back to initiator</div>
                                            </div>
                                        </label>
                                    </div>
                                </div>

                                <div id="hec_rejection_reason" style="display:none;" class="mb-3">
                                    <label class="form-label" style="font-weight: 600;">Rejection Reason <span class="text-danger">*</span></label>
                                    <textarea name="rejection_reason" id="hec_rejection_reason_text" class="form-control" rows="2"
                                              placeholder="Minimum 10 characters"></textarea>
                                </div>

                                @if(!$requisition->hasBudget())
                                <div id="proposedFundingField" style="display:none;" class="mb-3">
                                    <label class="form-label" style="font-weight: 600;">Proposed Funding Source <span class="text-danger">*</span></label>
                                    <input type="text" name="proposed_funding_source" id="proposed_funding_source" class="form-control">
                                </div>
                                @endif

                                <div class="mb-3 hec-approve-fields">
                                    <label class="form-label" style="font-weight: 600;">Comment (optional)</label>
                                    <textarea name="hec_comment" id="hec_comment" class="form-control" rows="2"></textarea>
                                </div>

                                <button type="submit" class="btn btn-success js-submit-review">
                                    <i class="fas fa-check me-1"></i> Submit Review
                                </button>
                            </form>
                            @elseif($requisition->hec_reviewer_id || $requisition->isInitiatedByHEC())
                            <div class="row">
                                @if($requisition->hec_reviewer_id)
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label class="text-muted small">Decision</label>
                                        <p class="mb-0">
                                            <span class="badge badge-{{ in_array($requisition->hec_financial_decision,['no_objection','no_objection_cfo_ceo','no_financial_implication','proposed_funding']) ? 'success' : 'danger' }}">
                                                {{ ucfirst(str_replace('_',' ',$requisition->hec_financial_decision)) }}
                                            </span>
                                        </p>
                                    </div>
                                </div>
                                @endif
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label class="text-muted small">{{ $requisition->hec_reviewer_id ? 'Reviewed By' : 'Initiated By' }}</label>
                                        <p class="mb-0"><strong>{{ $requisition->hec_reviewer_id ? ($requisition->hecReviewer?->fname.' '.$requisition->hecReviewer?->lname) : $requisition->initiator_name }}</strong></p>
                                        <small class="text-muted">{{ $requisition->hec_reviewed_at?->format('d M Y, H:i') ?? $requisition->created_at?->format('d M Y, H:i') }}</small>
                                    </div>
                                </div>
                                @if($requisition->proposed_funding_source)
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label class="text-muted small">Proposed Funding</label>
                                        <p class="mb-0"><strong>{{ $requisition->proposed_funding_source }}</strong></p>
                                    </div>
                                </div>
                                @endif
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label class="text-muted small">Signature</label>
                                        <p class="mb-0">
                                            @php $hecSig = $requisition->hec_reviewer_id ? $requisition->hecReviewer?->signature : $requisition->user?->signature; @endphp
                                            @if(!empty($hecSig))
                                            <img src="data:image/png;base64,{{ $hecSig }}" alt="Signature" style="max-height:50px; max-width:130px; border:1px solid #e9ecef; border-radius:4px; padding:3px;">
                                            @else <span class="text-muted">—</span>
                                            @endif
                                        </p>
                                    </div>
                                </div>
                                @if($requisition->hec_comment)
                                <div class="col-md-12">
                                    <div class="mb-3">
                                        <label class="text-muted small">Comment</label>
                                        <p class="mb-0" style="font-style:italic; color:#555;">{{ $requisition->hec_comment }}</p>
                                    </div>
                                </div>
                                @endif
                            </div>
                            @else
                            <div class="p-3 text-center" style="background: #fafafa; border: 1px dashed #dee2e6; border-radius: 8px;">
                                <i class="fas fa-hourglass-half text-muted me-2"></i>
                                <span class="text-muted">{{ $requisition->status === 'pending_cfo' ? 'Awaiting CFO approval.' : 'Awaiting HEC Member review.' }}</span>
                            </div>
                            @endif
                        </div>
                    </div>
                    @endif

                    <!-- CFO Approval Card -->
                    @if($canReviewCFO || $requisition->cfo_id)
                    <div class="card shadow-sm mb-4">
                        <div class="card-header bg-white border-bottom d-flex align-items-center justify-content-between">
                            <h5 class="card-title mb-0 section-title">
                                <i class="fas fa-chart-line me-2"></i>CFO Approval
                            </h5>
                            <div>
                                @if($requisition->cfo_id)
                                <span class="badge badge-{{ $requisition->cfo_decision === 'approved' ? 'success' : 'danger' }}" style="font-size:0.8rem;">{{ ucfirst($requisition->cfo_decision ?? '') }}</span>
                                @elseif($canReviewCFO)
                                <span class="badge badge-primary" style="font-size:0.8rem;"><i class="fas fa-pen me-1"></i>Your Turn</span>
                                @endif
                            </div>
                        </div>
                        <div class="card-body">
                            @if($canReviewCFO)
                            <form action="{{ route('requisitions.cfo-review', $requisition->access_id) }}" method="POST">
                                @csrf
                                <div class="row g-2 mb-3">
                                    <div class="col-md-6">
                                        <label class="dec-card w-100" for="cfo_approve">
                                            <input type="radio" name="cfo_decision" id="cfo_approve" value="approved" required checked>
                                            <div><div class="dec-label"><i class="fas fa-check-circle text-success me-1"></i>Approve</div></div>
                                        </label>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="dec-card danger w-100" for="cfo_reject">
                                            <input type="radio" name="cfo_decision" id="cfo_reject" value="rejected">
                                            <div><div class="dec-label"><i class="fas fa-times-circle text-danger me-1"></i>Reject for Editing</div></div>
                                        </label>
                                    </div>
                                </div>
                                <div id="cfo_rejection_reason" style="display:none;" class="mb-3">
                                    <label class="form-label" style="font-weight: 600;">Rejection Reason <span class="text-danger">*</span></label>
                                    <textarea name="rejection_reason" id="cfo_rejection_reason_text" class="form-control" rows="2"></textarea>
                                </div>
                                <div class="row g-3 cfo-approve-fields mb-3">
                                    <div class="col-md-6">
                                        <label class="form-label" style="font-weight: 600;">Financing Confirmation <span class="text-danger">*</span></label>
                                        <textarea name="cfo_financing_confirmation" class="form-control" rows="2"></textarea>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label" style="font-weight: 600;">Financing Code</label>
                                        <input type="text" name="cfo_financing_code" class="form-control">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label" style="font-weight: 600;">Comment</label>
                                        <textarea name="cfo_comment" class="form-control" rows="2"></textarea>
                                    </div>
                                </div>
                                <button type="submit" class="btn btn-success js-submit-review">
                                    <i class="fas fa-check me-1"></i> Submit Review
                                </button>
                            </form>
                            @elseif($requisition->cfo_id)
                            <div class="row">
                                <div class="col-md-3">
                                    <div class="mb-3">
                                        <label class="text-muted small">Decision</label>
                                        <p class="mb-0"><span class="badge badge-{{ $requisition->cfo_decision === 'approved' ? 'success' : 'danger' }}">{{ ucfirst($requisition->cfo_decision) }}</span></p>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="mb-3">
                                        <label class="text-muted small">Financing Confirmation</label>
                                        <p class="mb-0"><strong>{{ $requisition->cfo_financing_confirmation ?? '—' }}</strong></p>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="mb-3">
                                        <label class="text-muted small">Financing Code</label>
                                        <p class="mb-0"><strong>{{ $requisition->cfo_financing_code ?? '—' }}</strong></p>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="mb-3">
                                        <label class="text-muted small">Reviewed By</label>
                                        <p class="mb-0"><strong>{{ $requisition->cfo?->fname }} {{ $requisition->cfo?->lname }}</strong></p>
                                        <small class="text-muted">{{ $requisition->cfo_reviewed_at?->format('d M Y, H:i') }}</small>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label class="text-muted small">Signature</label>
                                        <p class="mb-0">
                                            @php $cfoSig = $requisition->cfo?->signature; @endphp
                                            @if(!empty($cfoSig))
                                            <img src="data:image/png;base64,{{ $cfoSig }}" alt="Signature" style="max-height:50px; max-width:130px; border:1px solid #e9ecef; border-radius:4px; padding:3px;">
                                            @else <span class="text-muted">—</span>
                                            @endif
                                        </p>
                                    </div>
                                </div>
                            </div>
                            @endif
                        </div>
                    </div>
                    @endif

                    <!-- CEO Approval Card -->
                    @if($requisition->status === 'pending_ceo' || $requisition->ceo_id)
                    <div class="card shadow-sm mb-4">
                        <div class="card-header bg-white border-bottom d-flex align-items-center justify-content-between">
                            <h5 class="card-title mb-0 section-title">
                                <i class="fas fa-crown me-2"></i>CEO Approval
                            </h5>
                            <div>
                                @if($requisition->ceo_id)
                                <span class="badge badge-{{ $requisition->ceo_decision === 'approved' ? 'success' : 'danger' }}" style="font-size:0.8rem;">{{ ucfirst(str_replace('_',' ',$requisition->ceo_decision ?? '')) }}</span>
                                @elseif($canReviewCEO)
                                <span class="badge badge-primary" style="font-size:0.8rem;"><i class="fas fa-pen me-1"></i>Your Turn</span>
                                @endif
                            </div>
                        </div>
                        <div class="card-body">
                            @if($canReviewCEO)
                            <form action="{{ route('requisitions.ceo-review', $requisition->access_id) }}" method="POST">
                                @csrf
                                <div class="row g-2 mb-3">
                                    <div class="col-md-6">
                                        <label class="dec-card w-100" for="ceo_approve">
                                            <input type="radio" name="ceo_decision" id="ceo_approve" value="approved" required>
                                            <div><div class="dec-label"><i class="fas fa-check-circle text-success me-1"></i>Approved</div></div>
                                        </label>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="dec-card danger w-100" for="ceo_decline">
                                            <input type="radio" name="ceo_decision" id="ceo_decline" value="declined">
                                            <div>
                                                <div class="dec-label"><i class="fas fa-times-circle text-danger me-1"></i>Declined</div>
                                                <div class="dec-sub">Reject for editing</div>
                                            </div>
                                        </label>
                                    </div>
                                </div>
                                <div id="ceo_rejection_reason" style="display:none;" class="mb-3">
                                    <label class="form-label" style="font-weight: 600;">Rejection Reason <span class="text-danger">*</span></label>
                                    <textarea name="rejection_reason" id="ceo_rejection_reason_text" class="form-control" rows="2"></textarea>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label" style="font-weight: 600;">Comment</label>
                                    <textarea name="ceo_comment" class="form-control" rows="2"></textarea>
                                </div>
                                <button type="submit" class="btn btn-success js-submit-review">
                                    <i class="fas fa-check me-1"></i> Submit Review
                                </button>
                            </form>
                            @elseif($requisition->ceo_id)
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label class="text-muted small">Decision</label>
                                        <p class="mb-0"><span class="badge badge-{{ $requisition->ceo_decision === 'approved' ? 'success' : 'danger' }}">{{ ucfirst(str_replace('_',' ',$requisition->ceo_decision)) }}</span></p>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label class="text-muted small">Reviewed By</label>
                                        <p class="mb-0"><strong>{{ $requisition->ceo?->fname }} {{ $requisition->ceo?->lname }}</strong></p>
                                        <small class="text-muted">{{ $requisition->ceo_reviewed_at?->format('d M Y, H:i') }}</small>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label class="text-muted small">Signature</label>
                                        <p class="mb-0">
                                            @php $ceoSig = $requisition->ceo?->signature; @endphp
                                            @if(!empty($ceoSig))
                                            <img src="data:image/png;base64,{{ $ceoSig }}" alt="Signature" style="max-height:50px; max-width:130px; border:1px solid #e9ecef; border-radius:4px; padding:3px;">
                                            @else <span class="text-muted">—</span>
                                            @endif
                                        </p>
                                    </div>
                                </div>
                                @if($requisition->ceo_comment)
                                <div class="col-md-12">
                                    <div class="mb-3">
                                        <label class="text-muted small">Comment</label>
                                        <p class="mb-0" style="font-style:italic; color:#555;">{{ $requisition->ceo_comment }}</p>
                                    </div>
                                </div>
                                @endif
                            </div>
                            @else
                            <div class="p-3 text-center" style="background: #fafafa; border: 1px dashed #dee2e6; border-radius: 8px;">
                                <i class="fas fa-hourglass-half text-muted me-2"></i>
                                <span class="text-muted">Awaiting CEO approval.</span>
                            </div>
                            @endif
                        </div>
                    </div>
                    @endif

                    <!-- HR Final Approval Card -->
                    @if(in_array($requisition->status, ['pending_hr','approved','rejected','rejected_for_editing']) || $requisition->hr_id)
                    <div class="card shadow-sm mb-4">
                        <div class="card-header bg-white border-bottom d-flex align-items-center justify-content-between">
                            <h5 class="card-title mb-0 section-title">
                                <i class="fas fa-user-check me-2"></i>HR Final Approval
                            </h5>
                            <div>
                                @if($requisition->hr_id)
                                <span class="badge badge-{{ $requisition->hr_decision === 'approved' ? 'success' : 'danger' }}" style="font-size:0.8rem;">{{ ucfirst($requisition->hr_decision ?? '') }}</span>
                                @elseif($canReviewHR)
                                <span class="badge badge-primary" style="font-size:0.8rem;"><i class="fas fa-pen me-1"></i>Your Turn</span>
                                @endif
                            </div>
                        </div>
                        <div class="card-body">
                            @if($canReviewHR)
                            <form action="{{ route('requisitions.hr-review', $requisition->access_id) }}" method="POST" enctype="multipart/form-data">
                                @csrf
                                <div class="row g-2 mb-3">
                                    <div class="col-md-6">
                                        <label class="dec-card w-100" for="hr_approve">
                                            <input type="radio" name="hr_decision" id="hr_approve" value="approved" required checked>
                                            <div><div class="dec-label"><i class="fas fa-check-circle text-success me-1"></i>Approve</div></div>
                                        </label>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="dec-card danger w-100" for="hr_reject">
                                            <input type="radio" name="hr_decision" id="hr_reject" value="rejected">
                                            <div><div class="dec-label"><i class="fas fa-times-circle text-danger me-1"></i>Reject</div></div>
                                        </label>
                                    </div>
                                </div>
                                <div id="hr_rejection_reason" style="display:none;" class="mb-3">
                                    <label class="form-label" style="font-weight: 600;">Rejection Reason <span class="text-danger">*</span></label>
                                    <textarea name="rejection_reason" id="hr_rejection_reason_text" class="form-control" rows="2"></textarea>
                                </div>
                                <div class="row g-3 hr-approve-fields mb-3">
                                    <div class="col-md-6">
                                        <label class="form-label" style="font-weight: 600;">Comment</label>
                                        <textarea name="hr_comment" class="form-control" rows="2"></textarea>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label" style="font-weight: 600;">Attachment (optional, PDF)</label>
                                        <input type="file" name="hr_attachment" class="form-control" accept=".pdf">
                                    </div>
                                </div>
                                <button type="submit" class="btn btn-success js-submit-review">
                                    <i class="fas fa-check me-1"></i> Submit Final Decision
                                </button>
                            </form>
                            @elseif($requisition->hr_id)
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label class="text-muted small">Decision</label>
                                        <p class="mb-0"><span class="badge badge-{{ $requisition->hr_decision === 'approved' ? 'success' : 'danger' }}">{{ ucfirst($requisition->hr_decision) }}</span></p>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label class="text-muted small">Processed By</label>
                                        <p class="mb-0"><strong>{{ $requisition->hr?->fname }} {{ $requisition->hr?->lname }}</strong></p>
                                        <small class="text-muted">{{ $requisition->hr_reviewed_at?->format('d M Y, H:i') }}</small>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label class="text-muted small">Signature</label>
                                        <p class="mb-0">
                                            @php $hrSig = $requisition->hr?->signature; @endphp
                                            @if(!empty($hrSig))
                                            <img src="data:image/png;base64,{{ $hrSig }}" alt="Signature" style="max-height:50px; max-width:130px; border:1px solid #e9ecef; border-radius:4px; padding:3px;">
                                            @else <span class="text-muted">—</span>
                                            @endif
                                        </p>
                                    </div>
                                </div>
                                @if($requisition->hr_comment)
                                <div class="col-md-12">
                                    <div class="mb-3">
                                        <label class="text-muted small">Comment</label>
                                        <p class="mb-0" style="font-style:italic; color:#555;">{{ $requisition->hr_comment }}</p>
                                    </div>
                                </div>
                                @endif
                            </div>
                            @elseif($requisition->status === 'rejected_for_editing')
                            <div class="alert alert-warning mb-0 py-2">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                <strong>Rejected</strong> at <strong>{{ ucfirst($requisition->rejection_stage ?? 'Review') }}</strong> stage.
                                @if($requisition->rejection_reason) — {{ $requisition->rejection_reason }} @endif
                                @if($requisition->rejected_at && $requisition->rejectedBy)
                                <div class="text-muted" style="font-size:0.82rem; margin-top:3px;">
                                    By {{ $requisition->rejectedBy->fname }} {{ $requisition->rejectedBy->lname }}
                                    on {{ $requisition->rejected_at->format('d M Y, H:i') }}
                                </div>
                                @endif
                            </div>
                            @else
                            <div class="p-3 text-center" style="background: #fafafa; border: 1px dashed #dee2e6; border-radius: 8px;">
                                <i class="fas fa-hourglass-half text-muted me-2"></i>
                                <span class="text-muted">Awaiting HR final review.</span>
                            </div>
                            @endif
                        </div>
                    </div>
                    @endif

                    <!-- Action Buttons -->
                    <div class="card shadow-sm mb-4">
                        <div class="card-body">
                            <div class="d-flex gap-2 flex-wrap">
                                <button type="button" class="btn btn-secondary" onclick="window.history.back()">
                                    <i class="fas fa-arrow-left me-1"></i> Back
                                </button>

                                @if($requisition->status === 'rejected_for_editing' && $requisition->canBeEdited() && $isInitiator)
                                <a href="{{ route('requisitions.edit', $requisition->access_id) }}" class="btn btn-warning">
                                    <i class="fas fa-edit me-1"></i> Edit
                                </a>
                                <form action="{{ route('requisitions.resubmit', $requisition->access_id) }}" method="POST" class="d-inline"
                                      onsubmit="return confirm('Resubmit this requisition?')">
                                    @csrf
                                    <button class="btn btn-success">
                                        <i class="fas fa-paper-plane me-1"></i> Resubmit
                                    </button>
                                </form>
                                @endif

                                @if($requisition->job_description_path)
                                <a href="{{ route('requisitions.download-jd', $requisition->access_id) }}" class="btn btn-outline-success">
                                    <i class="fas fa-file-pdf me-1"></i> Download JD
                                </a>
                                @endif
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>

    <style>
        .page-wrapper {
            padding: 20px;
        }

        .text-muted {
            font-size: 0.95rem;
            color: #6c757d;
        }

        .card {
            border: none;
            border-radius: 8px;
            background-color: #ffffff;
        }

        .card-body {
            padding: 2rem;
        }

        .section-title {
            font-size: 1.15rem;
            font-weight: 600;
            color: #007A33;
            margin-bottom: 0;
            border-bottom: 2px solid #007A33;
            padding-bottom: 0.5rem;
        }

        .section-title i {
            color: #007A33;
        }

        .card-header.bg-white {
            background-color: #ffffff !important;
            border-bottom: 1px solid #e9ecef;
        }

        /* Workflow stepper */
        .wf-stepper-inner {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            position: relative;
        }
        .wf-stepper-inner::before {
            content: '';
            position: absolute;
            top: 15px;
            left: 0;
            right: 0;
            height: 2px;
            background: #e9ecef;
            z-index: 0;
        }
        .wf-step-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 6px;
            position: relative;
            z-index: 1;
            flex: 1;
        }
        .wf-step-dot {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.68rem;
            font-weight: 700;
            border: 3px solid #e9ecef;
            background: #fff;
            transition: all .2s;
        }
        .wf-step-dot.done    { background: #007A33; border-color: #007A33; color: #fff; }
        .wf-step-dot.active  { background: #fff; border-color: #007A33; color: #007A33; box-shadow: 0 0 0 4px rgba(0,122,51,0.15); }
        .wf-step-dot.pending { background: #f8f9fa; border-color: #dee2e6; color: #adb5bd; }
        .wf-step-lbl { font-size: 0.72rem; font-weight: 600; text-align: center; white-space: nowrap; }
        .wf-step-lbl.done    { color: #007A33; }
        .wf-step-lbl.active  { color: #005a25; }
        .wf-step-lbl.pending { color: #adb5bd; }
        .wf-step-item.done::after {
            content: '';
            position: absolute;
            top: 15px;
            left: 50%;
            right: -50%;
            height: 2px;
            background: #007A33;
            z-index: 0;
        }

        /* Decision cards */
        .dec-card {
            border: 2px solid #dee2e6;
            border-radius: 8px;
            padding: 10px 14px;
            cursor: pointer;
            transition: all .15s;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .dec-card:hover  { border-color: #007A33; background: #f0fdf4; }
        .dec-card.selected { border-color: #007A33; background: #f0fdf4; }
        .dec-card.danger:hover  { border-color: #dc3545; background: #fff5f5; }
        .dec-card.danger.selected { border-color: #dc3545; background: #fff5f5; }
        .dec-card input[type=radio] { width: 15px; height: 15px; accent-color: #007A33; }
        .dec-card .dec-label { font-size: 0.88rem; font-weight: 600; }
        .dec-card .dec-sub   { font-size: 0.78rem; color: #6c757d; }

        /* Table header override */
        .table thead th {
            color: white !important;
            background-color: #007A33 !important;
        }

        @media print {
            .header, .sidebar, .footer, .fixed-bottom, .page-header,
            .page-sub-header, .btn, button, .button-group, #loader,
            .loader, .swal2-container, .sweet-alert, nav, .navbar, .no-print {
                display: none !important;
            }
            body { margin: 0 !important; padding: 0 !important; background: #fff !important; }
            .main-wrapper, .content-wrapper { margin: 0 !important; padding: 0 !important; width: 100% !important; float: none !important; }
            .page-wrapper { padding: 0 !important; margin: 0 !important; }
            .content.container-fluid { padding: 0 !important; margin: 0 !important; max-width: 100% !important; width: 100% !important; }
            * { -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
            @page { size: A4 portrait; margin: 10mm; }
            .card { box-shadow: none !important; border: 1px solid #dee2e6 !important; }
            .card-body { padding: 1rem !important; }
            .card, .card-body { page-break-inside: avoid; }
            tr { page-break-inside: avoid; }
            img { max-width: 100% !important; }
        }
    </style>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {

    /* ── Decision card highlight ── */
    function wireDecCards(radioName) {
        document.querySelectorAll(`input[name="${radioName}"]`).forEach(radio => {
            const updateCards = () => {
                document.querySelectorAll(`input[name="${radioName}"]`).forEach(r => {
                    r.closest('.dec-card')?.classList.toggle('selected', r.checked);
                });
            };
            radio.addEventListener('change', updateCards);
            if (radio.checked) radio.closest('.dec-card')?.classList.add('selected');
        });
    }
    ['hec_financial_decision','action','cfo_decision','ceo_decision','hr_decision'].forEach(wireDecCards);

    /* ── HEC: show/hide rejection + proposed funding ── */
    const hecRejectRadio = document.getElementById('hec_reject_for_editing');
    const hecRejBlock    = document.getElementById('hec_rejection_reason');
    const hecApprFields  = document.querySelectorAll('.hec-approve-fields');
    const propFundField  = document.getElementById('proposedFundingField');
    const budgetApproved = {{ $requisition->budget_approved ? 'true' : 'false' }};

    function updateHecUI() {
        const isReject = hecRejectRadio?.checked;
        if (hecRejBlock) hecRejBlock.style.display = isReject ? 'block' : 'none';
        if (hecRejBlock?.querySelector('textarea')) {
            hecRejBlock.querySelector('textarea').required = !!isReject;
        }
        hecApprFields.forEach(f => f.style.display = isReject ? 'none' : '');
        const hecDec = document.querySelector('input[name="hec_financial_decision"]:checked');
        if (propFundField) {
            const show = !budgetApproved && hecDec?.value === 'proposed_funding';
            propFundField.style.display = show ? 'block' : 'none';
            if (propFundField.querySelector('input')) propFundField.querySelector('input').required = show;
        }
    }

    if (hecRejectRadio) {
        hecRejectRadio.addEventListener('change', function() {
            if (this.checked) {
                document.querySelectorAll('input[name="hec_financial_decision"]').forEach(r => { r.checked = false; });
                wireDecCards('hec_financial_decision');
            }
            updateHecUI();
        });
    }
    document.querySelectorAll('input[name="hec_financial_decision"]').forEach(r => {
        r.addEventListener('change', function() {
            if (this.checked && hecRejectRadio) {
                hecRejectRadio.checked = false;
                wireDecCards('action');
            }
            updateHecUI();
        });
    });
    updateHecUI();

    const hecForm = hecRejectRadio?.closest('form');
    if (hecForm) {
        hecForm.addEventListener('submit', function(e) {
            const hecDec = document.querySelector('input[name="hec_financial_decision"]:checked');
            const isReject = hecRejectRadio?.checked;
            if (!hecDec && !isReject) {
                e.preventDefault();
                alert('Please select one of these options.');
            }
        });
    }

    /* ── CFO: show rejection reason ── */
    document.querySelectorAll('input[name="cfo_decision"]').forEach(r => {
        r.addEventListener('change', function () {
            const block = document.getElementById('cfo_rejection_reason');
            const ta    = document.getElementById('cfo_rejection_reason_text');
            const show  = this.value === 'rejected';
            if (block) block.style.display = show ? 'block' : 'none';
            if (ta)    ta.required = show;
            document.querySelectorAll('.cfo-approve-fields').forEach(f => f.style.display = show ? 'none' : '');
        });
    });

    /* ── CEO: show rejection reason ── */
    document.querySelectorAll('input[name="ceo_decision"]').forEach(r => {
        r.addEventListener('change', function () {
            const block = document.getElementById('ceo_rejection_reason');
            const ta    = document.getElementById('ceo_rejection_reason_text');
            const show  = this.value === 'declined';
            if (block) block.style.display = show ? 'block' : 'none';
            if (ta)    ta.required = show;
        });
    });

    /* ── HR: show rejection reason ── */
    document.querySelectorAll('input[name="hr_decision"]').forEach(r => {
        r.addEventListener('change', function () {
            const block = document.getElementById('hr_rejection_reason');
            const ta    = document.getElementById('hr_rejection_reason_text');
            const show  = this.value === 'rejected';
            if (block) block.style.display = show ? 'block' : 'none';
            if (ta)    ta.required = show;
            document.querySelectorAll('.hr-approve-fields').forEach(f => f.style.display = show ? 'none' : '');
        });
    });

    /* ── Submit button loading state ── */
    document.querySelectorAll('.js-submit-review').forEach(btn => {
        btn.closest('form')?.addEventListener('submit', function () {
            if (this.dataset.submitting === 'true') return;
            this.dataset.submitting = 'true';
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Submitting…';
        });
    });
});
</script>
@endpush
