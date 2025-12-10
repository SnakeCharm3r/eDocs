@extends('layouts.template')

@section('content')
    <div class="page-wrapper">
        <div class="content container-fluid">
            <div class="row">
                <div class="col-sm-12">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">{{ $policy->title }}</h5>
                        </div>
                        <br>
                        <div class="container">
                            <div class="form-group mb-3">
                                <label for="content">Description</label>
                                <div id="content">
                                    {!! $policy->content !!}
                                </div>
                            </div>
                            <a href="{{ route('policies.index') }}" class="btn btn-secondary">Back</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection