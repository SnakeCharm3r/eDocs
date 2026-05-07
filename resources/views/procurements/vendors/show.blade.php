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
            <div class="page-header">
                <div class="row align-items-center">
                    <div class="col">
                        <h3 class="page-title">
                            <i class="fas fa-building me-2"></i>Vendor Details
                        </h3>
                    </div>
                    <div class="col-auto">
                        <a href="{{ route('procurements.vendors.index') }}" class="btn btn-secondary">
                            <i class="fas fa-arrow-left me-1"></i> Back to List
                        </a>
                        <a href="{{ route('procurements.vendors.edit', $vendor->id) }}" class="btn btn-success">
                            <i class="fas fa-edit me-1"></i> Edit
                        </a>
                    </div>
                </div>
            </div>

            <div class="row">
                <!-- Status Overview Card -->
                <div class="col-lg-12 mb-4">
                    <div class="card shadow-sm border-0 bg-white">
                        <div class="card-body p-4">
                            <div class="row align-items-center">
                                <div class="col-md-8">
                                    <h3 class="mb-2 text-dark">
                                        {{ $vendor->name }}
                                    </h3>
                                    <p class="mb-0 text-muted">
                                        <i
                                            class="fas fa-tag me-1"></i>{{ ucfirst(str_replace('_', ' ', $vendor->type ?? 'N/A')) }}
                                        @if ($vendor->industry)
                                            | <i class="fas fa-industry me-1"></i>{{ $vendor->industry }}
                                        @endif
                                    </p>
                                    @if ($vendor->owner_name)
                                        <p class="mb-0 mt-2 text-muted">
                                            <i class="fas fa-user-tie me-1"></i>Owner: {{ $vendor->owner_name }}
                                        </p>
                                    @endif
                                </div>
                                <div class="col-md-4 text-end">
                                    <span
                                        class="badge bg-{{ ($vendor->status ?? 'active') == 'active' ? 'success' : 'danger' }} text-white fs-6 px-3 py-2 mb-2 d-inline-block">
                                        <i
                                            class="fas {{ ($vendor->status ?? 'active') == 'active' ? 'fa-check-circle' : 'fa-times-circle' }} me-1"></i>
                                        {{ ucfirst($vendor->status ?? 'Active') }}
                                    </span>
                                    @if ($vendor->average_rating > 0)
                                        <div class="mt-2">
                                            <small class="text-warning">
                                                {{ str_repeat('★', floor($vendor->average_rating)) }}{{ str_repeat('☆', 5 - floor($vendor->average_rating)) }}
                                                <strong>{{ number_format($vendor->average_rating, 1) }}/5.0</strong>
                                            </small>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Sidebar with Quick Stats -->
                <div class="col-lg-4 mb-4">
                    <!-- Quick Stats Card -->
                    <div class="card shadow-sm border-0 mb-4">
                        <div class="card-header bg-white border-bottom">
                            <h6 class="mb-0 text-dark">
                                <i class="fas fa-chart-bar me-2"></i>Quick Stats
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center mb-3 pb-3 border-bottom">
                                <div>
                                    <small class="text-muted d-block">Total Contracts</small>
                                    <strong class="text-dark">{{ $vendor->contracts->count() }}</strong>
                                </div>
                                <i class="fas fa-file-contract fa-2x text-muted opacity-25"></i>
                            </div>
                            @if ($vendor->years_in_business)
                                <div class="d-flex justify-content-between align-items-center mb-3 pb-3 border-bottom">
                                    <div>
                                        <small class="text-muted d-block">Years in Business</small>
                                        <strong class="text-dark">{{ $vendor->years_in_business }} years</strong>
                                    </div>
                                    <i class="fas fa-calendar-alt fa-2x text-muted opacity-25"></i>
                                </div>
                            @endif
                            @if ($vendor->number_of_employees)
                                <div class="d-flex justify-content-between align-items-center mb-3 pb-3 border-bottom">
                                    <div>
                                        <small class="text-muted d-block">Employees</small>
                                        <strong class="text-dark">{{ $vendor->number_of_employees }}</strong>
                                    </div>
                                    <i class="fas fa-users fa-2x text-muted opacity-25"></i>
                                </div>
                            @endif
                            @php
                                $attachments = json_decode($vendor->attachments ?? '[]', true) ?: [];
                            @endphp
                            @if (count($attachments) > 0)
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <small class="text-muted d-block">Documents</small>
                                        <strong class="text-dark">{{ count($attachments) }} file(s)</strong>
                                    </div>
                                    <i class="fas fa-paperclip fa-2x text-muted opacity-25"></i>
                                </div>
                            @endif
                        </div>
                    </div>

                    <!-- Actions Card -->
                    <div class="card shadow-sm border-0">
                        <div class="card-header bg-white border-bottom">
                            <h6 class="mb-0 text-dark">
                                <i class="fas fa-bolt me-2"></i>Quick Actions
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="d-grid gap-2">
                                <a href="{{ route('procurements.vendors.index') }}"
                                    class="btn btn-outline-secondary btn-sm">
                                    <i class="fas fa-arrow-left me-1"></i> Back to List
                                </a>
                                <a href="{{ route('procurements.vendors.edit', $vendor->id) }}"
                                    class="btn btn-outline-success btn-sm">
                                    <i class="fas fa-edit me-1"></i> Edit Vendor
                                </a>
                                @php
                                    $user = auth()->user();
                                    $isLineManager = $user && $user->hasRole('line-manager');
                                @endphp
                                @if ($isLineManager)
                                    <button type="button" class="btn btn-outline-primary btn-sm" data-bs-toggle="modal"
                                        data-bs-target="#rateVendorModal">
                                        <i class="fas fa-star me-1"></i> Rate Vendor
                                    </button>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Main Content -->
                <div class="col-lg-8">
                    <!-- Basic Information Section -->
                    <div class="card shadow-sm mb-4 border-0">
                        <div class="card-header bg-white border-bottom">
                            <h5 class="mb-0 text-dark">
                                <i class="fas fa-info-circle me-2"></i>Basic Information
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <div class="info-item p-3 bg-light rounded">
                                        <label class="text-muted small mb-1 d-block">
                                            <i class="fas fa-building me-1"></i>Vendor Name
                                        </label>
                                        <div class="fw-bold text-dark">{{ $vendor->name }}</div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="info-item p-3 bg-light rounded">
                                        <label class="text-muted small mb-1 d-block">
                                            <i class="fas fa-tag me-1"></i>Vendor Type
                                        </label>
                                        <div class="fw-bold">
                                            <span class="badge bg-secondary fs-6 px-3 py-2">
                                                {{ ucfirst(str_replace('_', ' ', $vendor->type ?? 'N/A')) }}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="info-item p-3 bg-light rounded">
                                        <label class="text-muted small mb-1 d-block">
                                            <i class="fas fa-user-tie me-1"></i>Owner/CEO
                                        </label>
                                        <div class="fw-bold text-dark">{{ $vendor->owner_name ?? 'N/A' }}</div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="info-item p-3 bg-light rounded">
                                        <label class="text-muted small mb-1 d-block">
                                            <i class="fas fa-industry me-1"></i>Industry
                                        </label>
                                        <div class="fw-bold text-dark">{{ $vendor->industry ?? 'N/A' }}</div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="info-item p-3 bg-light rounded">
                                        <label class="text-muted small mb-1 d-block">
                                            <i class="fas fa-globe me-1"></i>Country
                                        </label>
                                        <div class="fw-bold text-dark">{{ $vendor->country ?? 'N/A' }}</div>
                                    </div>
                                </div>
                                @if ($vendor->years_in_business)
                                    <div class="col-md-6">
                                        <div class="info-item p-3 bg-light rounded">
                                            <label class="text-muted small mb-1 d-block">
                                                <i class="fas fa-calendar-alt me-1"></i>Years in Business
                                            </label>
                                            <div class="fw-bold text-dark">{{ $vendor->years_in_business }} year(s)</div>
                                        </div>
                                    </div>
                                @endif
                                @if ($vendor->number_of_employees)
                                    <div class="col-md-6">
                                        <div class="info-item p-3 bg-light rounded">
                                            <label class="text-muted small mb-1 d-block">
                                                <i class="fas fa-users me-1"></i>Number of Employees
                                            </label>
                                            <div class="fw-bold text-dark">{{ $vendor->number_of_employees }}</div>
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>

                    <!-- Contact Information Section -->
                    <div class="card shadow-sm mb-4 border-0">
                        <div class="card-header bg-white border-bottom">
                            <h5 class="mb-0 text-dark">
                                <i class="fas fa-address-book me-2"></i>Contact Information
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <div class="info-item p-3 bg-light rounded">
                                        <label class="text-muted small mb-1 d-block">
                                            <i class="fas fa-user me-1"></i>Contact Person
                                        </label>
                                        <div class="fw-bold text-dark">{{ $vendor->contact_person ?? 'N/A' }}</div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="info-item p-3 bg-light rounded">
                                        <label class="text-muted small mb-1 d-block">
                                            <i class="fas fa-envelope me-1"></i>Email
                                        </label>
                                        <div class="fw-bold">
                                            @if ($vendor->contact_email)
                                                <a href="mailto:{{ $vendor->contact_email }}"
                                                    class="text-decoration-none text-dark">
                                                    {{ $vendor->contact_email }}
                                                </a>
                                            @else
                                                <span class="text-muted">N/A</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="info-item p-3 bg-light rounded">
                                        <label class="text-muted small mb-1 d-block">
                                            <i class="fas fa-phone me-1"></i>Phone
                                        </label>
                                        <div class="fw-bold">
                                            @if ($vendor->contact_phone)
                                                <a href="tel:{{ $vendor->contact_phone }}"
                                                    class="text-decoration-none text-dark">
                                                    {{ $vendor->contact_phone }}
                                                </a>
                                            @else
                                                <span class="text-muted">N/A</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                                @if ($vendor->alternative_phone)
                                    <div class="col-md-6">
                                        <div class="info-item p-3 bg-light rounded">
                                            <label class="text-muted small mb-1 d-block">
                                                <i class="fas fa-phone-alt me-1"></i>Alternative Phone
                                            </label>
                                            <div class="fw-bold">
                                                <a href="tel:{{ $vendor->alternative_phone }}"
                                                    class="text-decoration-none text-dark">
                                                    {{ $vendor->alternative_phone }}
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                @endif
                                @if ($vendor->website)
                                    <div class="col-md-6">
                                        <div class="info-item p-3 bg-light rounded">
                                            <label class="text-muted small mb-1 d-block">
                                                <i class="fas fa-globe me-1"></i>Website
                                            </label>
                                            <div class="fw-bold">
                                                <a href="{{ $vendor->website }}" target="_blank"
                                                    class="text-decoration-none text-dark">
                                                    {{ $vendor->website }}
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                @endif
                                @if ($vendor->address)
                                    <div class="col-12">
                                        <div class="info-item p-3 bg-light rounded">
                                            <label class="text-muted small mb-1 d-block">
                                                <i class="fas fa-map-marker-alt me-1"></i>Address
                                            </label>
                                            <div class="fw-bold text-dark">{{ $vendor->address }}</div>
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>

                    <!-- Business Registration Section -->
                    <div class="card shadow-sm mb-4 border-0">
                        <div class="card-header bg-white border-bottom">
                            <h5 class="mb-0 text-dark">
                                <i class="fas fa-file-alt me-2"></i>Business Registration
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <div class="info-item p-3 bg-light rounded">
                                        <label class="text-muted small mb-1 d-block">
                                            <i class="fas fa-file-contract me-1"></i>Registration Number
                                        </label>
                                        <div class="fw-bold text-dark">{{ $vendor->registration_number ?? 'N/A' }}</div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="info-item p-3 bg-light rounded">
                                        <label class="text-muted small mb-1 d-block">
                                            <i class="fas fa-receipt me-1"></i>Tax Number (TIN)
                                        </label>
                                        <div class="fw-bold text-dark">{{ $vendor->tax_number ?? 'N/A' }}</div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="info-item p-3 bg-light rounded">
                                        <label class="text-muted small mb-1 d-block">
                                            <i class="fas fa-calendar me-1"></i>Registered Date
                                        </label>
                                        <div class="fw-bold text-dark">
                                            @if ($vendor->registered_at)
                                                {{ \Carbon\Carbon::parse($vendor->registered_at)->format('F d, Y') }}
                                            @else
                                                {{ \Carbon\Carbon::parse($vendor->created_at)->format('F d, Y') }}
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Financial Information Section -->
                    @if ($vendor->bank_name || $vendor->bank_account_number || $vendor->payment_terms || $vendor->currency)
                        <div class="card shadow-sm mb-4 border-0">
                            <div class="card-header bg-white border-bottom">
                                <h5 class="mb-0 text-dark">
                                    <i class="fas fa-money-bill-wave me-2"></i>Financial Information
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="row g-3">
                                    @if ($vendor->bank_name)
                                        <div class="col-md-6">
                                            <div class="info-item p-3 bg-light rounded">
                                                <label class="text-muted small mb-1 d-block">
                                                    <i class="fas fa-university me-1"></i>Bank Name
                                                </label>
                                                <div class="fw-bold text-dark">{{ $vendor->bank_name }}</div>
                                            </div>
                                        </div>
                                    @endif
                                    @if ($vendor->bank_account_number)
                                        <div class="col-md-6">
                                            <div class="info-item p-3 bg-light rounded">
                                                <label class="text-muted small mb-1 d-block">
                                                    <i class="fas fa-credit-card me-1"></i>Bank Account Number
                                                </label>
                                                <div class="fw-bold text-dark">{{ $vendor->bank_account_number }}</div>
                                            </div>
                                        </div>
                                    @endif
                                    @if ($vendor->payment_terms)
                                        <div class="col-md-6">
                                            <div class="info-item p-3 bg-light rounded">
                                                <label class="text-muted small mb-1 d-block">
                                                    <i class="fas fa-handshake me-1"></i>Payment Terms
                                                </label>
                                                <div class="fw-bold">
                                                    <span class="badge bg-info fs-6 px-3 py-2">
                                                        {{ ucfirst(str_replace('_', ' ', $vendor->payment_terms)) }}
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    @endif
                                    @if ($vendor->currency)
                                        <div class="col-md-6">
                                            <div class="info-item p-3 bg-light rounded">
                                                <label class="text-muted small mb-1 d-block">
                                                    <i class="fas fa-coins me-1"></i>Preferred Currency
                                                </label>
                                                <div class="fw-bold">
                                                    <span
                                                        class="badge bg-secondary fs-6 px-3 py-2">{{ $vendor->currency }}</span>
                                                </div>
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endif

                    <!-- Rating & Performance Section -->
                    <div class="card shadow-sm mb-4 border-0">
                        <div class="card-header bg-white border-bottom">
                            <h5 class="mb-0 text-dark">
                                <i class="fas fa-star me-2"></i>Rating & Performance
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-md-12">
                                    <div class="info-item p-4 bg-light rounded">
                                        <label class="text-muted small mb-2 d-block">
                                            <i class="fas fa-star me-1"></i>Average Rating
                                        </label>
                                        @if ($vendor->average_rating > 0)
                                            <div class="display-6 fw-bold text-dark">
                                                <span class="text-warning">
                                                    {{ str_repeat('★', floor($vendor->average_rating)) }}{{ str_repeat('☆', 5 - floor($vendor->average_rating)) }}
                                                </span>
                                                <span
                                                    class="fs-4 ms-2">{{ number_format($vendor->average_rating, 1) }}/5.0</span>
                                            </div>
                                            <small class="text-muted">Based on {{ $vendor->scores->count() }}
                                                rating(s)</small>
                                        @else
                                            <div class="text-muted">Not rated yet</div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            @php
                                $user = auth()->user();
                                $isLineManager = $user && $user->hasRole('line-manager');
                            @endphp
                            @if ($isLineManager)
                                <div class="mt-3">
                                    <button type="button" class="btn btn-success btn-sm" data-bs-toggle="modal"
                                        data-bs-target="#rateVendorModal">
                                        <i class="fas fa-star me-1"></i> Rate Vendor
                                    </button>
                                </div>
                            @endif
                        </div>
                    </div>

                    <!-- Contracts Section -->
                    @if ($vendor->contracts->count() > 0)
                        <div class="card shadow-sm mb-4 border-0">
                            <div class="card-header bg-white border-bottom">
                                <h5 class="mb-0 text-dark">
                                    <i class="fas fa-file-contract me-2"></i>Contracts ({{ $vendor->contracts->count() }})
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="list-group">
                                    @foreach ($vendor->contracts->take(10) as $contract)
                                        <a href="{{ route('procurements.contracts.show', $contract->id) }}"
                                            class="list-group-item list-group-item-action">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <div>
                                                    <strong>{{ $contract->title ?? 'N/A' }}</strong>
                                                    <br>
                                                    <small class="text-muted">
                                                        {{ $contract->contract_number ?? 'N/A' }}
                                                        @if ($contract->cost)
                                                            | {{ number_format($contract->cost, 2) }}
                                                            {{ $contract->currency ?? 'TZS' }}
                                                        @endif
                                                    </small>
                                                </div>
                                                <span
                                                    class="badge bg-{{ ($contract->status ?? '') === 'active' ? 'success' : 'secondary' }}">
                                                    {{ ucfirst($contract->status ?? 'N/A') }}
                                                </span>
                                            </div>
                                        </a>
                                    @endforeach
                                </div>
                                @if ($vendor->contracts->count() > 10)
                                    <p class="text-muted mt-3 mb-0">
                                        <i class="fas fa-info-circle me-1"></i>... and
                                        {{ $vendor->contracts->count() - 10 }} more contract(s)
                                    </p>
                                @endif
                            </div>
                        </div>
                    @endif

                    <!-- Attachments Section -->
                    @php
                        $attachments = json_decode($vendor->attachments ?? '[]', true) ?: [];
                    @endphp
                    @if (count($attachments) > 0)
                        <div class="card shadow-sm mb-4 border-0">
                            <div class="card-header bg-white border-bottom">
                                <h5 class="mb-0 text-dark">
                                    <i class="fas fa-paperclip me-2"></i>Documents & Attachments
                                    ({{ count($attachments) }})
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="list-group">
                                    @foreach ($attachments as $attachment)
                                        <div class="list-group-item d-flex justify-content-between align-items-center">
                                            <div>
                                                <i class="fas fa-file me-2 text-muted"></i>
                                                <strong>{{ $attachment['document_name'] ?? ($attachment['original_name'] ?? ($attachment['name'] ?? 'File')) }}</strong>
                                                @if (isset($attachment['original_name']) &&
                                                        isset($attachment['document_name']) &&
                                                        $attachment['document_name'] !== $attachment['original_name']
                                                )
                                                    <br>
                                                    <small class="text-muted">
                                                        <i class="fas fa-file me-1"></i>{{ $attachment['original_name'] }}
                                                    </small>
                                                @endif
                                                @if (isset($attachment['file_size']))
                                                    <br>
                                                    <small class="text-muted">
                                                        {{ number_format($attachment['file_size'] / 1024, 2) }} KB
                                                        @if (isset($attachment['file_type']))
                                                            | {{ strtoupper($attachment['file_type']) }}
                                                        @endif
                                                    </small>
                                                @endif
                                            </div>
                                            @if (isset($attachment['file_path']))
                                                <a href="{{ asset($attachment['file_path']) }}" target="_blank"
                                                    class="btn btn-sm btn-outline-success">
                                                    <i class="fas fa-download me-1"></i> Download
                                                </a>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    @endif

                    <!-- Notes Section -->
                    @if ($vendor->notes)
                        <div class="card shadow-sm mb-4 border-0">
                            <div class="card-header bg-white border-bottom">
                                <h5 class="mb-0 text-dark">
                                    <i class="fas fa-sticky-note me-2"></i>Notes & Remarks
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="alert alert-light border">
                                    <p class="mb-0">{{ $vendor->notes }}</p>
                                </div>
                            </div>
                        </div>
                    @endif

                    <!-- Rating History Section -->
                    @if ($vendor->scores->count() > 0)
                        <div class="card shadow-sm mb-4 border-0">
                            <div class="card-header bg-white border-bottom">
                                <h5 class="mb-0 text-dark">
                                    <i class="fas fa-history me-2"></i>Rating History
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-sm table-hover">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Date</th>
                                                <th>Rated By</th>
                                                <th>Rating</th>
                                                <th>Type</th>
                                                <th>Contract</th>
                                                <th>Comments</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($vendor->scores->sortByDesc('created_at') as $score)
                                                <tr>
                                                    <td>{{ $score->created_at->format('Y-m-d') }}</td>
                                                    <td>{{ $score->scorer->name ?? 'N/A' }}</td>
                                                    <td>
                                                        <span class="text-warning">
                                                            {{ str_repeat('★', $score->score_value) }}{{ str_repeat('☆', 5 - $score->score_value) }}
                                                        </span>
                                                        <small class="text-muted">({{ $score->score_value }}/5)</small>
                                                    </td>
                                                    <td>
                                                        <span class="badge bg-secondary">
                                                            {{ ucfirst(str_replace('_', ' ', $score->rating_type)) }}
                                                        </span>
                                                    </td>
                                                    <td>
                                                        @if ($score->contract)
                                                            <a href="{{ route('procurements.contracts.show', $score->contract->id) }}"
                                                                class="text-decoration-none">
                                                                {{ Str::limit($score->contract->title, 30) }}
                                                            </a>
                                                        @else
                                                            <span class="text-muted">Overall</span>
                                                        @endif
                                                    </td>
                                                    <td>
                                                        @if ($score->comments)
                                                            <span title="{{ $score->comments }}">
                                                                {{ Str::limit($score->comments, 50) }}
                                                            </span>
                                                        @else
                                                            <span class="text-muted">N/A</span>
                                                        @endif
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Rate Vendor Modal --}}
    <div class="modal fade" id="rateVendorModal" tabindex="-1" aria-labelledby="rateVendorModalLabel"
        aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-white border-bottom">
                    <h5 class="modal-title text-dark" id="rateVendorModalLabel">
                        <i class="fas fa-star me-2 text-muted"></i>Rate Vendor: {{ $vendor->name }}
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="rateVendorForm" method="POST" action="{{ route('procurements.vendors.rate', $vendor->id) }}">
                    @csrf
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="rating_type" class="form-label fw-semibold">Rating Type</label>
                            <select class="form-select" id="rating_type" name="rating_type" required>
                                <option value="overall">Overall Rating</option>
                                <option value="contract_performance">Contract Performance</option>
                                <option value="quality">Quality</option>
                                <option value="delivery">Delivery</option>
                                <option value="communication">Communication</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="contract_id" class="form-label fw-semibold">Related Contract (Optional)</label>
                            <select class="form-select" id="contract_id" name="contract_id">
                                <option value="">Select Contract (Optional)</option>
                                @foreach ($vendor->contracts as $contract)
                                    <option value="{{ $contract->id }}">{{ $contract->title }}</option>
                                @endforeach
                            </select>
                            <small class="text-muted">Leave blank for overall vendor rating</small>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Rating <span class="text-danger">*</span></label>
                            <div class="rating-input">
                                @for ($i = 5; $i >= 1; $i--)
                                    <input type="radio" name="score_value" id="star{{ $i }}"
                                        value="{{ $i }}" required>
                                    <label for="star{{ $i }}" class="star-label">
                                        <i class="fas fa-star"></i>
                                    </label>
                                @endfor
                            </div>
                            <small class="text-muted d-block mt-2">Click on a star to rate (1 = Poor, 5 =
                                Excellent)</small>
                        </div>

                        <div class="mb-3">
                            <label for="comments" class="form-label fw-semibold">Comments (Optional)</label>
                            <textarea class="form-control" id="comments" name="comments" rows="3"
                                placeholder="Add your comments about this vendor..."></textarea>
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

    @push('styles')
        <style>
            /* Green icons styling */
            .fas,
            .fa,
            i[class*="fa-"] {
                color: #28a745 !important;
            }

            /* Override for specific cases where we want different colors */
            .badge .fas,
            .badge .fa {
                color: inherit !important;
            }

            .btn .fas,
            .btn .fa {
                color: inherit !important;
            }

            .text-warning .fas,
            .text-warning .fa {
                color: #ffc107 !important;
            }

            .text-danger .fas,
            .text-danger .fa {
                color: #dc3545 !important;
            }

            .text-success .fas,
            .text-success .fa {
                color: #28a745 !important;
            }

            .text-muted .fas,
            .text-muted .fa {
                color: #28a745 !important;
            }

            /* Opacity icons in stats should remain muted */
            .opacity-25 {
                opacity: 0.25 !important;
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
            .rating-input .star-label:hover~.star-label {
                color: #ffc107;
            }

            .rating-input input[type="radio"]:checked~.star-label {
                color: #ffc107;
            }

            .rating-input input[type="radio"]:checked~.star-label,
            .rating-input input[type="radio"]:checked~.star-label~.star-label {
                color: #ffc107;
            }

            .info-item {
                transition: all 0.2s ease;
            }

            .info-item:hover {
                background-color: #e9ecef !important;
            }

            .list-group-item {
                border-left: none;
                border-right: none;
            }

            .list-group-item:first-child {
                border-top: none;
            }
        </style>
    @endpush

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const form = document.getElementById('rateVendorForm');
                if (form) {
                    form.addEventListener('submit', function(e) {
                        e.preventDefault();

                        const formData = new FormData(form);
                        const url = form.action;

                        fetch(url, {
                                method: 'POST',
                                body: formData,
                                headers: {
                                    'X-Requested-With': 'XMLHttpRequest',
                                    'Accept': 'application/json'
                                }
                            })
                            .then(response => response.json())
                            .then(data => {
                                if (data.success) {
                                    const modal = bootstrap.Modal.getInstance(document.getElementById(
                                        'rateVendorModal'));
                                    modal.hide();

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
                                }
                            })
                            .catch(error => {
                                console.error('Error:', error);
                                alert('An error occurred while rating the vendor.');
                            });
                    });
                }
            });
        </script>
    @endpush
@endsection
