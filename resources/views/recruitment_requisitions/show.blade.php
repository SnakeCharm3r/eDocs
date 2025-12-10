@extends('layouts.template')

@section('breadcrumb')
    @include('sweetalert::alert')
@endsection

@section('content')
    @php
        $user = auth()->user();
        $isHOD = $user->hasRole('hod') && $requisition->created_by === $user->id;
        $isHEC = $user->hasAnyRole(['hec-cfo', 'hec-coo', 'hec-cms', 'hec-ccd']);
        $isCFO = $user->hasRole('cfo');
        $isCEO = $user->hasRole('ceo');
        $isHR = $user->hasRole('hr');

        // Determine if fields should be read-only
        $canEditHODSection = $isHOD && $requisition->status === 'draft';
        $canEditHECSection = $isHEC && in_array($requisition->status, ['submitted_by_hod', 'hec_review_in_progress']);
        $canEditCFOSection =
            $isCFO && $requisition->status === 'hec_no_objection_no_budget' && $requisition->needs_finance_review;
        $canEditCEOSection =
            $isCEO &&
            in_array($requisition->status, [
                'cfo_finance_confirmed',
                'hec_no_objection_in_budget',
                'ceo_review_in_progress',
            ]);
        $canEditHRSection =
            $isHR &&
            in_array($requisition->status, ['hec_no_objection_in_budget', 'ceo_approved', 'ready_for_hr_processing']);
    @endphp

    <style>
        .pdf-like-container {
            max-width: 1200px;
            margin: 0 auto;
            font-family: 'Arial', sans-serif;
            font-size: 14px;
            line-height: 1.6;
            border: 1px solid #ddd;
            padding: 30px;
            background: #fff;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            border-radius: 8px;
        }

        .pdf-header {
            margin-bottom: 20px;
            text-align: center;
            border-bottom: 2px solid #007A33;
            padding-bottom: 20px;
        }

        .pdf-header h3 {
            font-size: 24px;
            font-weight: bold;
            color: #007A33;
            margin: 0;
        }

        .pdf-section {
            margin-bottom: 30px;
        }

        .pdf-section h5 {
            font-size: 16px;
            font-weight: 600;
            text-transform: uppercase;
            color: #333;
            margin-bottom: 15px;
            border-bottom: 2px solid #007A33;
            padding-bottom: 5px;
        }

        .info-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
            font-size: 14px;
        }

        .info-table th,
        .info-table td {
            border: 1px solid #ddd;
            padding: 12px;
            text-align: left;
        }

        .info-table th {
            width: 30%;
            font-weight: 600;
            background: #f8f8f8;
            color: #333;
        }

        .info-table td {
            background: #fff;
        }

        .info-table td.readonly {
            background: #f5f5f5;
            color: #6c757d;
        }

        .badge-container {
            padding: 15px 0;
            margin-bottom: 20px;
            border-bottom: 1px solid #e9ecef;
        }

        .info-badge {
            display: inline-block;
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: 500;
            margin-right: 10px;
            margin-bottom: 10px;
        }

        .content-box {
            background: #f8f9fa;
            border-left: 4px solid #007A33;
            padding: 15px;
            border-radius: 5px;
            margin-top: 10px;
            color: #212529;
            line-height: 1.6;
        }

        .readonly-field {
            background: #f5f5f5 !important;
            color: #6c757d;
            cursor: not-allowed;
        }

        .action-section {
            background: #fff;
            border: 2px solid #007A33;
            border-radius: 8px;
            padding: 20px;
            margin-top: 20px;
        }

        .btn-primary {
            background-color: #007A33;
            border-color: #007A33;
        }

        .btn-primary:hover {
            background-color: #005a25;
            border-color: #005a25;
        }

        @media (max-width: 768px) {
            .pdf-like-container {
                padding: 15px;
            }
        }
    </style>

    <div class="page-wrapper">
        <div class="content container-fluid">
            <div class="page-header">
                <div class="row align-items-center">
                    <div class="col">
                        <h3 class="page-title">
                            <i class="fas fa-file-alt me-2"></i>Recruitment Requisition Form (HR.01)
                        </h3>
                    </div>
                    <div class="col-auto">
                        <a href="{{ route('recruitment-requisitions.index') }}" class="btn btn-secondary">
                            <i class="fas fa-arrow-left me-1"></i> Back to List
                        </a>
                        @if ($isHR)
                            <a href="{{ route('recruitment-requisitions.export-pdf', $requisition->id) }}"
                                class="btn btn-primary">
                                <i class="fas fa-file-pdf me-1"></i> Export PDF
                            </a>
                        @endif
                    </div>
                </div>
            </div>

            @if (session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            @if (session('error'))
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-circle me-2"></i>{{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            <!-- PDF-Like Content -->
            <div class="pdf-like-container">
                <!-- Custom Header -->
                <div class="pdf-header text-center border-bottom pb-3 mb-4">
                    <h3>Recruitment Requisition Form (HR.01)</h3>
                    <p class="mb-0">Reference No: RR-{{ str_pad($requisition->id, 6, '0', STR_PAD_LEFT) }}</p>
                </div>

                <!-- Status Badge -->
                <div class="badge-container">
                    <span class="info-badge"
                        style="background: #{{ $requisition->getStatusBadgeClass() === 'success' ? '28a745' : ($requisition->getStatusBadgeClass() === 'danger' ? 'dc3545' : ($requisition->getStatusBadgeClass() === 'warning' ? 'ffc107' : '6c757d')) }}; color: white;">
                        <i class="fas fa-info-circle me-1"></i> Status: {{ $requisition->getStatusLabel() }}
                    </span>
                    <span class="info-badge" style="background: #6c757d; color: white;">
                        <i class="fas fa-calendar me-1"></i> Created: {{ $requisition->created_at->format('d M Y, h:i A') }}
                    </span>
                    @if ($requisition->updated_at != $requisition->created_at)
                        <span class="info-badge" style="background: #6c757d; color: white;">
                            <i class="fas fa-edit me-1"></i> Updated: {{ $requisition->updated_at->format('d M Y, h:i A') }}
                        </span>
                    @endif
                </div>

                <!-- Section 1: Position Details -->
                <div class="pdf-section">
                    <h5>Section 1: Position Details (to be filled by respective HOD)</h5>
                    <table class="info-table">
                        <tr>
                            <th>Job Title</th>
                            <td class="{{ !$canEditHODSection ? 'readonly' : '' }}">{{ $requisition->job_title }}</td>
                        </tr>
                        <tr>
                            <th>Background</th>
                            <td class="{{ !$canEditHODSection ? 'readonly' : '' }}">
                                {{ ucfirst(str_replace('_', ' ', $requisition->background)) }}</td>
                        </tr>
                        @if ($requisition->background === 'replacement' || $requisition->background === 'contract_renewal_extension')
                            <tr>
                                <th>Employee Name</th>
                                <td class="{{ !$canEditHODSection ? 'readonly' : '' }}">
                                    {{ $requisition->employee_name ?? 'N/A' }}</td>
                            </tr>
                        @endif
                        @if ($requisition->background === 'contract_renewal_extension')
                            <tr>
                                <th>Current Contract End Date</th>
                                <td class="{{ !$canEditHODSection ? 'readonly' : '' }}">
                                    {{ $requisition->current_contract_end_date ? $requisition->current_contract_end_date->format('d M Y') : 'N/A' }}
                                </td>
                            </tr>
                        @endif
                        <tr>
                            <th>Department</th>
                            <td class="{{ !$canEditHODSection ? 'readonly' : '' }}">
                                {{ $requisition->department->dept_name ?? 'N/A' }}</td>
                        </tr>
                        <tr>
                            <th>Responsibility Centre</th>
                            <td class="{{ !$canEditHODSection ? 'readonly' : '' }}">
                                {{ $requisition->responsibility_centre }}</td>
                        </tr>
                        <tr>
                            <th>Reports To Position</th>
                            <td class="{{ !$canEditHODSection ? 'readonly' : '' }}">
                                {{ $requisition->reports_to_position }}</td>
                        </tr>
                        <tr>
                            <th>Contract Type</th>
                            <td class="{{ !$canEditHODSection ? 'readonly' : '' }}">
                                {{ ucfirst(str_replace('_', ' ', $requisition->contract_type)) }}</td>
                        </tr>
                    </table>

                    <h5 style="margin-top: 30px;">Budget Allocation (to be checked with Payroll Accountant)</h5>
                    <table class="info-table">
                        <tr>
                            <th>Position Approved in Budget</th>
                            <td class="{{ !$canEditHODSection ? 'readonly' : '' }}">
                                <span class="badge"
                                    style="background: {{ $requisition->position_approved_in_budget ? '#28a745' : '#dc3545' }}; color: white; padding: 6px 12px; border-radius: 5px;">
                                    {{ $requisition->position_approved_in_budget ? 'Yes' : 'No' }}
                                </span>
                            </td>
                        </tr>
                        <tr>
                            <th>Max Monthly Budget</th>
                            <td class="{{ !$canEditHODSection ? 'readonly' : '' }}">
                                {{ $requisition->max_monthly_budget ? number_format($requisition->max_monthly_budget, 2) . ' TZS' : 'N/A' }}
                            </td>
                        </tr>
                        <tr>
                            <th>Funding Available</th>
                            <td class="{{ !$canEditHODSection ? 'readonly' : '' }}">
                                <span class="badge"
                                    style="background: {{ $requisition->funding_available ? '#28a745' : '#dc3545' }}; color: white; padding: 6px 12px; border-radius: 5px;">
                                    {{ $requisition->funding_available ? 'Yes' : 'No' }}
                                </span>
                            </td>
                        </tr>
                        <tr>
                            <th>In Budget Flag</th>
                            <td>
                                <span class="badge"
                                    style="background: {{ $requisition->in_budget_flag ? '#28a745' : '#ffc107' }}; color: {{ $requisition->in_budget_flag ? 'white' : '#333' }}; padding: 6px 12px; border-radius: 5px;">
                                    {{ $requisition->in_budget_flag ? 'Yes' : 'No' }}
                                </span>
                            </td>
                        </tr>
                        <tr>
                            <th>Donor Code</th>
                            <td class="{{ !$canEditHODSection ? 'readonly' : '' }}">
                                {{ $requisition->donor_code ?? 'N/A' }}</td>
                        </tr>
                        <tr>
                            <th>Activity Code</th>
                            <td class="{{ !$canEditHODSection ? 'readonly' : '' }}">
                                {{ $requisition->activity_code ?? 'N/A' }}</td>
                        </tr>
                        <tr>
                            <th>Required Starting Date</th>
                            <td class="{{ !$canEditHODSection ? 'readonly' : '' }}">
                                <i
                                    class="fas fa-calendar me-1"></i>{{ $requisition->required_starting_date->format('d M Y') }}
                            </td>
                        </tr>
                        @if ($requisition->payrollAccountant)
                            <tr>
                                <th>Payroll Accountant</th>
                                <td>{{ $requisition->payrollAccountant->name }}</td>
                            </tr>
                        @endif
                    </table>

                    @if ($requisition->attachments->count() > 0)
                        <h5 style="margin-top: 30px;">Attachments</h5>
                        <div class="content-box">
                            <ul style="margin: 0; padding-left: 20px;">
                                @foreach ($requisition->attachments as $attachment)
                                    <li style="margin-bottom: 8px;">
                                        <a href="{{ Storage::url($attachment->file_path) }}" target="_blank"
                                            class="text-decoration-none">
                                            <i
                                                class="fas fa-file-pdf me-1 text-danger"></i>{{ $attachment->original_name }}
                                        </a>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                </div>

                <!-- Section 2: Conditions & Justification -->
                <div class="pdf-section">
                    <h5>Section 2: Conditions (HOD Justification)</h5>
                    <table class="info-table">
                        <tr>
                            <th>Selected Conditions</th>
                            <td class="{{ !$canEditHODSection ? 'readonly' : '' }}">
                                <ul style="margin: 0; padding-left: 20px;">
                                    @foreach ($requisition->conditions ?? [] as $condition)
                                        <li style="margin-bottom: 5px;">
                                            @if ($condition == 1)
                                                Overwhelming medical/operational imperatives
                                            @elseif($condition == 2)
                                                Safety or reputational risks
                                            @elseif($condition == 3)
                                                Legal requirement to fill the post
                                            @elseif($condition == 4)
                                                Evidence of demonstrable financial loss if not filled
                                            @elseif($condition == 5)
                                                Post is necessary to increase income significantly
                                            @endif
                                        </li>
                                    @endforeach
                                </ul>
                            </td>
                        </tr>
                        <tr>
                            <th>Justification Text</th>
                            <td class="{{ !$canEditHODSection ? 'readonly' : '' }}">
                                <div class="content-box">
                                    {!! nl2br(e($requisition->justification_text)) !!}
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <th>HOD Name</th>
                            <td>{{ $requisition->hod->name ?? 'N/A' }}</td>
                        </tr>
                        <tr>
                            <th>HOD Signed At</th>
                            <td>
                                @if ($requisition->hod_signed_at)
                                    <i
                                        class="fas fa-calendar me-1"></i>{{ $requisition->hod_signed_at->format('d M Y, h:i A') }}
                                @else
                                    N/A
                                @endif
                            </td>
                        </tr>
                    </table>
                </div>

                <!-- HEC Review Section -->
                @if ($requisition->hecReviewer || $canEditHECSection)
                    <div class="pdf-section">
                        <h5>HEC Review</h5>
                        @if ($requisition->hecReviewer)
                            <table class="info-table">
                                <tr>
                                    <th>Reviewed By</th>
                                    <td>{{ $requisition->hecReviewer->name }}</td>
                                </tr>
                                <tr>
                                    <th>Decision</th>
                                    <td>
                                        <span class="badge"
                                            style="background: {{ $requisition->hec_decision === 'no_objection' ? '#28a745' : '#dc3545' }}; color: white; padding: 6px 12px; border-radius: 5px;">
                                            {{ ucfirst(str_replace('_', ' ', $requisition->hec_decision ?? 'N/A')) }}
                                        </span>
                                    </td>
                                </tr>
                                @if ($requisition->hec_comments)
                                    <tr>
                                        <th>Comments</th>
                                        <td>
                                            <div class="content-box">
                                                {!! nl2br(e($requisition->hec_comments)) !!}
                                            </div>
                                        </td>
                                    </tr>
                                @endif
                                @if ($requisition->hec_justification_for_no_budget)
                                    <tr>
                                        <th>Justification for No Budget</th>
                                        <td>
                                            <div class="content-box">
                                                {!! nl2br(e($requisition->hec_justification_for_no_budget)) !!}
                                            </div>
                                        </td>
                                    </tr>
                                @endif
                                @if ($requisition->hec_proposed_funding)
                                    <tr>
                                        <th>Proposed Funding</th>
                                        <td>{{ $requisition->hec_proposed_funding }}</td>
                                    </tr>
                                @endif
                                @if ($requisition->hec_reviewed_at)
                                    <tr>
                                        <th>Reviewed At</th>
                                        <td><i
                                                class="fas fa-calendar me-1"></i>{{ $requisition->hec_reviewed_at->format('d M Y, h:i A') }}
                                        </td>
                                    </tr>
                                @endif
                            </table>
                        @endif
                    </div>
                @endif

                <!-- CFO Review Section -->
                @if ($requisition->cfoReviewer || $canEditCFOSection)
                    <div class="pdf-section">
                        <h5>CFO Finance Review</h5>
                        @if ($requisition->cfoReviewer)
                            <table class="info-table">
                                <tr>
                                    <th>Reviewed By</th>
                                    <td>{{ $requisition->cfoReviewer->name }}</td>
                                </tr>
                                <tr>
                                    <th>Status</th>
                                    <td>
                                        <span class="badge"
                                            style="background: {{ $requisition->status === 'cfo_finance_confirmed' ? '#28a745' : '#dc3545' }}; color: white; padding: 6px 12px; border-radius: 5px;">
                                            {{ $requisition->status === 'cfo_finance_confirmed' ? 'Confirmed' : 'Rejected' }}
                                        </span>
                                    </td>
                                </tr>
                                @if ($requisition->cfo_financing_confirmation)
                                    <tr>
                                        <th>Financing Confirmation</th>
                                        <td>
                                            <div class="content-box">
                                                {!! nl2br(e($requisition->cfo_financing_confirmation)) !!}
                                            </div>
                                        </td>
                                    </tr>
                                @endif
                                @if ($requisition->cfo_financing_code)
                                    <tr>
                                        <th>Financing Code</th>
                                        <td>{{ $requisition->cfo_financing_code }}</td>
                                    </tr>
                                @endif
                                @if ($requisition->cfo_comment)
                                    <tr>
                                        <th>Comments</th>
                                        <td>
                                            <div class="content-box">
                                                {!! nl2br(e($requisition->cfo_comment)) !!}
                                            </div>
                                        </td>
                                    </tr>
                                @endif
                                @if ($requisition->cfo_reviewed_at)
                                    <tr>
                                        <th>Reviewed At</th>
                                        <td><i
                                                class="fas fa-calendar me-1"></i>{{ $requisition->cfo_reviewed_at->format('d M Y, h:i A') }}
                                        </td>
                                    </tr>
                                @endif
                            </table>
                        @endif
                    </div>
                @endif

                <!-- CEO Decision Section -->
                @if ($requisition->ceoReviewer || $canEditCEOSection)
                    <div class="pdf-section">
                        <h5>CEO Decision</h5>
                        @if ($requisition->ceoReviewer)
                            <table class="info-table">
                                <tr>
                                    <th>Reviewed By</th>
                                    <td>{{ $requisition->ceoReviewer->name }}</td>
                                </tr>
                                <tr>
                                    <th>Decision</th>
                                    <td>
                                        <span class="badge"
                                            style="background: {{ $requisition->ceo_decision === 'approved' ? '#28a745' : ($requisition->ceo_decision === 'declined' ? '#dc3545' : '#ffc107') }}; color: {{ $requisition->ceo_decision === 'needs_further_information' ? '#333' : 'white' }}; padding: 6px 12px; border-radius: 5px;">
                                            {{ ucfirst(str_replace('_', ' ', $requisition->ceo_decision ?? 'N/A')) }}
                                        </span>
                                    </td>
                                </tr>
                                @if ($requisition->ceo_comment)
                                    <tr>
                                        <th>Comments</th>
                                        <td>
                                            <div class="content-box">
                                                {!! nl2br(e($requisition->ceo_comment)) !!}
                                            </div>
                                        </td>
                                    </tr>
                                @endif
                                @if ($requisition->ceo_reviewed_at)
                                    <tr>
                                        <th>Reviewed At</th>
                                        <td><i
                                                class="fas fa-calendar me-1"></i>{{ $requisition->ceo_reviewed_at->format('d M Y, h:i A') }}
                                        </td>
                                    </tr>
                                @endif
                            </table>
                        @endif
                    </div>
                @endif

                <!-- HR Processing Section -->
                @if ($requisition->hrProcessor || $canEditHRSection)
                    <div class="pdf-section">
                        <h5>HR Processing</h5>
                        @if ($requisition->hrProcessor)
                            <table class="info-table">
                                <tr>
                                    <th>Processed By</th>
                                    <td>{{ $requisition->hrProcessor->name }}</td>
                                </tr>
                                <tr>
                                    <th>Recruitment Started</th>
                                    <td>
                                        <span class="badge"
                                            style="background: {{ $requisition->start_recruitment_flag ? '#28a745' : '#6c757d' }}; color: white; padding: 6px 12px; border-radius: 5px;">
                                            {{ $requisition->start_recruitment_flag ? 'Yes' : 'No' }}
                                        </span>
                                    </td>
                                </tr>
                                @if ($requisition->hr_notes)
                                    <tr>
                                        <th>Notes</th>
                                        <td>
                                            <div class="content-box">
                                                {!! nl2br(e($requisition->hr_notes)) !!}
                                            </div>
                                        </td>
                                    </tr>
                                @endif
                                @if ($requisition->advert_date)
                                    <tr>
                                        <th>Advert Date</th>
                                        <td><i
                                                class="fas fa-calendar me-1"></i>{{ $requisition->advert_date->format('d M Y') }}
                                        </td>
                                    </tr>
                                @endif
                                @if ($requisition->shortlisting_date)
                                    <tr>
                                        <th>Shortlisting Date</th>
                                        <td><i
                                                class="fas fa-calendar me-1"></i>{{ $requisition->shortlisting_date->format('d M Y') }}
                                        </td>
                                    </tr>
                                @endif
                                @if ($requisition->hr_processed_at)
                                    <tr>
                                        <th>Processed At</th>
                                        <td><i
                                                class="fas fa-calendar me-1"></i>{{ $requisition->hr_processed_at->format('d M Y, h:i A') }}
                                        </td>
                                    </tr>
                                @endif
                            </table>
                        @endif
                    </div>
                @endif

                <!-- Workflow History -->
                <div class="pdf-section">
                    <h5>Workflow History</h5>
                    <table class="info-table">
                        <thead>
                            <tr>
                                <th>Step Name</th>
                                <th>Action</th>
                                <th>By</th>
                                <th>Date</th>
                                <th>Comments</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($requisition->workflowHistories as $history)
                                <tr>
                                    <td><strong>{{ $history->step_name }}</strong></td>
                                    <td>
                                        <span class="badge"
                                            style="background: {{ $history->action === 'approved' || $history->action === 'no_objection' ? '#28a745' : ($history->action === 'rejected' || $history->action === 'objection' ? '#dc3545' : '#6c757d') }}; color: white; padding: 4px 8px; border-radius: 3px;">
                                            {{ ucfirst(str_replace('_', ' ', $history->action)) }}
                                        </span>
                                    </td>
                                    <td>{{ $history->attendedBy->name ?? 'N/A' }}</td>
                                    <td><i
                                            class="fas fa-calendar me-1"></i>{{ $history->created_at->format('d M Y, h:i A') }}
                                    </td>
                                    <td>{{ $history->comments ?? '—' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center text-muted">No workflow history available</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <!-- Action Forms Based on Role and Status -->
                @if ($canEditHODSection)
                    <div class="action-section">
                        <h5 style="margin-top: 0;">Actions</h5>
                        <a href="{{ route('recruitment-requisitions.edit', $requisition->id) }}"
                            class="btn btn-primary me-2">
                            <i class="fas fa-edit me-1"></i> Edit Requisition
                        </a>
                        <form action="{{ route('recruitment-requisitions.submit', $requisition->id) }}" method="POST"
                            class="d-inline">
                            @csrf
                            <button type="submit" class="btn btn-success"
                                onclick="return confirm('Are you sure you want to submit this requisition for review?')">
                                <i class="fas fa-paper-plane me-1"></i> Submit for Review
                            </button>
                        </form>
                    </div>
                @endif

                @if ($canEditHECSection)
                    <div class="action-section">
                        <h5 style="margin-top: 0;">HEC Review Action</h5>
                        <form action="{{ route('recruitment-requisitions.hec-review', $requisition->id) }}"
                            method="POST">
                            @csrf
                            <div class="mb-3">
                                <label class="form-label fw-bold">Decision <span class="text-danger">*</span></label>
                                <div class="form-check">
                                    <input type="radio" name="hec_decision" value="no_objection"
                                        class="form-check-input" id="hec_no_objection" required>
                                    <label class="form-check-label" for="hec_no_objection">No Objection to Start
                                        Recruitment/Renewal</label>
                                </div>
                                <div class="form-check">
                                    <input type="radio" name="hec_decision" value="objection" class="form-check-input"
                                        id="hec_objection" required>
                                    <label class="form-check-label" for="hec_objection">Objection to Start
                                        Recruitment/Renewal</label>
                                </div>
                            </div>
                            <div class="mb-3" id="noBudgetFields" style="display: none;">
                                <label class="form-label fw-bold">Justification for No Budget</label>
                                <textarea name="hec_justification_for_no_budget" class="form-control" rows="3"></textarea>
                                <label class="form-label fw-bold mt-2">Proposed Funding</label>
                                <input type="text" name="hec_proposed_funding" class="form-control">
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-bold">Comments</label>
                                <textarea name="hec_comments" class="form-control" rows="3"></textarea>
                            </div>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-check me-1"></i> Submit Review
                            </button>
                        </form>
                    </div>
                    <script>
                        document.querySelectorAll('input[name="hec_decision"]').forEach(radio => {
                            radio.addEventListener('change', function() {
                                if (this.value === 'no_objection' && !
                                    {{ $requisition->isInBudget() ? 'true' : 'false' }}) {
                                    document.getElementById('noBudgetFields').style.display = 'block';
                                    document.querySelector('textarea[name="hec_justification_for_no_budget"]').required =
                                        true;
                                    document.querySelector('input[name="hec_proposed_funding"]').required = true;
                                } else {
                                    document.getElementById('noBudgetFields').style.display = 'none';
                                    document.querySelector('textarea[name="hec_justification_for_no_budget"]').required =
                                        false;
                                    document.querySelector('input[name="hec_proposed_funding"]').required = false;
                                }
                            });
                        });
                    </script>
                @endif

                @if ($canEditCFOSection)
                    <div class="action-section">
                        <h5 style="margin-top: 0;">CFO Finance Review</h5>
                        <form action="{{ route('recruitment-requisitions.cfo-review', $requisition->id) }}"
                            method="POST">
                            @csrf
                            <div class="mb-3">
                                <label class="form-label fw-bold">Financing Confirmation <span
                                        class="text-danger">*</span></label>
                                <textarea name="cfo_financing_confirmation" class="form-control" rows="4" required></textarea>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-bold">Financing Code</label>
                                <input type="text" name="cfo_financing_code" class="form-control">
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-bold">Comments</label>
                                <textarea name="cfo_comment" class="form-control" rows="3"></textarea>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-bold">Action <span class="text-danger">*</span></label>
                                <div class="form-check">
                                    <input type="radio" name="action" value="approve" class="form-check-input"
                                        id="cfo_approve" required>
                                    <label class="form-check-label" for="cfo_approve">Approve and Forward to CEO</label>
                                </div>
                                <div class="form-check">
                                    <input type="radio" name="action" value="reject" class="form-check-input"
                                        id="cfo_reject" required>
                                    <label class="form-check-label" for="cfo_reject">Reject</label>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-check me-1"></i> Submit Review
                            </button>
                        </form>
                    </div>
                @endif

                @if ($canEditCEOSection)
                    <div class="action-section">
                        <h5 style="margin-top: 0;">CEO Decision</h5>
                        <form action="{{ route('recruitment-requisitions.ceo-decision', $requisition->id) }}"
                            method="POST">
                            @csrf
                            <div class="mb-3">
                                <label class="form-label fw-bold">Decision <span class="text-danger">*</span></label>
                                <select name="ceo_decision" class="form-control" required>
                                    <option value="">-- Select Decision --</option>
                                    <option value="approved">Approved</option>
                                    <option value="declined">Declined</option>
                                    <option value="needs_further_information">Needs Further Information</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-bold">Comments</label>
                                <textarea name="ceo_comment" class="form-control" rows="4"></textarea>
                            </div>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-check me-1"></i> Submit Decision
                            </button>
                        </form>
                    </div>
                @endif

                @if ($canEditHRSection)
                    <div class="action-section">
                        <h5 style="margin-top: 0;">HR Processing</h5>
                        <form action="{{ route('recruitment-requisitions.hr-process', $requisition->id) }}"
                            method="POST">
                            @csrf
                            <div class="mb-3">
                                <label class="form-label fw-bold">Notes</label>
                                <textarea name="hr_notes" class="form-control" rows="3"></textarea>
                            </div>
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label class="form-label fw-bold">Advert Date</label>
                                    <input type="date" name="advert_date" class="form-control"
                                        value="{{ $requisition->advert_date ? $requisition->advert_date->format('Y-m-d') : '' }}">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-bold">Shortlisting Date</label>
                                    <input type="date" name="shortlisting_date" class="form-control"
                                        value="{{ $requisition->shortlisting_date ? $requisition->shortlisting_date->format('Y-m-d') : '' }}">
                                </div>
                            </div>
                            <div class="mb-3">
                                <div class="form-check">
                                    <input type="checkbox" name="start_recruitment" value="1"
                                        class="form-check-input" id="start_recruitment"
                                        {{ $requisition->start_recruitment_flag ? 'checked' : '' }}>
                                    <label class="form-check-label" for="start_recruitment">
                                        Start Recruitment Process
                                    </label>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-check me-1"></i> Process Requisition
                            </button>
                        </form>
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection
