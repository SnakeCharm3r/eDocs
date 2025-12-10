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
                            <h3 class="page-title">Edit eDocs Level</h3>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-body">
                            @if ($edocs)
                                <form action="{{ route('edocs.update', $edocs->id) }}" method="POST">
                                    @csrf
                                    @method('PUT')
                                    <div class="mb-3">
                                        <label for="edocs_name" class="form-label">Name</label>
                                        <input type="text" class="form-control" id="edocs_name" name="edocs_name"
                                            value="{{ old('edocs_name', $edocs->edocs_name) }}" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="edocs_status" class="form-label">Status</label>
                                        <select class="form-control" id="edocs_status" name="edocs_status" required>
                                            <option value="active"
                                                {{ old('edocs_status', $edocs->edocs_status) == 'active' ? 'selected' : '' }}>
                                                Active</option>
                                            <option value="not_active"
                                                {{ old('edocs_status', $edocs->edocs_status) == 'not_active' ? 'selected' : '' }}>
                                                Not Active</option>
                                        </select>
                                    </div>
                                    <button type="submit" class="btn btn-primary">Update</button>
                                    <a href="{{ route('edocs.index') }}" class="btn btn-secondary">Cancel</a>
                                </form>
                            @else
                                <p>No eDocs record found.</p>
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

