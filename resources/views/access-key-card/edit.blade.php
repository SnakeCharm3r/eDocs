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
                            <h3 class="page-title">Edit Access Key Card</h3>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-8">
                    <div class="card">
                        <div class="card-body">
                            <form method="POST" action="{{ route('access-key-card.update', $keyCard->id) }}">
                                @csrf
                                @method('PUT')
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="card_number">Card Number <span class="text-danger">*</span></label>
                                        <input type="text" name="card_number" id="card_number" class="form-control" value="{{ old('card_number', $keyCard->card_number) }}" required>
                                        @error('card_number') <span class="text-danger">{{ $message }}</span> @enderror
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="status">Status <span class="text-danger">*</span></label>
                                        <select name="status" id="status" class="form-control" required>
                                            <option value="active" {{ old('status', $keyCard->status) == 'active' ? 'selected' : '' }}>Active</option>
                                            <option value="not_active" {{ old('status', $keyCard->status) == 'not_active' ? 'selected' : '' }}>Not Active</option>
                                        </select>
                                        @error('status') <span class="text-danger">{{ $message }}</span> @enderror
                                    </div>
                                    <div class="col-md-12 mb-3">
                                        <label for="notes">Notes</label>
                                        <textarea name="notes" id="notes" class="form-control" rows="3">{{ old('notes', $keyCard->notes) }}</textarea>
                                        @error('notes') <span class="text-danger">{{ $message }}</span> @enderror
                                    </div>
                                    <div class="col-md-12">
                                        <button type="submit" class="btn btn-success">Update Access Key Card</button>
                                        <a href="{{ route('access-key-card.index') }}" class="btn btn-secondary">Cancel</a>
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

