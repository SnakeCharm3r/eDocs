@extends('layouts.template')

@section('content')
    <div class="page-wrapper">
        <div class="content container-fluid">
            <div class="row">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-body">
                            <h4>Edit Request</h4>
                            <form action="{{ route('request.update', $request->id) }}" method="POST">
                                @csrf
                                @method('PUT')
                                <!-- Add your form fields here -->
                                <div class="form-group">
                                    <label for="request_type">Request Type</label>
                                    <input type="text" name="request_type" value="{{ $request->request_type }}" class="form-control">
                                </div>
                                <div class="form-group">
                                    <label for="status">Status</label>
                                    <input type="text" name="status" value="{{ $request->status }}" class="form-control">
                                </div>
                                <button type="submit" class="btn btn-primary">Update Request</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
