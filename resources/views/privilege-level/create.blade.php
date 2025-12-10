
@extends('layouts.template')
@section('breadcrumb')
    <div class="page-wrapper">
        <div class="content container-fluid">
            <div class="row">
                <div class="col-sm-12">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Privilege Level</h5>
                        </div>
                        <div class="card-body">
                            <form method="POST" action="{{ route('privilege.store') }}" enctype="multipart/form-data">
                                @csrf
                                <div class="row">
                                    <div class="col-12 col-md-6">
                                        <div class="form-group">
                                            <label for="prv_name">Name <span class="text-danger">*</span></label>
                                            <input type="text" name="prv_name" id="prv_name" class="form-control" required>
                                        </div>
                                    </div>
                                    <div class="col-12 col-md-6">
                                        <div class="form-group">
                                            <label for="status">Status <span class="text-danger">*</span></label>
                                            <select name="prv_status" id="prv_status" class="form-control" required>
                                                <option value="">Select Status</option>
                                                <option value="active" {{ old('prv_status') == 'active' ? 'selected' : '' }}>Active</option>
                                                <option value="not_active" {{ old('prv_status') == 'not_active' ? 'selected' : '' }}>Not Active</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-12 text-right">
                                        <button type="submit" class="btn btn-primary" style="background-color: #00d084; border-color: #00d084;">Submit</button>
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

