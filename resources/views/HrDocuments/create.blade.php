@extends('layouts.template')
@section('breadcrumb')
    @include('sweetalert::alert')
@endsection

@section('content')
    <div class="page-wrapper">
        <div class="content container-fluid">
            <div class="row">
                <div class="col-sm-12">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="card-title mb-0">Add Document</h5>
                        </div>
                        <div class="card-body">
                            <form action="{{ route('HrDocuments.add') }}" method="POST" enctype="multipart/form-data">
                                @csrf
                                <div class="form-group">
                                    <label for="DocumentName">Document Name</label>
                                    <input type="text" name="DocumentName" id="DocumentName" class="form-control"
                                        required placeholder="Enter the name of the document"
                                        value="{{ old('DocumentName') }}">
                                    @error('DocumentName')
                                        <div class="text-danger">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="form-group">
                                    <label for="DocumentName">Document Description</label>
                                    <input type="text" name="Type" id="Type" class="form-control" required
                                        placeholder="Enter short description of the document" value="{{ old('Type') }}">
                                    @error('DocumentName')
                                        <div class="text-danger">{{ $message }}</div>
                                    @enderror
                                </div>
                                <!-- Document Upload -->
                                <div class="form-group">
                                    <label for="DocumentPath" class="font-weight-bold">Upload Document (PDF) <small
                                            class="form-text text-muted">Only PDF files (max 2MB) are
                                            allowed.</small></label>
                                    <div class="custom-file">
                                        <input type="file" class="custom-file-input" id="DocumentPath"
                                            name="DocumentPath" accept="application/pdf" required>
                                    </div>
                                    @error('DocumentPath')
                                        <div class="text-danger">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="form-group">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save"></i> Submit
                                    </button>
                                    <a href="javascript:history.back();" class="btn btn-secondary">
                                        <i class="fas fa-arrow-left"></i> Back
                                    </a>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
