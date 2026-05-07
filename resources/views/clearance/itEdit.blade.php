@extends('layouts.template')
@include('includes.loader')

@section('breadcrumb')
    <!-- Breadcrumb content would go here -->
@endsection

@section('content')
    @include('sweetalert::alert')

    <div class="page-wrapper">
        <div class="content container">
            <div class="row">
                <div class="col-md-12">

                    <!-- Main Card -->
                    <div class="card">
                        <div class="card-body">

                            <!-- Header Section with Logo and Title -->
                            <div class="d-flex align-items-center justify-content-between mb-4" 
                                style="border:2px solid black; padding:15px; background-color:#f8f9fa; border-radius:5px;">
                                <div class="d-flex align-items-center">
                                    <img src="{{ asset('assets/img/ccbrt.jpg') }}" alt="Logo" 
                                        style="width:100px; height:100px; object-fit:contain;">
                                </div>
                                <div class="text-center" style="flex:1;">
                                    <h3 class="fw-bold mb-0">Employee Exit Form</h3>
                                </div>
                                <div>
                                    <span class="badge rounded-pill bg-success">Version 1 (V1)</span>
                                </div>
                            </div>

                            <!-- Employee Information Card -->
                            <div class="card mb-4 border-0 shadow-sm">
                                <div class="card-header" style="background-color: #61ce70;">
                                    <h5 class="card-title mb-0 d-flex align-items-center text-white">
                                        <i class="fas fa-user-circle me-2"></i>
                                        Employee Information
                                    </h5>
                                </div>
                                <div class="card-body p-0">
                                    <div class="table-responsive">
                                        <table class="table table-hover mb-0">
                                            <tbody>
                                                <tr class="align-middle">
                                                    <td class="fw-semibold text-end" style="width:35%; border-right:2px solid #e9ecef;">
                                                        <div class="d-flex justify-content-between align-items-center">
                                                            <span>Requesters Name</span>
                                                            <i class="fas fa-user ms-2"></i>
                                                        </div>
                                                    </td>
                                                    <td class="ps-4" style="width:65%;">
                                                        <span class="text-dark fs-6">
                                                            {{ $clearance->fname.' '.$clearance->mname.' '.$clearance->lname }}
                                                        </span>
                                                    </td>
                                                </tr>
                                                <tr class="align-middle">
                                                    <td class="fw-semibold text-end" style="border-right:2px solid #e9ecef;">
                                                        <div class="d-flex justify-content-between align-items-center">
                                                            <span>Job Title</span>
                                                            <i class="fas fa-briefcase ms-2"></i>
                                                        </div>
                                                    </td>
                                                    <td class="ps-4">
                                                        <span class="text-dark">{{ $clearance->job_title }}</span>
                                                        <span class="badge bg-info ms-2">{{ $clearance->employment_type }}</span>
                                                    </td>
                                                </tr>
                                                <tr class="align-middle">
                                                    <td class="fw-semibold text-end" style="border-right:2px solid #e9ecef;">
                                                        <div class="d-flex justify-content-between align-items-center">
                                                            <span>Department</span>
                                                            <i class="fas fa-building ms-2"></i>
                                                        </div>
                                                    </td>
                                                    <td class="ps-4">
                                                        <span class="text-dark">{{ $clearance->department }}</span>
                                                        <small class="text-muted d-block mt-1">Team</small>
                                                    </td>
                                                </tr>
                                                <tr class="align-middle">
                                                    <td class="fw-semibold text-end" style="border-right:2px solid #e9ecef;">
                                                        <div class="d-flex justify-content-between align-items-center">
                                                            <span>Contact Information</span>
                                                            <i class="fas fa-envelope ms-2"></i>
                                                        </div>
                                                    </td>
                                                    <td class="ps-4">
                                                        <div class="d-flex flex-column">
                                                            <span class="text-dark">{{ $clearance->email }}</span>
                                                        </div>
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>

                            <!-- Line Manager Approval Card -->
                            <div class="card mb-4 border-0 shadow-sm">
                                <div class="card-header" style="background-color: #61ce70;">
                                    <h5 class="card-title mb-0 d-flex align-items-center text-white">
                                        <i class="fas fa-clipboard-check me-2"></i>
                                        Line Manager Approval Process
                                    </h5>
                                </div>
                                <div class="card-body p-0">
                                    <form id="lineManagerForm" method="POST" action="{{ route('exit_forms.It') }}">
                                        @csrf
                                        <input type="hidden" name="employee_id" value="{{ $clearance->userId }}">

                                        <div class="table-responsive">
                                            <table class="table table-hover mb-0">
                                                <tbody>
                                                    @php
                                                        $items = [
                                                            ['label' => 'CCBRT ID Card', 'icon' => 'fa-id-card', 'name' => 'ccbrt_id_card'],
                                                            ['label' => 'CCBRT Name Tag', 'icon' => 'fa-tag', 'name' => 'ccbrt_name_tag'],
                                                            ['label' => 'Changing Room Keys', 'icon' => 'fa-key', 'name' => 'changing_room_keys'],
                                                            ['label' => 'CCBRT Uniforms', 'icon' => 'fa-tshirt', 'name' => 'ccbrt_uniforms'],
                                                            ['label' => 'Office Car Keys', 'icon' => 'fa-car', 'name' => 'office_car_keys'],
                                                        ];
                                                        $statuses = [
                                                            ['value'=>'returned','label'=>'Returned'],
                                                            ['value'=>'not_returned','label'=>'Not Returned'],
                                                            ['value'=>'not_applicable','label'=>'Not Applicable'],
                                                            ['value'=>'lost','label'=>'Lost/Damaged'],
                                                        ];
                                                    @endphp

                                                    @foreach($items as $item)
                                                    <tr class="align-middle">
                                                        <td class="fw-semibold text-end" style="width:35%; border-right:2px solid #e9ecef;">
                                                            <div class="d-flex justify-content-between align-items-center">
                                                                <span>Has the user returned {{ $item['label'] }}?</span>
                                                                <i class="fas {{ $item['icon'] }} ms-2"></i>
                                                            </div>
                                                        </td>
                                                        <td class="ps-4" style="width:65%;">
                                                            <div class="d-flex flex-wrap gap-2">
                                                                @foreach($statuses as $status)
                                                                    <div class="form-check">
                                                                        <input class="form-check-input" type="radio" 
                                                                            name="{{ $item['name'] }}" 
                                                                            id="{{ $item['name'].'_'.$status['value'] }}" 
                                                                            value="{{ $status['value'] }}">
                                                                        <label class="form-check-label" 
                                                                            for="{{ $item['name'].'_'.$status['value'] }}">
                                                                            {{ $status['label'] }}
                                                                        </label>
                                                                    </div>
                                                                @endforeach
                                                            </div>
                                                        </td>
                                                    </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>

                                        <!-- Approver Details Card -->
                                        <div class="mt-4 card mb-4 border-0 shadow-sm">
                                            <div class="card-header" style="background-color: #61ce70;">
                                                <h5 class="card-title mb-0 d-flex align-items-center text-white">
                                                    <i class="fas fa-user-check me-2"></i>
                                                    Approver Details
                                                </h5>
                                            </div>
                                            <div class="card-body p-0">
                                                <div class="table-responsive">
                                                    <table class="table mb-0">
                                                        <tbody>
                                                            <tr style="background-color:#f8f9fa;">
                                                                <td class="fw-semibold text-end" style="width:50%; border-right:2px solid #dee2e6;">
                                                                    <div class="d-flex justify-content-between align-items-center">
                                                                        <span>Approver's Name</span>
                                                                        <i class="fas fa-user-tie ms-2" style="color:#61ce70;"></i>
                                                                    </div>
                                                                </td>
                                                                <td class="ps-4" style="width:50%;">
                                                                    <span class="text-muted fst-italic">Pending approval...</span>
                                                                </td>
                                                            </tr>
                                                            <tr>
                                                                <td class="fw-semibold text-end" style="border-right:2px solid #dee2e6;">
                                                                    <div class="d-flex justify-content-between align-items-center">
                                                                        <span>Signature</span>
                                                                        <i class="fas fa-signature ms-2" style="color:#61ce70;"></i>
                                                                    </div>
                                                                </td>
                                                                <td class="ps-4">
                                                                    <div class="signature-placeholder bg-light rounded text-center py-3">
                                                                        <span class="text-muted fst-italic">Signature will appear here</span>
                                                                    </div>
                                                                </td>
                                                            </tr>
                                                            <tr>
                                                                <td class="fw-semibold text-end" style="border-right:2px solid #dee2e6;">
                                                                    <div class="d-flex justify-content-between align-items-center">
                                                                        <span>Date of Approval</span>
                                                                        <i class="fas fa-calendar-check ms-2" style="color:#61ce70;"></i>
                                                                    </div>
                                                                </td>
                                                                <td class="ps-4">
                                                                    <span class="text-muted fst-italic">Pending approval...</span>
                                                                </td>
                                                            </tr>
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Justification Section -->
                                        <div class="p-4 border-top">
                                            <div class="row align-items-center mb-3">
                                                <div class="col-md-12">
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="checkbox" id="requireJustification" name="require_justification">
                                                        <label class="form-check-label fw-semibold" for="requireJustification">
                                                            <i class="fas fa-exclamation-triangle text-warning me-1"></i>
                                                            Justification Required (Check if items were not returned)
                                                        </label>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="row justify-content-end">
                                                <div class="col-md-8" id="justificationField" style="display:none;">
                                                    <div class="form-group">
                                                        <label for="justification" class="form-label">Justification Details</label>
                                                        <textarea class="form-control" id="justification" name="justification" rows="3"
                                                            placeholder="Please provide justification for items that were not returned..." disabled></textarea>
                                                        <small class="text-muted">Explain why items were not returned and any follow-up actions.</small>
                                                    </div>
                                                </div>
                                                <div class="col-md-4 d-flex align-items-end">
                                                    <button type="submit" class="btn btn-success w-100 py-2">
                                                        <i class="fas fa-check-circle me-2"></i>Approve
                                                    </button>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="card-footer bg-light py-3 border-top">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <div>
                                                    <small class="text-muted">Form will be locked after approval</small>
                                                </div>
                                                <div>
                                                    <button type="reset" class="btn btn-outline-secondary">
                                                        <i class="fas fa-redo me-1"></i>Reset Form
                                                    </button>
                                                </div>
                                            </div>
                                        </div>

                                    </form>
                                </div>
                            </div>

                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>
@endsection

@push('styles')
<style>
    .signature-placeholder {
        min-height: 60px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-style: italic;
        color: #6c757d;
    }

    .form-check {
        min-width: 130px; /* makes radio buttons more uniform */
    }
</style>
@endpush

@push('scripts')
<script>
    document.getElementById('requireJustification').addEventListener('change', function() {
        var field = document.getElementById('justificationField');
        var textarea = field.querySelector('textarea');

        if (this.checked) {
            field.style.display = 'block';
            textarea.disabled = false;
            textarea.focus();
        } else {
            field.style.display = 'none';
            textarea.disabled = true;
            textarea.value = '';
        }
    });
</script>
@endpush
