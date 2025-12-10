@extends('layouts.template')

@section('breadcrumb')
    @include('sweetalert::alert')
@endsection

@section('content')
    <div class="page-wrapper">
        <div class="content container-fluid">
            {{-- Page Header --}}
            <div class="page-header mb-4">
                <div class="row align-items-center">
                    <div class="col">
                        <h3 class="page-title">Recruitment Requisition Form</h3>
                    </div>
                    <div class="col-auto">
                        <a href="{{ route('requisitions.index') }}" class="btn btn-secondary">
                            <i class="fas fa-arrow-left me-1"></i> Back to List
                        </a>
                    </div>
                </div>
            </div>

            {{-- Error Messages --}}
            @if ($errors->any())
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <strong>Error!</strong> Please fix the following issues:
                    <ul class="mb-0 mt-2">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            @if (session('error'))
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <strong>Error!</strong> {{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            <form method="POST" action="{{ route('requisitions.store') }}" enctype="multipart/form-data" id="requisitionForm">
                @csrf

                {{-- Section 1: Position Details --}}
                <div class="card shadow-sm mb-4" style="border-left: 4px solid #007A33;">
                    <div class="card-header bg-light border-bottom">
                        <h5 class="card-title mb-0" style="color: #333;">
                            <i class="fas fa-briefcase me-2" style="color: #007A33;"></i>1. Position Details
                        </h5>
                        <small class="text-muted">To be filled by respective Head of Department</small>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            {{-- Background Selection (Common) --}}
                            @include('requisitions.partials.background-selection')

                            {{-- Position Details by User Type --}}
                            @include('requisitions.partials.position-details-line-manager')
                            @include('requisitions.partials.position-details-hec-member')
                            @include('requisitions.partials.position-details-regular')

                            {{-- Common Fields (Responsibility Centre, Reporting Line, Contract Type, etc.) --}}
                            @include('requisitions.partials.common-fields')
                        </div>
                    </div>
                </div>

                {{-- Section 2: Conditions --}}
                @include('requisitions.partials.conditions-section')

                {{-- Section 3: Reasoning --}}
                @include('requisitions.partials.reasoning-section')

                {{-- Form Actions --}}
                @include('requisitions.partials.form-actions')
            </form>
        </div>
    </div>
@endsection

@push('styles')
    <style>
        .form-control:focus,
        .form-select:focus {
            border-color: #007A33;
            box-shadow: 0 0 0 0.2rem rgba(0, 122, 51, 0.15);
        }

        .form-label {
            color: #495057;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }

        .card {
            border-radius: 8px;
        }

        .form-check.p-3.border.rounded {
            border-radius: 8px;
            transition: all 0.2s ease;
        }

        .form-check.p-3.border.rounded:hover {
            background-color: #f8f9fa;
            transform: translateY(-2px);
        }

        fieldset {
            border: none;
            padding: 0;
            margin: 0;
        }

        legend {
            font-size: 1rem;
            font-weight: 600;
            color: #495057;
            margin-bottom: 0.75rem;
            padding: 0;
            width: auto;
        }
    </style>
@endpush

@push('scripts')
    {{-- Set global variables for JavaScript --}}
    <script>
        window.isHecMember = @json($isHecMember ?? false);
        window.isLineManager = @json($isLineManager ?? false);
        
        // Route URLs for JavaScript
        window.usersByJobTitleRoute = @json(route('requisitions.users-by-job-title'));
        window.lineManagersByDepartmentRoute = @json(route('requisitions.line-managers-by-department'));
        window.jobTitlesByDepartmentRoute = @json(route('requisitions.job-titles-by-department'));
        window.staffByDepartmentRoute = @json(route('requisitions.staff-by-department'));
        window.allStaffInDepartmentRoute = @json(route('requisitions.all-staff-in-department'));
        
        // Old values for preserving selections
        window.oldReplacementEmployeeId = @json(old('replacement_employee_id'));
        window.oldContractEmployeeId = @json(old('contract_employee_id'));
    </script>

    {{-- Common JavaScript (File validation, form validation) --}}
    <script src="{{ asset('js/requisitions/create-common.js') }}"></script>

    {{-- User-specific JavaScript --}}
    @if ($isLineManager ?? false)
        <script src="{{ asset('js/requisitions/create-line-manager.js') }}"></script>
    @elseif ($isHecMember ?? false)
        <script src="{{ asset('js/requisitions/create-hec-member.js') }}"></script>
    @else
        <script src="{{ asset('js/requisitions/create-regular.js') }}"></script>
    @endif

    {{-- Initialize common functions --}}
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            try {
                initFileValidation();
                initFormValidation();
            } catch (error) {
                console.error('Error initializing form:', error);
                // Ensure submit button is enabled even if scripts fail
                const submitBtn = document.getElementById('submitRequisitionBtn');
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.type = 'submit';
                }
            }
        });
    </script>
@endpush

