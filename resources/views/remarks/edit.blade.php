@extends('layouts.template')

@section('breadcrumb')
    <div class="page-wrapper">
        <div class="content container-fluid">
            <div class="page-header">
                <div class="row">
                    <div class="col-sm-12">
                        <div class="page-sub-header">
                            <h3 class="page-title">Edit Remarks</h3>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-body">
                            @if ($rem)
                                <form action="{{ route('remark.update', $rem->id) }}" method="POST">
                                    @csrf
                                    @method('PUT')
                                    <div class="mb-3">
                                        <label for="rmk_name" class="form-label">Name</label>
                                        <input type="text" class="form-control" id="rmk_name" name="rmk_name"
                                            value="{{ old('rmk_name', $rem->rmk_name) }}" required>
                                    </div>

                                    <div class="mb-3">
                                        <label for="status" class="form-label">Status</label>
                                        <select class="form-control" id="status" name="status" required>
                                            <option value="active"
                                                {{ old('status', $rem->status) == 'active' ? 'selected' : '' }}>Active
                                            </option>
                                            <option value="inactive"
                                                {{ old('status', $rem->status) == 'inactive' ? 'selected' : '' }}>Inactive
                                            </option>

                                        </select>
                                    </div>
                                    <button type="submit" class="btn btn-primary">Update</button>
                                    <a href="{{ route('remark.index') }}" class="btn btn-secondary">Cancel</a>
                                </form>
                            @else
                                <p>No Remark record found.</p>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
@endsection

@section('content')
@endsection
