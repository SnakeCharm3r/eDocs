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
                            <h3 class="page-title">Edit Role</h3>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-body">
                            @if ($role)
                                <form action="{{ route('role.update', $role->id) }}" method="POST">
                                    @csrf
                                    @method('PUT')
                                    <div class="mb-3">
                                        <label for="name" class="form-label">Role Name <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="name" name="name" value="{{ old('name', $role->name) }}" required>
                                    </div>
                                    <div class="text-right">
                                        <button type="submit" class="btn btn-primary" style="background-color: #61ce70; border-color: #61ce70;">Save changes</button>
                                        <a href="{{ route('role.index') }}" class="btn btn-secondary">Cancel</a>
                                    </div>
                                </form>
                            @else
                                <p>No role record found.</p>
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
                confirmButtonText: 'OK',
                confirmButtonColor: '#61ce70'
            });
        @endif
        @if (session('error'))
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: '{{ session('error') }}',
                confirmButtonText: 'OK',
                confirmButtonColor: '#61ce70'
            });
        @endif
    </script>
@endsection