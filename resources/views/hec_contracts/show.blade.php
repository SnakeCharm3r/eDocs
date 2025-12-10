@extends('layouts.template')

@section('content')
<div class="page-wrapper">
    <div class="content container-fluid">
        <div class="page-header">
            <div class="row align-items-center">
                <div class="col">
                    <h3 class="page-title">
                        <i class="fas fa-file-contract me-2"></i>HEC Contract Details
                    </h3>
                </div>
                <div class="col-auto">
                    <a href="{{ route('hec-contracts.index') }}" class="btn btn-secondary">
                        <i class="fas fa-arrow-left me-1"></i> Back to List
                    </a>
                    <a href="{{ route('hec-contracts.edit', $contract->id) }}" class="btn btn-warning">
                        <i class="fas fa-edit me-1"></i> Edit
                    </a>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-lg-12">
                <div class="card">
                    <div class="card-header bg-light">
                        <h5 class="card-title mb-0">{{ $contract->title }}</h5>
                        <small class="text-muted">Contract Number: {{ $contract->contract_number ?? 'N/A' }}</small>
                    </div>
                    <div class="card-body">
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <h6 class="text-primary border-bottom pb-2 mb-3">Contract Information</h6>
                                <table class="table table-borderless">
                                    <tr>
                                        <th width="40%">Contract Type:</th>
                                        <td><span class="badge bg-info">{{ $contract->contract_type }}</span></td>
                                    </tr>
                                    <tr>
                                        <th>CCBRT Entity:</th>
                                        <td>
                                            @if($contract->division)
                                                {{ $contract->division->name }} @if($contract->division->code) ({{ $contract->division->code }}) @endif
                                            @else
                                                <span class="text-muted">N/A</span>
                                            @endif
                                        </td>
                                    </tr>
                                    <tr>
                                        <th>Status:</th>
                                        <td>
                                            @php
                                                $statusColors = [
                                                    'active' => 'success',
                                                    'expired' => 'danger',
                                                    'soonToExpire' => 'warning',
                                                    'draft' => 'secondary',
                                                    'in_progress' => 'info',
                                                    'renewed' => 'primary',
                                                    'terminated' => 'dark'
                                                ];
                                                $color = $statusColors[$contract->status] ?? 'secondary';
                                            @endphp
                                            <span class="badge bg-{{ $color }}">{{ ucfirst(str_replace('_', ' ', $contract->status)) }}</span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th>Start Date:</th>
                                        <td>{{ $contract->start_date ? $contract->start_date->format('Y-m-d') : 'N/A' }}</td>
                                    </tr>
                                    <tr>
                                        <th>End Date:</th>
                                        <td>{{ $contract->end_date ? $contract->end_date->format('Y-m-d') : 'N/A' }}</td>
                                    </tr>
                                    <tr>
                                        <th>Duration:</th>
                                        <td>{{ $contract->duration_months ?? 'N/A' }} months</td>
                                    </tr>
                                    @if($contract->description)
                                    <tr>
                                        <th>Description:</th>
                                        <td>{{ $contract->description }}</td>
                                    </tr>
                                    @endif
                                </table>
                            </div>
                            <div class="col-md-6">
                                <h6 class="text-primary border-bottom pb-2 mb-3">Financial Details</h6>
                                <table class="table table-borderless">
                                    <tr>
                                        <th width="40%">Contract Value:</th>
                                        <td><strong>{{ number_format($contract->cost ?? 0, 2) }} {{ $contract->currency ?? 'TZS' }}</strong></td>
                                    </tr>
                                    <tr>
                                        <th>Impact if Not Requested:</th>
                                        <td><span class="badge bg-warning">{{ $contract->impact_if_not_requested ?? 'N/A' }}</span></td>
                                    </tr>
                                    <tr>
                                        <th>Likelihood Rating:</th>
                                        <td><span class="badge bg-info">{{ $contract->likelihood_rating ?? 'N/A' }}</span></td>
                                    </tr>
                                </table>
                            </div>
                        </div>

                        <div class="row mb-4">
                            <div class="col-md-6">
                                <h6 class="text-primary border-bottom pb-2 mb-3">Contract Owner</h6>
                                <table class="table table-borderless">
                                    <tr>
                                        <th width="40%">HEC Member:</th>
                                        <td>
                                            @if($contract->contractOwner)
                                                {{ $contract->contractOwner->fname }} {{ $contract->contractOwner->mname }} {{ $contract->contractOwner->lname }}
                                                <br><small class="text-muted">{{ $contract->contractOwner->email }}</small>
                                            @else
                                                <span class="text-muted">None (external email only)</span>
                                            @endif
                                        </td>
                                    </tr>
                                    <tr>
                                        <th>Notification Email:</th>
                                        <td>
                                            @if($contract->owner_email)
                                                <span>{{ $contract->owner_email }}</span>
                                            @elseif($contract->contractOwner)
                                                <span class="text-muted">Uses HEC member email above</span>
                                            @else
                                                <span class="text-muted">Not set</span>
                                            @endif
                                        </td>
                                    </tr>
                                    <tr>
                                        <th>Source:</th>
                                        <td>
                                            <span class="badge bg-secondary">
                                                {{ $contract->contract_source === 'existing' ? 'Existing' : 'New' }}
                                            </span>
                                        </td>
                                    </tr>
                                </table>
                            </div>
                        </div>

                        <div class="row mb-4">
                            <div class="col-md-12">
                                <h6 class="text-primary border-bottom pb-2 mb-3">Documents</h6>
                                <div class="row">
                                    @if($contract->file_path)
                                        <div class="col-md-3 mb-2">
                                            <a href="{{ asset('storage/' . $contract->file_path) }}" target="_blank" class="btn btn-outline-primary btn-sm w-100">
                                                <i class="fas fa-file-pdf me-1"></i> Contract Document
                                            </a>
                                        </div>
                                    @endif
                                    @if($contract->signed_contract_path)
                                        <div class="col-md-3 mb-2">
                                            <a href="{{ asset('storage/' . $contract->signed_contract_path) }}" target="_blank" class="btn btn-outline-primary btn-sm w-100">
                                                <i class="fas fa-file-pdf me-1"></i> Signed Contract
                                            </a>
                                        </div>
                                    @endif
                                    @if($contract->terms_conditions_path)
                                        <div class="col-md-3 mb-2">
                                            <a href="{{ asset('storage/' . $contract->terms_conditions_path) }}" target="_blank" class="btn btn-outline-primary btn-sm w-100">
                                                <i class="fas fa-file-pdf me-1"></i> Terms & Conditions
                                            </a>
                                        </div>
                                    @endif
                                    @if($contract->sla_document_path)
                                        <div class="col-md-3 mb-2">
                                            <a href="{{ asset('storage/' . $contract->sla_document_path) }}" target="_blank" class="btn btn-outline-primary btn-sm w-100">
                                                <i class="fas fa-file-pdf me-1"></i> SLA Document
                                            </a>
                                        </div>
                                    @endif
                                    @if(!$contract->file_path && !$contract->signed_contract_path && !$contract->terms_conditions_path && !$contract->sla_document_path)
                                        <div class="col-md-12">
                                            <p class="text-muted">No documents uploaded.</p>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-12">
                                <small class="text-muted">
                                    Created by: {{ $contract->creator->fname ?? '' }} {{ $contract->creator->lname ?? '' }} on {{ $contract->created_at->format('Y-m-d H:i:s') }}
                                </small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

