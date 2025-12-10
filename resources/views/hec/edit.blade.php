@extends('layouts.template')

@section('breadcrumb')
    @include('sweetalert::alert')

    <div class="page-wrapper">
        <div class="content container-fluid">
            <div class="page-header">
                <div class="row">
                    <div class="col-sm-12">
                        <div class="page-sub-header">
                            <h3 class="page-title">Edit Department</h3>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Display validation errors -->
            @if ($errors->any())
                <div class="alert alert-danger">
                    <ul>
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <!-- Form to update hec -->
            <form action="{{ route('hec.update', $hec->id) }}" method="POST">
                @csrf
                @method('PUT')

                <div class="form-group mb-3">
                    <label for="hec_level_name" class="form-label">HEC Levels Name</label>
                    <input type="text" class="form-control" id="hec_level_name" name="hec_level_name"
                           value="{{ old('hec_level_name', $hec->hec_level_name) }}" required>
                </div>

                <div class="form-group mb-3">
                    <label for="descriptions" class="form-label">Description</label>
                    <textarea class="form-control" id="descriptions" name="descriptions" required>{{ old('descriptions', $hec->descriptions) }}</textarea>
                </div>

                <div class="form-group text-end">
                    <a href="{{ route('hec.index') }}" class="btn btn-danger">Cancel</a>
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
@endsection

@section('content')
@endsection
