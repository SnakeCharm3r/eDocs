@extends('layouts.template')
@section('breadcrumb')
    <div class="page-wrapper">
        <div class="content container-fluid">
            <div class="row">
                <div class="col-sm-12">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">HMIS Access</h5>
                        </div>
                        <div class="card-body">
                            <form method="POST" action="{{ route('employment.store') }}" enctype="multipart/form-data">
                                @csrf
                                <div class="row">
                                    <div class="col-12 col-md-6">
                                        <div class="form-group">
                                            <label for="employment_type">Employment Type <span
                                                    class="text-danger">*</span></label>
                                            <input type="text" name="employment_type" id="employment_type"
                                                class="form-control" required>
                                        </div>
                                    </div>
                                    <div class="col-12 col-md-6">
                                        <div class="form-group">
                                            <label for="description">Description <span class="text-danger">*</span></label>
                                            <input type="text" name="description" id="description" class="form-control"
                                                required>
                                        </div>
                                    </div>

                                    <div class="col-12 text-left">
                                        <a href="{{ url()->previous() }}" class="btn btn-secondary"
                                            style="background-color: #6c757d; border-color: #6c757d; margin-right: 10px;">Back</a>
                                        <button type="submit" class="btn btn-primary"
                                            style="background-color: #00d084; border-color: #00d084;">Submit</button>
                                    </div>

                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('content')
@endsection
