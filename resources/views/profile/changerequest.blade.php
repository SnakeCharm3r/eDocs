@extends('layouts.template')

@section('breadcrumb')
    @include('sweetalert::alert')
@endsection

@section('content')
    {{-- needed for AJAX --}}
    <meta name="csrf-token" content="{{ csrf_token() }}">
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

        .priority-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 15px;
            margin-top: 15px;
        }

        .priority-item {
            text-align: center;
            padding: 15px;
            border-radius: 8px;
            background: #f8f9fa;
            border: 2px solid #e9ecef;
        }

        .priority-item.active {
            border-color: #007A33;
            background: #e8f5e9;
        }

        .priority-item h6 {
            margin: 0 0 10px 0;
            font-size: 0.9rem;
            color: #6c757d;
            font-weight: 600;
        }

        .priority-badge {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 15px;
            font-size: 0.85rem;
            font-weight: 600;
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



        .price-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }

        .price-table thead {
            background: #007A33;
            color: white;
        }

        .price-table th,
        .price-table td {
            border: 1px solid #ddd;
            padding: 10px;
            text-align: center;
        }

        .price-table tbody tr:hover {
            background: #f8f9fa;
        }

        .button-group {
            display: flex;
            justify-content: space-between;
            align-items: center;
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
            .priority-grid {
                grid-template-columns: 1fr;
            }

            .pdf-like-container {
                padding: 15px;
            }
        }
    </style>

    <div class="page-wrapper">
        <div class="content container-fluid">
            <!-- PDF-Like Content -->
            <div class="pdf-like-container">
                <!-- Custom Header -->
                <div class="pdf-header text-center border-bottom pb-3 mb-4">
                    <h3>Change Request Form #{{ $changeRequest->id }}</h3>
            </div>

                <!-- Alerts -->
                @if (session('success'))
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        {{ session('success') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif
                @if (session('error'))
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        {{ session('error') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif

                @php
                    $changeType = $changeRequest->change_type ?? 'non_price';
                    $isPriceChange = $changeType === 'price';
                    $status = $changeRequest->workflow->work_flow_status ?? 'Pending';
                    $isCompleted = $changeRequest->workflow->work_flow_completed ?? 0;
                @endphp

                {{-- Originator Details --}}
                <div class="pdf-section">
                    <h5>Originator Details</h5>
                    <table class="info-table">
                        <tr>
                            <th>Requester's Full Name</th>
                            <td>
                            @if ($changeRequest->user)
                                {{ $changeRequest->user->fname ?? '' }} {{ $changeRequest->user->mname ?? '' }}
                                {{ $changeRequest->user->lname ?? '' }}
                            @else
                                N/A
                            @endif
                        </td>
                    </tr>
                    <tr>
                            <th>Department</th>
                        <td>{{ $changeRequest->user->department->dept_name ?? 'N/A' }}</td>
                    </tr>
                    <tr>
                            <th>Submission Date</th>
                            <td><i class="fas fa-calendar"></i> {{ $changeRequest->created_at->format('d M Y, h:i A') }}</td>
                    </tr>
                        <tr>
                            <th>Change Type</th>
                            <td>
                            <span class="badge"
                                style="background: #007A33; color: white; padding: 6px 12px; border-radius: 5px;">
                                    {{ $isPriceChange ? 'Price Change' : 'Non-Price Change' }}
                                </span>
                            </td>
                        </tr>
                        @if ($changeRequest->change_category)
                        <tr>
                                <th>Change Category</th>
                            <td>{{ $changeRequest->change_category }}</td>
                        </tr>
                        @endif
                    </table>
                </div>

                {{-- Priority --}}
                <div class="pdf-section">
                    <h5>Priority Level</h5>
                    <div class="priority-grid">
                    <div class="priority-item {{ $changeRequest->priority === 'P3-Low' ? 'active' : '' }}">
                        <h6>P3 - Low</h6>
                        @if ($changeRequest->priority === 'P3-Low')
                            <span class="priority-badge" style="background: #28a745; color: white;">
                                <i class="fas fa-check-circle"></i> Selected
                            </span>
                        @else
                            <span style="color: #6c757d; font-size: 1.5rem;">○</span>
                        @endif
                    </div>
                    <div class="priority-item {{ $changeRequest->priority === 'P2-Medium' ? 'active' : '' }}">
                        <h6>P2 - Medium</h6>
                        @if ($changeRequest->priority === 'P2-Medium')
                            <span class="priority-badge" style="background: #ffc107; color: #333;">
                                <i class="fas fa-check-circle"></i> Selected
                            </span>
                        @else
                            <span style="color: #6c757d; font-size: 1.5rem;">○</span>
                        @endif
                    </div>
                    <div class="priority-item {{ $changeRequest->priority === 'P1-High' ? 'active' : '' }}">
                        <h6>P1 - High</h6>
                        @if ($changeRequest->priority === 'P1-High')
                            <span class="priority-badge" style="background: #dc3545; color: white;">
                                <i class="fas fa-check-circle"></i> Selected
                            </span>
                        @else
                            <span style="color: #6c757d; font-size: 1.5rem;">○</span>
                        @endif
                    </div>
                </div>

                {{-- Description --}}
                <div class="pdf-section">
                    <h5>Description of Change(s)</h5>
                    <div class="content-box">
                        {!! nl2br(e($changeRequest->description_of_change ?? 'N/A')) !!}
                    </div>
                </div>

                {{-- Reason --}}
                <div class="pdf-section">
                    <h5>Reason for Change</h5>
                    <div class="content-box">
                        {!! nl2br(e($changeRequest->reason_for_change ?? 'N/A')) !!}
                    </div>
                </div>

                {{-- Tariff Details --}}
                @if ($isPriceChange && $changeRequest->tariff_type)
                    <div class="pdf-section">
                        <h5>Tariff Details</h5>
                        <table class="info-table">
                            <tr>
                                <th>Tariff Type</th>
                                <td>
                                <span class="badge"
                                    style="background: #007A33; color: white; padding: 6px 12px; border-radius: 5px;">
                                    {{ $changeRequest->tariff_type === 'new_tariff' ? 'New Tariff' : 'Edit Tariff' }}
                                </span>
                            </td>
                        </tr>
                        @if ($changeRequest->tariff_category_id)
                            <tr>
                                <th>Tariff Category</th>
                                <td>{{ $changeRequest->tariffCategory->name ?? 'N/A' }}</td>
                            </tr>
                        @endif
                        @if ($changeRequest->tariff_type === 'edit_tariff' && $changeRequest->current_tariff_name)
                            <tr>
                                <th>Current Tariff Name</th>
                                <td>{{ $changeRequest->current_tariff_name }}</td>
                            </tr>
                        @endif
                        @if ($changeRequest->tariff_name)
                            <tr>
                                <th>{{ $changeRequest->tariff_type === 'new_tariff' ? 'Tariff Name' : 'New Tariff Name' }}
                                </th>
                                <td>{{ $changeRequest->tariff_name }}</td>
                            </tr>
                        @endif
                    </table>
                </div>
                @endif

                {{-- Service Details --}}
                @if ($isPriceChange && $changeRequest->service_action_type)
                    <div class="pdf-section">
                        <h5>Service Details</h5>
                        <table class="info-table">
                            <tr>
                                <th>Service Action Type</th>
                                <td>
                                <span class="badge"
                                    style="background: #007A33; color: white; padding: 6px 12px; border-radius: 5px;">
                                    {{ $changeRequest->service_action_type === 'new_service' ? 'New Service' : 'Edit Service' }}
                                </span>
                            </td>
                        </tr>
                        @if ($changeRequest->service_category_id)
                            <tr>
                                <th>Service Category</th>
                                <td>{{ $changeRequest->serviceCategory->name ?? 'N/A' }}</td>
                            </tr>
                        @endif
                        @if ($changeRequest->service_action_type === 'edit_service' && $changeRequest->current_service_name)
                            <tr>
                                <th>Current Service Name</th>
                                <td>{{ $changeRequest->current_service_name }}</td>
                            </tr>
                        @endif
                        @if ($changeRequest->service_name)
                            <tr>
                                <th>{{ $changeRequest->service_action_type === 'new_service' ? 'Service Name' : 'New Service Name' }}
                                </th>
                                <td>{{ $changeRequest->service_name }}</td>
                            </tr>
                        @endif
                        @if ($changeRequest->service_action_type === 'new_service' && $changeRequest->service_prices)
                            <tr>
                                <td colspan="2">
                                    <strong>Service Prices:</strong>
                                    @php
                                        $servicePrices = is_array($changeRequest->service_prices)
                                            ? $changeRequest->service_prices
                                            : json_decode($changeRequest->service_prices, true);
                    @endphp
                                    @if ($servicePrices && is_array($servicePrices))
                                        <table class="price-table">
                                            <thead>
                                                <tr>
                                                    <th>Payment Type</th>
                                                    <th>Price</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                                @foreach ($servicePrices as $type => $price)
                                                    <tr>
                                                        <td><strong>{{ ucfirst(str_replace('_', ' ', $type)) }}</strong>
                                                        </td>
                                                        <td>{{ number_format($price, 2) }} TZS</td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    @endif
                                </td>
                            </tr>
                        @endif
                        </table>
                    </div>
                @endif

                {{-- Price Change Details --}}
                @if ($isPriceChange && $changeRequest->price_item_name)
                    <div class="pdf-section">
                        <h5>Price Change Details</h5>
                        <table class="info-table">
                            <tr>
                                <th>Item/Service Name</th>
                                <td>{{ $changeRequest->price_item_name }}</td>
                            </tr>
                            @if ($changeRequest->price_change_reason)
                                <tr>
                                    <th>Price Change Reason</th>
                                    <td>{{ $changeRequest->price_change_reason }}</td>
                                </tr>
                            @endif
                        @php
                            $currentPrices = is_array($changeRequest->current_price)
                                ? $changeRequest->current_price
                                : (is_string($changeRequest->current_price)
                                    ? json_decode($changeRequest->current_price, true)
                                    : []);
                            $newPrices = is_array($changeRequest->new_price)
                                ? $changeRequest->new_price
                                : (is_string($changeRequest->new_price)
                                    ? json_decode($changeRequest->new_price, true)
                                    : []);
                        @endphp
                        @if (!empty($currentPrices) || !empty($newPrices))
                            <tr>
                                <td colspan="2">
                                    <strong>Price Comparison:</strong>
                                    <table class="price-table">
                                        <thead>
                                            <tr>
                                                <th>Payment Type</th>
                                                <th>Current Price</th>
                                                <th>New Price</th>
                                                <th>Difference</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @php
                                                $allTypes = array_unique(
                                                    array_merge(
                                                        array_keys($currentPrices ?? []),
                                                        array_keys($newPrices ?? []),
                                                    ),
                                                );
                                            @endphp
                                            @foreach ($allTypes as $type)
                                                @php
                                                    $current = $currentPrices[$type] ?? 0;
                                                    $new = $newPrices[$type] ?? 0;
                                                    $difference = $new - $current;
                                                @endphp
                                                <tr>
                                                    <td><strong>{{ ucfirst(str_replace('_', ' ', $type)) }}</strong></td>
                                                    <td>{{ number_format($current, 2) }} TZS</td>
                                                    <td>{{ number_format($new, 2) }} TZS</td>
                                                    <td
                                                        style="color: {{ $difference > 0 ? '#28a745' : ($difference < 0 ? '#dc3545' : '#6c757d') }}; font-weight: 600;">
                                                        {{ $difference > 0 ? '+' : '' }}{{ number_format($difference, 2) }}
                                                        TZS
                                                    </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                                </td>
                            </tr>
                    @endif
                        </table>
                </div>
                @endif

                {{-- Implementation Notes --}}
                @if ($changeRequest->implementation_notes)
                    <div class="pdf-section">
                        <h5>Implementation Notes</h5>
                        <div class="content-box">
                            {!! nl2br(e($changeRequest->implementation_notes)) !!}
                        </div>
                </div>
                @endif

                {{-- Supporting Document --}}
                @if ($changeRequest->supporting_document)
                    <div class="pdf-section">
                        <h5>Supporting Document</h5>
                        <div>
                            <a href="{{ asset('storage/' . $changeRequest->supporting_document) }}" target="_blank"
                                class="btn btn-primary mb-3">
                                <i class="fas fa-download"></i> Download Document
                            </a>
                            <div style="border: 1px solid #ddd; border-radius: 5px; overflow: hidden;">
                            <iframe src="{{ asset('storage/' . $changeRequest->supporting_document) }}" width="100%"
                                    height="600px" style="border: none;"></iframe>
                            </div>
                        </div>
                    </div>
                @endif

                {{-- Approval Workflow --}}
                <div class="pdf-section">
                    <h5>Approval Workflow</h5>
                    @php
                        $workflowHistories = $changeRequest->workflow->histories ?? collect();
                        $steps = $isPriceChange
                            ? ['Line Manager', 'Price Committee', 'HEC Member', 'IT']
                            : ['Line Manager', 'HEC Member', 'IT'];
                        $stepGroups = [];
                        foreach ($steps as $stepName) {
                            $histories = $workflowHistories->where('step_name', $stepName);
                            if ($histories->count() > 0) {
                                $stepGroups[$stepName] = $histories;
                            }
                        }
                    @endphp

                    <table class="info-table">
                        <thead>
                            <tr>
                                <th>Step Name</th>
                                <th>Person</th>
                                <th>Signature</th>
                                <th>Remark</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($stepGroups as $stepName => $histories)
                                @foreach ($histories as $history)
                                    @php
                                        $person = null;
                                        $signature = null;
                                        if ($history->status == 1 && $history->who_approve && $history->approver) {
                                            $person = $history->approver;
                                            $signature = $person->signature ?? null;
                                        } elseif ($history->status == 2) {
                                            $person = $history->attendedBy;
                                        } else {
                                            $person = $history->attendedBy;
                                        }
                                        // For approved items, show comments first (what user entered during approval)
                                        // For rejected items, show rejection_reason first
                                        // For pending items, show remark (default message)
                                        if ($history->status == 1) {
                                            // Approved - prioritize comments (what user entered), then remark
                                            $remark = !empty($history->comments) ? $history->comments : ($history->remark ?? '');
                                        } elseif ($history->status == 2) {
                                            // Rejected - show rejection reason first
                                            $remark = $history->rejection_reason ?? ($history->comments ?? ($history->remark ?? ''));
                                        } else {
                                            // Pending - show remark (default message)
                                            $remark = $history->remark ?? '';
                                        }
                                        // If remark is empty or contains default "Awaiting" messages, show "No"
                                        $defaultMessages = [
                                            'Awaiting Line Manager approval',
                                            'Awaiting approval from Price Committee',
                                            'Awaiting approval from HEC Member',
                                            'Awaiting approval from IT',
                                            'Awaiting Price Committee approval',
                                            'Awaiting HEC Member approval',
                                            'Awaiting HEC Member approval (Line Manager request)',
                                            'Awaiting Price Committee approval (Line Manager request)'
                                        ];
                                        $isDefaultMessage = false;
                                        foreach ($defaultMessages as $defaultMsg) {
                                            if (str_contains($remark, $defaultMsg)) {
                                                $isDefaultMessage = true;
                                                break;
                                            }
                                        }
                                        if (empty(trim($remark)) || $isDefaultMessage) {
                                            $remark = 'No';
                                        }
                                    @endphp
                                    <tr>
                                        <td><strong>{{ $stepName }}</strong></td>
                                        <td>
                                            @if ($person)
                                                {{ $person->fname ?? '' }} {{ $person->lname ?? '' }}
                                                ({{ $person->username ?? 'N/A' }})
                                    @else
                                                N/A
                                            @endif
                                        </td>
                                        <td>
                                            @if ($signature && $history->status == 1)
                                                <img src="data:image/png;base64,{{ $signature }}" alt="Signature"
                                                    height="40"
                                                    style="border: 1px solid #ddd; padding: 5px; background: white; border-radius: 5px;">
                                            @else
                                                <span class="text-muted">—</span>
                                            @endif
                                        </td>
                                        <td>{{ $remark ?: '—' }}</td>
                                    </tr>
                                    @endforeach
                    @endforeach
                        </tbody>
                    </table>
            </div>

                {{-- Action Buttons --}}
            @php
                $currentPendingHistory = $changeRequest->workflow->histories
                    ->where('attended_by', Auth::user()->id)
                    ->where('status', 0)
                    ->first();
            @endphp

                @if ($currentPendingHistory && !$isCompleted)
                    <div class="button-group">
                        <button class="btn btn-primary" id="approveBtn">
                    <i class="fas fa-check"></i> Approve
                </button>
                <button class="btn btn-danger" id="rejectBtn">
                    <i class="fas fa-times"></i> Reject
                </button>
            </div>
            @endif
        </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        @if ($currentPendingHistory && !$isCompleted)
        document.getElementById('approveBtn').addEventListener('click', function() {
                Swal.fire({
                    title: 'Approve Change Request',
                    html: `
                        <div class="mb-3">
                            <label for="swal-approval-comments" class="form-label text-start d-block mb-2">
                                <strong>Comments (Optional)</strong>
                            </label>
                            <textarea id="swal-approval-comments" class="form-control" rows="4"
                                placeholder="Add any comments or notes..."
                                style="min-height: 100px; resize: vertical;"></textarea>
                        </div>
                    `,
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonText: '<i class="fas fa-check-circle me-1"></i> Approve',
                    cancelButtonText: 'Cancel',
                    confirmButtonColor: '#28a745',
                    preConfirm: () => {
                        return document.getElementById('swal-approval-comments').value.trim();
                    }
                }).then((result) => {
                    if (result.isConfirmed) {
                        Swal.fire({
                            title: 'Approving...',
                            html: '<div class="text-center"><div class="spinner-border text-success" role="status"></div><p class="mt-3">Please wait...</p></div>',
                            allowOutsideClick: false,
                            showConfirmButton: false,
                            didOpen: () => { Swal.showLoading(); }
                        });

                        const formData = new FormData();
                        formData.append('_token', document.querySelector('meta[name="csrf-token"]').content);
                        if (result.value) {
                            formData.append('comments', result.value);
                        }

                        fetch('{{ route('change_request.approve', $changeRequest->id) }}', {
                            method: 'POST',
                            body: formData,
                            headers: {
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                                'Accept': 'application/json'
                            }
                        })
                        .then(async response => {
                            const contentType = response.headers.get('content-type');
                            if (!contentType || !contentType.includes('application/json')) {
                                const text = await response.text();
                                throw new Error('Server returned non-JSON response. Please check the server logs.');
                            }
                            if (!response.ok) {
                                const err = await response.json();
                                throw err;
                            }
                            return response.json();
                        })
                        .then(data => {
                            const icon = data.icon || 'success';
                            const title = data.title || 'Success';
                            const text = data.text || data.message || 'Successfully approved.';
                            Swal.fire({
                                icon: icon,
                                title: title,
                                text: text,
                                timer: 2000,
                                showConfirmButton: false
                            }).then(() => {
                                // Redirect to the approval index page
                                window.location.href = '{{ route('requestapprove.index') }}';
                            });
                        })
                        .catch(error => {
                            const errorMessage = error.text || error.message || 'Unknown error occurred';
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: 'Failed to approve request: ' + errorMessage,
                                confirmButtonText: 'OK'
                            });
                        });
                    }
                });
        });

            document.getElementById('rejectBtn').addEventListener('click', function() {
                Swal.fire({
                    title: 'Reject Change Request',
                    html: `
                        <div class="mb-3">
                            <label for="swal-rejection-reason" class="form-label text-start d-block mb-2">
                                <strong>Reason for Rejection <span class="text-danger">*</span></strong>
                            </label>
                            <textarea id="swal-rejection-reason" class="form-control" rows="4" required
                                placeholder="Please provide a reason for rejection..."
                                style="min-height: 100px; resize: vertical;"></textarea>
                        </div>
                    `,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: '<i class="fas fa-times-circle me-1"></i> Reject',
                    cancelButtonText: 'Cancel',
                    confirmButtonColor: '#dc3545',
                    preConfirm: () => {
                        const reason = document.getElementById('swal-rejection-reason').value.trim();
                        if (!reason) {
                            Swal.showValidationMessage('Please provide a reason for rejection.');
                            return false;
                        }
                        return reason;
                    }
                }).then((result) => {
                    if (result.isConfirmed && result.value) {
                        Swal.fire({
                            title: 'Rejecting...',
                            html: '<div class="text-center"><div class="spinner-border text-danger" role="status"></div><p class="mt-3">Please wait...</p></div>',
                            allowOutsideClick: false,
                            showConfirmButton: false,
                            didOpen: () => { Swal.showLoading(); }
                        });

                        const formData = new FormData();
                        formData.append('_token', document.querySelector('meta[name="csrf-token"]').content);
                        formData.append('rejection_reason', result.value);

                        fetch('{{ route('change_request.reject', $changeRequest->id) }}', {
                            method: 'POST',
                            body: formData,
                            headers: {
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                                'Accept': 'application/json'
                            }
                        })
                        .then(async response => {
                            const contentType = response.headers.get('content-type');
                            if (!contentType || !contentType.includes('application/json')) {
                                const text = await response.text();
                                throw new Error('Server returned non-JSON response. Please check the server logs.');
                            }
                            if (!response.ok) {
                                const err = await response.json();
                                throw err;
                            }
                            return response.json();
                        })
                        .then(data => {
                            const icon = data.icon || 'success';
                            const title = data.title || 'Success';
                            const text = data.text || data.message || 'Successfully rejected.';
                            Swal.fire({
                                icon: icon,
                                title: title,
                                text: text,
                                timer: 2000,
                                showConfirmButton: false
                            }).then(() => {
                                // Redirect to the approval index page
                                window.location.href = '{{ route('requestapprove.index') }}';
                            });
                        })
                        .catch(error => {
                            const errorMessage = error.text || error.message || 'Unknown error occurred';
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: 'Failed to reject request: ' + errorMessage,
                                confirmButtonText: 'OK'
                            });
                        });
                    }
                });
            });
        @endif
    </script>
@endsection
