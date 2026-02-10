@extends('layouts.template')

@section('breadcrumb')
    @include('sweetalert::alert')
    @include('includes.loader')
@endsection

@section('content')
    <div class="page-wrapper">
        <div class="content container-fluid">
            <div class="page-header">
                <div class="row">
                    <div class="col-sm-12">
                        <div class="page-sub-header">
                            <h3 class="page-title">Edit Privilege</h3>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-body">
                            @if ($priv)
                                <form action="{{ route('privilege.update', $priv->id) }}" method="POST">
                                    @csrf
                                    @method('PUT')
                                    <div class="mb-3">
                                        <label for="prv_name" class="form-label">Name</label>
                                        <input type="text" class="form-control" id="prv_name" name="prv_name"
                                            value="{{ old('prv_name', $priv->prv_name) }}" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="prv_status" class="form-label">Status</label>
                                        <select class="form-control" id="prv_status" name="prv_status" required>
                                            <option value="active"
                                                {{ old('prv_status', $priv->prv_status) == 'active' ? 'selected' : '' }}>
                                                Active</option>
                                            <option value="not_active"
                                                {{ old('prv_status', $priv->prv_status) == 'not_active' ? 'selected' : '' }}>
                                                Not Active</option>
                                        </select>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label mb-2">Available For <small class="text-muted">(Select where this privilege can be used)</small></label>
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" name="can_use_for_domain_access" id="can_use_for_domain_access" value="1" {{ old('can_use_for_domain_access', $priv->can_use_for_domain_access) ? 'checked' : '' }}>
                                                    <label class="form-check-label" for="can_use_for_domain_access">
                                                        Domain Access Level
                                                    </label>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" name="can_use_for_email_access" id="can_use_for_email_access" value="1" {{ old('can_use_for_email_access', $priv->can_use_for_email_access) ? 'checked' : '' }}>
                                                    <label class="form-check-label" for="can_use_for_email_access">
                                                        Email Access Level
                                                    </label>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" name="can_use_for_vpn_access" id="can_use_for_vpn_access" value="1" {{ old('can_use_for_vpn_access', $priv->can_use_for_vpn_access) ? 'checked' : '' }}>
                                                    <label class="form-check-label" for="can_use_for_vpn_access">
                                                        Network Access VPN
                                                    </label>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" name="can_use_for_pbax_access" id="can_use_for_pbax_access" value="1" {{ old('can_use_for_pbax_access', $priv->can_use_for_pbax_access) ? 'checked' : '' }}>
                                                    <label class="form-check-label" for="can_use_for_pbax_access">
                                                        Call Manager-PABX
                                                    </label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <button type="submit" class="btn btn-primary">Update</button>
                                    <a href="{{ route('privilege.index') }}" class="btn btn-secondary">Cancel</a>
                                </form>
                            @else
                                <p>No privilege record found.</p>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Handle SweetAlert for session messages
        @if (session('success'))
            Swal.fire({
                icon: 'success',
                title: 'Success',
                text: '{{ session('success') }}',
                confirmButtonText: 'OK'
            });
        @endif
        @if (session('error'))
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: '{{ session('error') }}',
                confirmButtonText: 'OK'
            });
        @endif
    </script>
@endsection
