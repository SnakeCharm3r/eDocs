@extends('layouts.template')

@php
    use Illuminate\Support\Str;
@endphp

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
                        <h3 class="page-title">
                            <i class="fas fa-building me-2"></i>Department Vendors
                        </h3>

                    </div>
                    <div class="col-auto">
                        <a href="{{ route('procurements.vendors.index') }}" class="btn btn-secondary">
                            <i class="fas fa-arrow-left me-1"></i> All Vendors
                        </a>
                    </div>
                </div>
            </div>

            {{-- Filter Section --}}
            <div class="card shadow-sm mb-4 border-0">
                <div class="card-header bg-white border-bottom">
                    <h5 class="mb-0">
                        <i class="fas fa-filter me-2 text-primary"></i>Filters
                    </h5>
                </div>
                <div class="card-body">
                    <form method="GET" action="{{ route('procurements.vendors.hec-department-vendors') }}" id="filterForm">
                        <div class="row g-3">
                            {{-- Department Filter --}}
                            <div class="col-md-3">
                                <label for="department" class="form-label fw-semibold">Department</label>
                                <select name="department" id="department" class="form-select">
                                    <option value="">All Departments</option>
                                    @foreach($allDepartments ?? $departments as $dept)
                                        <option value="{{ $dept->id }}" {{ request('department') == $dept->id ? 'selected' : '' }}>
                                            {{ $dept->dept_name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            {{-- Vendor Type Filter --}}
                            <div class="col-md-2">
                                <label for="vendor_type" class="form-label fw-semibold">Vendor Type</label>
                                <select name="vendor_type" id="vendor_type" class="form-select">
                                    <option value="">All Types</option>
                                    @foreach($vendorTypes ?? [] as $type)
                                        <option value="{{ $type }}" {{ request('vendor_type') == $type ? 'selected' : '' }}>
                                            {{ ucfirst($type) }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            {{-- Vendor Name Filter --}}
                            <div class="col-md-2">
                                <label for="vendor_name" class="form-label fw-semibold">Vendor Name</label>
                                <input type="text" name="vendor_name" id="vendor_name" class="form-control" 
                                       placeholder="Search vendor..." value="{{ request('vendor_name') }}">
                            </div>

                            {{-- Industry Filter --}}
                            <div class="col-md-2">
                                <label for="industry" class="form-label fw-semibold">Industry</label>
                                <select name="industry" id="industry" class="form-select">
                                    <option value="">All Industries</option>
                                    @foreach($industries ?? [] as $industry)
                                        <option value="{{ $industry }}" {{ request('industry') == $industry ? 'selected' : '' }}>
                                            {{ ucfirst(str_replace('_', ' ', $industry)) }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            {{-- Status Filter --}}
                            <div class="col-md-2">
                                <label for="status" class="form-label fw-semibold">Status</label>
                                <select name="status" id="status" class="form-select">
                                    <option value="">All Status</option>
                                    <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Active</option>
                                    <option value="inactive" {{ request('status') == 'inactive' ? 'selected' : '' }}>Inactive</option>
                                </select>
                            </div>

                            {{-- Filter Buttons --}}
                            <div class="col-md-1 d-flex align-items-end">
                                <div class="d-flex gap-2 w-100">
                                    <button type="submit" class="btn btn-primary flex-fill">
                                        <i class="fas fa-search"></i>
                                    </button>
                                    <a href="{{ route('procurements.vendors.hec-department-vendors') }}" class="btn btn-secondary">
                                        <i class="fas fa-times"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>


            {{-- Vendors List --}}
            <div class="row">
                @forelse($vendors as $vendor)
                    <div class="col-md-6 col-lg-4 mb-4">
                        <div class="card shadow-sm border-0 h-100">
                            <div class="card-header bg-white border-bottom">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div class="flex-grow-1">
                                        <h6 class="mb-1 text-dark">
                                            <i class="fas fa-building me-2 text-success"></i>{{ Str::limit($vendor->name, 30) }}
                                        </h6>
                                        @if($vendor->industry)
                                            <span class="badge bg-info text-white">
                                                <i class="fas fa-industry me-1"></i>
                                                {{ ucfirst(str_replace('_', ' ', $vendor->industry)) }}
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            <div class="card-body">
                                {{-- Contact Info --}}
                                <div class="mb-3">
                                    @if($vendor->contact_person)
                                        <p class="mb-1 small">
                                            <i class="fas fa-user me-2 text-muted"></i>
                                            <strong>Contact:</strong> {{ $vendor->contact_person }}
                                        </p>
                                    @endif
                                    @if($vendor->contact_email)
                                        <p class="mb-1 small">
                                            <i class="fas fa-envelope me-2 text-muted"></i>
                                            <a href="mailto:{{ $vendor->contact_email }}" class="text-decoration-none">
                                                {{ Str::limit($vendor->contact_email, 25) }}
                                            </a>
                                        </p>
                                    @endif
                                    @if($vendor->contact_phone)
                                        <p class="mb-1 small">
                                            <i class="fas fa-phone me-2 text-muted"></i>
                                            <a href="tel:{{ $vendor->contact_phone }}" class="text-decoration-none">
                                                {{ $vendor->contact_phone }}
                                            </a>
                                        </p>
                                    @endif
                                </div>

                                {{-- Contracts by Department --}}
                                @if($vendor->contracts->count() > 0)
                                    <div class="mb-3">
                                        <h6 class="small fw-semibold text-muted mb-2">
                                            <i class="fas fa-file-contract me-1"></i>Contracts ({{ $vendor->contracts->count() }})
                                        </h6>
                                        <div class="list-group list-group-flush">
                                            @php
                                                $contractsByDept = $vendor->contracts->groupBy('department_id');
                                            @endphp
                                            @foreach($contractsByDept->take(3) as $deptId => $contracts)
                                                @php
                                                    $dept = $departments->firstWhere('id', $deptId);
                                                @endphp
                                                <div class="list-group-item px-0 py-2 border-0">
                                                    @if($dept)
                                                        <small class="text-muted d-block mb-1">
                                                            <i class="fas fa-sitemap me-1"></i><strong>{{ $dept->dept_name }}</strong>
                                                        </small>
                                                    @endif
                                                    @foreach($contracts->take(2) as $contract)
                                                        <div class="d-flex justify-content-between align-items-start mb-1">
                                                            <div class="flex-grow-1">
                                                                <a href="{{ route('procurements.contracts.show', $contract->id) }}"
                                                                   class="text-decoration-none text-dark fw-semibold small">
                                                                    {{ Str::limit($contract->title ?? $contract->contract_number ?? 'N/A', 25) }}
                                                                </a>
                                                                <br>
                                                                <small class="text-muted">
                                                                    {{ $contract->contract_number ?? 'N/A' }}
                                                                    @if($contract->status)
                                                                        | <span class="badge bg-{{ $contract->status == 'active' ? 'success' : 'secondary' }} badge-sm">
                                                                            {{ ucfirst($contract->status) }}
                                                                        </span>
                                                                    @endif
                                                                </small>
                                                            </div>
                                                        </div>
                                                    @endforeach
                                                    @if($contracts->count() > 2)
                                                        <small class="text-muted">
                                                            +{{ $contracts->count() - 2 }} more contract(s) in this department
                                                        </small>
                                                    @endif
                                                </div>
                                            @endforeach
                                            @if($contractsByDept->count() > 3)
                                                <small class="text-muted px-0">
                                                    +{{ $contractsByDept->count() - 3 }} more department(s)
                                                </small>
                                            @endif
                                        </div>
                                    </div>
                                @endif

                                {{-- Rating --}}
                                @if($vendor->scores->count() > 0)
                                    @php
                                        $latestScore = $vendor->scores->first();
                                    @endphp
                                    <div class="mb-3">
                                        <div class="d-flex align-items-center">
                                            <span class="small text-muted me-2">Your Rating:</span>
                                            <div>
                                                @for($i = 1; $i <= 5; $i++)
                                                    <i class="fas fa-star {{ $i <= $latestScore->score_value ? 'text-warning' : 'text-muted' }}"></i>
                                                @endfor
                                                <span class="ms-1 small fw-semibold">{{ $latestScore->score_value }}/5</span>
                                            </div>
                                        </div>
                                        @if($latestScore->comments)
                                            <p class="small text-muted mt-1 mb-0">
                                                <em>"{{ Str::limit($latestScore->comments, 50) }}"</em>
                                            </p>
                                        @endif
                                    </div>
                                @else
                                    <div class="mb-3">
                                        <span class="badge bg-warning text-dark">
                                            <i class="fas fa-exclamation-triangle me-1"></i>Not Rated Yet
                                        </span>
                                    </div>
                                @endif
                            </div>
                            <div class="card-footer bg-white border-top">
                                <div class="d-flex gap-2">
                                    <a href="{{ route('procurements.vendors.show', $vendor->id) }}"
                                       class="btn btn-sm btn-outline-success flex-fill">
                                        <i class="fas fa-eye me-1"></i> View Details
                                    </a>
                                    <button type="button"
                                            class="btn btn-sm btn-outline-primary flex-fill"
                                            data-bs-toggle="modal"
                                            data-bs-target="#rateVendorModal{{ $vendor->id }}">
                                        <i class="fas fa-star me-1"></i> Rate
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Rate Vendor Modal --}}
                    <div class="modal fade" id="rateVendorModal{{ $vendor->id }}" tabindex="-1"
                         aria-labelledby="rateVendorModalLabel{{ $vendor->id }}" aria-hidden="true">
                        <div class="modal-dialog">
                            <div class="modal-content border-0 shadow">
                                <div class="modal-header bg-white border-bottom">
                                    <h5 class="modal-title text-dark" id="rateVendorModalLabel{{ $vendor->id }}">
                                        <i class="fas fa-star me-2 text-success"></i>Rate Vendor: {{ $vendor->name }}
                                    </h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <form id="rateVendorForm{{ $vendor->id }}" method="POST"
                                      action="{{ route('procurements.vendors.rate', $vendor->id) }}">
                                    @csrf
                                    <div class="modal-body">
                                        <div class="mb-3">
                                            <label for="rating_type{{ $vendor->id }}" class="form-label fw-semibold">Rating Type</label>
                                            <select class="form-select" id="rating_type{{ $vendor->id }}" name="rating_type" required>
                                                <option value="overall">Overall Rating</option>
                                                <option value="contract_performance">Contract Performance</option>
                                                <option value="quality">Quality</option>
                                                <option value="delivery">Delivery</option>
                                                <option value="communication">Communication</option>
                                            </select>
                                        </div>

                                        <div class="mb-3">
                                            <label for="contract_id{{ $vendor->id }}" class="form-label fw-semibold">Related Contract (Optional)</label>
                                            <select class="form-select" id="contract_id{{ $vendor->id }}" name="contract_id">
                                                <option value="">Select Contract (Optional)</option>
                                                @foreach($vendor->contracts as $contract)
                                                    <option value="{{ $contract->id }}">
                                                        {{ $contract->title ?? $contract->contract_number ?? 'N/A' }}
                                                        @if($contract->contract_number && $contract->title)
                                                            ({{ $contract->contract_number }})
                                                        @endif
                                                    </option>
                                                @endforeach
                                            </select>
                                            <small class="text-muted">Leave blank for overall vendor rating</small>
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label fw-semibold">Rating <span class="text-danger">*</span></label>
                                            <div class="rating-input">
                                                @for($i = 5; $i >= 1; $i--)
                                                    <input type="radio" name="score_value" id="star{{ $i }}_{{ $vendor->id }}"
                                                           value="{{ $i }}" required>
                                                    <label for="star{{ $i }}_{{ $vendor->id }}" class="star-label">
                                                        <i class="fas fa-star"></i>
                                                    </label>
                                                @endfor
                                            </div>
                                            <small class="text-muted d-block mt-2">
                                                1 = Poor, 2 = Fair, 3 = Good, 4 = Very Good, 5 = Excellent
                                            </small>
                                        </div>

                                        <div class="mb-3">
                                            <label for="comments{{ $vendor->id }}" class="form-label fw-semibold">Comments (Optional)</label>
                                            <textarea class="form-control" id="comments{{ $vendor->id }}" name="comments"
                                                      rows="3" placeholder="Add your comments about this vendor..."></textarea>
                                        </div>
                                    </div>
                                    <div class="modal-footer bg-white border-top">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                        <button type="submit" class="btn btn-success">
                                            <i class="fas fa-star me-1"></i> Submit Rating
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="col-12">
                        <div class="card shadow-sm border-0">
                            <div class="card-body text-center py-5">
                                <i class="fas fa-building fa-3x text-muted mb-3"></i>
                                <h5 class="text-muted">No Vendors Found</h5>
                                <p class="text-muted">There are no vendors with contracts in your assigned departments yet.</p>
                            </div>
                        </div>
                    </div>
                @endforelse
            </div>
        </div>
    </div>
@endsection

@push('styles')
    <style>
        .card {
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .card:hover {
            transform: translateY(-2px);
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15) !important;
        }

        .rating-input {
            display: flex;
            flex-direction: row-reverse;
            justify-content: flex-end;
            gap: 5px;
        }

        .rating-input input[type="radio"] {
            display: none;
        }

        .rating-input .star-label {
            font-size: 2rem;
            color: #ddd;
            cursor: pointer;
            transition: color 0.2s;
        }

        .rating-input .star-label:hover,
        .rating-input .star-label:hover ~ .star-label,
        .rating-input input[type="radio"]:checked ~ .star-label {
            color: #ffc107;
        }

        .rating-input input[type="radio"]:checked ~ .star-label {
            color: #ffc107;
        }

        .badge-sm {
            font-size: 0.7rem;
            padding: 0.25em 0.5em;
        }
    </style>
@endpush

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Handle rating form submissions
            @foreach($vendors as $vendor)
                const form{{ $vendor->id }} = document.getElementById('rateVendorForm{{ $vendor->id }}');
                if (form{{ $vendor->id }}) {
                    form{{ $vendor->id }}.addEventListener('submit', function(e) {
                        e.preventDefault();

                        const formData = new FormData(this);
                        const submitBtn = this.querySelector('button[type="submit"]');
                        const originalText = submitBtn.innerHTML;
                        submitBtn.disabled = true;
                        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Submitting...';

                        fetch(this.action, {
                            method: 'POST',
                            body: formData,
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || formData.get('_token')
                            }
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                const modal = bootstrap.Modal.getInstance(document.getElementById('rateVendorModal{{ $vendor->id }}'));
                                if (modal) modal.hide();

                                if (typeof Swal !== 'undefined') {
                                    Swal.fire({
                                        icon: 'success',
                                        title: 'Success!',
                                        text: data.message || 'Vendor rated successfully!',
                                        timer: 2000,
                                        showConfirmButton: false
                                    }).then(() => {
                                        location.reload();
                                    });
                                } else {
                                    alert(data.message || 'Vendor rated successfully!');
                                    location.reload();
                                }
                            } else {
                                alert(data.message || 'An error occurred while rating the vendor.');
                                submitBtn.disabled = false;
                                submitBtn.innerHTML = originalText;
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            alert('An error occurred while rating the vendor.');
                            submitBtn.disabled = false;
                            submitBtn.innerHTML = originalText;
                        });
                    });
                }
            @endforeach
        });
    </script>
@endpush

