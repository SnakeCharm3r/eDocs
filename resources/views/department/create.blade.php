@extends('layouts.template')
@section('breadcrumb')
    <div class="page-wrapper">
        <div class="content container-fluid">
            <div class="row">
                <div class="col-sm-12">
                    <div class="card">
                        <div class="card-body">
                            <form method="POST" action="{{ route('department.store') }}" enctype="multipart/form-data">
                                @csrf
                                <div class="row">
                                    <div class="col-12">
                                        <h5 class="form-title"><span>Department Details</span></h5>
                                    </div>
                                    <div class="col-12 col-sm-4">
                                        <div class="form-group local-forms">
                                            <label>Department Name <span class="login-danger">*</span></label>
                                            <input type="text" name="dept_name" class="form-control">
                                        </div>
                                    </div>
                                    <div class="col-12 col-sm-4">
                                        <div class="form-group local-forms">
                                            <label>Descriptions <span class="login-danger">*</span></label>
                                            <input type="text" name="description" class="form-control">
                                        </div>
                                    </div>
                                    <div class="col-12 col-sm-4">
                                        <div class="form-group local-forms">
                                            <label>Clinical or Non-Clinical <span class="login-danger">*</span></label>
                                            <select class="form-control" id="clinical_or_non_clinical" name="clinical_or_non_clinical" required>
                                                <option value="">Select Type</option>
                                                <option value="Clinical">Clinical</option>
                                                <option value="Non-Clinical">Non-Clinical</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-12 col-sm-4">
                                        <div class="form-group local-forms">
                                            <label>Department HEC Level <span class="login-danger">*</span></label>
                                            <select class="form-control" id="hec_id" name="hec_id" required>
                                                <option value="">Select HEC Level</option>
                                                @foreach ($hec as $hecs)
                                                    <option value="{{ $hecs->id }}">{{ $hecs->hec_level_name }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>

                                    <div class="col-12">
                                        <div class="student-submit d-flex gap-2">
                                            <a href="{{ url()->previous() }}" class="btn btn-secondary">Back</a>
                                            <button type="submit" class="btn btn-primary">Save</button>
                                        </div>

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
