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
                            <h3 class="page-title">Edit Network Folder</h3>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-body">
                            @if ($folder)
                                <form action="{{ route('network-folder.update', $folder->id) }}" method="POST">
                                    @csrf
                                    @method('PUT')
                                    <div class="mb-3">
                                        <label for="folder_name" class="form-label">Folder Name</label>
                                        <input type="text" class="form-control" id="folder_name" name="folder_name"
                                            value="{{ old('folder_name', $folder->folder_name) }}" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="description" class="form-label">Description</label>
                                        <textarea class="form-control" id="description" name="description" rows="3">{{ old('description', $folder->description) }}</textarea>
                                    </div>
                                    <div class="mb-3">
                                        <label for="folder_status" class="form-label">Status</label>
                                        <select class="form-control" id="folder_status" name="folder_status" required>
                                            <option value="active"
                                                {{ old('folder_status', $folder->folder_status) == 'active' ? 'selected' : '' }}>
                                                Active</option>
                                            <option value="not_active"
                                                {{ old('folder_status', $folder->folder_status) == 'not_active' ? 'selected' : '' }}>
                                                Not Active</option>
                                        </select>
                                    </div>
                                    <button type="submit" class="btn btn-primary">Update</button>
                                    <a href="{{ route('network-folder.index') }}" class="btn btn-secondary">Cancel</a>
                                </form>
                            @else
                                <p>No network folder record found.</p>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
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

