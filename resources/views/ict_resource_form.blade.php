@extends('layouts.template')

@section('breadcrumb')
    @include('sweetalert::alert')
@endsection

@section('content')
    @php
        // Get privilege level names for display (only if values exist)
        $activeDrtPrivilege = $ictForm->active_drt ? \App\Models\PrivilegeLevel::find($ictForm->active_drt) : null;
        $emailPrivilege = $ictForm->email ? \App\Models\PrivilegeLevel::find($ictForm->email) : null;
        // Handle aruti - it can be a string (single ID) or array, but we only display the first one
        $arutiId = $ictForm->aruti;
        if (is_array($arutiId)) {
            $arutiId = !empty($arutiId) ? $arutiId[0] : null;
        }
        $arutiPrivilege = $arutiId ? \App\Models\ArutiLevel::find($arutiId) : null;
        $vpnPrivilege = $ictForm->VPN ? \App\Models\PrivilegeLevel::find($ictForm->VPN) : null;
        $pbaxPrivilege = $ictForm->pbax ? \App\Models\PrivilegeLevel::find($ictForm->pbax) : null;
        $folderPrivilege = $ictForm->folder_privilege
            ? \App\Models\PrivilegeLevel::find($ictForm->folder_privilege)
            : null;
        $sapLevel = $ictForm->ASPId ? \App\Models\SAPLevels::find($ictForm->ASPId) : null;
        // Handle access_key_card_id - it's now an array, so decode if it's a string
        $accessKeyCardIds = $ictForm->access_key_card_id;
        if (is_string($accessKeyCardIds)) {
            $accessKeyCardIds = json_decode($accessKeyCardIds, true);
        }
        if (!is_array($accessKeyCardIds)) {
            $accessKeyCardIds = [];
        }
        $accessKeyCards = !empty($accessKeyCardIds)
            ? \App\Models\AccessKeyCard::whereIn('id', $accessKeyCardIds)->get()
            : collect([]);

        // Format dates
        $startDate = $ictForm->start_date ? \Carbon\Carbon::parse($ictForm->start_date)->format('d F Y') : 'N/A';
        $endDate = $ictForm->end_date ? \Carbon\Carbon::parse($ictForm->end_date)->format('d F Y') : 'N/A';
        $submittedDate = $ictForm->created_at
            ? \Carbon\Carbon::parse($ictForm->created_at)->format('d F Y, H:i')
            : 'N/A';

        // Get status badge
        $statusClass = 'warning';
        $statusText = 'Pending';
        if ($ictForm->status == 1) {
            $statusClass = 'success';
            $statusText = 'Approved';
        } elseif ($ictForm->status == -1) {
            $statusClass = 'danger';
            $statusText = 'Rejected';
        }
    @endphp

    <div class="page-wrapper">
        <div class="content container-fluid">
            <br>
            <!-- Page Header -->
            <div class="page-header">
                <div class="row">
                    <div class="col-sm-12">
                        <div class="page-sub-header">
                            <h3 class="page-title">ICT Access Form Review</h3>
                        </div>
                    </div>
                </div>
            </div>

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
                                <div class="col-md-4">
                                    <p class="mb-2"><strong>Submitted Date:</strong> {{ $submittedDate }}</p>
                                </div>
                                <div class="col-md-4">
                                    <p class="mb-2"><strong>Status:</strong>
                                        <span class="badge badge-{{ $statusClass }}"
                                            style="font-size: 0.9rem; padding: 0.5em 0.75em; font-weight: 600;">{{ $statusText }}</span>
                                    </p>
                                </div>
                                <div class="col-md-4">
                                    <p class="mb-2"><strong>User Type:</strong>
                                        <span class="badge badge-info">{{ $ictForm->user_type ?? 'N/A' }}</span>
                                    </p>
                                </div>
                            </div>

                            @if (($ictForm->status ?? null) == -1 && !empty($latestRejectionHistory) && !empty($latestRejectionHistory->rejection_reason))
                                <div class="row mt-3">
                                    <div class="col-12">
                                        <div class="alert alert-danger mb-0" role="alert">
                                            <strong>Rejection Comment:</strong>
                                            <div class="mt-1">{{ $latestRejectionHistory->rejection_reason }}</div>
                                        </div>
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>

                    <!-- Reject Modal -->
                    <div class="modal fade" id="rejectCommentModal" tabindex="-1" aria-labelledby="rejectCommentModalLabel"
                        aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="rejectCommentModalLabel">Reject IT Access Form</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    <div class="mb-3">
                                        <label for="reject_reason" class="form-label">Reason for rejection <span class="text-danger">*</span></label>
                                        <textarea class="form-control" id="reject_reason" rows="4"
                                            placeholder="Enter reason here..."></textarea>
                                        <div class="form-text">Provide a clear reason so the requester can correct and resubmit.</div>
                                    </div>
                                    <div class="text-danger small" id="reject_reason_error" style="display:none;">You must provide a reason.</div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                    <button type="button" class="btn btn-danger" id="confirmRejectBtn">Reject</button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Primary Information Card -->
                    <div class="card shadow-sm mb-4">
                        <div class="card-header bg-white border-bottom">
                            <h5 class="card-title mb-0 section-title">
                                <i class="fas fa-user-circle me-2"></i>Primary Information
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label class="text-muted small">User Type</label>
                                        <p class="mb-0"><strong>{{ $ictForm->user_type ?? 'N/A' }}</strong></p>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label class="text-muted small">Access Action</label>
                                        <p class="mb-0">
                                            <strong>
                                                @if ($ictForm->access_required == 'Grant')
                                                    <span class="badge badge-success">Grant Access</span>
                                                @elseif($ictForm->access_required == 'Revoke')
                                                    <span class="badge badge-warning">Remove Access</span>
                                                @else
                                                    N/A
                                                @endif
                                            </strong>
                                        </p>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label class="text-muted small">Action Type</label>
                                        <p class="mb-0"><strong>{{ $ictForm->action_required ?? 'N/A' }}</strong></p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Personal Details Card -->
                    <div class="card shadow-sm mb-4">
                        <div class="card-header bg-white border-bottom">
                            <h5 class="card-title mb-0 section-title">
                                <i class="fas fa-user me-2"></i>Personal Details
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label class="text-muted small">Full Name</label>
                                        <p class="mb-0"><strong>{{ $ictForm->fname }} {{ $ictForm->mname ?? '' }}
                                                {{ $ictForm->lname }}</strong></p>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label class="text-muted small">Email</label>
                                        <p class="mb-0"><strong>{{ $ictForm->email ?? 'N/A' }}</strong></p>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label class="text-muted small">Mobile Number</label>
                                        <p class="mb-0"><strong>{{ $ictForm->mobile ?? 'N/A' }}</strong></p>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label class="text-muted small">Department</label>
                                        <p class="mb-0"><strong>{{ $ictForm->dept_name ?? 'N/A' }}</strong></p>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label class="text-muted small">Employment Type</label>
                                        <p class="mb-0"><strong>{{ $ictForm->employment_type ?? 'N/A' }}</strong></p>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label class="text-muted small">Professional Reg. No</label>
                                        <p class="mb-0"><strong>{{ $ictForm->professional_reg_number ?? 'N/A' }}</strong>
                                        </p>
                                    </div>
                                </div>
                            </div>

                            @if ($isClinicalDepartment && $requestUser && $requestUser->professional_reg_number)
                                <!-- Professional Registration & License Validation Alert for Clinical Departments -->
                                <div class="mt-4 p-3 rounded"
                                    style="background-color: #fff3cd; border-left: 4px solid #ffc107;">
                                    <h6 class="mb-3" style="color: #856404; font-weight: 600;">
                                        <i class="fas fa-certificate me-2"></i>Professional License Verification
                                        <span class="badge badge-warning ms-2">Required for Clinical Staff</span>
                                    </h6>

                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-2">
                                                <p class="mb-0"
                                                    style="font-size: 1.1rem; font-weight: 600; color: #007A33;">
                                                    {{ $requestUser->professional_reg_number }}
                                                </p>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            @if ($hrWorkflowHistory && $hrWorkflowHistory->license_provider)
                                                <div class="mb-2">
                                                    <strong>License Provider:</strong>
                                                    <p class="mb-0">{{ $hrWorkflowHistory->license_provider }}</p>
                                                </div>
                                            @elseif ($licenseProviderName)
                                                <div class="mb-2">
                                                    <strong>License Provider:</strong>
                                                    <p class="mb-0">{{ $licenseProviderName }}</p>
                                                </div>
                                            @endif
                                        </div>
                                    </div>

                                    @if ($hrWorkflowHistory && $hrWorkflowHistory->license_valid_until)
                                        <div class="row mt-2">
                                            <div class="col-md-12">
                                                <div class="mb-2">
                                                    <strong>License Valid Until:</strong>
                                                    <span class="ms-2" style="font-size: 1rem; font-weight: 600;">
                                                        {{ \Carbon\Carbon::parse($hrWorkflowHistory->license_valid_until)->format('d F Y') }}
                                                    </span>
                                                    @php
                                                        $daysUntilExpiry = \Carbon\Carbon::parse(
                                                            $hrWorkflowHistory->license_valid_until,
                                                        )->diffInDays(\Carbon\Carbon::now(), false);
                                                    @endphp
                                                    @if ($daysUntilExpiry < 0)
                                                        <span class="badge badge-success ms-2">Valid</span>
                                                    @elseif ($daysUntilExpiry <= 90)
                                                        <span class="badge badge-warning ms-2">Expiring Soon
                                                            ({{ abs($daysUntilExpiry) }} days)</span>
                                                    @else
                                                        <span class="badge badge-danger ms-2">Expired
                                                            ({{ abs($daysUntilExpiry) }} days ago)</span>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>

                                        @if ($daysUntilExpiry >= 0)
                                            <div class="alert alert-danger mt-2 mb-0" role="alert">
                                                <i class="fas fa-exclamation-triangle me-2"></i>
                                                <strong>Warning:</strong> This user's professional license has
                                                @if ($daysUntilExpiry > 90)
                                                    <strong>expired</strong>. Please verify license status before approving
                                                    ICT access.
                                                @else
                                                    <strong>expired or is expiring soon</strong>. Please verify license
                                                    renewal before approving ICT access.
                                                @endif
                                            </div>
                                        @endif
                                    @else
                                        <div class="alert alert-info mt-2 mb-0" role="alert">
                                            <i class="fas fa-info-circle me-2"></i>
                                            <strong>License Information Not Available:</strong> License expiration date has
                                            not been recorded. Please verify the license status before approving.
                                        </div>
                                    @endif

                                    @if ($hrWorkflowHistory && $hrWorkflowHistory->professional_reg_verified)
                                        <div class="alert alert-success mt-2 mb-0" role="alert">
                                            <i class="fas fa-check-circle me-2"></i>
                                            <strong>Verified:</strong> Professional registration has been verified by HR.
                                        </div>
                                    @else
                                        <div class="alert alert-warning mt-2 mb-0" role="alert">
                                            <i class="fas fa-exclamation-circle me-2"></i>
                                            <strong>Not Verified:</strong>
                                        </div>
                                    @endif
                                </div>
                            @endif
                        </div>
                    </div>

                    <!-- Access Period Card -->
                    <div class="card shadow-sm mb-4">
                        <div class="card-header bg-white border-bottom">
                            <h5 class="card-title mb-0 section-title">
                                <i class="fas fa-calendar-alt me-2"></i>Access Period
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="text-muted small">Start Date</label>
                                        <p class="mb-0"><strong>{{ $startDate }}</strong></p>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="text-muted small">End Date</label>
                                        <p class="mb-0"><strong>{{ $endDate }}</strong></p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Access Types Card -->
                    <div class="card shadow-sm mb-4">
                        <div class="card-header bg-white border-bottom">
                            <h5 class="card-title mb-0 section-title">
                                <i class="fas fa-key me-2"></i>Access Types
                            </h5>
                        </div>
                        <div class="card-body">
                            <!-- Domain Access -->
                            @if ($ictForm->active_drt && $activeDrtPrivilege)
                                <div class="mb-4 p-3" style="border-left: 4px solid #007A33; background-color: #f8f9fa;">
                                    <h6 class="mb-3">
                                        <i class="fas fa-network-wired me-2"></i>Domain Access (Active Directory)
                                    </h6>
                                    <p class="mb-0">
                                        <strong>Access Level:</strong>
                                        <span class="badge badge-info">{{ $activeDrtPrivilege->prv_name }}</span>
                                    </p>
                                </div>
                            @endif

                            <!-- Email Access -->
                            @if ($ictForm->email && $emailPrivilege)
                                <div class="mb-4 p-3" style="border-left: 4px solid #007A33; background-color: #f8f9fa;">
                                    <h6 class="mb-3">
                                        <i class="fas fa-envelope me-2"></i>Email Access (CCBRT Email Account)
                                    </h6>
                                    <p class="mb-0">
                                        <strong>Access Level:</strong>
                                        <span class="badge badge-info">{{ $emailPrivilege->prv_name }}</span>
                                    </p>
                                </div>
                            @endif

                            <!-- Network Folder Access -->
                            @if ($ictForm->network_folder)
                                <div class="mb-4 p-3" style="border-left: 4px solid #007A33; background-color: #f8f9fa;">
                                    <h6 class="mb-3">
                                        <i class="fas fa-folder-open me-2"></i>Network Folder Access
                                    </h6>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <p class="mb-2">
                                                <strong>Network Folder:</strong> {{ $ictForm->network_folder }}
                                            </p>
                                        </div>
                                        <div class="col-md-6">
                                            <p class="mb-0">
                                                <strong>Access Level:</strong>
                                                <span
                                                    class="badge badge-info">{{ $folderPrivilege->prv_name ?? 'N/A' }}</span>
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>

                    <!-- Additional System Access Card -->
                    <div class="card shadow-sm mb-4">
                        <div class="card-header bg-white border-bottom">
                            <h5 class="card-title mb-0 section-title">
                                <i class="fas fa-desktop me-2"></i>Additional System Access
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                @if ($hmaccess && $hmaccess->count() > 0)
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="text-muted small">HealthAI HMIS Access</label>
                                            <p class="mb-0">
                                                @foreach ($hmaccess as $access)
                                                    <span class="badge badge-primary me-1">{{ $access->names }}</span>
                                                @endforeach
                                            </p>
                                        </div>
                                    </div>
                                @endif
                                @if ($ictForm->aruti && $arutiPrivilege)
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="text-muted small">Aruti HR MIS</label>
                                            <p class="mb-0">
                                                <span
                                                    class="badge badge-info">{{ $arutiPrivilege->aruti_name ?? 'N/A' }}</span>
                                            </p>
                                        </div>
                                    </div>
                                @endif
                                @if (isset($edocsLevels) && $edocsLevels->count() > 0)
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="text-muted small">eDocs System</label>
                                            <p class="mb-0">
                                                @foreach ($edocsLevels as $edocsLevel)
                                                    <span
                                                        class="badge badge-info me-1">{{ $edocsLevel->edocs_name }}</span>
                                                @endforeach
                                            </p>
                                        </div>
                                    </div>
                                @endif
                                @if ($vpnPrivilege)
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="text-muted small">Network Access (VPN)</label>
                                            <p class="mb-0">
                                                <span class="badge badge-info">{{ $vpnPrivilege->prv_name }}</span>
                                            </p>
                                        </div>
                                    </div>
                                @endif
                                @if ($sapLevel)
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="text-muted small">SAP ERP Access</label>
                                            <p class="mb-0">
                                                <span class="badge badge-info">{{ $sapLevel->access_name }}</span>
                                            </p>
                                        </div>
                                    </div>
                                @endif
                                @if ($pbaxPrivilege)
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="text-muted small">Call Manager-PABX</label>
                                            <p class="mb-0">
                                                <span class="badge badge-info">{{ $pbaxPrivilege->prv_name }}</span>
                                            </p>
                                        </div>
                                    </div>
                                @endif
                                @if ($accessKeyCards && $accessKeyCards->count() > 0)
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="text-muted small">Access Key Cards</label>
                                            <p class="mb-0">
                                                @foreach ($accessKeyCards as $card)
                                                    <span class="badge badge-info me-1">{{ $card->card_number }}
                                                        @if ($card->notes)
                                                            - {{ $card->notes }}
                                                        @endif
                                                    </span>
                                                @endforeach
                                            </p>
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>

                    <!-- Hardware Request Card -->
                    @php
                        // Get hardware_request directly from the model if available
                        $hardwareRequest = null;
                        if (isset($ictForm->hardware_request)) {
                            $hardwareRequest = $ictForm->hardware_request;
                        } else {
                            // Try to get it from the access_id
                            $ictResource = \App\Models\IctAccessResource::find($ictForm->access_id ?? null);
                            $hardwareRequest = $ictResource->hardware_request ?? null;
                        }

                        $hardwareItems = [];
                        if (!empty($hardwareRequest) && is_string($hardwareRequest) && trim($hardwareRequest) !== '') {
                            $hardwareItems = array_filter(array_map('trim', explode(',', $hardwareRequest)), function (
                                $item,
                            ) {
                                return !empty(trim($item));
                            });
                        }
                    @endphp
                    @if (!empty($hardwareItems) && count($hardwareItems) > 0)
                        <div class="card shadow-sm mb-4" id="hardwareRequestCard">
                            <div class="card-header bg-white border-bottom">
                                <h5 class="card-title mb-0 section-title">
                                    <i class="fas fa-laptop me-2"></i>Hardware Request
                                </h5>
                            </div>
                            <div class="card-body">
                                <div id="hardwareItemsContainer">
                                    @foreach ($hardwareItems as $index => $item)
                                        <span class="badge me-1 mb-1 hardware-item-badge" data-item="{{ $item }}"
                                            style="background-color: #007A33; color: white; font-size: 0.9rem; padding: 0.5em 0.75em; font-weight: 500; position: relative; display: inline-block;">
                                            {{ $item }}
                                            @if ($ictForm->status == 0)
                                                <button type="button" class="btn-remove-hardware"
                                                    data-access-id="{{ $ictForm->access_id }}"
                                                    data-item="{{ $item }}"
                                                    style="background: rgba(255,255,255,0.25); border: 1px solid rgba(255,255,255,0.5); color: white; border-radius: 50%; width: 20px; height: 20px; line-height: 1; padding: 0; margin-left: 8px; cursor: pointer; font-size: 14px; font-weight: bold; vertical-align: middle; transition: all 0.2s;"
                                                    onmouseover="this.style.background='rgba(255,255,255,0.4)'; this.style.borderColor='rgba(255,255,255,0.8)';"
                                                    onmouseout="this.style.background='rgba(255,255,255,0.25)'; this.style.borderColor='rgba(255,255,255,0.5)';"
                                                    title="Remove this item">
                                                    ×
                                                </button>
                                            @endif
                                        </span>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    @endif

                    <!-- Approval Workflow Card -->
                    <div class="card shadow-sm mb-4">
                        <div class="card-header bg-white border-bottom">
                            <h5 class="card-title mb-0 section-title">
                                <i class="fas fa-tasks me-2"></i>Approval Workflow
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered table-hover">
                                    <thead style="background-color: #007A33; color: white !important;">
                                        <tr>
                                            <th style="color: white !important;">Approval Level</th>
                                            <th style="color: white !important;">Approver Name</th>
                                            <th style="color: white !important;">Signature</th>
                                            <th style="color: white !important;">Date</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <!-- Requester -->
                                        <tr>
                                            <td><strong>Requester</strong></td>
                                            <td>{{ $ictForm->fname }} {{ $ictForm->lname }}</td>
                                            <td class="text-center">
                                                @if ($ictForm->signature)
                                                    <img src="data:image/png;base64,{{ $ictForm->signature }}"
                                                        alt="Requester Signature"
                                                        style="max-width: 100px; max-height: 40px; object-fit: contain;">
                                                @else
                                                    <span class="text-muted">—</span>
                                                @endif
                                            </td>
                                            <td>{{ $submittedDate }}</td>
                                        </tr>

                                        <!-- Line Manager -->
                                        @if ($lineManager)
                                            <tr>
                                                <td><strong>Line Manager</strong></td>
                                                <td>{{ trim(($lineManager->fname ?? '') . ' ' . ($lineManager->lname ?? '')) }}
                                                </td>
                                                <td class="text-center">
                                                    @if ($lineManager->signature)
                                                        <img src="data:image/png;base64,{{ $lineManager->signature }}"
                                                            alt="Line Manager Signature"
                                                            style="max-width: 100px; max-height: 40px; object-fit: contain;">
                                                    @else
                                                        <span class="text-muted">—</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    @if ($lineManager->updated_at)
                                                        {{ \Carbon\Carbon::parse($lineManager->updated_at)->format('d F Y') }}
                                                    @else
                                                        <span class="text-muted">—</span>
                                                    @endif
                                                </td>
                                            </tr>
                                        @endif

                                        <!-- Approver (HEC Member) -->
                                        @if ($approver)
                                            <tr>
                                                <td><strong>Approver
                                                        ({{ $ictForm->hec_level_name ?? 'HEC Member' }})</strong></td>
                                                <td>{{ trim(($approver->fname ?? '') . ' ' . ($approver->lname ?? '')) }}
                                                </td>
                                                <td class="text-center">
                                                    @if ($approver->signature)
                                                        <img src="data:image/png;base64,{{ $approver->signature }}"
                                                            alt="Approver Signature"
                                                            style="max-width: 100px; max-height: 40px; object-fit: contain;">
                                                    @else
                                                        <span class="text-muted">—</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    @if ($approver->updated_at)
                                                        {{ \Carbon\Carbon::parse($approver->updated_at)->format('d F Y') }}
                                                    @else
                                                        <span class="text-muted">—</span>
                                                    @endif
                                                </td>
                                            </tr>
                                        @endif

                                        <!-- HR Officer -->
                                        @if ($hrOfficer)
                                            <tr>
                                                <td><strong>HR Officer</strong></td>
                                                <td>{{ trim(($hrOfficer->fname ?? '') . ' ' . ($hrOfficer->lname ?? '')) }}
                                                </td>
                                                <td class="text-center">
                                                    @if ($hrOfficer->signature)
                                                        <img src="data:image/png;base64,{{ $hrOfficer->signature }}"
                                                            alt="HR Officer Signature"
                                                            style="max-width: 100px; max-height: 40px; object-fit: contain;">
                                                    @else
                                                        <span class="text-muted">—</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    @if ($hrOfficer->updated_at)
                                                        {{ \Carbon\Carbon::parse($hrOfficer->updated_at)->format('d F Y') }}
                                                    @else
                                                        <span class="text-muted">—</span>
                                                    @endif
                                                </td>
                                            </tr>
                                        @endif

                                        <!-- IT Officer -->
                                        @if ($itOfficer)
                                            <tr>
                                                <td><strong>IT Officer</strong></td>
                                                <td>{{ trim(($itOfficer->fname ?? '') . ' ' . ($itOfficer->lname ?? '')) }}
                                                </td>
                                                <td class="text-center">
                                                    @if ($itOfficer->signature)
                                                        <img src="data:image/png;base64,{{ $itOfficer->signature }}"
                                                            alt="IT Officer Signature"
                                                            style="max-width: 100px; max-height: 40px; object-fit: contain;">
                                                    @else
                                                        <span class="text-muted">—</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    @if ($itOfficer->updated_at)
                                                        {{ \Carbon\Carbon::parse($itOfficer->updated_at)->format('d F Y') }}
                                                    @else
                                                        <span class="text-muted">—</span>
                                                    @endif
                                                </td>
                                            </tr>
                                        @endif
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="card shadow-sm mb-4">
                        <div class="card-body">
                            <div class="d-flex gap-2">
                                <button type="button" class="btn btn-secondary" onclick="window.history.back()">
                                    <i class="fas fa-arrow-left me-1"></i> Back
                                </button>

                                @if ($ictForm->status == 0)
                                    <button type="button" class="btn btn-success"
                                        onclick="approveForm('{{ $ictForm->access_id }}')">
                                        <i class="fas fa-check me-1"></i> Approve
                                    </button>

                                    <button type="button" class="btn btn-danger"
                                        onclick="rejectForm('{{ $ictForm->access_id }}')">
                                        <i class="fas fa-times me-1"></i> Reject
                                    </button>
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

        .section-title i,
        .card-body h6 i {
            color: #007A33;
        }

        .card-header.bg-white {
            background-color: #ffffff !important;
            border-bottom: 1px solid #e9ecef;
        }

        /* Ensure table header text is white */
        .table thead th {
            color: white !important;
            background-color: #007A33 !important;
        }
    </style>
    @push('scripts')
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
        <script>
            // Wait for Swal to be available before defining functions
            (function() {
                // Function to check if Swal is loaded
                function waitForSwal(callback, maxAttempts) {
                    maxAttempts = maxAttempts || 50; // 5 seconds max wait
                    var attempts = 0;

                    function check() {
                        if (typeof Swal !== 'undefined') {
                            callback();
                        } else if (attempts < maxAttempts) {
                            attempts++;
                            setTimeout(check, 100);
                        } else {
                            console.error('SweetAlert2 failed to load after 5 seconds');
                            alert('SweetAlert2 is not loaded. Please refresh the page and try again.');
                        }
                    }
                    check();
                }

                // Define functions after Swal is loaded
                waitForSwal(function() {
                    // Define functions globally so they're available for onclick handlers
                    // Make approveForm globally accessible
                    window.approveForm = function(accessId) {
                        // Double check Swal is available
                        if (typeof Swal === 'undefined') {
                            alert('SweetAlert2 is not loaded. Please refresh the page and try again.');
                            console.error('Swal is not defined');
                            return;
                        }

                        console.log('[approveForm] Starting approval for accessId:', accessId);

                        $.ajaxSetup({
                            headers: {
                                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                            }
                        });

                        Swal.fire({
                            title: 'Confirm Approval',
                            text: 'Are you sure you want to approve this form?',
                            icon: 'warning',
                            showCancelButton: true,
                            confirmButtonText: 'Yes, Approve',
                            cancelButtonText: 'Cancel',
                            reverseButtons: true,
                            confirmButtonColor: '#007A33'
                        }).then(result => {
                            if (result.isConfirmed) {
                                console.log('[approveForm] User confirmed approval');
                                Swal.fire({
                                    title: 'Processing...',
                                    text: 'Please wait while the form is being approved.',
                                    allowOutsideClick: false,
                                    allowEscapeKey: false,
                                    didOpen: () => {
                                        Swal.showLoading();
                                        console.log('[approveForm] Loading spinner shown');
                                    }
                                });

                                console.log('[approveForm] Sending AJAX POST to /approve_form with access_id:', accessId);

                                $.ajax({
                                    method: 'POST',
                                    url: '/approve_form',
                                    data: {
                                        access_id: accessId,
                                        _token: $('meta[name="csrf-token"]').attr('content')
                                    },
                                    dataType: 'json',
                                    headers: {
                                        'Accept': 'application/json',
                                        'X-Requested-With': 'XMLHttpRequest'
                                    },
                                    success: response => {
                                        console.log('[approveForm] AJAX success. Response:', response);
                                        if (response.success) {
                                            Swal.fire({
                                                title: 'Success',
                                                text: response.message || 'Form approved successfully.',
                                                icon: 'success',
                                                confirmButtonText: 'OK',
                                                confirmButtonColor: '#007A33'
                                            }).then(() => {
                                                window.location.href = '/requestapprove';
                                            });
                                        } else {
                                            console.error('[approveForm] Server returned success:false', response);
                                            Swal.fire({
                                                title: 'Error',
                                                text: response.message || 'Failed to approve form.',
                                                icon: 'error',
                                                confirmButtonText: 'OK'
                                            });
                                        }
                                    },
                                    error: (xhr, status, error) => {
                                        console.error('[approveForm] AJAX error. Status:', status, 'Error:', error);
                                        console.error('[approveForm] XHR object:', xhr);
                                        console.error('[approveForm] Response text:', xhr.responseText);
                                        let errorMessage = 'Failed to approve form. Please try again.';

                                        if (xhr.responseJSON) {
                                            if (xhr.responseJSON.message) {
                                                errorMessage = xhr.responseJSON.message;
                                            } else if (xhr.responseJSON.error) {
                                                errorMessage = xhr.responseJSON.error;
                                            }
                                            if (xhr.responseJSON.errors) {
                                                const errors = Object.values(xhr.responseJSON.errors).flat();
                                                errorMessage = errors.join('\n');
                                            }
                                        } else if (xhr.status === 0) {
                                            errorMessage = 'Network error. Please check your connection.';
                                        } else if (xhr.status === 403) {
                                            errorMessage = 'Permission denied. You may not have the required permissions to approve this form.';
                                        } else if (xhr.status === 404) {
                                            errorMessage = 'Form or workflow not found. It may have been already processed.';
                                        } else if (xhr.status === 500) {
                                            errorMessage = 'Server error. Please try again later.';
                                        }

                                        Swal.fire({
                                            title: 'Error',
                                            text: errorMessage,
                                            icon: 'error',
                                            confirmButtonText: 'OK',
                                            confirmButtonColor: '#dc3545'
                                        });
                                    },
                                    complete: () => {
                                        console.log('[approveForm] AJAX request completed');
                                    }
                                });
                            }
                        });
                    };

                    // Make rejectForm globally accessible
                    window.rejectForm = function(accessId) {
                        // Double check Swal is available
                        if (typeof Swal === 'undefined') {
                            return;
                        }

                        var modalEl = document.getElementById('rejectCommentModal');
                        if (!modalEl || typeof bootstrap === 'undefined') {
                            Swal.fire({
                                title: 'Error',
                                text: 'Reject modal is not available. Please refresh and try again.',
                                icon: 'error',
                                confirmButtonColor: '#dc3545'
                            });
                            return;
                        }

                        modalEl.dataset.accessId = accessId;
                        var reasonEl = document.getElementById('reject_reason');
                        var errorEl = document.getElementById('reject_reason_error');
                        if (reasonEl) reasonEl.value = '';
                        if (errorEl) errorEl.style.display = 'none';

                        var modal = bootstrap.Modal.getOrCreateInstance(modalEl);
                        modal.show();
                    };

                    // Modal confirm reject
                    var confirmBtn = document.getElementById('confirmRejectBtn');
                    if (confirmBtn) {
                        confirmBtn.addEventListener('click', function() {
                            var modalEl = document.getElementById('rejectCommentModal');
                            var accessId = modalEl ? modalEl.dataset.accessId : null;
                            var reasonEl = document.getElementById('reject_reason');
                            var reason = reasonEl ? reasonEl.value.trim() : '';
                            var errorEl = document.getElementById('reject_reason_error');

                            if (!reason) {
                                if (errorEl) errorEl.style.display = 'block';
                                return;
                            }
                            if (errorEl) errorEl.style.display = 'none';

                            Swal.fire({
                                title: 'Processing...',
                                text: 'Please wait while the form is being rejected.',
                                allowOutsideClick: false,
                                allowEscapeKey: false,
                                didOpen: () => {
                                    Swal.showLoading();
                                }
                            });

                            $.ajax({
                                method: 'POST',
                                url: '/reject_form',
                                data: {
                                    access_id: accessId,
                                    reason: reason,
                                    _token: $('meta[name="csrf-token"]').attr('content')
                                },
                                dataType: 'json',
                                headers: {
                                    'Accept': 'application/json',
                                    'X-Requested-With': 'XMLHttpRequest'
                                },
                                success: response => {
                                    if (response.success) {
                                        try {
                                            if (modalEl && typeof bootstrap !== 'undefined') {
                                                bootstrap.Modal.getOrCreateInstance(modalEl).hide();
                                            }
                                        } catch (e) {}

                                        Swal.fire({
                                            title: 'Rejected',
                                            text: response.message || 'Form rejected successfully.',
                                            icon: 'success',
                                            confirmButtonColor: '#007A33'
                                        }).then(() => {
                                            window.location.href = '/requestapprove';
                                        });
                                    } else {
                                        Swal.fire({
                                            title: 'Error',
                                            text: response.message || 'Failed to reject form.',
                                            icon: 'error',
                                            confirmButtonColor: '#dc3545'
                                        });
                                    }
                                },
                                error: xhr => {
                                    let errorMessage = 'Server error. Please try again later.';
                                    if (xhr.responseJSON && xhr.responseJSON.message) {
                                        errorMessage = xhr.responseJSON.message;
                                    }
                                    Swal.fire({
                                        title: 'Error',
                                        text: errorMessage,
                                        icon: 'error',
                                        confirmButtonColor: '#dc3545'
                                    });
                                }
                            });
                        });
                    }
                }); // End of waitForSwal callback
            })(); // End of IIFE

            // Wait for DOM and Swal to be ready
            $(document).ready(function() {
                        // Ensure Swal is available
                        if (typeof Swal === 'undefined') {
                            console.error('SweetAlert2 is not loaded. Please refresh the page.');
                        }

                        // Handle hardware item removal
                        $(document).on('click', '.btn-remove-hardware', function(e) {
                            e.preventDefault();
                            e.stopPropagation();

                            const button = $(this);
                            const accessId = button.data('access-id');
                            const item = button.data('item');
                            const badge = button.closest('.hardware-item-badge');

                            Swal.fire({
                                title: 'Remove Hardware Item?',
                                text: `Are you sure you want to remove "${item}"?`,
                                icon: 'warning',
                                showCancelButton: true,
                                confirmButtonText: 'Yes, Remove',
                                cancelButtonText: 'Cancel',
                                reverseButtons: true,
                                confirmButtonColor: '#dc3545'
                            }).then((result) => {
                                if (result.isConfirmed) {
                                    // Show loading
                                    button.prop('disabled', true).html('...');

                                    $.ajax({
                                        method: 'POST',
                                        url: '/remove_hardware_item',
                                        data: {
                                            access_id: accessId,
                                            item: item,
                                            _token: $('meta[name="csrf-token"]').attr('content')
                                        },
                                        dataType: 'json',
                                        headers: {
                                            'Accept': 'application/json',
                                            'X-Requested-With': 'XMLHttpRequest'
                                        },
                                        success: response => {
                                            if (response.success) {
                                                // Remove the badge from DOM
                                                badge.fadeOut(300, function() {
                                                    $(this).remove();

                                                    // Check if there are any remaining items
                                                    const remainingItems = $(
                                                        '#hardwareItemsContainer .hardware-item-badge'
                                                    );
                                                    if (remainingItems.length === 0) {
                                                        // Hide the entire card if no items left
                                                        $('#hardwareRequestCard').fadeOut(
                                                            300,
                                                            function() {
                                                                $(this).remove();
                                                            });
                                                    }
                                                });

                                                Swal.fire({
                                                    title: 'Removed',
                                                    text: response.message ||
                                                        'Hardware item removed successfully.',
                                                    icon: 'success',
                                                    confirmButtonText: 'OK',
                                                    confirmButtonColor: '#007A33',
                                                    timer: 2000,
                                                    timerProgressBar: true
                                                });
                                            } else {
                                                button.prop('disabled', false).html('×');
                                                Swal.fire({
                                                    title: 'Error',
                                                    text: response.message ||
                                                        'Failed to remove hardware item.',
                                                    icon: 'error',
                                                    confirmButtonText: 'OK'
                                                });
                                            }
                                        },
                                        error: (xhr, status, error) => {
                                            button.prop('disabled', false).html('×');
                                            console.error('Remove hardware item failed:', error, xhr
                                                .responseJSON);
                                            let errorMessage =
                                                'Failed to remove hardware item. Please try again.';

                                            if (xhr.responseJSON) {
                                                if (xhr.responseJSON.message) {
                                                    errorMessage = xhr.responseJSON.message;
                                                } else if (xhr.responseJSON.error) {
                                                    errorMessage = xhr.responseJSON.error;
                                                }
                                            }

                                            Swal.fire({
                                                title: 'Error',
                                                text: errorMessage,
                                                icon: 'error',
                                                confirmButtonText: 'OK',
                                                confirmButtonColor: '#dc3545'
                                            });
                                        }
                                    });
                                }
                            });
                        });
                        // Check if Swal is available
                        if (typeof Swal === 'undefined') {
                            alert('SweetAlert2 is not loaded. Please refresh the page and try again.');
                            console.error('Swal is not defined');
                            return;
                        }

                        $.ajaxSetup({
                            headers: {
                                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                            }
                        });

                        Swal.fire({
                            title: 'Confirm Approval',
                            text: 'Are you sure you want to approve this form?',
                            icon: 'warning',
                            showCancelButton: true,
                            confirmButtonText: 'Yes, Approve',
                            cancelButtonText: 'Cancel',
                            reverseButtons: true,
                            confirmButtonColor: '#007A33'
                        }).then(result => {
                            if (result.isConfirmed) {
                                Swal.fire({
                                    title: 'Processing...',
                                    text: 'Please wait while the form is being approved.',
                                    allowOutsideClick: false,
                                    allowEscapeKey: false,
                                    didOpen: () => {
                                        Swal.showLoading();
                                    }
                                });

                                $.ajax({
                                    method: 'POST',
                                    url: '/approve_form',
                                    data: {
                                        access_id: accessId,
                                        _token: $('meta[name="csrf-token"]').attr('content')
                                    },
                                    dataType: 'json',
                                    headers: {
                                        'Accept': 'application/json',
                                        'X-Requested-With': 'XMLHttpRequest'
                                    },
                                    success: response => {
                                        if (response.success) {
                                            Swal.fire({
                                                title: 'Success',
                                                text: response.message ||
                                                    'Form approved successfully.',
                                                icon: 'success',
                                                confirmButtonText: 'OK',
                                                confirmButtonColor: '#007A33'
                                            }).then(() => {
                                                window.location.href = '/requestapprove';
                                            });
                                        } else {
                                            Swal.fire({
                                                title: 'Error',
                                                text: response.message || 'Failed to approve form.',
                                                icon: 'error',
                                                confirmButtonText: 'OK'
                                            });
                                        }
                                    },
                                    error: (xhr, status, error) => {
                                        console.error('Approval failed:', error, xhr.responseJSON);
                                        let errorMessage = 'Failed to approve form. Please try again.';

                                        if (xhr.responseJSON) {
                                            if (xhr.responseJSON.message) {
                                                errorMessage = xhr.responseJSON.message;
                                            } else if (xhr.responseJSON.error) {
                                                errorMessage = xhr.responseJSON.error;
                                            }

                                            // Handle validation errors
                                            if (xhr.responseJSON.errors) {
                                                const errors = Object.values(xhr.responseJSON.errors)
                                                    .flat();
                                                errorMessage = errors.join('\n');
                                            }
                                        } else if (xhr.status === 0) {
                                            errorMessage = 'Network error. Please check your connection.';
                                        } else if (xhr.status === 500) {
                                            errorMessage = 'Server error. Please try again later.';
                                        }

                                        Swal.fire({
                                            title: 'Error',
                                            text: errorMessage,
                                            icon: 'error',
                                            confirmButtonText: 'OK',
                                            confirmButtonColor: '#dc3545'
                                        });
                                    }
                                });
                            }
                        });
                    }

                    // Make rejectForm globally accessible
                    window.rejectForm = function(accessId) {
                        // Check if Swal is available
                        if (typeof Swal === 'undefined') {
                            alert('SweetAlert2 is not loaded. Please refresh the page and try again.');
                            console.error('Swal is not defined');
                            return;
                        }

                        Swal.fire({
                            title: 'Reason for Rejection',
                            text: 'Please provide a reason for rejecting this form:',
                            input: 'textarea',
                            inputPlaceholder: 'Enter reason here...',
                            showCancelButton: true,
                            confirmButtonText: 'Reject',
                            cancelButtonText: 'Cancel',
                            reverseButtons: true,
                            confirmButtonColor: '#dc3545',
                            inputValidator: value => !value && 'You must provide a reason!'
                        }).then(result => {
                            if (result.isConfirmed) {
                                Swal.fire({
                                    title: 'Processing...',
                                    text: 'Please wait while the form is being rejected.',
                                    allowOutsideClick: false,
                                    allowEscapeKey: false,
                                    didOpen: () => {
                                        Swal.showLoading();
                                    }
                                });

                                $.ajax({
                                    method: 'POST',
                                    url: '/reject_form',
                                    data: {
                                        access_id: accessId,
                                        reason: result.value,
                                        _token: $('meta[name="csrf-token"]').attr('content')
                                    },
                                    dataType: 'json',
                                    headers: {
                                        'Accept': 'application/json',
                                        'X-Requested-With': 'XMLHttpRequest'
                                    },
                                    success: response => {
                                        if (response.success) {
                                            Swal.fire({
                                                title: 'Rejected',
                                                text: response.message ||
                                                    'Form rejected successfully.',
                                                icon: 'success',
                                                confirmButtonText: 'OK',
                                                confirmButtonColor: '#007A33'
                                            }).then(() => {
                                                window.location.href = '/requestapprove';
                                            });
                                        } else {
                                            Swal.fire({
                                                title: 'Error',
                                                text: response.message || 'Failed to reject form.',
                                                icon: 'error',
                                                confirmButtonText: 'OK'
                                            });
                                        }
                                    },
                                    error: (xhr, status, error) => {
                                        console.error('Rejection failed:', error, xhr.responseJSON);
                                        let errorMessage = 'Failed to reject form. Please try again.';

                                        if (xhr.responseJSON) {
                                            if (xhr.responseJSON.message) {
                                                errorMessage = xhr.responseJSON.message;
                                            } else if (xhr.responseJSON.error) {
                                                errorMessage = xhr.responseJSON.error;
                                            }

                                            // Handle validation errors
                                            if (xhr.responseJSON.errors) {
                                                const errors = Object.values(xhr.responseJSON.errors)
                                                    .flat();
                                                errorMessage = errors.join('\n');
                                            }
                                        } else if (xhr.status === 0) {
                                            errorMessage = 'Network error. Please check your connection.';
                                        } else if (xhr.status === 500) {
                                            errorMessage = 'Server error. Please try again later.';
                                        }

                                        Swal.fire({
                                            title: 'Error',
                                            text: errorMessage,
                                            icon: 'error',
                                            confirmButtonText: 'OK',
                                            confirmButtonColor: '#dc3545'
                                        });
                                    }
                                });
                            }
                        });
                    }
        </script>
    @endpush
@endsection
