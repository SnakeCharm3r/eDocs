@extends('layouts.template')

@section('breadcrumb')
    @include('sweetalert::alert')
@endsection

@section('content')
<div class="page-wrapper">
    <div class="content container-fluid">

        {{-- Page Header --}}
        <div class="page-header">
            <div class="row align-items-center">
                <div class="col-auto">
                    <a href="{{ route('vendorContract.index') }}" class="btn btn-secondary">
                        <i class="fas fa-arrow-left me-1"></i> Back to List
                    </a>
                </div>
            </div>
        </div>

        {{-- Contract Form --}}
        <div class="row">
            <div class="col-lg-12">
                <div class="card">
                    <div class="card-header bg-light">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-file-contract me-2"></i>Contract Information
                        </h5>
                    </div>
                    <div class="card-body">

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

                        <form action="{{ route('vendorContract.ExistingContract') }}" method="POST" enctype="multipart/form-data">
                            @csrf

                            {{-- Contract Details --}}
                            <div class="card mb-4 border shadow-sm">
                                <div class="card-header bg-white">
                                    <h6 class="mb-0 text-success"><i class="fas fa-info-circle me-2"></i>Contract Details</h6>
                                </div>
                                <div class="card-body row g-3">
                                    <div class="col-md-6">
                                        <label for="title" class="form-label">Contract Document Name <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control @error('title') is-invalid @enderror" id="title" name="title" value="{{ old('title') }}" required>
                                        @error('title')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-md-6">
                                        <label for="contract_type" class="form-label">Contract Document Type <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control @error('contract_type') is-invalid @enderror" id="contract_type" name="contract_type" value="{{ old('contract_type') }}" required>
                                        @error('contract_type')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            {{-- Associated Departments --}}
                            <div class="card mb-4 border shadow-sm">
                                <div class="card-header bg-white">
                                    <h6 class="mb-0 text-success"><i class="fas fa-building me-2"></i>Associated Departments</h6>
                                </div>
                                <div class="card-body row g-3">
                                    <div class="col-md-6">
                                        <label for="vendor_id" class="form-label">Vendor <span class="text-danger">*</span></label>
                                        <select name="vendor_id" id="vendor_id" class="form-select @error('vendor_id') is-invalid @enderror" required>
                                            <option value="">Select Vendor</option>
                                            @foreach($vendors as $vendor)
                                                <option value="{{ $vendor->id }}" {{ old('vendor_id') == $vendor->id ? 'selected' : '' }}>
                                                    {{ $vendor->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                        @error('vendor_id')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-md-6">
                                        <label for="division_id" class="form-label">Division <span class="text-danger">*</span></label>
                                        <select name="division_id" id="division_id" class="form-select @error('division_id') is-invalid @enderror" required>
                                            <option value="">Select Division</option>
                                            @foreach($divisions as $division)
                                                <option value="{{ $division->id }}" {{ old('division_id') == $division->id ? 'selected' : '' }}>
                                                    {{ $division->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                        @error('division_id')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-md-6">
                                        <label for="department_id" class="form-label">Department <span class="text-danger">*</span></label>
                                        <select name="department_id" id="department_id" class="form-select @error('department_id') is-invalid @enderror" required>
                                            <option value="">Select Department</option>
                                            @foreach($departments as $department)
                                                <option value="{{ $department->id }}" {{ old('department_id') == $department->id ? 'selected' : '' }}>
                                                    {{ $department->dept_name }}
                                                </option>
                                            @endforeach
                                        </select>
                                        @error('department_id')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-md-6">
                                        <label for="renewal_status" class="form-label">Renewable Status <span class="text-danger">*</span></label>
                                        <select name="renewal_status" id="renewal_status" class="form-select @error('renewal_status') is-invalid @enderror" required>
                                            <option value="">Select Status</option>
                                            <option value="renewed" {{ old('renewal_status')=='renewed' ? 'selected' : '' }}>Renewed</option>
                                            <option value="not_renewed" {{ old('renewal_status')=='not_renewed' ? 'selected' : '' }}>Not Renewed</option>
                                            <option value="pending" {{ old('renewal_status')=='pending' ? 'selected' : '' }}>Pending</option>
                                        </select>
                                        @error('renewal_status')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            {{-- Cost & Currency --}}
                            <div class="card mb-4 border shadow-sm">
                                <div class="card-header bg-white">
                                    <h6 class="mb-0 text-success"><i class="fas fa-dollar-sign me-2"></i>Cost & Currency</h6>
                                </div>
                                <div class="card-body row g-3">
                                    <div class="col-md-6">
                                        <label for="cost" class="form-label">Contract Cost <span class="text-danger">*</span></label>
                                        <input type="number" class="form-control @error('cost') is-invalid @enderror" id="cost" name="cost" value="{{ old('cost') }}" step="0.01" required>
                                        @error('cost')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-md-6">
                                        <label for="currency" class="form-label">Currency <span class="text-danger">*</span></label>
                                        <select name="currency" id="currency" class="form-select @error('currency') is-invalid @enderror" required>
                                            <option value="">Select Currency</option>
                                            <option value="USD" {{ old('currency')=='USD' ? 'selected' : '' }}>USD – US Dollar</option>
                                            <option value="TZS" {{ old('currency')=='TZS' ? 'selected' : '' }}>TZS – Tanzanian Shilling</option>
                                            <option value="EUR" {{ old('currency')=='EUR' ? 'selected' : '' }}>EUR – Euro</option>
                                            <option value="CAD" {{ old('currency')=='CAD' ? 'selected' : '' }}>CAD – Canadian Dollar</option>
                                            <option value="GBP" {{ old('currency')=='GBP' ? 'selected' : '' }}>GBP – British Pound</option>
                                            <option value="AUD" {{ old('currency')=='AUD' ? 'selected' : '' }}>AUD – Australian Dollar</option>
                                            <option value="RUP" {{ old('currency')=='RUP' ? 'selected' : '' }}>RUP – Indian Rupee</option>
                                            <option value="UGX" {{ old('currency')=='UGX' ? 'selected' : '' }}>UGX – Uganda Shilling</option>
                                            <option value="KES" {{ old('currency')=='KES' ? 'selected' : '' }}>KES – Kenyan Shilling</option>
                                        </select>
                                        @error('currency')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-md-6">
                                        <label for="duration_months" class="form-label">Duration (Months) <span class="text-danger">*</span></label>
                                        <input type="number" class="form-control @error('duration_months') is-invalid @enderror" id="duration_months" name="duration_months" value="{{ old('duration_months') }}" required>
                                        @error('duration_months')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-md-6">
                                        <label for="notice_period_months" class="form-label">Notice Period (Months) <span class="text-danger">*</span></label>
                                        <input type="number" class="form-control @error('notice_period_months') is-invalid @enderror" id="notice_period_months" name="notice_period_months" value="{{ old('notice_period_months') }}" required>
                                        @error('notice_period_months')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            {{-- Dates --}}
                            <div class="card mb-4 border shadow-sm">
                                <div class="card-header bg-white">
                                    <h6 class="mb-0 text-success"><i class="fas fa-calendar-alt me-2"></i>Dates</h6>
                                </div>
                                <div class="card-body row g-3">
                                    <div class="col-md-4">
                                        <label for="creation_date" class="form-label">Start Date <span class="text-danger">*</span></label>
                                        <input type="date" class="form-control @error('creation_date') is-invalid @enderror" id="creation_date" name="creation_date" value="{{ old('creation_date') }}" required>
                                        @error('creation_date')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-md-4">
                                        <label for="end_date" class="form-label">End Date <span class="text-danger">*</span></label>
                                        <input type="date" class="form-control @error('end_date') is-invalid @enderror" id="end_date" name="end_date" value="{{ old('end_date') }}" required>
                                        @error('end_date')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-md-4">
                                        <label for="status" class="form-label">Current Contract Status <span class="text-danger">*</span></label>
                                        <select name="status" id="status" class="form-select @error('status') is-invalid @enderror" required>
                                            <option value="">Select Status</option>
                                            <option value="draft" {{ old('status') == 'draft' ? 'selected' : '' }}>Draft</option>
                                            <option value="active" {{ old('status') == 'active' ? 'selected' : '' }}>Active</option>
                                            <option value="soon_to_expire" {{ old('status') == 'soon_to_expire' ? 'selected' : '' }}>Soon to Expire</option>
                                            <option value="expired" {{ old('status') == 'expired' ? 'selected' : '' }}>Expired</option>
                                            <option value="terminated" {{ old('status') == 'terminated' ? 'selected' : '' }}>Terminated</option>
                                        </select>
                                        @error('status')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            {{-- Impact & Likelihood --}}
                            <div class="card mb-4 border shadow-sm">
                                <div class="card-header bg-white">
                                    <h6 class="mb-0 text-success"><i class="fas fa-exclamation-triangle me-2"></i>Impact & Likelihood of the Current Contract</h6>
                                </div>
                                <div class="card-body row g-3">
                                    <div class="col-md-6">
                                        <label for="impact_if_not_requested" class="form-label">The Impact of the contract if not requested</label>
                                        <select name="impact_if_not_requested" id="impact_if_not_requested" class="form-select @error('impact_if_not_requested') is-invalid @enderror">
                                            <option value="">Select Impact</option>
                                            <option value="Low" {{ old('impact_if_not_requested') == 'Low' ? 'selected' : '' }}>Low</option>
                                            <option value="Medium" {{ old('impact_if_not_requested') == 'Medium' ? 'selected' : '' }}>Medium</option>
                                            <option value="High" {{ old('impact_if_not_requested') == 'High' ? 'selected' : '' }}>High</option>
                                        </select>
                                        @error('impact_if_not_requested')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-md-6">
                                        <label for="likelihood_rating" class="form-label">Likelihood of the Contracts Renewal</label>
                                        <select name="likelihood_rating" id="likelihood_rating" class="form-select @error('likelihood_rating') is-invalid @enderror">
                                            <option value="">Select Likelihood</option>
                                            <option value="Low" {{ old('likelihood_rating') == 'Low' ? 'selected' : '' }}>Low</option>
                                            <option value="Medium" {{ old('likelihood_rating') == 'Medium' ? 'selected' : '' }}>Medium</option>
                                            <option value="High" {{ old('likelihood_rating') == 'High' ? 'selected' : '' }}>High</option>
                                        </select>
                                        @error('likelihood_rating')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            {{-- Upload File --}}
                            <div class="card mb-4 border shadow-sm">
                                <div class="card-header bg-white">
                                    <h6 class="mb-0 text-success"><i class="fas fa-upload me-2"></i>Upload Contract File</h6>
                                </div>
                                <div class="card-body">
                                    <input type="file" class="form-control @error('file_path') is-invalid @enderror" name="file_path" id="contractFile" required>
                                    <small class="text-muted">Upload one contract file (PDF, Word, Excel, image). Maximum file size: 50MB.</small>
                                    @error('file_path')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <div id="filePreview" class="mt-3"></div>
                                </div>
                            </div>

                            {{-- Submit --}}
                            <div class="d-flex justify-content-between">
                                <a href="{{ route('vendorContract.index') }}" class="btn btn-outline-secondary">
                                    <i class="fas fa-times me-1"></i> Cancel
                                </a>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-1"></i> Add Contract
                                </button>
                            </div>

                        </form>

                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
.card { border: none; box-shadow: 0 0.5rem 1rem rgba(0,0,0,0.15); }
.card-header { border-bottom: 1px solid #dee2e6; background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%); }
.form-label { font-weight: 600; color: #495057; }
.btn { border-radius: 0.375rem; font-weight: 500; }
.form-control:focus, .form-select:focus { border-color: #86b7fe; box-shadow: 0 0 0 0.25rem rgba(13,110,253,0.25); }
.alert { border-radius: 0.5rem; }

.file-preview-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.5rem;
    border: 1px solid #dee2e6;
    border-radius: 0.375rem;
    margin-bottom: 0.5rem;
    background-color: #f8f9fa;
}
.file-preview-item i { margin-right: 0.5rem; font-size: 1.2rem; }
</style>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    // Status update (UX only - no validation)
    const startInput  = document.getElementById('creation_date');
    const endInput    = document.getElementById('end_date');
    const noticeInput = document.getElementById('notice_period_months');
    const statusSel   = document.getElementById('status');

    function updateStatus() {
        if (!startInput.value || !endInput.value || !noticeInput.value) return;

        const start  = new Date(startInput.value);
        const end    = new Date(endInput.value);
        const today  = new Date();
        today.setHours(0,0,0,0);

        const noticeMonths = parseInt(noticeInput.value, 10) || 0;
        const noticeStart = new Date(end);
        noticeStart.setMonth(end.getMonth() - noticeMonths);

        if (end < start) {
            statusSel.value = '';
            alert('End Date cannot be earlier than Start Date.');
            endInput.value = '';
            return;
        }

        let newStatus = '';
        if (today < start) newStatus = 'draft';
        else if (today >= start && today < noticeStart) newStatus = 'active';
        else if (today >= noticeStart && today <= end) newStatus = 'soon_to_expire';
        else if (today > end) newStatus = 'expired';

        statusSel.value = newStatus;
    }

    startInput.addEventListener('change', updateStatus);
    endInput.addEventListener('change', updateStatus);
    noticeInput.addEventListener('change', updateStatus);

    // File preview (UX only - no validation)
    const fileInput = document.getElementById('contractFile');
    const previewDiv = document.getElementById('filePreview');

    fileInput.addEventListener('change', function() {
        previewDiv.innerHTML = '';
        const file = this.files[0];
        if (!file) return;

        const fileName = file.name;
        const fileSizeMB = (file.size / (1024 * 1024)).toFixed(2);
        const extension = fileName.split('.').pop().toLowerCase();

        let iconClass = 'fa-file';
        if (extension === 'pdf') iconClass = 'fa-file-pdf text-danger';
        else if (['doc', 'docx'].includes(extension)) iconClass = 'fa-file-word text-primary';
        else if (['xls', 'xlsx'].includes(extension)) iconClass = 'fa-file-excel text-success';
        else if (['png','jpg','jpeg','gif'].includes(extension)) iconClass = 'fa-file-image text-warning';

        const previewItem = document.createElement('div');
        previewItem.className = 'file-preview-item';
        previewItem.innerHTML = `<div><i class="fas ${iconClass}"></i>${fileName} (${fileSizeMB} MB)</div>`;

        // Remove button
        const removeBtn = document.createElement('button');
        removeBtn.type = 'button';
        removeBtn.className = 'btn btn-sm btn-outline-danger';
        removeBtn.innerHTML = '<i class="fas fa-times"></i>';
        removeBtn.addEventListener('click', () => {
            fileInput.value = '';
            previewDiv.innerHTML = '';
        });

        previewItem.appendChild(removeBtn);
        previewDiv.appendChild(previewItem);
    });
});
</script>
@endpush