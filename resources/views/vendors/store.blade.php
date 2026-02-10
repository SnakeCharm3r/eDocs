@extends('layouts.template')

@section('breadcrumb')
    @include('sweetalert::alert')
@endsection

@section('content')
<div class="page-wrapper">
    <div class="content container-fluid">

        {{-- Page Title --}}
        <div class="row mb-4">
            <div class="col-md-12 text-center">
                <h3 class="page-title text-muted">
                    <strong><i class="fas fa-user-tie"></i> Add New Vendor</strong>
                </h3>
            </div>
        </div>

        {{-- Form Card --}}
        <div class="card shadow-sm">
            <div class="card-body">

                <form action="{{ route('vendors.store') }}" method="POST">
                    @csrf

                    {{-- Row 1: Vendor Name & Vendor Type --}}
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="name" class="form-label">Vendor Name</label>
                            <input type="text" name="name" id="name" class="form-control input-hover" placeholder="Enter vendor name" required>
                        </div>
                        <div class="col-md-6">
                            <label for="type" class="form-label">Vendor Type</label>
                            <input type="text" name="type" id="type" class="form-control input-hover" placeholder="e.g., Supplier, Service Provider" required>
                        </div>
                    </div>

                    {{-- Row 2: Contact Person & Contact Email --}}
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="contact_person" class="form-label">Contact Person</label>
                            <input type="text" name="contact_person" id="contact_person" class="form-control input-hover" placeholder="Enter contact person" required>
                        </div>
                        <div class="col-md-6">
                            <label for="contact_email" class="form-label">Contact Email</label>
                            <input type="email" name="contact_email" id="contact_email" class="form-control input-hover" placeholder="Enter email address" required>
                        </div>
                    </div>

                    {{-- Submit Button --}}
                    <div class="row mt-4">
                        <div class="col-md-12 text-center">
                            <button type="submit" class="btn btn-success btn-lg">
                                <i class="fas fa-plus-circle"></i> Add Vendor
                            </button>
                        </div>
                    </div>

                </form>
            </div>
        </div>

    </div>
</div>
@endsection

@push('styles')
<style>
/* Hover effect for inputs */
.input-hover:hover,
.input-hover:focus {
    border-color: #28a745 !important; /* Bootstrap green */
    box-shadow: 0 0 5px rgba(40, 167, 69, 0.5);
    transition: all 0.3s ease-in-out;
}
</style>
@endpush
