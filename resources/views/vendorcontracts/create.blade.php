@extends('layouts.template')

@section('breadcrumb')
    @include('sweetalert::alert')
@endsection

@section('content')
    <div class="page-wrapper">
        <div class="content container-fluid">

            {{-- Page Title --}}
            <div class="row mb-3">
                <div class="col-md-12 d-flex align-items-center">
                    <h3 class="page-title text-muted">
                        <strong><i class="fas fa-database"></i> Store and update contracts files in here</strong>
                    </h3>
                </div>
            </div>

            {{-- Gateway Card --}}
            <div class="card mb-4 shadow-lg border-0">
                <div class="card-body text-center">

                    <div class="d-flex flex-column flex-md-row justify-content-center align-items-center gap-4 mb-4">

                        {{-- Upload Existing Contract --}}
                        <a href="{{ route('vendorContract.upload') }}"
                            class="btn btn-primary btn-lg px-5 py-3 d-flex align-items-center gap-2 shadow-sm">
                            <i class="fas fa-upload"></i>
                            Upload Contract
                        </a>

                        {{-- Add New Contract --}}
                        <a href="{{ route('vendorContract.makeContract') }}"
                            class="btn btn-warning btn-lg px-5 py-3 d-flex align-items-center gap-2 shadow-sm text-dark">
                            <i class="fas fa-folder-plus"></i>
                            Contract renewals
                        </a>

                        {{-- View All Contracts --}}
                        <a href="{{ route('vendorContract.index') }}"
                            class="btn btn-success btn-lg px-5 py-3 d-flex align-items-center gap-2 shadow-sm">
                            <i class="fas fa-list"></i>
                            View All Contracts
                        </a>
                    </div>

                    {{-- Optional Card Title/Description --}}
                    <h5 class="card-title text-muted">
                        <strong><i class="fas fa-file-alt"></i> Manage your contracts efficiently</strong>
                    </h5>
                    <p class="text-muted">Choose an option above to upload, create, or view all contracts.</p>
                </div>
            </div>

            {{-- Contract Form --}}
            <div class="card mb-4 shadow-lg border-0">
                <div class="card-body">
                    <form action="{{ isset($contract) ? route('vendorContract.update', $contract->id) : route('vendorContract.store') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        @if(isset($contract))
                            @method('PUT')
                            <input type="hidden" name="id" value="{{ $contract->id }}">
                        @endif

                        <div class="mb-3">
                            <label class="form-label">Title</label>
                            <input name="title" id="title" class="form-control" value="{{ old('title', $contract->title ?? '') }}">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Vendor</label>
                            <select name="vendor_id" id="vendor_id" class="form-select">
                                <option value="">-- choose --</option>
                                @foreach(\App\Models\CcbrtVendor::all() as $v)
                                    <option value="{{ $v->id }}" {{ (old('vendor_id', $contract->vendor_id ?? '') == $v->id) ? 'selected' : '' }}>
                                        {{ $v->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Cost</label>
                            <input name="cost" id="cost" class="form-control" type="number" step="0.01" value="{{ old('cost', $contract->cost ?? '') }}">
                        </div>

                        {{-- add other inputs similarly using old() with $contract fallback --}}
                        <button class="btn btn-primary" type="submit">Save</button>
                    </form>
                </div>
            </div>

        </div>
    </div>

    {{-- Hover Effect --}}
    <style>
        .btn-lg {
            transition: transform 0.2s ease, box-shadow 0.2s ease;
            border-radius: 10px;
        }

        .btn-lg:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15);
        }
    </style>
@endsection
